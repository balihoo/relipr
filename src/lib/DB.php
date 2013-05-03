<?php

use Tonic\Response,
		Tonic\UnauthorizedException,
		Tonic\NotFoundException,
		Tonic\Request;

class DB {

	private static $instance = null;
	private $db;
	private $_brands;

	// Singleton constructor
	private function __construct($fileName = null) {
		if($fileName === null)
			$fileName = "../data/sample.db";
		$this->db = new SQLite3($fileName);
		$this->db->exec('PRAGMA foreign_keys = ON;');
	}

	// Singleton method
	public static function getInstance() {
		if(self::$instance === null)
			self::$instance = new DB();
		return self::$instance;
	}

	// Count the rows in a table - handy method used to check that things are set up
	public function getTableLength ($tableName) {
		$result = $this->db->query("select count(*) as total from $tableName;");
		if($result) {
			$data = $result->fetchArray();
			return $data['total'];
		} else {
			return null;
		}
	}

	// Return a list of brands
	public function getBrands() {
		if($this->_brands == null) {
			$this->_brands = array();
			$result = $this->db->query("select brandkey from brand order by brandkey;");
			while($row = $result->fetchArray(SQLITE3_ASSOC)) {
				$this->_brands[] = $row['brandkey'];
			}
		}
		return $this->_brands;
	}

	// Check that an affiliate exists and has recipients
	public function getAffiliate($brandkey, $affiliateid) {
		$sql = "select count(*) total from recipient where brandkey='$brandkey' and affiliateid=$affiliateid";
		$result = $this->db->query($sql);
		while($result) {
			$row = $result->fetchArray(SQLITE3_ASSOC);
			return $row['total'];
		}
		return null;
	}

	// Get a list of mediums
	public function getMediums() {
		return array(
			'email' => 'Email Marketing',
			'directmail' => 'Direct Mail Marketing',
		);
	}

	// Get all of the criteria for a single brand/medium
	// affiliateid is optional
	public function getBrandCriteria($brandkey, $medium, $affiliateid = null) {
		$compKey = "$brandkey-$medium";
		switch($compKey)
		{
			case 'acme-directmail':
				return array(
					$this->newMovers('12345', array(30, 60)),
					$this->demographics('12456', $brandkey, $affiliateid),
				);
			case 'oscorp-directmail':
				return array(
					$this->carCare('osc101', 'oscorp', $affiliateid),
				);
		}
		return null;
	}

	// Get a collection of criteria, or a single criteria if the id is specified
	public function getCriteria($medium, $brandkey, $criteriaid = null, $affiliateid = null) {
		$brandCriteria = $this->getBrandCriteria($brandkey, $medium, $affiliateid);

		// Throw a 404 if we didn't find any for this brand/medium
		if(!$brandCriteria)
			throw new NotFoundException("Criteria not found for brand '$brandkey', medium '$medium'");

		// If a specific critieriaid was supplied then try to return just that one
		if($criteriaid !== null) {
			foreach($brandCriteria as $criteria) {
				// If we found the criteria then return it
				if(isset($criteria['criteriaid']) && $criteria['criteriaid'] == $criteriaid)
					return $criteria;
			}
			throw new NotFoundException(
				"Criteria not found. medium:'$medium', brand:'$brandkey', criteriaid:'$criteriaid'");
		}

		return $brandCriteria;
	}

	// Create a new list object
	public function createList(Selection $selection, $medium, $brandkey, $criteriaid, $limit) {
		$count = $this->db->querySingle($selection->getCountQuery($medium, $brandkey, $criteriaid));

		$list = array(
			'listid' => null,
			'count' => $count,
			'brandkey' => $brandkey,
			'criteriaid' => $criteriaid,
			'medium' => $medium,
			'requestedCount' => $limit,
			'isEstimate' => false,
			'cost' => ($count > $limit ? $limit : $count) * .05,
			'status' => 'New',
			'callback' => null,
			'selections' => $selection->data,
		);

		$this->db->exec("insert into list (brandkey, criteriaid, medium, selections, requestedCount,
			count, status) values ('$brandkey', '$criteriaid', '$medium', '" .
				$this->db->escapeString($selection->jsonText) . "', $limit, $count, 'New');");
		$list['listid'] = $this->db->lastInsertRowID();
		return $list;
	}

