n<?php
/*
--------------------------------------------------------------------
              ///  WHMCS DOMAIN ADDITIONAL FIELDS  \\\
--------------------------------------------------------------------

This is where you can define the TLD specific fields required to
register certain TLDs. Supported variables are:

  Name - key name used to reference field in modules (required)
  DisplayName - name displayed in client & admin interfaces
  LangVar - the language file variable to use if set
  Type - field type: text, dropdown, radio, tickbox (required)
  Size - the length of the text field
  Default - the default value the field should take
  Required - force entry - true/false

--------------------------------------------------------------------
*/

// .US

$additionaldomainfields[".us"][] = array("Name" => "Nexus Category", "LangVar" => "ustldnexuscat", "Type" => "dropdown", "Options" => "C11,C12,C21,C31,C32", "Default" => "C11",);
$additionaldomainfields[".us"][] = array("Name" => "Nexus Country", "LangVar" => "ustldnexuscountry", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,);
$additionaldomainfields[".us"][] = array("Name" => "Application Purpose", "LangVar" => "ustldapppurpose", "Type" => "dropdown", "Options" => "Business use for profit,Non-profit business,Club,Association,Religious Organization,Personal Use,Educational purposes,Government purposes", "Default" => "Business use for profit",);

// .UK

$additionaldomainfields[".co.uk"][] = array("Name" => "Legal Type", "LangVar" => "uktldlegaltype", "Type" => "dropdown", "Options" => "Individual,UK Limited Company,UK Public Limited Company,UK Partnership,UK Limited Liability Partnership,Sole Trader,UK Registered Charity,UK Entity (other),Foreign Organization,Other foreign organizations", "Default" => "Individual",);
$additionaldomainfields[".co.uk"][] = array("Name" => "Company ID Number", "LangVar" => "uktldcompanyid", "Type" => "text", "Size" => "30", "Default" => "", "Required" => false,);
$additionaldomainfields[".co.uk"][] = array("Name" => "Registrant Name", "LangVar" => "uktldregname", "Type" => "text", "Size" => "30", "Default" => "", "Required" => true,);
$additionaldomainfields[".net.uk"] = $additionaldomainfields[".co.uk"];
$additionaldomainfields[".org.uk"] = $additionaldomainfields[".co.uk"];
$additionaldomainfields[".me.uk"] = $additionaldomainfields[".co.uk"];
$additionaldomainfields[".plc.uk"] = $additionaldomainfields[".co.uk"];
$additionaldomainfields[".ltd.uk"] = $additionaldomainfields[".co.uk"];
$additionaldomainfields[".co.uk"][] = array("Name" => "WHOIS Opt-out", "LangVar" => "uktldwhoisoptout", "Type" => "tickbox",);
$additionaldomainfields[".uk"] = $additionaldomainfields[".co.uk"];

// .CA

$additionaldomainfields[".ca"][] = array("Name" => "Legal Type", "LangVar" => "catldlegaltype", "Type" => "dropdown", "Options" => "Corporation,Canadian Citizen,Permanent Resident of Canada,Government,Canadian Educational Institution,Canadian Unincorporated Association,Canadian Hospital,Partnership Registered in Canada,Trade-mark registered in Canada,Canadian Trade Union,Canadian Political Party,Canadian Library Archive or Museum,Trust established in Canada,Aboriginal Peoples,Legal Representative of a Canadian Citizen,Official mark registered in Canada", "Default" => "Corporation", "Description" => "Legal type of registrant contact",);
$additionaldomainfields[".ca"][] = array("Name" => "CIRA Agreement", "LangVar" => "catldciraagreement", "Type" => "tickbox", "Description" => "Tick to confirm you agree to the CIRA Registration Agreement shown below<br /><blockquote>You have read, understood and agree to the terms and conditions of the Registrant Agreement, and that CIRA may, from time to time and at its discretion, amend any or all of the terms and conditions of the Registrant Agreement, as CIRA deems appropriate, by posting a notice of the changes on the CIRA website and by sending a notice of any material changes to Registrant. You meet all the requirements of the Registrant Agreement to be a Registrant, to apply for the registration of a Domain Name Registration, and to hold and maintain a Domain Name Registration, including without limitation CIRA's Canadian Presence Requirements for Registrants, at: www.cira.ca/assets/Documents/Legal/Registrants/CPR.pdf. CIRA will collect, use and disclose your personal information, as set out in CIRA's Privacy Policy, at: www.cira.ca/assets/Documents/Legal/Registrants/privacy.pdf</blockquote>",);
$additionaldomainfields[".ca"][] = array("Name" => "WHOIS Opt-out", "LangVar" => "catldwhoisoptout", "Type" => "tickbox", "Description" => "Tick to hide your contact information in CIRA WHOIS (only available to individuals)",);
// new ascio fields
$additionaldomainfields[".ca"][] = array("Name" => "Canadian ID number", "LangVar" => "catrademarkcountry", "Type" => "text", "Description" => "This is required when and individual is a Canadian citizen but does not reside in Canada, in this case a passport number or canadian ID number should be provided in this field.",);
$additionaldomainfields[".ca"][] = array("Name" => "Trademark number", "LangVar" => "catrademarknumber", "Type" => "text", "Description" => "This is only applicable when domain is based on a trademark. The domain has to be either an exact match of the trademark, or a domain name that includes a registered mark. For example, when the company owns the trademark for abccompany, they can register abccompany.ca or something like Iloveabccompany.ca as long as the name of the trademark is contained within the domain.",);
// .ES

