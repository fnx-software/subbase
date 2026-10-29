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
