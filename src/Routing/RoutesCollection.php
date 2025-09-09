<?php
declare(strict_types=1);

namespace ForgeAxiom\Framecore\Routing;

use ForgeAxiom\Framecore\Exceptions\FileNotExistsException;
use ForgeAxiom\Framecore\Exceptions\InvalidConfigReturnException;

/** 
 * Configuration Service. 
 * Responsible for parsing and validating the route configuration file.  
 */
final class RoutesCollection
{
    private const HTTP_METHODS = ['get', 'post', 'put', 'delete'];
    private static array $keys = [
        'uri', 'controller', 'controllerMethod' 
    ];
    private static array $routesCache = [];
    private array $routes;

    /**
     * @throws FileNotExistsException If file does not exists.
     * @throws InvalidConfigReturnException If returned invalid array from routes configuration.
     */
    public function __construct() {
        if (self::$routesCache === []) {
            self::$routesCache = $this->getAllRoutes();
        }

        $this->routes = self::$routesCache;
    }

    /**
     * Returns routes of current collection as array. 
     *
     * @return array Routes data.
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Loads and validates routes from configuration file.
     *
     * @return array<
     *      string,
     *      array< 
     *          array{
     *              uri: string, 
     *              controller: class-string, 
     *              controllerMethod: string
     *          }
     *      >
     * >
     * 
     * @throws FileNotExistsException
     * @throws InvalidConfigReturnException
     */
    private function getAllRoutes(): array
    {
        $routesPath = __DIR__ . '/../../config/routes.php';
        $this->failIfFileNotExists($routesPath);
        
        $routes = require_once $routesPath;

        $this->failIfRoutesNotInHttpMethod($routes);
        $this->failIfKeysNotUniq($routes);

        return $this->convertAllToAssoc($routes);
    }

    /** @throws FileNotExistsException */
    private function failIfFileNotExists(string $path): void
    {
        if (!file_exists($path)) {
            throw new FileNotExistsException("File config/routes.php does not exists. Tried: '$path'");
        }
    }

    /** @throws InvalidConfigReturnException */
    private function failIfRoutesNotInHttpMethod(array $routes): void
    {
        foreach($routes as $httpMethod => $_) {
            if (!in_array($httpMethod, self::HTTP_METHODS)) {
                throw new InvalidConfigReturnException(sprintf(
                    "Array must contain only: %s Given: ",
                    implode(', ', self::HTTP_METHODS),
                    implode(', ', $routes)
                ));
            }
        }
    }
    
    /** @throws InvalidConfigReturnException */
    private function failIfKeysNotUniq(array $array): void
    {
        if (count($array) === 0) {
            return;
        }
    
        $keys = array_keys($array);
  
        if (!count($keys) === count($array)) {
            throw new InvalidConfigReturnException(sprintf(
                "Config array must return only unique methods keys: %s. Given keys: %s",
                implode(', ', self::HTTP_METHODS),
                implode(', ', $keys)
            ));
        };
    }

    /** 
     * Converts passed routes to associate array with self keys.
     * 
     * @return array<
     *      string,
     *      array< 
     *          array{
     *              uri: string, 
     *              controller: class-string, 
     *              controllerMethod: string
     *          }
     *      >
     * >
     * @throws InvalidConfigReturnException If invalid routes key=>value format.
     */
    private function convertAllToAssoc(array $allRoutes)
    {
        $result = [];
       
        foreach ($allRoutes as $httpMethod => $routesByMethod) {
            if ($routesByMethod === []) {
                $this->throwInvalidConfigReturn($httpMethod, null);
            }
        
            foreach($routesByMethod as $route) {
                if (is_object($route)) {
                    $this->throwInvalidConfigReturn($httpMethod, $route);
                } elseif (!is_array($route)) {
                    $this->throwInvalidConfigReturn($httpMethod, $route);
                } 

                try {
                    $result[$httpMethod][] = array_combine(self::$keys, $route);
                } catch (\Throwable $th) {
                    $this->throwInvalidConfigReturn($httpMethod, $route);
                    throw $th;
                }
            }
        }
        
        return $result;
    }

    private function throwInvalidConfigReturn(string $httpMethod, mixed $route)
    {
        $type = gettype($route);

        if ($type === 'object') {
            $route = get_class($route);
        } elseif ($type === 'array') {
            $route = "['" . implode("', '", $route) . "']";
        }

        throw new InvalidConfigReturnException(sprintf(
            'Invalid routes config format. %s => value must be an array which contains routes like [0=>%s, 1=>%s, 2=>%s]. Now value is: "%s", type: "%s".',
            $httpMethod,
            self::$keys[0], self::$keys[1], self::$keys[2],
            $route,
            $type,
        ));
    }
}
