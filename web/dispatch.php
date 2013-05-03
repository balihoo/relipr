<?php

// This is some seriously ghetto security while I'm developing...
// It's called IP address white labeling (and no, it is not racist)
if(isset($_SERVR)) {
	$accessList = array('192.168.1.1', '127.0.0.1');
	if(!in_array($_SERVER['REMOTE_ADDR'], $accessList)) {
		header("HTTP/1.0 403 Forbidden");
		echo "<h1>Access Verboten</h1>";
		echo $_SERVER['REMOTE_ADDR'];
		exit;
	}
}

// Load the composer autoload script
// This will give me access to all the classes I need, especially tonic!

$basePath = realpath(dirname(__FILE__) . "/../src/");
set_include_path(
	get_include_path() . PATH_SEPARATOR . 
	$basePath . "/lib" . PATH_SEPARATOR .
	$basePath . "/lib/criteria" . PATH_SEPARATOR .
	$basePath . "/lib/Tonic"
);

require_once 'Autoloader.php';

$config = array(
    'load' => array(
        '../src/resource/*.php', // load example resources
    ),
);

// Create a tonic app and request
$app = new Tonic\Application($config);
$request = new Tonic\Request();

// Handle tonic exceptions by responding appropriately
try {
	$resource = $app->getResource($request);
	$response = $resource->exec();
} catch (Tonic\NotFoundException $e) {
	$response = new Tonic\Response(404, $e->getMessage());
} catch (Tonic\UnauthorizedException $e) {
	$response = new Tonic\Response(401, $e->getMessage());
	$response->wwwAuthenticate = 'Basic realm="API Server"';
} catch (Tonic\Exception $e) {
	$response = new Tonic\Response($e->getCode(), $e->getMessage());
}

// Spit out the response
$response->output();

