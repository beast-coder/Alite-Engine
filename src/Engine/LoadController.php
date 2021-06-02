<?php

namespace Alite\Engine;

use Alite\AliteException\AliteException;

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
     * @var type 
     */
    private $di = null;

    /**
     *
     * @var type 
     */
    private $nameCases = null;

    /**
     * 
     * @param type $bootObj
     */
    public function __construct($bootObj = null) {
        $this->bootObj = $bootObj;
        $this->scanDir = new ScanDir();
        $this->nameCases = new NameCases();
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
            $this->controllerDir = realpath($this->bootObj->config['PUBLIC_ABSPATH'] . DS . '..' . DS . 'App' . DS . 'Controller');
        } else {
            $this->controllerDir = $this->scanDir->getControllerDir();
        }

        return $this;
    }

    private function resetControllerDir($partsArr, &$index) {

        while (!empty($partsArr[$index]) && $partsArr[0] != '/' && $dir = $this->nameCases->isDir($this->controllerDir, $partsArr[$index])) {

            $this->controllerDir = rtrim($this->controllerDir, DS) . DS . $dir;
            $this->controllerNamespace = rtrim($this->controllerNamespace, '\\') . '\\' . $dir;
            $index++;
        }
    }

    /**
     * 
     * @param type $controllerClass
     * @return $this
     */
    private function getController($controllerClass) {
        $this->controllerObj = $this->di->dependencyInjector($controllerClass);

        if ($this->bootObj instanceof \Alite\Engine\Bootstrap) {
            $this->controllerObj->config = $this->bootObj->config;
        }

        return $this->controllerObj;
    }

    private function callAction($action = 'index', $paramsArr = [], $matches = []) {

        $newParamsArr = array_map(function($param) use ($matches) {
            $key = str_replace('$', '', $param);
            if (!empty($matches[$key])) {
                return $matches[$key];
            } else {
                return false;
            }
        }, $paramsArr);

        if (method_exists($this->controllerObj, $action)) {
            if (is_callable([$this->controllerObj, $action])) {
                call_user_func_array(array($this->controllerObj, $action), $newParamsArr);
            } else {
                $this->getController($this->controllerNamespace . '\PageNotFound');
                $this->callAction('index');
            }
            exit();
        } else {
            $MSG = ['Error : ', " $action method", ' does', ' not', ' exist in ' . get_class($this->controllerObj)];
            new AliteException(implode('', $MSG));
        }

        return true;
    }

    /**
     * 
     * @param type $param
     */
    private function processRoute() {

        foreach ($this->bootObj->routes as $routeKey => $routeValue) {
            if ($this->compareRoute($routeKey, $routeValue)) {
                break;
            }
        }

        /**
         * load controller if controller file exist after route check end
         */
        $partsArr = explode('/', $this->trimRequestUri);
        $index = 0;

        if (!empty($partsArr[$index]) && $file = $this->nameCases->isFile($this->controllerDir, $partsArr[$index])) {

            $this->getController(rtrim($this->controllerNamespace, '\\') . '\\' . $file);
            $index++;
            if (!empty($partsArr[$index]) && $method = $this->nameCases->hasMethod($this->controllerObj, $partsArr[$index])) {
                $paramsArr = array_slice($partsArr, ++$index);
                $this->callAction($method, array_keys($paramsArr), array_values($paramsArr));
            } else {
                $paramsArr = array_slice($partsArr, $index);
                $this->callAction('index', array_keys($paramsArr), array_values($paramsArr));
            }
        }

        if ($this->controllerObj == NULL) {
            if (class_exists($this->controllerNamespace . '\Index') && $this->trimRequestUri == '/') {
                $this->getController($this->controllerNamespace . '\Index');
                $this->callAction('index');
            } else {
                if (class_exists($this->controllerNamespace . '\PageNotFound')) {
                    $this->getController($this->controllerNamespace . '\PageNotFound');
                    $this->callAction('index');
                } else {
                    $this->getController('\App\Controller\PageNotFound');
                    $this->callAction('index');
                }
            }
        }
    }

    private function compareRoute($routeKey, $routeValue) {
        //echo $routeKey . '----' . $routeValue . '----------' . $this->trimRequestUri . '<br/>';

        $paramsToRegex[':any'] = "[^/]+";
        $paramsToRegex[':num'] = "[0-9]+";
        $paramsToRegex[':string'] = "[a-zA-Z]+";

        $regex = str_replace(array_keys($paramsToRegex), array_values($paramsToRegex), $routeKey);
        $regex = str_replace('/', '\/', $regex);

        //echo $routeKey . '----' . $this->trimRequestUri . '<br/>';
        if (preg_match("/^\/?$regex\/?$/", $this->trimRequestUri, $matches)) {
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
                    new AliteException(implode('', $MSG));
                } else {

                    $this->getController($this->controllerNamespace . '\\' . $routeValueParts[$routeValueIndex]);
                    $routeValueIndex = ++$routeValueIndex;
                    if (empty($routeValueParts[$routeValueIndex])) {
                        return $this->callAction('index');
                    } elseif (!method_exists($this->controllerObj, $routeValueParts[$routeValueIndex])) {

                        $MSG = ['Action', ' is', ' missing', ' in route' . ' -> ', $routeKey];
                        new AliteException(implode('', $MSG));
                    } else {
                        $action = $routeValueParts[$routeValueIndex];
                        return $this->callAction($action, array_slice($routeValueParts, ++$routeValueIndex), $matches);
                    }
                }
            }
        } elseif (preg_match("/^\/?$regex\/?/", $this->trimRequestUri, $matches)) {
            $this->trimRequestUri = preg_replace("/^\/?$regex/", "", $this->trimRequestUri);
            if (is_array($routeValue)) {
                foreach ($routeValue as $key => $value) {
                    $this->compareRoute($key, $value);
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
                    new AliteException(implode('', $MSG));
                } else {

                    $this->getController($this->controllerNamespace . '\\' . $routeValueParts[$routeValueIndex]);
                    $routeValueIndex = ++$routeValueIndex;
                    if (empty($routeValueParts[$routeValueIndex])) {
                        $this->callAction('index');
                    } elseif (!method_exists($this->controllerObj, $routeValueParts[$routeValueIndex])) {

                        $MSG = ['Action', ' is', ' missing', ' in route' . ' -> ', $routeKey];
                        new AliteException(implode('', $MSG));
                    } else {
                        $action = $routeValueParts[$routeValueIndex];
                        $this->callAction($action, array_slice($routeValueParts, ++$routeValueIndex), $matches);
                    }
                }
            }
        }
    }

}
