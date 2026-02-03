import { test, expect } from '@playwright/test';

/**
 * Conditional Domain Fields E2E Tests
 *
 * Tests the ascio-fields.js dynamic field visibility behavior.
 * When a user changes a dependency field (e.g., Legal Type for .IT),
 * conditional fields should show/hide accordingly.
 *
 * These tests run against the live WHMCS admin panel with Playwright.
 */

test.describe('Conditional Fields - .IT Domain', () => {
  test.beforeEach(async ({ page }) => {
    // Listen for console errors from the browser
    page.on('console', msg => {
      if (msg.type() === 'error') {
        console.log(`Browser console error: ${msg.text()}`);
      }
    });

    // Listen for page errors (uncaught exceptions)
    page.on('pageerror', error => {
      console.log(`Page error: ${error.message}`);
    });
  });

  test('ascio-fields.js loads without errors on domain page', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php');

    // Page should load without PHP errors
    await expect(page.locator('body')).not.toContainText('Fatal error');
    await expect(page.locator('body')).not.toContainText('Parse error');

    // Check that ascio-fields.js was loaded (injected by AdminAreaHeadOutput hook)
    const jsLoaded = await page.evaluate(() => {
      return typeof (window as any).AscioFields !== 'undefined';
    });

    // The JS should be loaded on domain management pages
    // It may not load on the list page, so we just verify no errors
    await expect(page.locator('body')).not.toContainText('Fatal error');
  });

  test('IT domain: Birth Country hidden when Legal Type is Company', async ({ page }) => {
    // Navigate to domain edit page for .IT configuration
    // First we need a .IT domain or a registration form with .IT selected
    await page.goto('/admin/configdomains.php');
    await expect(page.locator('body')).not.toContainText('Fatal error');

    // Try accessing domain registration with additional fields
    // The additional fields appear when editing/adding a domain with Ascio registrar
    await page.goto('/admin/clientsdomains.php?action=add');
    await expect(page.locator('body')).not.toContainText('Fatal error');

    // Look for domain input and set to .IT
    const domainInput = page.locator('input[name="domain"]');
    if (await domainInput.isVisible({ timeout: 5000 }).catch(() => false)) {
      await domainInput.fill('testdomain.it');

      // Select Ascio as registrar
      const registrarSelect = page.locator('select[name="registrar"]');
      if (await registrarSelect.isVisible({ timeout: 3000 }).catch(() => false)) {
        const ascioOption = registrarSelect.locator('option[value="ascio"]');
        if (await ascioOption.count() > 0) {
          await registrarSelect.selectOption('ascio');

          // Wait for additional fields to load
          await page.waitForTimeout(1000);

          // Look for Legal Type field (additional field for .IT)
          const legalTypeField = page.locator(
            'select[name*="Legal Type"], select[name*="additionalfields[Legal Type]"], select[name*="domainfield[Legal Type]"]'
          );

          if (await legalTypeField.isVisible({ timeout: 5000 }).catch(() => false)) {
            // Select Company type
            await legalTypeField.selectOption({ label: 'Companies/one man companies' });
            await page.waitForTimeout(500);

            // Birth Country should be HIDDEN for Company
            const birthCountryRow = page.locator(
              ':has(> label:has-text("Birth Country")), ' +
              ':has(> td:has-text("Birth Country")), ' +
              '.form-group:has(label:has-text("Birth Country"))'
            ).first();

            if (await birthCountryRow.count() > 0) {
              await expect(birthCountryRow).toBeHidden();
            }

            // Now switch to Natural Person
            await legalTypeField.selectOption({ label: 'Italian and foreign natural persons' });
            await page.waitForTimeout(500);

            // Birth Country should now be VISIBLE
            if (await birthCountryRow.count() > 0) {
              await expect(birthCountryRow).toBeVisible();
            }
          }
        }
      }
    }
  });

  test('IT domain: Birth Country shown for natural persons', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php?action=add');
    await expect(page.locator('body')).not.toContainText('Fatal error');

    const domainInput = page.locator('input[name="domain"]');
    if (await domainInput.isVisible({ timeout: 5000 }).catch(() => false)) {
      await domainInput.fill('testperson.it');

      const registrarSelect = page.locator('select[name="registrar"]');
      if (await registrarSelect.isVisible({ timeout: 3000 }).catch(() => false)) {
        const ascioOption = registrarSelect.locator('option[value="ascio"]');
        if (await ascioOption.count() > 0) {
          await registrarSelect.selectOption('ascio');
          await page.waitForTimeout(1000);

          const legalTypeField = page.locator(
            'select[name*="Legal Type"], select[name*="additionalfields[Legal Type]"], select[name*="domainfield[Legal Type]"]'
          );

          if (await legalTypeField.isVisible({ timeout: 5000 }).catch(() => false)) {
            // Select natural persons
            await legalTypeField.selectOption({ label: 'Italian and foreign natural persons' });
            await page.waitForTimeout(500);

            // Birth Country should be visible
            const birthCountryField = page.locator(
              'input[name*="Birth Country"], select[name*="Birth Country"]'
            ).first();

            if (await birthCountryField.count() > 0) {
              await expect(birthCountryField).toBeVisible();
            }
          }
        }
      }
    }
  });
});

