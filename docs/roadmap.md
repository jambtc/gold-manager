# Gold Manager v2 Roadmap

> Aggiornato: 2026-05-19 — Stato reale verificato sul codice

## Phase 1 - Discovery ✅

- [x] Analyze legacy codebase (GU Classic, oldGM, newGM)
- [x] Map database structure
- [x] Identify gameplay rules
- [x] Extract reusable assets (nomi, cognomi, skill icons, position icons)
- [x] Define migration strategy

## Phase 2 - Core architecture ✅

- [x] Docker multi-container (PHP, Nginx, MariaDB, Redis, Caddy, Go, Ollama, Queue workers)
- [x] Yii2 Basic structure
- [x] Authentication + ruoli (admin/manager)
- [x] Migrations framework
- [x] CI/CD pipeline
- [x] Unit test DB separato (`gold_manager_test`) + mock user seed

## Phase 3 - Core domain ✅

- [x] Teams, Players, Competitions, Fixtures, Standings
- [x] Formation & FormationSlot — griglia 7×9 colonne (63 zone)
- [x] Match engine PHP — tick-based, 5-step action pipeline (SIP-0020)
- [x] MatchEvent log — goal, save, near-miss, sub, tactic, half_time, full_time
- [x] MatchState — live state, pending actions, half_time_ticks
- [x] GameController — `game/run-fixtures`, `game/simulate-fixture`, `game/friendly`
- [x] World generation — WorldSeeder, 3 serie (A/B/C), gironi automatici (SIP-0022)
- [x] Registrazione utente → auto-assegnazione squadra Serie C
- [x] Admin panel — statistiche, gestione manager, eliminazione utenti

## Phase 4 - Economy systems ✅

- [x] Contracts & salaries
- [x] Transfer market (list + instant buy)
- [x] Staff roles reali (Head Coach, Fitness Coach, Scout) + efficiency/specialisation
- [x] Stadium capacity, upgrades, match-day revenue
- [x] Budget management
- [x] Economy console commands: `pay-wages`, `credit-match-revenue`, `season-rollover`
- [x] PlayerValuator — market value calculation
- [x] player_stat table — goal scorer tracking per fixture

## Phase 5 - UX ✅

- [x] Dark UI — glassmorphism, gold accents, CSS variables
- [x] Dashboard manager — classifica, prossima partita, statistiche
- [x] Formation view — griglia 7×9, jersey SVG drag&drop, calcolatore forza
- [x] Player card — stats, skill bars, valore di mercato
- [x] Login/Register — stile coerente, tema dark
- [x] User profile page — `/user/profile`
- [x] Admin panel — leagues overview, job monitor, amichevole
- [x] Bootstrap Icons locali (no CDN)
- [x] SVG campo di calcio con strisce erba, linee, porte

## Phase 6 - Go Live Engine ✅

- [x] Go microservice worker — multi-match parallel simulation
- [x] SSE streaming hub — `/stream/{fixtureId}`
- [x] Auto-start fixture scadute — `status=0` con `match_date <= now`, cleanup stato/eventi stale
- [x] SSE live updates — evento base + `event_update` dopo arricchimento LLM
- [x] Live Tactical Interventions — `MatchCommand` table (SIP-0023)
- [x] Advanced Match Logic — character traits, half-time (SIP-0024)
- [x] Live view — SSE + polling fallback, suspense reveal 3s, team badge, animazioni
- [x] Replay view — playback con orologio animato, velocità configurabile
- [x] Caddy reverse proxy — `/stream/*` → Go, `/ollama/*` → Ollama

## Phase 7 - AI Commentary & Match Engine Enhancements ✅

