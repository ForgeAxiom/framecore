<?php

declare(strict_types=1);

namespace ForgeAxiom\Framecore\Routing;

use ForgeAxiom\Framecore\Core\Container;
use ForgeAxiom\Framecore\Exceptions\Container\NotBoundException;
use ForgeAxiom\Framecore\Exceptions\Container\UnresolvableDependencyException;
use ForgeAxiom\Framecore\Exceptions\HttpException;
use ForgeAxiom\Framecore\Exceptions\InvalidConfigReturnException;
use ForgeAxiom\Framecore\Exceptions\NotInstantiableException;
use ForgeAxiom\Framecore\Exceptions\SignatureHasNoTypeSet;
use ForgeAxiom\Framecore\View\View;
use ReflectionException;

/** 
 * Service. 
 * Handles incoming HTTP requests by matching them with defined routes
 * and dispatches it to appropriate controller.  
 */
class Router
{
    private array $routes;
    private Container $container;

    /**
     * @param RoutesCollection $routesCollection Prepared routes collection.
     * @param Container $container DI-container.
     */
    public function __construct(
        RoutesCollection $routesCollection,
        Container $container
    ) {
        $this->container = $container;
        $this->routes = $routesCollection->getRoutes();   
    }

    /**
     *
     * Handles HTTP request by routing it to the appropriate controller.
     *
     * Dispatches HTTP request to appropriate controller method if match with config found.
     * Otherwise, generates a 404 Not Found Response.
     *
     * @param string $requestUri Requested uri.
     * @param string $httpMethod Http method.
     * 
     * @return Response The final HTTP response.
     *
     * @throws InvalidConfigReturnException If a controller defined in routes configuration is not a valid instance of Controller.
     * @throws HttpException If http method not get, post, put or delete.
     * @throws ReflectionException If the class does not exist.
     * @throws SignatureHasNoTypeSet If constructor has no parameter type set.
     * @throws NotInstantiableException If class abstract or interface.
     * @throws UnresolvableDependencyException If parameter type in constructor is a scalar, union, or intersection.
     * @throws NotBoundException If auto resolving was off and requested class was not bounded.
     */
    public function handleUri(string $requestUri, string $httpMethod): Response 
    {
        $routesByRequestMethod = $this->getRoutesByRequestMethod($httpMethod);

        foreach ($routesByRequestMethod as $route) {
            $routeUri = $route['uri'];

            $match = $this->matchUriAndPullParams($requestUri, $routeUri);

            // If request uri IS matches with route uri
            if ($match['success'] === true) {
                $controllerClassName = $route['controller'];

                $controller = $this->container->get($controllerClassName);
                
                $controllerMethod = $route['controllerMethod'];
                
                if ($controller instanceof Controller) {
                    $args = $match['params'];
                    
                    $controllerResponse = $controller->$controllerMethod(...$args);

                    return $this->handleControllerResponse($controllerResponse);
                } else {
                    throw new InvalidConfigReturnException(sprintf(
                        "Only instance of %s, given: %s",
                        Controller::class,
                        $controllerClassName
                    ));
                }
            } 
        }

        // If request uri NOT matches with route uri
        return Response::notFound();
    }

    /**
     * Retrieves routes filtered by request HTTP method.
     *
     * @return array<
     *      array{
     *          uri: string,
     *          controller: class-string,
     *          controllerMethod: string
     *      }
     * > The array of routes with appropriate http method.
     *
     * @throws HttpException If http method not get, post, put or delete.
     */
    private function getRoutesByRequestMethod($httpMethod): array
    {
        $httpMethod = trim(strtolower($httpMethod));
        return match ($httpMethod) {
            'get' => $this->routes['get'] ?? [],
            'post' => $this->routes['post'] ?? [],
            'put' => $this->routes['put'] ?? [],
            'delete' => $this->routes['delete'] ?? [],
            default => throw new HttpException("Error Processing Request. Wrong HTTP Method: {$httpMethod}"),
        };
    }

    /**
     * Matches passed argument between them. Retrieves URI params defined in routes configuration.
     *
     * @param string $requestUri Client request URI.
     * @param string $routeUri Configuration URI.
     * @return array{
     *      success: bool, 
     *      params?: array<int|string>
     * } An associate array containing a 'success' boolean and 'params' (if success).
     */
    private function matchUriAndPullParams(string $requestUri, string $routeUri): array
    {
        $formattedRequestUri = $this->formatUri($requestUri); 
        $formattedRouteUri = $this->formatUri($routeUri);

        $uriParams = [];

        if (count($formattedRequestUri) !== count($formattedRouteUri)) {
            return [
                'success' => false
            ];
        }

        $count = count($formattedRequestUri);

        for ($i = 0; $i != $count; $i++) {
            $requestUriValue = $formattedRequestUri[$i];
            $routeUriValue = $formattedRouteUri[$i];

            $isParam = preg_match('/^{.*}$/', $routeUriValue);
            if ($isParam) {
                $uriParams[] = $requestUriValue;
                continue;
            }

            if ($requestUriValue !== $routeUriValue) {
                return [
                    'success' => false
                ];
            }
        }

        return [
            'success' => true,
            'params' => $uriParams
        ];
    }

    /**
     * Trims '/' and explodes by '/' 
     *
     * @param string $uri
     * @return array
     */
    private function formatUri(string $uri): array
    {
        return explode('/', trim($uri, "/"));
    }

    /**
     * Decides which final Response to be sent for client. 
     * 
     * Converts View or null to Response object as appropriate,
     * also handles 404 Responses by delegating to Response::notFound().
     * 
     * @param Response | View | null $controllerResponse The value returned by the controller.
     * 
     * @return Response The Final Response.
     */
    private function handleControllerResponse(Response | View | null $controllerResponse): Response
    {
        if ($controllerResponse instanceof Response) {
            $responseCode = $controllerResponse->httpResponseCode;
            if ($responseCode === 404) {
                $view = $controllerResponse->view;
                
                return Response::notFound($view);
            }
            return $controllerResponse;

        } elseif ($controllerResponse instanceof View) {
            return new Response(200, $controllerResponse);
        }

        return new Response(200);
    }
}
