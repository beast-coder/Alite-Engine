<?php

namespace Alite\Engine;

/**
 * 
 */
class ScanDir {

    /**
     *
     * @var type 
     */
    private $scanPath;

    /**
     * 
     * @param type $path
     */
    public function __construct() {
        
    }

    public function setPath($path) {
        $this->scanPath = $path;
    }

    public function getControllerDir() {
        $curDir = __DIR__;
        while (true) {
            if (is_dir($curDir . DS . 'App' . DS . 'Controller')) {
                break;
            } else {
                $curDir = dirname($curDir);
            }
        }
        return $curDir . DS . 'App' . DS . 'Controller' . DS;
    }

}
