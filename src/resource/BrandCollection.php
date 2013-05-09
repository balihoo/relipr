<?php

require_once('BasicResource.php');

use Tonic\Resource,
    Tonic\Response,
    Tonic\ConditionException;

/**
 * The brand resource just gets a list of available brands
 * @uri /brand
 */
class BrandCollection extends BasicResource{

	/**
	 * Get a list of brands
	 * @method GET
	 * @auth
	 * @valid
	 * @json
	 */
	public function get(){
		return new Response(Response::OK, $this->db->getBrands());
	}

}

