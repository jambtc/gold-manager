# Gold Manager v2 — Formule Tecniche di Gioco

Questo documento riassume le formule attive nel codice per:

- scelta posizione/miglior ruolo del giocatore
- allenamento fisico/tattico/talenti
- simulazione partita (PHP engine / Go parity target)
- impatto staff
- valore mercato e altre variabili che influenzano il gameplay

Riferimenti principali:

- `v2/models/Player.php`
- `v2/components/FormationAutoHelper.php`
- `v2/components/MatchEngine.php`
- `v2/commands/EconomyController.php`
- `v2/components/PlayerAttributeHelper.php`
- `v2/models/Staff.php`
- `v2/components/PlayerValuator.php`
- `v2/components/ScoutingService.php`
- `v2/models/Stadium.php`

---

## 1) Migliore posizione in campo (per giocatore)

### 1.1 Rating per zona (`Player::getOverallForPosition`)

Per ogni zona `ord` (1..64) viene caricata la riga coefficienti da tabella `calcolatore` (`formula = "Formula 2"`).

Formula:

`overall_zone = (pdd + po*c_po + df*c_df + cn*c_cn + pa*c_pa + rg*c_rg + cr*c_cr + tc*c_tc + tr*c_tr) / (101 - 15)`

con:

- `pdd` = bonus/malus piede dominante in base alla corsia:
  - corsia sinistra: `R=-6`, `L=+6`, `LR=+4`
  - corsia centrale: `LR=+7`, altrimenti `+4`
  - corsia destra: `R=+6`, `L=-6`, `LR=+4`
- output arrotondato a 1 decimale.

### 1.2 Auto-formazione (`FormationAutoHelper::autoAssign`)

Per ogni coppia `(player, zone_template)`:

`score = overall_zone + roleFit + readiness + tacticScore`

poi assegnazione greedy in ordine decrescente score (1 player per 1 zona).

Componenti:

- `roleFit`:
  - GK in ruolo `+25`, fuori ruolo `-25`
  - DF/MF/FW in ruolo `+10`, adattati da ruolo vicino `+1/+2`, fuori `-8`
- `readiness`:
  - `((form-50)*0.08) + ((freshness-50)*0.06) + ((condition-50)*0.06)`
- `tacticScore` (dipende da stile scelto):
  - `all_out_attack`: usa `tr/tc/cr`, bonus ruolo FW, bonus training `contropiede+palla_bassa`
  - `ultra_defensive`: usa `df/cn/po`, bonus ruolo DF/GK, bonus training `catenaccio+fuorigioco`
  - `balanced`: usa `pa/rg/cn`, bonus ruolo MF, bonus training `possesso+pressing`

---

## 2) Allenamento giornaliero (skill/tattica/talenti)

Codice: `EconomyController::actionApplyWeeklyTraining()` (ora con cadenza daily-scaled).

### 2.1 Vincolo allocazioni UI

- Allenamento fisico: somma slider max `100` (`TrainingController::actionSaveSkill`).
- Piano tattico: somma slider max `100` (`TrainingController::actionSaveTactic`).

### 2.2 Crescita skill giocatore

Per ogni skill tecnica con allocazione `pts > 0`:

- XP teorico loggato:
  - `xp = (pts/100) * 10 * ageMod * charMod * staffMod * skillMod * dailyScale`
- Probabilità incremento `+1`:
  - `p = (pts/100) * 0.08 * ageMod * charMod * staffMod * skillMod * dailyScale`
  - roll su 10000.

Modifier:

- `ageMod = max(0.3, 1 - max(0, (age-25)*0.02))`
- `charMod` da `CharacterTraitHelper::trainingModifier(...)`
- `staffMod = 1 + sum(eff/1000)` per `head_coach + assistant_coach + fitness_coach`
- `skillMod`:
  - base `assistantSkillMod = 1 + ((assistantEff*0.05)/100)`
  - per portieri: ulteriore `gkSkillMod = 1 + ((gkCoachEff*0.15)/100)`

Se `pts = 0`:

