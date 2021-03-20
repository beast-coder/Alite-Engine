<?php

namespace Alite\Engine;

/**
 * 
 */
class NameCases {

    /**
     * 
     * @param type $param
     */
    public function __construct() {
        
    }

    private function getNameCases($name = "") {
        $arr[] = $name;
        $arr[] = ucfirst($name);

        $explodeDashes = explode('-', $name);
        if (!empty($explodeDashes)) {
            //pascal case
            $arr[] = implode('', array_map('ucwords', $explodeDashes));
            //camel case
            $arr[] = strtolower($explodeDashes[0]) . implode('', array_map('ucwords', array_slice($explodeDashes, 1)));
        }

        return array_unique($arr);
    }

    public function isDir($path = "", $dirName = "") {

        $returnDir = false;

        $cases = $this->getNameCases($dirName);

        foreach ($cases as $dir) {
            if (is_dir(rtrim($path) . DIRECTORY_SEPARATOR . $dir)) {
                $returnDir = $dir;
                break;
            }
        }

        return $returnDir;
    }

    public function isFile($path = "", $fileName = "") {

        $returnFile = false;

        $cases = $this->getNameCases($fileName);

        foreach ($cases as $file) {
            if (file_exists(rtrim($path) . DIRECTORY_SEPARATOR . $file . ".php")) {
                $returnFile = $file;
                break;
            }
        }
        return $returnFile;
    }

    public function hasMethod($obj = null, $methodName = "") {

        $returnMethod = false;

        $cases = $this->getNameCases($methodName);

        if ($obj) {
            foreach ($cases as $method) {
                if (method_exists($obj, $method)) {
                    $returnMethod = $method;
                    break;
                }
            }
        }

        return $returnMethod;
    }

}