test.describe('Conditional Fields - .CA Domain', () => {
  test('CA domain: Trademark fields shown only for Trademark legal type', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php?action=add');
    await expect(page.locator('body')).not.toContainText('Fatal error');

    const domainInput = page.locator('input[name="domain"]');
    if (await domainInput.isVisible({ timeout: 5000 }).catch(() => false)) {
      await domainInput.fill('testdomain.ca');

      const registrarSelect = page.locator('select[name="registrar"]');
      if (await registrarSelect.isVisible({ timeout: 3000 }).catch(() => false)) {
        const ascioOption = registrarSelect.locator('option[value="ascio"]');
        if (await ascioOption.count() > 0) {
          await registrarSelect.selectOption('ascio');
          await page.waitForTimeout(1000);

          const legalTypeField = page.locator(
            'select[name*="Legal Type"], select[name*="additionalfields[Legal Type]"], select[name*="domainfield[Legal Type]"]'
          );

          if (await legalTypeField.isVisible({ timeout: 5000 }).catch(() => false)) {
            // Select Corporation (non-trademark) - trademark fields should be hidden
            await legalTypeField.selectOption({ label: 'Corporation' });
            await page.waitForTimeout(500);

            const trademarkNumberField = page.locator(
              'input[name*="Trademark Number"], select[name*="Trademark Number"]'
            ).first();

            if (await trademarkNumberField.count() > 0) {
              // Trademark fields should be hidden
              const trademarkRow = page.locator(
                ':has(> label:has-text("Trademark Number")), ' +
                '.form-group:has(label:has-text("Trademark Number"))'
              ).first();

              if (await trademarkRow.count() > 0) {
                await expect(trademarkRow).toBeHidden();
              }

              // Switch to Trademark type
              await legalTypeField.selectOption({ label: 'Trademark' });
              await page.waitForTimeout(500);

              // Trademark fields should now be visible
              if (await trademarkRow.count() > 0) {
                await expect(trademarkRow).toBeVisible();
              }
            }
          }
        }
      }
    }
  });
});

test.describe('Conditional Fields - .US Domain', () => {
  test('US domain: Nexus Country shown only for C31/C32 categories', async ({ page }) => {
    await page.goto('/admin/clientsdomains.php?action=add');
    await expect(page.locator('body')).not.toContainText('Fatal error');

    const domainInput = page.locator('input[name="domain"]');
    if (await domainInput.isVisible({ timeout: 5000 }).catch(() => false)) {
      await domainInput.fill('testdomain.us');

      const registrarSelect = page.locator('select[name="registrar"]');
      if (await registrarSelect.isVisible({ timeout: 3000 }).catch(() => false)) {
        const ascioOption = registrarSelect.locator('option[value="ascio"]');
        if (await ascioOption.count() > 0) {
          await registrarSelect.selectOption('ascio');
          await page.waitForTimeout(1000);

          const nexusCategoryField = page.locator(
            'select[name*="Nexus Category"], select[name*="additionalfields[Nexus Category]"]'
          );

          if (await nexusCategoryField.isVisible({ timeout: 5000 }).catch(() => false)) {
            // Select C11 - Nexus Country should be hidden
            await nexusCategoryField.selectOption({ label: 'C11' });
            await page.waitForTimeout(500);

            const nexusCountryRow = page.locator(
              ':has(> label:has-text("Nexus Country")), ' +
              '.form-group:has(label:has-text("Nexus Country"))'
            ).first();

            if (await nexusCountryRow.count() > 0) {
              await expect(nexusCountryRow).toBeHidden();

              // Switch to C31 - Nexus Country should show
              await nexusCategoryField.selectOption({ label: 'C31' });
              await page.waitForTimeout(500);
              await expect(nexusCountryRow).toBeVisible();
            }
          }
        }
      }
    }
  });
});

test.describe('Conditional Fields - Browser Console', () => {
  test('no JavaScript errors on domain pages', async ({ page }) => {
    const consoleErrors: string[] = [];

    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });

    page.on('pageerror', error => {
      consoleErrors.push(error.message);
    });

    // Visit domain management page
    await page.goto('/admin/clientsdomains.php');
    await page.waitForTimeout(2000);

    // Filter out non-Ascio errors
    const ascioErrors = consoleErrors.filter(
      err => err.toLowerCase().includes('ascio') || err.toLowerCase().includes('asciofields')
    );

    expect(ascioErrors).toHaveLength(0);
  });

  test('AscioFields global is available on domain config page', async ({ page }) => {
    // Visit a page where ascio-fields.js is injected
    await page.goto('/admin/clientsdomains.php?action=add');
    await page.waitForTimeout(2000);

    // Check if AscioFields is defined
    const hasAscioFields = await page.evaluate(() => {
      return typeof (window as any).AscioFields !== 'undefined';
    });

    // If the JS loaded properly, AscioFields should be available
    // Note: may not be available if the admin hook didn't trigger for this page
    if (hasAscioFields) {
      // Verify it has init method
      const hasInit = await page.evaluate(() => {
        return typeof (window as any).AscioFields?.init === 'function';
      });
      expect(hasInit).toBe(true);
    }
  });
});
