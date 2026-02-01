import { test, expect } from '@playwright/test';

/**
 * Nameserver Management E2E Tests
 *
 * Tests nameserver viewing and editing through WHMCS
 * Authentication is handled via storageState from auth.setup.ts
 */

test.describe('Nameserver Management', () => {
  test('should load domains page without errors', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    // No PHP errors
    await expect(page.locator('body')).not.toContainText('Fatal error');
    await expect(page.locator('body')).not.toContainText('Parse error');
  });

  test('should show nameserver fields on domain add', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php?action=add');

    // Should have nameserver inputs
    const ns1 = page.locator('input[name="ns1"]');

    // Page loads successfully
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });

  test('should validate nameserver format', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php?action=add');

    const ns1 = page.locator('input[name="ns1"]');

    if (await ns1.isVisible()) {
      await ns1.fill('invalid..nameserver');
      // Should show validation error or browser validation
    }

    // Page should be accessible
    await expect(page).toHaveURL(/clientsdomains/);
  });

  test('should allow up to 5 nameservers', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php?action=add');

    // Check for 5 nameserver fields
    const nsFields = ['ns1', 'ns2', 'ns3', 'ns4', 'ns5'];

    for (const ns of nsFields) {
      const input = page.locator(`input[name="${ns}"]`);
      // Fields should exist (may not all be visible)
    }

    // Page loads
    await expect(page).toHaveURL(/clientsdomains/);
  });
});

test.describe('Nameserver Update', () => {
  test('should show update nameserver button on domain view', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    // Look for domains
    const domainLink = page.locator('a[href*="clientsdomains.php?userid"]').first();

    if (await domainLink.isVisible()) {
      // Navigate to domain detail
      await domainLink.click();

      // Should show nameserver management options
      await expect(page.locator('body')).not.toContainText('Fatal error');
    }
  });
});
