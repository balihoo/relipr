<?php
/* This script acts as the main entry point for all application endpoints.
	We are using the open source Tonic library to implement REST in PHP.
	For more info on Tonic see: https://github.com/peej/tonic

	The .htaccess file uses mod rewrite to direct all non-static traffic to this dispatcher
*/

// Set up the include paths to make including dependencies a little easier
$basePath = realpath(dirname(__FILE__) . "/../src/");
set_include_path(
	get_include_path() . PATH_SEPARATOR . 
	$basePath . "/lib" . PATH_SEPARATOR .
	$basePath . "/lib/criteria" . PATH_SEPARATOR .
	$basePath . "/lib/Tonic"
);

// Include the Tonic autoloader
require_once 'Autoloader.php';

// Configure Tonic, tell it which files to scan for resource definitions
$config = array(
    'load' => array(
        '../src/resource/*.php', // load example resources
    ),
);

// Check for X-CHAOS header
$chaosHeader = 'HTTP_X_CHAOS';
if(array_key_exists($chaosHeader, $_SERVER))
	require_once 'chaos.php';

// Create a tonic app and request
$app = new Tonic\Application($config);
$request = new Tonic\Request();

// Handle tonic exceptions by responding appropriately
try {
	// Get and execute the appropriate resource object
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

// Be sure to close the database connection
DB::close();

