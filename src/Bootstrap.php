<?php

namespace Alite\Engine;

/**
 * Regex routing checks when doesn't match any controller or action.
 */
class Bootstrap implements BootstrapInterface {

    public $config;

    /**
     *
     */
    public function __construct($config, $serviceContainer = null) {

        if (!defined('PUBLIC_ABSPATH')) {
            $MSG = ['PUBLIC', '_ABSPATH', ' must', ' be', ' defined', ' in', ' constants.'];
            die(implode('', $MSG));
        }

        $this->config = $config;
        $this->services = $serviceContainer;

        if (file_exists(realpath(PUBLIC_ABSPATH . '/../') . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'routes.php')) {
            $routesArray = require_once realpath(PUBLIC_ABSPATH . '/../') . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'routes.php';
            $this->routes = $routesArray;
        } else {
            $MSG = ['Route', ' file', ' is', ' missing'];
            die(implode('', $MSG));
        }
    }

    /**
     * 
     */
    public function run() {

        $controller = new LoadController($this);
        $controller->get();
    }

}
