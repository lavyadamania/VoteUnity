"""
VoteUnity — Automated Screenshot Capture
Uses Playwright (Chromium headless) to capture all pages needed for the report.
"""

from playwright.sync_api import sync_playwright
import os, time

BASE      = "https://vote-unity.vercel.app"
OUT_DIR   = r"c:\Users\Lavya\Desktop\Online Voting System\screenshots"
os.makedirs(OUT_DIR, exist_ok=True)

ADMIN_USER = "admin"
ADMIN_PASS = "admin123"

def ss(page, name, full=True):
    path = os.path.join(OUT_DIR, f"{name}.png")
    page.screenshot(path=path, full_page=full)
    print(f"  OK  {name}.png")
    return path

def wait(page, ms=1200):
    page.wait_for_timeout(ms)

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    ctx = browser.new_context(
        viewport={"width": 1280, "height": 800},
        device_scale_factor=1.5,
    )
    page = ctx.new_page()

    # ── 1. Homepage ──────────────────────────────────────────────────────────
    print("\n[1] Homepage")
    page.goto(BASE, wait_until="networkidle")
    wait(page)
    ss(page, "01_homepage")

    # ── 2. Register page ─────────────────────────────────────────────────────
    print("[2] Register")
    page.goto(f"{BASE}/pages/register.php", wait_until="networkidle")
    wait(page)
    ss(page, "02_register")

    # ── 3. Voter Login page ──────────────────────────────────────────────────
    print("[3] Voter Login")
    page.goto(f"{BASE}/pages/login.php", wait_until="networkidle")
    wait(page)
    ss(page, "03_voter_login")

    # ── 4. Public Verify page ────────────────────────────────────────────────
    print("[4] Public Vote Verification")
    page.goto(f"{BASE}/pages/verify.php", wait_until="networkidle")
    wait(page)
    ss(page, "04_public_verify")

    # ── 5. Admin Login — Step 1 (credentials) ────────────────────────────────
    print("[5] Admin Login — Step 1")
    page.goto(f"{BASE}/pages/admin/login.php", wait_until="networkidle")
    wait(page)
    ss(page, "05_admin_login_step1")

    # ── 6. Admin Login — Step 2 (face verification) ──────────────────────────
    print("[6] Admin Login — Step 2 (face verify)")
    try:
        page.fill('input[name="username"]', ADMIN_USER)
        page.fill('input[name="password"]', ADMIN_PASS)
        page.click('button[type="submit"]')
        page.wait_for_load_state("networkidle")
        wait(page, 1500)
        ss(page, "06_admin_login_step2_face")
    except Exception as e:
        print(f"  ! Step 2 face: {e}")
        ss(page, "06_admin_login_step2_face")

    # ── 7. Admin Dashboard ───────────────────────────────────────────────────
    print("[7] Admin Dashboard (direct, may redirect)")
    page.goto(f"{BASE}/pages/admin/dashboard.php", wait_until="networkidle")
    wait(page)
    ss(page, "07_admin_dashboard")

    # ── 8. Admin Vote Audit Trail ─────────────────────────────────────────────
    print("[8] Admin Vote Audit Trail")
    page.goto(f"{BASE}/pages/admin/view_votes.php", wait_until="networkidle")
    wait(page)
    ss(page, "08_admin_view_votes")

    # ── 9. Admin Audit Logs ──────────────────────────────────────────────────
    print("[9] Admin Audit Logs")
    page.goto(f"{BASE}/pages/admin/audit_logs.php", wait_until="networkidle")
    wait(page)
    ss(page, "09_admin_audit_logs")

    # ── 10. Admin Manage Admins ──────────────────────────────────────────────
    print("[10] Admin Manage Admins")
    page.goto(f"{BASE}/pages/admin/manage_admins.php", wait_until="networkidle")
    wait(page)
    ss(page, "10_admin_manage_admins")

    # ── 11. Admin Location Tracker ───────────────────────────────────────────
    print("[11] Admin Location Tracker")
    page.goto(f"{BASE}/pages/admin/location_tracker.php", wait_until="networkidle")
    wait(page, 2000)
    ss(page, "11_admin_location_tracker")

    # ── 12. Admin Tamper Demo ────────────────────────────────────────────────
    print("[12] Admin Tamper Demo")
    page.goto(f"{BASE}/pages/admin/tamper_demo.php", wait_until="networkidle")
    wait(page)
    ss(page, "12_admin_tamper_demo")

    # ── 13. Admin System Audit ───────────────────────────────────────────────
    print("[13] Admin System Audit")
    page.goto(f"{BASE}/pages/admin/system_audit.php", wait_until="networkidle")
    wait(page)
    ss(page, "13_admin_system_audit")

    # ── Now try full admin session via face bypass ───────────────────────────
    # Admin login requires face; try to navigate post-login pages directly
    # by injecting session via a fresh context (not possible externally).
    # Instead, try with a page that auto-completes if already in session.

    # ── 14. Vote page (will redirect to login — capture that UI) ─────────────
    print("[14] Vote page (unauthenticated redirect)")
    page2 = ctx.new_page()
    page2.goto(f"{BASE}/pages/vote.php", wait_until="networkidle")
    wait(page2)
    ss(page2, "14_vote_redirect")
    page2.close()

    # ── 15. Register form — scroll to face section ───────────────────────────
    print("[15] Register — face engine badge (scrolled)")
    page3 = ctx.new_page()
    page3.goto(f"{BASE}/pages/register.php", wait_until="networkidle")
    wait(page3)
    # scroll to face section
    page3.evaluate("window.scrollTo(0, document.body.scrollHeight * 0.7)")
    wait(page3, 600)
    ss(page3, "15_register_face_badge", full=False)
    page3.close()

    # ── 16. Homepage — face badge close-up ───────────────────────────────────
    print("[16] Homepage — face recognition feature card")
    page4 = ctx.new_page()
    page4.goto(BASE, wait_until="networkidle")
    wait(page4)
    page4.evaluate("window.scrollTo(0, document.querySelector('.features').offsetTop - 80)")
    wait(page4, 600)
    ss(page4, "16_homepage_features_section", full=False)
    page4.close()

    # ── 17. Admin Register page ──────────────────────────────────────────────
    print("[17] Admin Register")
    page5 = ctx.new_page()
    page5.goto(f"{BASE}/pages/admin/register.php", wait_until="networkidle")
    wait(page5)
    ss(page5, "17_admin_register")
    page5.close()

    browser.close()

print(f"\nAll screenshots saved to: {OUT_DIR}")
files = sorted(os.listdir(OUT_DIR))
print(f"Total: {len(files)} files")
for f in files:
    size = os.path.getsize(os.path.join(OUT_DIR, f))
    print(f"  {f}  ({size//1024} KB)")
