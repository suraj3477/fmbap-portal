import time
import os
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By

def main():
    os.makedirs(r"d:\fmbap-portal-main - Copy\screenshots", exist_ok=True)
    chrome_options = Options()
    chrome_options.add_argument('--headless=new')
    chrome_options.add_argument('--disable-gpu')
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--ignore-certificate-errors')
    chrome_options.add_argument('--allow-insecure-localhost')
    chrome_options.add_argument('--window-size=1920,1080')
    chrome_options.add_argument(r'--user-data-dir=C:\Users\HP\AppData\Local\Temp\chrome_temp_selenium_screens')

    driver = webdriver.Chrome(options=chrome_options)
    try:
        print("Navigating to login page...")
        driver.get("https://103.162.247.169/login")
        time.sleep(2)
        driver.save_screenshot(r"d:\fmbap-portal-main - Copy\screenshots\live_01_login.png")
        print("Captured live_01_login.png")

        # Login
        email_el = driver.find_element(By.CSS_SELECTOR, "input[type='email']")
        pass_el = driver.find_element(By.CSS_SELECTOR, "input[type='password']")
        email_el.clear()
        email_el.send_keys("admin@brahmaputraboard.gov.in")
        pass_el.clear()
        pass_el.send_keys("password@123")

        submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_btn.click()
        time.sleep(3)
        print("Logged in. Current URL:", driver.current_url)
        driver.save_screenshot(r"d:\fmbap-portal-main - Copy\screenshots\live_02_dashboard.png")
        print("Captured live_02_dashboard.png")

        # 1. Schemes Catalogue
        print("Visiting Schemes...")
        driver.get("https://103.162.247.169/schemes")
        time.sleep(2.5)
        driver.save_screenshot(r"d:\fmbap-portal-main - Copy\screenshots\live_03_schemes_catalogue.png")
        print("Captured live_03_schemes_catalogue.png")

        # 2. Fund Releases List
        print("Visiting Fund Releases...")
        driver.get("https://103.162.247.169/fund-releases")
        time.sleep(2.5)
        driver.save_screenshot(r"d:\fmbap-portal-main - Copy\screenshots\live_04_fund_releases.png")
        print("Captured live_04_fund_releases.png")

        # 3. Create Fund Release / GFR 12-A form
        print("Visiting GFR 12-A creation...")
        driver.get("https://103.162.247.169/fund-releases/create")
        time.sleep(2.5)
        driver.save_screenshot(r"d:\fmbap-portal-main - Copy\screenshots\live_05_gfr12a_form.png")
        print("Captured live_05_gfr12a_form.png")

        # 4. Progress Reports (Physical & Financial)
        print("Visiting Progress Reports...")
        driver.get("https://103.162.247.169/progress-reports")
        time.sleep(2.5)
        driver.save_screenshot(r"d:\fmbap-portal-main - Copy\screenshots\live_06_progress_reports.png")
        print("Captured live_06_progress_reports.png")

        # 5. Site Monitoring / Inspections
        print("Visiting Site Monitoring...")
        driver.get("https://103.162.247.169/monitoring-requests")
        time.sleep(2.5)
        driver.save_screenshot(r"d:\fmbap-portal-main - Copy\screenshots\live_07_site_monitoring.png")
        print("Captured live_07_site_monitoring.png")

        # 6. Analytics Cockpit
        print("Visiting Analytics Cockpit...")
        driver.get("https://103.162.247.169/analytics")
        time.sleep(3.0)
        driver.save_screenshot(r"d:\fmbap-portal-main - Copy\screenshots\live_08_analytics_cockpit.png")
        print("Captured live_08_analytics_cockpit.png")

        # 7. User Management (Admin)
        print("Visiting User Management...")
        driver.get("https://103.162.247.169/admin/users")
        time.sleep(2.5)
        driver.save_screenshot(r"d:\fmbap-portal-main - Copy\screenshots\live_09_user_management.png")
        print("Captured live_09_user_management.png")

        print("ALL SCREENSHOTS CAPTURED SUCCESSFULLY!")

    except Exception as e:
        print("Error during capture:", e)
        driver.save_screenshot(r"d:\fmbap-portal-main - Copy\screenshots\error_capture.png")
    finally:
        driver.quit()

if __name__ == "__main__":
    main()
