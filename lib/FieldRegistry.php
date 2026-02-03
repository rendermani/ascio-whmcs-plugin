<?php

namespace ascio;

/**
 * Field Registry - Single source of truth for API field → WHMCS field mappings
 *
 * Maps TLDKit API field names (e.g. "Registrant.Type") to WHMCS additional field
 * definitions (name, type, options, etc.). TLD plugins read these field names from
 * $params["additionalfields"], so the names here MUST match what plugins expect.
 */
class FieldRegistry
{
    /**
     * Default field definitions keyed by API field name.
     * Each entry defines how the API field renders in WHMCS.
     *
     * @var array<string, array>
     */
    private static $defaultMappings = [
        'Registrant.Type' => [
            'Name' => 'Registrant Type',
            'Type' => 'dropdown',
            'Options' => 'Individual,Organization',
            'Default' => 'Organization',
            'Required' => true,
            'Description' => 'Type of registrant',
        ],
        'Registrant.OrganisationNumber' => [
            'Name' => 'Registrant Number',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Required' => false,
            'Description' => 'Organisation or identification number',
        ],
        'Registrant.VatNumber' => [
            'Name' => 'VAT Number',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Required' => false,
            'Description' => 'VAT registration number',
        ],
        'Registrant.PassportNumber' => [
            'Name' => 'Passport Number',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Required' => false,
            'Description' => 'Passport number',
        ],
        'Registrant.PassportIssueDate' => [
            'Name' => 'Passport Issue Date',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '',
            'Required' => false,
            'Description' => 'Passport issue date (YYYY-MM-DD)',
        ],
        'Registrant.PassportIssuer' => [
            'Name' => 'Passport Issuer',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Required' => false,
            'Description' => 'Authority that issued the passport',
        ],
        'Registrant.BirthDate' => [
            'Name' => 'Birth Date',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '',
            'Required' => false,
            'Description' => 'Date of birth (YYYY-MM-DD)',
        ],
        'Registrant.BirthCountry' => [
            'Name' => 'Birth Country',
            'Type' => 'text',
            'Size' => '2',
            'Default' => '',
            'Required' => false,
            'Description' => 'Two-letter country code of birth country',
        ],
        'Registrant.BirthPlace' => [
            'Name' => 'Birth Place',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Required' => false,
            'Description' => 'City of birth',
        ],
        'Registrant.TIN' => [
            'Name' => 'TIN',
            'Type' => 'text',
            'Size' => '12',
            'Default' => '',
            'Required' => false,
            'Description' => 'Tax Identification Number',
        ],
        'Registrant.SIREN' => [
            'Name' => 'SIREN/SIRET',
            'Type' => 'text',
            'Size' => '14',
            'Default' => '',
            'Required' => false,
            'Description' => 'SIREN (9 digits) or SIRET (14 digits)',
        ],
        'Domain.Purpose' => [
            'Name' => 'Domain Purpose',
            'Type' => 'dropdown',
            'Options' => 'Personal,Business,Organization',
            'Default' => 'Business',
            'Required' => true,
            'Description' => 'Purpose of the domain registration',
        ],
        'Domain.IntendedUse' => [
            'Name' => 'Intended Use',
            'Type' => 'text',
            'Size' => '100',
            'Default' => '',
            'Required' => true,
            'Description' => 'Describe how the domain will be used',
        ],
        'Trademark.Number' => [
            'Name' => 'Trademark Number',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Required' => false,
            'Description' => 'Trademark registration number',
        ],
        'Trademark.Name' => [
            'Name' => 'Trademark Name',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Required' => false,
            'Description' => 'Name of the registered trademark',
        ],
        'Trademark.Country' => [
            'Name' => 'Trademark Country',
            'Type' => 'text',
            'Size' => '2',
            'Default' => '',
            'Required' => false,
            'Description' => 'Two-letter country code where trademark is registered',
        ],
        'Admin.ID' => [
            'Name' => 'Admin ID',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Required' => false,
            'Description' => 'Admin contact identification',
        ],
        'Admin.Number' => [
            'Name' => 'Admin Number',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Required' => false,
            'Description' => 'Admin contact number',
        ],
        'Admin.Type' => [
            'Name' => 'Admin Type',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Required' => false,
            'Description' => 'Admin contact type',
        ],
        'Tech.Number' => [
            'Name' => 'Tech Number',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Required' => false,
            'Description' => 'Tech contact number',
        ],
        'Tech.Type' => [
            'Name' => 'Tech Type',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Required' => false,
            'Description' => 'Tech contact type',
        ],
        'Registrant.IDForm' => [
            'Name' => 'ID Form',
            'Type' => 'dropdown',
            'Options' => '',
            'Default' => '',
            'Required' => false,
            'Description' => 'Type of identification document',
        ],
        'Registrant.IDNumber' => [
            'Name' => 'ID Number',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Required' => false,
            'Description' => 'Identification document number',
        ],
        'Domain.AuthCode' => [
            'Name' => 'Auth Code',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Required' => false,
            'Description' => 'Authorization/authentication code',
        ],
        'Registrant.LocalPresence' => [
            'Name' => 'Local Presence',
            'Type' => 'tickbox',
            'Default' => '',
            'Required' => false,
            'Description' => 'Local presence service required',
        ],
        'CED.Locality' => [
            'Name' => 'CED Locality',
            'Type' => 'dropdown',
            'Options' => 'AF,AU,BD,BN,BT,CC,CK,CN,CX,FJ,FM,GU,HK,ID,IN,IO,JP,KH,KI,KP,KR,LA,LK,MH,MM,MN,MO,MP,MV,MY,NC,NF,NP,NR,NU,NZ,PF,PG,PH,PK,PN,PW,SB,SG,TH,TK,TL,TO,TV,TW,VN,VU,WF,WS',
            'Default' => 'SG',
            'Required' => true,
            'Description' => 'Country/territory code where the contact is based (Asia-Pacific region)',
        ],
        'CED.EntityType' => [
            'Name' => 'CED Entity Type',
            'Type' => 'dropdown',
            'Options' => 'corporation,cooperative,partnership,government,politicalParty,society,institution,naturalPerson,other',
            'Default' => 'corporation',
            'Required' => true,
            'Description' => 'Legal entity type',
        ],
        'CED.IDForm' => [
            'Name' => 'CED ID Form',
            'Type' => 'dropdown',
            'Options' => 'passport,certificate,legislation,societyRegistry,politicalPartyRegistry,other',
            'Default' => 'certificate',
            'Required' => true,
            'Description' => 'Form of identification',
        ],
        'CED.IDNumber' => [
            'Name' => 'CED ID Number',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Required' => false,
            'Description' => 'Identification number',
        ],
        'Domain.NexusCategory' => [
            'Name' => 'Nexus Category',
            'Type' => 'dropdown',
            'Options' => 'C11,C12,C21,C31,C32',
            'Default' => 'C11',
            'Required' => true,
            'Description' => 'C11=US Citizen, C12=Permanent Resident, C21=US Organization, C31=Foreign Org with US presence, C32=Foreign Org with bona fide US presence',
        ],
        'Domain.NexusCountry' => [
            'Name' => 'Nexus Country',
            'Type' => 'text',
            'Size' => '2',
            'Default' => '',
            'Required' => false,
            'Description' => 'Two-letter country code (required for C31/C32 nexus categories only)',
        ],
        'Domain.ApplicationPurpose' => [
            'Name' => 'Application Purpose',
            'Type' => 'dropdown',
            'Options' => 'P1,P2,P3,P4,P5',
            'Default' => 'P1',
            'Required' => true,
            'Description' => 'P1=Business, P2=Non-profit, P3=Personal, P4=Educational, P5=Government',
        ],
        'ICM.Membership' => [
            'Name' => 'ICM Membership',
            'Type' => 'dropdown',
            'Options' => 'Yes,No',
            'Default' => 'No',
            'Required' => true,
            'Description' => 'Is the registrant an ICM Registry member?',
        ],
        'ICM.MemberID' => [
            'Name' => 'ICM Member ID',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Required' => false,
            'Description' => 'ICM Registry Member ID (if applicable)',
        ],
        'Registrant.Profession' => [
            'Name' => 'Profession',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Required' => true,
            'Description' => 'Professional designation',
        ],
        'Registrant.LicenseNumber' => [
            'Name' => 'License Number',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Required' => false,
            'Description' => 'Professional license number',
        ],
        'Registrant.Authority' => [
            'Name' => 'Authority',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Required' => false,
            'Description' => 'Licensing authority name',
        ],
        'HR.ContactName' => [
            'Name' => 'HR Contact Name',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Required' => true,
            'Description' => 'Name of HR/recruiting contact',
        ],
        'HR.ContactTitle' => [
            'Name' => 'HR Contact Title',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Required' => false,
            'Description' => 'Title of HR/recruiting contact',
        ],
        'Domain.WebsiteURL' => [
            'Name' => 'Website URL',
            'Type' => 'text',
            'Size' => '100',
            'Default' => '',
            'Required' => true,
            'Description' => 'Company website URL',
        ],
        'Domain.IndustryType' => [
            'Name' => 'Industry Type',
            'Type' => 'dropdown',
            'Options' => 'Airlines,Car Rentals,Cruise Lines,Destination Marketing,Hotels,Receptive Operators,Restaurants,Tour Operators,Travel Agencies,Travel Insurance,Travel Publications,Other',
            'Default' => 'Travel Agencies',
            'Required' => true,
            'Description' => 'Type of travel industry business',
        ],
        'Domain.UIN' => [
            'Name' => 'UIN',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Required' => false,
            'Description' => 'Unique Identification Number',
        ],
        'Domain.ENSAuthCode' => [
            'Name' => 'ENS Auth Code',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Required' => true,
            'Description' => 'ENS authentication code from SITA',
        ],
        'CIRA.Agreement' => [
            'Name' => 'CIRA Agreement',
            'Type' => 'tickbox',
            'Default' => '',
            'Required' => true,
            'Description' => 'I agree to the CIRA Registrant Agreement',
        ],
        'Registrant.CanadianCitizen' => [
            'Name' => 'Canadian Citizen',
            'Type' => 'tickbox',
            'Default' => '',
            'Required' => false,
            'Description' => 'Check if registrant is a Canadian citizen residing outside Canada',
        ],
        'Registrant.CompanyRegistrationNumber' => [
            'Name' => 'Company Registration Number',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Required' => false,
            'Description' => 'Company registration number',
        ],
        'Registrant.OrganizationNumber' => [
            'Name' => 'Organization Number',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Required' => false,
            'Description' => 'Organization registration number',
        ],
        'Registrant.CVR' => [
            'Name' => 'Registrant CVR nr.',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Required' => false,
            'Description' => 'Danish CVR number',
        ],
        'Admin.CVR' => [
            'Name' => 'Administrator CVR nr.',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Required' => false,
            'Description' => 'Administrator CVR number',
        ],
        'Registrant.DKHostmasterID' => [
            'Name' => 'Registrant DK-Hostmaster-ID',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Required' => false,
            'Description' => 'DK-Hostmaster registrant ID',
        ],
        'Registrant.EligibilityType' => [
            'Name' => 'Eligibility Type',
            'Type' => 'dropdown',
            'Options' => '',
            'Default' => '',
            'Required' => false,
            'Description' => 'Eligibility type',
        ],
        'Registrant.EligibilityID' => [
            'Name' => 'Eligibility ID',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Required' => false,
            'Description' => 'Eligibility identification',
        ],
        'Registrant.EligibilityIDType' => [
            'Name' => 'Eligibility ID Type',
            'Type' => 'dropdown',
            'Options' => '',
            'Default' => '',
            'Required' => false,
            'Description' => 'Type of eligibility identification',
        ],
        'Registrant.EligibilityName' => [
            'Name' => 'Eligibility Name',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Required' => false,
            'Description' => 'Eligibility name',
        ],
        'Registrant.EligibilityReason' => [
            'Name' => 'Eligibility Reason',
            'Type' => 'text',
            'Size' => '100',
            'Default' => '',
            'Required' => false,
            'Description' => 'Reason for eligibility',
        ],
        'Domain.MemberOfSponsoredCommunity' => [
            'Name' => 'Member of sponsored community',
            'Type' => 'dropdown',
            'Options' => 'Yes,No',
            'Default' => 'No',
            'Required' => false,
            'Description' => 'Member of sponsored community',
        ],
        'Registrant.NICDHandle' => [
            'Name' => 'NIC/D handle',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Required' => false,
            'Description' => 'NIC/D handle identifier',
        ],
        'Registrant.CompanyPosition' => [
            'Name' => 'Company position',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Required' => false,
            'Description' => 'Position within the company',
        ],
        'Registrant.BusinessNature' => [
            'Name' => 'Business Nature',
            'Type' => 'text',
            'Size' => '100',
            'Default' => '',
            'Required' => false,
            'Description' => 'Nature of business',
        ],
    ];

