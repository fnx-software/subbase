# FNX Subbase — verified development progress

Updated: 2026-10-29. **Draft PR**: https://github.com/fnx-software/subbase/pull/2

## Architecture decision

Subbase and its companion Subbase Payment are the **one owned subscription stack for ALL Laravel projects**, inside or outside Fenix. A separate central Fenix Accounts adapter is optional, NEVER part of the core dependency. Supports Eloquent billables such as User, School, Organization, Tenant. Does not replace application-specific Invoice/POS/School finance ledgers.

## Implemented on feat/universal-billing-foundation

- [x] Composer package identity changed to `fnx-software/subbase` **on the feature branch**, PHP namespace remains `Nafiswatsiq\Subbase\` for existing source integrations.
- [x] Laravel Gate denies unauthorized access even if Spatie Permission is not installed. Policies use explicit actor; unauthorized users cannot automatically manage plans.
- [x] Optional tenant access resolver, fail-closed if enabled but unavailable, Filament Plan/Subscription/Discount query, record and route scoping.
- [x] Immutable BCMath `Money` value object with zero/two/three/four decimal currency precision and valid percent calculations.
- [x] Accurate `Plan::getPriceDecimalForCurrency()` / `PlanPriceHelper::resolveExactWithDiscounts()` for checkout alongside legacy display APIs.
- [x] Canonical decimal string price storage, `decimal:4` model casts, and forward-only widening migration for plan/discount/pivot fields. Avoid destructive down migrations.
- [x] Tested price migration and applied discounts in Laravel/Testbench, initially using SQLite.
- [x] PHP 8.4/Filament 5/Laravel 13 compatible CI running Composer validate/update, PHPUnit, scoped Pint and Composer audit.
- [x] [Last known green code run](https://github.com/fnx-software/subbase/actions/runs/40838411564): **19 tests / 38 assertions**; Composer rename itself requires latest CI confirmation.
- [x] Product architecture, developer usage, staged upgrade and implementation backlog documented.

## Remaining before stable release

- [ ] Prove compatibility with real CRM and Zeno subscription data/migrations on PostgreSQL; keep their installed production package untouched.
- [ ] Full multi-tenancy isolation across relationship managers, actions, jobs, APIs and background processes; host must implement tenant-aware access.
- [ ] Finish scheduled recurring billing, trial/renewal/upgrade/downgrade, cancellation, grace, seat and usage quota enforcement with concurrency tests.
- [ ] Add optional headless core / Filament-independent package mode (Filament is currently in Composer `require`).
- [ ] Full UI/multi-language/RTL review, pricing and tax rule certification as applicable.
- [ ] Multi-DB matrix, backups and migration rollback, full legacy Pint cleanup (upstream baseline 64 violations), production/performance tests.
- [ ] Merge PR, align default-branch Composer identity, tag release and validate Packagist distribution.
- [ ] Real payment provider lifecycle; that work belongs to the companion package.

## Distribution warning

The current `main` branch still uses `nafiswatsiq/subbase` as the Composer package identity. **Do not assume `composer require fnx-software/subbase` resolves publicly yet.** A safe feature-branch evaluation uses a *path* repository as documented in `docs/INSTALLATION.md`, and never an existing production app.

## Continuation

Read `AGENTS.md`, `docs/PRODUCT-SPEC.md`, `docs/INSTALLATION.md`, `docs/PROGRESS.md`, `docs/BACKLOG.md` and `docs/PRECISION-MIGRATION.md`. Keep PR Draft until financial and tenant gates pass. Update `Changelog.md` for every substantive change.
