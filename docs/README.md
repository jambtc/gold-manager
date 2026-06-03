# Gold Manager v2 Documentation

This directory contains the planning and governance documentation for the Gold Manager rewrite.

Gold Manager v2 is intended as a modern rewrite of the original PHP 7.4 football manager game. The goal is to preserve the product idea while rebuilding the application with a cleaner architecture, modern UI, explicit domain rules, migrations, tests, and a documented development flow.

## Document map

- `docs/architecture.md` describes the target technical architecture.
- `docs/roadmap.md` defines the staged development roadmap.
- `docs/GAP.md` tracks implementation gaps against the roadmap.
- `docs/game-formulas.md` documents gameplay formulas (players, training, match engine, staff, economy).
- `docs/testing.md` describes the current test database and Unit test setup.
- `docs/sip/` contains Software Improvement Proposals.

## SIP workflow

SIPs are inspired by Bitcoin BIPs. They are used to discuss, approve, implement, and track major changes to the application.

Every relevant feature or architectural decision should have a SIP before implementation when it affects domain rules, database structure, APIs, UI conventions, deployment, security, or development workflow.

## Engine parity rule (PHP vs Go)

For match simulation behavior, PHP engine and Go worker must stay aligned unless a change explicitly requires divergence.

Before every push, run:

```bash
bash v2/scripts/check-engine-parity-pre-push.sh
```

This checks that changes touching `v2/components/MatchEngine.php` are mirrored by corresponding verification/updates in `v2/worker-go/engine/match.go` (and vice versa).

Optional local git hook (recommended):

```bash
chmod +x .githooks/pre-push v2/scripts/check-engine-parity-pre-push.sh
git config core.hooksPath .githooks
```

## i18n source-string rule (mandatory)

All user-facing text must be written using `Yii::t('app', 'English source text')`.

Required conventions:

- Source text inside `Yii::t` must be **English**.
- Do not hardcode Italian (or other locale) strings directly in controllers/views/js templates.
- Translations must be stored in locale catalogues (e.g. `v2/messages/it-IT/app.php`).
- New/edited UI messages must keep placeholders named (e.g. `{name}`, `{amount}`) and consistent across locales.

## SIP index

