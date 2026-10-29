# Implementation backlog (Issues disabled on this GitHub fork)

Last updated: 2026-10-29. This is the durable backlog while GitHub Issues cannot be created. The active draft PR is https://github.com/fnx-software/subbase/pull/2.

## P0 — Ship a secure, generic subscription core

- [ ] Tenant resolver that scopes **queries and record actions**, and fails closed if explicitly enabled but unresolved.
- [ ] Model-agnostic Eloquent billable owner and named subscriptions; no mandatory Fenix identity.
- [ ] Tenant/actor authorization integration tests for Filament resource, relation managers and API.
- [ ] Exact money arithmetic replacing float, ISO 4217 currency scale, plan/discount/FX snapshots.
- [ ] Concurrent discount redemption and subscription usage quota locking.

## P1 — Full product lifecycle

- [ ] Trial, grace, renewal, scheduled switch, pause, resume, cancel, subscriptions per seat or metered usage.
- [ ] Plans, add-ons, bundles, featured/discounted pricing, promotions and tax configuration per country.
- [ ] Full event/audit history, job retries and safe reconciliation with Subbase Payment.
- [ ] Localization ar/en/fr, RTL, headless APIs and optional Filament/Flux checkout.

## P2 — Distribution and adoption

- [ ] Maintenance release policy and new Composer identity `fnx-software/subbase`; preserve old aliases with tested upgrade guide.
- [ ] Full Pint baseline cleanup (upstream 64 issues) and end-to-end multi-database compatibility suite.
- [ ] Test existing CRM and Zeno records without changing their installed package until adoption approved.
- [ ] Standalone demo app (User), B2B demo app (Organization), school app (School), central service optional.
- [ ] Tag stable release only after the critical security/financial gates pass.

See `docs/PRODUCT-SPEC.md` for full acceptance criteria.
