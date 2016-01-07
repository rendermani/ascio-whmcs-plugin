<?php
//require_once("../../../../init.php");

class AscioQueue {
	var $jobs;
	var $lastId;
	public function __construct() {
	}
	public function add($method, $request,$result) {
		$data = array(
			"order_id" => $result->order->OrderId,
			"last_id" => $this->lastId,
			"method" => $method,
			"request" => serialize($request)

		);
		$this->lastId = insert_query("tblasciojobs",$data);
		return $this->lastId;
	}
	public function updateOrderId($lastId,$orderId) {
		$result = update_query("tblasciojobs",array("order_id"=>$orderId),array("last_id"=>$lastId));
		return $lastId;
	}
	public function getNextRequest($lastId) {
		$result = select_query("tblasciojobs","id,method,request" ,array("last_id" => $lastId));
		$data = mysql_fetch_assoc($result);
		if(!$data) return false;
		$data["request"] = unserialize($data["request"]);
		return $data;
	}
	public function getLastId($lastOrderId) {
		return get_query_val("tblasciojobs","id",array("order_id" => $lastOrderId));			
	}

}
?>