# SIP-0095: Documentation Governance and SIP Verification Audit

- Status: Proposed
- Type: Process / Quality
- Created: 2026-06-04
- Author: ChatGPT

## Summary

This SIP introduces a recurring documentation governance process to keep `docs/README.md`, `docs/roadmap.md`, `docs/GAP.md`, SIP files, and the real implementation state aligned.

The current documentation set contains useful information, but some documents are more recent than others. In particular, `README.md` and `roadmap.md` mark several features as implemented while `GAP.md` still reports older backlog or partial states. This creates uncertainty when deciding what should be developed next.

## Motivation

Gold Manager v2 is now driven by many SIPs. As the number of SIPs grows, documentation must become a reliable source of truth.

The project needs a lightweight but mandatory process to answer these questions:

- Which SIPs are implemented?
- Which SIPs are only proposed or deferred?
- Which roadmap items are verified by code, tests, or migrations?
- Which documented gaps are obsolete and should be closed?
- Which implementation claims are not yet covered by tests?

Without this process, future work may duplicate existing features, ignore real defects, or incorrectly assume that a requirement is complete.

## Scope

This SIP covers:

- SIP status governance.
- Roadmap and GAP synchronization.
- Verification snapshots.
- Evidence-based implementation tracking.
- Documentation cleanup rules.

It does not implement gameplay features directly.

## Proposed Status Model

Every SIP must use one of these statuses:

- `Draft`: idea not yet ready for implementation.
- `Proposed`: ready for discussion and planning.
- `Accepted`: approved but not yet implemented.
- `Accepted (Partial)`: partially implemented, with explicit remaining tasks.
- `Accepted (Implemented)`: implemented and verified.
- `Deferred`: intentionally postponed.
- `Rejected`: not planned.
- `Superseded`: replaced by a later SIP.

## Verification Evidence

A SIP can be marked `Accepted (Implemented)` only when at least one of the following exists:

- Controller/service/model code implementing the feature.
- Database migration or schema evidence.
- Unit/functional test coverage.
- Manual verification note with date and command/result.
- UI route/view evidence where relevant.

For high-risk systems such as match simulation, economy, transfers, auctions, season rollover, and realtime streaming, code evidence alone should not be enough. Tests or explicit manual QA notes should be required.

## Required Documentation Changes

When a SIP status changes, the following files must be reviewed:

- `docs/README.md`
- `docs/roadmap.md`
- `docs/GAP.md`
- the SIP file itself
- any related formula or testing documentation

If a gap is resolved, it must be moved to a `Resolved` section with date and evidence.

## Verification Command

Introduce a lightweight documentation audit command or script:

```bash
bash v2/scripts/check-docs-consistency.sh
```

The script should check at least:

- all SIPs listed in README exist as files;
- all SIP files have a status line;
- no SIP has conflicting statuses across docs;
- `GAP.md` does not mark as missing a SIP that README marks implemented;
- implementation claims that mention tests reference an actual test command or result.

## Acceptance Criteria

- A new docs consistency script exists.
- README, roadmap, and GAP use the same SIP statuses.
- Obsolete GAP entries are moved to resolved or removed.
- Every implemented SIP has an evidence section or verification reference.
- Documentation audit is mentioned in the pre-push/development workflow.

## Risks

The process must not become too heavy. The goal is to avoid stale documentation, not to block development with bureaucracy.

## Follow-up Work

- Add documentation audit to CI.
- Add a generated SIP index.
- Consider a machine-readable SIP registry in YAML or JSON.
