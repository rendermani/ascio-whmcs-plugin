<?
require("../lib/Tools.php");

ini_set("display_errors", 1);

function output()
{
	global $_POST;
	foreach (explode("\n",$_POST["list"]) as $key => $nrOld) {		
		if(strlen($nrOld) < 6 || substr($nrOld,0,2) == "99") $nr = "+00.0000";		
		else {
			if(!(substr($nrOld,0,1) == "+" || substr($nrOld,0,1) == "0"))   $nr = "+00.0000";		
		 	else $nr =Tools::fixPhone($nrOld,$_POST["country"]) ;
	     }
			

		echo "<tr><td style='background-color:#eee;height:15px'>".$nr."</td><td>". $nrOld ."</td></tr>";
	}
}



?>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Fix phone with WHMCS converter</title>
    </head>
    <body>
        <h1>Fix phone with WHMCS converter</h1>
        <p>Please enter a list of phonenumbers. Linebreak between each number</p>
        <form action="?" method="post">
            <p>
                Country: <input type="text" name="country" value="DE"></input>
            </p>
            <textarea rows="20" cols="30" name="list"><? echo $_POST["list"];?></textarea>
            <br/>
            <input type="submit" />
        </form>
        <table>
        	<? output(); ?>
        </table>

    </body>
</html>