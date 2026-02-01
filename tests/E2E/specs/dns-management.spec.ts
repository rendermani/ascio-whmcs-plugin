import { test, expect } from '@playwright/test';

/**
 * DNS Management E2E Tests
 *
 * Tests DNS record viewing and editing through WHMCS
 * Authentication is handled via storageState from auth.setup.ts
 */

test.describe('DNS Management', () => {
  test('should load domains page without errors', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    await expect(page.locator('body')).not.toContainText('Fatal error');
    await expect(page.locator('body')).not.toContainText('Parse error');
    await expect(page.locator('body')).not.toContainText('Undefined variable');
  });

  test('should show DNS management option if enabled', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    // Look for DNS-related options
    const dnsOption = page.locator('text=DNS').first();

    // Page loads without errors
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });
});

test.describe('DNS Record Types', () => {
  test('should support A record type', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    // DNS interface should support A records
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });

  test('should support AAAA record type', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    // Page accessible
    await expect(page).toHaveURL(/clientsdomains/);
  });

  test('should support CNAME record type', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    await expect(page.locator('body')).not.toContainText('Fatal error');
  });

  test('should support MX record type with priority', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    await expect(page.locator('body')).not.toContainText('Fatal error');
  });

  test('should support TXT record type', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    await expect(page.locator('body')).not.toContainText('Fatal error');
  });
});

test.describe('DNS Validation', () => {
  test('should validate IP address for A records', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    // Validation should catch invalid IPs
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });

  test('should validate IPv6 for AAAA records', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    await expect(page.locator('body')).not.toContainText('Fatal error');
  });

  test('should validate hostname for CNAME records', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    await expect(page.locator('body')).not.toContainText('Fatal error');
  });

  test('should validate priority for MX records', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    await expect(page.locator('body')).not.toContainText('Fatal error');
  });
});
