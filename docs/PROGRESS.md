# Subbase fork — progress

Date: 2026-10-29. Product target: one standalone billing package across all Laravel applications, with an optional companion Subbase Payment package.

## Current milestone: Stage 0 / Stabilize

- [x] Identified `fnx-software/subbase` fork; original upstream `nafiswatsiq/subbase` (MIT).
- [x] Created `feat/universal-billing-foundation` feature branch; no modifications to `main`.
- [x] Implemented a fail-closed native Laravel Gate authorization helper with optional Spatie integration.
- [x] Policy methods use the provided actor and accept generic `Authenticatable` contracts.
- [x] PHPUnit/Testbench tests added for permission denial and explicit grant; PHP 8.4 Laravel 13 tests observed passing (5/5).
- [x] Composer audit and scoped new-file formatting checked in the feature branch's CI run; this is **not** a full-repo audit of business behavior.
- [x] Documented independent Eloquent subscription owners and the no-Fenix-dependency decision.
- [ ] Complete reusable tenant-aware resource scoping and signed API adapters.
- [ ] Verified installation + upgrade in CRM and Zeno; do not change their installed packages yet.
- [ ] Full exact-decimal money and currency-scale refactor; currently original Plan/Discount floats are a known problem.
- [ ] Lifecycle/proration/usage/seat plans and end-to-end payment integration.
- [ ] Stable Composer identity as `fnx-software/subbase` and coordinated upgrade of the companion package.
- [ ] Full Pint baseline; 64 pre-existing style issues in upstream repo require a formatting-only PR.
- [ ] Multi-version PHP/Laravel/Filament test matrix, release and production readiness.

## Integration principles

1. Standalone app: `User` or any custom Eloquent model with `HasPlanSubscriptions`.
2. Organization SaaS: use an authorized `Organization`/Tenant model; no user-as-tenant fallback.
3. Fenix Apps: Fenix Accounts adapter is optional and lives outside package core.
4. Payment plugin adds checkout; no paid entitlement until server-verified settlement.
5. Do not merge a breaking authorization or financial migration change until consumer apps and update guides are tested.

## Continue next

Read `AGENTS.md`, `docs/PRODUCT-SPEC.md`, `docs/UNIVERSAL-BILLING.md`, `docs/SECURITY-MIGRATION.md` and this document. Add tests for each feature, link issues/PRs and update Changelog and this file with actual verification. Keep `main` unchanged until reviewed.
