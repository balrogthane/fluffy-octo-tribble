<?php

namespace LaughingWolf\API;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../autoload.php';

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use LaughingWolf\API\Helpers;

$dotenv = \Dotenv\Dotenv::createImmutable( __DIR__ . "/../" );
$dotenv->load( );

$GLOBALS['app'] = (object) [
	'router'		=>	AppFactory::create( ),
	'middleware'	=>	(object) [ ],
	'models'		=>	(object) [ ],
	'controllers'	=>	(object) [ ]
];

// Connect to database
// Generate the PDO handle
$dbhost = getenv( "MYSQL_HOST" );
$dbuser = getenv( "MYSQL_USERNAME" );
$dbpass = getenv( "MYSQL_PASSWORD" );
$dbcharset = 'utf8mb4';
$dbpassHashed = hash( 'sha256', $dbpass );
// error_log( "Connecting to mysql:$dbhost;charset=$dbcharset as $dbuser and authenticating with $dbpass" );
$dbconn = new \PDO( "mysql:host=$dbhost", $dbuser, $dbpass,
					array( \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ) );

// Load middleware
// Last middleware loaded is the first to be called on a request
//   & the last to be called on a response

// Load non-PECL YAML parser
//require_once "../";

// Parse incoming body as JSON app-wide
require_once "../middleware/parse-json.php";
$GLOBALS['app']->router->add( $GLOBALS['app']->middleware->parseJson );

// Remove trailing slash app-wide
require_once "../middleware/remove-trailing-slash.php";
$GLOBALS['app']->router->add( $GLOBALS['app']->middleware->removeTrailingSlash );

// Middleware to return JSON content type
require_once "../middleware/return-json.php";

// Load all models
foreach ( glob( "../models/*.php" ) as $filename ) {
	require_once $filename;

	$model = Helpers::extractFilename( $filename );

	error_log("Including file $filename for model $model");

	$GLOBALS['app']->models->{strtolower($model)} = new $model( $dbconn );
}
// Load all controllers
foreach ( glob( "../controllers/*.php" ) as $filename ) {
	require_once $filename;

	$controller = Helpers::extractFilename( $filename );

	error_log("Including file $filename for controller $controller");
}

$GLOBALS['app']->router->get( '/', function ( Request $req, Response $res, $params ) {
	$res->getBody( )->write( "Hello world!" );
	return $res;
});	

$GLOBALS['app']->router->post( '/', function ( Request $req, Response $res, $params ) {
	$res->getBody( )->write( "Hello post-world! You provided the following body: " . json_encode( $req->getParsedBody( ) ) );
	return $res;
});

$GLOBALS['app']->router->put( '/', function ( Request $req, Response $res, $params ) {
	$res->getBody( )->write( "You can't PUT in what God leaves out! Also, you said: " . json_encode( $req->getParsedBody( ) ) );
	return $res;
});

$GLOBALS['app']->router->run( );