<?php

require_once dirname(__DIR__) . "/vendor/autoload.php";
use Firebase\JWT\JWT;

class Token {

    /**
     * Name of the cookie.
     */
    private static string $cookie = "token";

    /**
     * The time (in seconds) that the token and cookie are valid.
     */
    private static int $delay = 1800;

    /**
     * The last error message.
     */
    private static string $lastError;

    /**
     * Issue a new JWT token.
     * @param $username string  The username of the user.
     */
    public static function issueJWT($username):void {
        $array = array(
            "iss" => "tomserver",
            "iat" => time(),
            "exp" => time() + self::$delay,
            "username" => $username
        );
        $payload = JWT::encode($array, JWT_SECRET);
        $_COOKIE[self::$cookie] = $payload;
        setcookie(self::$cookie, $payload, time() + self::$delay);
    }

    /**
     * Get the username for an user.
     * @return string|null  The username of the user, or null when something failed.
     */
    public static function getUsername():?string {
        try {
            if(isset($_COOKIE[self::$cookie]) && trim($_COOKIE[self::$cookie]) != "") {
                $payload = (array) JWT::decode($_COOKIE[self::$cookie], JWT_SECRET, array("HS256"));
                return $payload["username"];
            } else {
                self::$lastError = "The cookie was not set!";
                return null;
            }
        } catch (UnexpectedValueException $unexpectedValueException) {
            self::$lastError = $unexpectedValueException->getMessage();
            return null;
        }
    }

    /**
     * Refresh a token which was already present.
     * @return bool true on success, false on failure.
     */
    public static function refreshToken():bool {
        $username = self::getUsername();
        if($username != null) {
            self::issueJWT($username);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Unset and remove the JWT token.
     */
    public static function removeToken():void {
        unset($_COOKIE[self::$cookie]);
        setcookie(self::$cookie, "", -1);
    }

    /**
     * Get the last error.
     * @return string the last error.
     */
    public static function getLastError():string {
        return self::$lastError;
    }
}