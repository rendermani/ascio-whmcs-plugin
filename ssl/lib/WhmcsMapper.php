<?php
namespace ascio\whmcs\ssl;
require_once("Mapper.php");
use ascio\common\mapper as map;

class ParamFields extends map\Fields {
    public function __construct() {
        $this->fields =   [
            "configoption1" => "account",
            "configoption2" => "password",
            "configoption3" => "testmode",
            "serviceid"     => "serviceId",
            "userid"        =>  "userId"
        ];
    }
}
class ContactFields extends map\Fields {
    public function __construct() {
        $this->fields =   [ ];
    }
}

class Mapper extends map\Mappers {
    public static function whmcs() : WhmcsMapper {
        return new WhmcsMapper(null);
    }
}
class WhmcsMapper extends map\Mapper {
    public function parameters($data) : WhmcsParameterMapper {
        return new WhmcsParameterMapper($data);
    }
    public function contacts($data) : WhmcsContactMapper {
        return new WhmcsContactMapper($data);
    }
}
class WhmcsParameterMapper extends map\Mapper {
    public $account;
    public $password;
    public $testmode;
    public $serviceId;
    public $userId;

    public function __construct($data)
    {   
        parent::__construct($data);
        $this->setFields(new ParamFields());

    }
    public function ascio($existingData = null) {
        $fields = $this->fields->getOuput();
        $data = $this->serialize($fields,$existingData);
        if($data->testmode=="on") {
            $data->testmode = true;
        } else {
            $data->testmode = false;
        } 
        $this->add($existingData,$data);
        $this->add($this,$data);
        return $data;              
    }    
}
class WhmcsContactMapper extends map\Mapper {

    public function __construct($contact)
    {   
        parent::__construct($contact);
        $this->setFields(new ContactFields());

    }
    public function ascio() {               
    }    
}


class TestClass {
    function __construct() 
    {
        $params = [
            "configoption1" => "my account",
            "configoption2" => "my password",
            "configoption3" => "on",
            "serviceid"     => "my serviceId",
            "userid"        => "my userId",
        ];
        $data = Mapper::whmcs()->parameters($params)->ascio($this);
        var_dump($this);
    }
}


$params = [
    "configoption1" => "my account",
    "configoption2" => "my password",
    "configoption3" => "on",
    "serviceid"     => "my serviceId",
    "userid"        => "my userId",
];
$e = (object) ["a" => "b"];

$data = Mapper::whmcs()->parameters($params)->ascio($e);
var_dump($data);
var_dump($e);

$test = new TestClass();


/**


$params = [
    "configoption1" => $this->account,
    "configoption2" => $this->password,
    "configoption3" => $this->testmode ? "on" : "off",
    "serviceid"     => $this->serviceId,
    "userid"        => $this->userId
];
 */