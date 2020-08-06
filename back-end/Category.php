<?php

require_once "Database.php";
require_once "Movie.php";
require_once "Picture.php";

/**
 * Class Category.
 * Represents the category for example movies and pictures.
 * This class also handles access to these categories.
 *
 * @author Tom Meulenkamp.
 */
class Category {

    private Database $db;
    private int $id;
    private string $name;
    private int $img;
    private array $access;
    private array $errors;

    /**
     * Create a new category object.
     * @param $id int  The id of the category (cid).
     */
    public function __construct(int $id) {
        $this->id = $id;
        $this->db = new Database();
        $this->errors = array();
        $this->access = array();

        try {
            $this->name = $this->db->preparedQuery("SELECT name FROM categories WHERE cid = ?",
                array([$id, SQLITE3_INTEGER]))[0]["name"];
        } catch (SQLException $e) {
            array_push($this->errors, $e->getMessage());
        }

        try {
            $this->img = $this->db->preparedQuery("SELECT image FROM categories WHERE cid = ?",
                array([$id, SQLITE3_INTEGER]))[0]["image"];
        } catch (SQLException $e) {
            array_push($this->errors, $e->getMessage());
        }

        try {
            $access = $this->db->preparedQuery("SELECT username FROM access a, categories c WHERE a.cid AND c.cid = ?",
                array([$id, SQLITE3_INTEGER]));
            foreach ($access as $user) {
                array_push($this->access, $user["username"]);
            }
        } catch (SQLException $e) {
            array_push($this->errors, $e->getMessage());
        }

    }

    /**
     * Get the name of this category.
     * @return string   The name of this category.
     */
    public function getName():string {
        return $this->name;
    }

    /**
     * Get the image id of the category.
     * @return int      The id of the image.
     */
    public function getIMG():int {
        return $this->img;
    }

    /**
     * Check if the username has access to the category.
     * @param $username     string  The username of the user.
     * @return              bool    True if the user has access, otherwise it that false.
     */
    public function hasAccess(string $username):bool {
        return array_search($username, $this->access) !== false;
    }

    /**
     * Add access rights for this user to the provided category.
     * @param $username     string  The username of the user.
     * @return              bool    True if the update succeeded, otherwise false.
     */
    public function addAccess(string $username):bool {
        array_push($this->access, $username);
        try {
            return $this->db->preparedUpdate("INSERT INTO access (username, cid) VALUES (?, ?)",
                array([$username, SQLITE3_TEXT], [$this->id, SQLITE3_INTEGER]));
        } catch (SQLException $e) {
            array_push($this->errors, $e->getMessage());
            return false;
        }
    }

    /**
     * Remove access rights for this user for this category.
     * @param $username string  The username of the user.
     * @return          bool    True if the update succeeded, otherwise false.
     */
    public function removeAccess(string $username):bool {
        if (($key = array_search($username, $this->access)) !== false) {
            unset($this->access[$key]);
        }

        try {
            return $this->db->preparedUpdate("DELETE FROM access WHERE username = ? AND cid = ?",
                array([$username, SQLITE3_TEXT], [$this->id, SQLITE3_INTEGER]));
        } catch (SQLException $e) {
            array_push($this->errors, $e->getMessage());
            return false;
        }

    }

    /**
     * Add a new movie to this category.
     * @param Movie $movie  The movie to add.
     * @return bool         True if successful, otherwise false.
     */
    public function addMovie(Movie $movie) {
        try {
            return $this->db->preparedUpdate("INSERT INTO movie_category (mid, cid) VALUES (?, ?)",
                array([$movie->getId(), SQLITE3_INTEGER], [$this->id, SQLITE3_INTEGER]));
        } catch (SQLException $e) {
            array_push($this->errors, $e->getMessage());
            return false;
        }
    }

