<?php

declare(strict_types=1);

namespace ForgeAxiom\Framecore\Core;

use ForgeAxiom\Framecore\Core\Binding;
use Closure;
use InvalidArgumentException;

/**
 * Class assembler.
 * 
 * Can bind and give Classes.
 */
class Container
{
    /** @var array<string, Binding> */
    private array $bindings = [];

    /** @var Class[] */
    private array $sharedInstances = [];


    /**
     * Binds a ClassName with a Closure for later creation in get-method.
     *
     * Binds Class which would be creating anew instance every time.
     * If you need bind statement class use sigleton method.
     * 
     * @param string $className name of binding Class
     * @param Closure $closure instruction for creating Class
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
     * Binds Class which would be creating for once and would be storeged.
     * If you need bind always anewed class use bind method.
     * 
     * @param string $className name of binding Class
     * @param Closure $closure instruction for creating Class
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
     * @param string $className requested Class by ClassName
     * 
     * @return void
     * 
     * @throws InvalidArgumentException ClassName was not binded
     */
    public function get(string $className): object
    {
        $binding = $this->bindings[$className] ?? null;
        
        if ($binding === null) {
            // saying what class is not found by var
            throw new InvalidArgumentException("Not found '{$className}' in bindings");
        }

        $isCashed = isset($this->sharedInstances[$className]);
        if ($isCashed) {
            return $this->sharedInstances[$className];
        } 

        $closure = $binding->closure;
        $instance = $closure($this); 
        
        $isShared = $binding->shared === true;
        if ($isShared) {
            $this->sharedInstances[$className] = $instance;
        }

        return $instance;
    }
}
