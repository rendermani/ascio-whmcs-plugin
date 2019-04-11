<?php
$additionaldomainfields[".sg"][] = array("Name" => "RCB Singapore ID", "Remove" => true);
$additionaldomainfields[".sg"][] = array("Name" => "Registrant Type", "Remove" => true);

$additionaldomainfields[".sg"][] = array("Name" => "Registrant ID", "LangVar" => "registrantid", "Type" => "text", "Description" => "This is to be filled out with the business registration number (from ROC /RCB certificate) of the registrant", 'DisplayName' => 'Registrant ID');
$additionaldomainfields[".sg"][] = array("Name" => "Admin ID", "LangVar" => "Admin ID", "Type" => "text", "Description" => " Organization number - For the purposes of verification, this field needs to be filled with correct SINGPASS ID [NRIC/FIN], UEN, SGNICID. ", 'DisplayName' => 'Admin ID');
$additionaldomainfields[".sg"][] = array("Name" => "Local Presence", "LangVar" => "Admin ID", "Type" => "tickbox", "Description" => "For adminstrative contacts that are not in singapore", 'DisplayName' => 'Local Presence');

$additionaldomainfields[".com.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".com.sg"][] = array("Name" => "Registrant ID", "LangVar" => "registrantid", "Type" => "text", "Description" => "This is to be filled out with the business registration number (from ROC /RCB certificate) of the registrant", 'DisplayName' => 'Registrant ID');
$additionaldomainfields[".edu.sg"] = $additionaldomainfields[".com.sg"];
$additionaldomainfields[".net.sg"] = $additionaldomainfields[".com.sg"];
$additionaldomainfields[".org.sg"] = $additionaldomainfields[".com.sg"];
$additionaldomainfields[".per.sg"] = $additionaldomainfields[".com.sg"];

?>