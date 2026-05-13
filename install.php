<?php
use ascio\Tools as Tools;
use Illuminate\Database\Capsule\Manager as Capsule;

require_once(__DIR__."/../../../init.php");
require_once(__DIR__."/lib/Tools.php");
error_reporting(E_ALL);
ini_set('error_reporting', E_ERROR);
ini_set('display_errors', "on");
$isCLI = ( php_sapi_name() == 'cli' );
$lineBreak = $isCLI ? "\n" : "<br>\n";

function check($name, $value) {
	global $lineBreak;
	$ret = $value ? "ok" : "failed";
	echo "- Check " . $name . ": " . $ret . $lineBreak;
	if ($ret == "failed") die("Please fix the errors and retry" . $lineBreak);
}

function runSQL($q, $label) {
	global $lineBreak;
	try {
		Capsule::statement($q);
	} catch (\Exception $e) {
		echo "Warning (" . $label . "): " . $e->getMessage() . $lineBreak;
	}
}

echo $lineBreak . "* Check requirements *" . $lineBreak;
check("Soap", class_exists("SoapClient"));
check("init.php", file_exists(__DIR__ . "/../../../init.php"));
check("registrarfunctions.php", file_exists(__DIR__ . "/../../../includes/registrarfunctions.php"));

echo $lineBreak . "* Creating email templates *";
Tools::createEmailTemplates();

echo $lineBreak . "* Creating SQL tables" . $lineBreak;

echo "- Creating tblasciotlds table" . $lineBreak;
runSQL('CREATE TABLE IF NOT EXISTS `tblasciotlds` (`Tld` char(255) NOT NULL, `Threshold` int(11) NOT NULL, `Renew` tinyint(1) NOT NULL, `LocalPresenceRequired` tinyint(1) NOT NULL, `LocalPresenceOffered` tinyint(1) NOT NULL, `AuthCodeRequired` tinyint(1) NOT NULL, `Country` char(255) NOT NULL, UNIQUE KEY `tld` (`Tld`) )', 'tblasciotlds');

echo "- Creating tblasciojobs table" . $lineBreak;
runSQL('CREATE TABLE IF NOT EXISTS `tblasciojobs` (`id` int(11) NOT NULL AUTO_INCREMENT, `last_id` int(11) NOT NULL, `order_id` char(255) NOT NULL, `method` char(255) NOT NULL, `request` text NOT NULL, `response` text NOT NULL, `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `last_id` (`last_id`), KEY `order_id` (`order_id`) )', 'tblasciojobs');

echo "- Creating tblasciohandles table" . $lineBreak;
runSQL('CREATE TABLE IF NOT EXISTS `tblasciohandles` (`type` varchar(256) NOT NULL, `whmcs_id` int(10) NOT NULL, `ascio_id` varchar(256) NOT NULL, PRIMARY KEY (`whmcs_id`), KEY `ascio_id` (`ascio_id`) )', 'tblasciohandles');

echo "- Creating mod_asciosession table" . $lineBreak;
runSQL('CREATE TABLE IF NOT EXISTS `mod_asciosession` (`account` varchar(255) NOT NULL, `sessionId` varchar(255) NOT NULL, `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY `account` (`account`), KEY `date` (`timestamp`) )', 'mod_asciosession');

echo "- Adding domain column to tblasciohandles (update)" . $lineBreak;
runSQL('ALTER TABLE `tblasciohandles` ADD `domain` VARCHAR(255) NOT NULL AFTER `ascio_id`, ADD INDEX `domain` (`domain`)', 'tblasciohandles add domain');

echo "- tblasciohandles: Drop primary key" . $lineBreak;
runSQL('ALTER TABLE `tblasciohandles` DROP PRIMARY KEY', 'drop primary key');

echo "- tblasciohandles: Add whmcs_id index" . $lineBreak;
runSQL('ALTER TABLE `tblasciohandles` ADD INDEX(`whmcs_id`)', 'add whmcs_id index');

echo $lineBreak . "* Read TLD parameters *" . $lineBreak;
$s = curl_init();
curl_setopt($s, CURLOPT_URL, "https://aws.ascio.info/tldkit.xq");
curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
curl_setopt($s, CURLOPT_TIMEOUT, 30);
$tldsString = curl_exec($s);
curl_close($s);

if (!$tldsString) {
	die("Error: Could not fetch TLD data from ascio.info" . $lineBreak);
}

$tlds = json_decode($tldsString);
if (!$tlds || !isset($tlds->tld)) {
	die("Error: Invalid TLD data received" . $lineBreak);
}

foreach ($tlds->tld as $key => $tld) {
	echo "+ insert: " . $tld->tld . $lineBreak;
	flush();
	// delete existing entry and reinsert
	try {
		Capsule::table('tblasciotlds')->where('Tld', $tld->tld)->delete();
		Capsule::table('tblasciotlds')->insert(array(
			"Tld"                    => $tld->tld,
			"Threshold"              => $tld->Threshold,
			"Renew"                  => $tld->Renew == "true" ? 1 : 0,
			"LocalPresenceRequired"  => $tld->LocalPresenceRequired == "true" ? 1 : 0,
			"LocalPresenceOffered"   => $tld->LocalPresenceOffered == "true" ? 1 : 0,
			"AuthCodeRequired"       => $tld->AuthCodeRequired == "true" ? 1 : 0,
			"Country"                => $tld->Country
		));
	} catch (\Exception $e) {
		die("Error inserting TLD " . $tld->tld . ": " . $e->getMessage() . $lineBreak);
	}
}

echo $lineBreak . "* Installation complete *" . $lineBreak;
?>