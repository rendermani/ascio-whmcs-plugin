<h1>SSL Certificate</h1>
<script type="text/javascript" src="/whmcs/modules/servers/asciossl/asciossl.js"></script>
<form role="form" method="post" id="sslForm" action="{$smarty.server.PHP_SELF}?action=productdetails&step=register&id={$serviceid}">
   <input type="hidden" name="submit" value="true" />
   <input type="hidden" name="step" value="register">
   <input type="hidden" name="random" value="{$random}">
    <input type="hidden" name="id" value="{$serviceid}">
        {if $errors}                   
            <div class="alert alert-danger" role="alert">
                <p><b>Your last submission failed, please retry:</b></p>
                {foreach from=$errors  item=error}
                    <p>{$error}</p>
                {/foreach}
            </div>            
        {/if}
         <h2>Certificate Owner</h2>
        <div class="row">
             <div class="col-sm-6">            
                <div class="form-group">
                    <label for="ownerId" class="control-label">Existing Contact</label>
                    <select name="selectedId" id="ownerId" class="form-control contact-select" data-type="owner" >
                        {$contactList}
                    </select>
                </div>
             </div>
        </div>
        <div class="row" id="owner">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="ownerTitle" class="control-label">Title</label>
                    <input required="required" type="text" name="ownerTitle" id="ownerTitle" value="{$ownertitle}" class="form-control" />
                </div>
                <div class="form-group">                
                    <label for="ownerFirstName" class="control-label">{$LANG.clientareafirstname}</label>
                    <input required="required" type="text" name="ownerFirstName" id="ownerFirstName" value="{$ownerfirstname}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="ownerLastName" class="control-label">{$LANG.clientarealastname}</label>
                    <input required="required" type="text" name="ownerLastName" id="ownerLastName" value="{$ownerlastname}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="ownerCompanyName" class="control-label">{$LANG.clientareacompanyname}</label>
                    <input  type="text" name="ownerCompanyName" id="ownerCompanyName" value="{$ownercompanyname}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="ownerEmail" class="control-label">{$LANG.clientareaemail}</label>
                    <input required="required" type="email" name="ownerEmail" id="ownerEmail" value="{$owneremail}" class="form-control" />
                </div>

  <div class="form-group">
                    <label for="ownerPhone" class="control-label">{$LANG.clientareaphonenumber}</label>
                    <input type="tel" name="phonenumberowner" id="ownerPhone" value="{$ownerphone}" class="form-control" />
                    <input type ="hidden" name="ownerPhonePrefix" id="ownerPhonePrefix"/>
                </div>

                
              
            </div>
            <div class="col-sm-6 col-xs-12 pull-right">
                <div class="form-group">
                    <label for="ownerAddress1" class="control-label">{$LANG.clientareaaddress1}</label>
                    <input required="required" type="text" name="ownerAddress1" id="ownerAddress1" value="{$owneraddress1}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="ownerAddress2" class="control-label">{$LANG.clientareaaddress2}</label>
                    <input type="text" name="ownerAddress2" id="ownerAddress2" value="{$owneraddress2}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="ownerCity" class="control-label">{$LANG.clientareacity}</label>
                    <input required="required" type="text" name="ownerCity" id="ownerCity" value="{$ownercity}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="ownerState" class="control-label">{$LANG.clientareastate}</label>
                    <input required="required" type="text" name="ownerState" id="ownerState" value="{$ownerstate}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="ownerPostcode" class="control-label">{$LANG.clientareapostcode}</label>
                    <input required="required" type="text" name="ownerPostcode" id="ownerPostcode" value="{$ownerpostcode}" class="form-control" />
                </div>
                <div class="form-group">
                    <label class="control-label" for="ownerCountry">{$LANG.clientareacountry}</label>
                    <select name="ownerCountry" id="ownerCountry" class="form-control"  data-type="owner"><option value="AF">Afghanistan</option><option value="AX">Aland Islands</option><option value="AL">Albania</option><option value="DZ">Algeria</option><option value="AS">American Samoa</option><option value="AD">Andorra</option><option value="AO">Angola</option><option value="AI">Anguilla</option><option value="AQ">Antarctica</option><option value="AG">Antigua And Barbuda</option><option value="AR">Argentina</option><option value="AM">Armenia</option><option value="AW">Aruba</option><option value="AU">Australia</option><option value="AT">Austria</option><option value="AZ">Azerbaijan</option><option value="BS">Bahamas</option><option value="BH">Bahrain</option><option value="BD">Bangladesh</option><option value="BB">Barbados</option><option value="BY">Belarus</option><option value="BE">Belgium</option><option value="BZ">Belize</option><option value="BJ">Benin</option><option value="BM">Bermuda</option><option value="BT">Bhutan</option><option value="BO">Bolivia</option><option value="BA">Bosnia And Herzegovina</option><option value="BW">Botswana</option><option value="BV">Bouvet Island</option><option value="BR">Brazil</option><option value="IO">British Indian Ocean Territory</option><option value="BN">Brunei Darussalam</option><option value="BG">Bulgaria</option><option value="BF">Burkina Faso</option><option value="BI">Burundi</option><option value="KH">Cambodia</option><option value="CM">Cameroon</option><option value="CA">Canada</option><option value="CV">Cape Verde</option><option value="KY">Cayman Islands</option><option value="CF">Central African Republic</option><option value="TD">Chad</option><option value="CL">Chile</option><option value="CN">China</option><option value="CX">Christmas Island</option><option value="CC">Cocos (Keeling) Islands</option><option value="CO">Colombia</option><option value="KM">Comoros</option><option value="CG">Congo</option><option value="CD">Congo, Democratic Republic</option><option value="CK">Cook Islands</option><option value="CR">Costa Rica</option><option value="CI">Cote D'Ivoire</option><option value="HR">Croatia</option><option value="CU">Cuba</option><option value="CW">Curacao</option><option value="CY">Cyprus</option><option value="CZ">Czech Republic</option><option value="DK">Denmark</option><option value="DJ">Djibouti</option><option value="DM">Dominica</option><option value="DO">Dominican Republic</option><option value="EC">Ecuador</option><option value="EG">Egypt</option><option value="SV">El Salvador</option><option value="GQ">Equatorial Guinea</option><option value="ER">Eritrea</option><option value="EE">Estonia</option><option value="ET">Ethiopia</option><option value="FK">Falkland Islands (Malvinas)</option><option value="FO">Faroe Islands</option><option value="FJ">Fiji</option><option value="FI">Finland</option><option value="FR">France</option><option value="GF">French Guiana</option><option value="PF">French Polynesia</option><option value="TF">French Southern Territories</option><option value="GA">Gabon</option><option value="GM">Gambia</option><option value="GE">Georgia</option><option value="DE" selected="selected">Germany</option><option value="GH">Ghana</option><option value="GI">Gibraltar</option><option value="GR">Greece</option><option value="GL">Greenland</option><option value="GD">Grenada</option><option value="GP">Guadeloupe</option><option value="GU">Guam</option><option value="GT">Guatemala</option><option value="GG">Guernsey</option><option value="GN">Guinea</option><option value="GW">Guinea-Bissau</option><option value="GY">Guyana</option><option value="HT">Haiti</option><option value="HM">Heard Island &amp; Mcdonald Islands</option><option value="VA">Holy See (Vatican City State)</option><option value="HN">Honduras</option><option value="HK">Hong Kong</option><option value="HU">Hungary</option><option value="IS">Iceland</option><option value="IN">India</option><option value="ID">Indonesia</option><option value="IR">Iran, Islamic Republic Of</option><option value="IQ">Iraq</option><option value="IE">Ireland</option><option value="IM">Isle Of Man</option><option value="IL">Israel</option><option value="IT">Italy</option><option value="JM">Jamaica</option><option value="JP">Japan</option><option value="JE">Jersey</option><option value="JO">Jordan</option><option value="KZ">Kazakhstan</option><option value="KE">Kenya</option><option value="KI">Kiribati</option><option value="KR">Korea</option><option value="KW">Kuwait</option><option value="KG">Kyrgyzstan</option><option value="LA">Lao People's Democratic Republic</option><option value="LV">Latvia</option><option value="LB">Lebanon</option><option value="LS">Lesotho</option><option value="LR">Liberia</option><option value="LY">Libyan Arab Jamahiriya</option><option value="LI">Liechtenstein</option><option value="LT">Lithuania</option><option value="LU">Luxembourg</option><option value="MO">Macao</option><option value="MK">Macedonia</option><option value="MG">Madagascar</option><option value="MW">Malawi</option><option value="MY">Malaysia</option><option value="MV">Maldives</option><option value="ML">Mali</option><option value="MT">Malta</option><option value="MH">Marshall Islands</option><option value="MQ">Martinique</option><option value="MR">Mauritania</option><option value="MU">Mauritius</option><option value="YT">Mayotte</option><option value="MX">Mexico</option><option value="FM">Micronesia, Federated States Of</option><option value="MD">Moldova</option><option value="MC">Monaco</option><option value="MN">Mongolia</option><option value="ME">Montenegro</option><option value="MS">Montserrat</option><option value="MA">Morocco</option><option value="MZ">Mozambique</option><option value="MM">Myanmar</option><option value="NA">Namibia</option><option value="NR">Nauru</option><option value="NP">Nepal</option><option value="NL">Netherlands</option><option value="AN">Netherlands Antilles</option><option value="NC">New Caledonia</option><option value="NZ">New Zealand</option><option value="NI">Nicaragua</option><option value="NE">Niger</option><option value="NG">Nigeria</option><option value="NU">Niue</option><option value="NF">Norfolk Island</option><option value="MP">Northern Mariana Islands</option><option value="NO">Norway</option><option value="OM">Oman</option><option value="PK">Pakistan</option><option value="PW">Palau</option><option value="PS">Palestine, State of</option><option value="PA">Panama</option><option value="PG">Papua New Guinea</option><option value="PY">Paraguay</option><option value="PE">Peru</option><option value="PH">Philippines</option><option value="PN">Pitcairn</option><option value="PL">Poland</option><option value="PT">Portugal</option><option value="PR">Puerto Rico</option><option value="QA">Qatar</option><option value="RE">Reunion</option><option value="RO">Romania</option><option value="RU">Russian Federation</option><option value="RW">Rwanda</option><option value="BL">Saint Barthelemy</option><option value="SH">Saint Helena</option><option value="KN">Saint Kitts And Nevis</option><option value="LC">Saint Lucia</option><option value="MF">Saint Martin</option><option value="PM">Saint Pierre And Miquelon</option><option value="VC">Saint Vincent And Grenadines</option><option value="WS">Samoa</option><option value="SM">San Marino</option><option value="ST">Sao Tome And Principe</option><option value="SA">Saudi Arabia</option><option value="SN">Senegal</option><option value="RS">Serbia</option><option value="SC">Seychelles</option><option value="SL">Sierra Leone</option><option value="SG">Singapore</option><option value="SK">Slovakia</option><option value="SI">Slovenia</option><option value="SB">Solomon Islands</option><option value="SO">Somalia</option><option value="ZA">South Africa</option><option value="GS">South Georgia And Sandwich Isl.</option><option value="ES">Spain</option><option value="LK">Sri Lanka</option><option value="SD">Sudan</option><option value="SR">Suriname</option><option value="SJ">Svalbard And Jan Mayen</option><option value="SZ">Swaziland</option><option value="SE">Sweden</option><option value="CH">Switzerland</option><option value="SY">Syrian Arab Republic</option><option value="TW">Taiwan</option><option value="TJ">Tajikistan</option><option value="TZ">Tanzania</option><option value="TH">Thailand</option><option value="TL">Timor-Leste</option><option value="TG">Togo</option><option value="TK">Tokelau</option><option value="TO">Tonga</option><option value="TT">Trinidad And Tobago</option><option value="TN">Tunisia</option><option value="TR">Turkey</option><option value="TM">Turkmenistan</option><option value="TC">Turks And Caicos Islands</option><option value="TV">Tuvalu</option><option value="UG">Uganda</option><option value="UA">Ukraine</option><option value="AE">United Arab Emirates</option><option value="GB">United Kingdom</option><option value="US">United States</option><option value="UM">United States Outlying Islands</option><option value="UY">Uruguay</option><option value="UZ">Uzbekistan</option><option value="VU">Vanuatu</option><option value="VE">Venezuela</option><option value="VN">Viet Nam</option><option value="VG">Virgin Islands, British</option><option value="VI">Virgin Islands, U.S.</option><option value="WF">Wallis And Futuna</option><option value="EH">Western Sahara</option><option value="YE">Yemen</option><option value="ZM">Zambia</option><option value="ZW">Zimbabwe</option></select>
                </div>

            </div>
        </div>
        <h2>Administrative Contact</h2>
        <div class="row" id="admincontrol">
             <div class="col-sm-6">            
                <div class="form-group">
                    <label for="adminId" class="control-label">Existing Contact</label>
                    <select name="selectedId" id="adminId" class="form-control contact-select" data-type="admin">
                         <option value="owner">Use Owner Contact </option>
                        {$contactList}
                    </select>
                </div>
             </div>
             <div class="col-sm-6" id="adminupdate">            
                <div class="form-group">
                    <label for="uadmin">Use Owner Contact</label><br/>
                    <button  type="button" class="btn" id="uadmin"  data-from="owner" data-to="admin">update</button>
                </div>
             </div>
        </div>
        <div class="row" id="admin">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="adminTitle" class="control-label">Title</label>
                    <input required="required" type="text" name="adminTitle" id="adminTitle" value="{$admintitle}" class="form-control" />
                </div>
                <div class="form-group">                
                    <label for="adminFirstName" class="control-label">{$LANG.clientareafirstname}</label>
                    <input required="required" type="text" name="adminFirstName" id="adminFirstName" value="{$adminfirstname}" class="form-control" />
                </div>
                <div class="form-group">
                    <label for="adminLastName" class="control-label">{$LANG.clientarealastname}</label>
                    <input required="required" type="text" name="adminLastName" id="adminLastName" value="{$adminlastname}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="adminCompanyName" class="control-label">{$LANG.clientareacompanyname}</label>
                    <input type="text" name="adminCompanyName" id="adminCompanyName" value="{$admincompanyname}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="adminEmail" class="control-label">{$LANG.clientareaemail}</label>
                    <input required="required" type="email" name="adminEmail" id="adminEmail" value="{$adminemail}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="adminPhone" class="control-label">{$LANG.clientareaphonenumber}</label>
                    <input type="tel" name="phonenumberadmin" id="adminPhone" value="{$adminphone}" class="form-control" />
                    <input type ="hidden" name="adminPhonePrefix" id="adminPhonePrefix"/>
                </div>
              
            </div>
            <div class="col-sm-6 col-xs-12 pull-right">

                <div class="form-group">
                    <label for="adminAddress1" class="control-label">{$LANG.clientareaaddress1}</label>
                    <input required="required" type="text" name="adminAddress1" id="adminAddress1" value="{$adminaddress1}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="adminAddress2" class="control-label">{$LANG.clientareaaddress2}</label>
                    <input type="text" name="adminAddress2" id="adminAddress2" value="{$adminaddress2}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="adminCity" class="control-label">{$LANG.clientareacity}</label>
                    <input required="required" type="text" name="adminCity" id="adminCity" value="{$admincity}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="adminState" class="control-label">{$LANG.clientareastate}</label>
                    <input required="required" type="text" name="adminState" id="adminState" value="{$adminstate}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="adminPostcode" class="control-label">{$LANG.clientareapostcode}</label>
                    <input required="required" type="text" name="adminPostcode" id="adminPostcode" value="{$adminpostcode}" class="form-control" />
                </div>
                <div class="form-group">
                    <label class="control-label" for="adminCountry">{$LANG.clientareacountry}</label>
                    <select name="adminCountry" id="adminCountry" class="form-control"  data-type="admin"><option value="AF">Afghanistan</option><option value="AX">Aland Islands</option><option value="AL">Albania</option><option value="DZ">Algeria</option><option value="AS">American Samoa</option><option value="AD">Andorra</option><option value="AO">Angola</option><option value="AI">Anguilla</option><option value="AQ">Antarctica</option><option value="AG">Antigua And Barbuda</option><option value="AR">Argentina</option><option value="AM">Armenia</option><option value="AW">Aruba</option><option value="AU">Australia</option><option value="AT">Austria</option><option value="AZ">Azerbaijan</option><option value="BS">Bahamas</option><option value="BH">Bahrain</option><option value="BD">Bangladesh</option><option value="BB">Barbados</option><option value="BY">Belarus</option><option value="BE">Belgium</option><option value="BZ">Belize</option><option value="BJ">Benin</option><option value="BM">Bermuda</option><option value="BT">Bhutan</option><option value="BO">Bolivia</option><option value="BA">Bosnia And Herzegovina</option><option value="BW">Botswana</option><option value="BV">Bouvet Island</option><option value="BR">Brazil</option><option value="IO">British Indian Ocean Territory</option><option value="BN">Brunei Darussalam</option><option value="BG">Bulgaria</option><option value="BF">Burkina Faso</option><option value="BI">Burundi</option><option value="KH">Cambodia</option><option value="CM">Cameroon</option><option value="CA">Canada</option><option value="CV">Cape Verde</option><option value="KY">Cayman Islands</option><option value="CF">Central African Republic</option><option value="TD">Chad</option><option value="CL">Chile</option><option value="CN">China</option><option value="CX">Christmas Island</option><option value="CC">Cocos (Keeling) Islands</option><option value="CO">Colombia</option><option value="KM">Comoros</option><option value="CG">Congo</option><option value="CD">Congo, Democratic Republic</option><option value="CK">Cook Islands</option><option value="CR">Costa Rica</option><option value="CI">Cote D'Ivoire</option><option value="HR">Croatia</option><option value="CU">Cuba</option><option value="CW">Curacao</option><option value="CY">Cyprus</option><option value="CZ">Czech Republic</option><option value="DK">Denmark</option><option value="DJ">Djibouti</option><option value="DM">Dominica</option><option value="DO">Dominican Republic</option><option value="EC">Ecuador</option><option value="EG">Egypt</option><option value="SV">El Salvador</option><option value="GQ">Equatorial Guinea</option><option value="ER">Eritrea</option><option value="EE">Estonia</option><option value="ET">Ethiopia</option><option value="FK">Falkland Islands (Malvinas)</option><option value="FO">Faroe Islands</option><option value="FJ">Fiji</option><option value="FI">Finland</option><option value="FR">France</option><option value="GF">French Guiana</option><option value="PF">French Polynesia</option><option value="TF">French Southern Territories</option><option value="GA">Gabon</option><option value="GM">Gambia</option><option value="GE">Georgia</option><option value="DE" selected="selected">Germany</option><option value="GH">Ghana</option><option value="GI">Gibraltar</option><option value="GR">Greece</option><option value="GL">Greenland</option><option value="GD">Grenada</option><option value="GP">Guadeloupe</option><option value="GU">Guam</option><option value="GT">Guatemala</option><option value="GG">Guernsey</option><option value="GN">Guinea</option><option value="GW">Guinea-Bissau</option><option value="GY">Guyana</option><option value="HT">Haiti</option><option value="HM">Heard Island &amp; Mcdonald Islands</option><option value="VA">Holy See (Vatican City State)</option><option value="HN">Honduras</option><option value="HK">Hong Kong</option><option value="HU">Hungary</option><option value="IS">Iceland</option><option value="IN">India</option><option value="ID">Indonesia</option><option value="IR">Iran, Islamic Republic Of</option><option value="IQ">Iraq</option><option value="IE">Ireland</option><option value="IM">Isle Of Man</option><option value="IL">Israel</option><option value="IT">Italy</option><option value="JM">Jamaica</option><option value="JP">Japan</option><option value="JE">Jersey</option><option value="JO">Jordan</option><option value="KZ">Kazakhstan</option><option value="KE">Kenya</option><option value="KI">Kiribati</option><option value="KR">Korea</option><option value="KW">Kuwait</option><option value="KG">Kyrgyzstan</option><option value="LA">Lao People's Democratic Republic</option><option value="LV">Latvia</option><option value="LB">Lebanon</option><option value="LS">Lesotho</option><option value="LR">Liberia</option><option value="LY">Libyan Arab Jamahiriya</option><option value="LI">Liechtenstein</option><option value="LT">Lithuania</option><option value="LU">Luxembourg</option><option value="MO">Macao</option><option value="MK">Macedonia</option><option value="MG">Madagascar</option><option value="MW">Malawi</option><option value="MY">Malaysia</option><option value="MV">Maldives</option><option value="ML">Mali</option><option value="MT">Malta</option><option value="MH">Marshall Islands</option><option value="MQ">Martinique</option><option value="MR">Mauritania</option><option value="MU">Mauritius</option><option value="YT">Mayotte</option><option value="MX">Mexico</option><option value="FM">Micronesia, Federated States Of</option><option value="MD">Moldova</option><option value="MC">Monaco</option><option value="MN">Mongolia</option><option value="ME">Montenegro</option><option value="MS">Montserrat</option><option value="MA">Morocco</option><option value="MZ">Mozambique</option><option value="MM">Myanmar</option><option value="NA">Namibia</option><option value="NR">Nauru</option><option value="NP">Nepal</option><option value="NL">Netherlands</option><option value="AN">Netherlands Antilles</option><option value="NC">New Caledonia</option><option value="NZ">New Zealand</option><option value="NI">Nicaragua</option><option value="NE">Niger</option><option value="NG">Nigeria</option><option value="NU">Niue</option><option value="NF">Norfolk Island</option><option value="MP">Northern Mariana Islands</option><option value="NO">Norway</option><option value="OM">Oman</option><option value="PK">Pakistan</option><option value="PW">Palau</option><option value="PS">Palestine, State of</option><option value="PA">Panama</option><option value="PG">Papua New Guinea</option><option value="PY">Paraguay</option><option value="PE">Peru</option><option value="PH">Philippines</option><option value="PN">Pitcairn</option><option value="PL">Poland</option><option value="PT">Portugal</option><option value="PR">Puerto Rico</option><option value="QA">Qatar</option><option value="RE">Reunion</option><option value="RO">Romania</option><option value="RU">Russian Federation</option><option value="RW">Rwanda</option><option value="BL">Saint Barthelemy</option><option value="SH">Saint Helena</option><option value="KN">Saint Kitts And Nevis</option><option value="LC">Saint Lucia</option><option value="MF">Saint Martin</option><option value="PM">Saint Pierre And Miquelon</option><option value="VC">Saint Vincent And Grenadines</option><option value="WS">Samoa</option><option value="SM">San Marino</option><option value="ST">Sao Tome And Principe</option><option value="SA">Saudi Arabia</option><option value="SN">Senegal</option><option value="RS">Serbia</option><option value="SC">Seychelles</option><option value="SL">Sierra Leone</option><option value="SG">Singapore</option><option value="SK">Slovakia</option><option value="SI">Slovenia</option><option value="SB">Solomon Islands</option><option value="SO">Somalia</option><option value="ZA">South Africa</option><option value="GS">South Georgia And Sandwich Isl.</option><option value="ES">Spain</option><option value="LK">Sri Lanka</option><option value="SD">Sudan</option><option value="SR">Suriname</option><option value="SJ">Svalbard And Jan Mayen</option><option value="SZ">Swaziland</option><option value="SE">Sweden</option><option value="CH">Switzerland</option><option value="SY">Syrian Arab Republic</option><option value="TW">Taiwan</option><option value="TJ">Tajikistan</option><option value="TZ">Tanzania</option><option value="TH">Thailand</option><option value="TL">Timor-Leste</option><option value="TG">Togo</option><option value="TK">Tokelau</option><option value="TO">Tonga</option><option value="TT">Trinidad And Tobago</option><option value="TN">Tunisia</option><option value="TR">Turkey</option><option value="TM">Turkmenistan</option><option value="TC">Turks And Caicos Islands</option><option value="TV">Tuvalu</option><option value="UG">Uganda</option><option value="UA">Ukraine</option><option value="AE">United Arab Emirates</option><option value="GB">United Kingdom</option><option value="US">United States</option><option value="UM">United States Outlying Islands</option><option value="UY">Uruguay</option><option value="UZ">Uzbekistan</option><option value="VU">Vanuatu</option><option value="VE">Venezuela</option><option value="VN">Viet Nam</option><option value="VG">Virgin Islands, British</option><option value="VI">Virgin Islands, U.S.</option><option value="WF">Wallis And Futuna</option><option value="EH">Western Sahara</option><option value="YE">Yemen</option><option value="ZM">Zambia</option><option value="ZW">Zimbabwe</option></select>
                </div>

            </div>
        </div>
        <h2>Technical Contact</h2>
         <div class="row" id="techcontrol">
             <div class="col-sm-6">            
                <div class="form-group">
                    <label for="techId" class="control-label">Existing Contact</label>
                    <select name="selectedId" id="techId" class="form-control contact-select" data-type="tech">
                         <option value="admin">Use Administrative Contact </option>
                         <option value="owner">Use Owner Contact </option>                         
                        {$contactList}
                    </select>
                </div>
             </div>
             <div class="col-sm-6" id="techupdate">            
                <div class="form-group">
                <label for="utech">Update from Administrative Contact</label><br/>
                <button  type="button" class="btn" id="utech" data-from="admin" data-to="tech">update</button>
                </div>
             </div>
        </div>
        <div class="row" id="tech">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="techTitle" class="control-label">Title</label>
                    <input required="required" type="text" name="techTitle" id="techTitle" value="{$techtitle}" class="form-control" />
                </div>
                <div class="form-group">                
                    <label for="techFirstName" class="control-label">{$LANG.clientareafirstname}</label>
                    <input required="required" type="text" name="techFirstName" id="techFirstName" value="{$techfirstname}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="techLastName" class="control-label">{$LANG.clientarealastname}</label>
                    <input required="required" type="text" name="techLastName" id="techLastName" value="{$techlastname}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="techCompanyName" class="control-label">{$LANG.clientareacompanyname}</label>
                    <input type="text" name="techCompanyName" id="techCompanyName" value="{$techcompanyname}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="techEmail" class="control-label">{$LANG.clientareaemail}</label>
                    <input required="required" type="email" name="techEmail" id="techEmail" value="{$techemail}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="techPhone" class="control-label">{$LANG.clientareaphonenumber}</label>
                    <input type="tel" name="phonenumbertech" id="techPhone" value="{$techphone}" class="form-control" />
                    <input type ="hidden" name="techPhonePrefix" id="techPhonePrefix"/>
                </div>
              
            </div>
            <div class="col-sm-6 col-xs-12 pull-right">

                <div class="form-group">
                    <label for="techAddress1" class="control-label">{$LANG.clientareaaddress1}</label>
                    <input required="required" type="text" name="techAddress1" id="techAddress1" value="{$techaddress1}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="techAddress2" class="control-label">{$LANG.clientareaaddress2}</label>
                    <input type="text" name="techAddress2" id="techAddress2" value="{$techaddress2}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="techCity" class="control-label">{$LANG.clientareacity}</label>
                    <input required="required" type="text" name="techCity" id="techCity" value="{$techcity}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="techState" class="control-label">{$LANG.clientareastate}</label>
                    <input required="required" type="text" name="techState" id="techState" value="{$techstate}" class="form-control" />
                </div>

                <div class="form-group">
                    <label for="techPostcode" class="control-label">{$LANG.clientareapostcode}</label>
                    <input required="required" type="text" name="techPostcode" id="techPostcode" value="{$techpostcode}" class="form-control" />
                </div>
                <div class="form-group">
                    <label class="control-label" for="techCountry">{$LANG.clientareacountry}</label>
                    <select name="techCountry" id="techCountry" class="form-control"  data-type="tech"><option value="AF">Afghanistan</option><option value="AX">Aland Islands</option><option value="AL">Albania</option><option value="DZ">Algeria</option><option value="AS">American Samoa</option><option value="AD">Andorra</option><option value="AO">Angola</option><option value="AI">Anguilla</option><option value="AQ">Antarctica</option><option value="AG">Antigua And Barbuda</option><option value="AR">Argentina</option><option value="AM">Armenia</option><option value="AW">Aruba</option><option value="AU">Australia</option><option value="AT">Austria</option><option value="AZ">Azerbaijan</option><option value="BS">Bahamas</option><option value="BH">Bahrain</option><option value="BD">Bangladesh</option><option value="BB">Barbados</option><option value="BY">Belarus</option><option value="BE">Belgium</option><option value="BZ">Belize</option><option value="BJ">Benin</option><option value="BM">Bermuda</option><option value="BT">Bhutan</option><option value="BO">Bolivia</option><option value="BA">Bosnia And Herzegovina</option><option value="BW">Botswana</option><option value="BV">Bouvet Island</option><option value="BR">Brazil</option><option value="IO">British Indian Ocean Territory</option><option value="BN">Brunei Darussalam</option><option value="BG">Bulgaria</option><option value="BF">Burkina Faso</option><option value="BI">Burundi</option><option value="KH">Cambodia</option><option value="CM">Cameroon</option><option value="CA">Canada</option><option value="CV">Cape Verde</option><option value="KY">Cayman Islands</option><option value="CF">Central African Republic</option><option value="TD">Chad</option><option value="CL">Chile</option><option value="CN">China</option><option value="CX">Christmas Island</option><option value="CC">Cocos (Keeling) Islands</option><option value="CO">Colombia</option><option value="KM">Comoros</option><option value="CG">Congo</option><option value="CD">Congo, Democratic Republic</option><option value="CK">Cook Islands</option><option value="CR">Costa Rica</option><option value="CI">Cote D'Ivoire</option><option value="HR">Croatia</option><option value="CU">Cuba</option><option value="CW">Curacao</option><option value="CY">Cyprus</option><option value="CZ">Czech Republic</option><option value="DK">Denmark</option><option value="DJ">Djibouti</option><option value="DM">Dominica</option><option value="DO">Dominican Republic</option><option value="EC">Ecuador</option><option value="EG">Egypt</option><option value="SV">El Salvador</option><option value="GQ">Equatorial Guinea</option><option value="ER">Eritrea</option><option value="EE">Estonia</option><option value="ET">Ethiopia</option><option value="FK">Falkland Islands (Malvinas)</option><option value="FO">Faroe Islands</option><option value="FJ">Fiji</option><option value="FI">Finland</option><option value="FR">France</option><option value="GF">French Guiana</option><option value="PF">French Polynesia</option><option value="TF">French Southern Territories</option><option value="GA">Gabon</option><option value="GM">Gambia</option><option value="GE">Georgia</option><option value="DE" selected="selected">Germany</option><option value="GH">Ghana</option><option value="GI">Gibraltar</option><option value="GR">Greece</option><option value="GL">Greenland</option><option value="GD">Grenada</option><option value="GP">Guadeloupe</option><option value="GU">Guam</option><option value="GT">Guatemala</option><option value="GG">Guernsey</option><option value="GN">Guinea</option><option value="GW">Guinea-Bissau</option><option value="GY">Guyana</option><option value="HT">Haiti</option><option value="HM">Heard Island &amp; Mcdonald Islands</option><option value="VA">Holy See (Vatican City State)</option><option value="HN">Honduras</option><option value="HK">Hong Kong</option><option value="HU">Hungary</option><option value="IS">Iceland</option><option value="IN">India</option><option value="ID">Indonesia</option><option value="IR">Iran, Islamic Republic Of</option><option value="IQ">Iraq</option><option value="IE">Ireland</option><option value="IM">Isle Of Man</option><option value="IL">Israel</option><option value="IT">Italy</option><option value="JM">Jamaica</option><option value="JP">Japan</option><option value="JE">Jersey</option><option value="JO">Jordan</option><option value="KZ">Kazakhstan</option><option value="KE">Kenya</option><option value="KI">Kiribati</option><option value="KR">Korea</option><option value="KW">Kuwait</option><option value="KG">Kyrgyzstan</option><option value="LA">Lao People's Democratic Republic</option><option value="LV">Latvia</option><option value="LB">Lebanon</option><option value="LS">Lesotho</option><option value="LR">Liberia</option><option value="LY">Libyan Arab Jamahiriya</option><option value="LI">Liechtenstein</option><option value="LT">Lithuania</option><option value="LU">Luxembourg</option><option value="MO">Macao</option><option value="MK">Macedonia</option><option value="MG">Madagascar</option><option value="MW">Malawi</option><option value="MY">Malaysia</option><option value="MV">Maldives</option><option value="ML">Mali</option><option value="MT">Malta</option><option value="MH">Marshall Islands</option><option value="MQ">Martinique</option><option value="MR">Mauritania</option><option value="MU">Mauritius</option><option value="YT">Mayotte</option><option value="MX">Mexico</option><option value="FM">Micronesia, Federated States Of</option><option value="MD">Moldova</option><option value="MC">Monaco</option><option value="MN">Mongolia</option><option value="ME">Montenegro</option><option value="MS">Montserrat</option><option value="MA">Morocco</option><option value="MZ">Mozambique</option><option value="MM">Myanmar</option><option value="NA">Namibia</option><option value="NR">Nauru</option><option value="NP">Nepal</option><option value="NL">Netherlands</option><option value="AN">Netherlands Antilles</option><option value="NC">New Caledonia</option><option value="NZ">New Zealand</option><option value="NI">Nicaragua</option><option value="NE">Niger</option><option value="NG">Nigeria</option><option value="NU">Niue</option><option value="NF">Norfolk Island</option><option value="MP">Northern Mariana Islands</option><option value="NO">Norway</option><option value="OM">Oman</option><option value="PK">Pakistan</option><option value="PW">Palau</option><option value="PS">Palestine, State of</option><option value="PA">Panama</option><option value="PG">Papua New Guinea</option><option value="PY">Paraguay</option><option value="PE">Peru</option><option value="PH">Philippines</option><option value="PN">Pitcairn</option><option value="PL">Poland</option><option value="PT">Portugal</option><option value="PR">Puerto Rico</option><option value="QA">Qatar</option><option value="RE">Reunion</option><option value="RO">Romania</option><option value="RU">Russian Federation</option><option value="RW">Rwanda</option><option value="BL">Saint Barthelemy</option><option value="SH">Saint Helena</option><option value="KN">Saint Kitts And Nevis</option><option value="LC">Saint Lucia</option><option value="MF">Saint Martin</option><option value="PM">Saint Pierre And Miquelon</option><option value="VC">Saint Vincent And Grenadines</option><option value="WS">Samoa</option><option value="SM">San Marino</option><option value="ST">Sao Tome And Principe</option><option value="SA">Saudi Arabia</option><option value="SN">Senegal</option><option value="RS">Serbia</option><option value="SC">Seychelles</option><option value="SL">Sierra Leone</option><option value="SG">Singapore</option><option value="SK">Slovakia</option><option value="SI">Slovenia</option><option value="SB">Solomon Islands</option><option value="SO">Somalia</option><option value="ZA">South Africa</option><option value="GS">South Georgia And Sandwich Isl.</option><option value="ES">Spain</option><option value="LK">Sri Lanka</option><option value="SD">Sudan</option><option value="SR">Suriname</option><option value="SJ">Svalbard And Jan Mayen</option><option value="SZ">Swaziland</option><option value="SE">Sweden</option><option value="CH">Switzerland</option><option value="SY">Syrian Arab Republic</option><option value="TW">Taiwan</option><option value="TJ">Tajikistan</option><option value="TZ">Tanzania</option><option value="TH">Thailand</option><option value="TL">Timor-Leste</option><option value="TG">Togo</option><option value="TK">Tokelau</option><option value="TO">Tonga</option><option value="TT">Trinidad And Tobago</option><option value="TN">Tunisia</option><option value="TR">Turkey</option><option value="TM">Turkmenistan</option><option value="TC">Turks And Caicos Islands</option><option value="TV">Tuvalu</option><option value="UG">Uganda</option><option value="UA">Ukraine</option><option value="AE">United Arab Emirates</option><option value="GB">United Kingdom</option><option value="US">United States</option><option value="UM">United States Outlying Islands</option><option value="UY">Uruguay</option><option value="UZ">Uzbekistan</option><option value="VU">Vanuatu</option><option value="VE">Venezuela</option><option value="VN">Viet Nam</option><option value="VG">Virgin Islands, British</option><option value="VI">Virgin Islands, U.S.</option><option value="WF">Wallis And Futuna</option><option value="EH">Western Sahara</option><option value="YE">Yemen</option><option value="ZM">Zambia</option><option value="ZW">Zimbabwe</option></select>
                </div>

            </div>
            <div class="form-group text-center">
            <input class="btn btn-primary" type="submit" id="saveContacts" name="save" value="{$LANG.clientareasavechanges}" />
            <input class="btn btn-default" type="reset" value="{$LANG.cancel}" />            
        </div>
        </div>        
</form>
