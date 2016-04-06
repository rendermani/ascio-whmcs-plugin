<?php
require_once(ROOTDIR."/init.php");

$data = file_get_contents('http://aws.ascio.info/tldkit.xq');
$tlds = json_decode($data);
var_dump($tlds);
?>