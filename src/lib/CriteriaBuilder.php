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

	public function addSection($title) {
		$section = new CriteriaSection($title);
		$this->criteria[] = $section;
		return $section;
	}

	public function addMultiSelect($criterionid, $title, $options, $description = null) {
		$multi = new CriterionMultiSelect($criterionid, $title, $description);
		$multi->setOptions($options);
		$this->criteria[] = $multi;
		return $this;
	}

	public function addSelect($criterionid, $title, $options, $description = null) {
		$select = new CriterionSelect($criterionid, $title, $description);
		$select->setOptions($options);
		$this->criteria[] = $select;
		return $this;
	}

	public function addNested($criterionid, $title, $options, $description = null) {
		$select = new CriterionNested($criterionid, $title, $description);
		$select->setOptions($options);
		$this->criteria[] = $select;
		return $this;
	}

	public function addDateRange($criterionid, $title, $description = null) {
		$this->criteria[] = new CriterionDateRange($criterionid, $title, $description);
		return $this;
	}

	public function addRange($criterionid, $title, $description = null) {
		$this->criteria[] = new CriterionRange($criterionid, $title, $description);
		return $this;
	}

	public function addOption($criterionid, $title, $option) {
		$this->criteria[] = array(
			'criterionid' => $criterionid,
			'type' => 'option',
			'title' => '',
			'option' => array('value' => $option, 'title' => $title)
		);
		return $this;
	}

}

class CriteriaSection extends CriteriaList
{
	public $type = 'section';
	public $title;
	public $criteria;

	public function __construct($title) {
		$this->title = $title;
	}
}

abstract class Criterion
{
	public $criterionid;
	public $type = null;
	public $helpText = "", $description = "", $title = "",
		$defaultvalue = null, $editable = false, $hidden = false;

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
	public $options = array();

	public function setOptions($options) {
		$this->options = $options;
	}
}

class CriterionMultiSelect extends CriterionSelect
{
	public $type = 'selectmultiple';
	public $maxselections = null;
}

class CriterionNested extends CriterionSelect
{
	public $type = 'nested';
}

class CriterionRange extends Criterion
{
	public $type = 'range';
}

class CriterionDate extends Criterion
{
	public $type = 'date';
	public $mindate = null, $maxdate = null;
}

class CriterionDateRange extends CriterionDate
{
	public $type = 'daterange';
}

