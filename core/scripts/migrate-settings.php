<?php

/**
 * Migration script: Copy Ascio credentials to shared settings table.
 *
 * Migrates settings from:
 * - mod_asciossl_settings (SSL module)
 * - WHMCS registrar config (domains module)
 *
 * Usage: php migrate-settings.php
 */

require_once realpath(dirname(__FILE__)) . '/../../../init.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "=== Ascio Settings Migration ===\n\n";

// Step 1: Ensure mod_ascio_settings table exists
echo "Step 1: Checking/creating mod_ascio_settings table...\n";

if (!Capsule::schema()->hasTable('mod_ascio_settings')) {
    Capsule::schema()->create('mod_ascio_settings', function ($table) {
        $table->increments('id');
        $table->string('name', 255)->unique();
        $table->text('value')->nullable();
        $table->enum('role', ['User', 'Admin', ''])->default('User');
        $table->string('description', 500)->nullable();
    });
    echo "  Created mod_ascio_settings table.\n";
} else {
    echo "  Table already exists.\n";
}

// Step 2: Try to migrate from SSL settings
echo "\nStep 2: Checking SSL module settings (mod_asciossl_settings)...\n";

$sslMigrated = 0;
if (Capsule::schema()->hasTable('mod_asciossl_settings')) {
    $sslSettings = Capsule::table('mod_asciossl_settings')->get();

    foreach ($sslSettings as $setting) {
        // Skip if already exists in new table
        $exists = Capsule::table('mod_ascio_settings')
            ->where('name', $setting->name)
            ->exists();

        if (!$exists && !empty($setting->value)) {
            Capsule::table('mod_ascio_settings')->insert([
                'name' => $setting->name,
                'value' => $setting->value,
                'role' => $setting->role ?? 'User',
            ]);
            echo "  Migrated: {$setting->name}\n";
            $sslMigrated++;
        } elseif ($exists) {
            echo "  Skipped (exists): {$setting->name}\n";
        } else {
            echo "  Skipped (empty): {$setting->name}\n";
        }
    }

    echo "  Total migrated from SSL: {$sslMigrated}\n";
} else {
    echo "  SSL settings table not found.\n";
}

// Step 3: Try to migrate from domains registrar config
echo "\nStep 3: Checking Domains registrar config (tblregistrars)...\n";

$domainsMigrated = 0;
try {
    // Get Ascio registrar config
    $domainConfig = Capsule::table('tblregistrars')
        ->where('registrar', 'ascio')
        ->pluck('value', 'setting')
        ->toArray();

    if (!empty($domainConfig)) {
        // Map domains config keys to settings keys
        $mapping = [
            'Account' => 'Account',
            'Password' => 'Password',
            'TestModeAccount' => 'AccountTesting',
            'TestModePassword' => 'PasswordTesting',
            'TestMode' => 'Environment', // 'on' -> 'testing', otherwise 'live'
        ];

        foreach ($mapping as $domainKey => $settingKey) {
            if (!isset($domainConfig[$domainKey]) || empty($domainConfig[$domainKey])) {
                continue;
            }

            $value = $domainConfig[$domainKey];

            // Handle TestMode -> Environment conversion
            if ($domainKey === 'TestMode') {
                $value = ($value === 'on') ? 'testing' : 'live';
            }

            // Decrypt password if needed (WHMCS stores encrypted)
            if (in_array($domainKey, ['Password', 'TestModePassword'])) {
                try {
                    $decrypted = decrypt($value);
                    $value = $decrypted;
                } catch (\Exception $e) {
                    // Already decrypted or can't decrypt
                }
            }

            // Check if setting exists and is empty
            $existing = Capsule::table('mod_ascio_settings')
                ->where('name', $settingKey)
                ->first();

            if (!$existing) {
                Capsule::table('mod_ascio_settings')->insert([
                    'name' => $settingKey,
                    'value' => $value,
                    'role' => 'User',
                ]);
                echo "  Migrated: {$domainKey} -> {$settingKey}\n";
                $domainsMigrated++;
            } elseif (empty($existing->value) && !empty($value)) {
                Capsule::table('mod_ascio_settings')
                    ->where('name', $settingKey)
                    ->update(['value' => $value]);
                echo "  Updated empty: {$domainKey} -> {$settingKey}\n";
                $domainsMigrated++;
            } else {
                echo "  Skipped (has value): {$settingKey}\n";
            }
        }

        echo "  Total migrated from Domains: {$domainsMigrated}\n";
    } else {
        echo "  No Ascio registrar config found.\n";
    }
} catch (\Exception $e) {
    echo "  Error reading domains config: {$e->getMessage()}\n";
}

// Step 4: Ensure all required settings exist
echo "\nStep 4: Ensuring required settings exist...\n";

$requiredSettings = [
    'Account' => ['default' => '', 'description' => 'Live API account username'],
    'Password' => ['default' => '', 'description' => 'Live API account password'],
    'AccountTesting' => ['default' => '', 'description' => 'Test/Demo API account username'],
    'PasswordTesting' => ['default' => '', 'description' => 'Test/Demo API account password'],
    'Environment' => ['default' => 'testing', 'description' => 'Environment: testing or live'],
    'DbVersion' => ['default' => '1.0', 'description' => 'Database schema version'],
];

foreach ($requiredSettings as $name => $config) {
    $exists = Capsule::table('mod_ascio_settings')
        ->where('name', $name)
        ->exists();

    if (!$exists) {
        Capsule::table('mod_ascio_settings')->insert([
            'name' => $name,
            'value' => $config['default'],
            'role' => $name === 'DbVersion' ? 'Admin' : 'User',
            'description' => $config['description'],
        ]);
        echo "  Created default: {$name}\n";
    }
}

// Summary
echo "\n=== Migration Complete ===\n";
echo "Settings in mod_ascio_settings:\n";

$allSettings = Capsule::table('mod_ascio_settings')->get();
foreach ($allSettings as $s) {
    $displayValue = in_array($s->name, ['Password', 'PasswordTesting'])
        ? (empty($s->value) ? '(empty)' : '***')
        : (empty($s->value) ? '(empty)' : $s->value);
    echo "  {$s->name}: {$displayValue}\n";
}

echo "\nDone.\n";
