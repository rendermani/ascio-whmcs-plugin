#ascio-whmcs-plugin

##First version of the WHMCS domain registration plugin for the Ascio Webservice. 

###Requirements
- php 5+
- PHP SoapClient module installed
- php5-memcached, memcached (if you can't install it ask me for a workaround)

###Commandline install

- change to your modules/registrars directory
- get plugin:  git clone https://github.com/rendermani/ascio-whmcs-plugin.git ascio

###FTP install
- Download ZIP - https://github.com/rendermani/ascio-whmcs-plugin/archive/master.zip
- unpack the php-files to modules/registrars/ascio

###Configuring the plugin

- activate the ascio plugin in the WHMCS settings and configure it
- If you are in testmode, you also need to add the testing-credentials
- copy config.php.dist to config.php
- add your credentials to config.php

##Known issues: 

- Not all TLDs and fields are configured
