<?php

namespace Alite\Database;

use PDO;
use PDOException;
use Alite\AliteException\AliteException;

/*
 * Mysql database class - only one connection alowed
 */

class Database {

    private static $_connection;
    private static $_instance; //The single instance

    // Constructor

    private function __construct() {
        
    }

    /*
      Get an instance of the Database
      @return Instance
     */

    public static function getInstance() {
        if (!self::$_instance) {
            // If no instance then make one
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public static function getConnection(array $db) {

        try {
            if (!self::$_connection) {

                $error = "";
                if (!isset($db['DRIVER'])) {
                    $error = ['Error : DB', " DRIVER", ' is', ' not', ' set '];
                }
                if (!isset($db['HOST'])) {
                    $error = ['Error : DB', " HOST", ' is', ' not', ' set '];
                }
                if (!isset($db['DBNAME'])) {
                    $error = ['Error : DB', " DBNAME", ' is', ' not', ' set '];
                }
                if (!isset($db['USER'])) {
                    $error = ['Error : DB', " USER", ' is', ' not', ' set '];
                }
                if (!isset($db['PASSWORD'])) {
                    $error = ['Error : DB', " PASSWORD", ' is', ' not', ' set '];
                }

                if (empty($error)) {
                    // If no instance then make one
                    self::$_connection = new PDO($db['DRIVER'] . ":host=" . $db['HOST'] . ";dbname=" . $db['DBNAME'], $db['USER'], $db['PASSWORD']);
                    // set the PDO error mode to exception
                    self::$_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$_connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                } else {
                    new AliteException(implode('', $error));
                }
            }
        } catch (PDOException $e) {
            new AliteException('Connection failed: ' . $e->getMessage());
        }

        return self::$_connection;
    }

    // Magic method clone is empty to prevent duplication of connection
    private function __clone() {
        
    }

}
