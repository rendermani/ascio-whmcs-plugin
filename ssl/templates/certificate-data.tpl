<style>
    .Failed { color:#9e0600}
    .Completed { color: #516402}
    .sans label{ margin-top:5px;}
    .sanlabel {
        text-align:right;
    }
    .sannr {
        text-align: right;
    }
    #mxverification { margin-top:22px;padding-left:19px}
</style>
<h1>SSL Certificate Data</h1>
<script type="text/javascript" src="/whmcs/modules/servers/asciossl/asciossl.js"></script>
<form role="form" method="post" id="sslForm" action="{$smarty.server.PHP_SELF}?action=productdetails&step=contacts">
   <input type="hidden" name="submit" value="true" />
   <input type="hidden" name="step" value="contacts">
   <input type="hidden" id="domainroot" name="domainroot" value="{$domainroot}">
    <input type="hidden" name="id" value="{$serviceid}">
    <input type="hidden" name="commonName" id="commonName"/>
        <h2>CSR</h2>
        {if $errors}                    
            <div class="alert alert-danger" role="alert">
                <p><b>Your last submission failed, please retry:</b></p>
                {foreach from=$errors  item=error}
                    <p>{$error}</p>
                {/foreach}
            </div>            
        {/if}

        <div class="row">
             <div class="col-sm-12">            
                <div class="form-group">
                    <label for="csr" class="control-label">Please paste your certificate signing request here</label>
                    <textarea style="height:400px" name="csr" id="csr" class="form-control" >{$csr}</textarea>                    
                </div>               
             </div>
        </div>
        <div class="row">
             <div class="col-sm-10"><p><button id ="csrbutton" type="button" class="btn  btn-alert alert alert-success">Check CSR</button></p> </div>
             <div class="col-sm-2"><span id="csrvalid"></span></div>
        </div>
        <div id="certdetails"  style="display:none">
            <div class="row">
                <h2 id="domainname"></h2>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="verificationType" class="control-label">Verification Type</label>
                        <select required="required" type="text" name="verificationType" id="verificationType" value="{$verification_type}" class="form-control" >
                            <option value="Email">E-Mail Verification</option>
                            <option value="Dns">DNS Verification</option>
                            <option value="File">File Verification</option>
                        </select>
                    </div>                                        
                </div>
                <div class="col-sm-8 col-xs-12 pull-right">
                    <div class="form-group" id="approvalEmailContainer">
                        <label for="approvalEmail" class="control-label">Approval E-Mail</label>
                        <select {if $verification_type eq "Email"}required="true"{/if} type="text" name="approvalEmail" id="approvalEmail" value="{$approval_email}" class="form-control" ></select>
                    </div>
                </div>           
            </div>
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="webserver" class="control-label">Webserver</label>
                            <select required="required" type="text" name="webserver" id="webserver" value="{$webserver}" class="form-control" >
                        
                                <option value=""></option>
                                <option value="ApacheSsl">ApacheSsl</option>
                                <option value="ApacheRaven">ApacheRaven</option>
                                <option value="ApacheSsleay">ApacheSsleay</option>
                                <option value="C2net">C2net</option>
                                <option value="IbmHttp">IbmHttp</option>
                                <option value="Iplanet">Iplanet</option>
                                <option value="DominoGo4625">DominoGo4625</option>
                                <option value="DominoGo4626">DominoGo4626</option>
                                <option value="Domino">Domino</option>
                                <option value="Iis4">Iis4</option>
                                <option value="Iis5">Iis5</option>
                                <option value="Netscape">Netscape</option>
                                <option value="Zeusv3">Zeusv3</option>
                                <option value="Other">Other</option>
                                <option value="ApacheOpenSsl">ApacheOpenSsl</option>
                                <option value="Apache2">Apache2</option>
                                <option value="ApacheApacheSsl">ApacheApacheSsl</option>
                                <option value="CobaltSeries">CobaltSeries</option>
                                <option value="Cpanel">Cpanel</option>
                                <option value="Ensim">Ensim</option>
                                <option value="Hsphere">Hsphere</option>
                                <option value="IpSwitch">IpSwitch</option>
                                <option value="Plesk">Plesk</option>
                                <option value="Tomcat">Tomcat</option>
                                <option value="WebLogic">WebLogic</option>
                                <option value="WebSite">WebSite</option>
                                <option value="WebStar">WebStar</option>
                                <option value="Iis">Iis</option>


                        </select>
                    </div>                   
                   
                </div> 
                 <div class="col-sm-8" id="mxverification">
                    <div class="form-group" id="mxverification"></diV>
                 </div>
            </div>
            {$sans}
            <div class="row">
                <div class="form-group text-center">
                    <input class="btn btn-primary" type="submit" id="saveContacts" name="save" value="{$LANG.clientareasavechanges}" />
                    <input class="btn btn-default" type="reset" value="{$LANG.cancel}" />            
                </div>
            
            </div>


        </div>

    </div>        
</form>
