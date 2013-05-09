<?php

/* This is the base class that all criteria objects inherit from.
	This class implements default behaviors for 'build' and 'buildQuery'

	See src/lib/criteria/GenericDemographics.php on how to implement `buildCriteria`
*/
abstract class CriteriaBase
{
	protected $criteriaid, $brandkey, $medium, $affiliatenumber;

	protected $spec;

	public function __construct($medium, $brandkey, $criteriaid, $affiliatenumber) {
		$this->medium = $medium;
		$this->brandkey = $brandkey;
		$this->criteriaid = $criteriaid;
		$this->affiliatenumber= $affiliatenumber;

		$this->spec = new CriteriaSpec($criteriaid, 'No title', 'No description');
	}

	/*
		Subclasses must define their own buildCriteria behavior.
		This method should build up a criteria object and assign it to $spec.
		See src/lib/criteria/GenericDemographics.php on how to implement this method.
	*/
	public abstract function buildCriteria();
	
	public function getCriteriaSpec() {
		return $this->spec;
	}

	// Build a SQL `in` clause
	private function inClause($filter, $criterionid, $column, $isString = true) {
		// Only build the clause if the criterion is selected
		if(isset($filter->$criterionid) and count($filter->$criterionid) > 0) {
			$sql = " and $column in (";
			foreach($filter->$criterionid as $value) {
				if($isString)
					$sql .= "'$value',";
				else
					$sql .= "$value,";
			}
			// TODO: clean this up, it is a yucky cheat
			$sql .= "'_')";
			return $sql;
		} else {
			return "";
		}
	}

	// TODO: Add orderinfo, affilateinfo & creativeinfo to buildQuery method
	public function buildQuery($filter) {
		$sql = "from recipient where";
		$sql .= " brandkey = '{$this->brandkey}'";

		$sql .= $this->inClause($filter, 'affiliates', 'affiliatenumber', true);
		$sql .= $this->inClause($filter, 'vehicle', 'make', true);
		$sql .= $this->inClause($filter, 'custloyalty', 'loyaltyprogram', true);
		$sql .= $this->inClause($filter, 'gender', 'gender', true);

		if(isset($filter->visitedrange) and count($filter->visitedrange) == 2) {
			$r1 = $filter->visitedrange[0];
			$r2 = $filter->visitedrange[1];
			if($r1)
				$sql .= " and lastvisit >= '$r1'";
			if($r2)
				$sql .= " and lastvisit <= '$r2'";
		}

		if(isset($filter->mileage) and count($filter->mileage) == 2) {
			$m1 = $filter->mileage[0];
			$m2 = $filter->mileage[1];
			if($m1)
				$sql .= " and mileage >= $m1";
			if($m2)
				$sql .= " and mileage <= $m2";
		}

		return "$sql;";
	}

	// Start the build chain (fluent interface design pattern)
	protected function build($title, $description) {
		$this->spec->title = $title;
		$this->spec->description = $description;
		return $this->spec;
	}
}

