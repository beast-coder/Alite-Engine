<?php

namespace Alite\Engine;

use Alite\AliteException\AliteException;

/**
 * Regex routing checks when doesn't match any controller or action.
 */
class Bootstrap implements BootstrapInterface {

    public $config;

    /**
     *
     */
    public function __construct() {

        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }

        if (!defined('ROOTPATH')) {
            $MSG = ['Error : ', 'ROOTPATH', ' must', ' be', ' defined'];
            new AliteException(implode('', $MSG));
        }

        if (!defined('ENV')) {
            define('ENV', 'production');
        }

        $this->config = getAliteConfig();
        loadAliteConstants();

        if (file_exists(ROOTPATH . DS . 'routes' . DS . 'routes.php')) {
            $routesArray = require_once ROOTPATH . DS . 'routes' . DS . 'routes.php';
            $this->routes = $routesArray;
        } else {
            $MSG = ['Route', ' file', ' is', ' missing'];
            new AliteException(implode('', $MSG));
        }
    }

    /**
     * 
     */
    public function init() {

        $controller = new LoadController($this);
        $controller->get();
    }

}
