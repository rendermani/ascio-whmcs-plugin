<?php

/**
 * PHPUnit bootstrap file for TMCH module unit tests.
 *
 * Loads core test helpers and TMCH module classes.
 */

declare(strict_types=1);

// Load core test helpers (MockDatabase, MockAscioClient, MockParams)
require_once dirname(__DIR__, 3) . '/core/tests/bootstrap.php';

// Load TMCH module classes
require_once dirname(__DIR__, 2) . '/lib/Tmch.php';
require_once dirname(__DIR__, 2) . '/lib/TmchCallback.php';

// Load v3 service classes if available (for type hints)
$v3AutoloadPath = dirname(__DIR__, 3) . '/ssl/v3/service/autoload.php';
if (file_exists($v3AutoloadPath)) {
    require_once $v3AutoloadPath;
}
