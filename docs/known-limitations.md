# Known limitations

- **One opening per day.** A day has a single opening range (which may run past midnight). Split shifts such as
  9–12 and 14–18 need two ranges, which the block does not yet support.
- **Fixed UTC offsets.** If the site's time zone is set to an offset ("UTC+2") rather than a city, the browser does
  not recompute the badge, because an offset has no daylight-saving rules; the server-rendered status is shown.
- **Time display format** follows the site's time format on the server and the visitor's locale in the browser
  (for example "6:00 pm" against "6:00 PM").
- **Testimonials have no review markup**, by design (ADR 3).
- **FAQ structured data** should appear once per page; two FAQ blocks with structured data on one page produce two
  `FAQPage` entries. The editor help text says so.
- **Editor tests** cover the shared helpers with Jest and the rendered output in WordPress; there are no end-to-end
  tests that click through the block editor UI.
- **Accessibility** is checked with axe-core's automated rules, which catch a large share of issues but not all;
  manual screen-reader testing has not been done.
