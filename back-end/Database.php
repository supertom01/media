<?php

require_once dirname(__DIR__, 1) . "/Exceptions/SQLException.php";

/**
 * The class that handles all the database related stuff.
 */
class Database {

    private SQLite3 $db;

    /**
     * Database constructor.
     * Initiates a connection with the SQLite database.
     */
    public function __construct() {
        $this->db = new SQLite3(__DIR__ . "/media.db");
        $this->db->exec("PRAGMA foreign_keys = ON");
        return $this->db;
    }

    /**
     * Get the SQLite3 database object.
     * @return SQLite3  The database object.
     */
    public function getDB():SQLite3 {
        return $this->db;
    }

    /**
     * Execute an update on the SQLite database.
     * @param $query    string  The query to execute.
     * @return          bool    True if executed successfully, otherwise false.
     * @throws SQLException     When something went wrong during SQL execution.
     */
    public function executeUpdate($query):bool {
        $success = $this->db->exec($query);
        if(!$success) {
            throw new SQLException(self::getError());
        }
        return $success;
    }

    /**
     * Execute an query on the SQLite database.
     * @param $query    string  The query to execute.
     * @return          array   The result.
     * @throws SQLException     When something went wrong during SQL execution.
     */
    public function executeQuery($query): array {
        $res = $this->db->query($query);
        if($res === false) {
            throw new SQLException(self::getError());
        }
        $arr = array();
        while ($row = $res->fetchArray()) {
            array_push($arr, $row);
        }
        return $arr;
    }

    /**
     * Execute a prepared update on the SQLite database.
     * @param $query string The query to execute.
     * @param $param array  {[0] => {[0] => "value", [1] => "PDO::type"}}.
     * @return bool         True if executed successfully, otherwise false.
     * @throws SQLException When something went wrong during SQL execution.
     */
    public function preparedUpdate($query, $param):bool {
        $stmt = $this->db->prepare($query);
        if(!$stmt) {
            throw new SQLException(self::getError());
        } else {
            for($i = 0; $i < count($param); $i++) {
                $stmt->bindValue($i + 1, $param[$i][0], $param[$i][1]);
            }
        }
        return $stmt->execute() !== false;
    }

    /**
     * Execute a prepared query on the SQLite database.
     * @param $query string The query to execute.
     * @param $param array  {[0] => {[0] => "value", [1] => "PDO::type"}}.
     * @return array        The result array.
     * @throws SQLException When something went wrong during SQL execution.
     */
    public function preparedQuery($query, $param):array {
        $stmt = $this->db->prepare($query);
        if(!$stmt) {
            throw new SQLException(self::getError());
        } else {
            for($i = 0; $i < count($param); $i++) {
                $stmt->bindValue($i + 1, $param[$i][0], $param[$i][1]);
            }
        }
        $res = $stmt->execute();
        $arr = array();
        while ($row = $res->fetchArray())  {
            array_push($arr, $row);
        }
        return $arr;
    }

    /**
     * Get the last error message.
     * @return string   The error message.
     */
    public function getError():string {
        return $this->db->lastErrorMsg();
    }
}