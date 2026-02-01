<?php
require_once(__DIR__."/../../lib/Error.php");
require_once(realpath(dirname(__FILE__))."/../../../../../init.php");
require_once(__DIR__."/../Installer/Installer.php");

use ascio\whmcs\ssl\Installer;
use ascio\whmcs\ssl\AscioSystemException;

$git = "rendermani/ascio-ssl-whmcs-plugin";
$local = realpath(__DIR__."/../../../../servers/asciossl");
$installer = new Installer($git,$local,"ssl"); 
$installer->doFsUpdates();
$installer->doDatabaseUpdates();