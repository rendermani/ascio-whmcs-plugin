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

// Create mod_asciosession table
executeSchema("Creating mod_asciosession table", function() {
    if (!Capsule::schema()->hasTable('mod_asciosession')) {
        Capsule::schema()->create('mod_asciosession', function($table) {
            $table->string('account', 255)->unique();
            $table->string('sessionId', 255);
            $table->timestamp('timestamp')->useCurrent();
            $table->index('timestamp', 'date');
        });
    }
});

echo $lineBreak . "* Syncing TLD data from Ascio TLDKit API *" . $lineBreak;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://aws.ascio.info/tldkit.xq");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$tldsString = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "Error fetching TLD data: " . $curlError . $lineBreak;
} elseif ($httpCode !== 200) {
    echo "Error fetching TLD data: HTTP " . $httpCode . $lineBreak;
} else {
    $tlds = json_decode($tldsString);

    if (!$tlds || !isset($tlds->tld)) {
        echo "Error: Invalid TLD data received" . $lineBreak;
    } else {
        $count = 0;
        foreach ($tlds->tld as $tld) {
            echo "+ " . $tld->tld;
            flush();

            try {
                // Use upsert pattern: delete then insert
                Capsule::table('tblasciotlds')
                    ->where('Tld', $tld->tld)
                    ->delete();

                Capsule::table('tblasciotlds')->insert([
                    'Tld' => $tld->tld,
                    'Threshold' => $tld->Threshold ?? 0,
                    'Renew' => ($tld->Renew ?? '') === 'true' ? 1 : 0,
                    'LocalPresenceRequired' => ($tld->LocalPresenceRequired ?? '') === 'true' ? 1 : 0,
                    'LocalPresenceOffered' => ($tld->LocalPresenceOffered ?? '') === 'true' ? 1 : 0,
                    'AuthCodeRequired' => ($tld->AuthCodeRequired ?? '') === 'true' ? 1 : 0,
                    'Country' => $tld->Country ?? null,
                    'LastUpdated' => date('Y-m-d H:i:s')
                ]);

                echo " [OK]" . $lineBreak;
                $count++;
            } catch (\Exception $e) {
                echo " [Error: " . $e->getMessage() . "]" . $lineBreak;
            }
        }

        echo $lineBreak . "* Synced " . $count . " TLDs *" . $lineBreak;
    }
}

echo $lineBreak . "* Installation complete *" . $lineBreak;
