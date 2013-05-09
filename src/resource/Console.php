<?php

require_once('BasicResource.php');

use Tonic\Resource,
    Tonic\Response;

/**
 * Display a simple console for managing this application
 * @uri /console
 * @uri /
 */
class Console extends BasicResource{

	/**
	 * Produce a management console with some links and buttons
	 * @method GET
	 * @auth
	 */
	public function get(){
		// Get a view and populate it with model data
		$view = $this->getView('index');

		// Check to see if sqlite3 is running on this machine
		$lastLine = exec('which sqlite3', $output, $return);
		$view->check['sqlite3 is available on command line'] = $lastLine != '' && $return == 0;
		$view->check['sqlite3 command is executable'] = is_executable($lastLine);

		// Make sure that the sample csv file is available
		$view->check['Sample database source file'] = file_exists('../data/sample.csv');
		$view->check['Sample source file is writable'] = is_writable('../data/sample.csv');

		// Make sure that the db file is available
		$view->check['Sample Database'] = file_exists('../data/sample.db');
		$view->check['Sample DB is writable'] = is_writable('../data/sample.db');

		// Make sure that the  refresh script is available
		$view->check['Database refresh script'] = is_readable('../data/refreshdb.sql');

		// Make sure that the database is available and configured
		if(class_exists('SQLite3') && $view->check['Sample Database']) {
			$view->check['PHP SQLLite3 support'] = true;
			$view->check['Recipient table exists and is not empty'] = $this->db->getTableLength('recipient');
			$view->check['List table exists and is not empty'] = $this->db->getTableLength('list') !== null;
		} else {
			$view->check['PHP SQLLite3 support'] = false;
		}

		$view->render();
	}

}