	public function refreshDatabase() {
		// Make sure that we can run sqlite3 from the command line
		$sqlite3 = exec('which sqlite3', $output, $return);
		if($sqlite3 == '' || $return != 0) {
			throw new Exception("Unable to find sqlite3 executable");
		}
		if(!is_executable($sqlite3)) {
			throw new Exception("$sqlite3 is not executable on this server");
		}

		// Drop the existing database by truncating it to an empty file
		file_put_contents('../data/sample.db', '');

		$process = proc_open(
			"$sqlite3 sample.db",
			array(	0 => array('file', '../data/refreshdb.sql', 'r'),
							1 => array('pipe', 'w'),
							2 => array('pipe', 'w'),
			),
			$pipes, realpath('../data/')
		);

		// Read std out
		$out = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		// Read std err
		$err = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		// Check the exit code, if non-zero then it failed
		$exitCode = proc_close($process);
		if($exitCode != 0)
			throw new Exception("Failed to refresh db: '$err'");
		else
			return preg_replace('/\n/', '<br/>', $out);
	}

	private function demographics($id, $brandkey, $affiliateid = null) {
		$criteria = array(
			'criteriaid' => "$id",
			'title' => 'Select Your Target Audience',
			'description' => 'Use the consumer demographic selections to narrow your audience',
			'criteria' => array(
					array(
						'type' => 'section',
						'title' => 'Group of Items',
						'criteria' => array(
						array(
							'criterionid' => 'gender',
							'type' => 'selectmultiple',
							'title' => 'Gender',
							'options' => array('Male', 'Female'),
						),
						$this->getAgeRange($brandkey, $affiliateid),
					), 
				),
				$this->getIncomeRange($brandkey, $affiliateid),
			)
		);

		return $criteria;
	}

	private function getAgeRange($brandkey, $affiliateid = null) {
		$criterion = array(
			'criterionid' => 'agerange',
			'type' => 'selectmultiple',
			'title' => 'Age Ranges',
		);

		$sql = <<<SQL
select ar.range || ' (' || count(r.recipientid) || ' customers)' title, ar.lo value
from ( select '18 - 30' range, 18 lo, 30 hi
	union select '31 - 45', 31, 45
	union select '46 - 65', 46, 65
	union select '65 and older', 65, 120
) as ar
left join recipient r on r.age >= ar.lo and r.age <= ar.hi and r.brandkey = '$brandkey'
SQL;
		if($affiliateid)
			$sql .= " and r.affiliateid = $affiliateid\n";
		$sql .= "group by ar.range order by ar.lo;";
		$result = $this->db->query($sql);

		$options = array();
		while($data= $result->fetchArray()) {
			$options[] = array(
				'title' => $data['title'],
				'value' => $data['value'],
			);
		}

		$criterion['options'] = $options;
		return $criterion;
	}

	private function getIncomeRange($brandkey, $affiliateid = null) {
		$criterion = array(
			'criterionid' => 'income',
			'type' => 'selectmultiple',
			'title' => 'Household Income',
		);

		$sql = <<<SQL
select ir.range || ' (' || count(r.recipientid) || ' customers)'  title, ir.lo value
from (select '$0 - $25K' range, 0 lo, 25000 hi
 union select '$25K - $50K', 25000, 50000
 union select '$50K - $100K', 50000, 100000
 union select '$100K - $250K', 100000, 250000
 union select '$250K and above', 250000, 250000000
) ir
left join recipient r on r.income >= ir.lo and r.income < ir.hi and r.brandkey = '$brandkey'
SQL;
		if($affiliateid)
			$sql .= " and r.affiliateid = $affiliateid\n";
		$sql .= "group by ir.range order by ir.lo;";
		$result = $this->db->query($sql);

		$options = array();
		while($data= $result->fetchArray()) {
			$options[] = array(
				'title' => $data['title'],
				'value' => $data['value'],
			);
		}

		$criterion['options'] = $options;
		return $criterion;
	}

