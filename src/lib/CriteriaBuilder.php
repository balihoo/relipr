<?php
/* The CriteriaBuilder encapsulates all the details of how criterion objects are strung together
 Scroll down to see how we define a class for each criterion type
*/

require_once 'OptionBuilder.php';
require_once 'CriteriaBase.php';

use Tonic\NotFoundException;

class CriteriaBuilder
{
	// Get an array representation of the specified criteria specification
	public static function getCriteria($medium, $brandkey, $criteriaid, $affiliatenumber= null) {
		$critObject = self::getCriteriaObject($medium, $brandkey, $criteriaid, $affiliatenumber);
		$critObject->buildCriteria();
		return $critObject->getCriteriaSpec();
	}

	private static function getBasePath($medium, $brandkey) {
		return realpath(dirname(__FILE__) . "/criteria/$medium/$brandkey");
	}

	// Get a list of all the valid criteriaids for a specific brand and medium
	public static function getCriteriaids($medium, $brandkey) {
		$base = self::getBasePath($medium, $brandkey);
		$files = array_values(preg_grep('/\.php$/', scandir($base)));
		return array_map(function ($fname) {
				return preg_replace('/\.php$/', '', $fname);
			}, $files);
	}

	// Get a list of all the valid criteria specs for this brand and medium
	public static function getCriteriaCollection($medium, $brandkey) {
		$criteriaids = self::getCriteriaids($medium, $brandkey);

		$result = array();
		foreach($criteriaids as $criteriaid)
			$result[] = self::getCriteria($medium, $brandkey, $criteriaid);
		return $result;
	}

	// Get an instance of the appropriate CriteriaBase subclass
	public static function getCriteriaObject($medium, $brandkey, $criteriaid, $affiliatenumber= null) {
		// Use a naming convention to find the criteria class file
		$classPath = self::getBasePath($medium, $brandkey) . "/$criteriaid.php";
		if(!file_exists($classPath)) // Make sure the file actually exists
			throw new NotFoundException("Unable to find criteria '$criteriaid' for brand '$brandkey' and medium '$medium'");
		// Load the file's source
		require_once($classPath);
		// Figure out the class name based on this pattern
		$className = "{$medium}_{$brandkey}_{$criteriaid}";
		// Return a new instance of the criteria class
		return new $className($medium, $brandkey, $criteriaid, $affiliatenumber);
	}
}

class CriteriaSpec extends CriteriaList
{
	public $criteriaid, $title, $description;

	public function __construct($criteriaid, $title, $description) {
		$this->criteriaid = $criteriaid;
		$this->title = $title;
		$this->description = $description;
	}
}

class CriteriaList
{
	public $criteria = array();
	private $top = null;
	protected $parent = null;

	public function __construct($parent = null) {
		$this->parent = $parent;
	}

	// Create a convenience method for setting arbitrary criterion attributes
	public function __call($fname, $args) {
		if($this->top == null) {
			// Puke if a criterion isn't already set
			throw new Exception("Criteriabuilder: Trying to call method $fname on a non-object");
		} else if(preg_match('/^set([a-z]+)$/', $fname, $matches)) {
			// Only handle methods that start with the name set...
			$fname = $matches[1];
			if(property_exists($this->top, $fname)) {
				if(count($args) != 1) {
					// Setter methods expect exactly one parameter
					error_log("WARN CriteriaBuilder: Expected exactly one arg when calling $fname of " . get_class($this->top));
				} else {
					// Let's actually set the desired property on the criterion object
					$this->top->$fname = $args[0];
				}
			} else {
				// Log a warning if this class doesn't have the desired property
				error_log("WARN CriteriaBuilder: Property $fname not found in class " . get_class($this->top));
			}
		} else {
			// Puke if a method other than setxxx is is called
			throw new Exception("Criteriabuilder: Method $fname of class " . get_class($this->top) . " does not exist");
		}
		return $this;
	}

	public function getTop() {
		return $this->top;
	}

	public function startSection($title) {
		$this->top = null;
		$section = new CriteriaSection($title, $this);
		$this->criteria[] = $section;
		return $section;
	}

