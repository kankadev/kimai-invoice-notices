# Security policy

Report suspected vulnerabilities privately to [mail@kanka.dev](mailto:mail@kanka.dev). Include plugin/Kimai/PHP versions, affected route/template, prerequisites and a minimal reproduction with synthetic data. Never include production credentials, invoices or customer records. Please coordinate disclosure while a fix is prepared.

The maintained release line is 1.x. Acceptance covers Kimai 2.67.0 and 2.69.0 with PHP 8.3. Other versions require staging validation. Maintain Kimai and its dependencies independently of this plugin.

Profile administration requires native `system_configuration` and full authentication. Customer/invoice permissions, CSRF configuration, database and filesystem protection remain host/operator responsibilities. Templates are trusted administrative code. Profile content is bounded plain text and is not interpreted or fetched remotely.

Snapshots are protected through native invoice updates while the plugin is loaded. Database operators retain database authority. Hashes are not digital signatures or tamper-proof storage. Back up the database and original files. This plugin does not validate legal applicability.
