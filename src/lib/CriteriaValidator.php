<?php

class CriteriaValidationException extends Exception{
	protected $errors;
	protected $warnings;
	protected $json;
	
	public function __construct(CriteriaValidator $val, $json) {
		$this->json = $json;
		$this->errors = $val->getErrors();
		$this->warnings = $val->getWarnings();
		$this->message = "Criteria validation failed with " . 
			count($this->errors) . " errors and " .
			count($this->warnings) . " warnings";
	}

	public function getJson() {
		return $this->json;
	}

	public function getErrors() {
		return $this->errors;
	}
	
	public function getWarnings() {
		return $this->warnings;
	}
}

/* Validates the criteria object returned by the list provider api
	This code sucks and is going to be a pain to maintain.
	Should we consider using http://json-schema.org/ ?
*/
class CriteriaValidator {
	// Keep track of errors and warnings
	private $errors, $warnings, $criterionids;

	// Validate a single criteria specification object
	public function validateJSON($criteriaJson) {
		// Reset all of the state variables
		$this->errors = array();
		$this->warnings = array();
		$this->criterionids = array();

		// Try to turn the JSON string into an object
		$crit = $this->decode($criteriaJson);
		if($crit) {
			$this->validateCriteria($crit);
		} else {
			$this->warn("Unable to attempt validation because JSON decode failed");
		}

		if(count($this->errors) > 0)
			throw new CriteriaValidationException($this, json_encode($crit));

		return $crit;
	}

	// Turn a JSON string into a PHP object and report any decode errors that may occur
	private function decode($criteriaJson) {
		$object = json_decode($criteriaJson);
		if($object === NULL && json_last_error() != JSON_ERROR_NONE)
			$this->error("Unable to decode JSON: " . $this->getJsonErrorMessage(json_last_error()));
		else
			return $object;
	}

	// Validate the top level criteria object
	public function validateCriteria($crit) {
		// Must have criteriaid
		$identifier = "Top level criteria";
		if(!isset($crit->criteriaid)) {
			$this->error("Criteria specification is missing property 'criteriaid'");
		} else if(!is_string($crit->criteriaid)) {
			$this->error("Expected property 'criteriaid' to be a string, but got '{$crit->criteriaid}'");
		} else {
			$identifier = "Top level object: {$crit->criteriaid}";
		}

		// Must have a title
		if(!isset($crit->title))
			$this->error("Criteria specification is missing property 'title'");
		else if(!is_string($crit->title))
			$this->error("Expected property 'title' to be a string, but got '{$crit->title}'");

		// Nice to have a description (very, very nice!)
		if(!isset($crit->description))
			$this->warn("Criteria specification does not have a 'description' property.");
		else if(!is_string($crit->description))
			$this->error("Expected property 'description' to be a string, but got '{$crit->description}'");

		// Need to have a columns property
		if(isset($crit->columns)) {
			$this->validateColumns($crit->columns);
		} else {
			$this->error("Criteria specification is missing property 'columns'");
		}

		// Need to a criteria property
		if(isset($crit->criteria)) {
			$this->validateCriteriaCollection($crit->criteria, $identifier);
		} else {
			$this->error("Criteria specification is missing property 'criteria'");
		}

		if(count($this->errors) > 0)
			throw new CriteriaValidationException($this, json_encode($crit));
	}

	// The columns property is a 1d, key-value pair
	// Keys and values should all be strings
	private function validateColumns($columns) {
		if(is_object($columns)) {
			foreach($columns as $key => $val) {
				if(!is_string($val)) {
					$display = is_object($val) ? "Object" : "$val";
					$this->error("Expected a string description for column '$key', but got '$display'");
				}
			}
		} else {
			$this->error("Expected property 'columns' to be an object, but it was '$columns'");
		}
	}

	// Validate an array of criteria objects
	private function validateCriteriaCollection($crits, $identifier) {
		if(is_array($crits)) {
			$index = 0;
			foreach($crits as $criterion) {
				$this->validateCriterion($index, $criterion, $identifier);
				$index++;
			}
		} else {
			$this->error("Expected property 'criteria' of '$identifier' to be an array of critierion objects");
		}
	}

