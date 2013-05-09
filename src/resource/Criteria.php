<?php

require_once('BasicResource.php');

use Tonic\Resource,
    Tonic\Response,
    Tonic\ConditionException,
		Tonic\UnauthorizedException;

/**
 * This class defines the criteria resource
 * A criteria resource is a specification for the list selectio criteria form
 *
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
		// Affiliatenumber is optional, if not set then this is a nation campaign
		$affiliatenumber = isset($this->affiliatenumber) ? $this->affiliatenumber : null;

		// Get the criteria specification and return it as the response
		$criteria = CriteriaBuilder::getCriteria($this->medium, $this->brandkey, $this->criteriaid, $affiliatenumber);
		return new Response(Response::OK, $criteria);
	}

}

