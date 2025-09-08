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
        $this->failIfFileNotExists(__DIR__ . '/../../app/Routes/routes.php');
        
        $routes = require_once __DIR__ . '/../../app/Routes/routes.php';

        $this->failIfRoutesNotInHttpMethod($routes);
        $this->failIfKeysNotUniq($routes);

        return $this->convertAllToAssoc($routes);
    }

    /** @throws FileNotExistsException */
    private function failIfFileNotExists(string $path): void
    {
        if (!file_exists($path)) {
            throw new FileNotExistsException("File app/Routes/routes.php does not exists. Tried: '$path'");
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
            if (!is_array($routesByMethod[0])) {
                throw new InvalidConfigReturnException(sprintf(
                    'Invalid routes config format. %s => value must be array which contains routes. Now value is route.',
                    $httpMethod 
                ));
            }
        
            foreach($routesByMethod as $route) {
                $result[$httpMethod][] = array_combine(self::$keys, $route);
            }
        }
        
        return $result;
    }
}