$additionaldomainfields[".es"][] = array("Name" => "ID Form Type", "LangVar" => "estldidformtype", "Type" => "dropdown", "Options" => "Other Identification,Tax Identification Number,Tax Identification Code,Foreigner Identification Number", "Default" => "Other Identification",);
$additionaldomainfields[".es"][] = array("Name" => "ID Form Number", "LangVar" => "estldidformnum", "Type" => "text", "Size" => "30", "Default" => "", "Required" => true,);

// .SG

$additionaldomainfields[".sg"][] = array("Name" => "RCB Singapore ID", "DisplayName" => "RCB/Singapore ID", "LangVar" => "sgtldrcbid", "Type" => "text", "Size" => "30", "Default" => "", "Required" => true,);
$additionaldomainfields[".sg"][] = array("Name" => "Registrant Type", "LangVar" => "sgtldregtype", "Type" => "dropdown", "Options" => "Individual,Organisation", "Default" => "Individual",);
$additionaldomainfields[".com.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".edu.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".net.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".org.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".per.sg"] = $additionaldomainfields[".sg"];

// .TEL

$additionaldomainfields[".tel"][] = array("Name" => "Legal Type", "LangVar" => "teltldlegaltype", "Type" => "dropdown", "Options" => "Natural Person,Legal Person", "Default" => "Natural Person",);
$additionaldomainfields[".tel"][] = array("Name" => "WHOIS Opt-out", "LangVar" => "teltldwhoisoptout", "Type" => "tickbox",);

// .IT

$additionaldomainfields[".it"][] = array("Name" => "Legal Type", "LangVar" => "ittldlegaltype", "Type" => "dropdown", "Options" => "Italian and foreign natural persons,Companies/one man companies,Freelance workers/professionals,non-profit organizations,public organizations,other subjects,non natural foreigners", "Default" => "Italian and foreign natural persons", "Description" => "Legal type of registrant",);
$additionaldomainfields[".it"][] = array("Name" => "Tax ID", "LangVar" => "ittldtaxid", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,);
$additionaldomainfields[".it"][] = array("Name" => "Publish Personal Data", "LangVar" => "ittlddata", "Type" => "tickbox",);
$additionaldomainfields[".it"][] = array("Name" => "Accept Section 3 of .IT registrar contract", "LangVar" => "ittldsec3", "Type" => "tickbox",);
$additionaldomainfields[".it"][] = array("Name" => "Accept Section 5 of .IT registrar contract", "LangVar" => "ittldsec5", "Type" => "tickbox",);
$additionaldomainfields[".it"][] = array("Name" => "Accept Section 6 of .IT registrar contract", "LangVar" => "ittldsec6", "Type" => "tickbox",);
$additionaldomainfields[".it"][] = array("Name" => "Accept Section 7 of .IT registrar contract", "LangVar" => "ittldsec7", "Type" => "tickbox",);

// .DE

$additionaldomainfields[".de"][] = array("Name" => "Tax ID", "LangVar" => "detldtaxid", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,);
$additionaldomainfields[".de"][] = array("Name" => "Address Confirmation", "LangVar" => "detldaddressconfirm", "Type" => "tickbox", "Description" => "Please tick to confirm you have a valid German address",);

// .AU

