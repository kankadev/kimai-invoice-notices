# Design specification

Status: target design. The first read-only slice is implemented and installed only in an isolated test environment; see DEVELOPMENT.md for verified behavior and remaining work.

## Architecture

Use a standalone Kimai plugin, provisionally `KankaInvoiceNoticesBundle`, with its own administrator pages and persistence. Do not add this functionality to Invoice Mail or modify Kimai core. Validate extension points against the installed version before implementation.

Register a native customer metadata choice field through `CustomerMetaDefinitionEvent`. Store a stable profile identifier rather than its translated display name. Populate choices from active profiles. Existing assignments to inactive profiles must remain visible and must not be silently replaced.

Use Kimai's invoice model hydration extension to expose namespaced template data. Proposed contract: `kanka_notices.short`, `kanka_notices.blocks`, `kanka_notices.profile_id`, `kanka_notices.revision`, and `kanka_notices.language`. Confirm the final contract against the renderer before documenting it as supported.

Native Twig templates control placement, typography, escaping, and page flow. Profile content is plain text, not executable Twig or arbitrary HTML. Do not fetch external resources during rendering. Define supported placeholders explicitly if they are needed; never interpret user content as template code.

## Profile administration

A profile has a stable ID, display name, description of intended use, active state, revision, and localized content. Localized content includes a short notice and ordered long explanation blocks. Use simple move-up/move-down controls; a free-form layout editor is out of scope.

English and German are interface translations. Notice content may use any supported Kimai document locale. Discover languages used by customers and invoice templates, retain configured languages, and allow explicitly adding a language. Show why a language appears and how many customers use it. Missing translations must be explicit; never silently substitute another language. Define locale-variant fallback narrowly and visibly before implementation.

The customer field may default to no assigned profile. A missing assignment is not equivalent to an explicit profile without notices. Decide a configurable requirement policy during implementation. Administrators can define an explicit no-notice profile. Exceptions must remain possible for a customer's individual invoice if the verified native workflow supports this; otherwise separate invoices/templates require an explicit handling rule before release.

## Financial and legal boundaries

This is a notice-management plugin, not a tax engine. Do not infer legal applicability from country or legal form. Do not alter tax rates, prices, discounts, invoice numbering, time entries, or delivery status. Optional consistency constraints may check existing invoice settings, but must be expressly configured and must not claim to verify tax law.

No jurisdiction-specific legal profile is enabled by default. Any examples must be fictional and clearly distinguish sample content from reviewed installation configuration.

## Historical consistency

Stored invoice PDFs remain authoritative and unchanged. Determine a native generation/persistence hook for recording the profile revision and resolved texts with the invoice before promising snapshot support. Re-rendering and downloading historical invoices must have documented semantics. Profile changes must never silently rewrite a saved invoice's notices. Decide retention and deletion behavior for profile history before release.

## External offers

An existing external offer workflow can maintain its own document-appropriate notice. Version one does not require a live dependency on Kimai and does not require prospects to become Kimai customers. An optional authenticated read-only profile export/API can be considered later, addressed by stable profile ID and language rather than customer name. No bidirectional customer synchronization is planned.

## Security and acceptance

Use native authorization, CSRF protection, validated identifiers, length limits, and escaped output. Profile mutation requires administrator permissions. Validate concurrent edits and referenced-profile deletion behavior. Profile data must survive deactivation; define safe uninstall behavior separately.

Tests must cover customer assignment, inactive/missing profiles, language discovery and retention, missing translations, block ordering, escaping, access control, inconsistent invoice settings, and historical PDF preservation. Render representative PDFs through Kimai's actual renderer without consuming production invoice numbers or sending mail. Keep installation-specific templates and evidence outside the public repository.
