<?php

namespace Alite\View;

use Alite\AliteException\AliteException;

abstract class View {

    private $layoutFile;

    public function __construct() {

        if (file_exists(rtrim(ROOTPATH, DS) . DS . 'app' . DS . 'View' . DS . 'Layout' . DS . 'layout.php')) {
            $this->layoutFile = rtrim(ROOTPATH, DS) . DS . 'app' . DS . 'View' . DS . 'Layout' . DS . 'layout.php';
        }
    }

    protected function layout($layout = false) {
        if ($layout == false) {
            $this->layoutFile = false;
        } else {
            $layout = rtrim($layout, '.php') . '.php';
            $this->layoutFile = rtrim(ROOTPATH, DS) . DS . 'app' . DS . 'View' . DS . 'Layout' . DS . $layout;
        }
        return $this;
    }

    protected function setScript(array $script) {
        $this->scripts[] = $script;
    }

    protected function setStyleSheets(array $style) {
        $this->styleSheets[] = $style;
    }

    /**
     * 
     * @param type $viewFile
     * @param type $data
     */
    protected function view($data = [], $return = false, $file = "") {

        if (empty($file)) {
            $method = '';
            foreach (debug_backtrace() as $value) {
                if (!empty($value['class']) && get_called_class() == $value['class']) {
                    $method = $value['function'];
                    break;
                }
            }

            $path = preg_replace('/\\\\?App\\\\Controller/', 'app' . DS . 'View', get_called_class(), 1);
            $path = preg_replace('/\\\\/', DS, $path);
            $path = rtrim(ROOTPATH, DS) . DS . $path;

            //camelcase to dashed
            $method = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $method));
            $path = rtrim($path, DS) . DS . $method . '.php';
        } else {
            $file = rtrim($file, '.php') . '.php';
            $path = rtrim(ROOTPATH, DS) . DS . 'app' . DS . 'View' . DS . $file;
        }

        $content = $this->getBuffer($path, $data);
        if ($return) {
            return $content;
        } else {
            echo $content;
        }
    }

    private function getBuffer($fileAlite123, $dataAlite123 = []) {

        extract($dataAlite123);

        $contents = "";
        if (file_exists($fileAlite123)) {
            ob_start();
            require $fileAlite123;
            $contents = ob_get_contents();
            ob_end_clean();
        } else {
            $MSG = ['Error :', ' file ', 'doesn\'t exist -> ', $fileAlite123];
            new AliteException(implode('', $MSG));
        }

        return $contents;
    }

}