    /**
     * TLD-specific overrides where the same API field has different WHMCS rendering.
     * Key: TLD (with dot prefix), Value: array of API field → override definition
     *
     * @var array<string, array<string, array>>
     */
    private static $tldOverrides = [
        '.it' => [
            'Registrant.Type' => [
                'Name' => 'Legal Type',
                'LangVar' => 'itlegaltype',
                'Type' => 'dropdown',
                'Options' => 'Italian and foreign natural persons,Companies/one man companies,Freelance workers/professionals,Non-profit organizations,Public organizations,Other subjects,Non natural foreigners',
                'Default' => 'Companies/one man companies',
                'Required' => true,
                'Description' => 'Legal type of the registrant',
            ],
            'Registrant.OrganisationNumber' => [
                'Name' => 'Tax ID',
                'LangVar' => 'ittaxid',
                'Type' => 'text',
                'Size' => '20',
                'Default' => '',
                'Required' => true,
                'Description' => 'Fiscal code (for individuals) or VAT number (for companies)',
            ],
            'Registrant.BirthCountry' => [
                'Name' => 'Birth Country',
                'LangVar' => 'itbirthcountry',
                'Type' => 'text',
                'Size' => '2',
                'Default' => '',
                'Required' => false,
                'Description' => 'Two-letter country code of birth country (required for natural persons only)',
            ],
        ],
        '.ca' => [
            'Registrant.Type' => [
                'Name' => 'Legal Type',
                'LangVar' => 'calegaltype',
                'Type' => 'dropdown',
                'Options' => 'Corporation,Canadian Citizen,Permanent Resident,Government,Educational,Association,Hospital,Partnership,Trademark,Trade Union,Political Party,Library/Museum,Trust,Aboriginal,Legal Representative,Official Mark',
                'Default' => 'Corporation',
                'Required' => true,
                'Description' => 'Legal type of the registrant (CIRA requirement)',
            ],
            'CIRA.Agreement' => [
                'Name' => 'CIRA Agreement',
                'LangVar' => 'caciraagreement',
                'Type' => 'tickbox',
                'Default' => '',
                'Required' => true,
                'Description' => 'I agree to the CIRA Registrant Agreement',
            ],
            'Registrant.CanadianCitizen' => [
                'Name' => 'Canadian Citizen',
                'LangVar' => 'cacanadiancitizen',
                'Type' => 'tickbox',
                'Default' => '',
                'Required' => false,
                'Description' => 'Check if registrant is a Canadian citizen residing outside Canada',
            ],
            'Trademark.Number' => [
                'Name' => 'Trademark Number',
                'LangVar' => 'catrademarknumber',
                'Type' => 'text',
                'Size' => '30',
                'Default' => '',
                'Required' => false,
                'Description' => 'Trademark registration number (required only for Trademark legal type)',
            ],
            'Trademark.Name' => [
                'Name' => 'Trademark Name',
                'LangVar' => 'catrademarkname',
                'Type' => 'text',
                'Size' => '50',
                'Default' => '',
                'Required' => false,
                'Description' => 'Name of the registered trademark',
            ],
            'Trademark.Country' => [
                'Name' => 'Trademark Country',
                'LangVar' => 'catrademarkcountry',
                'Type' => 'text',
                'Size' => '2',
                'Default' => '',
                'Required' => false,
                'Description' => 'Two-letter country code where trademark is registered',
            ],
        ],
        '.uk' => [
            'Registrant.Type' => [
                'Name' => 'Legal Type',
                'LangVar' => 'uklegaltype',
                'Type' => 'dropdown',
                'Options' => 'IND,LTD,PLC,PTNR,LLP,STRA,RCHAR,GOV,STAT,OTHER,CRC,FCORP,IP,FOTHER,SCHOOL',
                'Default' => 'IND',
                'Required' => true,
                'Description' => 'IND=Individual, LTD=UK Ltd, PLC=UK PLC, PTNR=Partnership, LLP=LLP, RCHAR=Charity, GOV=Government, etc.',
            ],
            'Registrant.OrganisationNumber' => [
                'Name' => 'Company ID Number',
                'LangVar' => 'ukcompanyid',
                'Type' => 'text',
                'Size' => '20',
                'Default' => '',
                'Required' => false,
                'Description' => 'Companies House registration number (required for companies)',
            ],
        ],
        '.fr' => [
            'Registrant.Type' => [
                'Name' => 'Contact Type',
                'LangVar' => 'frcontacttype',
                'Type' => 'dropdown',
                'Options' => 'Individual,Company',
                'Default' => 'Individual',
                'Required' => true,
                'Description' => 'Type of registrant',
            ],
            'Registrant.BirthDate' => [
                'Name' => 'Birth Date',
                'LangVar' => 'frbirthdate',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '',
                'Required' => false,
                'Description' => 'Date of birth (YYYY-MM-DD) - required for individuals',
            ],
            'Registrant.BirthPlace' => [
                'Name' => 'Birth Place',
                'LangVar' => 'frbirthplace',
                'Type' => 'text',
                'Size' => '50',
                'Default' => '',
                'Required' => false,
                'Description' => 'City of birth - required for individuals',
            ],
            'Registrant.SIREN' => [
                'Name' => 'SIREN/SIRET',
                'LangVar' => 'frsiren',
                'Type' => 'text',
                'Size' => '14',
                'Default' => '',
                'Required' => false,
                'Description' => 'SIREN (9 digits) or SIRET (14 digits) - required for French companies',
            ],
        ],
        '.ru' => [
            'Registrant.Type' => [
                'Name' => 'Registrant Type',
                'LangVar' => 'ruregistranttype',
                'Type' => 'dropdown',
                'Options' => 'Individual,Organization',
                'Default' => 'Organization',
                'Required' => true,
                'Description' => 'Type of registrant',
            ],
            'Registrant.PassportNumber' => [
                'Name' => 'Passport Number',
                'LangVar' => 'rupassportnumber',
                'Type' => 'text',
                'Size' => '20',
                'Default' => '',
                'Required' => false,
                'Description' => 'Passport number (required for Russian individuals)',
            ],
            'Registrant.PassportIssueDate' => [
                'Name' => 'Passport Issue Date',
                'LangVar' => 'rupassportdate',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '',
                'Required' => false,
                'Description' => 'Passport issue date (YYYY-MM-DD)',
            ],
            'Registrant.PassportIssuer' => [
                'Name' => 'Passport Issuer',
                'LangVar' => 'rupassportissuer',
                'Type' => 'text',
                'Size' => '50',
                'Default' => '',
                'Required' => false,
                'Description' => 'Authority that issued the passport',
            ],
            'Registrant.BirthDate' => [
                'Name' => 'Birth Date',
                'LangVar' => 'rubirthdate',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '',
                'Required' => false,
                'Description' => 'Date of birth (YYYY-MM-DD) - required for individuals',
            ],
            'Registrant.TIN' => [
                'Name' => 'TIN',
                'LangVar' => 'rutin',
                'Type' => 'text',
                'Size' => '12',
                'Default' => '',
                'Required' => false,
                'Description' => 'Tax Identification Number (INN) - required for organizations',
            ],
        ],
        '.us' => [
            'Domain.ApplicationPurpose' => [
                'Name' => 'Application Purpose',
                'LangVar' => 'usapppurpose',
                'Type' => 'dropdown',
                'Options' => 'P1,P2,P3,P4,P5',
                'Default' => 'P1',
                'Required' => true,
                'Description' => 'P1=Business, P2=Non-profit, P3=Personal, P4=Educational, P5=Government',
            ],
            'Domain.NexusCategory' => [
                'Name' => 'Nexus Category',
                'LangVar' => 'usnexuscategory',
                'Type' => 'dropdown',
                'Options' => 'C11,C12,C21,C31,C32',
                'Default' => 'C11',
                'Required' => true,
                'Description' => 'C11=US Citizen, C12=Permanent Resident, C21=US Organization, C31=Foreign Org with US presence, C32=Foreign Org with bona fide US presence',
            ],
            'Domain.NexusCountry' => [
                'Name' => 'Nexus Country',
                'LangVar' => 'usnexuscountry',
                'Type' => 'text',
                'Size' => '2',
                'Default' => '',
                'Required' => false,
                'Description' => 'Two-letter country code (required for C31/C32 nexus categories only)',
            ],
            'Domain.Purpose' => [
                'Name' => 'Domain Purpose',
                'LangVar' => 'usdomainpurpose',
                'Type' => 'dropdown',
                'Options' => 'P1,P2,P3,P4,P5',
                'Default' => 'P1',
                'Required' => true,
                'Description' => 'Domain purpose (alias for Application Purpose)',
            ],
        ],
        '.asia' => [
            'CED.Locality' => [
                'Name' => 'CED Locality',
                'LangVar' => 'asiacedlocality',
            ],
            'CED.EntityType' => [
                'Name' => 'CED Entity Type',
                'LangVar' => 'asiacedentitytype',
            ],
            'CED.IDForm' => [
                'Name' => 'CED ID Form',
                'LangVar' => 'asiacedidform',
            ],
            'CED.IDNumber' => [
                'Name' => 'CED ID Number',
                'LangVar' => 'asiacedidnumber',
            ],
        ],
        '.es' => [
            'Registrant.IDForm' => [
                'Name' => 'ID Form',
                'LangVar' => 'esidform',
                'Type' => 'dropdown',
                'Options' => 'DNI,NIE,NIF,Passport,Other',
                'Default' => 'NIF',
                'Required' => true,
                'Description' => 'Type of identification document',
            ],
            'Registrant.IDNumber' => [
                'Name' => 'ID Number',
                'LangVar' => 'esidnumber',
                'Type' => 'text',
                'Size' => '20',
                'Default' => '',
                'Required' => true,
                'Description' => 'Identification document number',
            ],
        ],
        '.sg' => [
            'Registrant.OrganisationNumber' => [
                'Name' => 'Registrant ID',
                'LangVar' => 'sgregistrantid',
                'Type' => 'text',
                'Size' => '30',
                'Default' => '',
                'Required' => true,
                'Description' => 'Business registration number (ROC/RCB certificate) of the registrant',
            ],
            'Admin.ID' => [
                'Name' => 'Admin ID',
                'LangVar' => 'sgadminid',
                'Type' => 'text',
                'Size' => '30',
                'Default' => '',
                'Required' => true,
                'Description' => 'SINGPASS ID (NRIC/FIN), UEN, or SGNICID for verification',
            ],
            'Registrant.LocalPresence' => [
                'Name' => 'Local Presence',
                'LangVar' => 'sglocalpresence',
                'Type' => 'tickbox',
                'Default' => '',
                'Required' => false,
                'Description' => 'Check if admin contact is not located in Singapore (local presence service required)',
            ],
        ],
        '.nl' => [
            'Registrant.OrganisationNumber' => [
                'Name' => 'Organisation Number',
                'LangVar' => 'nlorgnum',
                'Type' => 'text',
                'Size' => '20',
                'Default' => '',
                'Required' => false,
                'Description' => 'KVK (Chamber of Commerce) registration number',
            ],
        ],
        '.nyc' => [
            'Domain.Purpose' => [
                'Name' => 'Domain Purpose',
                'LangVar' => 'nycdomainpurpose',
            ],
        ],
        '.amsterdam' => [
            'Domain.Purpose' => [
                'Name' => 'Domain Purpose',
                'LangVar' => 'amsterdamdomainpurpose',
            ],
        ],
        '.moscow' => [
            'Domain.Purpose' => [
                'Name' => 'Domain Purpose',
                'LangVar' => 'moscowdomainpurpose',
            ],
        ],
        '.cat' => [
            'Domain.IntendedUse' => [
                'Name' => 'Intended Use',
                'LangVar' => 'catintendeduse',
                'Default' => 'Promotion of Catalan language and culture',
                'Description' => 'Describe how the domain will be used to promote Catalan language and culture',
            ],
        ],
        '.dk' => [
            'Registrant.VatNumber' => [
                'Name' => 'VAT Number',
                'LangVar' => 'dkvatnumber',
                'Type' => 'text',
                'Size' => '20',
                'Default' => '',
                'Required' => false,
                'Description' => 'Danish CVR number (required for companies)',
            ],
        ],
        '.no' => [
            'Registrant.OrganizationNumber' => [
                'Name' => 'Organization Number',
                'LangVar' => 'noorgnum',
                'Type' => 'text',
                'Size' => '20',
                'Default' => '',
                'Required' => false,
                'Description' => 'Norwegian organization number (required for companies)',
            ],
        ],
        '.fi' => [
            'Registrant.IDNumber' => [
                'Name' => 'ID Number',
                'LangVar' => 'fiidnumber',
                'Type' => 'text',
                'Size' => '20',
                'Default' => '',
                'Required' => false,
                'Description' => 'Finnish business ID (Y-tunnus) or personal ID',
            ],
        ],
        '.hk' => [
            'Registrant.CompanyRegistrationNumber' => [
                'Name' => 'Company Registration Number',
                'LangVar' => 'hkcrn',
                'Type' => 'text',
                'Size' => '20',
                'Default' => '',
                'Required' => false,
                'Description' => 'Hong Kong Companies Registry number (required for Hong Kong companies)',
            ],
        ],
        '.xxx' => [
            'ICM.Membership' => [
                'Name' => 'ICM Membership',
                'LangVar' => 'xxxicmmembership',
            ],
            'ICM.MemberID' => [
                'Name' => 'ICM Member ID',
                'LangVar' => 'xxxicmmemberid',
            ],
        ],
        '.pro' => [
            'Registrant.Profession' => [
                'Name' => 'Profession',
                'LangVar' => 'proprofession',
            ],
            'Registrant.LicenseNumber' => [
                'Name' => 'License Number',
                'LangVar' => 'prolicensenumber',
            ],
            'Registrant.Authority' => [
                'Name' => 'Authority',
                'LangVar' => 'proauthority',
            ],
        ],
        '.aero' => [
            'Domain.ENSAuthCode' => [
                'Name' => 'ENS Auth Code',
                'LangVar' => 'aeroensauthcode',
            ],
        ],
        '.jobs' => [
            'HR.ContactName' => [
                'Name' => 'HR Contact Name',
                'LangVar' => 'jobshrname',
            ],
            'HR.ContactTitle' => [
                'Name' => 'HR Contact Title',
                'LangVar' => 'jobshrtitle',
            ],
            'Domain.WebsiteURL' => [
                'Name' => 'Website URL',
                'LangVar' => 'jobswebsite',
            ],
        ],
        '.travel' => [
            'Domain.IndustryType' => [
                'Name' => 'Industry Type',
                'LangVar' => 'travelindustrytype',
            ],
            'Domain.UIN' => [
                'Name' => 'UIN',
                'LangVar' => 'traveluin',
            ],
        ],
        '.se' => [
            'Registrant.OrganisationNumber' => [
                'Name' => 'Identification Number',
                'Type' => 'text',
                'Size' => '20',
                'Default' => '',
                'Required' => false,
                'Description' => 'Swedish personal or organisation number',
            ],
        ],
        '.nu' => [
            'Registrant.OrganisationNumber' => [
                'Name' => 'Identification Number',
                'Type' => 'text',
                'Size' => '20',
                'Default' => '',
                'Required' => false,
                'Description' => 'Personal or organisation number',
            ],
        ],
    ];

