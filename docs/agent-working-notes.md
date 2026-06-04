# Agent Working Notes

This document tracks the agreed working mode, current context, and next priorities for agentic work on Gold Manager v2.

## Branch

Active branch:

```text
gold-manager-v2
```

## Agreed agentic working rule

The assistant may proceed autonomously on the `gold-manager-v2` branch for:

- repository analysis;
- documentation;
- SIP creation and updates;
- roadmap updates;
- backlog updates;
- bug audits;
- technical proposals;
- small non-destructive refactoring;
- changes clearly aligned with already proposed SIPs;
- commits on the working branch.

The assistant must stop and ask for confirmation before:

- deleting important files;
- changing database schema or migrations;
- modifying authentication, authorization, security, payment, wallet, or production configuration;
- large runtime refactoring;
- merge, rebase, force push, or release operations;
- adding new external dependencies;
- high-impact gameplay/runtime changes.

## Current documentation state

The active documentation structure is:

```text
docs/
├── README.md
├── roadmap.md
├── backlog.md
├── architecture.md
├── testing.md
├── game-formulas.md
├── repository-migration-runbook.md
├── agent-working-notes.md
├── sip/
└── archive/
    └── GAP-2026-05-19.md
```

`docs/GAP.md` was archived and removed from active planning. The historical snapshot is preserved in `docs/archive/GAP-2026-05-19.md`.

## Repository migration focus

SIP-0072 covers extracting `v2/` from the current monorepo into a new dedicated repository named:

```text
jambtc/gold-manager-v2
```

A migration runbook is available at:

```text
docs/repository-migration-runbook.md
```

The next manual step is to run `git filter-repo` in an isolated local clone, because GitHub API operations cannot rewrite repository history in the same way.

After the migration, the new repository should have the former `v2/` contents at root, plus restored root-level assets such as `docs/`, `docker/`, `docker-compose.yml`, and `start-queue.sh` when present.

## Recently added SIPs

- SIP-0095 Documentation Governance and SIP Verification Audit
- SIP-0096 i18n Compliance Enforcement
- SIP-0097 Go Engine Test Coverage Framework
- SIP-0098 Dynamic Transfer Market and Rival Bidding
- SIP-0099 Daily Engagement Loop
- SIP-0100 Objectives, Missions and Achievements
- SIP-0101 Rivalries and Manager Reputation
- SIP-0102 Dynamic News and Narrative Engine
- SIP-0103 Youth Academy and Primavera
- SIP-0104 Market Pressure and Urgency Mechanics

## Current roadmap focus

Recommended implementation order after repository migration:

1. SIP-0097 Go Engine Test Coverage Framework
2. SIP-0102 Dynamic News and Narrative Engine
3. SIP-0103 Youth Academy and Primavera
4. SIP-0104 Market Pressure and Urgency Mechanics
5. SIP-0096 i18n Compliance Enforcement
6. SIP-0095 Documentation Governance and SIP Verification Audit

## Rationale

SIP-0072 should be completed before new feature development so the active v2 codebase becomes the canonical repository and future commits do not continue accumulating under a legacy monorepo layout.

SIP-0097 protects the realtime match engine and reduces regression risk before future gameplay changes.

SIP-0102 and SIP-0103 offer the strongest perceived gameplay value because they turn existing systems into stories and long-term attachment.

SIP-0104 increases daily market activity and login frequency.

SIP-0096 and SIP-0095 keep the project maintainable as documentation and UI surfaces grow.

## Open analysis topics

- Complete SIP-0072 repository migration and verify the new repository root layout.
- Audit current Go worker tests and identify first test files to add.
- Audit existing NewsService and determine how much of SIP-0102 can reuse current code.
- Compare SIP-0093 and SIP-0103 to avoid duplicate Youth Academy scope.
- Identify i18n hardcoded strings in controllers, services, views and JS.
- Verify whether SIP-0098 and SIP-0104 should remain separate or be merged later.

## Safety note

All implementation work should be done incrementally, preferably with one SIP-focused commit series at a time.
