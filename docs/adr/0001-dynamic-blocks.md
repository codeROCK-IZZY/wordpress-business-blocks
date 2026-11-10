# 1. Render every block in PHP (dynamic blocks)

## Context

A static block saves its HTML into the post. When the block's markup changes in a plugin update, every post that
uses the old markup shows "This block contains unexpected or invalid content" until each block is migrated with
`deprecated` entries, one per historical version.

Several of these blocks also need the server at render time: the business-hours status depends on the current time
and the site's time zone, and structured data should be generated from attributes, not stored as text.

## Decision

All six blocks save only their attributes (`save: () => null`) and render through `render.php` declared in
`block.json`. The editor shows editable controls and previews; the front end is always produced by current code.

## Consequences

- Markup and accessibility fixes reach every existing page on update, with no block deprecations to maintain.
- Rendering costs a little PHP on each uncached request; page caches absorb it.
- Content lives in block attributes, so it is not visible to plugins that read `post_content` as HTML (search
  indexers that do not render blocks). `do_blocks()` produces the HTML when needed.
