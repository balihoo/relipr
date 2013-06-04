<?php

// Get the value of the chaos header
$_CHAOS = $_SERVER[$chaosHeader];

// Set up the error mapper
$errors = array(
	'500' => 'server error',
	'503' => 'gateway timeout - throttle',
	'404' => 'not found',
	'403' => 'forbidden',
	'401' => 'unauthorized',
	'400' => 'bad request',
	'304' => 'not modified',
	'BADFORMAT' => 'return poorly formatted criteria',
	'BADCONFIG' => 'return incorrect criteria configurations'
);

// If RAND is chosen then 
if(preg_match('/^RAND([0-9]+)$/', $_CHAOS, $matches)) {
	$rate = intval($matches[1]);
	if(rand(1, $rate) == 1) {
		$keys = array_keys($errors);
		$_CHAOS= $keys[rand(0, count($errors) - 1)];
	}
}

// If the value in the chaos header is mapped, then just kick it out
if(array_key_exists($_CHAOS, $errors) && is_numeric($_CHAOS) && intval($_CHAOS) == $_CHAOS) {
	$response = new Tonic\Response(intval($_CHAOS), $errors[$_CHAOS]);
	$response->output();
	exit;
}

