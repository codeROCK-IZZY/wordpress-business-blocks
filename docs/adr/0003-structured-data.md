# 3. Emit only structured data that search engines accept, and encode it safely

## Context

FAQ and opening-hours markup can earn rich results. Review markup on a business's own testimonials cannot: search
engines treat self-serving reviews of a LocalBusiness as ineligible and may take manual action. And JSON-LD is
printed inside a `<script>` element, where any `</script>` in user content would end the element and run whatever
follows.

## Decision

- FAQ → `FAQPage` with one `Question`/`acceptedAnswer` per item (answers keep only links and basic formatting).
- Business hours → `LocalBusiness` or a chosen subtype, with `OpeningHoursSpecification` grouped by identical hours
  and dated closures as special-hours entries.
- Testimonials → no structured data.
- Encoding uses `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`, so `<`, `>`, `&` and quotes become
  `<` etc. The type is restricted to a schema.org-style name so attributes cannot inject properties.

## Consequences

- 2,000 randomly built FAQs full of hostile strings all produce one valid, contained JSON-LD element.
- Sites that want review stars must use a third-party review platform's markup, which is outside this plugin.
