<?php

require_once('BasicResource.php');

use Tonic\Response;

/**
 * This class defines the actions available on a list
 * @uri /medium/:medium/brand/:brandkey/criteria/:criteriaid/list/:listid/:command
 */
class ListAction extends BasicResource{

	/**
	 * Dispatch a list action
	 * @method POST
	 * @auth
	 * @valid
	 * @json
	 */
	public function dispatch(){
		switch($this->command) {
			case 'submit': return $this->submitList();
			case 'cancel': return $this->cancelList();
			default:
				throw new NotFoundException("List action '{$this->command}' not found");
		}
	}

	private function submitList() {
		$list = $this->db->submitList($this->medium, $this->brandkey, $this->criteriaid, $this->listid);
		return new Response(Response::OK, $list);
	}

	private function cancelList() {
		$list = $this->db->getList($this->medium, $this->brandkey, $this->criteriaid, $this->listid);
		$this->db->cancelList($list);
		return new Response(Response::OK, $list);
	}

}

