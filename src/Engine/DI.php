<?php

namespace Alite\Engine;

use Alite\AliteException\AliteException;
use Alite\Engine\BootstrapInterface;

class DI {

    private $singleTon = [];

    public function __construct() {
        
    }

    public function dependencyInjector($class, BootstrapInterface $bootObj) {

        if (!class_exists($class)) {
            $MSG = ['Error : ', $class, ' : ', ' doesn\'t ', ' exist!'];
            new AliteException(implode('', $MSG));
        }

        $reflector = new \ReflectionClass($class);
        $constructor = $reflector->getConstructor();

        if (!$reflector->isInstantiable()) {
            new AliteException("Class {$class} is not instantiable");
        }

        $dependencies = [];
        if ($constructorParams = $constructor->getParameters()) {

            foreach ($constructorParams as $parameter) {

                try {
                    $dependency = $parameter->getClass();
                } catch (\Exception $e) {
                    $msg = $e->getMessage() . ' which is' . '' . ' passed ' . '' . 'in __constructor' . '' . ' of ' . $parameter->getDeclaringClass()->name;
                    new AliteException($msg);
                }

                if ($dependency === NULL) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                    } else {
                        $msg = "No" . '' . " Default" . '' . " Value" . '' . " Available For : {$parameter->name} in " . $parameter->getDeclaringClass()->name;
                        new AliteException($msg);
                    }
                } else {
                    if (array_key_exists(strtolower($dependency->name), $this->singleTon)) {
                        $dependencies[] = $this->singleTon[strtolower($dependency->name)];
                    } else {
                        $obj = $this->dependencyInjector($dependency->name, $bootObj);
                        $obj->config = $bootObj->config;
                        $obj->services = $bootObj->services;
                        $obj->fetchController = function($class) {
                            if ($controllerClass != $class || TRUE) {
                                $this->dependencyInjector($class, $bootObj);
                            }
                        };
                        $this->singleTon[strtolower($dependency->name)] = $dependencies[] = $obj;
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