- [x] LlmCommentary — Ollama qwen2:1.5b streaming, modello `telecronista`
- [x] LlmEnrichJob — arricchimento asincrono via queue (SIP-0026)
- [x] SimulateMatchJob — simulazione in background, 2s/tick, Go non interferisce
- [x] Amichevole manager — FriendlyController, lista avversari con forza per reparto
- [x] Amichevole admin — form squadre con serie, SimulateMatchJob
- [x] 2 queue worker containers in parallelo (simulazione + LLM)
- [x] MatchEngine robusto — `save(false)`, `strtolower(phase)`, window ≥45 half-time
- [x] Go match finalization — risultati fixture e classifica aggiornati a fine partita
- [x] Suspense text pre-shot, reveal 3s dopo il risultato
- [x] player_stat tracking — scorer da finalizeMatch (SIP-0028 prep)
- [x] Commentary fallback DB templates + LLM toggle ENV (SIP-0062)
- [x] Commentary SSE unificato (`commentary_event` start/token/done) + metadata minuto/tipo (SIP-0066)

## Phase 8 - Calendar, Standings & Season Management 🚧

- [x] Calendario con navigazione per giornata (andata/ritorno, prev/next)
- [x] Tab Campionato / Amichevoli separati
- [x] Classifica con selettore competizione + badge "TU" sulla propria squadra
- [x] Multi-tier leagues A/B/C con espansione automatica (SIP-0025)
- [x] Season rollover console base — age, contract expiry, standings reset, stadium reset (SIP-0027 partial)
- [x] Season rollover completo core — standing archive, new fixture calendar, budget allocation (SIP-0027)
- [x] Promotion/Relegation hook nel rollover (swap con slot CPU target, pre-reset standings)
- [x] Promotion & Relegation completa + notification/UI (SIP-0028)
- [x] Transfer windows — pre-season e winter (SIP-0030)
- [x] Manager friendly match (SIP-0030) — con aggiornamento stat giocatori
- [x] Notification system per promozione/retrocessione
- [x] Tabellino marcatori in live/replay/dettaglio fixture (SIP-0063)
- [x] CPU AI behavior: transfer/staff/training/friendly/tattiche/sub automatiche (SIP-0051)

## Phase 9 - Formation UX & Legacy Data 📋

- [x] Skill bars per Forma/Freschezza/Condizione come % (SIP-0032)
- [x] Position suggestion basata su stat — corsa→fascia, regia→centro (SIP-0032)
- [ ] SVG icon system per posizioni e abilità speciali (SIP-0033)
- [x] Import nomi/cognomi italiani dal legacy — 1,043 nomi unici, 1,849 cognomi unici (SIP-0034)
- [x] Special skill badges nel player card (SIP-0032, SIP-0033)

## Phase 10 - Training, Staff & Tactical Depth ✅

- [x] Staff v2 base: efficiency, specialisation, hire/fire UI (SIP-0035 partial)
- [x] Training System UI: slider fisici (10 skill) + slider tattici (8 tattiche) (SIP-0036 partial)
- [x] Training system E2E (controller alias, budget 100, daily apply, tactic plan persistence) (SIP-0036)
- [x] Character Traits completi: effetti in partita + effetti in allenamento (SIP-0037)
- [x] Tactical Philosophy: pressing, possesso, lancio lungo, catenaccio, fuorigioco (SIP-0038)
- [x] Ciclo settimanale: fisico vs tattico, staff amplification, fatica, penalità match-week (SIP-0039)
- [x] Training progress charts: snapshot settimanali, delta badges, top improvers (SIP-0061)

## Live UX Follow-up ✅

- [x] Live substitution UI (panchina modal, roster endpoint, dual write MatchState+MatchCommand) (SIP-0043)

## Phase 11 - Injuries & Discipline ✅

- [x] Injury system: lieve/medio/grave, recovery via doctor efficiency (SIP-0040)
- [x] Yellow/red cards: accumulation, suspension, ejection 10-men (SIP-0041)
- [x] Formation view: block injured/suspended players
- [x] Live view: 🚑 injury event, 🟨🟥 card events, 10-men indicator
- [x] EconomyController: weekly recovery + pre-fixture suspension check

## Phase 12 - Social & Multiplayer 🚧 (ex Phase 10)

- [ ] In-game Chat & Private Messages
- [ ] User Profiles & Trophies
- [ ] Clan/Federation system
- [ ] Mobile API (SIP-0017)
