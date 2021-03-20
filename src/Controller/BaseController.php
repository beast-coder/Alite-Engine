<?php

namespace Alite\Controller;

use Alite\AliteException\AliteException;
use Alite\Engine\NameCases;

abstract class BaseController {

    private $layoutFile;
    protected $data;
    protected $contents;
    protected $scripts = [];
    protected $styleSheets = [];
    private $nameCases = null;

    public function __construct() {
        $this->layoutFile = rtrim(ABSPATH, DIRECTORY_SEPARATOR) .
                DIRECTORY_SEPARATOR . 'app' .
                DIRECTORY_SEPARATOR . 'View' .
                DIRECTORY_SEPARATOR . 'Layout' .
                DIRECTORY_SEPARATOR . 'layout.php';

        $this->nameCases = new NameCases();
    }

    public function setLayout($layout = false) {
        if ($layout == false) {
            $this->layoutFile = false;
        } else {
            $this->layoutFile = rtrim(ABSPATH, DIRECTORY_SEPARATOR) .
                    DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . $layout;
        }
    }

    public function setScript(array $script) {
        $this->scripts[] = $script;
    }

    public function setStyleSheets(array $style) {
        $this->styleSheets[] = $style;
    }

    /**
     * 
     * @param type $viewFile
     * @param type $data
     */
    protected function loadView($data = [], $return = false, $file = "") {

        if (empty($file)) {
            $method = '';
            foreach (debug_backtrace() as $value) {
                if (!empty($value['class']) && get_called_class() == $value['class']) {
                    $method = $value['function'];
                    break;
                }
            }
            $path = preg_replace('/\\\\?App\\\\Controller/', 'app' . DIRECTORY_SEPARATOR . 'View', get_called_class(), 1);
            $path = preg_replace('/\\\\/', DIRECTORY_SEPARATOR, $path);
            $path = rtrim(ABSPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;

            //camelcase to dashed
            $method = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $method));
            $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $method . '.php';
        } else {
            $path = rtrim(ABSPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'app' .
                    DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . $file;
        }

        $this->data = $data;
        $this->contents = $this->getBuffer($path);

        //echo $path . '---' . $this->layoutFile . '<br/>';
        if ($this->layoutFile) {
            if ($return) {
                return $this->getBuffer($this->layoutFile);
            } else {
                echo $this->getBuffer($this->layoutFile);
            }
        } else {
            if ($return) {
                return $this->contents;
            } else {
                echo $this->contents;
            }
        }
    }

    private function getBuffer($file = "") {
        $contents = "";
        if (file_exists($file)) {
            ob_start();
            require $file;
            $contents = ob_get_contents();
            ob_end_clean();
        } else {
            $MSG = ['Error :', ' file ', 'doesn\'t exist -> ', $file];
            new AliteException(implode('', $MSG));
        }

        return $contents;
    }

    public function getController($class) {
        return call_user_func($this->fetchController, $class);
    }

    public function redirect($location = "/", $statusCode = 302) {
        header('Location:' . $location, TRUE, $statusCode);
        exit();
    }

    public function setFlashMsg($msg = "", $key = "common") {
        $_SESSION['flash_msg'][$key] = $msg;
        return $this;
    }

    public function getFlashMsg($key = "common") {
        $msg = empty($_SESSION['flash_msg'][$key]) ? false : $_SESSION['flash_msg'][$key];
        unset($_SESSION['flash_msg'][$key]);
        return $msg;
    }

}
