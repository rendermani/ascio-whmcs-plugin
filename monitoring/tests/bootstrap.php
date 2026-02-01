<?php

/**
 * PHPUnit bootstrap file for Monitoring module tests.
 */

// Load core test helpers
require_once dirname(__DIR__, 2) . '/core/tests/bootstrap.php';

// Load monitoring module
require_once dirname(__DIR__) . '/lib/Monitoring.php';
require_once dirname(__DIR__) . '/lib/MonitoringCallback.php';
