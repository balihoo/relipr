<?php

require_once('BasicResource.php');

use Tonic\Response,
	Tonic\NotFoundException;

/**
 * This class defines the list resource, you can create and get lists
 * @uri /medium/:medium/brand/:brandkey/criteria/:criteriaid/list
 * @uri /medium/:medium/brand/:brandkey/criteria/:criteriaid/list/:listid
 */
class ListResource extends BasicResource{

	/**
	 * Create a new list for the given parameters
	 * @method POST
	 * @auth
	 * @valid
	 * @json
	 */
	public function post(){
		// Make sure that the user is not trying to POST to a list instance
		if($this->listid !== null)
			return new Response(Response::BADREQUEST, "You can't POST to an existing list");

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
		$callback = null;
		if(isset($_POST['callback']) && trim($_POST['callback']) != '')
			$callback = trim($_POST['callback']);

		// Pull out the order, creative and affiliate info
		$orderinfo = (!isset($_POST['orderinfo']) || trim($_POST['orderinfo']) == '')
			? '[]' : $_POST['orderinfo'];
		$creativeinfo = (!isset($_POST['creativeinfo']) || trim($_POST['creativeinfo']) == '')
			? '[]' : $_POST['creativeinfo'];
		$affiliateinfo = (!isset($_POST['affiliateinfo']) || trim($_POST['affiliateinfo']) == '')
			? '[]' : $_POST['affiliateinfo'];

		// Create a template for the list constructor
		$listArray = array(
			'listid' => null,
			'count' => null,
			'brandkey' => $this->brandkey,
			'criteriaid' => $this->criteriaid,
			'medium' => $this->medium,
			'requestedcount' => $_POST['requestedcount'],
			'isestimate' => null,
			'cost' => null,
			'status' => ListDTO::STATUS_SUBMITTED,
			'callback' => $callback,
			'filter' => $_POST['filter'],
			'orderinfo' => $orderinfo,
			'creativeinfo' => $creativeinfo,
			'affiliateinfo' => $affiliateinfo,
			'columns' => $_POST['columns'],
		);

		try {
			// Turn the list template into a list object
			// May throw an exception if params are not right
			$list = ListDTO::fromArray($listArray, false);

			// Try to save the list - throws if it can't save
			$this->db->saveList($list);
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
		// Make sure that the listid was specified
		if($this->listid === null)
			throw new NotFoundException("Not found: you can't GET a list without specifying a listid");

		// Try to find the list and send it back as the response
		$list = $this->db->getList($this->medium, $this->brandkey, $this->criteriaid, $this->listid);
		return new Response(Response::OK, $list);
	}

}

