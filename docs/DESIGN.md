# Architecture

Native customer metadata (`kanka_notice_profile`) selects a stable profile ID. Profiles use one native configuration row (`kanka_invoice_notices.profiles`) with versioned, strictly validated plain-text JSON. Writes use a transaction, locked read and registry-wide compare-and-swap token. Mutation requires native system configuration permission and valid Symfony forms. Limits prevent native TEXT truncation.

Content-language editing discovers customer/template languages and retains configured translations. Resolution uses the invoice formatter's exact locale. No legal inference or locale fallback exists. Unassigned customers resolve to empty `unassigned` data; explicitly empty translations remain `resolved`. Missing, inactive or untranslated assigned profiles fail.

The native invoice toolbar provides a batch-wide exception without changing customers. Pre-render resolves and freezes text; post-render hashes exact response bytes; native invoice-update-pre attaches a hidden bounded snapshot before the same ORM flush. Existing invoice snapshots are compared with stored database values and cannot be changed. New invoices cannot supply the reserved metadata themselves. Uploads without rendering have no generated snapshot. Reset clears transient render state.

Native templates own layout and escaping. `invoice_notices(invoice)` validates model data. Only this pure function is added to the native strict sandbox policy. Nothing executes profile text. Native history downloads use stored files; no historical re-render or backfill occurs.

No extra tables, PDF copies, profile-revision archive, external API, mail delivery, customer synchronization or financial calculation. Profile deactivation and plugin removal preserve data; deletion is deliberately unavailable in version one. External offers use their own document-appropriate wording without a Kimai dependency.
