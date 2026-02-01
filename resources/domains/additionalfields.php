<?php
/**
 * Ascio WHMCS Registrar Module - Additional Domain Fields
 *
 * This file defines custom domain fields for TLDs supported by Ascio.
 * Copy this file to /resources/domains/additionalfields.php in your WHMCS installation.
 *
 * @see https://docs.whmcs.com/Additional_Domain_Fields
 */

// ============================================================================
// .IT (Italy) - Requires Legal Type, Tax ID, Birth Country (for individuals)
// ============================================================================
$additionaldomainfields[".it"][] = array(
    "Name" => "Legal Type",
    "LangVar" => "itlegaltype",
    "Type" => "dropdown",
    "Options" => "Italian and foreign natural persons,Companies/one man companies,Freelance workers/professionals,Non-profit organizations,Public organizations,Other subjects,Non natural foreigners",
    "Default" => "Companies/one man companies",
    "Required" => true,
    "Description" => "Legal type of the registrant"
);
$additionaldomainfields[".it"][] = array(
    "Name" => "Tax ID",
    "LangVar" => "ittaxid",
    "Type" => "text",
    "Size" => "20",
    "Default" => "",
    "Required" => true,
    "Description" => "Fiscal code (for individuals) or VAT number (for companies)"
);
$additionaldomainfields[".it"][] = array(
    "Name" => "Birth Country",
    "LangVar" => "itbirthcountry",
    "Type" => "text",
    "Size" => "2",
    "Default" => "",
    "Required" => false,
    "Description" => "Two-letter country code of birth country (required for natural persons only)"
);

// ============================================================================
// .CA (Canada) - Requires Legal Type, optional trademark fields
// ============================================================================
// Remove WHMCS default .CA fields to use our customized versions
$additionaldomainfields[".ca"][] = array("Name" => "Legal Type", "Remove" => true);
$additionaldomainfields[".ca"][] = array("Name" => "CIRA Agreement", "Remove" => true);
$additionaldomainfields[".ca"][] = array("Name" => "WHOIS Opt-out", "Remove" => true);

$additionaldomainfields[".ca"][] = array(
    "Name" => "Legal Type",
    "LangVar" => "calegaltype",
    "Type" => "dropdown",
    "Options" => "Corporation,Canadian Citizen,Permanent Resident,Government,Educational,Association,Hospital,Partnership,Trademark,Trade Union,Political Party,Library/Museum,Trust,Aboriginal,Legal Representative,Official Mark",
    "Default" => "Corporation",
    "Required" => true,
    "Description" => "Legal type of the registrant (CIRA requirement)"
);
$additionaldomainfields[".ca"][] = array(
    "Name" => "CIRA Agreement",
    "LangVar" => "caciraagreement",
    "Type" => "tickbox",
    "Required" => true,
    "Description" => "I agree to the CIRA Registrant Agreement"
);
$additionaldomainfields[".ca"][] = array(
    "Name" => "Canadian Citizen",
    "LangVar" => "cacanadiancitizen",
    "Type" => "tickbox",
    "Default" => "",
    "Required" => false,
    "Description" => "Check if registrant is a Canadian citizen residing outside Canada"
);
$additionaldomainfields[".ca"][] = array(
    "Name" => "Trademark Number",
    "LangVar" => "catrademarknumber",
    "Type" => "text",
    "Size" => "30",
    "Default" => "",
    "Required" => false,
    "Description" => "Trademark registration number (required only for Trademark legal type)"
);
$additionaldomainfields[".ca"][] = array(
    "Name" => "Trademark Name",
    "LangVar" => "catrademarkname",
    "Type" => "text",
    "Size" => "50",
    "Default" => "",
    "Required" => false,
    "Description" => "Name of the registered trademark"
);
$additionaldomainfields[".ca"][] = array(
    "Name" => "Trademark Country",
    "LangVar" => "catrademarkcountry",
    "Type" => "text",
    "Size" => "2",
    "Default" => "",
    "Required" => false,
    "Description" => "Two-letter country code where trademark is registered"
);

// ============================================================================
// .US (United States) - Requires Nexus Category and Application Purpose
// ============================================================================
// Remove WHMCS defaults to use our versions
$additionaldomainfields[".us"][] = array("Name" => "Nexus Category", "Remove" => true);
$additionaldomainfields[".us"][] = array("Name" => "Nexus Country", "Remove" => true);
$additionaldomainfields[".us"][] = array("Name" => "Application Purpose", "Remove" => true);

