var states = new Array();
states['AU'] = ["Australian Capital Territory","New South Wales","Northern Territory","Queensland","South Australia","Tasmania","Victoria","Western Australia","end"];
states['BR'] = ["AC","AL","AP","AM","BA","CE","DF","ES","GO","MA","MT","MS","MG","PA","PB","PR","PE","PI","RJ","RN","RS","RO","RR","SC","SP","SE","TO","end"];
states['CA'] = ["Alberta","British Columbia","Manitoba","New Brunswick","Newfoundland","Northwest Territories","Nova Scotia","Nunavut","Ontario","Prince Edward Island","Quebec","Saskatchewan","Yukon","end"];
states['FR'] = ["Ain","Aisne","Allier","Alpes-de-Haute-Provence","Hautes-Alpes","Alpes-Maritimes","Ardèche","Ardennes","Ariège","Aube","Aude","Aveyron","Bouches-du-Rhône","Calvados","Cantal","Charente","Charente-Maritime","Cher","Corrèze","Corse-du-Sud", "Haute-Corse", "Côte-d'Or", "Côtes-d'Armor", "Creuse", "Dordogne", "Doubs", "Drôme", "Eure", "Eure-et-Loir", "Finistère", "Gard", "Haute-Garonne", "Gers", "Gironde", "Hérault", "Ille-et-Vilaine", "Indre", "Indre-et-Loire", "Isère", "Jura", "Landes", "Loir-et-Cher", "Loire", "Haute-Loire", "Loire-Atlantique", "Loiret", "Lot", "Lot-et-Garonne", "Lozère", "Maine-et-Loire", "Manche", "Marne", "Haute-Marne", "Mayenne", "Meurthe-et-Moselle", "Meuse", "Morbihan", "Moselle", "Nièvre", "Nord", "Oise", "Orne", "Pas-de-Calais", "Puy-de-Dôme", "Pyrénées-Atlantiques", "Hautes-Pyrénées", "Pyrénées-Orientales", "Bas-Rhin", "Haut-Rhin", "Rhône", "Haute-Saône", "Saône-et-Loire", "Sarthe", "Savoie", "Haute-Savoie", "Paris", "Seine-Maritime", "Seine-et-Marne", "Yvelines", "Deux-Sèvres", "Somme", "Tarn", "Tarn-et-Garonne", "Var", "Vaucluse", "Vendée", "Vienne", "Haute-Vienne", "Vosges", "Yonne", "Territoire de Belfort", "Essonne", "Hauts-de-Seine", "Seine-Saint-Denis", "Val-de-Marne", "Val-d'Oise", "Guadeloupe", "Martinique", "Guyane", "La Réunion", "Mayotte","end"];
states['DE'] = ["Baden-Wuerttemberg","Bayern","Berlin","Brandenburg","Bremen","Hamburg","Hessen","Mecklenburg-Vorpommern","Niedersachsen","Nordrhein-Westfalen","Rheinland-Pfalz","Saarland","Sachsen","Sachsen-Anhalt","Schleswig-Holstein","Thueringen","end"];
states['ES'] = ["ARABA","ALBACETE","ALICANTE","ALMERIA","AVILA","BADAJOZ","ILLES BALEARS","BARCELONA","BURGOS","CACERES","CADIZ","CASTELLON","CIUDAD REAL","CORDOBA","CORUÑA, A","CUENCA","GIRONA","GRANADA","GUADALAJARA","GIPUZKOA","HUELVA","HUESCA","JAEN","LEON","LLEIDA","RIOJA, LA","LUGO","MADRID","MALAGA","MURCIA","NAVARRA","OURENSE","ASTURIAS","PALENCIA","PALMAS, LAS","PONTEVEDRA","SALAMANCA","SANTA CRUZ DE TENERIFE","CANTABRIA","SEGOVIA","SEVILLA","SORIA","TARRAGONA","TERUEL","TOLEDO","VALENCIA","VALLADOLID","BIZKAIA","ZAMORA","ZARAGOZA","CEUTA","MELILLA","end"];
states['IN'] = ["Andaman and Nicobar Islands","Andhra Pradesh","Arunachal Pradesh","Assam","Bihar","Chandigarh","Chhattisgarh","Dadra and Nagar Haveli","Daman and Diu","Delhi","Goa","Gujarat","Haryana","Himachal Pradesh","Jammu and Kashmir","Jharkhand","Karnataka","Kerala","Lakshadweep","Madhya Pradesh","Maharashtra","Manipur","Meghalaya","Mizoram","Nagaland","Orissa","Puducherry","Punjab","Rajasthan","Sikkim","Tamil Nadu","Telangana","Tripura","Uttarakhand","Uttar Pradesh","West Bengal","end"];
states['IT'] = ["AG", "AL", "AN", "AO", "AR", "AP", "AQ", "AT", "AV", "BA", "BT", "BL", "BN", "BG", "BI", "BO", "BZ", "BS", "BR", "CA", "CL", "CB", "CI", "CE", "CT", "CZ", "CH", "CO", "CS", "CR", "KR", "CN", "EN", "FM", "FE", "FI", "FG", "FC", "FR", "GE", "GO", "GR", "IM", "IS", "SP", "LT", "LE", "LC", "LI", "LO", "LU", "MB", "MC", "MN", "MS", "MT", "ME", "MI", "MO", "NA", "NO", "NU", "OT", "OR", "PD", "PA", "PR", "PV", "PG", "PU", "PE", "PC", "PI", "PT", "PN", "PZ", "PO", "RG", "RA", "RC", "RE", "RI", "RN", "RM", "RO", "SA", "VS", "SS", "SV", "SI", "SR", "SO", "TA", "TE", "TR", "TO", "OG", "TP", "TN", "TV", "TS", "UD", "VA", "VE", "VB", "VC", "VR", "VS", "VV", "VI", "VT","end"];
states['NL'] = ["Drenthe","Flevoland","Friesland","Gelderland","Groningen","Limburg","Noord-Brabant","Noord-Holland","Overijssel","Utrecht","Zeeland","Zuid-Holland","end"];
states['NZ'] = ["Northland","Auckland","Waikato","Bay of Plenty","Gisborne","Hawkes Bay","Taranaki","Manawatu-Wanganui","Wellington","Tasman","Nelson","Marlborough","West Coast","Canterbury","Otago","Southland","end"];
states['GB'] = ["Avon","Aberdeenshire","Angus","Argyll and Bute","Barking and Dagenham","Barnet","Barnsley","Bath and North East Somerset","Bedfordshire","Berkshire","Bexley","Birmingham","Blackburn with Darwen","Blackpool","Blaenau Gwent","Bolton","Bournemouth","Bracknell Forest","Bradford","Brent","Bridgend","Brighton and Hove","Bromley","Buckinghamshire","Bury","Caerphilly","Calderdale","Cambridgeshire","Camden","Cardiff","Carmarthenshire","Ceredigion","Cheshire","Cleveland","City of Bristol","City of Edinburgh","City of Kingston upon Hull","City of London","Clackmannanshire","Conwy","Cornwall","Coventry","Croydon","Cumbria","Darlington","Denbighshire","Derby","Derbyshire","Devon","Doncaster","Dorset","Dudley","Dumfries and Galloway","Dundee City","Durham","Ealing","East Ayrshire","East Dunbartonshire","East Lothian","East Renfrewshire","East Riding of Yorkshire","East Sussex","Eilean Siar (Western Isles)","Enfield","Essex","Falkirk","Fife","Flintshire","Gateshead","Glasgow City","Gloucestershire","Greenwich","Gwynedd","Hackney","Halton","Hammersmith and Fulham","Hampshire","Haringey","Harrow","Hartlepool","Havering","Herefordshire","Hertfordshire","Highland","Hillingdon","Hounslow","Inverclyde","Isle of Anglesey","Isle of Wight","Islington","Kensington and Chelsea","Kent","Kingston upon Thames","Kirklees","Knowsley","Lambeth","Lancashire","Leeds","Leicester","Leicestershire","Lewisham","Lincolnshire","Liverpool","London","Luton","Manchester","Medway","Merthyr Tydfil","Merton","Merseyside","Middlesbrough","Middlesex","Midlothian","Milton Keynes","Monmouthshire","Moray","Neath Port Talbot","Newcastle upon Tyne","Newham","Newport","Norfolk","North Ayrshire","North East Lincolnshire","North Lanarkshire","North Lincolnshire","North Somerset","North Tyneside","North Yorkshire","Northamptonshire","Northumberland","North Humberside","Nottingham","Nottinghamshire","Oldham","Orkney Islands","Oxfordshire","Pembrokeshire","Perth and Kinross","Peterborough","Plymouth","Poole","Portsmouth","Powys","Reading","Redbridge","Renfrewshire","Rhondda Cynon Taff","Richmond upon Thames","Rochdale","Rotherham","Rutland","Salford","Sandwell","Sefton","Sheffield","Shetland Islands","Shropshire","Slough","Solihull","Somerset","South Ayrshire","South Humberside","South Gloucestershire","South Lanarkshire","South Tyneside","Southampton","Southend-on-Sea","Southwark","South Yorkshire","St. Helens","Staffordshire","Stirling","Stockport","Stockton-on-Tees","Stoke-on-Trent","Suffolk","Sunderland","Surrey","Sutton","Swansea","Swindon","Tameside","Telford and Wrekin","The Scottish Borders","The Vale of Glamorgan","Thurrock","Torbay","Torfaen","Tower Hamlets","Trafford","Tyne and Wear","Wakefield","Walsall","Waltham Forest","Wandsworth","Warrington","Warwickshire","West Midlands","West Dunbartonshire","West Lothian","West Sussex","West Yorkshire","Westminster","Wigan","Wiltshire","Windsor and Maidenhead","Wirral","Wokingham","Wolverhampton","Worcestershire","Wrexham","York","Co. Antrim","Co. Armagh","Co. Down","Co. Fermanagh","Co. Londonderry","Co. Tyrone","end"];
states['US'] = ["Alabama","Alaska","Arizona","Arkansas","California","Colorado","Connecticut","Delaware","District of Columbia","Florida","Georgia","Hawaii","Idaho","Illinois","Indiana","Iowa","Kansas","Kentucky","Louisiana","Maine","Maryland","Massachusetts","Michigan","Minnesota","Mississippi","Missouri","Montana","Nebraska","Nevada","New Hampshire","New Jersey","New Mexico","New York","North Carolina","North Dakota","Ohio","Oklahoma","Oregon","Pennsylvania","Rhode Island","South Carolina","South Dakota","Tennessee","Texas","Utah","Vermont","Virginia","Washington","West Virginia","Wisconsin","Wyoming","end"];

