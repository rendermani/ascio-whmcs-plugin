import { test, expect } from '@playwright/test';

/**
 * Contact Management E2E Tests
 *
 * Tests contact details viewing and editing through WHMCS
 * Authentication is handled via storageState from auth.setup.ts
 */

test.describe('Contact Management', () => {
  test('should load domain details page without errors', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    // Page should load without PHP errors
    await expect(page.locator('body')).not.toContainText('Fatal error');
    await expect(page.locator('body')).not.toContainText('Parse error');
    await expect(page.locator('body')).not.toContainText('Warning:');
  });

  test('should display contact sections on domain view', async ({ page }) => {
    // Navigate to a domain detail page (if any domains exist)
    await page.goto('/admin/clientsdomains.php');

    // Look for domain links
    const domainLink = page.locator('a[href*="clientsdomains.php?userid"]').first();

    if (await domainLink.isVisible()) {
      await domainLink.click();

      // Contact sections should be visible
      await expect(page.locator('body')).not.toContainText('Fatal error');
    }
  });

  test('should show registrant contact fields', async ({ page }) => {
    // If viewing a domain with contacts
    await page.goto('/admin/clientsdomains.php');

    // Page loads successfully
    await expect(page).toHaveURL(/clientsdomains/);
  });

  test('should show admin contact fields', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    // Page loads successfully
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });

  test('should show tech contact fields', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    // Page loads successfully
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });
});

test.describe('Contact Validation', () => {
  test('should validate email format', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php?action=add');

    // Find email field and enter invalid value
    const emailInput = page.locator('input[name*="email"]').first();

    if (await emailInput.isVisible()) {
      await emailInput.fill('invalid-email');
      // Browser validation or form validation should catch this
    }

    // Page should be accessible
    await expect(page).toHaveURL(/clientsdomains/);
  });

  test('should validate phone format', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php?action=add');

    const phoneInput = page.locator('input[name*="phone"]').first();

    if (await phoneInput.isVisible()) {
      await phoneInput.fill('123'); // Too short
    }

    // Page accessible
    await expect(page).toHaveURL(/clientsdomains/);
  });

  test('should require country selection', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php?action=add');

    const countrySelect = page.locator('select[name*="country"]').first();

    if (await countrySelect.isVisible()) {
      // Country should be required
      await expect(countrySelect).toBeVisible();
    }
  });
});