$additionaldomainfields[".us"][] = array(
    "Name" => "Application Purpose",
    "LangVar" => "usapppurpose",
    "Type" => "dropdown",
    "Options" => "P1,P2,P3,P4,P5",
    "Default" => "P1",
    "Required" => true,
    "Description" => "P1=Business, P2=Non-profit, P3=Personal, P4=Educational, P5=Government"
);
$additionaldomainfields[".us"][] = array(
    "Name" => "Nexus Category",
    "LangVar" => "usnexuscategory",
    "Type" => "dropdown",
    "Options" => "C11,C12,C21,C31,C32",
    "Default" => "C11",
    "Required" => true,
    "Description" => "C11=US Citizen, C12=Permanent Resident, C21=US Organization, C31=Foreign Org with US presence, C32=Foreign Org with bona fide US presence"
);
$additionaldomainfields[".us"][] = array(
    "Name" => "Nexus Country",
    "LangVar" => "usnexuscountry",
    "Type" => "text",
    "Size" => "2",
    "Default" => "",
    "Required" => false,
    "Description" => "Two-letter country code (required for C31/C32 nexus categories only)"
);

// Alias for .US
$additionaldomainfields[".us"][] = array(
    "Name" => "Domain Purpose",
    "LangVar" => "usdomainpurpose",
    "Type" => "dropdown",
    "Options" => "P1,P2,P3,P4,P5",
    "Default" => "P1",
    "Required" => true,
    "Description" => "Domain purpose (alias for Application Purpose)"
);

// ============================================================================
// .SG (Singapore) - Requires Registrant ID and Admin ID
// ============================================================================
$additionaldomainfields[".sg"][] = array("Name" => "RCB Singapore ID", "Remove" => true);
$additionaldomainfields[".sg"][] = array("Name" => "Registrant Type", "Remove" => true);

$additionaldomainfields[".sg"][] = array(
    "Name" => "Registrant ID",
    "LangVar" => "sgregistrantid",
    "Type" => "text",
    "Size" => "30",
    "Default" => "",
    "Required" => true,
    "Description" => "Business registration number (ROC/RCB certificate) of the registrant"
);
$additionaldomainfields[".sg"][] = array(
    "Name" => "Admin ID",
    "LangVar" => "sgadminid",
    "Type" => "text",
    "Size" => "30",
    "Default" => "",
    "Required" => true,
    "Description" => "SINGPASS ID (NRIC/FIN), UEN, or SGNICID for verification"
);
$additionaldomainfields[".sg"][] = array(
    "Name" => "Local Presence",
    "LangVar" => "sglocalpresence",
    "Type" => "tickbox",
    "Default" => "",
    "Required" => false,
    "Description" => "Check if admin contact is not located in Singapore (local presence service required)"
);

