<?php

require_once('BasicResource.php');
require_once('Selection.php');

use Tonic\Response;

/**
 * This class defines the list resource 
 * @uri /medium/:medium/brand/:brandkey/criteria/:criteriaid/list
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
		// Get the criteria selection
		if(!isset($_POST['selections']) || trim($_POST['selections']) == '')
			return new Response(Response::BADREQUEST, "Missing or empty 'selections'");
		try {
			$selection = new Selection($_POST['selections']);
		} catch (Exception $ex) {
			// Something bad happened while trying to pre-parse the criteria selections
			return new Response($ex->getCode(), $ex->getMessage());
		}

		//$affiliateid = isset($this->affiliateid) ? $this->affiliateid : null;
		//$criteria = $this->db->getCriteria($this->medium, $this->brandkey, $this->criteriaid, $affiliateid);
		$list = $this->db->createList($selection, $this->medium, $this->brandkey, $this->criteriaid, 100);
		return new Response(Response::CREATED, $list);
	}

}