| SIP | Title | Status |
| --- | --- | --- |
| SIP-0001 | SIP Process and Governance | Accepted (Implemented) |
| SIP-0002 | Target Architecture | Accepted (Implemented) |
| SIP-0003 | Domain Model | Accepted (Implemented) |
| SIP-0004 | Database and Migrations | Accepted (Implemented) |
| SIP-0005 | Authentication and Authorization | Accepted (Implemented) |
| SIP-0006 | UI and UX Design System | Accepted (Implemented) |
| SIP-0007 | Team and Player Management | Accepted (Implemented) |
| SIP-0008 | Competitions, Fixtures and Standings | Accepted (Implemented) |
| SIP-0009 | Economy, Transfers and Contracts | Accepted (Implemented) |
| SIP-0010 | Docker, Environments and Delivery | Accepted (Implemented) |
| SIP-0011 | Match Simulation Engine | Accepted (Implemented) |
| SIP-0012 | AI Controlled Teams | Accepted (Implemented) |
| SIP-0013 | Match Event Sourcing | Accepted (Implemented) |
| SIP-0014 | Notifications and WebSocket Infrastructure | Accepted (Implemented) |
| SIP-0015 | API Versioning | Accepted (Implemented) |
| SIP-0016 | Plugin System | Deferred (Backlog) |
| SIP-0017 | Mobile Application Support | Deferred (Backlog — Final Phase) |
| SIP-0018 | Multiplayer Synchronization | Accepted (Implemented Baseline) |
| SIP-0020 | Real-Time Match Engine Architecture | Accepted (Implemented) |
| SIP-0021 | Go Match Worker (Live Engine) | Accepted (Implemented) |
| SIP-0022 | World Generation and New-Game Seeding | Accepted (Implemented) |
| SIP-0023 | Live Tactical Interventions (Command Channel) | Accepted (Implemented) |
| SIP-0024 | Advanced Match Logic: Character Traits and Half-time | Accepted (Implemented) |
| SIP-0025 | Automatic League Expansion (Infinite World) | Accepted (Implemented) |
| SIP-0026 | AI Match Commentary (Claude Haiku) | Accepted (Implemented) |
| SIP-0027 | Season Rollover | Accepted (Implemented) |
| SIP-0028 | Promotion and Relegation | Accepted (Implemented) |
| SIP-0029 | Admin Panel and Platform Governance | Accepted (Implemented) |
| SIP-0030 | Manager Friendly Matches and Transfer Windows | Deferred (Backlog) |
| SIP-0031 | Dynamic Commentary Enhancements and Visual Goal Celebrations | Accepted (Implemented) |
| SIP-0032 | Formation View Enhancement: Skill Bars and Position Hints | Accepted (Implemented) |
| SIP-0033 | SVG Skill and Position Icon System | Accepted (Implemented) |
| SIP-0034 | Italian Name Database (Legacy Import) | Accepted (Implemented) |
| SIP-0035 | Staff Management v2: Roles, Contracts and Match Bonus | Accepted (Implemented) |
| SIP-0036 | Player Training System: Skill and Tactical Weekly Training | Accepted (Implemented) |
| SIP-0037 | Character Traits: Match and Training Effects | Accepted (Implemented) |
| SIP-0038 | Game Style and Tactical Philosophy | Accepted (Implemented) |
| SIP-0039 | Physical and Tactical Training Balance plus Staff Amplification | Accepted (Implemented) |
| SIP-0040 | Injury System (Infortuni) | Accepted (Implemented) |
| SIP-0041 | Cards System (Cartellini Gialli e Rossi) | Accepted (Implemented) |
| SIP-0042 | Event-Driven Live View: SSE-Only Architecture | Accepted (Implemented) |
| SIP-0043 | Live Substitution UI | Deferred (Backlog) |
| SIP-0044 | Special Roles: Captain, Penalty Taker, Free Kick Specialist | Accepted (Implemented) |
| SIP-0045 | Statistics System: Scorers, Assists, Team Stats | Accepted (Implemented) |
| SIP-0046 | Staff Market: Hiring, Firing and Contract Negotiation | Accepted (Implemented) |
| SIP-0047 | Player Transfer Market v2 plus Generation Engine | Accepted (Implemented) |
| SIP-0048 | Auto Formazione da Modulo + Tattica (v2) | Accepted (Implemented) |
| SIP-0049 | Friendly Match System (Amichevoli Challenge) | Accepted (Implemented) |
| SIP-0050 | News Feed (Notiziario) | Accepted (Implemented) |
| SIP-0051 | CPU AI Behavior | Accepted (Implemented) |
| SIP-0052 | LLM Streaming to Browser | Accepted (Implemented) |
| SIP-0053 | Temporary Manager Test Control: Advance Day | Accepted (Implemented Temporary) |
| SIP-0054 | Daily Training Cycle | Accepted (Implemented) |
| SIP-0056 | Sponsor Management v2 | Accepted (Implemented) |
| SIP-0066 | Unified Commentary Event Stream | Accepted (Implemented) |
| SIP-0067 | Campo Logico 10-Righe (Field Grid Refactor) | Accepted (Implemented) |
| SIP-0068 | Player Experience Ibrida (Quadrante + Cella) | Accepted (Implemented) |
| SIP-0069 | Internationalization (i18n) | Accepted (Implemented) |
| SIP-0070 | Player Nationality & Multi-National Name Database | Accepted (Implemented) |
| SIP-0071 | Auction Market System (Free Agent, Staff, Sponsor) | Accepted (Implemented) |
| SIP-0072 | Repository Migration: gold-manager → gold-manager-v2 | Accepted (Implemented) |
| SIP-0073 | Player Physical Attributes: Height & Weight | Accepted (Implemented) |
| SIP-0074 | Natural Position Overall Rating (OVR) | Accepted (Implemented) |
| SIP-0075 | Set Piece Skill (Calci Piazzati) | Accepted (Implemented) |
| SIP-0076 | Multi-Nation Onboarding + Campionati per Paese | Accepted (Implemented) |
| SIP-0077 | Go Orchestrator Hard-Cut (No Cron, Reset DB) | Accepted (Implemented) |
| SIP-0078 | Prestiti Giocatori (Loan) con Scadenza Reale | Accepted (Implemented) |
| SIP-0079 | Multilingual Match Commentary | Accepted (Implemented) |
| SIP-0080 | Notifiche affidabili (SSE first, Telegram best-effort) | Accepted (Implemented) |
| SIP-0081 | Daily Manager Digest (riepilogo giornaliero) | Accepted (Implemented) |
| SIP-0082 | Gold Manager Coin (GMC) — Token Economy su Besu | Draft |
| SIP-0083 | Team Effort Trigger (0/25/50/75/100) | Accepted (Implemented) |
| SIP-0084 | Smart Contract Suite (GoldManagerCoin + GameEconomy) | Proposed |
| SIP-0085 | Wallet Manager UI (GMC) | Proposed |
| SIP-0086 | PHP ↔ Besu Bridge | Proposed |
| SIP-0087 | Always-On Market & Continuous Play | Accepted (Implemented) |
| SIP-0088 | Sequenza Piazzati PHP (Set Piece Sequence Parity) | Accepted (Implemented) |
| SIP-0089 | Besu Node Infrastructure | Proposed |
| SIP-0090 | Goal Rate Calibration (anti-over-scoring) | Accepted (Implemented) |
| SIP-0092 | PWA + Landing Page | Accepted (Implemented) |
| SIP-0091 | Contract Duration & Termination Fees | Accepted (Implemented) |

## Current verification snapshot

Verified on 2026-05-26:

- Web entrypoint responds through Caddy/Nginx on `http://127.0.0.1:30203/`.
- Application migrations are up to date.
- `./yii import-names/run` imported 1,043 unique first names and 1,849 unique surnames.
- `vendor/bin/codecept run Unit` passes: 40 tests, 640 assertions.
- Go worker compiles with `go test ./...`.
- Go worker now auto-starts due scheduled fixtures, cleans stale live state before kickoff, streams SSE event updates, and applies standings on match end.
- Existing scheduled fixtures are normalized to 15:00 kickoff; no scheduled fixture remains at 00:00.
- Match commentary supports template-based suspense/fallback flow; LLM enrichment is env-toggleable.
- Formation/player UI uses shared SVG icon helpers for role/position/talent rendering.
- Legacy temporary snippet `trait_php_snippet.tmp` is archived after merge verification.

## Branch

These documents are introduced on branch `gold-manager-v2`.
