<?php


// ascio

// .FR
$additionaldomainfields[".fr"] = [];
$additionaldomainfields[".fr"][] = array("Name" => "City of birth (Individual)", "LangVar" => "frbirthcity", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "If the registrant is individual, city of birth must be provided.",);
$additionaldomainfields[".fr"][] = array("Name" => "Postcode of city of birth (Individual)", "LangVar" => "frbirthpostal", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "If the registrant is individual, postalcode of the city of birth must be provided.",);
$additionaldomainfields[".fr"][] = array("Name" => "Country of birth (Individual)", "LangVar" => "frbirthcountry", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "If the registrant is individual, country of birth must be provided.",);
$additionaldomainfields[".fr"][] = array("Name" => "Date of birth (Individual)", "LangVar" => "frbirthdate", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "If the registrant is individual, date of birth must be provided.",);
$additionaldomainfields[".fr"][] = array("Name" => "VAT (Company)", "LangVar" => "frvat", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "If the registrant is a company, VAT must be provided.",);

// .XXX
$additionaldomainfields[".xxx"] = [];
$additionaldomainfields[".xxx"][] = array("Name" => "Member of sponsored community", "LangVar" => "xxxmember", "Type" => "tickbox", "Default" => "", "Required" => true,"Description" => "As non-member, only blocking of domains is possible",);

//.jobs
$additionaldomainfields[".jobs"] = [];
$additionaldomainfields[".jobs"][] = array("Name" => "Website", "LangVar" => "jobswebsite", "Type" => "text", "Default" => "", "Required" => true,"Description" => "The URL with Jobs. Must contain the company name",);
$additionaldomainfields[".jobs"][] = array("Name" => "Business Nature", "LangVar" => "jobswebsite", "Type" => "dropdown", "Default" => "", "Required" => true, "Options" => "Accounting/Banking/Finance,Agriculture/Farming,Biotechnology/Science,Computer/Information Technology,Construction/Building Services,Consulting,Education/Training/Library,Entertainment,Environmental,Hospitality,Government/Civil Service,Healthcare,HR/Recruiting,Insurance,Legal,Manufacturing,Media/Advertising,Parks & Recreation,Pharmaceutical,Real Estate,Restaurant/Food Service,Retail,Telemarketing,Transportation,Other","Description" => "The nature of the business",);
$additionaldomainfields[".jobs"][] = array("Name" => "Company position", "LangVar" => "jobswebsite", "Type" => "text", "Default" => "", "Required" => true, "Description" => "This is to be filled out with the title / position of the personnel who is authorised to apply for a domain name.",);

// .nl 
$additionaldomainfields[".nl"] = [];
$additionaldomainfields[".nl"][] = array("Name" => "Organisation Number", "LangVar" => "registrantnumber", "Type" => "text", "Default" => "", "Required" => false,"Description" => "Organization Number of the Registrant's company");

// .ru

$additionaldomainfields['.ru'][] = array('Name'	=> 'NIC/D handle', 'DisplayName' => 'nicdhandle', 'Type' => 'text', 'Size' => '10', "Required" => false,"Description" => "NIC/D handle from RU-Center. Only required if the individual has an existing handle at RU-CENTER. Please note that by supplying the NIC/D handle, order processing time can be greatly reduced.");