	public function addMultiSelect($criterionid, $title, $options, $description = null) {
		$this->top = new CriterionMultiSelect($criterionid, $title, $description);
		$this->top->setOptions($options);
		$this->criteria[] = $this->top;
		return $this;
	}

	public function addSelect($criterionid, $title, $options, $description = null) {
		$this->top = new CriterionSelect($criterionid, $title, $description);
		$this->top->setOptions($options);
		$this->criteria[] = $this->top;
		return $this;
	}

	public function addNested($criterionid, $title, $options, $description = null) {
		$this->top = new CriterionNested($criterionid, $title, $description);
		$this->top->setOptions($options);
		$this->criteria[] = $this->top;
		return $this;
	}

	public function startNested($criterionid, $title, $description = null) {
		$this->top = new CriterionNested($criterionid, $title, $description);
		$this->criteria[] = $this->top;
		return new CriteriaNestedHelper($this);
	}

	public function nextOption($title) {
		return $this->parent->nextOption($title, $this);
	}

	public function endNested() {
		return $this->parent->endNested($this);
	}

	public function addDateRange($criterionid, $title, $description = null) {
		$this->top = new CriterionDateRange($criterionid, $title, $description);
		$this->criteria[] = $this->top;
		return $this;
	}

	public function addDate($criterionid, $title, $description = null) {
		$this->top = new CriterionDate($criterionid, $title, $description);
		$this->criteria[] = $this->top;
		return $this;
	}

	public function addNumber($criterionid, $title, $description = null) {
		$this->top = new CriterionNumber($criterionid, $title, $description);
		$this->criteria[] = $this->top;
		return $this;
	}

	public function addNumberRange($criterionid, $title, $description = null) {
		$this->top = new CriterionNumberRange($criterionid, $title, $description);
		$this->criteria[] = $this->top;
		return $this;
	}

}

class CriteriaSection extends CriteriaList
{
	public $type = 'section';
	public $title = '';
	public $criteria = array();

	public function __construct($title, $parent) {
		$this->title = $title;
		$this->parent = $parent;
	}

	public function endSection() {
		return $this->parent;
	}
}

class CriteriaNestedHelper
{
	public $title;
	public $parent;

	public function __construct($parent) {
		$this->parent = $parent;
	}

	public function nextOption($title, $previousList = null) {
		if($previousList != null)
			$this->setOptions($previousList);

		$this->title = $title;
		return new CriteriaList($this);
	}

	private function setOptions($criteriaList) {
		$this->parent->getTop()->options[] = array(
			'title' => $this->title,
			'criteria' => $criteriaList->criteria
		);
	}

	public function endNested($criteriaList) {
		$this->setOptions($criteriaList);
		return $this->parent;
	}
}

abstract class Criterion
{
	public $criterionid;
	public $type = null;
	public $helptext = "", $description = "", $title = "",
		$defaultvalue = null, $editable = true, $hidden = false, $required = false;

	public function __construct ($criterionid, $title, $description = null) {
		$this->criterionid = $criterionid;
		$this->title = $title;

		if($description)
			$this->description = $description;
	}
}

class CriterionSelect extends Criterion
{
	public $type = 'selectsingle';
	public $options = array(), $defaultvalue;

	public function setOptions($options) {
		$this->options = $options;
	}
}

class CriterionNested extends CriterionSelect
{
	public $type = 'nestedsingle';
}

class CriterionMultiSelect extends CriterionSelect
{
	public $type = 'selectmultiple';
	public $maxselections = null, $minselections = 0;
}

class CriterionNumberRange extends Criterion
{
	public $type = 'numberrange';
	public $integer = true;
	public $min = -1000000;
	public $max = 1000000;
	public $unit = "";
	public $defaultminlabel = "Unlimited";
	public $defaultmaxlabel = "Unlimited";
}

class criterionNumber extends Criterion
{
	public $type = 'number';
	public $integer = true, $min = null, $max = null, $unit = '';
}

class CriterionDate extends Criterion
{
	public $type = 'date';
	public $mindate = null, $maxdate = null, $defaultvalue = null;
}

class CriterionDateRange extends CriterionDate
{
	public $type = 'daterange';
	public $defaultmindate = null, $defaultmaxdate = null;
}

