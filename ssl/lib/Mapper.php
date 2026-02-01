<?php
namespace ascio\common\mapper;

class Mappers {

}

class Mapper {
    protected $data;
    private $inputOuputMap;
    /**
     * @var Fields $fields
     */
    protected $fields;  
    public function __construct($data)
    {   
        $this->data = $data;
    }
    public function setFields (Fields $fields) {
        $this->fields = $fields; 
    }
    protected function serialize($fields) : object {        
        if(!$this->data) {
            return  (array) $existingData;
        }
        $data = [];
        foreach($this->data as $key => $value) {
            $newKey = isset($fields[$key]) ? $fields[$key] : $key;
            $data[$newKey] = $value;
        }
        return (object) $data;
    }
    protected function add($existingData,$data) {
        if(!$existingData) {
            return $data;
        }
        if(is_object($existingData)) {
            foreach($data as $key => $value) {
                $newKey = isset($fields[$key])  ? $fields[$key] : $key;
                $existingData->$newKey = $value;
            }
        } if(is_array($existingData)) {
            array_merge((array) $data, $existingData);
        }
        return (array) $existingData;
    }

}
class Fields {
    protected $fields;
    protected $data;
    public function __construct($fields) {
        $this->fields = $fields;
        foreach($fields as $key => $value) {
            $this->$key = $value;
        }
    }
    public function getInput() {
        return array_flip($this->fields); 
    }    
    public function getOuput() {
        return $this->fields;
    }    
    
}


