<?php

class ListDTO
{
	const STATUS_NEW = 'New';
	const STATUS_SUBMITTED = 'Submitted';
	const STATUS_CANCELED = 'Canceled';
	const STATUS_FINALCOUNT = 'Final Count';
	const STATUS_LISTREADY = 'List Ready';

	public $listid,
		$status,
		$medium,
		$brandkey,
		$criteriaid,
		$requestedcount,
		$count,
		$isestimate,
		$cost,
		$callback,
		$filter,
		$orderinfo,
		$affiliateinfo,
		$creativeinfo,
		$columns,
		$links;

	private $canceled, $cancelnotified, $counted, $countnotified, $readied, $readynotified;

	// Set attribute defaults
	public function __construct() {
		$this->status = 'New';
		$this->filter = array();
		$this->columns = array();
	}

	public static function fromArray($arr, $complete = true) {
		$list = new ListDTO();
		$list->listid = $arr['listid'];
		$list->count = $arr['count'];
		$list->brandkey = $arr['brandkey'];
		$list->criteriaid = $arr['criteriaid'];
		$list->medium = $arr['medium'];
		$list->requestedcount = $arr['requestedcount'];
		$list->isestimate = $arr['isestimate'] == 1;
		$list->cost = $arr['cost'];
		$list->status = $arr['status'];
		$list->callback = $arr['callback'];
		$list->setFilter($arr['filter']);
		$list->setCreativeInfo($arr['creativeinfo']);
		$list->setOrderInfo($arr['orderinfo']);
		$list->setAffiliateInfo($arr['affiliateinfo']);
		$list->setColumns($arr['columns']);
		$list->updateLinks();

		if($complete) {
			$list->canceled = $arr['canceled'];
			$list->cancelnotified = $arr['cancelnotified'];
			$list->counted = $arr['counted'];
			$list->countnotified = $arr['countnotified'];
			$list->readied = $arr['readied'];
			$list->readynotified = $arr['readynotified'];
		}

		return $list;
	}

	public function getCanceled() { return $this->canceled; }
	public function getCancelNotified() { return $this->cancelnotified; }
	public function getCounted() { return $this->counted; }
	public function getCountNotified() { return $this->countnotified; }
	public function getReadied() { return $this->readied; }
	public function getReadyNotified() { return $this->readynotified; }


	private function getBaseUri() {
		// Obviously this won't work for https or for sites hosted on a non-root path
		//  but this is just an example
		return "http://$_SERVER[HTTP_HOST]/medium/{$this->medium}/brand/{$this->brandkey}/criteria/{$this->criteriaid}/list/{$this->listid}";
	}

	public function updateLinks() {
		$this->links = array();
		$base = $this->getBaseUri();

		// Add the self link to every list
		$this->links['self'] = "$base";

		// Add state specific links
		switch($this->status) {
			case ListDTO::STATUS_NEW:
				$this->links['cancel'] = "$base/cancel";
				$this->links['submit'] = "$base/submit";
				break;
			case ListDTO::STATUS_SUBMITTED:
				$this->links['cancel'] = "$base/cancel";
				break;
			case ListDTO::STATUS_LISTREADY:
				$this->links['download'] = "$base/download";
				break;
		}
	}

	public function setFilter($filter) { $this->filter = self::decodeObject($filter); }
	public function setAffiliateInfo($info) { $this->affiliateinfo = self::decodeObject($info); }
	public function setCreativeInfo($info) { $this->creativeinfo = self::decodeObject($info); }
	public function setOrderInfo($info) { $this->orderinfo = self::decodeObject($info); }

	public function encodeObject($object) {
		return json_encode($object);
	}

	public static function decodeObject($string) {
		$object = json_decode($string);
		if($object === NULL && json_last_error() != JSON_ERROR_NONE)
			throw new Exception("Unable to decode JSON string: " . self::getJsonErrorMessage(json_last_error()), 400);
		return $object;
	}

	public function setColumns($columns) {
		$this->columns = self::decodeColumns($columns);
	}

	public static function decodeColumns($columns) {
		$result = array();

		// Split the comma delimited list of columns into a nice array
		$cols = preg_split('/,/', $columns);
		foreach($cols as $col) {
			if(trim($col != ''))
				$result[] = $col;
		}
		return $result;
	}

	public static function encodeColumns($columns) {
		return implode(',', $columns);
	}

	private static function getJsonErrorMessage($code) {
		switch ($code) {
			case JSON_ERROR_STATE_MISMATCH:
				return "Invalid or malformed JSON";
			case JSON_ERROR_CTRL_CHAR:
				return "Control character error, possibly incorrectly encoded JSON";
			case JSON_ERROR_SYNTAX:
				return "JSON syntax error";
			case JSON_ERROR_UTF8:
				return "Malformed UTF-8 characters in JSON";
			default:
				return "Unknown JSON parse error";
		}
	}

}