// Copy .SG fields to all Singapore variants
$additionaldomainfields[".com.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".edu.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".net.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".org.sg"] = $additionaldomainfields[".sg"];
$additionaldomainfields[".per.sg"] = $additionaldomainfields[".sg"];

// ============================================================================
// .NL (Netherlands) - Optional Organisation Number
// ============================================================================
$additionaldomainfields[".nl"][] = array(
    "Name" => "Organisation Number",
    "LangVar" => "nlorgnum",
    "Type" => "text",
    "Size" => "20",
    "Default" => "",
    "Required" => false,
    "Description" => "KVK (Chamber of Commerce) registration number"
);

// ============================================================================
// .DE (Germany) - No additional fields required (uses default contact info)
// ============================================================================
// .DE domains do not require additional fields, but EPP code is auto-generated

// ============================================================================
// .UK / .CO.UK / .ORG.UK (United Kingdom) - Requires Legal Type
// ============================================================================
// Remove WHMCS defaults
$additionaldomainfields[".uk"][] = array("Name" => "Legal Type", "Remove" => true);
$additionaldomainfields[".uk"][] = array("Name" => "Company ID Number", "Remove" => true);
$additionaldomainfields[".uk"][] = array("Name" => "Registrant Name", "Remove" => true);

$additionaldomainfields[".uk"][] = array(
    "Name" => "Legal Type",
    "LangVar" => "uklegaltype",
    "Type" => "dropdown",
    "Options" => "IND,LTD,PLC,PTNR,LLP,STRA,RCHAR,GOV,STAT,OTHER,CRC,FCORP,IP,FOTHER,SCHOOL",
    "Default" => "IND",
    "Required" => true,
    "Description" => "IND=Individual, LTD=UK Ltd, PLC=UK PLC, PTNR=Partnership, LLP=LLP, RCHAR=Charity, GOV=Government, etc."
);
$additionaldomainfields[".uk"][] = array(
    "Name" => "Company ID Number",
    "LangVar" => "ukcompanyid",
    "Type" => "text",
    "Size" => "20",
    "Default" => "",
    "Required" => false,
    "Description" => "Companies House registration number (required for companies)"
);

// Copy .UK fields to variants
$additionaldomainfields[".co.uk"] = $additionaldomainfields[".uk"];
$additionaldomainfields[".org.uk"] = $additionaldomainfields[".uk"];
$additionaldomainfields[".me.uk"] = $additionaldomainfields[".uk"];
$additionaldomainfields[".ltd.uk"] = $additionaldomainfields[".uk"];
$additionaldomainfields[".plc.uk"] = $additionaldomainfields[".uk"];

// ============================================================================
// .FR (France) - Requires Contact Type and identification
// ============================================================================
$additionaldomainfields[".fr"][] = array(
    "Name" => "Contact Type",
    "LangVar" => "frcontacttype",
    "Type" => "dropdown",
    "Options" => "Individual,Company",
    "Default" => "Individual",
    "Required" => true,
    "Description" => "Type of registrant"
);
$additionaldomainfields[".fr"][] = array(
    "Name" => "Birth Date",
    "LangVar" => "frbirthdate",
    "Type" => "text",
    "Size" => "10",
    "Default" => "",
    "Required" => false,
    "Description" => "Date of birth (YYYY-MM-DD) - required for individuals"
);
$additionaldomainfields[".fr"][] = array(
    "Name" => "Birth Place",
    "LangVar" => "frbirthplace",
    "Type" => "text",
    "Size" => "50",
    "Default" => "",
    "Required" => false,
    "Description" => "City of birth - required for individuals"
);
$additionaldomainfields[".fr"][] = array(
    "Name" => "SIREN/SIRET",
    "LangVar" => "frsiren",
    "Type" => "text",
    "Size" => "14",
    "Default" => "",
    "Required" => false,
    "Description" => "SIREN (9 digits) or SIRET (14 digits) - required for French companies"
);

// Copy .FR fields to AFNIC TLDs
$additionaldomainfields[".re"] = $additionaldomainfields[".fr"];
$additionaldomainfields[".pm"] = $additionaldomainfields[".fr"];
$additionaldomainfields[".tf"] = $additionaldomainfields[".fr"];
$additionaldomainfields[".wf"] = $additionaldomainfields[".fr"];
$additionaldomainfields[".yt"] = $additionaldomainfields[".fr"];

// ============================================================================
// .RU / .SU (Russia) - Requires Registrant Type and identification
// ============================================================================
$additionaldomainfields[".ru"][] = array(
    "Name" => "Registrant Type",
    "LangVar" => "ruregistranttype",
    "Type" => "dropdown",
    "Options" => "Individual,Organization",
    "Default" => "Organization",
    "Required" => true,
    "Description" => "Type of registrant"
);
$additionaldomainfields[".ru"][] = array(
    "Name" => "Passport Number",
    "LangVar" => "rupassportnumber",
    "Type" => "text",
    "Size" => "20",
    "Default" => "",
    "Required" => false,
    "Description" => "Passport number (required for Russian individuals)"
);
$additionaldomainfields[".ru"][] = array(
    "Name" => "Passport Issue Date",
    "LangVar" => "rupassportdate",
    "Type" => "text",
    "Size" => "10",
    "Default" => "",
    "Required" => false,
    "Description" => "Passport issue date (YYYY-MM-DD)"
);
$additionaldomainfields[".ru"][] = array(
    "Name" => "Passport Issuer",
    "LangVar" => "rupassportissuer",
    "Type" => "text",
    "Size" => "50",
    "Default" => "",
    "Required" => false,
    "Description" => "Authority that issued the passport"
);
$additionaldomainfields[".ru"][] = array(
    "Name" => "Birth Date",
    "LangVar" => "rubirthdate",
    "Type" => "text",
    "Size" => "10",
    "Default" => "",
    "Required" => false,
    "Description" => "Date of birth (YYYY-MM-DD) - required for individuals"
);
$additionaldomainfields[".ru"][] = array(
    "Name" => "TIN",
    "LangVar" => "rutin",
    "Type" => "text",
    "Size" => "12",
    "Default" => "",
    "Required" => false,
    "Description" => "Tax Identification Number (INN) - required for organizations"
);

// Copy .RU fields to .SU
$additionaldomainfields[".su"] = $additionaldomainfields[".ru"];

// ============================================================================
// .ASIA - Requires CED Info (Charter Eligibility Declaration)
// ============================================================================
$additionaldomainfields[".asia"][] = array(
    "Name" => "CED Locality",
    "LangVar" => "asiacedlocality",
    "Type" => "dropdown",
    "Options" => "AF,AU,BD,BN,BT,CC,CK,CN,CX,FJ,FM,GU,HK,ID,IN,IO,JP,KH,KI,KP,KR,LA,LK,MH,MM,MN,MO,MP,MV,MY,NC,NF,NP,NR,NU,NZ,PF,PG,PH,PK,PN,PW,SB,SG,TH,TK,TL,TO,TV,TW,VN,VU,WF,WS",
    "Default" => "SG",
    "Required" => true,
    "Description" => "Country/territory code where the contact is based (Asia-Pacific region)"
);
$additionaldomainfields[".asia"][] = array(
    "Name" => "CED Entity Type",
    "LangVar" => "asiacedentitytype",
    "Type" => "dropdown",
    "Options" => "corporation,cooperative,partnership,government,politicalParty,society,institution,naturalPerson,other",
    "Default" => "corporation",
    "Required" => true,
    "Description" => "Legal entity type"
);
$additionaldomainfields[".asia"][] = array(
    "Name" => "CED ID Form",
    "LangVar" => "asiacedidform",
    "Type" => "dropdown",
    "Options" => "passport,certificate,legislation,societyRegistry,politicalPartyRegistry,other",
    "Default" => "certificate",
    "Required" => true,
    "Description" => "Form of identification"
);
$additionaldomainfields[".asia"][] = array(
    "Name" => "CED ID Number",
    "LangVar" => "asiacedidnumber",
    "Type" => "text",
    "Size" => "30",
    "Default" => "",
    "Required" => false,
    "Description" => "Identification number (passport, registration certificate, etc.)"
);

// ============================================================================
// .ES (Spain) - Requires ID Type and Number
// ============================================================================
$additionaldomainfields[".es"][] = array(
    "Name" => "ID Form",
    "LangVar" => "esidform",
    "Type" => "dropdown",
    "Options" => "DNI,NIE,NIF,Passport,Other",
    "Default" => "NIF",
    "Required" => true,
    "Description" => "Type of identification document"
);
$additionaldomainfields[".es"][] = array(
    "Name" => "ID Number",
    "LangVar" => "esidnumber",
    "Type" => "text",
    "Size" => "20",
    "Default" => "",
    "Required" => true,
    "Description" => "Identification document number"
);

// ============================================================================
// .NYC (New York City) - Requires Domain Purpose
// ============================================================================
$additionaldomainfields[".nyc"][] = array(
    "Name" => "Domain Purpose",
    "LangVar" => "nycdomainpurpose",
    "Type" => "dropdown",
    "Options" => "Personal,Business,Organization",
    "Default" => "Business",
    "Required" => true,
    "Description" => "Purpose of the domain registration"
);

// ============================================================================
// .AMSTERDAM - Requires Domain Purpose
// ============================================================================
$additionaldomainfields[".amsterdam"][] = array(
    "Name" => "Domain Purpose",
    "LangVar" => "amsterdamdomainpurpose",
    "Type" => "dropdown",
    "Options" => "Personal,Business,Organization",
    "Default" => "Business",
    "Required" => true,
    "Description" => "Purpose of the domain registration"
);

// ============================================================================
// .MOSCOW - Requires Domain Purpose
// ============================================================================
$additionaldomainfields[".moscow"][] = array(
    "Name" => "Domain Purpose",
    "LangVar" => "moscowdomainpurpose",
    "Type" => "dropdown",
    "Options" => "Personal,Business,Organization",
    "Default" => "Business",
    "Required" => true,
    "Description" => "Purpose of the domain registration"
);

// ============================================================================
// .CAT (Catalonia) - Requires intended use declaration
// ============================================================================
$additionaldomainfields[".cat"][] = array(
    "Name" => "Intended Use",
    "LangVar" => "catintendeduse",
    "Type" => "text",
    "Size" => "100",
    "Default" => "Promotion of Catalan language and culture",
    "Required" => true,
    "Description" => "Describe how the domain will be used to promote Catalan language and culture"
);

// ============================================================================
// .AERO (Aviation) - Requires ENS Auth Code
// ============================================================================
$additionaldomainfields[".aero"][] = array(
    "Name" => "ENS Auth Code",
    "LangVar" => "aeroensauthcode",
    "Type" => "text",
    "Size" => "30",
    "Default" => "",
    "Required" => true,
    "Description" => "ENS authentication code from SITA"
);

// ============================================================================
// .JOBS - Requires HR Contact information
// ============================================================================
$additionaldomainfields[".jobs"][] = array(
    "Name" => "HR Contact Name",
    "LangVar" => "jobshrname",
    "Type" => "text",
    "Size" => "50",
    "Default" => "",
    "Required" => true,
    "Description" => "Name of HR/recruiting contact"
);
$additionaldomainfields[".jobs"][] = array(
    "Name" => "HR Contact Title",
    "LangVar" => "jobshrtitle",
    "Type" => "text",
    "Size" => "50",
    "Default" => "",
    "Required" => false,
    "Description" => "Title of HR/recruiting contact"
);
$additionaldomainfields[".jobs"][] = array(
    "Name" => "Website URL",
    "LangVar" => "jobswebsite",
    "Type" => "text",
    "Size" => "100",
    "Default" => "",
    "Required" => true,
    "Description" => "Company website URL"
);

// ============================================================================
// .TRAVEL - Requires Industry Type
// ============================================================================
$additionaldomainfields[".travel"][] = array(
    "Name" => "Industry Type",
    "LangVar" => "travelindustrytype",
    "Type" => "dropdown",
    "Options" => "Airlines,Car Rentals,Cruise Lines,Destination Marketing,Hotels,Receptive Operators,Restaurants,Tour Operators,Travel Agencies,Travel Insurance,Travel Publications,Other",
    "Default" => "Travel Agencies",
    "Required" => true,
    "Description" => "Type of travel industry business"
);
$additionaldomainfields[".travel"][] = array(
    "Name" => "UIN",
    "LangVar" => "traveluin",
    "Type" => "text",
    "Size" => "30",
    "Default" => "",
    "Required" => false,
    "Description" => "Unique Identification Number (if available)"
);

// ============================================================================
// .XXX (Adult) - Requires membership agreement
// ============================================================================
$additionaldomainfields[".xxx"][] = array(
    "Name" => "ICM Membership",
    "LangVar" => "xxxicmmembership",
    "Type" => "dropdown",
    "Options" => "Yes,No",
    "Default" => "No",
    "Required" => true,
    "Description" => "Is the registrant an ICM Registry member?"
);
$additionaldomainfields[".xxx"][] = array(
    "Name" => "ICM Member ID",
    "LangVar" => "xxxicmmemberid",
    "Type" => "text",
    "Size" => "30",
    "Default" => "",
    "Required" => false,
    "Description" => "ICM Registry Member ID (if applicable)"
);

// ============================================================================
// .PRO (Professionals) - Requires professional credentials
// ============================================================================
$additionaldomainfields[".pro"][] = array(
    "Name" => "Profession",
    "LangVar" => "proprofession",
    "Type" => "text",
    "Size" => "50",
    "Default" => "",
    "Required" => true,
    "Description" => "Professional designation (e.g., Attorney, CPA, Engineer)"
);
$additionaldomainfields[".pro"][] = array(
    "Name" => "License Number",
    "LangVar" => "prolicensenumber",
    "Type" => "text",
    "Size" => "30",
    "Default" => "",
    "Required" => false,
    "Description" => "Professional license number"
);
$additionaldomainfields[".pro"][] = array(
    "Name" => "Authority",
    "LangVar" => "proauthority",
    "Type" => "text",
    "Size" => "50",
    "Default" => "",
    "Required" => false,
    "Description" => "Licensing authority name"
);

// ============================================================================
// .SE (Sweden) - No additional fields required for most registrations
// ============================================================================
// .SE uses standard contact information

// ============================================================================
// .DK (Denmark) - Requires VAT number for companies
// ============================================================================
$additionaldomainfields[".dk"][] = array(
    "Name" => "VAT Number",
    "LangVar" => "dkvatnumber",
    "Type" => "text",
    "Size" => "20",
    "Default" => "",
    "Required" => false,
    "Description" => "Danish CVR number (required for companies)"
);

// ============================================================================
// .NO (Norway) - Requires Organization Number
// ============================================================================
$additionaldomainfields[".no"][] = array(
    "Name" => "Organization Number",
    "LangVar" => "noorgnum",
    "Type" => "text",
    "Size" => "20",
    "Default" => "",
    "Required" => false,
    "Description" => "Norwegian organization number (required for companies)"
);

// ============================================================================
// .FI (Finland) - Requires identification
// ============================================================================
$additionaldomainfields[".fi"][] = array(
    "Name" => "ID Number",
    "LangVar" => "fiidnumber",
    "Type" => "text",
    "Size" => "20",
    "Default" => "",
    "Required" => false,
    "Description" => "Finnish business ID (Y-tunnus) or personal ID"
);

// ============================================================================
// .PL (Poland) - No additional fields required
// ============================================================================
// .PL uses standard contact information

// ============================================================================
// .CH (Switzerland) - No additional fields required
// ============================================================================
// .CH uses standard contact information

// ============================================================================
// .AT (Austria) - No additional fields required
// ============================================================================
// .AT uses standard contact information

// ============================================================================
// .HK (Hong Kong) - Requires company registration number
// ============================================================================
$additionaldomainfields[".hk"][] = array(
    "Name" => "Company Registration Number",
    "LangVar" => "hkcrn",
    "Type" => "text",
    "Size" => "20",
    "Default" => "",
    "Required" => false,
    "Description" => "Hong Kong Companies Registry number (required for Hong Kong companies)"
);

// ============================================================================
// Conditional Fields Configuration for JavaScript
// This array defines which fields should be shown/hidden based on other field values
// Used by ascio-fields.js for dynamic field visibility
// ============================================================================
if (!isset($GLOBALS['ascio_conditional_fields'])) {
    $GLOBALS['ascio_conditional_fields'] = array(
        '.it' => array(
            'Birth Country' => array(
                'depends_on' => 'Legal Type',
                'show_when' => array('Italian and foreign natural persons')
            )
        ),
        '.ca' => array(
            'Trademark Number' => array(
                'depends_on' => 'Legal Type',
                'show_when' => array('Trademark')
            ),
            'Trademark Name' => array(
                'depends_on' => 'Legal Type',
                'show_when' => array('Trademark')
            ),
            'Trademark Country' => array(
                'depends_on' => 'Legal Type',
                'show_when' => array('Trademark')
            )
        ),
        '.us' => array(
            'Nexus Country' => array(
                'depends_on' => 'Nexus Category',
                'show_when' => array('C31', 'C32')
            )
        ),
        '.ru' => array(
            'Passport Number' => array(
                'depends_on' => 'Registrant Type',
                'show_when' => array('Individual')
            ),
            'Passport Issue Date' => array(
                'depends_on' => 'Registrant Type',
                'show_when' => array('Individual')
            ),
            'Passport Issuer' => array(
                'depends_on' => 'Registrant Type',
                'show_when' => array('Individual')
            ),
            'Birth Date' => array(
                'depends_on' => 'Registrant Type',
                'show_when' => array('Individual')
            ),
            'TIN' => array(
                'depends_on' => 'Registrant Type',
                'show_when' => array('Organization')
            )
        ),
        '.fr' => array(
            'Birth Date' => array(
                'depends_on' => 'Contact Type',
                'show_when' => array('Individual')
            ),
            'Birth Place' => array(
                'depends_on' => 'Contact Type',
                'show_when' => array('Individual')
            ),
            'SIREN/SIRET' => array(
                'depends_on' => 'Contact Type',
                'show_when' => array('Company')
            )
        ),
        '.xxx' => array(
            'ICM Member ID' => array(
                'depends_on' => 'ICM Membership',
                'show_when' => array('Yes')
            )
        )
    );

    // Copy conditional rules to variant TLDs
    $GLOBALS['ascio_conditional_fields']['.su'] = $GLOBALS['ascio_conditional_fields']['.ru'];
    $GLOBALS['ascio_conditional_fields']['.re'] = $GLOBALS['ascio_conditional_fields']['.fr'];
    $GLOBALS['ascio_conditional_fields']['.pm'] = $GLOBALS['ascio_conditional_fields']['.fr'];
    $GLOBALS['ascio_conditional_fields']['.tf'] = $GLOBALS['ascio_conditional_fields']['.fr'];
    $GLOBALS['ascio_conditional_fields']['.wf'] = $GLOBALS['ascio_conditional_fields']['.fr'];
    $GLOBALS['ascio_conditional_fields']['.yt'] = $GLOBALS['ascio_conditional_fields']['.fr'];
}