    /**
     * TLD variant inheritance - variants inherit fields from a parent TLD.
     *
     * @var array<string, string>
     */
    private static $tldVariants = [
        '.su' => '.ru',
        '.re' => '.fr',
        '.pm' => '.fr',
        '.tf' => '.fr',
        '.wf' => '.fr',
        '.yt' => '.fr',
        '.co.uk' => '.uk',
        '.org.uk' => '.uk',
        '.me.uk' => '.uk',
        '.ltd.uk' => '.uk',
        '.plc.uk' => '.uk',
        '.com.sg' => '.sg',
        '.edu.sg' => '.sg',
        '.net.sg' => '.sg',
        '.org.sg' => '.sg',
        '.per.sg' => '.sg',
    ];

    /**
     * WHMCS built-in fields that must be removed before adding custom ones.
     * Key: TLD, Value: array of field names to remove.
     *
     * @var array<string, string[]>
     */
    private static $removeFields = [
        '.ca' => ['Legal Type', 'CIRA Agreement', 'WHOIS Opt-out'],
        '.us' => ['Nexus Category', 'Nexus Country', 'Application Purpose'],
        '.sg' => ['RCB Singapore ID', 'Registrant Type'],
        '.uk' => ['Legal Type', 'Company ID Number', 'Registrant Name'],
    ];

