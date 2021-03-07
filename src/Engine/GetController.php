<?php

namespace Alite\Engine;

/**
 * 
 */
class GetController {

    private $bootObj;
    private $controllerDir;
    private $index = 0;
    private $allSegments;
    private $filterSegments;
    private $controllerNameSpace;
    private $controllerObj = null;
    private $di;

    /**
     * 
     * @param type $param
     */
    public function __construct(BootstrapInterface $bootObj) {
        $this->bootObj = $bootObj;

        $this->controllerDir = $this->getControllerDir();
        $this->controllerNameSpace = $this->getcontrollerNameSpace();
        $this->di = new DI();

        $this->requestUri = preg_replace('/\?.*/', '', preg_replace('/.*?index.php\/?/', '', $_SERVER['REQUEST_URI']));
        $this->requestUri = empty($this->requestUri) ? '/' : $this->requestUri;
        $this->allSegments = $this->filterSegments = array_values(array_filter(explode('/', $this->requestUri)));
    }

    public function controller() {

        $this->loopTillLastDir();
        $this->controllerObj = $this->checkControllerFileExist();

        if ($this->controllerObj == null) {

            if (empty($this->requestUri) || $this->requestUri == '/') {
                $this->controllerObj = $this->di->dependencyInjector($this->getDefaultController());
            } elseif (!method_exists($this->controllerObj, $this->allSegments[$this->index])) {
                $this->controllerObj = $this->matchRoutes();
            }
        }

        return $this;
    }

    /**
     * 
     * @return type
     */
    private function getDefaultController() {
        return empty($this->bootObj->config['DEFAULT_CONTROLLER']) ? 'App\Controller\Index' : $this->bootObj->config['DEFAULT_CONTROLLER'];
    }

    public function getDefault404() {
        return empty($this->bootObj->config['__404__']) ? 'App\Controller\Pagenotfound' : $this->bootObj->config['__404__'];
    }

    /**
     * 
     * @param type $param
     */
    private function getControllerDir() {
        if (!defined('WEB_ROOT'))
            die('WEB_ROOT ' . '' . ' is ' . '' . ' not' . '' . ' defined');

        return WEB_ROOT . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR;
    }

    /**
     * 
     * @param type $param
     */
    private function getcontrollerNameSpace() {
        return preg_replace('/\\\\[^\\\\]+$/', '', $this->getDefaultController()) . '\\';
    }

    /**
     * 
     * @param type $controllerDir
     */
    private function loopTillLastDir() {

        if (!empty($this->allSegments[$this->index]) &&
                is_dir($this->controllerDir . ucfirst($this->allSegments[$this->index]))) {

            $this->controllerDir .= ucfirst($this->allSegments[$this->index]) . DIRECTORY_SEPARATOR;
            $this->controllerNameSpace .= ucfirst($this->allSegments[$this->index]) . '\\';
            unset($this->filterSegments[$this->index]);

            $this->index++;
            $this->loopTillLastDir();
        }
    }

    /**
     * 
     */
    public function checkControllerFileExist() {

        $controller = null;
        if (!empty($this->allSegments[$this->index]) && file_exists($this->controllerDir . ucfirst($this->allSegments[$this->index]) . '.php')) {

            unset($this->filterSegments[$this->index]);
            $class = $this->controllerNameSpace . ucfirst($this->allSegments[$this->index]);

            $controller = $this->di->dependencyInjector($class);
            $this->index++;
        }

        return $controller;
    }

    /**
     * matchRoutes
     */
    private function matchRoutes() {

        $routesArray = include_once WEB_ROOT . '/routes/routes.php';

        foreach ($routesArray as $routeKey => $routeValue) {

            //if (preg_match('/^' . $pattern . '$/i', $this->requestUri)) {
            if ($this->matchRoutePattern($routeKey, $routeValue)) {
                $this->di->setSingleTon();

                $controller = preg_replace('/\//', '\\', $controller);
                $controller = preg_replace('/\.php/i', '', $controller);
                $controller = trim($controller, '\\');
                $class = $this->controllerNameSpace . $controller;
                $this->controllerObj = $this->di->dependencyInjector($class);
                break;
            }
        }

        if ($this->controllerObj == NULL)
            return $this->di->dependencyInjector($this->getDefault404());
        else
            return $this->controllerObj;
    }

    private function matchRoutePattern($routeKey, $routeValue) {

        $paramsToRegex[':any'] = "[^/.]+";
        $paramsToRegex[':num'] = "[0-9]+";
        $paramsToRegex[':string'] = "[a-zA-Z]+";

        if (is_array($routeValue)) {
            foreach ($routeValue as $key => $value) {
                $this->matchRoutePattern($key, $value);
            }
        } else {
            $regex = str_replace(array_keys($paramsToRegex), array_values($paramsToRegex), $routeKey);
            $regex = str_replace('/', '\/', $regex);

            //echo $regex . '---' . implode('/', array_filter($this->filterSegments)) . '<br/>';

            if (preg_match('/^' . $regex . '$/', implode('/', array_filter($this->filterSegments)), $matches)) {

                $routeValueArr = explode('/', $routeValue);
                $controller = preg_replace('/\//', '\\', $routeValueArr[0]);
                $controller = preg_replace('/\.php/i', '', $controller);
                $controller = trim($controller, '\\');
                $class = $this->controllerNameSpace . $controller;
                $this->controllerObj = $this->di->dependencyInjector($class);

                if (!empty($routeValueArr[1]) && method_exists($this->controllerObj, $routeValueArr[1])) {

                    $paramsArr = array_slice($routeValueArr, 2);
                    $newParamsArr = array_map(function($param) use ($matches) {
                        $key = str_replace('$', '', $param);
                        if (!empty($matches[$key])) {
                            return $matches[$key];
                        } else {
                            return false;
                        }
                    }, $paramsArr);

                    call_user_func_array(array($this->controllerObj, $routeValueArr[1]), $newParamsArr);
                } elseif (method_exists($this->controllerObj, 'index')) {

                    $paramsArr = array_slice($routeValueArr, 1);
                    $newParamsArr = array_map(function($param) use ($matches) {
                        $key = str_replace('$', '', $param);
                        if (!empty($matches[$key])) {
                            return $matches[$key];
                        } else {
                            return false;
                        }
                    }, $paramsArr);
                    call_user_func_array(array($this->controllerObj, 'index'), $paramsArr);
                } else {

                    call_user_func_array(array($this->controllerObj, 'index'), []);
                }
            }
        }
    }

    /**
     * 
     */
    public function loadAction() {
        if (is_object($this->controllerObj)) {

            $this->controllerObj->config = $this->bootObj->config;
            $this->controllerObj->services = $this->bootObj->services;
            if (!empty($this->allSegments[$this->index]) && method_exists($this->controllerObj, $this->allSegments[$this->index])) {

                unset($this->filterSegments[$this->index]);
                call_user_func_array(array($this->controllerObj, $this->allSegments[$this->index]), array_filter($this->filterSegments));
            } else {
                call_user_func_array(array($this->controllerObj, 'index'), array_filter($this->filterSegments));
            }
        } else {
            die('No Controller Found');
        }
    }

}
