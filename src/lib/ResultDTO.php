<?php

class ResultDTO
{
	const TYPE_DELIVERED = 'delivered';
	const TYPE_BLOCKED = 'blocked';
	const TYPE_SOFTBOUNCE = 'softbounce';
	const TYPE_HARDBOUNCE = 'hardbounce';
	const TYPE_OPEN = 'open';
	const TYPE_CLICK = 'click';
	const TYPE_UNSUBSCRIBE = 'unsubscribe';
	const TYPE_SPAMREPORT = 'spamreport';

	public $type,
		$recipientid,
		$timestamp,
		$detail;

	public static function fromArray($arr) {
		$result = new ResultDTO();
		foreach(array('type', 'recipientid', 'timestamp', 'detail') as $pname) {
			if(!isset($arr->$pname))
				throw new Tonic\Exception("Missing result property '$pname'");
			$result->$pname = $arr->$pname;
		}
		$result->validate();
		return $result;
	}

	protected function validate() {
		// Validate type
		if($this->type != self::TYPE_DELIVERED &&
				$this->type != self::TYPE_BLOCKED &&
				$this->type != self::TYPE_SOFTBOUNCE &&
				$this->type != self::TYPE_HARDBOUNCE &&
				$this->type != self::TYPE_OPEN &&
				$this->type != self::TYPE_CLICK &&
				$this->type != self::TYPE_UNSUBSCRIBE &&
				$this->type != self::TYPE_SPAMREPORT) {
			throw new Tonic\Exception("Unrecognized result type '{$this->type}'");
		}

		// Validate timestamp
		if(!is_int($this->timestamp))
			throw new Tonic\Exception("Unable to parse timestamp '{$this->timestamp}'");
	}

}