    /**
     * Get all the movies in this category.
     * @return array        The array with all the movies.
     */
    public function getMovies():array {
        try {
            $result = $this->db->preparedQuery("SELECT mid FROM movie_category WHERE cid = ?",
                array([$this->id, SQLITE3_INTEGER]));
            $movies = array();
            foreach($result as $movie) {
                $m = new Movie(intval($movie['mid']));
                array_push($movies, array(
                    "mid" => $m->getId(),
                    "name" => $m->getName(),
                    "img" => $m->getIMG()
                ));
            }
            return $movies;
        } catch (SQLException $e) {
            array_push($this->errors, $e->getMessage());
            return array();
        }
    }

    /**
     * Add a new picture to this category.
     * @param Picture $picture  The picture to add.
     * @return bool             True if successful, otherwise false.
     */
    public function addPicture(Picture $picture) {
        try {
            return $this->db->preparedUpdate("INSERT INTO picture_category (pid, cid) VALUES (?, ?)",
                array([$picture->getId(), SQLITE3_INTEGER], [$this->id, SQLITE3_INTEGER]));
        } catch (SQLException $e) {
            array_push($this->errors, $e->getMessage());
            return false;
        }
    }

    /**
     * Get all the pictures in this category.
     * @return array    The array with all the pictures.
     */
    public function getPictures() {
        try {
            $result = $this->db->preparedQuery("SELECT pid FROM picture_category WHERE cid = ?",
                array([$this->id, SQLITE3_INTEGER]));
            $pictures = array();
            foreach($result as $picture) {
                array_push($pictures, new Picture(intval($picture['pid'])));
            }
            return $pictures;
        } catch (SQLException $e) {
            array_push($this->errors, $e->getMessage());
            return array();
        }
    }

    /**
     * Create a new category.
     * @param $name             string  The name of the new category
     * @param $img              string  The filepath to the image for the new category
     * @param $usersWithAccess  array   Users with initial access to this category
     * @return                  bool    True if the insert is succeeded, otherwise return false.
     * @throws SQLException             Thrown when something goes wrong during the SQL execution.
     */
    public static function add(string $name, string $img, array $usersWithAccess = array()):bool {
        $db = new Database();

        // Add the picture to the database.
        $success = $db->preparedUpdate("INSERT INTO pictures (filepath, thumbnail) VALUES (?, 1)",
            array([$img, SQLITE3_TEXT]));


        // Get the pid from the picture.
        $success = $success && $pid = $db->getDB()->lastInsertRowID();

        // Add the name and pid of the new category to database.
        $success = $success && $db->preparedUpdate("INSERT INTO categories (name, image) VALUES (?, ?)",
            array([$name, SQLITE3_TEXT], [$pid, SQLITE3_INTEGER]));
        $cid = $db->getDB()->lastInsertRowID();

        // Add initial access to this category for the users in the $usersSWithAccess array.
        foreach($usersWithAccess as $user) {
            $success = $success && $db->preparedUpdate("INSERT INTO access (username, cid) VALUES (?, ?)",
                array([$user, SQLITE3_TEXT], [$pid, SQLITE3_INTEGER]));
        }

        // The person who has created this category should of course have access to it.
        if($success) {
            $c = new Category($cid);
            $c->addAccess(Token::getUsername());
        }

        return $success;
    }

    /**
     * Get all the categories in the system.
     * @return array The array with category names, ids and images.
     */
    public static function getAll():?array {
        $db = new Database();
        try {
            return $db->executeQuery("SELECT cid, name, image FROM categories c");
        } catch (SQLException $e) {
            return null;
        }
    }

    /**
     * Remove this category from the database.
     * @return bool True if the update succeeded, otherwise false.
     */
    public function remove():bool {
        try {
            return $this->db->preparedUpdate("DELETE FROM categories WHERE cid = ?",
                array([$this->id, SQLITE3_INTEGER]));
        } catch (SQLException $e) {
            array_push($this->errors, $e->getMessage());
            return false;
        }
    }

    public function getErrors():array {
        return $this->errors;
    }

}