    /**
     * Context value mappings - maps API context values to WHMCS dropdown option labels.
     * Used by ConditionalFieldMapper.
     *
     * @var array<string, array<string, array<string, string[]>>>
     */
    private static $contextMappings = [
        '.it' => [
            'Registrant.Type' => [
                'individual' => ['Italian and foreign natural persons'],
                'organization' => ['Companies/one man companies', 'Freelance workers/professionals', 'Non-profit organizations', 'Public organizations', 'Other subjects', 'Non natural foreigners'],
            ],
        ],
        '.ca' => [
            'Registrant.Type' => [
                'trademark' => ['Trademark'],
            ],
        ],
        '.us' => [
            'Domain.NexusCategory' => [
                'foreign' => ['C31', 'C32'],
            ],
        ],
        '.ru' => [
            'Registrant.Type' => [
                'individual' => ['Individual'],
                'organization' => ['Organization'],
            ],
        ],
        '.fr' => [
            'Registrant.Type' => [
                'individual' => ['Individual'],
                'organization' => ['Company'],
            ],
        ],
        '.xxx' => [
            'ICM.Membership' => [
                'yes' => ['Yes'],
            ],
        ],
    ];

    /**
     * Get the WHMCS field definition for an API field, considering TLD overrides.
     *
     * @param string $apiFieldName API field name (e.g. "Registrant.Type")
     * @param string $tld TLD with dot prefix (e.g. ".it")
     * @return array|null WHMCS field definition or null if unknown
     */
    public function getFieldDefinition(string $apiFieldName, string $tld): ?array
    {
        $resolvedTld = $this->resolveTld($tld);

        // Check TLD-specific override first
        if (isset(self::$tldOverrides[$resolvedTld][$apiFieldName])) {
            $override = self::$tldOverrides[$resolvedTld][$apiFieldName];
            // Merge with default to fill in any missing keys
            if (isset(self::$defaultMappings[$apiFieldName])) {
                return array_merge(self::$defaultMappings[$apiFieldName], $override);
            }
            return $override;
        }

        // Fall back to default mapping
        return self::$defaultMappings[$apiFieldName] ?? null;
    }