jQuery(document).ready(function(){
    activateCountrySelector("owner");
    activateCountrySelector("admin");
    activateCountrySelector("tech");

});
function activateCountrySelector (contactType) {    
    jQuery("input[name="+contactType+"State]").attr("id",contactType+"State");   
    jQuery("select[name="+contactType+"Country]").change(function() {
        statechange(this);
    });
    statechange($("#"+contactType+"Country"));

} 
function statechange(element) {
    // Check if we need the select-inline class applied,
    // which is a data attribute set by the input field.
    var addClass = '';
    var type = $(element).data("type"); 
    var stateSelectorName = " #"+type+"State";
    var parentSelector = "#" + type+" ";
    var stateInput =  jQuery(parentSelector+stateSelectorName);
    var stateSelector =  jQuery(parentSelector+stateSelectorName+"Select");
    var stateParent = stateInput.parent();
    var country = jQuery(parentSelector+"select[name="+type+"Country]").val();
    var data = stateInput.data("selectinlinedropdown");
    addClass = getStateSelectClass(data);

    var state = stateInput.val();
    var statesTab = stateInput.attr("tabindex");
    var disabled = stateInput.attr("disabled"); 
    var readonly = stateInput.attr("readonly");
    if (typeof(statesTab) == "undefined") statesTab = '';
    if (typeof(disabled) == "undefined") disabled = '';
    if (typeof(readonly) == "undefined") readonly = '';
    if (states[country]) {
        stateInput.hide()
            .removeAttr("name")
            .removeAttr("required");
        jQuery(stateSelectorName+"#inputStateIcon").hide();
        stateSelector.remove();
        var stateops = '';
        for (key in states[country]) {
            stateval = states[country][key];
            if (stateval=="end") break;
            stateops += '<option';
            if (stateval==state) stateops += ' selected="selected"'
            stateops += '>'+stateval+'</option>';
        }
        if(statesTab != '') { statesTab = ' tabindex="'+statesTab+'"'; }
        if (disabled || readonly) {
            disabled = ' disabled';
        }
        stateParent.append('<select name="'+type+'State" class="' + stateInput.attr("class") + addClass + '" id="'+type+'StateSelect"' + statesTab + disabled + '><option value="">&mdash;</option>' + stateops + '</select>');
        var required = true;
        if (typeof stateNotRequired == "boolean" && stateNotRequired) {
            required = false;
        }
        $(parentSelector+stateSelectorName+"Select").attr("required", required);
    } else {
        var required = true;
        if (typeof stateNotRequired == "boolean" && stateNotRequired) {
            required = false;
        }
        jQuery(parentSelector+stateSelectorName+"Select").remove();
        stateInput.show()
            .attr("name","state")
            .attr("required", required);
        jQuery(parentSelector+"#inputStateIcon").show();
    }
}

