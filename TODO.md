# Roadmap

This is a planning backlog, not a list of implemented features.

## Before implementation

- [ ] Confirm supported Kimai versions and native metadata/model extension behavior.
- [ ] Finalize profile schema, template variable contract, language fallback, and missing-profile policy.
- [ ] Decide how an individual invoice can override its customer's default profile.
- [ ] Prove a safe hook for invoice text snapshots and define historical re-render semantics.
- [ ] Decide profile-history retention, deletion, migration, and uninstall behavior.

## First implementation

- [ ] Profile administration with English/German interface translations.
- [ ] Localized short notices and ordered long blocks.
- [ ] Customer profile selection through native metadata.
- [ ] Language discovery, explicit language addition, and missing-translation guidance.
- [ ] Native invoice model integration and example template instructions.
- [ ] Validation, authorization, concurrency checks, and optional explicit consistency constraints.
- [ ] Automated tests and isolated renderer acceptance with synthetic fixtures.
- [ ] Installation, upgrade, recovery, and operator documentation before any release.

## Deferred

- External offer profile export/API: revisit only when duplicate text maintenance becomes a concrete problem. No prospect-to-Kimai requirement.
- Financial calculations, tax determination, discounts, recurring bookings, and email delivery: outside this plugin's scope.
