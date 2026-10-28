# Tiara Tax — Sprint 1 foundation

This package provides a **draft workspace**, not a compliant invoice issuance system.
No invoice can be issued, numbered, paid, printed or submitted to ZATCA in this sprint.
No Phase 2 service, credential, XML, device, submission table or queue is included.

## Implemented behavior

- Legal seller records with Arabic name, VAT format validation and structured Saudi address.
- SAR-only invoice drafts with standard/simplified profiles and ordinary/advance/final purposes.
- Stable UUID, optimistic edit versions, duplicate-create detection and transactional audit events.
- Manual drafts or conversion from an accessible **accepted** Catering Proposal.
- Proposal conversion uses commercial `quote_items`; included/descriptive menus are excluded.
- The original proposal, items, totals, snapshot and exports are never written by this module.
- Original proposal data/revision/fingerprint is captured for provenance. Its adjustment is displayed for review; it is not silently discarded into a calculated tax total.
- Draft input amounts are exact decimal strings normalized with the existing Brick Math dependency. No statutory calculations or rounding rules are implemented yet.
- Tax classification may remain blank while drafting. When supplied, standard rates must be positive; Z/E/O require a zero rate and explicit treatment code/reason. These are consistency checks, not regulatory validation of a category or code.
- Seller/buyer snapshots are independent of later master-data changes. Explicitly saving a draft refreshes its seller snapshot; it never refreshes from an edited source proposal.
- All draft screens clearly disclose their unissued status. No tax total, approval or clearance is fabricated.

## Files

New domain files live under `packages/Webkul/Tax/src/`:

- `Providers/{TaxServiceProvider,ModuleServiceProvider}.php`
- `Database/Migrations/2026_10_28_000001_create_tax_foundation_tables.php`
- `Contracts/`, `Models/` and model proxies for the nine entities below.
- `Repositories/{TaxDocumentRepository,TaxSellerRepository}.php` (reads; writes go through services).
- `Services/{TaxAccess,SellerService,DraftService,DraftLineNormalizer,ProposalToDraft}.php`
- `Validation/{DraftRules,SellerRules,FieldLabels}.php`

New Admin files:

- `packages/Webkul/Admin/src/Routes/Admin/tax-routes.php`
- `packages/Webkul/Admin/src/Http/Controllers/Tax/{DraftController,SellerController}.php`
- `packages/Webkul/Admin/src/Resources/views/tax/documents/{index,form,items}.blade.php`
- `packages/Webkul/Admin/src/Resources/views/tax/sellers/{index,form}.blade.php`
- `packages/Webkul/Admin/src/Resources/views/tax/partials/{notice,field}.blade.php`
- `packages/Webkul/Admin/src/Resources/lang/{en,ar}/tax.php`

Existing integration files modified: root `composer.json`, `config/app.php`, `config/concord.php`, Admin `Config/{acl,menu}.php`, Admin `Routes/Admin/web.php`, and `Quote/Models/Quote.php` (only a taxDocuments relationship).

Tests: `phpunit.tax.xml`, `tests/Tax/{TaxTestCase,FoundationTest,AuthorizationAndViewsTest,ProposalConversionTest,MigrationSafetyTest}.php`, plus an isolated layout fixture.

## Tables and relationships

| Table | Purpose |
| --- | --- |
| tax_sellers | Legal seller/branch settings; one seller has many drafts and sequences |
| tax_document_sequences | Future controlled numbering state; no rows allocated in Sprint 1 |
| tax_documents | Draft identity, parties, source, profile, purpose, nullable future issued fields |
| tax_document_items | Charge lines, decimal inputs, nullable line VAT/calculated amounts |
| payments | Future independent receipt/deposit/refund records, not tied mandatorily to an invoice |
| payment_allocations | Future receipt/refund application and reversal entries |
| tax_document_links | Future advance application and original-note references |
| tax_document_artifacts | Future private PDF/snapshot and extensible artifact types, hashes and versions |
| tax_audit_events | Append-only seller and draft change events, including before/after data |

Existing CRM foreign IDs use unsigned INT; all new IDs use unsigned BIGINT. All foreign-key deletion actions are RESTRICT. The historical source quote-item ID is provenance rather than a foreign key because proposal edits can recreate items. No historical table/data migration is included.

The payment, allocation, link, artifact and sequence models intentionally reject writes in Sprint 1. They are persistence foundations, not incomplete posting APIs. Documents reject issuance/number assignment and non-draft modification; audit models reject update/delete. Direct SQL can bypass Eloquent guards: full database/storage immutability, least-privilege credentials and retention enforcement remain an issuance release gate in Sprint 2. Do not enable real issuance on this foundation alone.

## Routes and permissions

Routes use the existing configured admin prefix, `web`, `admin_locale` and `user` middleware.

