# License keys & authorized providers — universal reseller specification

**Status:** Implemented as a development feature on `feat/universal-billing-foundation`. Do **not** use with production payments until the companion package and actual provider sandbox flows have been audited.

## Commercial example and responsibilities

Fenix sets `LicenseOffer.retail_price = 150.0000 USD`, `wholesale_price = 100.0000 USD`, `wholesale_minimum = 12`, `term_count = 1`, `term_unit = year`; the offer belongs to `LicenseProduct.sku = crm` and the `crm-individual` Subbase plan.

An approved electronics-store Provider purchases **12 license keys for 1,200 USD total** and resells to customers at **140 USD each**, independently collecting the customer's payment. The Provider's **gross margin is 40 USD per sold key**, 480 USD if all twelve are sold, before taxes, processing fees, returns, and other business costs. The customer saves 10 USD versus the official 150 USD retail price. The reseller's declaration of resale price is **not proof of receipt or settlement**.

There are two channels:

- `retail`: the end buyer pays official retail for **exactly one license key** through the checkout.
- `wholesale`: an approved Provider, whose owning organization/user is the authorized purchaser, buys a quantity at or above the offer's minimum (12 in this example). Wholesale prices and inventory are privileged.

**No recurring reseller commission or payout** is owed by the platform. Reseller profit is the difference between independent retail proceeds and its paid wholesale acquisition cost. A wholesale purchase does **not** grant CRM access to the reseller organization; the ultimate buyer receives the access on redemption.

## Database ownership

Subbase package owns the catalog and licensing business logic. The companion Subbase Payment package owns collection and gateway verification.

| Table | Responsibility |
|---|---|
| `subbase_license_products` | Named app/product, stable slug (`crm`, `invoice`, `pos`) |
| `subbase_license_offers` | App + plan + currency + retail/wholesale prices + minimum quantity + key lifetime |
| `subbase_license_providers` | Provider application, owning Eloquent model, admin approval/suspension |
| `subbase_license_orders` | Purchase intent, provider/purchaser, channel, currency, quantity, immutable price **and term** snapshots, payment link |
| `subbase_license_keys` | One encrypted 128-bit random token per paid unit, SHA-256 lookup, redemption/owner/plan audit |
| `subbase_license_sales` | Provider's optional, independently reported resale price and sale reference |

Foreign keys enforce core relations. The order's `contract_product_sku`, `contract_plan_id`, `contract_term_count`, `contract_term_unit` remain fixed even when an administrator updates or discontinues its offer. Historical plan deletion/price changes **must never silently destroy sold entitlements**.

Migration order:
1. Existing core `laravelcm/laravel-subscriptions` and Subbase migrations.
2. `create_license_catalog_and_providers_table.php`.
3. `create_license_orders_and_keys_table.php`.
4. `add_license_order_contract_snapshots_table.php` (forward-only, backfills original prototype orders).

The Subbase `subbase:install` and `subbase:upgrade --migrations` commands publish these additively. **Inspect timestamp order, make a verified backup, and test on a clone before migrating any existing app.**

## Provider lifecycle

1. An authorized Fenix user logs into the **host's Fenix Accounts/ZITADEL** app and selects an organization; the host supplies its **authorized** Eloquent purchaser via `CheckoutBillableManager`.
2. `POST /subbase-payment/licenses/providers` creates one pending Provider owned by that Eloquent account (an organization or another persisted billable). The host Gate `applyForLicenseProvider` must enforce actual membership.
3. A platform billing administrator reviews the application and approves it through Filament **License Providers**, using `LicenseProviderService::approve`. The `manageLicenseProviders` ability is required.
4. Only an approved Provider with Gate `purchaseWholesaleLicenses` can view wholesale prices and create a quantity-eligible wholesale order.
5. `LicenseOrderService::place` calculates the **server-defined** unit price and amount. Client-supplied prices are ignored. It snapshots plan, currency, prices, reseller and term, with a unique idempotency key.
6. `LicenseCheckoutService` in Subbase Payment starts a gateway checkout. Payment return URLs are not proof of settlement.
7. Only a **verified signed gateway webhook**, with matching currency, transaction, amount and purchase metadata, can fulfill a paid order. A DB transaction issues unique encrypted keys; replay cannot issue another 12.
8. The Provider exports **available** codes through `GET /subbase-payment/licenses/orders/{order}/keys`, protected by Gate `viewPurchasedLicenseKeys`, optional tenant access policy and no-store headers. Never expose full codes via Filament data tables or logs.
9. Provider sells via their own physical store/site/cash/transfer channels; `POST /subbase-payment/licenses/keys/{key}/resale` records the reported resale price for their dashboard, subject to the default retail cap.
10. A customer signs in to Fenix App with an authorized destination organization and redeems `POST /subbase-payment/licenses/redeem`; Gate `redeemLicenseKey` must enforce permission on that destination. The license creates/extends the `license-crm` Subbase subscription for one calendar year **from redemption** (or appends to an existing future period).
11. Duplicate redemption is rejected. The ciphertext is wiped on redemption, the key is marked redeemed, the subscription and redeemer remain auditable. Suspending a Provider does **not** retroactively invalidate legitimate paid customer keys.

