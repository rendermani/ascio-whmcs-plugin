<?php

/**
 * Ascio Core module installer.
 * Creates the shared settings table used by all Ascio modules.
 */

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Run the installation.
 *
 * @return array Result with 'success' and 'message' keys
 */
function asciocore_install(): array
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
            'message' => 'Ascio Core settings table installed successfully.',
        ];

    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Installation failed: ' . $e->getMessage(),
        ];
    }
}

/**
 * Check if settings table exists.
 *
 * @return bool
 */
function asciocore_tables_exist(): bool
{
    return Capsule::schema()->hasTable('mod_ascio_settings');
}

/**
 * Migrate settings from old SSL table if it exists.
 *
 * @return array
 */
function asciocore_migrate_from_ssl(): array
{
    try {
        if (!Capsule::schema()->hasTable('mod_asciossl_settings')) {
            return ['success' => true, 'message' => 'No SSL settings to migrate'];
        }

        // Copy settings from SSL table to core table
        $sslSettings = Capsule::table('mod_asciossl_settings')->get();

        foreach ($sslSettings as $setting) {
            Capsule::table('mod_ascio_settings')
                ->updateOrInsert(
                    ['name' => $setting->name],
                    ['value' => $setting->value, 'role' => $setting->role]
                );
        }

        return [
            'success' => true,
            'message' => 'Migrated ' . count($sslSettings) . ' settings from SSL module.',
        ];

    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Migration failed: ' . $e->getMessage(),
        ];
    }
}

// Run installation if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($argv[0])) {
    require_once __DIR__ . '/../../init.php';

    echo "Installing Ascio Core...\n";
    $result = asciocore_install();
    echo $result['message'] . "\n";

    if ($result['success']) {
        echo "Checking for SSL settings to migrate...\n";
        $migrate = asciocore_migrate_from_ssl();
        echo $migrate['message'] . "\n";
    }

    exit($result['success'] ? 0 : 1);
}
