<?php

//require_once("DB.php");

class Selection
{
	public $jsonText, $data;

	public function __construct($jsonText) {

		$this->jsonText = $jsonText;
		$this->data = json_decode($jsonText);
		if($this->data === NULL && json_last_error() != JSON_ERROR_NONE)
			throw new Exception("Unable to parse 'selections': " . $this->getJsonErrorMessage(json_last_error()), 400);
	}

	private function getJsonErrorMessage($code) {
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

	public function getCountQuery($medium, $brandkey, $criteriaid) {
		// Wow, this is an ugly way to do this - good thing it is a PoC!
		switch($criteriaid) {
			case 'osc101': return $this->getOscCountQuery($brandkey);
			default:
				throw new Exception("Don't recognize criteria with id '$criteriaid'");
		}
	}

	private function getOscCountQuery($brandkey) {
		$sql = "select count(*) from recipient where";
		$sql .= " brandkey = '$brandkey'";

		if(isset($this->data->affiliates) and count($this->data->affiliates) > 0) {
			$sql .= " and affiliateid in (";
			foreach($this->data->affiliates as $affiliateid) {
				$sql .= "$affiliateid,";
			}
			$sql .= "-1)";
		}

		if(isset($this->data->visitedrange) and count($this->data->visitedrange) == 2) {
			$r1 = $this->data->visitedrange[0];
			$r2 = $this->data->visitedrange[1];
			if($r1)
				$sql .= " and lastvisit >= '$r1'";
			if($r2)
				$sql .= " and lastvisit <= '$r2'";
		}

		if(isset($this->data->vehicle) and count($this->data->vehicle) > 0) {
			$sql .= " and make in (";
			foreach($this->data->vehicle as $make) {
				$sql .= "'$make',";
			}
			$sql .= "'')";
		}

		if(isset($this->data->mileage) and count($this->data->mileage) == 2) {
			$m1 = $this->data->mileage[0];
			$m2 = $this->data->mileage[1];
			if($m1)
				$sql .= " and mileage >= $m1";
			if($m2)
				$sql .= " and mileage <= $m2";
		}

		if(isset($this->data->custloyalty) and count($this->data->custloyalty) > 0) {
			$sql .= " and loyaltyprogram in (";
			foreach($this->data->custloyalty as $prog) {
				$sql .= "'$prog',";
			}
			$sql .= "'')";
		}

		return "$sql;";
	}
}

