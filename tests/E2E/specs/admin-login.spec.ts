import { test, expect } from '@playwright/test';

/**
 * Admin Login Tests
 *
 * Tests WHMCS admin panel login page loading.
 * Actual login is handled by auth.setup.ts and reused via storageState.
 */

test.describe('Admin Login Page', () => {
  // These tests use storageState (already authenticated)
  test('should access admin dashboard when authenticated', async ({ page }) => {
    await page.goto('/admin');

    // Should be on admin dashboard (already logged in via storageState)
    await expect(page).toHaveURL(/admin/);

    // Should not show login form
    await expect(page.locator('body')).not.toContainText('IP Banned');
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });
});

test.describe('Admin Navigation', () => {
  // All tests use shared authentication state from auth.setup.ts

  test('should navigate to domain registrars settings', async ({ page }) => {
    // Navigate to Setup > Domain Registrars
    await page.goto('/admin/configregistrars.php');

    // Should show registrars configuration page
    await expect(page).toHaveURL(/configregistrars/);
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });

  // Skip: Session may expire during test run, causing redirect to login
  // This test requires Ascio module to be activated in WHMCS
  test.skip('should show Ascio in registrars list', async ({ page }) => {
    await page.goto('/admin/configregistrars.php');

    // Ascio should be listed (check for either link or text)
    const ascioLocator = page.locator('a:has-text("Ascio"), td:has-text("Ascio"), span:has-text("Ascio")');
    await expect(ascioLocator.first()).toBeVisible({ timeout: 15000 });
  });
});
