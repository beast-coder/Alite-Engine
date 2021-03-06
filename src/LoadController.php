<?php

namespace Alite\Engine;

class LoadController {

    /**
     *
     * @var type 
     */
    private $bootObj = null;

    /**
     *
     * @var type 
     */
    private $requestUri;

    /**
     *
     * @var type 
     */
    private $trimRequestUri;

    /**
     *
     * @var type 
     */
    private $pathParts;

    /**
     *
     * @var type 
     */
    private $index = 0;

    /**
     *
     * @var type 
     */
    private $controllerNamespace = '';

    /**
     *
     * @var type 
     */
    private $controllerDir = '';

    /**
     *
     * @var type 
     */
    private $controllerObj = null;

    /**
     * 
     * @param type $bootObj
     */
    public function __construct($bootObj) {
        $this->bootObj = $bootObj;
        $this->scanDir = new ScanDir();
        $this->di = new DI();
    }

    /**
     * 
     */
    public function get() {
        $this->setRequestUri()->setPathParts();
        $this->setControllerDir()->setControllerNamespace();
        $this->resetControllerDir(array_map('ucfirst', $this->pathParts), $this->index);
        $this->processRoute();
    }

    /**
     * 
     * @return $this
     */
    private function setRequestUri() {
        echo $_SERVER['REQUEST_URI'] . '<br/>';
        $this->requestUri = preg_replace('/\?.*/', '', preg_replace('/.*index\.php\/?/', '', $_SERVER['REQUEST_URI']));
        $this->requestUri = $this->trimRequestUri = preg_replace('/\/+/', '/', rtrim($this->requestUri, '/') . '/');
        return $this;
    }

    /**
     * 
     * @return $this
     */
    private function setPathParts() {
        $this->pathParts = array_values(array_filter(explode('/', $this->requestUri)));
        if (empty($this->pathParts)) {
            $this->pathParts[0] = '/';
        }
        return $this;
    }

    /**
     * 
     * @param type $param
     */
    private function setControllerNameSpace() {
        $this->controllerNamespace = preg_replace('/\\\\[^\\\\]+$/', '', $this->getDefaultController());
    }

    /**
     * 
     * @return type
     */
    private function getDefaultController() {

        if (!empty($this->bootObj->config['DEFAULT_CONTROLLER'])) {
            return $this->bootObj->config['DEFAULT_CONTROLLER'];
        } else {
            return '\App\Controller\Index';
        }
    }

