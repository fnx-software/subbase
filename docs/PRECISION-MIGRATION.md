# Exact currency precision upgrade — safety rules

The fork now stores canonical currency price strings in JSON and offers `Plan::getPriceDecimalForCurrency` and `PlanPriceHelper::resolveExactWithDiscounts`. Legacy float-returning public methods remain for presentation/backward compatibility, **not payment posting**.

A new widening migration `database/migrations/upgrade_billing_decimal_columns.php` increases Plan/Discount/Pivot numeric columns to decimal(20,4). It must run **after laravelcm and the existing Subbase migrations**. If this package publishes migrations manually, publish this new migration after existing ones. Do not execute it until schema/migration integration tests prove its order, table names, indexes and running data.

After rollout, all priced JSON values are canonical decimal strings; existing historical float JSON remains readable but may have been rounded when originally stored. Never silently rewrite financial history. Confirm two-tenant isolation and a backup before migration.

**No destructive down migration**: narrowing precision would truncate live KWD/CLF values. A rollback requires an explicit data-preserving migration and backup.

UI and legacy `calculateDiscount(float,...)` still use floats for display; checkout must use the new exact quote method and must block checkout when the matching billing package isn't installed.

This migration is not yet verified on production PostgreSQL or MySQL.
