<?php

require_once('BasicResource.php');

use Tonic\Resource,
    Tonic\Response,
    Tonic\ConditionException,
		Tonic\UnauthorizedException;

/**
 * This class defines the criteria 
 * @uri /medium/:medium/brand/:brandkey/criteria/:criteriaid
 * @uri /medium/:medium/brand/:brandkey/affiliate/:affiliatenumber/criteria/:criteriaid
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
		$affiliatenumber = isset($this->affiliatenumber) ? $this->affiliatenumber : null;
		$criteria = CriteriaBuilder::getCriteria($this->medium, $this->brandkey, $this->criteriaid, $affiliatenumber);
		return new Response(Response::OK, $criteria);
	}

}