    /**
     * 
     * @param type $param
     */
    private function setControllerDir() {

        if (!empty($this->bootObj->config['CONTROLLER_PATH'])) {
            $this->controllerDir = $this->bootObj->config['CONTROLLER_PATH'];
        } elseif (!empty($this->bootObj->config['PUBLIC_ABSPATH'])) {
            $this->controllerDir = realpath($this->bootObj->config['PUBLIC_ABSPATH'] . DIRECTORY_SEPARATOR . '..' .
                    DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Controller');
        } else {
            $this->controllerDir = $this->scanDir->getControllerDir();
        }

        return $this;
    }

    /**
     * 
     */
    private function resetControllerDir($partsArr, &$index) {

        while (!empty($partsArr[$index]) && $partsArr[0] != '/' &&
        is_dir(rtrim($this->controllerDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $partsArr[$index])) {

            $this->controllerDir = rtrim($this->controllerDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $partsArr[$index];
            $this->controllerNamespace = rtrim($this->controllerNamespace, '\\') . '\\' . $partsArr[$index];
            $index++;
        }
    }

    /**
     * 
     * @param type $controllerClass
     * @return $this
     */
    public function getController($controllerClass) {
        $this->controllerObj = $this->di->dependencyInjector($controllerClass);
        $this->controllerObj->config = $this->bootObj->config;
        $this->controllerObj->services = $this->bootObj->services;
        return $this;
    }

    public function callAction($action = 'index', $paramsArr = [], $matches = []) {

        $newParamsArr = array_map(function($param) use ($matches) {
            $key = str_replace('$', '', $param);
            if (!empty($matches[$key])) {
                return $matches[$key];
            } else {
                return false;
            }
        }, $paramsArr);

        if (method_exists($this->controllerObj, $action)) {
            call_user_func_array(array($this->controllerObj, $action), $newParamsArr);
        } else {
            $MSG = ['Error : ', " $action method", ' does', ' not', ' exist of class ' . get_class($this->controllerObj)];
            die(implode('', $MSG));
        }
    }

    /**
     * 
     * @param type $param
     */
    private function processRoute() {

        foreach ($this->bootObj->routes as $routeKey => $routeValue) {
            $this->compareRoute($routeKey, $routeValue);
        }

        if ($this->controllerObj == NULL) {
            s($this->trimRequestUri);
            if (class_exists($this->controllerNamespace . '\Index') && $this->trimRequestUri == '/') {
                $this->getController($this->controllerNamespace . '\Index')->callAction('index');
            } else {
                if (class_exists($this->controllerNamespace . '\PageNotFound')) {
                    $this->getController($this->controllerNamespace . '\PageNotFound')->callAction('index');
                } else {
                    $this->getController('\App\Controller\PageNotFound')->callAction('index');
                }
            }
        }
    }

    private function compareRoute($routeKey, $routeValue) {

        $paramsToRegex[':any'] = "[^/]+";
        $paramsToRegex[':num'] = "[0-9]+";
        $paramsToRegex[':string'] = "[a-zA-Z]+";

        $regex = str_replace(array_keys($paramsToRegex), array_values($paramsToRegex), $routeKey);
        $regex = str_replace('/', '\/', $regex);

        s($regex . '-' . $this->trimRequestUri, '/');
        if (preg_match("/^\/?$regex/", $this->trimRequestUri, $matches)) {
            $this->trimRequestUri = preg_replace("/^\/?$regex/", "", $this->trimRequestUri);

            if (is_array($routeValue)) {
                foreach ($routeValue as $key => $value) {
                    $this->compareRoute($key, $value);
                }
            } else {
                $routeValueParts = explode('/', $routeValue);
                $routeValueIndex = 0;

                $this->resetControllerDir($routeValueParts, $routeValueIndex);

                if (empty($routeValueParts[$routeValueIndex])) {
                    $MSG = ['Controller', ' class', ' is', ' missing', ' in route' . ' -> ', $routeKey];
                    die(implode('', $MSG));
                } else {

                    $this->getController($this->controllerNamespace . '\\' . $routeValueParts[$routeValueIndex]);
                    $routeValueIndex = ++$routeValueIndex;
                    if (empty($routeValueParts[$routeValueIndex])) {
                        $this->callAction('index');
                    } elseif (!method_exists($this->controllerObj, $routeValueParts[$routeValueIndex])) {

                        $MSG = ['Action', ' is', ' missing', ' in route' . ' -> ', $routeKey];
                        die(implode('', $MSG));
                    } else {
                        $action = $routeValueParts[$routeValueIndex];
                        $this->callAction($action, array_slice($routeValueParts, ++$routeValueIndex), $matches);
                    }
                }
            }
        }
    }

    private function compareRoute2($routeKey, $routeValue) {

        $paramsToRegex[':any'] = "[^/.]+";
        $paramsToRegex[':num'] = "[0-9]+";
        $paramsToRegex[':string'] = "[a-zA-Z]+";

        if (is_array($routeValue)) {
            foreach ($routeValue as $key => $value) {
                $this->compareRoute($key, $value);
            }
        } else {
            $regex = str_replace(array_keys($paramsToRegex), array_values($paramsToRegex), $routeKey);
            $regex = str_replace('/', '\/', $regex);

            $remainingPartsArr = array_filter(array_slice($this->pathParts, $this->index));

            if (preg_match('/^' . $regex . '$/', implode('/', $remainingPartsArr), $matches)) {

                echo $routeValue;
                $routeValueParts = explode('/', $routeValue);
                $routeValueIndex = 0;

                $this->resetControllerDir($routeValueParts, $routeValueIndex);

                if (empty($routeValueParts[$routeValueIndex])) {
                    $MSG = ['Controller', ' class', ' is', ' missing', ' in route' . ' -> ', $routeKey];
                    die(implode('', $MSG));
                } else {

                    $this->getController($this->controllerNamespace . '\\' . $routeValueParts[$routeValueIndex]);
                    $routeValueIndex = ++$routeValueIndex;
                    if (empty($routeValueParts[$routeValueIndex])) {
                        $this->callAction('index');
                    } elseif (!method_exists($this->controllerObj, $routeValueParts[$routeValueIndex])) {

                        $MSG = ['Action', ' is', ' missing', ' in route' . ' -> ', $routeKey];
                        die(implode('', $MSG));
                    } else {
                        $action = $routeValueParts[$routeValueIndex];
                        $this->callAction($action, array_slice($routeValueParts, ++$routeValueIndex), $matches);
                    }
                }
            }
        }
    }

}
