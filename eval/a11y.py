"""Accessibility audit of the rendered blocks with axe-core (WCAG 2.1 A and AA rules), plus screenshots.

Usage: python eval/a11y.py <path to axe.min.js> [--shots]
Needs the integration stack running (bash tests/integration/run.sh). Writes eval/reports/a11y.json.
"""

import json
import sys
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parent.parent
SITE = "http://127.0.0.1:8080"
AXE = Path(sys.argv[1]).read_text(encoding="utf-8")
SHOTS = "--shots" in sys.argv
SELECTORS = [".bb-hours", ".bb-faq", ".bb-pricing", ".bb-testimonials", ".bb-person", ".bb-cta"]

with sync_playwright() as p:
    browser = p.chromium.launch(channel="msedge" if sys.platform == "win32" else None, headless=True)
    page = browser.new_page(viewport={"width": 1280, "height": 900}, device_scale_factor=2 if SHOTS else 1)
    page.goto(SITE + "/blocks-demo/")
    page.wait_for_timeout(800)
    page.add_script_tag(content=AXE)
    result = page.evaluate(
        """async (selectors) => {
            const r = await axe.run({ include: selectors.map((s) => [s]) },
                { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] } });
            return {
                violations: r.violations.map((v) => ({ id: v.id, impact: v.impact, help: v.help, nodes: v.nodes.length })),
                passes: r.passes.length,
                incomplete: r.incomplete.map((v) => v.id),
            };
        }""",
        SELECTORS,
    )
    keyboard = page.evaluate(
        """() => {
            const summaries = [...document.querySelectorAll('.bb-faq summary')];
            return summaries.length > 0 && summaries.every((s) => s.tabIndex === 0);
        }"""
    )
    result["blocks"] = len(SELECTORS)
    result["faq_keyboard"] = keyboard
    out = ROOT / "eval" / "reports"
    out.mkdir(parents=True, exist_ok=True)
    (out / "a11y.json").write_text(json.dumps(result, indent=2) + "\n", encoding="utf-8")
    print(json.dumps({"violations": result["violations"], "passes": result["passes"], "faq_keyboard": keyboard}))

    if SHOTS:
        img = ROOT / "docs" / "images"
        img.mkdir(parents=True, exist_ok=True)
        page.locator("main").first.screenshot(path=str(img / "front.png"))
        page.goto(SITE + "/wp-login.php")
        page.fill("#user_login", "admin")
        page.fill("#user_pass", "admin")
        page.click("#wp-submit")
        page.wait_for_load_state()
        pid = page.evaluate("fetch('/wp-json/wp/v2/pages?slug=blocks-demo', {credentials:'same-origin'}).then(r=>r.json()).then(j=>j[0].id)")
        page.goto(f"{SITE}/wp-admin/post.php?post={pid}&action=edit")
        page.wait_for_timeout(5000)
        for sel in ["button[aria-label='Close']", ".components-modal__frame button[aria-label='Close']"]:
            if page.locator(sel).count():
                page.locator(sel).first.click()
                page.wait_for_timeout(500)
        page.screenshot(path=str(img / "editor.png"))
    browser.close()
