<?php

namespace ascio;

class al extends Request {
    protected function mapToRegistrant($params) {
        $contact = parent::mapToRegistrant($params);
        if (isset($params["additionalfields"]["Registrant Number"])) {
            $contact["RegistrantNumber"] = $params["additionalfields"]["Registrant Number"];
        }
        return $contact;
    }
}
?>
