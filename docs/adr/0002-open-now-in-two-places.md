# 2. Compute "Open now" on the server and again in the browser, and test that they agree

## Context

Most small-business sites sit behind a page cache. A status rendered once on the server ("Open now") is frozen into
the cached HTML and is wrong an hour later. Computing it only in the browser leaves visitors without JavaScript, and
search engines, with nothing.

Opening hours are wall-clock times in the business's time zone, so both implementations must handle daylight saving
the same way, including times that do not exist (the spring gap) or exist twice (the autumn overlap).

## Decision

- `includes/Hours.php` is the reference: openings belong to the date they start, overnight hours close on the next
  calendar date, and dated closures cancel the opening that starts that day. Times inside a gap follow PHP's
  `DateTime` rules.
- The block renders the status server-side and adds the schedule and the site's IANA time zone as a data attribute.
  `src/shared/hours.js` recomputes the status every minute with `Intl.DateTimeFormat`, resolving gaps and overlaps
  the same way as PHP.
- `eval/hours_cases.php` generates 3,600 cases with PHP across eight time zones and their 2026 DST weekends; Jest
  requires the browser code to agree on every one. A separate PHP property test checks the engine against a
  minute-by-minute reference.
- Sites configured with a fixed UTC offset instead of a city get the server-rendered status only, because an offset
  carries no DST rules for the browser to follow.

## Consequences

- The badge is right on cached pages and still present without JavaScript.
- The tests found a real DST bug in the first PHP version and a gap-handling difference in the first JavaScript
  version; both are fixed and covered.
