<?php

namespace Alite\Controller;

abstract class BaseController {

    public function __construct() {
        
    }

    /* public function index() {
      die('Create a index method in the controller.');
      } */

    /**
     * 
     * @param type $viewFile
     * @param type $data
     */
    protected function loadView($viewFile, $data = array()) {

        $viewPath = WEB_ROOT . '/app/view/';
        $viewPath .= str_replace('.', '/', $viewFile) . '.php';

        if (is_readable($viewPath)) {
            include_once($viewPath);
        } else {
            die($viewPath . ' : View file doesn\'t exist OR permission denied.');
        }
    }

}
