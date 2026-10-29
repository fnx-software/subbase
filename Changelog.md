# Changelog

## Unreleased (2026-10-29) — Universal billing foundation

- Kept Subbase independent of Fenix Accounts for all Laravel applications.
- Hardened SubbasePermission: no implicit allow when Spatie is absent; native Laravel Gate is always checked.
- Passed the requesting authorization actor into Plan, Subscription and Discount policies (no Auth global mismatch).
- Added Testbench/PHPUnit authorization regression tests (5 passing on PHP 8.4 / Laravel 13).
- Added initial CI with Composer validation, scoped Pint and dependency audit.
- Documented broad product requirements, independent subscriber models, upgrade strategy and roadmap.

### Breaking behavior and outstanding work

Existing apps that relied on permissive missing-Spatie access will be denied until they grant explicit Laravel Gate abilities. No existing app has been upgraded. Tenant record scoping and financial decimal precision are **not yet implemented**. A stable release and new Composer name are pending.

## 2026-10-29 — Exact money, tenancy and package ownership

- Added high-precision BCMath `Money` and currency scale policy; implemented exact checkout pricing alongside legacy presentation methods.
- Widened Plan/Discount columns and pivot to decimal(20,4) using a forward-only upgrade migration; tested migrations/pricing on isolated SQLite.
- Introduced optional fail-closed host-defined `TenantAccess`, scoped Filament resources and record-aware policies.
- Changed feature-branch Composer identity to `fnx-software/subbase` while preserving existing PHP namespace.
- Expanded Testbench suite to 19 tests/38 assertions (CI run 40838411564), with follow-up installation/upgrade tests still required.
- Added `docs/INSTALLATION.md` for safe path-repo sandbox integration.

**Not yet released.** Multi-tenant end-to-end coverage, recurring billing, version tags, CRM/Zeno migration, and complete formatting/DB matrix are outstanding.
