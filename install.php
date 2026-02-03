<?php
/**
 * Ascio WHMCS Registrar Module - Installation Script
 *
 * Creates required database tables and syncs TLD data from Ascio TLDKit API.
 * Uses WHMCS Capsule ORM for database operations (PHP 7.0+ compatible).
 */

use WHMCS\Database\Capsule;
use ascio\Tools as Tools;

require_once(__DIR__ . "/../../../init.php");
require_once(__DIR__ . "/lib/Tools.php");

error_reporting(E_ALL);
ini_set('error_reporting', E_ERROR);
ini_set('display_errors', "on");

$isCLI = (php_sapi_name() == 'cli');
$lineBreak = $isCLI ? "\n" : "<br>\n";

function check($name, $value) {
    global $lineBreak;
    $ret = $value ? "ok" : "failed";
    echo "- Check " . $name . ": " . $ret . $lineBreak;
    if ($ret == "failed") {
        die("Please fix the errors and retry" . $lineBreak);
    }
}

function executeSchema($description, $callback) {
    global $lineBreak;
    echo "- " . $description . $lineBreak;
    try {
        $callback();
        echo "  [OK]" . $lineBreak;
    } catch (\Exception $e) {
        // Ignore "already exists" errors for idempotent installation
        if (strpos($e->getMessage(), 'already exists') === false &&
            strpos($e->getMessage(), 'Duplicate') === false) {
            echo "  [Warning: " . $e->getMessage() . "]" . $lineBreak;
        } else {
            echo "  [Already exists - skipped]" . $lineBreak;
        }
    }
}

echo $lineBreak . "* Check requirements *" . $lineBreak;
check("Soap", class_exists("SoapClient"));
check("init.php", file_exists(__DIR__ . "/../../../init.php"));
check("registrarfunctions.php", file_exists(__DIR__ . "/../../../includes/registrarfunctions.php"));
check("Capsule ORM", class_exists('WHMCS\Database\Capsule'));

echo $lineBreak . "* Creating email templates *" . $lineBreak;
Tools::createEmailTemplates();

echo $lineBreak . "* Creating SQL tables *" . $lineBreak;

// Create tblasciotlds table
executeSchema("Creating tblasciotlds table", function() {
    if (!Capsule::schema()->hasTable('tblasciotlds')) {
        Capsule::schema()->create('tblasciotlds', function($table) {
            $table->string('Tld', 255)->unique();
            $table->integer('Threshold')->default(0);
            $table->boolean('Renew')->default(false);
            $table->boolean('LocalPresenceRequired')->default(false);
            $table->boolean('LocalPresenceOffered')->default(false);
            $table->boolean('AuthCodeRequired')->default(false);
            $table->string('Country', 255)->nullable();
            $table->timestamp('LastUpdated')->nullable();
        });
    }
});

// Create tblasciojobs table
executeSchema("Creating tblasciojobs table", function() {
    if (!Capsule::schema()->hasTable('tblasciojobs')) {
        Capsule::schema()->create('tblasciojobs', function($table) {
            $table->increments('id');
            $table->integer('last_id')->index();
            $table->string('order_id', 255)->index();
            $table->string('method', 255);
            $table->text('request');
            $table->text('response');
            $table->timestamp('date')->useCurrent();
        });
    }
});

// Create tblasciohandles table
executeSchema("Creating tblasciohandles table", function() {
    if (!Capsule::schema()->hasTable('tblasciohandles')) {
        Capsule::schema()->create('tblasciohandles', function($table) {
            $table->string('type', 256);
            $table->integer('whmcs_id')->index();
            $table->string('ascio_id', 256)->index();
            $table->string('domain', 255)->index();
        });
    }
});

// Add domain column if missing (migration for existing installations)
executeSchema("Adding domain column to tblasciohandles (if missing)", function() {
    if (Capsule::schema()->hasTable('tblasciohandles') &&
        !Capsule::schema()->hasColumn('tblasciohandles', 'domain')) {
        Capsule::schema()->table('tblasciohandles', function($table) {
            $table->string('domain', 255)->index()->after('ascio_id');
        });
    }
});

// Create tblascio_domain_history table for PS-148
executeSchema("Creating tblascio_domain_history table", function() {
    if (!Capsule::schema()->hasTable('tblascio_domain_history')) {
        Capsule::schema()->create('tblascio_domain_history', function($table) {
            $table->increments('id');
            $table->integer('domain_id')->index();
            $table->string('domain_name', 255)->index();
            $table->string('ascio_status', 100);
            $table->string('whmcs_status', 50);
            $table->string('order_id', 100)->nullable()->index();
            $table->string('order_type', 100)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }
});

// Create tblascio_transfer_status table for PS-145 Transfer Tracking
executeSchema("Creating tblascio_transfer_status table", function() {
    if (!Capsule::schema()->hasTable('tblascio_transfer_status')) {
        Capsule::schema()->create('tblascio_transfer_status', function($table) {
            $table->increments('id');
            $table->integer('domain_id')->unsigned();
            $table->string('domain_name', 255);
            $table->string('current_stage', 50);
            $table->string('order_id', 255)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->text('message')->nullable();
            $table->unique('domain_id');
            $table->index('current_stage');
        });
    }
});

// Create tblascio_import_log table for PS-147 Bulk Domain Import
executeSchema("Creating tblascio_import_log table", function() {
    if (!Capsule::schema()->hasTable('tblascio_import_log')) {
        Capsule::schema()->create('tblascio_import_log', function($table) {
            $table->increments('id');
            $table->string('domain_name', 255)->index();
            $table->string('action', 50)->index(); // imported, skipped, conflict, unmatched, error
            $table->integer('client_id')->nullable()->index();
            $table->text('message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }
});

// NOTE: TLD sync and field generation now happen automatically when
// registrar credentials are saved in WHMCS Admin (via ascio_config_validate).
// This ensures the API calls use proper authentication.
//
// If you need to manually sync TLDs, configure the registrar credentials first,
// then save the settings - the sync will trigger automatically.

echo $lineBreak . "* TLD sync and field generation *" . $lineBreak;
echo "  TLD data and additional fields will be synced automatically" . $lineBreak;
echo "  when you save your Ascio registrar credentials in WHMCS Admin:" . $lineBreak;
echo "  Setup -> Products/Services -> Domain Registrars -> Ascio" . $lineBreak;
echo "  [Deferred to config save]" . $lineBreak;

echo $lineBreak . "* Installation complete *" . $lineBreak;
