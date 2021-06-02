<?php

if (!function_exists('get_alite')) {

    function get_alite() {
        return \Alite\Controller\BaseController::getAlite();
    }

}

if (!function_exists('getAliteConfig')) {

    function getAliteConfig() {

        $config = [];
        if (file_exists(rtrim(ROOTPATH, DS) . DS . 'config' . DS . 'config.' . ENV . '.php')) {
            $config = require rtrim(ROOTPATH, DS) . DS . 'config' . DS . 'config.' . ENV . '.php';
        } elseif (file_exists(rtrim(ROOTPATH, DS) . DS . 'config' . DS . 'config.php')) {
            $config = require rtrim(ROOTPATH, DS) . DS . 'config' . DS . 'config.php';
        }
        return $config;
    }

}

if (!function_exists('loadAliteConstants')) {

    function loadAliteConstants() {

        try {
            if (file_exists(rtrim(ROOTPATH, DS) . DS . 'config' . DS . 'constants.' . ENV . '.php')) {
                require rtrim(ROOTPATH, DS) . DS . 'config' . DS . 'constants.' . ENV . '.php';
            } elseif (file_exists(rtrim(ROOTPATH, DS) . DS . 'config' . DS . 'constants.php')) {
                require rtrim(ROOTPATH, DS) . DS . 'config' . DS . 'constants.php';
            }
        } catch (Exception $exc) {
            new Alite\Engine\AliteException($exc->getTraceAsString());
        }
    }

}
?>