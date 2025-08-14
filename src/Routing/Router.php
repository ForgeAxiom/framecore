<?php

declare(strict_types=1);

namespace ForgeAxiom\Framecore\Routing;

use ForgeAxiom\Framecore\Routing\Controller;
use ForgeAxiom\Framecore\Routing\Response;
use ForgeAxiom\Framecore\View\View;
use \InvalidArgumentException;
use \Exception;

/** 
 * Service. 
 * Handles incoming HTTP requests by matching them with defined routes
 * and dispatches it to appropriate controller.  
 */
class Router
{
    private string $requestUri;
    private string $httpMethod;
    private array $routes;

    /**
     * @param string $requestUri Client requested URI. 
     * @param string $httpMethod Client requested HTTP method.
     * @param RoutesCollection $routesCollection Prepared routes collection.
     */
    public function __construct(
        string $requestUri, string $httpMethod, RoutesCollection $routesCollection
    ) {
        $this->requestUri = $requestUri;

        $this->httpMethod = trim(strtolower($httpMethod));

        $this->routes = $routesCollection->getRoutes();
    }

    /**
     * 
     * Handles HTTP request by routing it to the appropriate controller.
     * 
     * Dispatches HTTP request to appropriate controller method if match with config found.
     * Otherwise, generates a 404 Not Found Response.
     * 
     * @return Response The final HTTP response.
     * 
     * @throws InvalidArgumentException If a controller defined in routes configuration is not a valid instance of Controller.
     */
    public function handleUri(): Response 
    {
        $requestUri = $this->requestUri;

        $routesByRequestMethod = $this->getRoutesByRequestMethod();

        foreach ($routesByRequestMethod as $route) {
            $routeUri = $route['uri'];

            $match = $this->matchUriAndPullParams($requestUri, $routeUri);

            // If request uri IS matches with route uri
            if ($match['success'] === true) {
                $controllerClassName = $route['controller'];
                $controller = new $controllerClassName();
                
                $controllerMethod = $route['controllerMethod'];
                
                if ($controller instanceof Controller) {
                    $args = $match['params'];
                    
                    $controllerResponse = $controller->$controllerMethod(...$args);

                    return $this->handleControllerResponse($controllerResponse);
                } else {
                    throw new InvalidArgumentException(
                        'Only instanceof ForgeAxiom\Framecore\Routing\Controller, given: ' . $controllerClassName
                    );
                }
                break;
            } 
        }

        // If request uri NOT matches with route uri
        $response = Response::notFound();

        return $response;
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
     */
    private function getRoutesByRequestMethod(): array
    {
        $httpMethod = $this->httpMethod;
        switch ($httpMethod) {
            case 'get':
                $routesByRequestMethod = isset($this->routes['get']) ? $this->routes['get'] : [];
                break;
            case 'post':
                $routesByRequestMethod = isset($this->routes['post']) ? $this->routes['post'] : [];
                break;
            case 'put':
                $routesByRequestMethod = isset($this->routes['put']) ? $this->routes['put'] : [];
                break;
            case 'delete':
                $routesByRequestMethod = isset($this->routes['delete']) ? $this->routes['delete'] : [];
                break;
            default:
                throw new Exception("Error Processing Request.");
        }

        return $routesByRequestMethod;
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
    private function handleControllerResponse(Response | View | null $controllerResponse) {
        if ($controllerResponse === null) {
            return new Response(200);

        } elseif ($controllerResponse instanceof Response) {
            $responseCode = $controllerResponse->httpResponseCode;
            if ($responseCode === 404) {
                $view = $controllerResponse->view;
                
                return Response::notFound($view);
            } 

            return $controllerResponse; 

        } elseif ($controllerResponse instanceof View) {
            return new Response(200, $controllerResponse);
        } 
    }
}
