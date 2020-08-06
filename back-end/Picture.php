<?php

require_once "Database.php";

class Picture {

    private int $id;
    private Database $db;

    private static array $errors;

    public function __construct(int $id) {
        $this->id = $id;
        $this->db = new Database();
    }

    public function getId():int {
        return $this->id;
    }

    public function getFilePath() {
        try {
            return $this->db->preparedQuery("SELECT filepath FROM pictures WHERE pid = ?",
                array([$this->id, SQLITE3_INTEGER]))[0]['filepath'];
        } catch (SQLException $e) {
            array_push(self::$errors, $e->getMessage());
            return null;
        }
    }

    public static function createPicture(string $filepath, array $categories = array(), bool $isThumbnail = false) {
        $db = new Database();
        try {
            $db->preparedUpdate("INSERT INTO pictures (filepath, thumbnail) VALUES (?, ?)",
                array([$filepath, SQLITE3_TEXT], [$isThumbnail, SQLITE3_INTEGER]));
        } catch (SQLException $e) {
            array_push(self::$errors, $e->getMessage());
            return null;
        }

        $pid = $db->getDB()->lastInsertRowID();

        try {
            foreach($categories as $category) {
                $db->preparedUpdate("INSERT INTO picture_category (pid, cid) VALUES (?, ?)",
                    array([$pid, SQLITE3_INTEGER], [$category, SQLITE3_INTEGER]));
            }
        } catch (SQLException $e) {
            array_push(self::$errors, $e->getMessage());
            return null;
        }

        return $pid;
    }
}