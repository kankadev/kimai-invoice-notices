# Validation and development

## Acceptance baseline — 2026-10-11

Isolated Kimai 2.67.0 and 2.69.0 installations, PHP 8.3, dedicated synthetic database volumes, internal Docker networks and a mail catcher. Production was not changed and no message was sent. Private infrastructure/test-login helpers and company templates are intentionally excluded from this repository.

- 26 standalone domain checks: strict schema, types, lengths, UTF-8, exact locale, inactive profiles, snapshot round trips/provenance/state/size.
- 28 native checks: field registration/preservation, effective formatter locale, real Twig escaping, duplicate IDs, create/update/revisions, language discovery, stale saves, storage bound, native invoice create/save, original document hash, model freeze, status/reload preservation, protected metadata changes/removal/new injection, override rejection/no fallback, exception without customer mutation and protected-table hashes after rollback.
- 6 native renderer sandbox checks: allowed pure function, denied source function and setter, no forbidden mutation, missing data and missing plugin fail explicitly.
- HTTP acceptance: EN/DE administration, creation/editing, block order, explicit French, retained languages/input, success feedback, HTML escaping/inert Twig, malformed/absent CSRF, stale edit, field limits, restricted-role 403, native invoice selection/default/exception, forged choice rejection and invalid create token.
- Private actual PDF-renderer acceptance: mixed hourly/fixed charges, absent notices, long blocks and long address; all pages visually reviewed, embedded Carlito, numbering, Unicode/currency, totals, footer spacing and no repeated tax footer. Texts were synthetic layout fixtures, not a legal review.

The source audit reviews permissions, bounded parsing, bound SQL/concurrency, plaintext rendering, sandbox and snapshot lifecycle. No source-supported vulnerability was found. This does not guarantee absence of every defect or certify legal texts. Native core boundaries were inspected separately. No full HTTP API mutation test is claimed; native save and API event-path inspection establish the protected path.

## Running checks

```sh
php Tests/run.php
```

Within a **genuinely isolated** Kimai installation at `/opt/kimai`, containing synthetic customer/user/template fixtures and the database named `kimai_stage`:

```sh
php var/plugins/KankaInvoiceNoticesBundle/Tests/native.php
php var/plugins/KankaInvoiceNoticesBundle/Tests/sandbox.php
php bin/console lint:twig var/plugins/KankaInvoiceNoticesBundle/Resources/views --env=prod
```

Never run native tests against production. The database-name guard is a safeguard, not proof of isolation. `native.php` uses rollback transactions, randomly named temporary documents and protected-table fingerprints; its artificial JSON renderer tests persistence, not PDF layout. `sandbox.php` uses the actual native Twig renderer without database writes. Actual PDF acceptance is a separate operator check with the installation's private template.

The compiled container service factories give the test harness access to private native services; they do not change public service visibility. A different Symfony host build may require harness adjustment. Public CI runs domain, syntax and metadata checks. Native HTTP/PDF acceptance needs a real isolated installation and is not implied by the public source checks.

## Release review

Use the normal Kimai plugin directory and cache clear for installation. Package only explicitly maintained public plugin files; exclude private fixtures, credentials, customer/issuer data and exports. Review staged changes and run secret scanning before committing. Test the extracted release archive in staging, verify its checksums and rerun acceptance after changes that affect behavior or layout.
