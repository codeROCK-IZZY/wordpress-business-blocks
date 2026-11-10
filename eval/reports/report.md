# Business Blocks: evaluation

## Gate: 7 / 7 checks passed

| Check | Value | Target | |
|---|---|---|---|
| FAQ JSON-LD that breaks out of its script or is invalid, hostile input | 0 / 2000 | = 0 | pass |
| "Open now" disagreements with the reference, 8 zones incl. DST changes | 0 / 59,600 | = 0 | pass |
| Browser and PHP agree on generated cases (Jest) | 3,600 cases | ≥ 3,000 | pass |
| Front-end CSS for all six blocks, gzipped | 1944 B | ≤ 3 KB | pass |
| Front-end JavaScript (hours badge only), gzipped | 1081 B | ≤ 2 KB | pass |
| axe-core WCAG 2.1 A/AA violations in the blocks | 0 | = 0 | pass |
| FAQ questions reachable by keyboard | yes | yes | pass |

## Front-end weight per block (gzipped)

| Block | CSS | Front-end JS | Editor JS (editor only) |
|---|---:|---:|---:|
| business-hours | 406 B | 1081 B | 1675 B |
| call-to-action | 219 B | none | 1037 B |
| faq | 389 B | none | 1409 B |
| pricing-table | 413 B | none | 1372 B |
| team-member | 201 B | none | 1090 B |
| testimonials | 316 B | none | 1034 B |
