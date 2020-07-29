<?php

require_once "Database.php";
require_once dirname(__DIR__, 1) . "/Exceptions/LoginException.php";

/**
 * Class User.
 * Handles everything which has to be done with users.
 *
 * @author Tom Meulenkamp
 */
class User {

    private string $username;
    private Database $db;

    /**
     * Creates an user object.
     * @param $username string          The username of the user.
     * @param $password string          The password of the user.
     * @throws          LoginException  Thrown when the login is incorrect.
     * @throws          SQLException    Thrown when the SQL fails.
     */
    public function __construct($username, $password) {
        $this->db = new Database();

        $credentials = $this->db->preparedQuery("SELECT password FROM users WHERE username = ?",
            array([$username, SQLITE3_TEXT]));

        if(count($credentials) == 1) {
            if(!password_verify($password, $credentials[0]["password"])) {
                throw new LoginException("Password invalid!");
            } else {
                $this->username = $username;
            }
        } else {
            throw new LoginException("Account not found!");
        }
    }

    /**
     * Get the username of this user.
     * @return string   The username of the user.
     */
    public function getUsername():string {
        return $this->username;
    }

    /**
     * Creates a new user.
     * @param $username     string  The username of the user.
     * @param $password     string  The password of the user.
     * @param $categories   array   Categories where an user has initially access to.
     * @return              User    A new user object of the just created user.
     * @throws LoginException       Thrown when something went wrong while logging in. This should not happen in this case...
     * @throws SQLException         Thrown when the SQL is broken.
     */
    public static function createUser($username, $password, $categories = array()):?self {
        $db = new Database();

        $users = $db->preparedQuery("SELECT * FROM users WHERE username = ?",
            array([$username, SQLITE3_TEXT]));
        if(count($users) > 0) {
            return null;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $db->preparedUpdate("INSERT INTO users (username, password) VALUES (?, ?)",
            array([$username, SQLITE3_TEXT], [$passwordHash, SQLITE3_TEXT]));

        foreach($categories as $category) {
            $db->preparedUpdate(
                "INSERT INTO access (username, cid) VALUES (?, (SELECT cid FROM categories WHERE name = ?))",
                array([$username, SQLITE3_TEXT], [$category, SQLITE3_TEXT]));
        }

        return new User($username, $password);
    }

    /**
     * Removes this user.
     * @throws SQLException Thrown when the SQL is broken.
     */
    public function remove():void {
        $this->db->preparedUpdate("DELETE FROM users WHERE username = ?",
            array([$this->username, SQLITE3_TEXT]));
    }
}