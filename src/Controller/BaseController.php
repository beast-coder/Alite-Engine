<?php

namespace Alite\Controller;

use Alite\AliteException\AliteException;
use Alite\View\View;

abstract class BaseController extends View {

    public $config = [];
    protected $services;
    public static $instance;

    public function __construct() {
        parent::__construct();

        self::$instance = & $this;

        if (empty($this->config)) {
            $this->config = getAliteConfig();
        }

        if (file_exists(rtrim(ROOTPATH, DS) . DS . 'services' . DS . 'register-services.php')) {
            $this->services = require rtrim(ROOTPATH, DS) . DS . 'services' . DS . 'register-services.php';
        } else {
            $this->services = new Alite\Engine\ServiceContainer();
        }
    }

    public static function getAlite() {
        return self::$instance;
    }

    protected function redirect($location = "/", $statusCode = 302) {
        header('Location:' . $location, TRUE, $statusCode);
        exit();
    }

    protected function setFlashMsg($msg = "", $key = "common") {
        $_SESSION['flash_msg'][$key] = $msg;
        return $this;
    }

    protected function getFlashMsg($key = "common") {
        $msg = empty($_SESSION['flash_msg'][$key]) ? false : $_SESSION['flash_msg'][$key];
        unset($_SESSION['flash_msg'][$key]);
        return $msg;
    }

}
