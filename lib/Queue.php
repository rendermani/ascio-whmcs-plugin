<?php
use Illuminate\Database\Capsule\Manager as Capsule;

class AscioQueue {
	public $jobs;
	public $lastId;

	public function __construct() {
	}

	public function add($method, $request, $result) {
		$data = array(
			"order_id" => $result->order->OrderId,
			"last_id"  => $this->lastId,
			"method"   => $method,
			"request"  => serialize($request)
		);
		$this->lastId = Capsule::table('tblasciojobs')->insertGetId($data);
		return $this->lastId;
	}

	public function updateOrderId($lastId, $orderId) {
		Capsule::table('tblasciojobs')
			->where('last_id', $lastId)
			->update(array("order_id" => $orderId));
		return $lastId;
	}

	public function getNextRequest($lastId) {
		$data = Capsule::table('tblasciojobs')
			->where('last_id', $lastId)
			->select('id', 'method', 'request')
			->first();
		if (!$data) return false;
		$data = (array) $data;
		$data["request"] = unserialize($data["request"]);
		return $data;
	}

	public function getLastId($lastOrderId) {
		return Capsule::table('tblasciojobs')
			->where('order_id', $lastOrderId)
			->value('id');
	}
}
?>