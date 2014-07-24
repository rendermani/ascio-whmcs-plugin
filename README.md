#ascio-whmcs-plugin

##First version of the WHMCS domain registration plugin for the Ascio Webservice. 

###Requirements
- php 5+
- PHP SoapClient module installed
- The webserver needs write permissions for the file ascio-session.txt

### Commandline install

- change to your modules/registrars directory
- get plugin:  git clone https://github.com/rendermani/ascio-whmcs-plugin.git ascio

### FTP install (alternative)

- Download ZIP - https://github.com/rendermani/ascio-whmcs-plugin/archive/master.zip
- unpack the php-files to modules/registrars/ascio

### Setting Directory Permissions

- the directory sessioncache must be writeable for the webserver

###Configuring the plugin

- activate the ascio plugin in the WHMCS settings and configure it
- If you are in testmode, you need to add the testing-credentials


##Known issues: 

- Not all TLDs and fields are configured. But you can add your own TLD-Definition in ascio/tlds

##Ascio DNS

- AscioDNS is added. WHMCS has only a minimal DNS-management, but there are plugins. Please contact your account-manager for an AscioDNS account.
