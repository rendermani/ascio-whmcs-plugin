import { test as setup, expect } from '@playwright/test';

const authFile = 'playwright/.auth/admin.json';

/**
 * Global setup that logs in once and stores authentication state.
 * All tests reuse this authenticated session to avoid repeated logins
 * which can trigger IP bans in WHMCS.
 */
setup('authenticate', async ({ page }) => {
  const adminUser = process.env.WHMCS_ADMIN_USER || 'Admin';
  const adminPass = process.env.WHMCS_ADMIN_PASS || 'smurf5506';

  // Go to admin login page
  await page.goto('/admin');

  // Wait for form to be ready
  await expect(page.locator('input[name="username"]')).toBeVisible({ timeout: 15000 });

  // Fill login form
  await page.fill('input[name="username"]', adminUser);
  await page.fill('input[name="password"]', adminPass);

  // Submit and wait for redirect
  await page.click('button[type="submit"], input[type="submit"]');

  // Wait for successful login - wait for page to settle
  await page.waitForLoadState('networkidle', { timeout: 30000 });

  // Close any modals that might appear (like "What's New" modal)
  const closeModal = page.locator('.modal .close, button[data-dismiss="modal"], .modal-header button').first();
  if (await closeModal.isVisible({ timeout: 2000 }).catch(() => false)) {
    await closeModal.click();
    await page.waitForTimeout(500);
  }

  // Verify we're on the admin panel (should see sidebar or dashboard)
  await expect(page.locator('.sidebar, #sidebar, .admin-sidebar, .main-content, #main-body')).toBeVisible({ timeout: 10000 });

  // Save storage state to reuse across tests
  await page.context().storageState({ path: authFile });
});
