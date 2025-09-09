<?php

declare(strict_types=1);

// Front Controller

require_once __DIR__ . '/../vendor/autoload.php';

use ForgeAxiom\Framecore\Core\Container;
use ForgeAxiom\Framecore\Database\Connection\Connection;
use ForgeAxiom\Framecore\Database\Schema\SchemaReader;
use ForgeAxiom\Framecore\Database\Schema\TableReaderSqlite;
use ForgeAxiom\Framecore\Routing\Router;
use ForgeAxiom\Framecore\Routing\RoutesCollection;
use function ForgeAxiom\Framecore\Helpers\dd; 

$requestUri = $_SERVER['REQUEST_URI' ?? '/'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

$container = new Container();

/**
 * Framework bindings
 */

$container->singleton(RoutesCollection::class, function() {
    return new RoutesCollection();
});

$container->bind(Router::class, function(Container $c) {
    /** @var RoutesCollection $routes */
    $routes = $c->get(RoutesCollection::class);
    
    return new Router($routes, $c);
});

// Database connection

$container->singleton(Connection::class, function() {
    return new Connection('sqlite:' . __DIR__ . '/../database.db');
});

$container->bind(SchemaReader::class, function(Container $c) {
    /** @var Connection $connection*/
    $connection = $c->get(Connection::class); 
    /** @var TableReaderSqlite $tableReader */
    $tableReader = $c->get(TableReaderSqlite::class);

    return new SchemaReader($connection, $tableReader);
});

/** 
 * Yours bindings
 */



/**
 * Framework output
 */

$router = $container->get(Router::class);

/** @var \ForgeAxiom\Framecore\Routing\Router $router */
$response = $router->handleUri($requestUri, $requestMethod);
$responseCode = $response->httpResponseCode;
$responseView = $response->view;

http_response_code($responseCode);
if ($responseView !== null) {
    echo $responseView->getView();
}

?>

