# Invoice Notices for Kimai

Reusable, multilingual notice profiles for customer invoices — by [kanka.dev](https://kanka.dev).

**Status: early development preview.** Validated profiles, multilingual profile editing, native customer selection and invoice-model integration are implemented. Historical snapshots and PDF acceptance remain pending. Do not install this build in production. No stable release or general compatibility guarantee has been declared.

Invoice Notices is designed to let administrators maintain invoice explanations centrally, select a profile on each customer, and display its texts through an existing native Kimai invoice template. Profiles can contain tax explanations or other informational notices. The plugin will not calculate taxes, apply discounts, change invoice amounts, or send emails.

## Workflow

1. Create a notice profile with a name, purpose, and translated texts.
2. Add a short notice for the invoice totals area and ordered explanatory blocks for a longer information section.
3. Select the profile in a native customer custom field.
4. Integrate the documented variables into your native invoice template once.
5. Preview and verify the rendered document before using the profile for invoicing.

The interface is English with German translations. Content languages are discovered from customer languages and invoice-template languages. Previously configured languages remain editable, and additional languages can be selected explicitly. Discovery offers editing sections; it does not automatically translate legal text. Invoice content follows the document language to avoid mixed-language PDFs.

Open Invoice Notices in the administration menu to create or edit a profile. Enable each translation you intend to provide. Adding a language or moving, adding or removing blocks changes the current form only; **Save profile** persists it. An enabled empty translation explicitly provides no notices. A disabled translation with entered text is rejected so that content is not silently discarded.

Concurrent changes are detected across the profile registry. If another administrator saves first, your input remains visible. Open the current profile in another tab and reconcile your changes before saving. Reloading the old form discards its unsaved input; it does not force an overwrite.

This preview stores profiles in one native Kimai configuration entry, with a combined limit of **60,000 UTF-8 bytes**, at most 200 profiles and 20 blocks per language. The overview shows usage. Oversized changes are rejected before writing. Profiles can be deactivated but cannot yet be deleted; deactivation of the plugin preserves its configuration.

## Responsibilities and boundaries

- Administrators supply and review their own notices. Selecting a profile is not a legal certification.
- Country, legal form, or a zero tax rate alone must not silently determine tax treatment.
- A notice saying “10% discount” does not apply that discount. Financial calculations remain outside this plugin.
- Profiles are independent of Invoice Mail. PDF billing information and email templates retain their separate purposes.
- Integration with a native template is required; arbitrary invoice templates will not change automatically.
- Existing stored invoices and PDF documents must remain unchanged when a profile is edited.
- The initial design does not require external offer systems to create prospects as Kimai customers. An external profile API is deferred.

See [the design specification](docs/DESIGN.md), [the roadmap](TODO.md), and [the German overview](docs/README.de.md).

## Development

The current preview was checked in an isolated Kimai 2.67.0 / PHP 8.3 installation: 20 domain checks, 14 native integration checks, and HTTP checks for editing, ordering, languages, validation, CSRF, concurrent updates, escaping and restricted-role access. Production-version compatibility and actual invoice PDF rendering are still pending. See [development status and test instructions](docs/DEVELOPMENT.md).

Customer records, installation-specific legal texts, private invoice templates, signatures, credentials, and production exports do not belong in this repository.

## Author and license

Developed by **Akay — kanka.dev**. Contact: [mail@kanka.dev](mailto:mail@kanka.dev).

GNU Affero General Public License, version 3 or later. See [LICENSE](LICENSE).
