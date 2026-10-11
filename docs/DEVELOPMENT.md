# Development status

## First slice — 2026-10-11

Development preview `0.1.0-dev`, not a release. Installed only in an isolated Kimai 2.67.0 / PHP 8.3 test environment.

Implemented: strict profile validation, a read-only configuration registry, native customer selection, a model hydrator exposing `invoice.kanka_notices`, and an English/German administrator overview at `/{locale}/invoice-notices/settings`. The overview uses Kimai's native components and has no write endpoint yet.

The current registry reads its own configuration key. No legal profiles are shipped or enabled. Synthetic fixtures used by tests are transaction-scoped and rolled back; do not import real invoices or customer data into this repository.

Document language is taken from the invoice model's formatter. A template with a nullable language must not cause a type error or a fallback to the customer's email language. An exact translation is currently required, including locale variants. Unassigned profiles return an explicit `unassigned` state and empty notices; an explicitly empty profile returns `resolved`. The stricter final missing-profile policy remains open.

Assigned inactive or missing profiles, corrupt configuration and missing translations fail explicitly. The final localized error/preview workflow is still to be built. This preview is not suitable for production invoice creation.

The shared private invoice template has not been modified. PDF layout, historical snapshots, per-invoice overrides and supported-version coverage are pending. Stored PDFs remain untouched because this slice does not write invoices or handle history downloads.

## Profile editor — 2026-10-11

The administrator overview now links to native Symfony forms for creating and editing profiles. Stable identifiers are generated on creation and each successful save increments the revision. Languages are offered from aggregated customer/template usage, existing profile translations and explicit additions. Language sections require explicit activation; nonempty disabled content is rejected. Blocks can be added, removed and reordered before saving.

Persistence uses the plugin's existing native configuration key, a database transaction, a locked read and a registry-wide revision token. Stale forms preserve their input and cannot overwrite a newer save. The current native TEXT storage requires a combined 60,000-byte UTF-8 limit, checked before writing; the overview shows usage. Dedicated larger storage, deletion, profile history and migrations remain future work.

Current verified results: **20 domain checks and 14 native checks**. Native checks additionally cover profile creation/update, revision increments, language discovery, stale saves and storage-limit rejection. Transaction fixtures are rolled back and before/after hashes of customer, invoice and timesheet tables match.

Actual HTTP checks cover create/update, DE/EN content, explicitly added French, block ordering, escaped HTML and inert Twig-like text, visible save confirmation, missing/invalid CSRF tokens, stale submissions with retained input, disabled translations containing text, malformed revision tokens, field-length validation and language retention. A restricted fixture user receives 403 on overview, create and edit routes. German labels and native customer dropdown choices were checked. These HTTP checks leave one synthetic active profile for manual testing; no customer record, invoice or timesheet was saved and no mail was sent.

PHP syntax, both Twig templates, both XLIFF translations and Composer metadata pass validation. These checks are not visual PDF acceptance or proof of compatibility with Kimai 2.69.0. The installation-private HTTP harness is not yet a portable public integration suite.

## Tests

Run domain checks with PHP 8.2 or later and mbstring:

```sh
php Tests/run.php
```

`Tests/native.php` requires a complete isolated Kimai installation at `/opt/kimai` with the dedicated `kimai_stage` fixture database. It refuses other database configurations, boots the actual kernel, and runs configuration fixtures inside a transaction that is always rolled back. It checks native field registration, selected-ID preservation, actual Twig escaping, document language, duplicate identifiers, inactive assignments, and unchanged customer/invoice/timesheet tables. Never run it against production.

The native check uses the compiled Twig service factory only to access the actual test-kernel renderer; it does not change service visibility. This harness may need adjustment for a different Symfony build.

Verified results: 17 domain checks; 9 native checks; kernel compilation; hydrator autoconfiguration; Twig syntax; both XLIFF files; authenticated English/German overview and customer form; anonymous access redirected to login; POST to the read-only overview rejected with 405. No customer save, invoice creation, or mail delivery was performed.

The deployment and HTTP fixture-login helpers remain installation-private. A portable CI/integration harness and full security acceptance are still required before release.
