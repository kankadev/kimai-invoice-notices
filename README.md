# Invoice Notices for Kimai

Reusable, multilingual notice profiles for customer invoices — by [kanka.dev](https://kanka.dev).

**Status: planning.** This repository currently contains the project specification, not an installable plugin. No release or supported Kimai version has been declared.

Invoice Notices is designed to let administrators maintain invoice explanations centrally, select a profile on each customer, and display its texts through an existing native Kimai invoice template. Profiles can contain tax explanations or other informational notices. The plugin will not calculate taxes, apply discounts, change invoice amounts, or send emails.

## Planned workflow

1. Create a notice profile with a name, purpose, and translated texts.
2. Add a short notice for the invoice totals area and ordered explanatory blocks for a longer information section.
3. Select the profile in a native customer custom field.
4. Integrate the documented variables into your native invoice template once.
5. Preview and verify the rendered document before using the profile for invoicing.

The planned interface is English with German translations. Content languages will be discovered from customer languages and invoice-template languages. Previously configured languages remain editable, and additional languages can be selected explicitly. Discovery offers editing sections; it does not automatically translate legal text. Invoice content follows the document language to avoid mixed-language PDFs.

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

Implementation and compatibility testing have not started. The design will be validated against Kimai's native customer metadata and invoice-model extension points before coding. A public release requires automated checks and actual PDF rendering in an isolated environment with synthetic data.

Customer records, installation-specific legal texts, private invoice templates, signatures, credentials, and production exports do not belong in this repository.

## Author and license

Developed by **Akay — kanka.dev**. Contact: [mail@kanka.dev](mailto:mail@kanka.dev).

GNU Affero General Public License, version 3 or later. See [LICENSE](LICENSE).