| Routes | Permission |
| --- | --- |
| GET /admin/tax-documents | tax_documents |
| GET /admin/tax-documents/create; POST /admin/tax-documents | tax_documents.create |
| GET /admin/tax-documents/{id}/edit; PUT /admin/tax-documents/{id} | tax_documents.edit |
| GET /admin/tax-sellers | tax_settings |
| GET /admin/tax-sellers/create; POST /admin/tax-sellers; GET /admin/tax-sellers/{id}/edit; PUT /admin/tax-sellers/{id} | tax_settings.manage |

Proposal conversion is initiated on the create-draft screen (latest 100 accepted proposals), or with `?quote_id=ID`. It additionally requires `quotes` permission. Draft and proposal access respects the existing own/group/global visibility. Legal seller settings are finance-wide and protected by their dedicated permissions. Assign parent/list permissions along with create/edit permissions for a navigable workspace.

There are no issue/delete/payment-posting/QR/PDF/Phase 2 routes.

## Safe installation and migrations

No migrations have been run against the populated CRM database during development.

1. Review the existing local changes and back up both database and files. Rehearse on a disposable **MySQL staging clone** first.
2. Verify existing Krayin migrations are up to date, and that none of these nine names (particularly `payments`) belong to another installed module. Use a MySQL/InnoDB environment matching production. The tests compile MySQL DDL but do not execute a live MySQL migration.
3. Refresh local class discovery: `composer dump-autoload --no-scripts`. Deploy with the normal application cache/config rebuild process; do not leave an old cached provider/route configuration active.
4. Inspect only this additive migration:

   `php artisan migrate --pretend --path=packages/Webkul/Tax/src/Database/Migrations`

5. On the reviewed staging connection, then on the approved deployment connection, run:

   `php artisan migrate --path=packages/Webkul/Tax/src/Database/Migrations`

   Do not use `migrate:fresh`, `refresh`, `reset` or blanket rollback. Confirm the active database before executing. MySQL DDL is not transactional: if interrupted, inspect the schema and migration ledger before retrying.
6. Grant permissions, enter verified seller settings, and create a sample draft. Verify desktop/mobile and RTL layout in the real admin shell on staging.

Rollback refuses to drop tax tables when sellers, documents, payments or audit records exist. Remove the feature from navigation/access if needed and preserve financial tables; do not delete financial history to make rollback succeed. Empty-install rollback is tested only in isolation.

## Testing

Run:

`php vendor/phpunit/phpunit/phpunit -c phpunit.tax.xml`

The dedicated config forces SQLite `:memory:`, foreign keys, array cache/session and fake mail; the test base refuses any other connection **before schema operations**. It runs selected real legacy migrations plus the new migration only in memory. No production credentials are used.

The suite verifies migration up/down and deletion restrictions, MySQL DDL compilation without any connection, Concord bindings, precise decimal input handling, authorization/record scope, real tax Blade templates in English/Arabic, validation, duplicate-create handling, stale edits, frozen source provenance, unchanged proposals and disabled later-sprint operations.

Tax Blade views use a minimal test layout to isolate unrelated CRM dependencies. Browser JavaScript execution, actual desktop/mobile visual QA, MySQL DECIMAL storage and simultaneous MySQL transactions still require staging verification. SQLite results do not prove MySQL concurrency or decimal persistence.

## Outstanding accounting decisions

- Verify legal seller/branch registrations and structured national address; format validation does not confirm VAT registration.
- Confirm standard/simplified eligibility, approved tax categories/rates, tax-inclusive pricing and rounding/allocation rules.
- Approve number series, tax-point/period policies and actual issue timestamp behavior.
- Distinguish taxable advances from refundable deposits and approve advance/final VAT reconciliation.
- Define partial credit/debit adjustments, overpayments, refunds, retention and accountant exports.
- Confirm actual Phase 2 applicability outside this module. No activation date is assumed or encoded.

## Exact recommended Sprint 2

1. Implement and approve a decimal statutory calculator: line/document allowances and charges, inclusive/exclusive pricing, tax breakdowns and consistent rounding.
2. Add issue-time legal/Arabic validation, finalized snapshot schema, locked sequence allocation and issuance idempotency with actual simultaneous MySQL tests. Issuance must not depend on full payment.
3. Implement immutable issue/archive transaction design, database guards, audit integrity, private retention/storage and recovery checks; introduce only the operation/outbox support necessary for Phase 1 artifact reliability if needed.
4. Add bilingual frozen PDF generation and the five-field Phase 1 TLV QR for simplified invoices, with independent decode tests and accountant-reviewed fixtures.
5. Keep payment posting, advance-to-final settlement, correction-note posting and refunds for a subsequent sprint unless explicitly included after these issuance foundations pass review. Draft purpose fields do not constitute those workflows.
6. Do not add Phase 2 tables, APIs, XML, signing, credentials, onboarding, workers or fake regulatory statuses.

The UUID, profile/type/purpose, versioned snapshot fields, document links and generic artifact table are the only future integration boundary at this stage.