$additionaldomainfields[".com.au"][] = array("Name" => "Registrant Name", "LangVar" => "autldregname", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,);
$additionaldomainfields[".com.au"][] = array("Name" => "Registrant ID", "LangVar" => "autldregid", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,);
$additionaldomainfields[".com.au"][] = array("Name" => "Registrant ID Type", "LangVar" => "autldregidtype", "Type" => "dropdown", "Options" => "ABN,ACN,Business Registration Number", "Default" => "ABN",);
$additionaldomainfields[".com.au"][] = array("Name" => "Eligibility Name", "LangVar" => "autldeligname", "Type" => "text", "Size" => "20", "Default" => "", "Required" => false,);
$additionaldomainfields[".com.au"][] = array("Name" => "Eligibility ID", "LangVar" => "autldeligid", "Type" => "text", "Size" => "20", "Default" => "", "Required" => false,);
$additionaldomainfields[".com.au"][] = array("Name" => "Eligibility ID Type", "LangVar" => "autldeligidtype", "Type" => "dropdown", "Options" => ",Australian Company Number (ACN),ACT Business Number,NSW Business Number,NT Business Number,QLD Business Number,SA Business Number,TAS Business Number,VIC Business Number,WA Business Number,Trademark (TM),Other - Used to record an Incorporated Association number,Australian Business Number (ABN)", "Default" => "",);
$additionaldomainfields[".com.au"][] = array("Name" => "Eligibility Type", "LangVar" => "autldeligtype", "Type" => "dropdown", "Options" => "Charity,Citizen/Resident,Club,Commercial Statutory Body,Company,Incorporated Association,Industry Body,Non-profit Organisation,Other,Partnership,Pending TM Owner  ,Political Party,Registered Business,Religious/Church Group,Sole Trader,Trade Union,Trademark Owner,Child Care Centre,Government School,Higher Education Institution,National Body,Non-Government School,Pre-school,Research Organisation,Training Organisation", "Default" => "Company",);
$additionaldomainfields[".com.au"][] = array("Name" => "Eligibility Reason", "LangVar" => "autldeligreason", "Type" => "radio", "Options" => "Domain name is an Exact Match Abbreviation or Acronym of your Entity or Trading Name.,Close and substantial connection between the domain name and the operations of your Entity.", "Default" => "Domain name is an Exact Match Abbreviation or Acronym of your Entity or Trading Name.",);

$additionaldomainfields[".net.au"] = $additionaldomainfields[".com.au"];
$additionaldomainfields[".org.au"] = $additionaldomainfields[".com.au"];
$additionaldomainfields[".asn.au"] = $additionaldomainfields[".com.au"];
$additionaldomainfields[".id.au"] = $additionaldomainfields[".com.au"];

// .ASIA

$additionaldomainfields[".asia"][] = array("Name" => "Legal Type", "LangVar" => "asialegaltype", "Type" => "dropdown", "Options" => "naturalPerson,corporation,cooperative,partnership,government,politicalParty,society,institution", "Default" => "naturalPerson",);
$additionaldomainfields[".asia"][] = array("Name" => "Identity Form", "LangVar" => "asiaidentityform", "Type" => "dropdown", "Options" => "passport,certificate,legislation,societyRegistry,politicalPartyRegistry", "Default" => "passport",);
$additionaldomainfields[".asia"][] = array("Name" => "Identity Number", "LangVar" => "asiaidentitynumber", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,);

// .PRO

$additionaldomainfields[".pro"][] = array("Name" => "Profession", "LangVar" => "proprofession", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true, "Description" => "Indicated professional association recognized by government body",);
// .COOP

$additionaldomainfields[".coop"][] = array("Name" => "Contact Name", "LangVar" => "coopcontactname", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "A sponsor is required to register .coop domains. Please enter the information here",);
$additionaldomainfields[".coop"][] = array("Name" => "Contact Company", "LangVar" => "cooopcontactcompany", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "",);
$additionaldomainfields[".coop"][] = array("Name" => "Contact Email", "LangVar" => "coopcontactemail", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "",);
$additionaldomainfields[".coop"][] = array("Name" => "Address 1", "LangVar" => "coopaddress1", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "",);
$additionaldomainfields[".coop"][] = array("Name" => "Address 2", "LangVar" => "coopaddress2", "Type" => "text", "Size" => "20", "Default" => "", "Required" => false,"Description" => "",);
$additionaldomainfields[".coop"][] = array("Name" => "City", "LangVar" => "coopcity", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "",);
$additionaldomainfields[".coop"][] = array("Name" => "State", "LangVar" => "coopstate", "Type" => "text", "Size" => "20", "Default" => "", "Required" => false,"Description" => "",);
$additionaldomainfields[".coop"][] = array("Name" => "ZIP Code", "LangVar" => "coopzip", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "",);
$additionaldomainfields[".coop"][] = array("Name" => "Country", "LangVar" => "coopcountry", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "2 Letter Country Code",);
$additionaldomainfields[".coop"][] = array("Name" => "Phone CC", "LangVar" => "coopphonecc", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "Phone Country Code eg 1 for US & Canada, 44 for UK",);
$additionaldomainfields[".coop"][] = array("Name" => "Phone", "LangVar" => "coopphone", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "",);

