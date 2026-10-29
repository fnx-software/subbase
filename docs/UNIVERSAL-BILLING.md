# Universal subscription platform — package contract

**Status:** Fenix-owned upstream fork, reusable independently of Fenix Accounts. This document is a versioned project direction, not a claim of feature completion.

## Product principles

- No mandatory Fenix Account, ZITADEL, Fenix organization schema, or Fenix-specific global scopes.
- One subscription engine across Laravel products: local installation per app or a dedicated subscription service via an optional API adapter.
- Billable subjects can be **any Eloquent model** using `Laravelcm\Subscriptions\Traits\HasPlanSubscriptions`, including User, Organization, School, Tenant, or Project. The current model relationship comes from laravelcm; the package should not create another subscription model merely for tenant support.
- Optional Filament 5 management UI. Headless usage remains valid; future versions may extract core billing without Filament.
- The same independent package supports single-tenant and multi-tenant applications. **Filament authorization does not by itself isolate tenant records**; applications must provide tenant-aware query scoping and policies.
- Subbase handles subscription plans, features, trial periods, lifecycle, discounts, plan currency prices and usage counters.
- Subbase Payment handles gateway checkout, verification, subscription settlement, payment history and refunds; the **consumer application's financial ledger** remains separate for non-subscription charges.

## Security and money rules

- Permission checks are fail-closed through Laravel Gate, whether or not Spatie Permission is installed. Filament resource policies must use the actual actor, not a session-global assumption.
- A role/permission grant does **not** confer access to a different tenant's record. Each app must implement record-level tenant scope; a reusable tenant-resolver contract with strict fail-closed defaults is on the roadmap.
- Never use PHP floating-point numbers for financial posting. Replace Plan/Discount float calculations with exact decimal arithmetic in a separately versioned migration and API upgrade, followed by rounding fixtures for 0/2/3 decimal currencies.
- Payment gateways must verify raw webhook signatures, event ID, account/merchant, transaction ownership, amount and currency before settling subscriptions. Deduplicate events atomically.
- Preserve historical subscription, price, payment and discount snapshots after they are sold.

## Distribution and upgrade strategy

- For compatibility, initial patches **retain** the `nafiswatsiq/subbase` Composer package identity and `Nafiswatsiq\Subbase\` PHP namespace. The fork is at `fnx-software/subbase`.
- Review and test future distribution under `fnx-software/subbase` with a documented Composer migration. Do not ship both identities in the same app or silently mark them as replaced.
- Keep existing production installs of CRM and Zeno on pinned versions until the compatibility suite passes.
- A matching release of `fnx-software/subbase-payment` must be tested with every breaking Subbase release.
- Keep MIT license and original author attribution.

## Supported modes (design target)

| Mode | Owner | Access / subscription lifecycle |
| --- | --- | --- |
| Standalone user SaaS | User | Local auth; user subscriptions |
| B2B SaaS | Organization or Tenant | App-specific membership policies and tenant resolver |
| School SaaS | School | School billing owner, not necessarily creator user |
| Central billing service | Custom business subject | API with explicit external identity references |
| Fenix Apps | Organization | Optional Fenix Accounts adapter; zero core dependency |

## Roadmap

1. Fail-closed resource permissions and PHP/Filament 5 Testbench suite.
2. Tenant-aware resource and action authorization with a configurable resolver and explicit denial when enabled but unresolved.
3. Model-agnostic subscription checkout and safe renew/switch/cancel with idempotency and lifecycle tests.
4. Accurate money/discount math, subscription pricing snapshots and multiple currency scales.
5. Gateway abstraction, verified webhooks, payment ledger, refunds, chargebacks, retries and reconciliations.
6. Dashboard and public checkout UX, languages ar/en/fr, headless APIs, package distribution, semver, migration guide.
7. Tested integrations with a standalone demo application, Zeno, CRM and optional Fenix Accounts.

## No API / migration claims yet

Version 2 is not released. No installed app is migrated by this branch. Run `composer test`, Composer audit, package migrations and a real application integration suite before merging.

### Legacy styling gate

The upstream fork currently has 64 pre-existing Pint style violations across 152 PHP files. This feature branch lints only new helper + tests; a separate formatting-only PR will make full-repo `pint --test` mandatory before the first Fenix major release. Formatting has no semantic intent.
