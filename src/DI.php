<?php

namespace Alite\Engine;

class DI {

    private $singleTon = [];

    public function __construct() {
        
    }

    public function dependencyInjector($class) {

        if (!class_exists($class)) {
            $MSG = ['Error : ', $class, ' : ', ' doesn\'t ', ' exist!'];
            die(implode('', $MSG));
        }

        $reflector = new \ReflectionClass($class);
        $constructor = $reflector->getConstructor();

        if (!$reflector->isInstantiable()) {
            die("Class {$class} is not instantiable");
        }

        $dependencies = [];
        if ($constructorParams = $constructor->getParameters()) {
            foreach ($constructorParams as $parameter) {

                try {
                    $dependency = $parameter->getClass();
                } catch (\Exception $e) {
                    die($e->getMessage() . ' which is' . '' . ' passed ' . '' . 'in __constructor' . '' . ' of ' . $parameter->getDeclaringClass()->name);
                }

                if ($dependency === NULL) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                    } else {
                        die("No" . '' . " Default" . '' . " Value" . '' . " Available For : {$parameter->name} in " . $parameter->getDeclaringClass()->name);
                    }
                } else {
                    if (array_key_exists(strtolower($dependency->name), $this->singleTon)) {
                        $dependencies[] = $this->singleTon[strtolower($dependency->name)];
                    } else {
                        $this->singleTon[strtolower($dependency->name)] = $dependencies[] = $this->dependencyInjector($dependency->name);
                    }
                }
            }
        }
        return new $class(...$dependencies);
    }

    public function setSingleTon(Array $objArr = []) {
        $this->singleTon = $objArr;
    }

}