// .CN
$additionaldomainfields[".cn"][] = array("Name" => "cnhosting", "DisplayName" => "Hosted in China?", "LangVar" => "cnhosting", "Type" => "tickbox");
$additionaldomainfields[".cn"][] = array("Name" => "cnhregisterclause", "DisplayName" => "Agree to the .CN <a href=\"http://www1.cnnic.cn/PublicS/fwzxxgzcfg/201208/t20120830_35735.htm\" target=\"_blank\">Register Agreement</a>", "LangVar" => "ittldsec3", "Type" => "tickbox", "Required" => true, );

// ascio

// .FR
$additionaldomainfields[".fr"][] = array("Name" => "City of birth (Individual)", "LangVar" => "frbirthcity", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "If the registrant is individual, city of birth must be provided.",);
$additionaldomainfields[".fr"][] = array("Name" => "Postcode of city of birth (Individual)", "LangVar" => "frbirthpostal", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "If the registrant is individual, postalcode of the city of birth must be provided.",);
$additionaldomainfields[".fr"][] = array("Name" => "Country of birth (Individual)", "LangVar" => "frbirthcountry", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "If the registrant is individual, country of birth must be provided.",);
$additionaldomainfields[".fr"][] = array("Name" => "Date of birth (Individual)", "LangVar" => "frbirthdate", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "If the registrant is individual, date of birth must be provided.",);
$additionaldomainfields[".fr"][] = array("Name" => "VAT (Company)", "LangVar" => "frvat", "Type" => "text", "Size" => "20", "Default" => "", "Required" => true,"Description" => "If the registrant is a company, VAT must be provided.",);

// .XXX
$additionaldomainfields[".xxx"][] = array("Name" => "Member of sponsored community", "LangVar" => "xxxmember", "Type" => "tickbox", "Default" => "", "Required" => true,"Description" => "As non-member, only blocking of domains is possible",);

//.jobs

$additionaldomainfields[".jobs"][] = array("Name" => "Website", "LangVar" => "jobswebsite", "Type" => "text", "Default" => "", "Required" => true,"Description" => "The URL with Jobs. Must contain the company name",);
$additionaldomainfields[".jobs"][] = array("Name" => "Business Nature", "LangVar" => "jobswebsite", "Type" => "dropdown", "Default" => "", "Required" => true, "Options" => "Accounting/Banking/Finance,Agriculture/Farming,Biotechnology/Science,Computer/Information Technology,Construction/Building Services,Consulting,Education/Training/Library,Entertainment,Environmental,Hospitality,Government/Civil Service,Healthcare,HR/Recruiting,Insurance,Legal,Manufacturing,Media/Advertising,Parks & Recreation,Pharmaceutical,Real Estate,Restaurant/Food Service,Retail,Telemarketing,Transportation,Other","Description" => "The nature of the business",);
$additionaldomainfields[".jobs"][] = array("Name" => "Company position", "LangVar" => "jobswebsite", "Type" => "text", "Default" => "", "Required" => true, "Description" => "This is to be filled out with the title / position of the personnel who is authorised to apply for a domain name.",);


//.ru

$additionaldomainfields[".ru"][] = array("Name" => "INN number", "LangVar" => "ruinn", "Type" => "text", "Default" => "", "Required" => true,"Description" => "Company: 10 digit long INN number, called Taxpayer identification number (code)",);
$additionaldomainfields[".ru"][] = array("Name" => "KPP number", "LangVar" => "rukpp", "Type" => "text", "Default" => "", "Required" => true, "Description" => "Company: 9 digit long KPP number, called the Russian Territory-linked taxpayer number",);

// .nl 

$additionaldomainfields[".nl"][] = array("Name" => "Organisation Number", "LangVar" => "registrantnumber", "Type" => "text", "Default" => "", "Required" => false,"Description" => "Organization Number of the Registrant's company");