# Gold Manager v2 Documentation

This directory contains the planning and governance documentation for the Gold Manager rewrite.

Gold Manager v2 is intended as a modern rewrite of the original PHP 7.4 football manager game. The goal is to preserve the product idea while rebuilding the application with a cleaner architecture, modern UI, explicit domain rules, migrations, tests, and a documented development flow.

## Document map

- `docs/architecture.md` describes the target technical architecture.
- `docs/roadmap.md` defines the staged development roadmap.
- `docs/sip/` contains Software Improvement Proposals.

## SIP workflow

SIPs are inspired by Bitcoin BIPs. They are used to discuss, approve, implement, and track major changes to the application.

Every relevant feature or architectural decision should have a SIP before implementation when it affects domain rules, database structure, APIs, UI conventions, deployment, security, or development workflow.

## Initial SIP index

| SIP | Title | Status |
| --- | --- | --- |
| SIP-0001 | SIP process and governance | Draft |
| SIP-0002 | Target architecture | Draft |
| SIP-0003 | Domain model | Draft |
| SIP-0004 | Database and migrations | Draft |
| SIP-0005 | Authentication and authorization | Draft |
| SIP-0006 | UI/UX design system | Draft |
| SIP-0007 | Team and player management | Draft |
| SIP-0008 | Competitions, calendar, matches and standings | Draft |
| SIP-0009 | Economy, market and contracts | Draft |
| SIP-0010 | Docker, environments and delivery | Draft |

## Branch

These documents are introduced on branch `gold-manager-v2`.
