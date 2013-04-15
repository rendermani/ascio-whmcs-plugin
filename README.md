#ascio-whmcs-plugin

##First version of the WHMCS domain registration plugin for the Ascio Webservice. 

- change to your modules/registrars directory
- get plugin:  git clone https://github.com/rendermani/ascio-whmcs-plugin.git ascio
- copy it to modules/registrars
- add your credentials and your API Url to callbacks.php
- activate the ascio plugin in the WHMCS settings and configure it

##Known issues: 

- After submitting an order status is active instead of pending
- Not all TLDs and fields are configured
- Changing contacts not implemented yet. 