	// Validate the generic fields of a criterion object
	private function validateCriterion($index, $criterion, $identifier) {
		$criterionid = "index #$index of '$identifier'";

		// A criterion is an object
		if(!is_object($criterion)) {
			$this->error("Criterion $criterionid is not an object");
			return;
		}

		// A criterion must always have a criterionid
		if(!isset($criterion->criterionid)) {
			if(isset($criterion->type) && $criterion->type == 'section') {
				$criterionid = "section '{$criterion->title}' of '$identifier'";
			} else {
				$this->error("Criterion $criterionid is missing the 'criterionid' property");
			}
		} else {
			$criterionid = $criterion->criterionid;
			$this->registerCriterionid($criterionid);
		}

		// title is optional, but very nice to have and should always be a string
		if(!isset($criterion->title))
			$this->warn("Criterion '$criterionid' does not have a title");
		else if(!is_string($criterion->title))
			$this->error("Criterion '$criterionid' has a non-string title");

		// description is optional, but should always be a string
		if(isset($criterion->description) && !is_string($criterion->description))
			$this->error("Criterion '$criterionid' has a non-string 'description' property");

		// helptext is optional, but should always be a string
		if(isset($criterion->helptext) && !is_string($criterion->helptext))
			$this->error("Criterion '$criterionid' has a non-string 'helptext' property");

		// editable is optional, but should always be a boolean
		if(isset($criterion->editable) && !is_bool($criterion->editable))
			$this->error("Criterion '$criterionid' has a non-boolean 'editable' property");

		// required is optional, but should always be a boolean
		if(isset($criterion->required) && !is_bool($criterion->required))
			$this->error("Criterion '$criterionid' has a non-boolean 'required' property");

		// hidden is optional, but should always be a boolean
		if(isset($criterion->hidden) && !is_bool($criterion->hidden))
			$this->error("Criterion '$criterionid' has a non-boolean 'hidden' property");

		// A criterion must always have a type
		if(!isset($criterion->type))
			$this->error("Criterion $criterionid does not have a 'type' property");
		else
			$this->validateCriterionType($criterion->type, $criterion, $criterionid);
	}

	// Each criterion object has its own nuances, dispatch to the appropriate validator
	private function validateCriterionType($type, $criterion, $criterionid) {
		switch($type) {
			case 'section': $this->validateSection($criterion, $criterionid); break;
			case 'selectsingle': $this->validateSelectSingle($criterion, $criterionid); break;
			case 'nestedsingle': $this->validateNestedSingle($criterion, $criterionid); break;
			case 'selectmultiple': $this->validateSelectMultiple($criterion, $criterionid); break;
			case 'selectmultiplesubitem': $this->validateSelectMultipleSubItem($criterion, $criterionid); break;
			case 'numberrange': $this->validateNumberRange($criterion, $criterionid); break;
			case 'text': $this->validateText($criterion, $criterionid); break;
			case 'date': $this->validateDate($criterion, $criterionid); break;
			case 'daterange': $this->validateDateRange($criterion, $criterionid); break;
			case 'number': $this->validateNumber($criterion, $criterionid); break;
			default:
				$this->error("Unrecognized criterion type '$type' for criterion $criterionid");
		}
	}

	// Validate a section criterion and its sub-criteria
	private function validateSection($criterion, $criterionid) {
		// A section has to have an array of criteria
		if(!isset($criterion->criteria)) {
			$this->error("'section' is missing property 'criteria'");
		} else {
			$this->validateCriteriaCollection($criterion->criteria, $criterionid);
		}
	}

	private function validateSelectSingle($criterion, $criterionid) {
		// Make sure that the options exist and are valid
		$keys = $this->validateOptions($criterion, $criterionid);

		// Makes sure that the default value is a valid one
		if(isset($criterion->defaultvalue)) {
			if(!in_array($criterion->defaultvalue, $keys))
				$this->error("Criterion '$criterionid' indicates a default value of '$criterion->defaultvalue', but it isn't one of the available options");
		}
	}

	private function validateNestedSingle($criterion, $criterionid) {
		// This has a special set of options with title/criteria
		if(isset($criterion->options)) {
			$index = 0;
			foreach($criterion->options as $option) {
				// Needs a title and criteria array
				if(!isset($option->title))
					$this->error("Option #$index of criterion '$criterionid' is missing property 'title'");
				if(isset($option->criteria)) {
					$this->validateCriteriaCollection($option->criteria, "option #$index of $criterionid");
				} else {
					$this->error("Option #$index of criterion '$criterionid' is missing property 'criteria'");
				}
				$index++;
			}
			if($index == 0)
				$this->error("'options' property of criterion '$criterionid' should not be empty");
		} else {
			$this->error("Criterion '$criterionid' is missing the 'options' property");
		}
	}

	private function validateSelectMultiple($criterion, $criterionid) {
		$this->validateOptions($criterion, $criterionid);
		$this->validateMinMaxSelections($criterion, $criterionid);
	}

