<?php

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

	private function refreshSourceData($path) {
		file_put_contents('../data/sample.csv', file_get_contents($path));
		return new Response(Response::CREATED, "Source data synchronization from $path complete.");
	}

	private function refreshDatabase() {
		try {
			$message = $this->db->refreshDatabase();
		} catch (Exception $ex) {
			throw new Tonic\Exception($ex->getMessage());
		}
		return new Response(Response::CREATED, $message);
	}

}

