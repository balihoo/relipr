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

	// Get a list of all vehicl makes and models
	public function getVehicles($brandkey, $affiliateid = null) {
		$sql = "select distinct make, model from recipient where brandkey = '$brandkey'";
		if($affiliateid)
			$sql .= " and affiliateid = $affiliateid";
		$sql .= " order by make, model";
		return $this->db->query($sql);
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

	// Refresh the database back to the baseline
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

	// Use the recipient database to build a list of criteria selection options
	public function getOptionsFromSQL($brandkey, $affiliateid, $sql, $on, $group, $order) {
		$sql .= " left join recipient r on $on and r.brandkey = '$brandkey'";
		if($affiliateid)
			$sql .= " and r.affiliateid = $affiliateid ";
		$sql .= "\ngroup by $group\norder by $order;";
		$result = $this->db->query($sql);

		$options = array();
		while($data= $result->fetchArray()) {
			$options[] = array(
				'title' => $data['title'],
				'value' => $data['value'],
			);
		}
		return $options;
	}

}

