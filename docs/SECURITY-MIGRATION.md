# Permission hardening: upgrade note

The original Subbase permission helper returned `true` when Spatie Permission was absent, regardless of authenticated status/assigned abilities. That behavior was insecure for any billing admin panel.

**This branch intentionally changes it to deny by default** using Laravel's native `Gate::forUser(...)->allows(...)`. This should be treated as a breaking behavior change pending a new major version.

## Legacy consumers

Do not deploy before confirming the app already grants the configured ability names. The legacy fallback for blank `subbase.permissions.plan` uses Shield-like names such as `ViewAny:Plan`, `Create:Plan`, and `View:Subscription`; Spatie/Shield may register those through Laravel Gate.

Without Spatie, define the abilities in your AuthServiceProvider or app boot:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('manage billing plans', fn ($actor): bool => $actor->is_billing_admin);
```

Then set `subbase.permissions.plan` to `manage billing plans`, and likewise for other resources. Ensure more specific per-record checks for tenant data; this package does not yet provide automatic tenant scoping.

## Smoke tests

1. Unauthenticated and authenticated unauthorized users cannot open plans/subscription pages.
2. Authorized user sees only records allowed by the application's tenant policy.
3. With Spatie removed, native Laravel Gate can grant an explicit permission.
4. Passing a different actor to policy must not use the logged-in session actor.
5. Existing installations' plan/feature/subscription records remain unchanged.

No migrations are introduced by this change.
