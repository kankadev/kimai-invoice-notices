# Native template integration

Adapt a copy of an existing native `.pdf.twig` or `.html.twig` invoice document. Preserve its calculator, numbering, bank information and layout. Templates are trusted operator code; profile content is data. No company-specific layout is bundled.

Resolve validated data once:

```twig
{% set notices = invoice_notices(invoice) %}
```

Below the invoice total:

```twig
{% if notices.short %}<p>{{ notices.short|nl2br }}</p>{% endif %}
```

In the information section:

```twig
{% for block in notices.blocks %}
    {% if block.title %}<h3>{{ block.title }}</h3>{% endif %}
    {% if block.text %}<p>{{ block.text|nl2br }}</p>{% endif %}
{% endfor %}
```

The contract contains `status` (`unassigned` or `resolved`), `profile_id`, `revision`, `language`, `short` and `blocks` with `title`/`text`. An enabled empty profile is `resolved`; no assignment is `unassigned`. Either may print no text.

The native hydrator also exposes `invoice.kanka_notices`. The explicit function validates the contract and fails when the plugin/data is missing. Do not hide missing integration with `default`. Its narrow sandbox adapter permits only this extra pure function; other native restrictions remain delegated.

Do not use `raw`, compile content as Twig or fetch it remotely. Literal `{{ ... }}` stays literal. Manage heading spacing in template styles and reserve a footer area based on its actual height plus a gap. Permit long blocks to continue across pages. Check long addresses, long notices, fixed/hourly positions and fonts with the actual renderer. Fonts and logos belong to the operator.

During migration, review duplicate legacy notices in hard-coded footers, customer billing text and payment terms. Do not strip content by matching a sentence. Keep financial settings and payment conditions unchanged unless separately intended. Remove old notices only after replacement profiles/template pass preview acceptance.

Before removing the plugin, restore a template without `invoice_notices`; otherwise new generation intentionally fails. Stored invoice files still download normally.