## Services and authorization

Core services (namespace `Nafiswatsiq\Subbase\Services`):
`LicenseProviderService`, `LicenseOrderService`, `LicenseFulfillmentService`, `LicenseInventoryService`, `LicenseRedemptionService`, `LicenseResaleService`, `LicenseProviderDashboard`.

The host application **must** define and test these Laravel Gate abilities:
`applyForLicenseProvider` (owner), `manageLicenseProviders` (billing administrator), `purchaseWholesaleLicenses` (Provider), `purchaseRetailLicense` (buyer), `viewPurchasedLicenseKeys` (own paid order), `viewLicenseOrder` (own order), `redeemLicenseKey` (destination org), `recordLicenseResale` (own Provider), `viewLicenseProviderDashboard` (own Provider), `manageLicenseCatalog` and `viewLicenseOrders` (platform administrator).

Any enabled `subbase.tenancy.enabled` requires an implementation of `Nafiswatsiq\Subbase\Contracts\TenantAccess`; otherwise access fails closed. These Gates and the tenant resolver must check **actual session actor, organization membership, panel and roles**, never merely the supplied `provider_id`, `owner_id` or ID in request JSON. The package does not create or guess your Fenix Account or ZITADEL IDs.

The trusted method `LicenseFulfillmentService::fulfillPaidOrder` is intentionally **not exposed as a public endpoint**. Calling it directly is only valid from a payment-verification handler or a separately audited admin grant workflow.

## Filament 5 resources

Registered in `SubbasePlugin`: License Products, License Offers, License Providers (approve/suspend), License Orders (read-only), License Keys (read-only masked tail/status). Admin resources are gated and tenant-scoped. Provider storefront and customer authentication UI are **host-specific**; the reusable package exposes API endpoints through the companion Payment package.

## Security and operational limits

- Keys are generated using 16 cryptographically random bytes = **128-bit entropy** and stored encrypted. The plaintext cannot be reconstructed from the SHA-256 lookup hash. The `APP_KEY` must be protected; rotation needs a planned re-encryption strategy for unsold keys.
- Enforce HTTPS, CSRF protections on web routes, secret masking, rate limits, no plaintext logging/analytics, restricted inventory exports and employee access audits. Keys are bearer credentials until redeemed: anyone possessing an unused key can activate it for an account they control, so protect physical distribution.
- A reseller may report a price lower than wholesale (negative margin) to reflect its real loss. The default MSRP ceiling is configurable (`subbase.licensing.reseller_max_retail_price`) rather than hardcoded. It is not a legal price-fixing recommendation; evaluate applicable resale-price rules and avoid imposing unlawful restrictions.
- The system currently **does not** enforce resale channel customer identity, reissue stolen codes, disputes, chargeback refund/revocation, cancellation of unpaid orphan checkout, statutory invoicing/VAT, geographic restrictions, reseller contract signing, multiple currencies of exchange, or fraud scoring. These need a separate approved implementation.
- Total order and margin values are **per currency**; do not add USD and LBP without a fixed exchange-rate snapshot.
- The buyer must redeem a key in the **same central billing service/database** that issued it. Other Fenix apps query central entitlements through a future integration adapter; installing this package independently in two app databases does **not** synchronize license keys.
- Real paid end-to-end provider sandbox/PG/MySQL integration, tenant/provider authorization, browser UI and compliance approval are release blockers. PRs remain Draft; do not enable live payment credentials.

## Worked verification

Unit and integration tests cover 12×100 USD wholesale pricing, order idempotency, key uniqueness/encryption, single redemption, activation from redemption date, extension of an active plan, immutable contract terms, reseller 140 USD price/40 USD gross margin, and fail-closed tenancy. The companion tests the full paid gateway webhook issuance without creating a reseller subscription.
