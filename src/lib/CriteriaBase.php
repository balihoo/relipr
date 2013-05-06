<?php

abstract class CriteriaBase
{
	protected $criteriaid, $brandkey, $medium, $affiliateid;

	protected $spec;

	public function __construct($medium, $brandkey, $criteriaid, $affiliateid) {
		$this->medium = $medium;
		$this->brandkey = $brandkey;
		$this->criteriaid = $criteriaid;
		$this->affiliateid = $affiliateid;

		$this->spec = new CriteriaSpec($criteriaid, 'No title', 'No description');
	}

	public abstract function buildCriteria();
	
	public function getCriteriaSpec() {
		return $this->spec;
	}

	private function inClause($data, $criterionid, $column, $isString = true) {
		if(isset($data->$criterionid) and count($data->$criterionid) > 0) {
			$sql = " and $column in (";
			foreach($data->$criterionid as $value) {
				if($isString)
					$sql .= "'$value',";
				else
					$sql .= "$value,";
			}
			$sql .= "'_')";
			return $sql;
		} else {
			return "";
		}
	}

	public function buildQuery(Selection $selection) {
		$data = $selection->data;

		$sql = "from recipient where";
		$sql .= " brandkey = '{$this->brandkey}'";

		$sql .= $this->inClause($data, 'affiliates', 'affiliateid', true);

		if(isset($data->visitedrange) and count($data->visitedrange) == 2) {
			$r1 = $data->visitedrange[0];
			$r2 = $data->visitedrange[1];
			if($r1)
				$sql .= " and lastvisit >= '$r1'";
			if($r2)
				$sql .= " and lastvisit <= '$r2'";
		}

		if(isset($data->vehicle) and count($data->vehicle) > 0) {
			$sql .= " and make in (";
			foreach($data->vehicle as $make) {
				$sql .= "'$make',";
			}
			$sql .= "'')";
		}

		if(isset($data->mileage) and count($data->mileage) == 2) {
			$m1 = $data->mileage[0];
			$m2 = $data->mileage[1];
			if($m1)
				$sql .= " and mileage >= $m1";
			if($m2)
				$sql .= " and mileage <= $m2";
		}

		if(isset($data->custloyalty) and count($data->custloyalty) > 0) {
			$sql .= " and loyaltyprogram in (";
			foreach($data->custloyalty as $prog) {
				$sql .= "'$prog',";
			}
			$sql .= "'')";
		}

		if(isset($data->gender) and count($data->gender) > 0) {
			$sql .= " and gender in (";
			foreach($data->gender as $gender) {
				$sql .= "'$gender',";
			}
			$sql .= "'')";
		}

		return "$sql;";
	}

	protected function build($title, $description) {
		$this->spec->title = $title;
		$this->spec->description = $description;
		return $this->spec;
	}
}
