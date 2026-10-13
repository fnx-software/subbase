# Fenix: organization subscription integration

This fork is intended to serve as a billing **engine**, not as the source of truth for user authentication, organization memberships, or per-product permissions.

## Subscriber identity

Subbase's README uses `HasPlanSubscriptions` on `User` as an example. Fenix Accounts requires subscriptions on **Organization** instead. Verify the underlying `laravelcm/laravel-subscriptions` polymorphic subscriber contract and its migrations before attaching that trait to an organization model. Do not migrate user subscriptions by simply changing a morph type in production.

## Product catalog

CRM, POS, ERP and Zeno may have independent plans. A billing plan needs a stable application identifier; do not infer a product from a plan display name. Keep the application catalog and authorization decisions in Fenix Accounts. Entitlements should always be resolved against the selected organization and application.

## Security and testing gate

- Negative test: a member of organization A cannot read or modify organization B's subscriptions.
- Negative test: a member of A cannot use a CRM entitlement as a POS entitlement.
- Test cancellation, expiration, upgrade/downgrade, renewal and usage reconciliation.
- Use database transactions for subscription transitions; enforce uniqueness/idempotency of external event IDs.
- Verify PostgreSQL migrations, indexes and monetary precision before deployment.

Composer currently uses package name `nafiswatsiq/subbase`. When consuming this fork from a VCS repository, verify Composer resolves the intended source/commit, and do not silently fall back to the upstream package.
