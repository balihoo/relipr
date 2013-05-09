<?php

/* This class implements a resource endpoint for invoking background processes.
	Because these are exposed as REST resources, it is very easy to
	invoke them from cron using cUrl.
*/

require_once('BasicResource.php');

use Tonic\Resource,
    Tonic\Response;

/**
 * Run recurring jobs
 * @uri /jobs/{jobname}
 */
class Jobs extends BasicResource{

	/**
	 * Run a recurring job based on the given job name
	 * @method POST
	 * @auth
	 * @json
	 */
	public function dispatch($jobname){
		// Determine which action to take base on the provided job name
		switch($jobname) {
			case 'callback': return $this->executeCallbacks();
			case 'count': return $this->executeCounts();
			case 'ready': return $this->prepareLists();
			default:
				return new Response(Response::NOTFOUND, "Job '$jobname' not recognized");
		}
	}

	// Find lists that are submitted and count them
	private function executeCounts() {
		$lists = $this->db->findLists(ListDTO::STATUS_SUBMITTED);
		foreach($lists as $list)
			$this->db->countList($list);
		return $this->executeCallbacks();
	}

	// Find lists that are in the final count status and update them to ready
	// The delay between submit, count and ready is artificial, but useful for
	// testing and demonstration purposes
	private function prepareLists() {
		$lists = $this->db->findLists(ListDTO::STATUS_FINALCOUNT);
		foreach($lists as $list) {
			$list->status = ListDTO::STATUS_LISTREADY;
			$this->db->saveList($list, array('readied = datetime()'));
		}
		return $this->executeCallbacks();
	}

	// Find lists that have been canceled, counted or made ready for download and notify client
	private function executeCallbacks() {
		$lists = $this->db->getPendingCallbacks();
		$result = array();

		// Run through all of the lists that need a callback executed
		foreach($lists as $list) {
			// Check for lists that were canceled
			if($list->getCanceled() != null && $list->getCancelNotified() == null)
				$result[] = $this->executeCallback($list, 'cancel');
			// Check for lists that have been counted
			if($list->getCounted() != null && $list->getCountNotified() == null)
				$result[] = $this->executeCallback($list, 'count');
			// Check for lists that are ready
			if($list->getReadied() != null && $list->getReadyNotified() == null)
				$result[] = $this->executeCallback($list, 'ready');
		}
		return new Response(Response::OK , $result);
	}

	// Run any pending callbacks, sending a POST to the registered
	// callback url for each list
	private function executeCallback(ListDTO $list, $event) {
		// Keep track of the results of this callback
		$detail = array('listid' => $list->listid, 'event' => $event);

		// Set up the body of the POST message
		$body = json_encode(array(
			'event' => $event,
			'list' => $list
		));

		// Initialize a curl session used to invoke the callback
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $list->callback);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

		// Execute the curl callback and capture any errors
		$result = curl_exec($ch);
		$detail['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		// Update the results for this callback
		if($result === FALSE || $detail['code'] != 200) {
			$detail['result'] = 'fail';
			$detail['message'] = $result ? substr($result, 0, 100) : $error;
			$this->db->saveList($list, array("callbackfailures = callbackfailures + 1"));
		} else{
			$detail['result'] = 'success';
			$detail['message'] = $result;
			$this->db->saveList($list, array("callbackfailures = 0", "{$event}notified = datetime()"));
		}
		return $detail;
	}

}

