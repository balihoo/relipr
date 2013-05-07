<?php

require_once 'ListDTO.php';

use Tonic\Response,
		Tonic\UnauthorizedException,
		Tonic\NotFoundException,
		Tonic\ForbiddenException,
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

	// Get a list object
	public function getList($medium, $brandkey, $criteriaid, $listid) {
		$sql = "select * from list where medium = '" . $this->db->escapestring($medium) . "' " .
			" and brandkey = '" . $this->db->escapestring($brandkey) . "' " .
			" and criteriaid = '" . $this->db->escapestring($criteriaid) . "' " .
			" and listid = $listid;";
		$list = $this->db->query($sql)->fetchArray(SQLITE3_ASSOC);
		if($list === FALSE)
			throw new NotFoundException("List not found");
		return ListDTO::fromArray($list);
	}

	public function submitList($medium, $brandkey, $criteriaid, $listid) {
		$list = $this->getList($medium, $brandkey, $criteriaid, $listid);
		if($list->status != ListDTO::STATUS_NEW)
			throw new ForbiddenException("This list is in status '{$list->status}' - list cannot be submitted");
		$list->status = ListDTO::STATUS_SUBMITTED;
		$countQuery = $this->getCountQuery($list->filter, $medium, $brandkey, $criteriaid);
		$list->count = $this->db->querySingle($countQuery);
		$list->isestimate = false;
		$list->cost = $list->count * 0.05;
		$list->updateLinks();
		$this->saveList($list);
		return $list;
	}

	public function cancelList($medium, $brandkey, $criteriaid, $listid) {
		$list = $this->getList($medium, $brandkey, $criteriaid, $listid);
		if($list->status != ListDTO::STATUS_NEW
			&& $list->status != ListDTO::STATUS_SUBMITTED
			&& $list->status != ListDTO::STATUS_CANCELED)
			throw new ForbiddenException("This list is in status '{$list->status}' - too late to cancel");
		$list->status = ListDTO::STATUS_CANCELED;
		$list->updateLinks();
		$this->saveList($list);
		return $list;
	}

	public function getCountQuery($filter, $medium, $brandkey, $criteriaid) {
		$criteriaObject = CriteriaBuilder::getCriteriaObject($medium, $brandkey, $criteriaid);
		return "select count(*) " . $criteriaObject->buildQuery($filter);
	}

	// Create a new list object
	public function createList($filter, $medium, $brandkey, $criteriaid, $columns, $requestedcount) {
		$list = ListDTO::fromArray(array(
			'listid' => null,
			'count' => null,
			'brandkey' => $brandkey,
			'criteriaid' => $criteriaid,
			'medium' => $medium,
			'requestedcount' => $requestedcount,
			'isestimate' => null,
			'cost' => null,
			'status' => ListDTO::STATUS_NEW,
			'callback' => null,
			'filter' => $filter,
			'columns' => $columns,
		));
		$this->saveList($list);
		return $list;
	}

	public function saveList($list) {
		if($list->listid === null) {
			// Prepare the insert statement
			$stmt = $this->db->prepare('
			insert into list(
				medium, brandkey, criteriaid, filter, requestedcount, count, status, columns)
			values(
				:medium, :brandkey, :criteriaid, :filter, :requestedcount, :count, :status, :columns);
			');
		} else {
			// Prepare the update statement
			$stmt = $this->db->prepare('
			update list set
				medium = :medium,
				brandkey = :brandkey,
				criteriaid = :criteriaid,
				filter = :filter,
				requestedcount = :requestedcount,
				count = :count,
				status = :status,
				columns = :columns
			where listid = :listid;');
			$stmt->bindValue(':listid', $list->listid, SQLITE3_INTEGER);
		}

		// Bind the values to their columns
		$stmt->bindValue(':medium', $list->medium, SQLITE3_TEXT);
		$stmt->bindValue(':brandkey', $list->brandkey, SQLITE3_TEXT);
		$stmt->bindValue(':criteriaid', $list->criteriaid, SQLITE3_TEXT);
		$stmt->bindValue(':filter', ListDTO::encodeFilter($list->filter), SQLITE3_TEXT);
		$stmt->bindValue(':requestedcount', $list->requestedcount, SQLITE3_INTEGER);
		$stmt->bindValue(':count', $list->count, SQLITE3_INTEGER);
		$stmt->bindValue(':status', $list->status, SQLITE3_TEXT);
		$stmt->bindValue(':columns', ListDTO::encodeColumns($list->columns), SQLITE3_TEXT);
		$stmt->execute();

		// Set the list id if this was an insert
		if($list->listid === null)
			$list->listid = $this->db->lastInsertRowID();
		$list->updateLinks();
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

	// Use the recipient database to build a list of criteria options
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

