<?php

require_once('BasicResource.php');

use Tonic\Resource,
    Tonic\Response;

/**
 * Display a nice little help file
 * @uri /help
 * @uri /docs
 */
class Help extends BasicResource{

	/**
	 * Produce the help page
	 * @method GET
	 */
	public function get(){
		$view = $this->getView('help');
		$view->render();
	}

}

