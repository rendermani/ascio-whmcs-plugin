<?php
namespace ascio\whmcs\ssl;

class AscioException extends \Exception {
    private $templateCode;
    private $result;
    public function __construct ($message,$code=500,$templateCode=null,$result=null) {
        $this->result = $result;
        $this->templateCode = $templateCode;
        parent::__construct($message, $code, null);
    }
    public function getTemplateCode() {
        return $this->templateCode;
    }
    public function getResult() {
        return $this->result;
    }
}
class AscioUserException extends AscioException {
    public function __construct ($message,$code=500,$templateCode=null,$result=null) {
        parent::__construct($message, $code, $templateCode,$result);
    }
}
class AscioSystemException extends AscioException {
    public function __construct ($message,$code=500,$result=null) {
        parent::__construct($message, $code, null,$result);
    }
}
