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

## 0) Modello attributi (canone)

### 0.1 Skill tecniche giocatore (0..99)

Usate da motore partita e valutazione ruolo:

- `skill_po` (parate)
- `skill_df` (difesa)
- `skill_cn` (contrasti)
- `skill_pa` (passaggi)
- `skill_rg` (regia)
- `skill_cr` (cross)
- `skill_tc` (tecnica)
- `skill_tr` (tiro)

`general_skill = media arrotondata delle 8 skill`.

### 0.2 Stato atletico/forma giocatore

- `form` (forma)
- `freshness` (freschezza)
- `condition` (condizione atletica)
- `experience` (esperienza, non percentuale)

Questi valori modificano direttamente i moltiplicatori in match.

### 0.3 Talenti giocatore

- indipendenti dal carattere
- max `2` talenti per giocatore
- livello `1..3`
- crescita lenta: richiede lavoro prolungato + probabilità

Talenti supportati:

- `creativita`, `resistenza`, `dribbling`, `velocita`
- `visione`, `leadership`, `marcatura`, `riflessi`
- `finalizzazione`, `disciplina`, `tenacia`, `freddezza`
- `calci_piazzati` (nuovo talento esplicito)

### 0.4 Allenamento squadra

- **Fisico/tecnico (allocazioni 0..100, somma max 100):**  
  `alloc_forma`, `alloc_cond`, `alloc_po`, `alloc_df`, `alloc_cn`, `alloc_pa`, `alloc_rg`, `alloc_cr`, `alloc_tc`, `alloc_tr`, `alloc_calci_piazzati`.
- **Tattico (allocazioni 0..100, somma max 100):**  
  `pressing`, `contropiede`, `possesso`, `palla_bassa`, `lancio_lungo`, `catenaccio`, `fuorigioco`.
- `calci_piazzati` non è più tattica live: viene allenato dal blocco fisico/tecnico e alimenta il valore team set-piece.

### 0.5 Tattiche squadra in partita

Stile gara:

- `balanced`
- `ultra_defensive`
- `all_out_attack`

Switch live immediato:

- marcatura `zone/man`
- trappola fuorigioco `on/off`
- focus su tattica allenata (solo elenco tattico sopra, senza `calci_piazzati`)

---

## 1) Migliore posizione in campo (per giocatore)

### 1.1 Rating per zona (`Player::getOverallForPosition`)

Per ogni zona `ord` (attuale 1..63 giocabili) viene caricata la riga coefficienti da tabella `calcolatore` (`formula = "Formula 2"`).

Formula:

`overall_zone = (pdd + po*c_po + df*c_df + cn*c_cn + pa*c_pa + rg*c_rg + cr*c_cr + tc*c_tc + tr*c_tr) / (101 - 15)`

con:

- `pdd` = bonus/malus piede dominante in base alla corsia:
  - corsia sinistra: `R=-6`, `L=+6`, `LR=+4`
  - corsia centrale: `LR=+7`, altrimenti `+4`
  - corsia destra: `R=+6`, `L=-6`, `LR=+4`
- output arrotondato a 1 decimale.

### 1.3 Schema campo: stato attuale vs target

#### Stato attuale (legacy)

- griglia a zone storiche (layout non ancora separato formalmente in 10 righe)
- riga portiere non isolata in modo rigido

#### Target richiesto (prossimo refactor)

Campo logico a **10 righe**:

1. Riga 1: solo portiere (GK-only)
2. Righe 2-4: difesa (3 righe DF)
3. Righe 5-7: centrocampo (3 righe MF)
4. Righe 8-10: attacco (3 righe FW)

Schema concettuale:

```text
R1  [GK-only]
R2  [DF lane L/C/R]
R3  [DF lane L/C/R]
R4  [DF lane L/C/R]
R5  [MF lane L/C/R]
R6  [MF lane L/C/R]
R7  [MF lane L/C/R]
R8  [FW lane L/C/R]
R9  [FW lane L/C/R]
R10 [FW lane L/C/R]
```

Nota migrazione: questa modifica impatta `FormationAutoHelper`, rendering campo (`formation/view`, `fixture/live`, `fixture/replay`), query zone `<=63` in PHP/Go e mapping `Player::getOverallForPosition`.

### 1.2 Auto-formazione (`FormationAutoHelper::autoAssign`)

#### 1.2.1 Regola attiva (ora)

L'auto-formazione e vincolata al modulo con **ruolo hard**:

- slot `GK` -> solo giocatori `GK`
- slot `DF` -> solo giocatori `DF`
- slot `MF` -> solo giocatori `MF`
- slot `FW` -> solo giocatori `FW`

