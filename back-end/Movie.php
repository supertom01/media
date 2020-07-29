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

    private array $errors;

    private Database $db;

    /**
     * Constructs a movie object and gets all the information about a movie.
     * @param int $id   The id of the movie.
     */
    public function __construct(int $id) {
        $this->id = $id;
        $this->db = new Database();
        $this->errors = array();

        try {
            // Get the name, filepath and summary of the movie.
            $movie = $this->db->preparedQuery("SELECT * FROM movies WHERE mid = ?", array([$id, PDO::PARAM_INT]))[0];
            $this->name = $movie["name"];
            $this->filepath = $movie["filepath"];
            $this->summary = $movie["summary"];

            // Get the image/thumbnail of the movie.
            $this->img = $this->db->preparedQuery("SELECT p.filepath FROM movies m, pictures p WHERE m.image = p.pid AND m.mid = ?",
                array([$id, PDO::PARAM_INT]))[0]["filepath"];

            // Get the subtitles of the movie.
            $this->subtitles = $this->db->preparedQuery("SELECT s.filepath, s.language FROM subtitles s WHERE s.mid = ?",
                array([$id, PDO::PARAM_INT]));
        } catch (SQLException $se) {
            array_push($errors, $se->getMessage());
        }
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

    public function getErrors():string {
        return implode("<br>", $this->errors);
    }

    public function setName(string $name):bool {
        $this->name = $name;
        try {
            return $this->db->preparedUpdate("UPDATE movies SET name = ? WHERE mid = " . $this->id,
                array([$name, SQLITE3_TEXT]));
        } catch (SQLException $e) {
            array_push($this->errors, $e->getMessage());
            return false;
        }
    }

    public function setIMG(string $img):bool {
        $this->img = $img;
        try {
            $success = $this->db->preparedUpdate("INSERT INTO pictures (filepath, thumbnail) VALUES (?, 1)",
                array([$img, SQLITE3_TEXT]));
        } catch (SQLException $e) {
            array_push($this->errors, $e->getMessage());
            $success = false;
        }

        $img_id = $this->db->getDB()->lastInsertRowID();

        try {
            $success = $success && $this->db->executeUpdate("UPDATE movies SET image = " . $img_id . " WHERE mid = " . $this->id);
        } catch (SQLException $e) {
            array_push($this->errors, $e->getMessage());
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
            array_push($this->errors, $e->getMessage());
            return false;
        }
    }

    public function addSubtitle(string $filepath, string $language) {
        array_push($this->subtitles, array($filepath, $language));
        try {
            return $this->db->preparedUpdate("INSERT INTO subtitles (mid, language, filepath) VALUES (" . $this->id . ", ?, ?)",
                array([$language, SQLITE3_TEXT], [$filepath, SQLITE3_TEXT]));
        } catch (SQLException $e) {
            array_push($this->errors, $e->getMessage());
            return false;
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
            $m_error = $e->getMessage();
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
            $t_error = $e->getMessage();
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
            $s_error = $e->getMessage();
        }


        if(!$movie) {
            echo "Error while adding movie!<br>";
            if(isset($m_error)) {
                echo "DB error: " . $m_error . "<br>";
            }
        }
        if(!$subtitle) {
            echo "Error while adding subtitles!<br>";
            if(isset($s_error)) {
                echo "DB error: " . $s_error . "<br>";
            }
        }
        if(!$thumbnail) {
            echo "Error while adding thumbnail!<br>";
            if(isset($t_error)) {
                echo "DB error: " . $t_error . "<br>";
            }
        }

        return $id;
    }
}