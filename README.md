#ascio-whmcs-plugin

##First version of the WHMCS domain registration plugin for the Ascio Webservice. 

###Commandline install

- change to your modules/registrars directory
- get plugin:  git clone https://github.com/rendermani/ascio-whmcs-plugin.git ascio
- copy it to modules/registrars

###FTP install
- Download ZIP - https://github.com/rendermani/ascio-whmcs-plugin/archive/master.zip
- unpack the php-files to modules/registrars/ascio

###Configuring the plugin

- add your Ascio and WHMCS credentials and your API Url to callbacks.php
- activate the ascio plugin in the WHMCS settings and configure it
- If you are in testmode, you also need to add the testing-credentials

##Known issues: 

- After submitting an order status is active instead of pending
- Not all TLDs and fields are configured
- Changing contacts not implemented yet. 
