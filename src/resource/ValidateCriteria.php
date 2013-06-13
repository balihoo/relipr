<?php

/* This class implements a resource endpoint for validating criteria specifications
*/

require_once('BasicResource.php');

use Tonic\Resource,
    Tonic\Response;

/**
 * Expose the validation functionality
 * @uri /validatecriteria
 */
class ValidateCriteria extends BasicResource{

	/**
	 * Read in the criteria and run the CriteriaValidator
	 * @method POST
	 */
	public function post(){
		$criteriajson = isset($_POST['criteriajson']) ? trim($_POST['criteriajson']) : null;
		$criteriaurl = isset($_POST['criteriaurl']) ? trim($_POST['criteriaurl']) : null;
		$format = isset($_POST['format']) ? trim($_POST['format']) : 'RAW';

		if ($criteriaurl != "" && $criteriajson == "") {
			if(strtolower(substr($criteriaurl, 0, 4)) != 'http')
				return new Response(400, "URL should start with http");
			$criteriajson = file_get_contents($criteriaurl);
		}
		
		if($criteriajson != "") {
			require_once 'CriteriaValidator.php';
			$val = new CriteriaValidator();
			try {
				$val->validateJSON($criteriajson);
			} catch (CriteriaValidationException $ex) {
				// Don't need to do anything here
			}
			$view = $this->getView($format == 'HTML' ? 'validationresultshtml' : 'validationresults');
			$view->json = $criteriajson;
			$view->format = $format;
			$view->val = $val;
			$view->render();
		} else {
			return new Response(400, "Unable to validate criteria - please provide either JSON or URL");
		}

	}

	/**
	 * Render a helper page to post criteria
	 * @method GET
	 */
	public function get() {
		$view = $this->getView('validatecriteria');
		$view->render();
	}

}