	private function newMovers($id, $days) {
		$criteria = array(
			'criteriaid' => "$id",
			'title' => 'New Movers',
			'description' => 'Select households that have new occupants',
			'criteria' => array(
				array(
					'criterionid' => 'reslength',
					'type' => 'selectSingle',
					'title' => 'Length of Residence',
					'description' => 'Choose the maximum length of occupancy',
					'options' => array(),
					'default' => 30,
					'required' => true,
					'editable' => true,
					'hidden' => false,
				),
			)
		);
		foreach($days as $day)
			$criteria['criteria'][0]['options'][] = array('title' => "$day Days", 'value' => $day);
		return $criteria;
	}

	private function carCare($id, $brandkey, $affiliateid = null) {
		$criteria = array(
			'criteriaid' => "$id",
			'title' => 'Car Care Customers',
			'description' => 'Select vehicle owners',
			'criteria' => array(
				array(
					'criterionid' => 'visitedrange',
					'type' => 'daterange',
					'title' => 'Visited',
					'description' => 'Neque porro quisquam est qui dolorem ipsum quia dolor sit amet',
				),
				array(
					'criterionid' => 'vehicles',
					'type' => 'nested',
					'title' => 'Vehicles',
					'description' => 'Choose the vehicles, makes and models, etc',
					'options' => $this->vehicleOptions($brandkey, $affiliateid),
				),
				array(
					'criterionid' => 'mileage',
					'type' => 'range',
					'title' => 'Vehicle Mileage',
					'description' => 'Choose target vehicle mileage',
					'min' => 0,
					'max' => 1000000000,
					'defaultMaxLabel' => 'Unlimited',
				),
				array(
					'criterionid' => 'maxvehicles',
					'type' => 'selectSingle',
					'title' => 'Maximum Vehicles',
					'description' => 'Up to how many vehicles...',
					'options' => array(1, 2, 3, 4),
				),
				array(
					'criterionid' => 'custloyalty',
					'type' => 'selectMany',
					'title' => 'Customer Loyalty',
					'description' => 'Choose the target loyalty programs',
					'options' => array('New Customer', 'Lost', 'Oil Change Only', 'Oil Change+', 'Specialty Service')
				),
				array(
					'criterionid' => 'carcareclub',
					'type' => 'option',
					'title' => '',
					'option' => array('value' => 'eCarCareClub', 'title' => 'Include customers enrolled in the eCarCare Club')
				),
			)
		);
		return $criteria;
	}

	private function vehicleOptions($brandkey, $affiliateid = null) {
		$sql = "select distinct make, model
			from recipient
			where brandkey = '$brandkey'";

		if($affiliateid)
			$sql .= "\nand affiliateid = $affiliateid";

		$sql .= "\norder by make, model";

		$result = $this->db->query($sql);

		$options = array();
		$idx = -1;
		$lastMake = null;

		while($data= $result->fetchArray()) {
			$make = $data[0];
			$model = $data[1];

			if($make != $lastMake) {
				$options[++$idx] = array(
					'value' => $make,
					'title' => $make,
					'criteria' => array(
						'criterionid' => 'models',
						'type' => 'selectMany',
						'title' => 'Model',
						'options' => array(),
					));
				$lastMake = $make;
			}
			$options[$idx]['criteria']['options'][] = $model;
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
						'type' => 'nestedSelect',
						'options' => $options
					),
					array(
						'criterionid' => 'year',
						'title' => 'Year(s)',
						'type' => 'selectMany',
						'options' => $years
					)
				),
			)
		);
	}

}

