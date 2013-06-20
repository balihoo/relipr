<?php

/* This class provides access to the sqlite database */

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

	public static function close() {
		if(self::$instance != null)
			self::$instance->db->close();
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
	public function getAffiliate($brandkey, $affiliatenumber) {
		$sql = "select count(*) total from recipient where brandkey='$brandkey' and affiliatenumber=$affiliatenumber";
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
	public function getVehicles($brandkey, $affiliatenumber= null) {
		$sql = "select distinct make, model from recipient where brandkey = '$brandkey'";
		if($affiliatenumber)
			$sql .= " and affiliatenumber= $affiliatenumber";
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

	// Get the estimated count for a given filter and criteria
	public function getFilterCount($filter, $medium, $brandkey, $criteriaid) {
		$countQuery = $this->getCountQuery($filter, $medium, $brandkey, $criteriaid);
		try {
			$result = $this->db->querySingle($countQuery);
		} catch (Exception $ex) {
			error_log("Error runing count query: `$countQuery`");
			throw $ex;
		}
		return $result;
	}

	// Get the estimated count for a given list
	public function getListCount($list) {
		return $this->getFilterCount($list->filter, $list->medium, $list->brandkey, $list->criteriaid);
	}

	public function countList($list) {
		$list->count = $this->getListCount($list);
		$list->cost = $list->count * 0.05;

		// Save the list if it has a non-zero count
		$list->status = ListDTO::STATUS_FINALCOUNT;
		$list->updateLinks();
		$this->saveList($list, array('counted= datetime()'));
	}

	// TODO: only select the specified columns
	public function pullList($list, $fname) {
		$criteriaObject = CriteriaBuilder::getCriteriaObject($list->medium, $list->brandkey, $list->criteriaid);
		$spec = $criteriaObject->getCriteriaSpec();
		$columns = implode(',', array_keys($spec->columns));
		$sql = "select $columns " . $criteriaObject->buildQuery($list->filter);

		// If a requestedcount is set then limit the query to that many.
		if($list->requestedcount)
			$sql .= " limit $list->requestedcount";
		$sql .= ';';

		try {
			$result = $this->db->query($sql);
		} catch (Exception $ex) {
			error_log("Error executing list query: `$sql`");
			throw $ex;
		}

		$fp = fopen($fname, 'w');

		$hasHeaders = false;
		while($row = $result->fetchArray(SQLITE3_ASSOC)) {
			if(!$hasHeaders) {
				fputcsv($fp, array_keys($row));
				$hasHeaders = true;
			}
			fputcsv($fp, array_values($row));
		}
		fclose($fp);
	}

	public function cancelList($list) {
		// Idempotence: if already canceled then do nothing
		if($list->status == ListDTO::STATUS_CANCELED)
			return $list;

		// Make sure that the list is in a cancellable status
		if($list->status != ListDTO::STATUS_SUBMITTED)
			throw new ForbiddenException("This list is in status '{$list->status}' - too late to cancel");

		// Update the status to cancelled
		$list->status = ListDTO::STATUS_CANCELED;
		// Calculate the new links
		$list->updateLinks();
		// Save and return the list
		$this->saveList($list, array('canceled = datetime()'));
		return $list;
	}

	public function getCountQuery($filter, $medium, $brandkey, $criteriaid) {
		$criteriaObject = CriteriaBuilder::getCriteriaObject($medium, $brandkey, $criteriaid);
		return "select count(*) " . $criteriaObject->buildQuery($filter) . ";";
	}

	public function saveList($list, $updates = null) {
		if($list->listid === null) {
			// Prepare the insert statement
			$stmt = $this->db->prepare("
			insert into list(
				medium, brandkey, criteriaid, filter, orderinfo, affiliateinfo, creativeinfo, requestedcount,
				count, status, columns, callback, cost, inserted, cancelnotified, readied)
			values(
				:medium, :brandkey, :criteriaid, :filter, :orderinfo, :affiliateinfo, :creativeinfo, :requestedcount,
				:count, :status, :columns, :callback, :cost, datetime(), null, null);
			");
		} else {
			$sql = 'update list set ';
			if($updates) {
				foreach($updates as $update)
					$sql .= "$update ,";
			}
			$sql .= 'medium = :medium,
				brandkey = :brandkey,
				criteriaid = :criteriaid,
				filter = :filter,
				orderinfo = :orderinfo,
				affiliateinfo = :affiliateinfo,
				creativeinfo = :creativeinfo,
				requestedcount = :requestedcount,
				count = :count,
				status = :status,
				columns = :columns,
				cost = :cost
			where listid = :listid;';
			// Prepare the update statement
			$stmt = $this->db->prepare($sql);
			$stmt->bindValue(':listid', $list->listid, SQLITE3_INTEGER);
		}

		// Bind the values to their columns
		$stmt->bindValue(':medium', $list->medium, SQLITE3_TEXT);
		$stmt->bindValue(':brandkey', $list->brandkey, SQLITE3_TEXT);
		$stmt->bindValue(':criteriaid', $list->criteriaid, SQLITE3_TEXT);
		$stmt->bindValue(':filter', ListDTO::encodeObject($list->filter), SQLITE3_TEXT);
		$stmt->bindValue(':orderinfo', ListDTO::encodeObject($list->orderinfo), SQLITE3_TEXT);
		$stmt->bindValue(':affiliateinfo', ListDTO::encodeObject($list->affiliateinfo), SQLITE3_TEXT);
		$stmt->bindValue(':creativeinfo', ListDTO::encodeObject($list->creativeinfo), SQLITE3_TEXT);
		$stmt->bindValue(':requestedcount', $list->requestedcount, SQLITE3_INTEGER);
		$stmt->bindValue(':count', $list->count, SQLITE3_INTEGER);
		$stmt->bindValue(':status', $list->status, SQLITE3_TEXT);
		$stmt->bindValue(':columns', ListDTO::encodeColumns($list->columns), SQLITE3_TEXT);
		$stmt->bindValue(':callback', $list->callback, SQLITE3_TEXT);
		$stmt->bindValue(':cost', $list->cost, SQLITE3_FLOAT);
		try {
			$stmt->execute();
		} catch (Exception $ex) {
			error_log ("Error saving list " . json_encode($list));
			throw $ex;
		}

		// Set the list id if this was an insert
		if($list->listid === null)
			$list->listid = $this->db->lastInsertRowID();
		$list->updateLinks();
	}

	// Get an array of all the lists that have a callback that needs to be invoked
	public function getPendingCallbacks() {
		// Find all the lists that have a callback hook registered
		//  and have a status change that has not been propogated to the caller
		$sql = <<<SQL
			select * from list
			where callback is not null and callbackfailures < 5
				and ((canceled is not null and cancelnotified is null)
				or (counted is not null and countnotified is null)
				or (readied is not null and readynotified is null));
SQL;
		$result = $this->db->query($sql);
		$lists = array();
		while($data = $result->fetchArray())
			$lists[] = ListDTO::fromArray($data);
		return $lists;
	}

	public function findLists($status) {
		$sql = "select * from list where status = '" .
			$this->db->escapestring($status) . "';";
		$result = $this->db->query($sql);
		$lists = array();
		while($data = $result->fetchArray())
			$lists[] = ListDTO::fromArray($data);
		return $lists;
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
	public function getOptionsFromSQL($brandkey, $affiliatenumber, $sql, $on, $group, $order) {
		$sql .= " left join recipient r on $on and r.brandkey = '$brandkey'";
		if($affiliatenumber)
			$sql .= " and r.affiliatenumber= $affiliatenumber";
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

