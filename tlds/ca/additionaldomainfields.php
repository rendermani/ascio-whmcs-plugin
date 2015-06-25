<?
// you may add the new fields to your ../whmcs/includes/additionaldomainfields.php

// existing fields

//$additionaldomainfields[".ca"][] = array("Name" => "Legal Type", "LangVar" => "catldlegaltype", "Type" => "dropdown", "Options" => "Corporation,Canadian Citizen,Permanent Resident of Canada,Government,Canadian Educational Institution,Canadian Unincorporated Association,Canadian Hospital,Partnership Registered in Canada,Trade-mark registered in Canada,Canadian Trade Union,Canadian Political Party,Canadian Library Archive or Museum,Trust established in Canada,Aboriginal Peoples,Legal Representative of a Canadian Citizen,Official mark registered in Canada", "Default" => "Corporation", "Description" => "Legal type of registrant contact",);
//$additionaldomainfields[".ca"][] = array("Name" => "CIRA Agreement", "LangVar" => "catldciraagreement", "Type" => "tickbox", "Description" => "Tick to confirm you agree to the CIRA Registration Agreement shown below<br /><blockquote>You have read, understood and agree to the terms and conditions of the Registrant Agreement, and that CIRA may, from time to time and at its discretion, amend any or all of the terms and conditions of the Registrant Agreement, as CIRA deems appropriate, by posting a notice of the changes on the CIRA website and by sending a notice of any material changes to Registrant. You meet all the requirements of the Registrant Agreement to be a Registrant, to apply for the registration of a Domain Name Registration, and to hold and maintain a Domain Name Registration, including without limitation CIRA's Canadian Presence Requirements for Registrants, at: www.cira.ca/assets/Documents/Legal/Registrants/CPR.pdf. CIRA will collect, use and disclose your personal information, as set out in CIRA's Privacy Policy, at: www.cira.ca/assets/Documents/Legal/Registrants/privacy.pdf</blockquote>",);
//$additionaldomainfields[".ca"][] = array("Name" => "WHOIS Opt-out", "LangVar" => "catldwhoisoptout", "Type" => "tickbox", "Description" => "Tick to hide your contact information in CIRA WHOIS (only available to individuals)",);


// new ascio fields


$additionaldomainfields[".ca"][] = array("Name" => "Canadian ID number", "LangVar" => "catrademarkcountry", "Type" => "text", "Description" => "This is required when and individual is a Canadian citizen but does not reside in Canada, in this case a passport number or canadian ID number should be provided in this field.",);
$additionaldomainfields[".ca"][] = array("Name" => "Trademark number", "LangVar" => "catrademarknumber", "Type" => "text", "Description" => "This is only applicable when domain is based on a trademark. The domain has to be either an exact match of the trademark, or a domain name that includes a registered mark. For example, when the company owns the trademark for abccompany, they can register abccompany.ca or something like Iloveabccompany.ca as long as the name of the trademark is contained within the domain.",);
?>