- possibile decay `-1` (solo se stat > 30), chance daily-scaled.

### 2.3 Forma, condizione, freschezza

- Forma gain: `rollScaledPositive(alloc_forma * 0.08, dailyScale)`
- Condizione gain: `rollScaledPositive(alloc_cond * 0.08, dailyScale)`

Load:

- `physicalLoad` da alloc fisiche
- `tacticalLoad` da alloc piano tattico
- `totalLoad = round((physicalLoad + tacticalLoad)/2)`

Delta freschezza base:

- `totalLoad >= 80 => -3`
- `>= 50 => -1`
- `>= 20 => +2`
- `< 20 => +5`

Correzioni:

- `diligente`: ulteriore `-2` freschezza
- fitness coach riduce perdita (fino a +2)
- se fitness coach presente: +1 freschezza extra

Penalty pre-partita (`matchSoon` entro 3 giorni e load > 60):

- `formPenalty = ((totalLoad - 60) * 0.1) * dailyScale`

### 2.4 General skill

Ricalcolo a ogni update:

`general_skill = round((po+df+cn+pa+rg+cr+tc+tr)/8)`

### 2.5 Crescita talenti

Codice: `PlayerAttributeHelper::progressTalentsDaily`.

Regole:

- talento cresce solo se allocazione correlata `> 80`
- probabilità base settimanale:
  - `weeklyChance = base + bonus_surplus`
  - `base` da env `GM_TALENT_GROWTH_CHANCE_BASE` (default 35)
  - `maxBonus` da env `GM_TALENT_GROWTH_CHANCE_MAX_BONUS` (default 20)
- chance giornaliera:
  - `dailyChance = round(weeklyChance * talentDailyScale)`
  - default `talentDailyScale = 1/7` (env `GM_TALENT_DAILY_SCALE`)
- avanzamento livello dopo `weeksPerLevel` step (env `GM_TALENT_WEEKS_PER_LEVEL`, fallback stagione).

---

## 3) Formula partita (engine)

Codice: `MatchEngine::resolveTick`.

Pipeline:

1. Cartellini
2. Infortuni
3. Duello centrocampo / bypass lancio lungo
4. scelta lato attacco (L/C/R)
5. attacco vs difesa
6. tiro vs portiere
7. gate precisione (open play / piazzato)
8. evento finale (goal / near_miss / gk_save / rumore)

### 3.1 Moltiplicatore individuale

`playerMult = effortMult * formMult * freshMult * condMult * charBonus`

- `effortMult`: `{1:0.50, 2:0.75, 3:1.00, 4:1.25, 5:1.50}`
- `formMult = (form/100)*0.30 + 0.70`
- `freshMult = (freshness/100)*0.15 + 0.85`
- `condMult = (condition/100)*0.15 + 0.85`
- `charBonus` dipende da tratto (es. `grintoso` in svantaggio, `inflessibile` in/out ruolo, `irrequieto` casa/trasferta, ecc.)

### 3.2 Totali squadra

Per ogni starter:

- ogni skill contribuisce con `skill * playerMult`
- attacco laterale `at_L/at_C/at_R` derivato dalla colonna zona.

Effetti extra:

- capitano `carismatico`: aura ai compagni (`+1%`, +adiacenza)
- squadra in 10 (`ejected`): riduzione ~9% su blocco outfield
- marcature uomo (`ManMarking`):
  - marker attivo: bonus difesa
  - giocatore marcato: malus output offensivo

### 3.3 Profili tattici e conflitti

`buildFormationTacticProfile` applica moltiplicatori:

- stile: `balanced / ultra_defensive / all_out_attack`
- focus su tattica allenata (`trained_tactic` + livello)
- `marking`, `offside_enabled`, `longball_mult`, ecc.

Conflitti (`applyTacticConflicts`):

- `pressing > 50` e `catenaccio > 50` -> pressing * 0.70
- `lancio_lungo > 50` e `possesso > 50` -> possesso * 0.60

### 3.4 Duello centrocampo e fasi offensive

Bypass lancio lungo:

