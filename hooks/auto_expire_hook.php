<?php
/**
 * Ascio Auto-Expire Hook
 *
 * This hook runs on the WHMCS DailyCronJob to check domains that should be
 * set to expiring based on threshold dates.
 *
 * This implements the "AutoExpire OFF" behavior where:
 * - Domains stay Active after registration
 * - At ExpDate + Threshold date, unpaid domains are set to Expiring
 * - If domain invoice is paid, domain stays Active
 * - If domain is already Expiring and invoice gets paid, domain is Unexpired
 *
 * To use: Include this file from the main hooks.php or place in WHMCS includes/hooks/
 */

use ascio\AutoExpireService;

// Require the AutoExpireService class
require_once(__DIR__ . '/../lib/AutoExpireService.php');

/**
 * DailyCronJob hook for auto-expire processing
 *
 * Only runs when AutoExpire setting is OFF (unchecked in module config)
 * When AutoExpire is ON, the existing immediate expire behavior takes effect
 */
add_hook('DailyCronJob', 1, function ($vars) {
    try {
        // Get registrar configuration
        $registrarConfig = getRegistrarConfigOptions('ascio');

        // Only run if AutoExpire is OFF (not set or 'off')
        // When AutoExpire is ON, domains are expired immediately after register/transfer
        // When AutoExpire is OFF (default), we use threshold-based expiration
        if (isset($registrarConfig['AutoExpire']) && $registrarConfig['AutoExpire'] === 'on') {
            // AutoExpire is ON - skip threshold-based expiration
            // The immediate expiration happens in callbacks.php after registration/transfer
            return;
        }

        logActivity('Ascio AutoExpire: Starting daily threshold check (AutoExpire=OFF mode)');

        // Initialize the AutoExpireService with registrar config
        $params = [
            'Username' => $registrarConfig['Username'] ?? '',
            'Password' => $registrarConfig['Password'] ?? '',
            'TestMode' => $registrarConfig['TestMode'] ?? '',
        ];

        $autoExpireService = new AutoExpireService($params);

        // Run the auto-expire check
        $results = $autoExpireService->run();

        // Log summary
        $summary = $results['summary'];
        $logMessage = sprintf(
            'Ascio AutoExpire: Completed - Processed: %d, Expired: %d, Unexpired: %d, Skipped (paid): %d, Errors: %d',
            $summary['total_processed'],
            $summary['total_expired'],
            $summary['total_unexpired'],
            $summary['total_skipped_paid'],
            $summary['total_errors']
        );
        logActivity($logMessage);

        // Log any errors
        $allErrors = array_merge(
            $results['threshold_check']['errors'] ?? [],
            $results['unexpire_check']['errors'] ?? []
        );

        foreach ($allErrors as $error) {
            $errorMessage = sprintf(
                'Ascio AutoExpire Error - Domain: %s (ID: %s) - %s',
                $error['domain'] ?? 'N/A',
                $error['domain_id'] ?? 'N/A',
                $error['error'] ?? 'Unknown error'
            );
            logActivity($errorMessage);
        }

    } catch (\Exception $e) {
        logActivity('Ascio AutoExpire: Fatal error - ' . $e->getMessage());
    }
});

/**
 * InvoicePaid hook to check if we need to unexpire domains
 *
 * When an invoice is paid that contains domain renewal items,
 * check if the domain is currently in expiring state and unexpire it
 */
add_hook('InvoicePaid', 1, function ($vars) {
    try {
        $invoiceId = $vars['invoiceid'];

        // Get registrar configuration
        $registrarConfig = getRegistrarConfigOptions('ascio');

        // Only run if AutoExpire is OFF
        if (isset($registrarConfig['AutoExpire']) && $registrarConfig['AutoExpire'] === 'on') {
            return;
        }

        // Check if this invoice has any Ascio domain items
        $domainItems = \Illuminate\Database\Capsule\Manager::table('tblinvoiceitems')
            ->join('tbldomains', 'tblinvoiceitems.relid', '=', 'tbldomains.id')
            ->where('tblinvoiceitems.invoiceid', $invoiceId)
            ->where('tblinvoiceitems.type', 'Domain')
            ->where('tbldomains.registrar', 'ascio')
            ->select('tbldomains.id', 'tbldomains.domain')
            ->get();

        if ($domainItems->isEmpty()) {
            return;
        }

        logActivity("Ascio AutoExpire: Invoice {$invoiceId} paid, checking " . $domainItems->count() . " Ascio domain(s) for unexpire");

        // Initialize the AutoExpireService
        $params = [
            'Username' => $registrarConfig['Username'] ?? '',
            'Password' => $registrarConfig['Password'] ?? '',
            'TestMode' => $registrarConfig['TestMode'] ?? '',
        ];

        $autoExpireService = new AutoExpireService($params);

        // Process each domain
        foreach ($domainItems as $domain) {
            try {
                // Use reflection to call the protected unexpireDomain method
                // or create a public method wrapper
                $result = $autoExpireService->processExpiredButPaidDomains();

                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $error) {
                        logActivity("Ascio AutoExpire: Error unexpiring {$domain->domain} - " . $error['error']);
                    }
                }
            } catch (\Exception $e) {
                logActivity("Ascio AutoExpire: Error processing domain {$domain->domain} - " . $e->getMessage());
            }
        }

    } catch (\Exception $e) {
        logActivity('Ascio AutoExpire: Error in InvoicePaid hook - ' . $e->getMessage());
    }
});
