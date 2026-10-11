# Invoice Notices for Kimai

Reusable, multilingual invoice explanations — developed by **Akay at [kanka.dev](https://kanka.dev)**.

Maintain notice profiles centrally, assign one to a customer and print its translated text through an existing native Twig invoice template. Each profile has a short notice and ordered explanation blocks. English and German interface translations are included. The plugin does not calculate tax, change invoice amounts, apply discounts or send email. No legal profiles are supplied or automatically selected.

## Installation

Requires Kimai 2.67 or later, PHP 8.2 or later, JSON and mbstring. Acceptance covers **Kimai 2.67.0 and 2.69.0 with PHP 8.3**. Other host versions require staging validation; Kimai 3 is not covered.

1. Back up the Kimai database, stored invoice files, custom templates and plugins.
2. Download the versioned `KankaInvoiceNoticesBundle` archive from [Releases](https://github.com/kankadev/kimai-invoice-notices/releases). Extract it so that `var/plugins/KankaInvoiceNoticesBundle/KankaInvoiceNoticesBundle.php` exists. Avoid an extra nested directory.
3. As the application user, run `php -d memory_limit=512M bin/console cache:clear --env=prod --no-debug` from the Kimai directory.
4. Open **Invoice Notices** in the system menu with native `system_configuration` permission.
5. Adapt your native Twig invoice document using [the integration guide](docs/TEMPLATES.md). Arbitrary templates are not changed automatically.
6. Create a profile, select it in the customer's **Invoice notice profile** field and check the actual PDF preview before saving an invoice.

For Docker, use your installation's persistent plugin directory and run the cache command inside the application container. Do not install into an ephemeral image layer. No plugin-specific migration or external service is required.

## Workflow

Create a profile with a name and a description of when it applies. Enable each required content language, enter a short notice and any longer blocks. Language/block buttons change the current form only; **Save profile** persists the entire profile and increments its revision. An enabled empty translation is an explicit no-text choice. Entered text in a disabled translation is rejected rather than discarded.

Content languages are discovered from customers and invoice templates. Configured languages remain available and other supported Kimai languages can be added. Discovery does not translate text. The actual **invoice formatter language**, including locale variants, determines the translation. There is no silent fallback. Missing/inactive assigned profiles or missing translations block generation with corrective guidance. An unassigned customer receives empty notices.

In the native invoice toolbar, **Notice profile for this export** inherits each customer's assignment. Choose a different active profile or no notices for an exception. This applies to **every invoice created from that selection**, without changing customer defaults. Check the preview and restore the inherited selection afterwards. Do not save an exception as your default search bookmark.

Concurrent edits are detected across the shared registry. If somebody saves first, your input remains visible; reconcile it against the current profile in a new tab. Reloading discards unsaved input. Combined storage is **60,000 UTF-8 bytes**, at most 200 profiles, 100 translations per profile and 20 blocks per translation. The overview displays usage. Profiles can be deactivated; permanent deletion is intentionally unavailable in version one.

## Saved invoices and recovery

New invoices generated through the native service receive hidden metadata containing resolved notices, language, profile ID/revision, renderer name and a SHA-256 reference to the generated document. It is saved with the invoice and protected from normal native invoice edits. Profile changes do not rewrite it. The hash is a provenance reference, **not a digital signature or legal certification**.

Kimai downloads the original stored invoice file; it does not regenerate history from current profiles. Historical invoices are not backfilled. Uploaded documents without a rendering lifecycle have no generated snapshot. Snapshots record resolved data; printing it depends on a correctly integrated template. Previews store no invoice snapshot. No additional PDF copy or separate revision archive is created.

For upgrades, preserve the bundle directory name and database, replace only this plugin's files and clear the cache. Test templates in staging before host upgrades. For rollback/removal, first restore a template without `invoice_notices`, then remove the bundle from the active plugin directory and clear the cache. **Removal does not delete configuration, customer assignments or invoice snapshots.** Retain them in backups. Reinstallation restores access. Do not delete stored PDFs or invoice metadata as cleanup.

## Responsibilities

- Administrators review applicability and wording. Country, legal form and zero tax do not automatically determine tax treatment.
- Content is plain text; HTML, Twig and placeholder-like text are not executed. Follow the template guide's escaping.
- A discount notice does not apply a discount. Financial settings remain separate.
- Invoice Mail stays independent. PDF billing information and email templates serve different purposes.
- External offer systems remain independent. Prospects need not become Kimai customers; a profile API is deferred.

[German guide](docs/README.de.md) · [Templates](docs/TEMPLATES.md) · [Validation](docs/DEVELOPMENT.md) · [Security](SECURITY.md) · [Roadmap](TODO.md).

Customer records, installation-specific legal texts, bank data, signatures, credentials and real exports do not belong in this repository.

GNU Affero General Public License, version 3 or later. [License](LICENSE). Contact: [mail@kanka.dev](mailto:mail@kanka.dev).
