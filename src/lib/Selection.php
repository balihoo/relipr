<?php

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
		$criteriaObject = CriteriaBuilder::getCriteriaObject($medium, $brandkey, $criteriaid);
		return "select count(*) " . $criteriaObject->buildQuery($this);
	}

}