/**
 * Gets the select-inline class name, depending on whether
 * data is true when evaluated ToBoolean.
 *
 * @param {Number} data The data attribute from the form.
 * @return {String} addClass Returns the select-inline class on success.
 */
function getStateSelectClass(data)
{
    var addClass = '';

    if (data) {
        addClass = ' select-inline';
    }

    return addClass;
}

function setContactData(type,data) {
    $.ajax({
        url: "modules/servers/asciossl/getcontact.php",
        datatype : "json",
        data: { contactId: $("#"+type+"Id").val()}
      }).done(function(data) {
         Object.keys(data).forEach((key) => {
            var value = data[key];
            $("#"+type+key).val(value);
        });
        $("#"+type+"Phone").intlTelInput('setNumber',data.Phone) 
        $("#"+type+"PhonePrefix").val($("#"+type+" .selected-dial-code").html()); 
      });
      var updateDiv = $("#"+type+"update");
      updateDiv.fadeOut();
}
function emptyContactData(type) {
    $("#"+type+" input").each((key,input) => {
        $(input).val("");
    })
    var updateDiv = $("#"+type+"update");
    updateDiv.fadeOut();
}
function copyContactData(fromType,toType) {
    var inputsFrom = $("#"+fromType+" input, #"+fromType+" select");
    var prefix = $("#"+fromType+" .selected-dial-code").html(); 
    var phoneFrom =  prefix +"."+ $("#"+fromType+"Phone").val()
    var inputsTo = $("#"+toType+" input, #"+toType+" select");
    inputsFrom.each((key,from) => {
        $(inputsTo[key]).val($(from).val());       
    })
    $("#"+toType+"Phone").intlTelInput('setNumber',phoneFrom)   
    var title = $("#"+toType+"control label");
    var updateDiv = $("#"+toType+"update");
    var updateButton = $("#u"+toType);
    updateDiv.fadeIn();
    updateButton.data("from",fromType);
    updateButton.data("to",toType);
    var titleTxt = fromType == "owner" ? "Owner" : "Administrative";
    title.html("Update from "+titleTxt+ " Contact");
}
function checkCsr() {
    if(!$("#csr").val() ) return;
    $.ajax({
        url: "modules/servers/asciossl/checkcsr.php",
        datatype : "json",
        method : "post",
        data: { csr : $("#csr").val() }
      }).done(function(data) {        
        var csr = data.csr;
        if(csr.CN) {
            $("#csrvalid").html(" <span class='glyphicon glyphicon-ok'> </span> Valid CSR");
            $("#csrvalid").attr("class","Completed");
            $("#domainname").html(csr.CN);
            $("#domainroot").val(data.domainroot);
            $("#commonName").val(csr.CN);
            $("#certdetails").slideDown();
            $("#approvalEmail").html(data.emailOptions);
            //setApprovalEmails(data.mx);  
            $("#approvalEmail").val($("#approvalEmail").attr("value"));
            $("#webserver").val($("#webserver").attr("value"));
            $("#verificationType").val($("#verificationType").attr("value"));
            if($("#verificationType").val() !== "Email") {
                $("#approvalEmailContainer").hide();
                $("#mxverification").hide();
            } else {
                $("#approvalEmailContainer").show();
                $("#mxverification").show();
            }
        } else {
            $("#csrvalid").attr("class","Failed");
            $("#certdetails").slideUp();
            $("#csrvalid").html(" <span class='glyphicon glyphicon-remove'> </span> Invalid CSR!");
        }

    }).fail(function( jqXHR, textStatus, errorThrown ){
        console.log("FAIL");
        console.log(jqXHR);
        console.log(textStatus);
        console.log(errorThrown);
    });
    
}
function setVerificationType() {
    var verificationType = $("#verificationType").val();
    if(verificationType == "Dns" || verificationType == "File") {
        var defaultAddress = "admin@"+$("#domainroot").val();
        $("#approvalEmail").val(defaultAddress);
        $("#approvalEmail").removeAttr("required");
    } 
    if(verificationType == "Email") {
        $("#approvalEmailContainer").show();
        $("#mxverification").show();
        $("#approvalEmail").attr("required",true);
    } else {
        $("#approvalEmailContainer").hide();
        $("#mxverification").hide();
        $("#approvalEmail").removeAttr("required");
        
    }
    setSanVerificationType();
}
function setSanVerificationType() {
    if(!$("#San_0")[0]) {
        return;
    }
    var verificationType = $("#verificationType").val();
    if(verificationType == "Email") {
        $(".san-no-email").each((nr,san) => {
            $(san).hide();
            $("#SanEmail_"+nr).show();
            $("#SanEmail_"+nr).attr("required",true);
        });
    } else {
        $(".san-no-email").each((nr,san) => {
            $(san).show();
            $(san).html(verificationType+"-Verification");            
            $("#SanEmail_"+nr).hide();
            $("#SanEmail_"+nr).removeAttr("required");
        });
    }
}
function fixPhonePrefix (){
    $("#ownerPhonePrefix").val($("#owner .selected-dial-code").html());
    $("#adminPhonePrefix").val($("#admin .selected-dial-code").html());
    $("#techPhonePrefix").val($("#tech .selected-dial-code").html());
}
function updateStatus() {
    if(!$("#order-status")[0]) return;
    window.setTimeout(function() {
        $.ajax({
            url: "modules/servers/asciossl/status.php",
            datatype : "json",
            method : "post",
            data: { serviceId : $("#order-status").data("serviceid")}
          }).done(function(data) {        
            $("#order-status").html(data.status);
        });
        updateStatus();
    },5000);

}
jQuery(document).ready(function(){
    jQuery(".contact-select").change(function() {
        var type = $(this).data("type");
        var value = $(this).val();
        switch(value) {
            case  "newcontact" : emptyContactData(type); break;
            case  "owner" : copyContactData("owner",type); break;
            case  "admin" : copyContactData("admin",type); break;
            default : setContactData(type)
        }       
    });
    checkCsr();
    $("#csrbutton").click(function(){
        checkCsr();
    })
    $(".country").click(function(){
        // workaround, todo fix this. create new intl inputs. remove whmcs assignments.
        window.setTimeout(fixPhonePrefix,10)
        return;
    })
    $("#verificationType").change(setVerificationType);
    $("#csr").change(checkCsr);
    var updateClick = function() {
        var from = $(this).data("from");
        var to = $(this).data("to");
        copyContactData(from,to);
        return ;
    }
    $("#uadmin").click(updateClick);
    $("#utech").click(updateClick);
    fixPhonePrefix();
    if($("#order-status")[0]) {
        updateStatus();
    }
    $(".san-input").change(function () {
        var nr = $(this).data("nr");
        var select = $("#SanEmail_"+nr);
        $.ajax({
            url: "modules/servers/asciossl/approval-addresses.php",
            datatype : "json",
            method : "post",
            data: { fqdn : $(this).val()}
          }).done(function(data) {        
            select.html(data.html);
            var firstOption = $("#SanEmail_"+nr+ " option:first");
            select.val(firstOption.val());
            select.prop('selectedIndex', 0)
        });
    })
    

});


