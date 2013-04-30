<?php

require_once('BasicResource.php');

use Tonic\Resource,
    Tonic\Response,
    Tonic\ConditionException;

/**
 * @uri /medium
 */
class Medium extends BasicResource{

	/**
	 * Get a list of mediums
	 * @method GET
	 * @auth
	 * @valid
	 * @json
	 */
	public function get(){
		return new Response(Response::OK, $this->db->getMediums());
	}

}

