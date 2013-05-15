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
		if(isset($filter->$criterionid) && count($filter->$criterionid) > 0) {
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
		$sql .= $this->vehicleSelect($filter, 'vehicle');
		$sql .= $this->inClause($filter, 'custloyalty', 'loyaltyprogram', true);
		$sql .= $this->inClause($filter, 'gender', 'gender', true);

		if(isset($filter->visitedrange) && count($filter->visitedrange) == 2) {
			$r1 = $filter->visitedrange[0];
			$r2 = $filter->visitedrange[1];
			if($r1)
				$sql .= " and lastvisit >= '$r1'";
			if($r2)
				$sql .= " and lastvisit <= '$r2'";
		}

		if(isset($filter->mileage) && count($filter->mileage) == 2) {
			$m1 = $filter->mileage[0];
			$m2 = $filter->mileage[1];
			if($m1)
				$sql .= " and mileage >= $m1";
			if($m2)
				$sql .= " and mileage <= $m2";
		}

		return "$sql;";
	}

	private function vehicleSelect($filter, $criterionid) {
		// Only insert a vehicle AND clause if vehicles were selected
		if(isset($filter->$criterionid) && isset($filter->$criterionid->make)) {
			// Start this and clause with a 0, all following criteria will be OR'd to this
			// 0 or X <=> X
			$sql = " and (0"; // This hack makes my life much easier
			$selections = $filter->$criterionid->make;
			foreach($selections as $selection) {
				foreach($selection as $make => $models) {
					$model = $models->model;
					$sql .= " or (make = '$make'";
					if($model != 'Any')
						$sql .= " and model = '$model'";
					$sql .= ")";
				}
			}
			$sql .= ")";
			return $sql;
		} else {
			return "";
		}
	}

	// Start the build chain (fluent interface design pattern)
	protected function build($title, $description) {
		$this->spec->title = $title;
		$this->spec->description = $description;
		return $this->spec;
	}
}

