# Subbase package contribution rules

Goal: one reusable subscription engine for standalone Laravel and all organization/tenant SaaS apps, **without mandatory Fenix Accounts dependencies**.

- Read `docs/PRODUCT-SPEC.md`, `docs/PROGRESS.md`, `docs/UNIVERSAL-BILLING.md`, `docs/SECURITY-MIGRATION.md` before modifying business rules.
- Detect actual PHP, Laravel, Filament and laravelcm/laravel-subscriptions versions before using version-specific APIs.
- Keep existing Composer and PHP namespaces stable until a coordinated versioned migration; no silent renames or destructive data conversion.
- Keep subscription business operations in tested actions/services; UI and API delegate. Prefer Eloquent relations, explicit indexes, transactions and locking.
- Never conflate authorization with authentication. Explicitly scope both the selected billable and all record/read-model queries to the permitted tenant.
- Use exact decimal / integer-scaled monetary computations and currency scales, never float for financial truth.
- Keep any model-translations package choice an internal implementation detail; do not make all host apps use Spatie or Astrotomic.
- No dependencies on Fenix, ZITADEL, a particular tenancy package or a fixed `User` model.
- Every important change needs tests: unauthenticated, unauthorized, cross-tenant, concurrency, invoice/payment consistency and migration compatibility.
- PHP: strict types for new files, Laravel Pint, PSR-12 where compatible. Legacy file formatting is a separate mechanical PR.
- Preserve MIT license and original upstream authorship. Maintain `docs/PROGRESS.md` and `Changelog.md` per PR.
- No release or main-branch merge without a green CI, a compatibility demo and clearly identified breaking changes.
