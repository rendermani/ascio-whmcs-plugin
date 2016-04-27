#ascio-whmcs-plugin

##WHMCS domain registration plugin for the Ascio Webservice. 

Please visit this link for details: http://aws.ascio.info/whmcs.html

###Requirements
- php 5.3+
- PHP SoapClient module installed

### Commandline install

- change to your modules/registrars directory
- get plugin:  git clone https://github.com/rendermani/ascio-whmcs-plugin.git ascio
- cd ascio
- run install.php

### FTP install (alternative)

- Download ZIP - https://github.com/rendermani/ascio-whmcs-plugin/archive/master.zip
- unpack the php-files to modules/registrars/ascio
- cd ascio
- run install.php

###Configuring the plugin

- activate the ascio plugin in the WHMCS settings and configure it
- If you are in testmode, you need to add the testing-credentials

##Ascio DNS

- AscioDNS is added. WHMCS has only a minimal DNS-management, but there are plugins. Please contact your account-manager for an AscioDNS account.

