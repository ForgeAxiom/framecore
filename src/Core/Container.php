<?php

declare(strict_types=1);

namespace ForgeAxiom\Framecore\Core;

use ForgeAxiom\Framecore\Core\Binding;
use Closure;
use ForgeAxiom\Framecore\Exceptions\FileNotExistsException;
use ForgeAxiom\Framecore\Exceptions\InvalidConfigReturnException;
use ForgeAxiom\Framecore\Exceptions\NotBoundException;
use ForgeAxiom\Framecore\Exceptions\NotInstantiableException;
use ForgeAxiom\Framecore\Exceptions\SignatureHasNoTypeSet;
use ForgeAxiom\Framecore\Exceptions\UnresolvableDependencyException;

/**
 * Class assembler.
 * 
 * Can bind and give classes with resolved dependencies.
 */
class Container
{
    /** @var array<string, Binding> */
    private array $bindings = [];

    private array $sharedInstances = [];

    private array $reflectionSingletons = [];


    /**
     * @throws FileNotExistsException|InvalidConfigReturnException
     */
    public function __construct()
    {
        $this->uploadReflectionSingletons();
    }
    
    /**
     * Binds a ClassName with a Closure for later creation in get-method.
     *
     * Binds Class which would be creating anew instance every time.
     * If you need bind statement class use singleton method.
     * 
     * @param string $className name of binding Class.
     * @param Closure $closure instruction for creating Class.
     * @return void
     */
    public function bind(string $className, Closure $closure): void
    {
        $binding = new Binding($closure, false);

        $this->bindings[$className] = $binding;
    }

    /**
     * Binds a ClassName with a Closure for later creation in get-method.
     *
     * Binds Class which would be creating for once and would be stored.
     * If you need bind always anew class use bind method.
     * 
     * @param string $className name of binding Class.
     * @param Closure $closure instruction for creating Class.
     * @return void
     */
    public function singleton(string $className, Closure $closure): void
    {
        $binding = new Binding($closure, true);

        $this->bindings[$className] = $binding;
    }

    /**
     * Gives instance of requested Class by ClassName.
     *
     * @template TClass of object.
     *
     * @param class-string<TClass> $className requested Class by ClassName.
     * @param bool $autoResolving Use or not automatic resolving of not bound classes.
     * 
     * @return TClass Instance of requested class.
     * 
     * @throws \ReflectionException If the class does not exist.
     * @throws SignatureHasNoTypeSet If constructor has no parameter type set.
     * @throws NotInstantiableException If class abstract or interface.
     * @throws UnresolvableDependencyException If parameter type in constructor is a scalar, union, or intersection.
     * @throws NotBoundException If autoResolving was off and requested class was not bounded 
     */ 
    public function get(string $className, bool $autoResolving = true): object
    {
        $binding = $this->bindings[$className] ?? null;

        $isCashed = isset($this->sharedInstances[$className]);
        if ($isCashed) {
            return $this->sharedInstances[$className];
        }

        if ($binding === null && $autoResolving) {
            return $this->getWithReflection($className);
        } elseif ($binding === null) {
            throw new NotBoundException("{$className} is was not bounded");
        }

        $closure = $binding->closure;
        $instance = $closure($this); 
        
        $isShared = $binding->shared === true;
        if ($isShared) {
            $this->sharedInstances[$className] = $instance;
        }

        return $instance;
    }

    /**
     * Automatically resolves dependencies using the reflection API.
     * 
     * @param class-string<TClass> $className requested Class by ClassName.
     * 
     * @return TClass Instance of requested class.
     * 
     * @throws \ReflectionException If the class does not exist.
     * @throws SignatureHasNoTypeSet If constructor has no parameter type set.
     * @throws NotInstantiableException If class abstract or interface.
     * @throws UnresolvableDependencyException If parameter type scalar, union or intersection in constructor.
     */
    private function getWithReflection(string $className): object
    {      
        $reflectionClass = new \ReflectionClass($className);

        if (!$reflectionClass->isInstantiable()) {
            throw new NotInstantiableException("{$className} is not instantiable");
        }
        if ($reflectionClass->getName() === Container::class) {
            return $this;
        }

        $isSingleton = false;
        if (in_array($reflectionClass->getName(), $this->reflectionSingletons)) {
            $isSingleton = true;
        }

        $reflectionMethod = $reflectionClass->getConstructor();
        if ($reflectionMethod === null && $isSingleton) {
            return $this->sharedInstances[$reflectionClass->getName()] = new $className();
        } elseif ($reflectionMethod === null) {
            return new $className();
        }

        $reflectionParameters = $reflectionMethod->getParameters();

        $dependencies = [];
        foreach ($reflectionParameters as $reflectionParameter) {
            if (!$reflectionParameter->hasType()) {
                throw new SignatureHasNoTypeSet("In {$className} has a no type set in constructor");
            }

            $type = $reflectionParameter->getType();
            if ($type->isBuiltin()) {
                throw new UnresolvableDependencyException("Scalar types not supported in constructor. From: {$className}");
            } elseif ($type instanceof \ReflectionUnionType) {
                throw new UnresolvableDependencyException("Union types are not support in constructor. From: {$className}");
            } elseif ($type instanceof \ReflectionIntersectionType) {
                throw new UnresolvableDependencyException("Intersection types are not support in constructor. From: {$className}");
            }

            $dependencies[] = $this->get($type->getName());
        }

        if ($isSingleton) {
            return $this->sharedInstances[$className] = new $className(...$dependencies);
        }
        return new ($className)(...$dependencies);
    }

    /**
     * Uploads auto_singletons.php configuration file in $this->reflectionSingletones.
     * 
     * @throws FileNotExistsException If auto_singletons.php does not exist
     * @throws InvalidConfigReturnException If auto_singletons.php does not return an array.
     */
    private function uploadReflectionSingletons(): void
    {
        $configPath = __DIR__ . '/../../config/auto_singletons.php';
        if (!file_exists($configPath)) {
            throw new FileNotExistsException('auto_singletons.php does not exist, searching:' . $configPath);
        }

        $singletones = require_once $configPath;
        if (!is_array($singletones)) {
            throw new InvalidConfigReturnException('auto_singletons.php does not return an array, searching: ' . $configPath);
        }

        $this->reflectionSingletons = $singletones;
    }
}
