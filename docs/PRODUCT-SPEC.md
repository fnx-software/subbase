# Universal Subscription Platform — product requirements

Date: 2026-10-29. Approved owner intent: **one maintainable subscription system for every Laravel project** (inside or outside Fenix). The canonical implementation is this Subbase fork paired with `fnx-software/subbase-payment` for optional checkout.

## Definition of done

A supported Laravel app can install the packages, select an Eloquent billable model and a billing policy, create plans, subscribe, invoice, charge, renew, upgrade, downgrade, cancel, pause, resume, refund and reconcile — **without any Fenix dependency**. A Filament UI is optional for operating the service; API and background processing remain reusable.

### Plan catalog and pricing
- Free, paid, trial, recurring, fixed-term, lifetime and manually granted plans.
- Plan features, quotas, unlimited vs finite limits, per-organization seat counts, usage counters and time-period metering with atomic consumption.
- Plan variants per currency and billing interval; effective-dated price versions and locked price snapshots after purchase.
- Upgrades, downgrades, scheduled switches, renewal, cancellation at period end, grace periods and proration (explicit calculation and rounding policy).
- Promotions/coupons: percentage, fixed amount, redemption caps, date ranges, allowed plans/currencies, per-customer limits and concurrency safety.
- Use exact decimal/integer money representations; ISO 4217 scales; **locale must never determine the customer's currency automatically without an explicitly chosen policy**.

### Subscribers and tenancy
- Subject is any persisted Eloquent model (`User`, `Team`, `Organization`, `School`, `Tenant`, custom).
- Permit multiple named subscriptions per subject; no hidden global-subscriber assumption.
- Resource permissions, relationship queries, URL binding, aggregates, files, jobs, events, callbacks and plan changes must be tenant-safe.
- Support shared database, database-per-tenant and single-tenant apps through host-defined adapters. Do not require a particular tenancy package.
- One application may run local subscriptions. Another may call a central billing service with explicit external ID mappings and signed API; centralization is opt-in, not a hard dependency.

### Payments and invoicing
- Gateway-neutral checkout and verified payment lifecycle, customer portal, invoice/receipt emails, webhook logs, refunds, disputes/chargebacks, retries and backoff.
- Hosted gateways (Stripe, PayPal, Paddle, Midtrans, Xendit) with explicit supported country/currency checks and plug-in custom drivers.
- Offline payments: record/approve and audit; no false provider capture.
- Idempotent, asynchronous payment confirmation must control subscription activation. Do not grant paid entitlements on frontend redirect.
- Scheduled billing and recurring mandate collection only when gateway policy permits; dunning, retry schedules, customer notice, tax and FX accounting snapshots.
- Never store full payment card information; preserve compliance and receipt/credit-note history.

### UI, localization and APIs
- Laravel 13 / PHP 8.4 reference baseline, Filament 5 operational panel, optional checkout Blade/Flux UI presentation, headless API adapters.
- RTL/LTR, many locales, initial ar/en/fr. Prices are data, not locale-derived guesses. Format dates/timezones and numbers per user separately from billing currency.
- Tenant and admin dashboards: plan CRUD, subscribers, usage/limits, payments, subscriptions, disputes, reconciliation, audit and provider health.
- Signed webhooks or authenticated APIs for cross-service entitlement checks; TTL/caching with fail-closed revocation behavior.

### Operational guarantees
- Postgres and MySQL supported after integration tests; SQLite only for lightweight package tests.
- No `float` for posting; no destructive rewrite of invoices or money; scoped unique indexes; transactional row locking; event/outbox/retry guarantees.
- Package semantic versioning, real migrations and upgrade/rollback plans, CI compatibility suite, Composer audit, Pint and Laravel tests.
- Stable releases must be tested with a standalone demo app and existing real consumers CRM and Zeno before rollout.
- Configuration defaults must be safe. Explicit permissions and billable membership authorization. No default admin user/password.

## Boundary between the two packages

**Subbase:** plans, features, subscriptions, entitlements, usage, discounts and lifecycle.

**Subbase Payment:** checkout, provider drivers, signed webhooks, payment history, refunds, settlement and receipts. The gateway plugin must never become an untrusted shortcut to marking subscriptions paid.

External business applications (POS, ERP, school finance, Invoice customer collections) retain **their own financial ledgers and domain records**; do not double-count those as SaaS subscription payments.

## Delivery stages

| Stage | Scope | Acceptance gate |
| --- | --- | --- |
| 0 — Stabilize | permissions, precise package test harness, documented APIs | tests, audit, explicit denials |
| 1 — Generic billable | user/organization/school/tenant, scoping, adapter contracts | two-tenant isolation tests and checkout owner tests |
| 2 — Financial precision | money, price snapshots, discounts, taxes and proration | currency-specific reference calculations |
| 3 — Full lifecycle | renewal, switch, cancellation, pauses, trials, seats and usage | lifecycle/concurrency integration tests |
| 4 — Payment production | idempotent payment ledger, refunds, disputes, gateway sandbox | end-to-end confirmed settlements and failed-payment recovery |
| 5 — Distribution | Composer package identity, upgrade from upstream, releases | CRM/Zeno migration + demo app verified |
| 6 — UX and internationalization | Filament and optional checkout UI, 100+ locale architecture | ar/en/fr and RTL visual/functional QA |

**No stage is complete because its specification exists.** Status is tracked separately in `docs/PROGRESS.md`.
