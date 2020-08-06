<?php

require_once "Database.php";

/**
 * Handles everything that involves movies.
 */
class Movie {

    private int $id;
    private string $name;
    private string $filepath;
    private ?string $img;
    private ?string $summary;
    private ?array $subtitles;

    private static array $errors;

    private Database $db;

    /**
     * Constructs a movie object and gets all the information about a movie.
     * @param int $id   The id of the movie.
     */
    public function __construct(int $id) {
        $this->id = $id;
        $this->db = new Database();
        self::$errors = array();

        try {
            // Get the name, filepath and summary of the movie.
            $movie = $this->db->preparedQuery("SELECT * FROM movies WHERE mid = ?", array([$id, SQLITE3_INTEGER]))[0];
            $this->name = $movie["name"];
            $this->filepath = $movie["filepath"];
            $this->summary = $movie["summary"];
            $this->img = $movie["image"];

            // Get the subtitles of the movie.
            $this->subtitles = $this->db->preparedQuery("SELECT s.sid, s.language FROM subtitles s WHERE s.mid = ?",
                array([$id, SQLITE3_INTEGER]));
        } catch (SQLException $se) {
            array_push($errors, $se->getMessage());
        }
    }

    public function getId():int {
        return $this->id;
    }

    public function getName():string {
        return $this->name;
    }

    public function getFilePath():string {
        return $this->filepath;
    }

    public function getIMG():string {
        return $this->img;
    }

    public function getSummary():string {
        return $this->summary;
    }

    public function getSubtitles():array {
        return $this->subtitles;
    }

    public static function getErrors():string {
        return implode("<br>", self::$errors);
    }

    public function setFilePath(string $filepath) {
        $this->filepath = $filepath;
        try {
            return $this->db->preparedUpdate("UPDATE movies SET filepath = ? WHERE mid = " . $this->id,
                array([$filepath, SQLITE3_TEXT]));
        } catch (SQLException $e) {
            array_push(self::$errors, $e->getMessage());
            return false;
        }
    }

    public function setName(string $name):bool {
        $this->name = $name;
        try {
            return $this->db->preparedUpdate("UPDATE movies SET name = ? WHERE mid = " . $this->id,
                array([$name, SQLITE3_TEXT]));
        } catch (SQLException $e) {
            array_push(self::$errors, $e->getMessage());
            return false;
        }
    }

    public function setIMG(string $img):bool {
        $this->img = $img;
        try {
            $success = $this->db->preparedUpdate("INSERT INTO pictures (filepath, thumbnail) VALUES (?, 1)",
                array([$img, SQLITE3_TEXT]));
        } catch (SQLException $e) {
            array_push(self::$errors, $e->getMessage());
            $success = false;
        }

        $img_id = $this->db->getDB()->lastInsertRowID();

        try {
            $success = $success && $this->db->executeUpdate("UPDATE movies SET image = " . $img_id . " WHERE mid = " . $this->id);
        } catch (SQLException $e) {
            array_push(self::$errors, $e->getMessage());
            $success = false;
        }

        return $success;
    }

    public function setSummary(string $summary):bool {
        $this->summary = $summary;
        try {
            return $this->db->preparedUpdate("UPDATE movies SET summary = ? WHERE mid = " . $this->id,
                array([$summary, SQLITE3_TEXT]));
        } catch (SQLException $e) {
            array_push(self::$errors, $e->getMessage());
            return false;
        }
    }

    public function addSubtitle(string $filepath, string $language) {
        array_push($this->subtitles, array($filepath, $language));
        try {
            return $this->db->preparedUpdate("INSERT INTO subtitles (mid, language, filepath) VALUES (" . $this->id . ", ?, ?)",
                array([$language, SQLITE3_TEXT], [$filepath, SQLITE3_TEXT]));
        } catch (SQLException $e) {
            array_push(self::$errors, $e->getMessage());
            return false;
        }
    }

    /**
     * Get all available categories for a provided username.
     * @param   string      $username   The username of the user who want access.
     * @return  array|null              Null when nothing found, otherwise an array with the cid, name and filepath.
     */
    public static function getAvailableCategories(string $username) {
        $db = new Database();
        try {
            return $db->preparedQuery("SELECT c.cid, c.image, c.name FROM categories c, access a, movie_category mc " .
                "WHERE a.cid = c.cid AND mc.cid = c.cid AND a.username = ?", array([$username, SQLITE3_TEXT]));
        } catch (SQLException $e) {
            array_push(self::$errors, $e->getMessage());
            return null;
        }
    }

    /**
     * Creates a new movie in the database.
     * @param string $name The name of the movie.
     * @param string $filepath The filepath of the .mp4 file.
     * @param string|null $img The filepath of the thumbnail.
     * @param array|null $subtitles The array with information about the subtitles [["language", "filepath"]].
     * @param string|null $summary A short summary from the movie.
     * @return int The id of the movie.
     */
    public static function createMovie(string $name, string $filepath, string $img = null,
                                       array $subtitles = null, string $summary = null):int {
        if($summary == null) {
            $summary = "NULL";
        }

        $movie = true;
        $subtitle = true;
        $thumbnail = true;

        $db = new Database();
        $id = -1;

        // Add the new movie to the database.
        try {
            $movie = $db->preparedUpdate("INSERT INTO movies (name, filepath, summary) VALUES (?, ?, ?)",
                array([$name, SQLITE3_TEXT], [$filepath, SQLITE3_TEXT], [$summary, SQLITE3_TEXT]));
            $id = $db->getDB()->lastInsertRowID();
        } catch (SQLException $e) {
            array_push(self::$errors, $e->getMessage());
        }

        // Add a thumbnail to the movie.
        try {
            if($img != null) {
                $thumbnail = $db->preparedUpdate("INSERT INTO pictures (filepath, thumbnail) VALUES (?, 1)",
                    array([$img, SQLITE3_TEXT]));
                $img_id = $db->getDB()->lastInsertRowID();
                $thumbnail = $thumbnail && $db->executeUpdate("UPDATE movies SET image = $img_id WHERE mid = $id");
            }
        } catch (SQLException $e) {
            array_push(self::$errors, $e->getMessage());
        }

        // Add subtitles to the movie.
        try {
            if($subtitles != null && count($subtitles) > 0) {
                foreach ($subtitles as $sub) {
                    $subtitle = $subtitle && $db->preparedUpdate("INSERT INTO subtitles (mid, language, filepath) VALUES ($id, ?, ?)",
                            array([$sub["language"], SQLITE3_TEXT], [$sub["filepath"], SQLITE3_TEXT]));
                }
            }
        } catch (SQLException $e) {
            array_push(self::$errors, $e->getMessage());
        }

        return $id;
    }
}