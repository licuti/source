<?php

namespace App\Core;

use App\Core\Contracts\ContainerInterface;
use Exception;
use ReflectionClass;

class Container implements ContainerInterface {
    protected $bindings = [];
    protected $instances = [];

    public function bind(string $abstract, $concrete = null, bool $shared = false) {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }
        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    public function singleton(string $abstract, $concrete = null) {
        $this->bind($abstract, $concrete, true);
    }

    public function bound(string $abstract): bool {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    public function instance(string $abstract, $instance) {
        $this->instances[$abstract] = $instance;
        return $instance;
    }

    public function make(string $abstract, array $parameters = []) {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;

        if ($concrete instanceof \Closure) {
            $object = $concrete($this, $parameters);
        } else {
            $object = $this->build($concrete, $parameters);
        }

        if (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Gọi một function/method và tự động inject tham số
     */
    public function call($callback, array $parameters = []) {
        if (is_array($callback)) {
            $reflector = new \ReflectionMethod($callback[0], $callback[1]);
        } elseif (is_string($callback) && strpos($callback, '@') !== false) {
            [$class, $method] = explode('@', $callback);
            $reflector = new \ReflectionMethod($class, $method);
            $callback = [$this->make($class), $method];
        } else {
            $reflector = new \ReflectionFunction($callback);
        }

        $dependencies = $reflector->getParameters();
        $instances = $this->resolveDependencies($dependencies, $parameters);

        if (is_array($callback)) {
            return $reflector->invokeArgs(is_object($callback[0]) ? $callback[0] : $this->make($callback[0]), $instances);
        }

        return $reflector->invokeArgs($instances);
    }

    protected function build($concrete, $parameters) {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (Exception $e) {
            return new $concrete(...$parameters);
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("Class {$concrete} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return new $concrete();
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies, $parameters);

        $object = $reflector->newInstanceArgs($instances);

        // Nâng cấp: Tự động chạy validate nếu class là FormRequest
        if ($object instanceof \App\Core\FormRequest) {
            $object->validateResolved();
        }

        return $object;
    }

    protected function resolveDependencies($dependencies, $parameters) {
        $results = [];
        foreach ($dependencies as $dependency) {
            if (array_key_exists($dependency->name, $parameters)) {
                $results[] = $parameters[$dependency->name];
                continue;
            }
            $type = $dependency->getType();
            if ($type && !$type->isBuiltin()) {
                $results[] = $this->make($type->getName());
            } else {
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                } else {
                    throw new Exception("Cannot resolve class dependency {$dependency->name}");
                }
            }
        }
        return $results;
    }
}
