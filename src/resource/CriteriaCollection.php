<?php

require_once('BasicResource.php');

use Tonic\Resource,
    Tonic\Response,
    Tonic\ConditionException;

/**
 * This class defines the criteria collection resource
 * @uri /medium/{medium}/brand/{brandkey}/criteria
 */
class CriteriaCollection extends BasicResource{

	/**
	 * Get a list of criteria objects
	 * @method GET
	 * @auth
	 * @valid
	 * @json
	 */
	public function get($medium, $brandkey){
		$criteria = $this->db->getCriteria($medium, $brandkey);
		return new Response(Response::OK, $criteria);
	}

}

