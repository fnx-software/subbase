# FNX Subbase — installation and integration guide

**Development branch only (2026-10-29).** This is not an approved production release. The default branch has the upstream Composer name; local path setup below deliberately uses the new feature branch identity.

## 1. Safe local prototype installation

From a fresh Laravel 13 + PHP 8.4 application with PostgreSQL (or isolated SQLite test database), clone **the requested feature branch** inside the application's `packages` directory:

```bash
mkdir -p packages
git clone --branch feat/universal-billing-foundation https://github.com/fnx-software/subbase.git packages/subbase
composer config repositories.fnx-subbase --json '{"type":"path","url":"packages/subbase","options":{"symlink":true,"versions":{"fnx-software/subbase":"dev-feat/universal-billing-foundation"}}}'
composer require 'fnx-software/subbase:dev-feat/universal-billing-foundation' -W
php artisan subbase:install
php artisan migrate
```

Review the generated migrations before running them in existing projects. The package uses `laravelcm/laravel-subscriptions`, Spatie Package Tools and Filament 5. It now requires `ext-bcmath`. Once the default branch and releases are migrated, use tagged stable Composer versions instead of these feature branches.

## 2. Choose a billable model

Any persisted Eloquent model may own a subscription; this is the existing upstream trait:

```php
use Illuminate\Database\Eloquent\Model;
use Laravelcm\Subscriptions\Traits\HasPlanSubscriptions;

final class Organization extends Model
{
    use HasPlanSubscriptions;
}
```

Create a plan with the Subbase Filament resources, then the authorized host application can create a subscription:

```php
$organization->newPlanSubscription('default', $plan);
```

**Caution:** This call grants access immediately. It is suitable only for free/manual/admin-authorized subscriptions. For paid plans, use the companion payment package's **verified webhook** flow, not direct subscription creation after browser redirect.

## 3. Single-tenant versus multi-tenant

For single-tenant/local user subscriptions the default is `subbase.tenancy.enabled = false`.

For multi-tenant applications, implement `Nafiswatsiq\Subbase\Contracts\TenantAccess` in the host application and configure:

```php
'tenancy' => [
    'enabled' => true,
    'access' => \App\Billing\TenantBillingAccess::class,
],
```

Your implementation must scope `Builder` by the **authenticated actor's authorized tenant(s)** and check record-level access. If this feature is enabled with an unresolvable contract, queries and record permissions deny by default. This is *not* a substitute for host route policies and cross-tenant API/job/export isolation.

## 4. Financial accuracy

Use `Nafiswatsiq\Subbase\Support\Money` for arithmetic and `PlanPriceHelper::resolveExactWithDiscounts($plan, 'USD')` for checkout. Never use the older `float` presentation helpers to post payments. `Money` accepts and returns canonical decimal strings; `ext-bcmath` is required.

The widening migration is intentionally non-reversible without a backup. Confirm precision and current production data before upgrading.

## 5. How to use independent projects / Fenix

- Standalone projects use their own users/organizations and database.
- Fenix SaaS apps can instead call a central billing API through an **optional app-level adapter**; that adapter is not in the package core.
- The package still exports `Nafiswatsiq\Subbase\...` PHP classes and `subbase` configuration keys, even though feature branch Composer name is now `fnx-software/subbase`.

## 6. Testing, not production usage

```bash
composer validate --no-check-publish --strict
composer audit
vendor/bin/phpunit
vendor/bin/pint --test
```

These commands are intended for the package checkout. Full Pint may report **pre-existing upstream violations** outside the new code. The feature-branch CI gates added files but a full-formatting PR is needed for stable release.

Do not replace `nafiswatsiq/subbase` in CRM or Zeno with this feature branch until audited migration and fixture tests pass.

## 7. Authorized subscription cancellation (new)

`Nafiswatsiq\Subbase\Services\SubscriptionAdministration` provides a DB-transactional cancellation operation that is denied unless the authenticated actor is authorized by Laravel Gate *and*, if enabled, by the tenant access resolver:

```php
use Nafiswatsiq\Subbase\Services\SubscriptionAdministration;

// Define Gate/policy 'manageSubscription' for the billable Organization.
app(SubscriptionAdministration::class)->cancel(
    billable: $organization,
    actor: $request->user(),
    subscriptionName: 'default',
    immediately: false, // default: cancel at end of paid period
);
```

By design, this does **not** initiate a gateway refund or modify historical payment records. Use Subbase Payment's eventual verified refund workflow for that separately. Do not bypass the Gate in host controllers or jobs.


## 8. Optional licensed-app reseller channel

After installing the Subbase package, run its additive `subbase:upgrade --migrations` to publish the new licensing migrations, **inspect timestamps and run only on a backed-up staging database**. The authenticated retail/wholesale/purchase/activation endpoints are provided by the companion `fnx-software/subbase-payment` package. Provide the required platform-level Gates and company/user billable resolver in your application (Fenix Accounts/ZITADEL is an optional host integration).

See [License Key & Provider specification](LICENSING-RESELLERS.md) for commerce rules, 12-key CRM example, Filament resources, safe API, required permissions and release blockers.
