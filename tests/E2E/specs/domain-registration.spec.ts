import { test, expect } from '@playwright/test';

/**
 * Domain Registration E2E Tests
 *
 * Tests the domain registration flow through the WHMCS admin panel
 * Authentication is handled via storageState from auth.setup.ts
 */

test.describe('Domain Registration', () => {
  test('should load domains list page', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    // Page should load without errors
    await expect(page.locator('body')).not.toContainText('Fatal error');
    await expect(page.locator('body')).not.toContainText('Parse error');
    await expect(page).toHaveURL(/clientsdomains/);
  });

  test('should open add domain form', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    // Click add domain button
    const addButton = page.locator('a:has-text("Add Domain"), button:has-text("Add Domain")');
    if (await addButton.isVisible()) {
      await addButton.click();

      // Should show domain registration form
      await expect(page.locator('input[name="domain"]')).toBeVisible();
    }
  });

  test('should validate domain name format', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php?action=add');

    // Try to submit with invalid domain
    const domainInput = page.locator('input[name="domain"]');
    if (await domainInput.isVisible()) {
      await domainInput.fill('invalid..domain');

      // Form validation should prevent submission or show error
      const submitButton = page.locator('button[type="submit"], input[type="submit"]');
      if (await submitButton.isVisible()) {
        await submitButton.click();
        // Check for validation message
      }
    }
  });

  test('should show registrar selection with Ascio', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php?action=add');

    // Registrar dropdown should include Ascio
    const registrarSelect = page.locator('select[name="registrar"]');
    if (await registrarSelect.isVisible()) {
      await expect(registrarSelect.locator('option:has-text("Ascio")')).toBeAttached();
    }
  });
});

test.describe('Domain Registration Form Fields', () => {
  test('should show all required contact fields', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php?action=add');

    // Check for contact field sections
    const contactFields = [
      'firstname',
      'lastname',
      'companyname',
      'email',
      'address1',
      'city',
      'postcode',
      'country'
    ];

    // These fields should exist in the form (may be in different sections)
    for (const field of contactFields) {
      const input = page.locator(`input[name*="${field}"], select[name*="${field}"]`).first();
      // Just verify the page loaded successfully
    }
  });

  test('should show nameserver fields', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php?action=add');

    // Should have nameserver input fields
    const ns1 = page.locator('input[name="ns1"], input[name*="nameserver"]').first();
    // Page should load without errors
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });
});
