<?php

namespace Alite\Model;

use Service\Database\Database;
use PDOException;
use PDO;

/**
 * Abstract model class
 */
abstract class BaseModel {

    protected $table;
    protected $fields = array();
    protected $dbConn = null;
    protected $sql;

    /**
     * constructor
     */
    public function __construct() {
        if (!is_object($this->dbConn))
            $this->dbConn = Database::getConnection();
    }

    /**
     * @author Amit Singh
     * @param type array $data
     * @description bind data with class property array
     */
    protected function bind($data = array()) {

        foreach ($this->fields as $key => $value) {

            if (array_key_exists($key, $data)) {
                $this->fields[$key] = $data[$key];
            }
        }
    }

    /**
     * @author Amit Singh
     * @param type string $fieldValue
     * @param type string $fields
     * @param type string $fieldName
     * @return type array
     */
    public function fetch($fieldValue = 0, $fieldName = 'id', $fields = '*') {

        $this->sql = "SELECT $fields FROM $this->table WHERE $fieldName = $fieldValue LIMIT 1";
        $stmt = $this->dbConn->prepare($this->sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row;
    }

    /**
     * 
     * @param type $fields
     * @return type
     */
    public function fetchAll($fields = '*') {

        $this->sql = "SELECT $fields FROM $this->table";
        $stmt = $this->dbConn->prepare($this->sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }

    /**
     * 
     * @param type $sql
     * @return type
     */
    public function query($sql = '') {

        $this->sql = $sql;
        $stmt = $this->dbConn->prepare($this->sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }

    /**
     * @author Amit Singh
     * @method insert
     * @param type array $data
     */
    public function insert($data = array()) {

        $this->bind($data);

        $this->sql = 'INSERT INTO ' . $this->table;
        $fields = $fieldValues = '';
        foreach ($this->fields as $key => $value) {
            $fields .= '`' . $key . '`,';
            $fieldValues .= ':' . $key . ',';
        }

        $this->sql .= '(' . trim($fields, ',') . ') VALUES (' . trim($fieldValues, ',') . ')';

        try {

            $statement = $this->dbConn->prepare($this->sql);
            $statement->execute($this->fields);
        } catch (PDOException $e) {
            echo 'Insert Error : ' . $e->getMessage();
            exit;
        }
    }

    /**
     * @author Amit Singh
     * @method update
     * @param type array $data
     * @param type string $field
     * @param type int $id
     */
    public function update($data = array(), $field = 'id') {

        $this->sql = 'UPDATE ' . $this->table . ' SET ';
        $fields = $fieldValues = '';
        foreach ($data as $key => $value) {

            if ($key == $field)
                continue;

            $this->sql .= '`' . $key . '` = ' . ':' . $key . ', ';
        }

        $this->sql = trim($this->sql, ', ');
        $this->sql .= ' WHERE ' . '`' . $field . '` = :' . $field;

        try {

            $statement = $this->dbConn->prepare($this->sql);
            $statement->execute($data);
            $this->bind($data);
        } catch (PDOException $e) {
            echo 'Update Error : ' . $e->getMessage();
            exit;
        }
    }

}
