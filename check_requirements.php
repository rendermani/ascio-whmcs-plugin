<?php 
ini_set('error_reporting', E_ALL);
ini_set('display_errors', "on");


function check($name,$value) {	
	if($value==true) {
		$ret =  "ok";
	} else {
	 	$ret = "failed";
	}
	echo "<li>".$name.": ".$ret."</li>";
}
?>


<html>
	<head>
		<title>Check server requirements</title>
		<style type="text/css">
			body {font-family: arial,sans-serif}
		</style>
	</head>
	<body>
		<h1>Check server requirements</h1>
		<ul>
			<?php check("Soap",class_exists("SoapClient"))?>
			<?php check("init.php",file_exists("../../../init.php"))?>
			<?php check("registrarfunctions.php",file_exists("../../../includes/registrarfunctions.php"))?>
		</ul>
	</body>
</html>