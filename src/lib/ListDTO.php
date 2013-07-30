<?php

class ListDTO
{
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
		$cost,
		$callback,
		$filter,
		$orderinfo,
		$affiliateinfo,
		$creativeinfo,
		$columns,
		$links;

	private $canceled, $cancelnotified, $counted, $countnotified, $readied, $readynotified;
	private $baseuri;

	// Set attribute defaults
	public function __construct() {
		$this->status = 'Submitted';
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

		if(array_key_exists('baseuri', $arr))
			$list->baseuri = $arr['baseuri'];
		else {
			// Obviously this won't work for https or for sites hosted on a non-root path
			//  but this is just an example
			$list->baseuri = "http://$_SERVER[HTTP_HOST]/medium/{$list->medium}/brand/{$list->brandkey}/criteria/{$list->criteriaid}/list";
		}

		return $list;
	}

	public function getCanceled() { return $this->canceled; }
	public function getCancelNotified() { return $this->cancelnotified; }
	public function getCounted() { return $this->counted; }
	public function getCountNotified() { return $this->countnotified; }
	public function getReadied() { return $this->readied; }
	public function getReadyNotified() { return $this->readynotified; }
	public function getBaseuri() { return $this->baseuri; }

	public function updateLinks() {
		$this->links = array();

		// Add the self link to every list
		$this->links['self'] = "{$this->baseuri}/{$this->listid}";

		// Add state specific links
		switch($this->status) {
			case ListDTO::STATUS_SUBMITTED:
				$this->links['cancel'] = "{$this->baseuri}/{$this->listid}/cancel";
				break;
			case ListDTO::STATUS_LISTREADY:
				$this->links['download'] = "{$this->baseuri}/{$this->listid}/download";
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
			throw new Tonic\BadRequestException("Unable to decode JSON string: " . self::getJsonErrorMessage(json_last_error()));
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

