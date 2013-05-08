<?php

require_once('BasicResource.php');

use Tonic\Response;

/**
 * This class defines the list resource 
 * @uri /medium/:medium/brand/:brandkey/criteria/:criteriaid/list
 * @uri /medium/:medium/brand/:brandkey/criteria/:criteriaid/list/:listid
 */
class ListResource extends BasicResource{

	/**
	 * Create a new list for the given criteria
	 * @method POST
	 * @auth
	 * @valid
	 * @json
	 */
	public function post(){
		// Get the criteria filter
		if(!isset($_POST['filter']) || trim($_POST['filter']) == '')
			return new Response(Response::BADREQUEST, "Missing or empty 'filter'");

		// Get the columns
		if(!isset($_POST['columns']) || trim($_POST['columns']) == '')
			return new Response(Response::BADREQUEST, "Missing or empty 'columns'");

		// Get the requestednount
		if(!isset($_POST['requestedcount']) || trim($_POST['requestedcount']) == '')
			return new Response(Response::BADREQUEST, "Missing or empty 'requestedcount'");

		// Get the callback url
		if(!isset($_POST['callback']) || trim($_POST['callback']) == '')
			return new Response(Response::BADREQUEST, "Missing or empty 'callback'");


		try {
			$list = $this->db->createList($_POST['filter'], $this->medium, $this->brandkey, $this->criteriaid,
				$_POST['columns'], $_POST['requestedcount'], $_POST['callback']);
			return new Response(Response::CREATED, $list);
		} catch (Exception $ex) {
			return new Response($ex->getCode(), $ex->getMessage());
		}
	}

	/**
	 * Get a list object by its id
	 * @method GET
	 * @auth
	 * @valid
	 * @json
	 */
	public function get(){
		$list = $this->db->getList($this->medium, $this->brandkey, $this->criteriaid, $this->listid);
		return new Response(Response::OK, $list);
	}

}

