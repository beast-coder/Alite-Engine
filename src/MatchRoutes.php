<?php

namespace Alite\Engine;

/**
 * 
 */
class MatchRoutes {

    private $getController;
    private $controllerObj;
    private $pagenotFound = 'App\Controller\Pagenotfound';

    /**
     * 
     * @param type $param
     */
    public function __constructor() {
        $this->getController = $getController;
        $this->controllerObj = $controllerObj;
    }

    public function getRoute() {

        if (empty(preg_replace('/.*?index.php\/?/', '', @$_REQUEST['page'])))
            return false;

        $routesArray = include_once WEB_ROOT . '/routes/routes.php';

        foreach ($routesArray as $pattern => $controller) {

            if (preg_match('/^' . $pattern . '$/i', preg_replace('/.*?index.php\/?/', '', @$_REQUEST['page']))) {
                $this->getController->di->setSingleTon();

                $controller = preg_replace('/\//', '\\', $controller);
                $controller = preg_replace('/\.php/i', '', $controller);
                $controller = trim($controller, '\\');
                $class = $this->nameSpace . $controller;
                $this->controllerObj = $this->di->dependencyInjector($class);
                break;
            }
        }

        ($this->getController->getDefaultController() == @get_class($this->controllerObj)) ? $this->controllerObj = $this->getController->di->dependencyInjector($this->pagenotFound) : null;
    }

}
