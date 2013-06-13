<?php

class OptionBuilder
{
	public static function ageRange($brandkey, $affiliatenumber) {
		$sql = <<<SQL
select ar.range || ' (' || count(r.recipientid) || ' customers)' title, ar.lo value
from ( select '18 - 30' range, 18 lo, 30 hi
	union select '31 - 45', 31, 45
	union select '46 - 65', 46, 65
	union select '65 and older', 65, 120
) as ar 
SQL;
		return DB::getInstance()->getOptionsFromSQL($brandkey, $affiliatenumber, $sql,
			'r.age >= ar.lo and r.age <= ar.hi', 'ar.range', 'ar.lo');
	}

	public static function gender($brandkey, $affiliatenumber) {
		$sql = <<<SQL
select g.title || ' (' || count(r.recipientid) || ' customers)' title, g.value
from( select 'Female' title, 'f' value
 union select 'Male', 'm'
) as g
SQL;
		return DB::getInstance()->getOptionsFromSQL($brandkey, $affiliatenumber, $sql,
			'r.gender = g.value', 'g.value', 'g.title');
	}

	public static function radius($min, $max, $unit = "", $units = "") {
		$options = array();
		for($i = $min; $i <= $max; $i++) {
			$options[] = array(
				'title' => $i . ($i == 1 ? " $unit" : " $units"),
				'value' => $i
			);
		}
		return $options;
	}

	public static function incomeRange($brandkey, $affiliatenumber) {
		$sql = <<<SQL
select ir.range || ' (' || count(r.recipientid) || ' customers)' title, ir.lo value
from (select '$0 - $25K' range, 0 lo, 25000 hi
 union select '$25K - $50K', 25000, 50000
 union select '$50K - $100K', 50000, 100000
 union select '$100K - $250K', 100000, 250000
 union select '$250K and above', 250000, 250000000
) as ir
SQL;
		return DB::getInstance()->getOptionsFromSQL($brandkey, $affiliatenumber, $sql,
			'r.income >= ir.lo and r.income < ir.hi', 'ir.range', 'ir.lo');
	}

	public static function vehicles($brandkey, $affiliatenumber= null) {
		$vehicles = DB::getInstance()->getVehicles($brandkey, $affiliatenumber);
		$options = array();
		$idx = -1;
		$lastMake = null;

		while($data= $vehicles->fetchArray()) {
			$make = $data[0];
			$model = $data[1];

			if($make != $lastMake) {
				$criterion = array(
						'criterionid' => 'model',
						'type' => 'selectsingle',
						'title' => 'Model',
						'options' => array('Any'),
				);
				$criteria = array();
				$criteria[] = $criterion;

				$options[++$idx] = array(
					'value' => $make,
					'title' => $make,
					'criteria' => $criteria);
				$lastMake = $make;
			}
			$options[$idx]['criteria'][0]['options'][] = $model;
		}

		$years = array();
		for($yr = 2013; $yr >= 1999; $yr--)
			$years[] = $yr;

		return array(
			array(
				'title' => 'All Vehicles', 'criteria' => array()),
			array(
				'title' => 'Specific Vehicles', 'criteria' => array(
					array(
						'criterionid' => 'make',
						'title' => 'Make',
						'type' => 'selectmultiplesubitem',
						'options' => $options
					),
					array(
						'criterionid' => 'year',
						'title' => 'Year(s)',
						'type' => 'selectmultiple',
						'options' => $years
					)
				),
			)
		);
	}

}

