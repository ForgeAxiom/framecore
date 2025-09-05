<?php

declare(strict_types=1);

// Front Controller

require_once __DIR__ . '/../vendor/autoload.php';

use ForgeAxiom\Framecore\Core\Container;
use ForgeAxiom\Framecore\Routing\Router;
use ForgeAxiom\Framecore\Routing\RoutesCollection;


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
    /** @var \ForgeAxiom\Framecore\Routing\RoutesCollection $routes */
    $routes = $c->get(RoutesCollection::class);
    return new Router(
        $routes,
        $c
    );
});

/** 
 * Yours bindings
 */



/**
 * Framework output
 */

$router = $container->get(Router::class, $requestUri, $requestMethod);

/** @var \ForgeAxiom\Framecore\Routing\Router $router */
$response = $router->handleUri($requestUri, $requestMethod);
$responseCode = $response->httpResponseCode;
$responseView = $response->view;

http_response_code($responseCode);
if ($responseView !== null) {
    echo $responseView->getView();
}

?>

