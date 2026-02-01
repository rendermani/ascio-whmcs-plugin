<?php

/**
 * PHPUnit bootstrap file for Monitoring module unit tests.
 *
 * Loads mock classes and dependencies for isolated testing.
 */

declare(strict_types=1);

// Load core test helpers (includes mocks and WHMCS function stubs)
require_once dirname(__DIR__, 3) . '/core/tests/bootstrap.php';

// Load monitoring module classes
require_once dirname(__DIR__, 2) . '/lib/Monitoring.php';
require_once dirname(__DIR__, 2) . '/lib/MonitoringCallback.php';
