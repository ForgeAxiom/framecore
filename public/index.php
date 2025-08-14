<?php

declare(strict_types=1);

// Front Controller

require_once __DIR__ . '/../vendor/autoload.php';

use ForgeAxiom\Framecore\Routing\Router;
use ForgeAxiom\Framecore\Routing\RoutesCollection;

$requestUri = $_SERVER['REQUEST_URI' ?? '/'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

$router = new Router($requestUri, $requestMethod, new RoutesCollection());

$response = $router->handleUri();
$responseCode = $response->httpResponseCode;
$responseView = $response->view;

http_response_code($responseCode);
if ($responseView !== null) {
    echo $responseView->getView();
}

?>

