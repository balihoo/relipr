<?php

require_once 'PestJSON.php';
$errCount = 0;

// Read in the command line parameters
if(count($argv) < 6)
	die("Usage: php validator.php <baseurl> <username> <password> <medium> <brandkey>");
list($app, $url, $username, $password, $medium, $brandkey) = $argv;

// Make sure that auth fails when the wrong password is used
info("Ensuring that bad auth fails");
$badpest = new PestJSON($url);
$badpest->setupAuth($username, "*$password*");
try {
	$response = $badpest->get("medium/$medium/brand/$brandkey/criteria");
	error("Expected authentication failure, but got an Ok response");
} catch(Pest_Unauthorized $ex) {
	info("Properly failed to authenticate");
}

// This time authenticate properly
info("Setting up REST connection");
$pest = new PestJSON($url);
$pest->setupAuth($username, $password);

// Make sure that bad brand returns a not found exception
try {
	$pest->get("medium/$medium/brand/XYXYX/criteria");
	error("Expected brand not found, but got an Ok response");
} catch(Pest_NotFound $ex) {
	info("Properly failed to find bad brand");
}

// Make sure that bad medium returns a not found exception
try {
	$pest->get("medium/SDFOJ/brand/$brandkey/criteria");
	error("Expected medium not found, but got an Ok response");
} catch(Pest_NotFound $ex) {
	info("Properly failed to find bad medium");
}

// Make sure that bad criteriaid returns a not found exception
try {
	$pest->get("medium/$medium/brand/$brandkey/criteria/FDSF12");
	error("Expected brand not found, but got an Ok response");
} catch(Pest_NotFound $ex) {
	info("Properly failed to find bad criteria");
}

// Get a criteria collection for this medium/brand
info("Grabbing a list of $medium criteria for $brandkey");
$criteriaCollection = $pest->get("medium/$medium/brand/$brandkey/criteria");
// Make sure that the response is an array
if(!is_array($criteriaCollection)) {
	error("Expected an array of criteria (even empty array would be ok), but got " . get_class($criteriaCollection));
} else {
	info("Found " . count($criteriaCollection) . " criteria specs");
	foreach($criteriaCollection as $criteria) {
		// Make sure that each criteria that we get back is in good order
		$criteriaid = checkCriteria($criteria);

		if($criteriaid) {
			// Make sure that we can grab the criteria specific resource for each of these
			info("Grabbing the criteria specific resource");
			$directCriteria = $pest->get("medium/$medium/brand/$brandkey/criteria/$criteriaid");

			// Do a top level comparison to make sure the objects are equal
			info("Comparing the direct and collection version of $criteriaid");
			$diff = array_diff_assoc($criteria, $directCriteria);
			if(count($diff) > 0)
				error("The direct resource for criteria $criteriaid is different from the version found on the collection");

			// Try to create a list for this criteria
			checkList($pest, $medium, $brandkey, $criteriaid);
		}
	}
}

function checkCriteria($criteria) {
	if(!array_key_exists('criteriaid', $criteria)) {
		error("Expected the criteria object to have a criteriaid, but didn't get one. That really stinks.");
		$id = null;
	} else {
		$id = $criteria['criteriaid'];
	}
	info("Checking criteria for '$id'");

	if(!array_key_exists('title', $criteria))
		error("Criteria '$id' does not have a 'title'");

	if(!array_key_exists('description', $criteria))
		error("Criteria '$id' does not have a 'description'");

	if(!array_key_exists('criteria', $criteria))
		error("Criteria '$id' does not have a 'criteria' array");
	else if(!is_array($criteria['criteria']))
		error("Criteria '$id' has a 'criteria' property that is not an array!");
	else {
		$order = 0;
		foreach($criteria['criteria'] as $criterion) {
			checkCriterion($id, $order++, $criterion);
		}
	}
	return $id;
}

function checkCriterion($criteriaid, $order, $criterion) {
	if(!array_key_exists('type', $criterion)) {
		error("Expected criterion[$order] of $criteriaid to hav a type, but not title was found");
		$type= null;
	} else {
		$type= $criterion['type'];
	}

	if($type == 'section') {
		info("Found a 'section'");
		return;
	}

	if(!array_key_exists('criterionid', $criterion)) {
		error("Expected criterion[$order] of $criteriaid to have a criterionid, but it didn't.");
		$id = '*undefined*';
	} else {
		$id = $criterion['criterionid'];
	}
	info("Checking criterion '$id'");
}

function checkList($pest, $medium, $brandkey, $criteriaid) {
	info("Trying to create a new list for $criteriaid");
	$count = rand(50, 100);
	$data = array(
		'filter' => '[]xyz',
		'columns' => '*',
		'requestedcount' => $count
	);

	// make sure it pukes with an unparseable filter
	try {
		$list = $pest->post("medium/$medium/brand/$brandkey/criteria/$criteriaid/list", $data);
		error("Expected bad request on unparseable JSON, but got Ok response");
	} catch(Pest_BadRequest $ex) {
		info("Unparseable JSON properly returned bad request code");
	}

	// make sure it pukes when filter is not set
	unset($data['filter']);
	try {
		$list = $pest->post("medium/$medium/brand/$brandkey/criteria/$criteriaid/list", $data);
		error("Expected bad request with missing filter, but got Ok response");
	} catch(Pest_BadRequest $ex) {
		info("Missing filter properly returned bad request code");
	}

	// Fix the filter param
	$data['filter'] = '[]';

	// make sure it pukes when columns is not set
	unset($data['columns']);
	try {
		$list = $pest->post("medium/$medium/brand/$brandkey/criteria/$criteriaid/list", $data);
		error("Expected bad request with missing columns, but got Ok response");
	} catch(Pest_BadRequest $ex) {
		info("Missing columns properly returned bad request code");
	}

	// Fix the columns param
	$data['columns'] = '*';

	// make sure it pukes when requested count is not set
	unset($data['requestedcount']);
	try {
		$list = $pest->post("medium/$medium/brand/$brandkey/criteria/$criteriaid/list", $data);
		error("Expected bad request with missing requestedcount, but got Ok response");
	} catch(Pest_BadRequest $ex) {
		info("Missing requestedcount properly returned bad request code");
	}

	// Fix the requestedcount param
	$data['requestedcount'] = $count;

	$list = $pest->post("medium/$medium/brand/$brandkey/criteria/$criteriaid/list", $data);

	if(!array_key_exists('listid', $list)) {
		error("Expected new list to have listid, but didn't get one.");
	} else {
		$listid = $list['listid'];
		info("Created new list with id $listid");

		// Make sure that get returns the list object
		if(!array_key_exists('links', $list) || !array_key_exists('self', $list['links'])) {
			error("Expected to find a self link on list $listid, but none was provided");
		} else {
			$self = $list['links']['self'];
			$link = clone $pest;
			$link->base_url = $self;
			info("Getting list with id $listid");
			$newList = $link->get('');
			$diff = array_diff_assoc($newList, $list);
			unset($diff['status']);
			if(count($diff) > 0)
				error('The list we pulled back with get was more different than expected' . print_r($diff, true));
		}
	}

}

function info($message) {
	echo "INFO: $message\n";
}

function error($message) {
	global $errCount;
	$errCount++;
	error_log("ERROR: $message\n");
}

