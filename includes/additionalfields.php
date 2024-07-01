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
$additionaldomainfields['.ru'][] = array('Name'	=> 'NIC/D handle', 'LangVar' => 'nicdhandle', 'Type' => 'text', 'Size' => '10', "Required" => false,"Description" => "NIC/D handle from RU-Center. Only required if the individual has an existing handle at RU-CENTER. Please note that by supplying the NIC/D handle, order processing time can be greatly reduced.");

$additionaldomainfields[".ca"][] = array("Name" => "Password / ID Card Number", "LangVar" => "catrademarkcountry", "Type" => "text", "Description" => "This is required when and individual is a Canadian citizen but does not reside in Canada, in this case a passport number or canadian ID number should be provided in this field.",);
$additionaldomainfields[".ca"][] = array("Name" => "Trademark Number", "LangVar" => "catrademarknumber", "Type" => "text", "Description" => "Trademark Number. This is only applicable when domain is based on a trademark. The domain has to be either an exact match of the trademark, or a domain name that includes a registered mark. For example, when the company owns the trademark for abccompany, they can register abccompany.ca or something like Iloveabccompany.ca as long as the name of the trademark is contained within the domain. Please note that if the trademarks that are hyphenated are now accepted by the registry. Please note that the Trademark should be registered and the address details in the Registration order must be those of the trademark holder.");
$additionaldomainfields[".ca"][] = array("Name" => "Trademark Name", "LangVar" => "catrademarknumber", "Type" => "text", "Description" => "Trademark Name. This is only applicable when domain is based on a trademark. The domain has to be either an exact match of the trademark, or a domain name that includes a registered mark. For example, when the company owns the trademark for abccompany, they can register abccompany.ca or something like Iloveabccompany.ca as long as the name of the trademark is contained within the domain. Please note that if the trademarks that are hyphenated are now accepted by the registry. Please note that the Trademark should be registered and the address details in the Registration order must be those of the trademark holder.");
