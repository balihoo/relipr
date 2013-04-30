<?php

require_once('BasicResource.php');

use Tonic\Resource,
    Tonic\Response,
    Tonic\ConditionException,
		Tonic\UnauthorizedException;

/**
 * This class defines the criteria 
 * @uri /medium/:medium/brand/:brandkey/criteria/:criteriaid
 * @uri /medium/:medium/brand/:brandkey/affiliate/:affiliateid/criteria/:criteriaid
 */
class Criteria extends BasicResource{

	/**
	 * Get a single criteria object
	 * @method GET
	 * @auth
	 * @valid
	 * @json
	 */
	public function get(){
		$affiliateid = isset($this->affiliateid) ? $this->affiliateid : null;
		$criteria = $this->db->getCriteria($this->medium, $this->brandkey, $this->criteriaid, $affiliateid);
		return new Response(Response::OK, $criteria);
	}

}

