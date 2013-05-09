<?php

/*
	The admin console will invoke this resource to run administrative tasks
*/

require_once('BasicResource.php');

use Tonic\Resource,
    Tonic\Response;

/**
 * Run administrative tasks 
 * @uri /admin/{command}
 */
class Admin extends BasicResource{

	/**
	 * Run an administrative task based on the requested command
	 * @method POST
	 * @auth
	 */
	public function dispatch($command){
		switch($command) {
			case 'refreshsourcedata': return $this->refreshSourceData($_POST['path']);
			case 'refreshdatabase': return $this->refreshDatabase();
			default:
				return new Response(Response::NOTFOUND, "Command '$command' not recognized");
		}
	}

	// Pull down the raw csv file used to populate the sample database
	// This pulls the data from the given path and puts it in the data directory
	private function refreshSourceData($path) {
		file_put_contents('../data/sample.csv', file_get_contents($path));
		return new Response(Response::CREATED, "Source data synchronization from $path complete.");
	}

	// Reload the database from scratch
	private function refreshDatabase() {
		try {
			$message = $this->db->refreshDatabase();
		} catch (Exception $ex) {
			throw new Tonic\Exception($ex->getMessage());
		}
		return new Response(Response::CREATED, $message);
	}

}

