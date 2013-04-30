<?php

use Tonic\Response,
		Tonic\UnauthorizedException,
		Tonic\NotFoundException,
		Tonic\Request;

class DB {

	private static $instance = null;
	private $db;
	private $_brands;

	private function __construct($fileName = null) {
		if($fileName === null)
			$fileName = "../data/sample.db";
		$this->db = new SQLite3($fileName);
	}

	public static function getInstance() {
		if(self::$instance === null)
			self::$instance = new DB();
		return self::$instance;
	}

	public function getTableLength ($tableName) {
		$result = $this->db->query("select count(*) as total from $tableName;");
		if($result) {
			$data = $result->fetchArray();
			return $data['total'];
		} else {
			return null;
		}
	}

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

	public function getAffiliate($brandkey, $affiliateid) {
		$sql = "select count(*) total from recipient where brandkey='$brandkey' and affiliateid=$affiliateid";
		$result = $this->db->query($sql);
		while($result) {
			$row = $result->fetchArray(SQLITE3_ASSOC);
			return $row['total'];
		}
		return null;
	}

	public function getMediums() {
		return array(
			'email' => 'Email Marketing',
			'directmail' => 'Direct Mail Marketing',
		);
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

	public function getBrandCriteria($brandkey, $medium, $affiliateid) {
		$compKey = "$brandkey-$medium";
		switch($compKey)
		{
			case 'acme-directmail':
				return array(
					$this->newMovers('12345', array(30, 60)),
					$this->demographics('12456'),
				);
			case 'oscorp-directmail':
				return array(
					$this->carCare('osc101', 'oscorp', $affiliateid),
				);
		}
		return null;
	}

	private function demographics($id) {
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
						array(
							'criterionid' => 'agerange',
							'type' => 'selectmultiple',
							'title' => 'Age Ranges',
							'options' => array(
								array('title' => '18 or younger', 'value' => '1'),
								array('title' => '19 to 25', 'value' => '2'),
								array('title' => '26 to 35', 'value' => '3'),
								array('title' => '36 to 35', 'value' => '4'),
								array('title' => '46 to 35', 'value' => '5'),
								array('title' => '56 to 35', 'value' => '6'),
								array('title' => '66 to 35', 'value' => '7'),
								array('title' => '76 and older', 'value' => '8'),
							),
						),
					), 
				),
				array(
					'criterionid' => 'income',
					'type' => 'selectmultiple',
					'title' => 'Household Income',
					'options' => array(
						array('title' => 'Less than 20,000', 'value' => '1'),
						array('title' => '20,000 to 40,000', 'value' => '2'),
						array('title' => '40,000 to 60,000', 'value' => '3'),
						array('title' => '60,000 to 80,000', 'value' => '4'),
						array('title' => '80,000 to 100,000', 'value' => '5'),
						array('title' => '100,000 to 150,000', 'value' => '6'),
						array('title' => 'Greater than 150,000', 'value' => '7'),
					)
				),
			)
		);

		return $criteria;
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