Giocatori infortunati/squalificati sono esclusi automaticamente.

Se il ruolo richiesto non ha abbastanza giocatori disponibili:

- lo slot resta vuoto
- ritorna warning con conteggio ruoli mancanti (`missing_by_role`)

Questo garantisce coerenza modulo (es. `4-3-3` = 4 difensori reali in auto).

Per i candidati dello stesso ruolo:

`score = overall_zone + roleFit + readiness + tacticScore`

assegnazione greedy in ordine decrescente score (1 player per 1 zona).

Componenti attive:

- `roleFit`:
  - GK in ruolo `+25`, fuori ruolo non ammesso
  - DF/MF/FW in ruolo `+10` (fuori ruolo non ammesso in auto)
- `readiness`:
  - `((form-50)*0.08) + ((freshness-50)*0.06) + ((condition-50)*0.06)`
- `tacticScore` (stile gara):
  - `all_out_attack`: `tr/tc/cr`, bonus FW, training `contropiede+palla_bassa`
  - `ultra_defensive`: `df/cn/po`, bonus DF/GK, training `catenaccio+fuorigioco`
  - `balanced`: `pa/rg/cn`, bonus MF, training `possesso+pressing`

#### 1.2.2 Estensione target (prossimo step): peso del focus `trained_tactic`

Il focus tattico allenato (`trained_tactic`) deve entrare nello score auto.

Formula target:

`score_final = score_base + focusScore`

dove:

- `score_base` = formula attiva sopra
- `focusScore` dipende da:
  - tattica allenata (`trained_tactic`)
  - livello allenamento squadra per quella tattica (`0..100`)
  - profilo giocatore (skill, stato atletico, talenti coerenti)

Esempi target:

- `lancio_lungo`: premia `tr`, `tc`, profondita, talento `velocita`, buona `freshness`
- `catenaccio`: premia `df`, `cn`, `po`, `freshness/condition`, talento `marcatura`/`disciplina`
- `pressing`: premia `cn`, `freshness`, `condition`, talento `resistenza`
- `contropiede`: premia progressione verticale, finalizzazione, talento `velocita`
- `possesso` / `palla_bassa`: premia `pa`, `rg`, `tc`, controllo ritmo
- `fuorigioco`: premia linea difensiva disciplinata, `df/cn`, lettura tempi

Nota design: il focus non deve rompere il modulo/ruolo; deve solo ordinare i
giocatori **dentro** i candidati gia validi per ruolo.

#### 1.2.3 Forza squadra teorica (card `formation/view`)

La card "Forza squadra" non e piu una media semplice di `general_skill`.
Usa un calcolo teorico per slot reale:

`slot_score = overall_per_zona * readinessMult * roleFitMult * styleMult * focusMult`

con:

- `overall_per_zona` da `Player::getOverallForPosition(zone)`
- `readinessMult` da `form`, `freshness`, `condition`
- `roleFitMult` severo sui fuori-ruolo (es. FW in slot DF penalizzato forte)
- `styleMult` da stile gara (`balanced`, `ultra_defensive`, `all_out_attack`)
- `focusMult` da tattica allenata + livello (es. `catenaccio` alto premia DF/GK)

Output:

- score reparto (`GK/DF/MF/FW`) = media `slot_score` delle celle di quel reparto
- overall teorico = media pesata dei reparti (pesi dinamici per stile/focus)
- penalty automatico per slot mancanti (`starters/11`)

Effetto atteso:

- schierare giocatori fuori ruolo in reparti critici abbassa sensibilmente la
  forza teorica
- modulo + tattica/focus coerenti (es. `5-3-2` + `catenaccio` alto) alzano la
  forza teorica, a parita di rosa

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
- probabilità base giornaliera tattica (formula attiva da SIP-0067-prep):
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

### 3.4b Allenamento tattico — formula crescita (aggiornata)

`levelMod = max(0.3, 1.0 - (value/100)*0.55)`
`prob = (alloc/100) * 0.035 * levelMod * tacticalStaffMod * dailyScale`

- al livello 0: `levelMod=1.0` → prob massima
- al livello 100: `levelMod=0.3` → prob ridotta del 70%
- decay (no alloc): `max(1, round(150 * dailyScale))`

### 3.5 Precisione finale e piazzati

Base precision cap `30`.

Su piazzati:

- `precisionCapForSetPiece` da `FormationRoleHelper`:
  - `cap = min(60, 30 + int((tc*0.2 + tr*0.3)/10))`
- +bonus livello team `calci_piazzati` (allenato via `alloc_calci_piazzati`)
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