	private function validateMinMaxSelections($criterion, $criterionid) {
		if(isset($criterion->minselections)) {
			if(!is_int($criterion->minselections)) {
				$this->error("'minselections' property of '$criterionid' should be an integer");
			} else if($criterion->minselections < 0) {
				$this->error("'minselections' property of '$criterionid' should be greater at least zero");
			} else if(isset($criterion->options) && is_array($criterion->options)
					&& $criterion->minselections > count($criterion->options)) {
				$this->error("'minselections' property of '$criterionid' is greater than available options");
			} else if(isset($criterion->maxselections) && $criterion->minselections > $criterion->maxselections) {
				$this->error("'minselections' property of '$criterionid' is greater than 'maxselections'");
			}
		}

		if(isset($criterion->maxselections)) {
			if(!is_int($criterion->maxselections)) {
				$this->error("'maxselections' property of '$criterionid' should be an integer");
			} else if($criterion->maxselections <= 0) {
				$this->error("'maxselections' property of '$criterionid' should be greater than zero");
			}
		}
	}

	private function validateSelectMultipleSubItem($criterion, $criterionid) {
		if(isset($criterion->description))
			$this->warn("Criterion objects of type '{$criterion->type}' do not have a 'description' property, but '$criterionid' does");
		$this->validateOptions($criterion, $criterionid, true);
		$this->validateMinMaxSelections($criterion, $criterionid);
	}

	private function validateOptions($criterion, $criterionid, $hasSubCriteria = false) {
		$keys = array();
		// Make sure that the options property exists
		if(!isset($criterion->options)) {
			$this->error("Criterion '$criterionid' is missing 'options' property");
		} else if(!is_array($criterion->options)) {
			$this->error("'options' proptery of criterion '$criterionid' should be an array, it isn't");
		} else if(count($criterion->options) == 0) {
			$this->error("'options' property of criterion '$criterionid' should not be empty");
		} else {
			$index = 0;
			foreach($criterion->options as $option) {
				$key = null;

				if(is_object($option)) {
					// needs a title and a value
					if(!isset($option->title))
						$this->error("Option #$index of '$criterionid' is missing the 'title' property");
					if(!isset($option->value))
						$this->error("Option #$index of '$criterionid' is missing the 'value' property");
					else
						$key = $option->value;
				} else if (is_array($option)) {
					$this->error("Criterion '$criterionid' has an array for an option. Should be a title/value object or a string");
				} else {
					$key = $option;
				}

				// Check to make sure that the same option isn't repeated multiple times
				if($key && in_array($key, $keys)) {
					$this->error("Criterion '$criterionid' has multiple of the same option, '$key'");
				} else {
					$keys[] = $key;
				}

				// If this set of options is supposed to have sub-criteria, then make sure that it does
				if($hasSubCriteria) {
					if(isset($option->criteria)) {
						// Pop off the criterionids for this nested set of criteria
						$oldCriterionids = $this->criterionids;
						$this->criterionids = array();

						// Validate nested set of criteria
						$this->validateCriteriaCollection($option->criteria, "option #$index of $criterionid");

						// Push the old criterion ids back on
						$this->criterionids = $oldCriterionids;
					} else {
						$this->error("Option #$index of '$criterionid' is missing the 'criteria' property");
					}
				}
				
				$index++;
			}
		}

		return $keys;
	}

	private function validateDateRange($criterion, $criterionid) {
		if(isset($criterion->defaultmindate))
			$this->validateDateString($criterion->defaultmindate, 'defaultmindate', $criterionid);
		if(isset($criterion->defaultmaxdate))
			$this->validateDateString($criterion->defaultmaxdate, 'defaultmaxdate', $criterionid);
		if(isset($criterion->mindate))
			$this->validateDateString($criterion->mindate, 'mindate', $criterionid);
		if(isset($criterion->maxdate))
			$this->validateDateString($criterion->maxdate, 'maxdate', $criterionid);
	}

	private function validateDate($criterion, $criterionid) {
		if(isset($criterion->defaultvalue))
			$this->validateDateString($criterion->defaultvalue, 'defaultvalue', $criterionid);
		if(isset($criterion->mindate))
			$this->validateDateString($criterion->mindate, 'mindate', $criterionid);
		if(isset($criterion->maxdate))
			$this->validateDateString($criterion->maxdate, 'maxdate', $criterionid);
	}

	private function validateNumberRange($criterion, $criterionid) {
		$this->validateNumber($criterion, $criterionid);

		// Make sure that min and max labels are strings
		if(isset($criterion->defaultminlabel) && !is_string($criterion->defaultminlabel))
			$this->error("Property 'defaultminlabel' of criterion '$criterionid' should be a string");
		if(isset($criterion->defaultmaxlabel) && !is_string($criterion->defaultmaxlabel))
			$this->error("Property 'defaultmaxlabel' of criterion '$criterionid' should be a string");
	}