    /**
     * Resolve a TLD variant to its parent TLD for override lookups.
     */
    public function resolveTld(string $tld): string
    {
        return self::$tldVariants[$tld] ?? $tld;
    }

    /**
     * Get all TLD variants.
     *
     * @return array<string, string>
     */
    public function getTldVariants(): array
    {
        return self::$tldVariants;
    }

    /**
     * Get fields to remove for a TLD (WHMCS built-in field overrides).
     *
     * @param string $tld TLD with dot prefix
     * @return string[] Field names to remove
     */
    public function getRemoveFields(string $tld): array
    {
        $resolvedTld = $this->resolveTld($tld);
        return self::$removeFields[$resolvedTld] ?? [];
    }

    /**
     * Get context value mappings for conditional field resolution.
     *
     * @param string $tld TLD with dot prefix
     * @return array<string, array<string, string[]>>
     */
    public function getContextMappings(string $tld): array
    {
        $resolvedTld = $this->resolveTld($tld);
        return self::$contextMappings[$resolvedTld] ?? [];
    }

    /**
     * Get all registered API field names.
     *
     * @return string[]
     */
    public function getAllApiFieldNames(): array
    {
        return array_keys(self::$defaultMappings);
    }

    /**
     * Get all TLDs that have specific overrides.
     *
     * @return string[]
     */
    public function getTldsWithOverrides(): array
    {
        return array_keys(self::$tldOverrides);
    }

    /**
     * Check if a TLD has specific overrides defined.
     */
    public function hasTldOverrides(string $tld): bool
    {
        $resolvedTld = $this->resolveTld($tld);
        return isset(self::$tldOverrides[$resolvedTld]);
    }

    /**
     * Get all overrides for a TLD.
     *
     * @param string $tld TLD with dot prefix
     * @return array<string, array>
     */
    public function getTldOverrides(string $tld): array
    {
        $resolvedTld = $this->resolveTld($tld);
        return self::$tldOverrides[$resolvedTld] ?? [];
    }
}
