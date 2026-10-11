# Roadmap

Target backlog. Checked items have implementation evidence; unchecked items remain open.

## First development slice — 2026-10-11

- [x] Native bundle registration, read-only administrator overview, English/German labels.
- [x] Validated plain-text profile model with stable identifiers and ordered blocks.
- [x] Native customer choice field; re-registration preserves the selected identifier.
- [x] Invoice-model hydrator uses the effective document formatter locale.
- [x] Missing translations and inactive/missing selected profiles do not silently resolve.
- [x] 17 domain checks and 9 native integration checks in isolated Kimai 2.67.0 / PHP 8.3.
- [x] German/English HTTP overview, login guard, read-only method restriction and customer field checked.
- [x] Profile editor, content-language discovery, explicit language addition and retained languages.
- [x] Native form validation, CSRF, restricted-role access, stale-edit rejection and storage limits.
- [x] Current test totals: 20 domain checks and 14 native integration checks; HTTP editor acceptance.
- [ ] Historical snapshots, PDF placement and Kimai 2.69 compatibility.

## Before implementation

- [ ] Confirm supported Kimai versions and native metadata/model extension behavior.
- [ ] Finalize profile schema, template variable contract, language fallback, and missing-profile policy.
- [ ] Decide how an individual invoice can override its customer's default profile.
- [ ] Prove a safe hook for invoice text snapshots and define historical re-render semantics.
- [ ] Decide profile-history retention, deletion, migration, and uninstall behavior.

## First implementation

- [x] Profile administration with English/German interface translations.
- [x] Localized short notices and ordered long blocks.
- [x] Customer profile selection through native metadata.
- [x] Language discovery, explicit language addition, and missing-translation guidance.
- [ ] Native invoice model integration and example template instructions.
- [ ] Validation, authorization, concurrency checks, and optional explicit consistency constraints.
- [ ] Automated tests and isolated renderer acceptance with synthetic fixtures.
- [ ] Installation, upgrade, recovery, and operator documentation before any release.

## Deferred

- External offer profile export/API: revisit only when duplicate text maintenance becomes a concrete problem. No prospect-to-Kimai requirement.
- Financial calculations, tax determination, discounts, recurring bookings, and email delivery: outside this plugin's scope.