	private function validateNumber($criterion, $criterionid) {
		$isInt = false;
		// Make sure that the integer property is a boolean
		if(isset($criterion->integer) && !is_bool($criterion->integer))
			$this->error("Property 'integer' of criterion '$criterionid' should be a bool (true or false)");
		if(isset($criterion->integer) && $criterion->integer)
			$isInt = $criterion->integer === true;

		// Validate that min and max are the right data type and that they don't conflict with one another
		if(isset($criterion->min) && !is_numeric($criterion->min))
			$this->error("Property 'min' of criterion '$criterionid' needs to be a number");
		else if($isInt && isset($criterion->min) && !is_int($criterion->min))
			$this->error("Property 'min' of criterion '$criterionid' needs to be an integer");

		if(isset($criterion->max) && !is_numeric($criterion->max))
			$this->error("Property 'max' of criterion '$criterionid' needs to be a number");
		else if($isInt && isset($criterion->max) && !is_int($criterion->max))
			$this->error("Property 'max' of criterion '$criterionid' needs to be an integer");

		if(isset($criterion->min) && isset($criterion->max)) {
			if($criterion->min >= $criterion->max)
				$this->error("Property 'min' of criterion '$criterionid' should be less than 'max'");
		}

		// Ensure that unit is a string
		if(isset($criterion->unit) && !is_string($criterion->unit))
			$this->error("Property 'unit' of criterion '$criterionid' should be a string, but it isn't");

		// Make sure that the default value is a number and that it lies within the min and max
		if(isset($criterion->defaultvalue)) {
			if(!is_numeric($criterion->defaultvalue))
				$this->error("Property 'defaultvalue' of criterion '$criterionid' needs to be a number");
			else if($isInt && !is_int($criterion->defaultvalue))
				$this->error("Property 'defaultvalue' of criterion '$criterionid' needs to be an integer");
			else {
				if(isset($criterion->min) && $criterion->defaultvalue < $criterion->min)
					$this->error("Properity 'defaultvalue' of criterion '$criterionid' should not be less than 'min'");
				if(isset($criterion->max) && $criterion->defaultvalue > $criterion->max)
					$this->error("Properity 'defaultvalue' of criterion '$criterionid' should not be greater than 'max'");
			}
		}
	}

	private function validateDateString($dateString, $propName, $criterionid) {
		if(!is_string($dateString)) {
			$this->error("'$propName' property of '$criterionid' should be a string");
		} else {
			// Make sure that the date is in the right format
			if(!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateString, $parts)) {
				$this->error("'$propName' property of '$criterionid' is '$dateString', should be 'YYYY-MM-DD' format");
			} else {
				// Make sure that the date is valid
				$date = strtotime($dateString);
				if($date === FALSE)
					$this->error("'$propName' property of '$criterionid' is not a valid date");
			}
		}
	}

	private function validateText($criterion, $criterionid) {
		// Validate that maxchars is both an integer and positive, non-zero
		if(isset($criterion->maxchars)) {
			if(!is_int($criterion->maxchars))
				$this->error("Criterion '$criterionid' got a non-integer value for 'maxchars' property");
			else if($criterion->maxchars <= 0)
				$this->error("'maxchars' should be greater than zero, but '$criterionid' got {$criterion->maxchars}");
		}

		// If regex is set then make sure that it is a valid regex
		if(isset($criterion->regex)) {
			if(@preg_match('/' . $criterion->regex . '/', "") === FALSE || preg_last_error() != PREG_NO_ERROR)
				$this->error("'regex' property of criterion '$criterionid' is not valid");
		}
	}

	private function registerCriterionid($criterionid) {
		// Make sure that the criterionid is a string
		if(!is_string($criterionid)) {
			$display = is_object($criterionid) ? "Object" : "$criterionid";
			$this->error("Execpted criterionid to be a string but got '$criterionid'");
		}

		// Disallow use of the same criterionid multiple times in the same spec
		if(array_key_exists($criterionid, $this->criterionids)) {
			$this->error("Multiple criterion objects are sharing the same id '$criterionid'");
		} else {
			$this->criterionids[$criterionid] = 1;
		}
	}

	public function getErrors() {
		return $this->errors;
	}

	private function error($message) {
		$this->errors[] = $message;
	}

	public function getWarnings() {
		return $this->warnings;
	}

	private function warn($message) {
		$this->warnings[] = $message;
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

}

