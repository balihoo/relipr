<?php

class ListDTO
{
	const STATUS_NEW = 'New';
	const STATUS_SUBMITTED = 'Submitted';
	const STATUS_CANCELED = 'Canceled';
	const STATUS_FINALCOUNT = 'Final Count';
	const STATUS_LISTREADY = 'List Ready';

	public $listid,
		$count,
		$brandkey,
		$criteriaid,
		$medium,
		$requestedcount,
		$isestimate,
		$cost,
		$status,
		$callback,
		$filter,
		$columns,
		$links;

	// Set attribute defaults
	public function __construct() {
		$this->status = 'New';
		$this->filter = array();
		$this->columns = array();
	}

	public static function fromArray($arr) {
		$list = new ListDTO();
		$list->listid = $arr['listid'];
		$list->count = $arr['count'];
		$list->brandkey = $arr['brandkey'];
		$list->criteriaid = $arr['criteriaid'];
		$list->medium = $arr['medium'];
		$list->requestedcount = $arr['requestedcount'];
		$list->isestimate = $arr['isestimate'];
		$list->cost = $arr['cost'];
		$list->status = $arr['status'];
		$list->callback = $arr['callback'];
		$list->setFilter($arr['filter']);
		$list->setColumns($arr['columns']);
		$list->updateLinks();

		return $list;
	}

	private function getBaseUri() {
		// Obviously this won't work for https, but this is just an example
		$base = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		// Make sure there is a slash on the end
		if(!preg_match('/\/$/', $base))
			$base .= "/";
		// Make sure there is a listid at the end
		if(!preg_match('/list\/[0-9]+\//', $base))
			$base .= $this->listid . "/";

		return preg_replace('/\/$/', '', $base);
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

	public function setFilter($filter) {
		$this->filter = self::decodeFilter($filter);
	}

	public function encodeFilter($filter) {
		return json_encode($filter);
	}

	public static function decodeFilter($filter) {
		$filter = json_decode($filter);
		if($filter === NULL && json_last_error() != JSON_ERROR_NONE)
			throw new Exception("Unable to decode 'filter': " . self::getJsonErrorMessage(json_last_error()), 400);
		return $filter;
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

	public static function toArray($arr) {
		$arr = array();

		$arr['listid'] = $this->listid;
		$arr['count'] = $this->count;
		$arr['brandkey'] = $this->brandkey;
		$arr['criteriaid'] = $this->criteriaid;
		$arr['medium'] = $this->medium;
		$arr['requestedcount'] = $this->requestedcount;
		$arr['isestimate'] = $this->isestimate;
		$arr['cost'] = $this->cost;
		$arr['status'] = $this->status;
		$arr['callback'] = $this->callback;
		$arr['filter'] = json_encode($this->filter);
		$arr['columns'] = self::encodeColumns($this->columns);
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

