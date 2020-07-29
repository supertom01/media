<?php

require_once __DIR__ . '/back-end/User.php';

// Autoload files using composer
require_once __DIR__ . '/vendor/autoload.php';
use Steampixel\Route;

Route::add('/', function() {
    readfile(__DIR__ . "/html/signIn.html");
}, 'get');

Route::add('/',
    function () {
        try {
            $usr = new User($_POST["username"], $_POST["password"]);
        } catch (LoginException $e) {
            echo "Whoops! Something went wrong during login: " . $e->getMessage();
        } catch (SQLException $e) {
            echo "The SQL is broken... " . $e->getMessage();
        }
    },
    'post');

Route::add('/register', function () {
    readfile(__DIR__ . "/html/newUsr.html");
}, 'get');

Route::add('/register', function () {
    try {
        $usr = User::createUser($_POST["username"], $_POST["password"]);
        if($usr->getUsername() != null && $usr->getUsername() != "") {
            header("Location: /home");
        }
    } catch (LoginException $e) {
        echo "Whoops! Something went wrong while creating your account.. You cannot login: " . $e->getMessage();
    } catch (SQLException $e) {
        echo "The SQL is broken... " . $e->getMessage();
    }

}, 'post');

Route::add('/home', function () {

});

Route::add('/movies', function () {
    readfile(__DIR__ . "/html/movies.html");
});

Route::add('/pictures', function () {
    readfile(__DIR__ . "/html/pictures.html");
});

// Run the router
Route::run('/media2/');