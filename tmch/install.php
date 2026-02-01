<?php

/**
 * Ascio TMCH module installer.
 * Creates required database tables.
 */

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Run the installation.
 *
 * @return array Result with 'success' and 'message' keys
 */
function asciotmch_install(): array
{
    try {
        $sql = file_get_contents(__DIR__ . '/install/install.sql');

        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => !empty($s) && !str_starts_with(trim($s), '--')
        );

        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                Capsule::statement($statement);
            }
        }

        return [
            'success' => true,
            'message' => 'Ascio TMCH module installed successfully.',
        ];

    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Installation failed: ' . $e->getMessage(),
        ];
    }
}

/**
 * Check if tables exist.
 *
 * @return bool
 */
function asciotmch_tables_exist(): bool
{
    return Capsule::schema()->hasTable('mod_ascio_tmch');
}

/**
 * Uninstall the module.
 *
 * @return array
 */
function asciotmch_uninstall(): array
{
    try {
        Capsule::schema()->dropIfExists('mod_ascio_tmch_documents');
        Capsule::schema()->dropIfExists('mod_ascio_tmch');

        return [
            'success' => true,
            'message' => 'Ascio TMCH module uninstalled successfully.',
        ];

    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Uninstallation failed: ' . $e->getMessage(),
        ];
    }
}

// Run installation if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($argv[0])) {
    require_once __DIR__ . '/../../init.php';
    $result = asciotmch_install();
    echo $result['message'] . PHP_EOL;
    exit($result['success'] ? 0 : 1);
}
