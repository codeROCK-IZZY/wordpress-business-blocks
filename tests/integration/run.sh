#!/usr/bin/env bash
# Installs WordPress, activates the plugin (build/ must exist: npm run build), creates demo pages, runs the checks
# inside WordPress, then checks what the front end actually sends: assets only where blocks are used.
# Usage: bash tests/integration/run.sh
set -euo pipefail
cd "$(dirname "$0")/../.."

wp() { docker compose exec -T cli wp "$@"; }
SITE=http://127.0.0.1:8080
[ -f build/blocks/faq/block.json ] || { echo "build/ missing: run npm run build first"; exit 1; }

docker compose up -d
echo "waiting for WordPress"
for _ in $(seq 1 60); do curl -fsS -o /dev/null "$SITE/wp-admin/install.php" 2>/dev/null && break; sleep 2; done
if ! wp core is-installed 2>/dev/null; then
  wp core install --url="$SITE" --title="Riverside Dental" --admin_user=admin --admin_password=admin --admin_email=admin@example.com --skip-email
fi
wp rewrite structure '/%postname%/' --hard >/dev/null
wp plugin activate business-blocks
wp eval-file wp-content/plugins/business-blocks/tests/integration/setup.php

echo "== inside WordPress"
wp eval-file wp-content/plugins/business-blocks/tests/integration/checks.php

echo "== front end over HTTP"
fail=0
ok() { echo "  ok    $1"; }
bad() { echo "  FAIL  $1"; fail=1; }
DEMO=$(curl -fsS "$SITE/blocks-demo/")
PLAIN=$(curl -fsS "$SITE/plain-page/")
# Here-strings, not "printf | grep -q": with pipefail an early grep exit can fail the pipe.
# WordPress 6.5 writes this attribute with single quotes, later versions with double quotes.
grep -qE "id=[\"']business-blocks-faq-style-inline-css[\"']" <<<"$DEMO" && ok "block styles load where blocks are used (inlined by WordPress, no extra request)" || bad "styles missing on the demo page"
grep -q 'business-hours/view.js' <<<"$DEMO" && grep -q 'id="business-blocks-business-hours-view-script-js"' <<<"$DEMO" && ok "the hours script loads, deferred, where the hours block is used" || bad "view script missing"
grep -q 'business-blocks' <<<"$PLAIN" && bad "plugin assets on a page without blocks" || ok "nothing from the plugin on a page without blocks"
grep -q 'business-blocks/build/blocks/faq/index.js' <<<"$DEMO" && bad "editor script on the front end" || ok "editor scripts never reach visitors"

[ "$fail" = "0" ] && echo "all HTTP checks passed" || { echo "HTTP checks failed"; exit 1; }
