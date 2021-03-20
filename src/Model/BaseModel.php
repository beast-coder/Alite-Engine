<?php

namespace Alite\Model;

use Alite\Database\Database;
use Alite\AliteException\AliteException;

/**
 * Abstract model class
 */
abstract class BaseModel {

    protected $conn = null;
    private $db = "";
    protected $table;
    private $debug = false;

    /**
     * constructor
     */
    public function __construct() {
        
    }

    protected function connect($db = []) {
        if (empty($db)) {
            if (empty($this->config['DATABASE']['default'])) {
                $error = ['Error : DATABASE', " default", ' is', ' empty', ' in config'];
                new AliteException(implode('', $error));
            } else {
                try {
                    $this->db = $this->config['DATABASE']['default'];
                    $this->conn = Database::getConnection($this->config['DATABASE']['default']);
                } catch (\Exception $e) {
                    new AliteException($e->getMessage());
                }
            }
        } else {
            $this->db = $db;
            $this->conn = Database::getConnection($db);
        }
        return $this;
    }

    public function setTable($table = '') {
        if (!empty($table)) {
            $this->table = $table;
        }
        return $this;
    }

    public function debug() {
        $this->debug = true;
        return $this;
    }

    private function getTableFields() {

        if (empty($this->table)) {
            new AliteException('Table name is missing');
        }

        $sql = "select * from information_schema.columns where table_schema = '" . $this->db['DBNAME'] . "' and table_name = '" . $this->table . "'";
        $stmt = $this->conn->prepare($sql);
        try {
            $stmt->execute();
        } catch (\Exception $e) {
            new AliteException($e->getMessage());
        }
        $tableSchema = $stmt->fetchAll();
        $fields = [];
        foreach ($tableSchema as $value) {
            $fields[] = $value['COLUMN_NAME'];
        }

        return $fields;
    }

    public function query($sql = '', array $where = [], $queryType = 'all') {

        if (!$sql) {
            return false;
        }

        if (!$this->conn) {
            $this->connect();
        }

        $result = [];

        $stmt = $this->conn->prepare($sql);
        try {
            $stmt->execute($where);
        } catch (\Exception $e) {
            new AliteException($e->getMessage());
        }
//        echo $stmt->fullQuery;
//        echo '<pre>';
//        $stmt->debugDumpParams();
//        echo '</pre>';

        switch ($queryType) {
            case 'first':
                $result = $stmt->fetch();
                break;

            case 'all':
                $result['data'] = $stmt->fetchAll();
                $result['no_of_rows'] = $stmt->rowCount();
                break;

            case 'update':
            case 'delete':
                $result['affected_rows'] = $stmt->rowCount();
                break;

            case 'insert':
                $result['insert_id'] = $this->conn->lastInsertId();
                break;

            default:
                break;
        }
        return $result;
    }

    public function insert($data = []) {

        if (!$this->conn) {
            $this->connect();
        }

        $data['created_on'] = date('Y-m-d H:i:s');
        $data['created_by'] = $_SESSION['admin']['id'];

        $fields = $this->getTableFields();
        $newData = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $fields)) {
                $newData[$key] = trim($value);
            }
        }

        //s($this->table);
        if (!empty($newData)) {

            $fieldsArr = array_keys($newData);
            $fieldsStr = implode(',', $fieldsArr);
            $questionMark = rtrim(str_repeat("?,", count($fieldsArr)), ',');
            $values = array_values($newData);

            $sql = "INSERT INTO $this->table ($fieldsStr) VALUES($questionMark)";
            $stmt = $this->conn->prepare($sql);

            if ($this->debug) {
                echo '<pre>';
                print_r($stmt->debugDumpParams());
                echo '</pre>';
            }

            try {
                $stmt->execute($values);
            } catch (\Exception $e) {
                new AliteException($e->getMessage());
            }

            return $this->conn->lastInsertId();
        } else {
            new AliteException('No Data To Save');
        }
    }

    public function update($data = [], $id) {

        if (!$this->conn) {
            $this->connect();
        }

        $data['updated_on'] = date('Y-m-d H:i:s');
        $data['updated_by'] = $_SESSION['admin']['id'];

        $fields = $this->getTableFields();
        $newData = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $fields)) {
                $newData[$key] = trim($value);
            }
        }

        if (!empty($newData)) {

            $fieldsArr = array_keys($newData);
            $fieldsStr = (implode('=?,', $fieldsArr)) . "=?";
            $values = array_values($newData);

            if (is_array($id)) {
                //$where = 
            } else {
                $where = " WHERE id = '" . $id . "'";
            }

            $sql = "UPDATE $this->table SET $fieldsStr $where";
            $stmt = $this->conn->prepare($sql);

            if ($this->debug) {
                echo '<pre>';
                print_r($stmt->debugDumpParams());
                echo '</pre>';
            }

            try {
                $stmt->execute($values);
            } catch (\Exception $e) {
                new AliteException($e->getMessage());
            }

            return $stmt->rowCount();
        } else {
            return false;
        }
    }

    public function getById($id) {

        if (empty($this->table)) {
            new AliteException('Table name is missing');
        }

        if (!$this->conn) {
            $this->connect();
        }

        $sql = "select * from $this->table where id = ?";
        $stmt = $this->conn->prepare($sql);

        if ($this->debug) {
            echo '<pre>';
            print_r($stmt->debugDumpParams());
            echo '</pre>';
        }

        try {
            $stmt->execute([$id]);
        } catch (\Exception $e) {
            new AliteException($e->getMessage());
        }

        return $stmt->fetch();
    }

    public function fetchAll($where = [], $fields = "*") {

        if (empty($this->table)) {
            new AliteException('Table name is missing');
        }

        if (!$this->conn) {
            $this->connect();
        }

        $cond = "";
        $values = [];
        if (!empty($where) && is_array($where)) {
            $keys = array_keys($where);
            $values = array_values($where);
            $cond = " WHERE " . implode("=? AND ", $keys) . "=?";
        }

        $sql = "select $fields from $this->table $cond";
        $stmt = $this->conn->prepare($sql);

        if ($this->debug) {
            echo '<pre>';
            print_r($stmt->debugDumpParams());
            echo '</pre>';
        }

        try {
            $stmt->execute($values);
        } catch (\Exception $e) {
            new AliteException($e->getMessage());
        }

        return $stmt->fetchAll();
    }

    public function fetch($where = [], $fields = "*") {

        if (empty($this->table)) {
            new AliteException('Table name is missing');
        }
        if (!$this->conn) {
            $this->connect();
        }

        $cond = "";
        $values = [];
        if (!empty($where) && is_array($where)) {
            $keys = array_keys($where);
            $values = array_values($where);
            $cond = " WHERE " . implode("=? AND ", $keys) . "=?";
        }

        $sql = "select $fields from $this->table $cond";
        $stmt = $this->conn->prepare($sql);

        if ($this->debug) {
            echo '<pre>';
            print_r($stmt->debugDumpParams());
            echo '</pre>';
        }

        try {
            $stmt->execute($values);
        } catch (\Exception $e) {
            new AliteException($e->getMessage());
        }

        return $stmt->fetch();
    }

}
