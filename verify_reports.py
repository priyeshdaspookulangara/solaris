import os
from playwright.sync_api import sync_playwright

def verify_reports():
    os.makedirs('/home/jules/verification/screenshots', exist_ok=True)
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        # 1. Customer Reports
        page.goto('http://127.0.0.1:8000/login.php')
        page.fill('input[name="username"]', 'sarah_trader')
        page.fill('input[name="password"]', 'User123!')
        page.click('button[type="submit"]')
        page.wait_for_url('**/dashboard.php')

        # Navigate to reports via sidebar
        page.click('a[href="/reports.php"]')
        page.wait_for_url('**/reports.php')
        page.wait_for_timeout(500)
        page.screenshot(path='/home/jules/verification/screenshots/customer_report.png', full_page=True)

        # Emulate print media to capture print preview layout
        page.emulate_media(media='print')
        page.screenshot(path='/home/jules/verification/screenshots/customer_report_print.png', full_page=True)
        page.emulate_media(media='screen')

        # 2. Admin Reports
        page.goto('http://127.0.0.1:8000/logout.php')
        page.goto('http://127.0.0.1:8000/login.php')
        page.fill('input[name="username"]', 'admin')
        page.fill('input[name="password"]', 'Admin123!')
        page.click('button[type="submit"]')
        page.wait_for_url('**/admin.php')

        page.click('a[href="/admin_reports.php"]')
        page.wait_for_url('**/admin_reports.php')
        page.wait_for_timeout(500)
        page.screenshot(path='/home/jules/verification/screenshots/admin_report.png', full_page=True)

        # Emulate print media for admin audit
        page.emulate_media(media='print')
        page.screenshot(path='/home/jules/verification/screenshots/admin_report_print.png', full_page=True)

        browser.close()
        print("Reports verification screenshots generated successfully.")

if __name__ == '__main__':
    verify_reports()