- `bypassChance = round((lancio_lungo * 0.20) * longball_mult)` (clamp 0..100)

Se non bypass:

- `mid = (cn+rg+pa) * (1 + pressing*0.08/100) * (1 + possesso*0.10/100) * mid_mult`

Duello:

- `duel(a,b) = rand(0,a) > rand(0,b)`

Attacco vs difesa:

- `atkForce = (at_side + cr*0.3) * counterBonus * attack_mult`
- `counterBonus = 1 + contropiede*0.15/100` (se contropiede)
- `defForce = (df + cn*0.3) * (1 + catenaccio*0.12/100) * (1 - palla_bassa*0.05/100) * def_mult * markingModifier`

Fuorigioco:

- `offsideChance = fuorigioco * 0.15 * offside_mult`

Tiro vs portiere:

- `shotPower = tc*0.4 + tr*0.6`
- `gkPower = po*2.0 + df*0.2`

### 3.5 Precisione finale e piazzati

Base precision cap `30`.

Su piazzati:

- `precisionCapForSetPiece` da `FormationRoleHelper`:
  - `cap = min(60, 30 + int((tc*0.2 + tr*0.3)/10))`
- +bonus `calci_piazzati`
- +bonus focus tattico `calci_piazzati`
- bonus razionale sul rigorista.

Se roll > cap -> `near_miss`, altrimenti `goal`.

### 3.6 Cartellini e infortuni

Cartellini (`resolveCards`):

- giallo con disciplina modifier
- doppio giallo -> rosso
- rosso diretto con chance separata
- friendly: no cards.

Infortuni (`resolveInjuries`):

- chance base per tick + modifier carattere/età/condizione
- severità lieve/medio/grave con settimane associate
- friendly: no injuries.

---

## 4) Staff: formule attive

### 4.1 Efficienza staff

`efficiency = round((0.9 * ability * motivation / 100) + (experience / 8))`

### 4.2 Staff su training

Da `EconomyController`:

- `staffMod` globale fisico: somma efficienze scalate
- `tacticalStaffMod` da head coach
- `assistantSkillMod` da assistant coach
- `gkSkillMod` da goalkeeping coach
- fitness coach influenza fatigue/freshness.

### 4.3 Staff su recupero infortuni

`applyWeeklyRecovery`:

- senza doctor: `injury_weeks - 1`
- con doctor: `injury_weeks - 2`

### 4.4 Scout

`ScoutingService`:

- giorni report: `ceil(7 - scout_eff/20)` (da ~5 a ~1)
- rumore valutazione: `noise = round(30 - scout_eff*0.25)` (5..30)

---

## 5) Economia che impatta scelte tecniche

### 5.1 Valore mercato giocatore

`marketValue = general_skill * basePerSkill`
`* ageFactor * positionFactor * condMult * (1 + expBonus)`

dove:

- `condMult = (form/100)*0.2 + (freshness/100)*0.1 + (condition/100)*0.1 + 0.60`
- `expBonus <= +15%`

Salario suggerito:

- `suggestedSalary = round(marketValue * 0.10)`

### 5.2 Stadio e ricavi

- `matchRevenue = capacity * attendanceFactor * ticket_price`
- upgrade:
  - capacità `+5000` per livello
  - costo upgrade `* 1.8`

---

## 6) Parametri ENV principali (tuning)

- `GM_TRAINING_DAILY_SCALE`
- `GM_TALENT_DAILY_SCALE`
- `GM_TALENT_WEEKS_PER_LEVEL`
- `GM_TALENT_GROWTH_CHANCE_BASE`
- `GM_TALENT_GROWTH_CHANCE_MAX_BONUS`
- (commentary) `GM_LLM_*`, `GM_COMMENTARY_STREAM_*`

---

## 7) Nota operativa importante

Per logica partita, mantenere parity PHP/Go:

```bash
bash v2/scripts/check-engine-parity-pre-push.sh
```

Se si cambia formula in `v2/components/MatchEngine.php`, verificare equivalente in `v2/worker-go/engine/match.go`.
