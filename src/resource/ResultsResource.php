<?php

require_once('BasicResource.php');

use Tonic\Response,
	Tonic\NotFoundException;

/**
 * This class defines the results resource, you can pull a list of results
 * @uri /results
 */
class ResultsResource extends BasicResource{
	/**
	 * Get a list object by its id
	 * @method GET
	 * @auth
	 * @json
	 */
	public function get(){
		// Grab the last 200 results and return them
		$results = $this->db->getResults(200);
		return new Response(Response::OK, $results);
	}

}

