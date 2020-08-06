<?php

require_once __DIR__ . '/back-end/User.php';
require_once __DIR__ . '/back-end/Movie.php';
require_once __DIR__ . '/back-end/Picture.php';
require_once __DIR__ . '/back-end/Category.php';
require_once __DIR__ . '/back-end/Token.php';
require_once __DIR__ . '/back-end/FileUpload.php';
require_once __DIR__ . '/back-end/Torrent.php';
require_once __DIR__ . '/back-end/Subtitle.php';
require_once __DIR__ . '/vendor/autoload.php';
use Steampixel\Route;

/**
 * Static variables used for JSON responses.
 */
static $SUCCESS = "SUCCESS";
static $UNAUTHORIZED = "UNAUTHORIZED";
static $SQL_DOWN = "SQL_DOWN";
static $FAIL = "FAIL";

function getFiles($file)  {
    $torrent = new Torrent();
    try {
        if(isset($_POST['type']) && $_POST['type'] == "MOVIE") {
            $id = $torrent->addTorrent($file, __DIR__ . '/movies');
            $db_id = Movie::createMovie($_POST['name'], 'CHOOSE_FILE');
            $movie = new Movie($db_id);
            $movie->setIMG(FileUpload::generateImage($_POST['img']));
            $category = new Category(intval($_POST['category']));
            $category->addMovie($movie);
            return array("db_id" => $db_id, "transmission_id" => $id, "files" => $torrent->getFiles($id));
        } else if (isset($_POST['type']) && $_POST['type'] == "IMG") {
            //TODO: Pictures aren't handled that well yet. First focus on movies.
            $id = $torrent->addTorrent($file, __DIR__ . '/pics');
            $db_id = Picture::createPicture('CHOOSE_FILE');
            return array("db_id" => $db_id, "transmission_id" => $id, "files" => $torrent->getFiles($id));
        } else {
            return array("FAIL");
        }
    } catch (RuntimeException $e) {
        return array("RUNTIME_EXCEPTION", $e->getMessage());
    } catch (UploadException $e) {
        return array("FAIL", "Failed to upload img: " . $e->getMessage());
    }
}

/**
 * Check if a JSON representation was requested.
 * @return bool True if so otherwise false.
 */
function getJSON() {
    return strpos($_SERVER["HTTP_ACCEPT"],"application/json") !== false;
}

// Display the 404 page if the path is not found.
Route::pathNotFound(function () {
    readfile(__DIR__ . "/html/404.html");
});

// Display the 405 page if the method is not allowed.
Route::methodNotAllowed(function () {
    readfile(__DIR__ . "html/405.html");
});

/**
 * Login Page
 */
Route::add('/', function() {
    if(Token::getUsername() != null) {
        header("Location: home");
    } else {
        readfile(__DIR__ . "/html/signIn.html");
    }
}, 'get');

Route::add('/',
    function () {
        try {
            $usr = new User($_POST["username"], $_POST["password"]);
            if($usr->getUsername() != null && $usr->getUsername() != "") {
                header("Location: home");
            }
        } catch (LoginException $e) {
            echo "Whoops! Something went wrong during login: " . $e->getMessage();
        } catch (SQLException $e) {
            echo "The SQL is broken... " . $e->getMessage();
        }
    },
    'post');

/**
 * Registration page
 */
Route::add('/register', function () {
    readfile(__DIR__ . "/html/newUsr.html");
}, 'get');

Route::add('/register', function () {
    try {
        if($_POST["password"] === $_POST["passwordCheck"]) {
            $usr = User::createUser($_POST["username"], $_POST["password"]);
            if($usr->getUsername() != null && $usr->getUsername() != "") {
                header("Location: home");
            }
        } else {
            echo "Whoops! Both passwords should match!";
        }
    } catch (LoginException $e) {
        echo "Whoops! Something went wrong while creating your account.. You cannot login: " . $e->getMessage();
    } catch (SQLException $e) {
        echo "The SQL is broken... " . $e->getMessage();
    }

}, 'post');

/**
 * Home page
 */
Route::add('/home', function () {
    if(Token::getUsername() != null) {
        readfile(__DIR__ . "/html/home.html");
    } else {
        header("Location: .");
    }
});

Route::add('/newFile', function () {
    if(Token::getUsername() != null) {
        readfile(__DIR__ . "/html/newFile.html");
    } else {
        header("Location: .");
    }
});

/**
 * Adds a new torrent to the transmission application.
 *
 * @requires $_POST['img'], $_POST['magnet-link'], $_POST['name'], $_POST['type']
 */
Route::add('/newFile', function () use ($FAIL, $UNAUTHORIZED, $SUCCESS) {
     if(Token::getUsername() != null) {
         $magnet_link = $_POST['magnet-link'];
         if(trim($magnet_link) != "" || (isset($_POST['torrent-file']) && $_POST['torrent-file'] == "null")) {
             getFiles($magnet_link);
         } else {
             $torrent_file = new FileUpload($_FILES['torrent-file'], __DIR__ . '/torrents/');
             if($torrent_file->checkFile(array("torrent"))) {
                 if($torrent_file->upload()) {

                     echo json_encode(array_merge(array($SUCCESS), getFiles($torrent_file->getTargetFile())));
                 } else {
                     echo json_encode(array("UPLOAD_FAIL"));
                 }
             } else {
                 echo json_encode(array("INVALID_FILE"));
             }
         }
     } else {
         echo json_encode(array($UNAUTHORIZED));
     }
}, 'post');

Route::add('/newFile/([0-9]*)', function ($id) use ($FAIL) {
    $torrent = new Torrent();
    try {
        $transmission_id = $_POST['transmission_id'];
        $input = json_decode($_POST['files']);
        $files = array();
        $types = array();

        foreach($input as $file) {
            // Get the indices of the files which should be downloaded.
            array_push($files, intval($file[0]));

            // Get the type of files in order to store them in the database.
            array_push($types, array($file[2], $file[1]));
        }

        if($torrent->selectFiles(intval($transmission_id), $files)){
            $torrent->startTorrent(intval($transmission_id));

            $movie = new Movie(intval($id));
            foreach($types as $type) {
                if($type[0] == "video") {
                    $movie->setFilePath(__DIR__ . '/movies/' . $type[1]);
                } else if ($type[0] == "subtitle") {
                    $movie->addSubtitle(__DIR__ . '/movies/' . $type[1], 'English');
                } else if ($type[0] == "picture") {
                    //TODO: Handle pictures!
                }
            }
        } else {
            echo json_encode(array($FAIL, "Failed to select files..."));
        }
        echo json_encode(array("SUCCESS"));
    } catch (RuntimeException $e) {
        echo json_encode(array("RUNTIME_EXCEPTION", $e->getMessage(), $torrent->getIdleTorrents(), $torrent->getDownloading()));
    }

}, 'post');

/**
 * Add a new category.
 */
Route::add('/newCategory', function () {
    if(Token::getUsername() != null) {
        readfile(__DIR__ . "/html/newCategory.html");
    } else {
        header("Location: .");
    }
});

Route::add('/newCategory', function () use ($UNAUTHORIZED, $SQL_DOWN, $FAIL, $SUCCESS) {
    if(Token::getUsername() != null) {
        try {
            $success = Category::add($_POST['name'], FileUpload::generateImage($_POST['img']));
            if($success) {
                echo json_encode(array($SUCCESS));
            } else {
                echo json_encode(array($FAIL));
            }
        } catch (SQLException $SQLException) {
            echo json_encode(array($SQL_DOWN));
        }
    } else {
        echo json_encode(array($UNAUTHORIZED));
    }
}, 'post');

/**
 * All categories
 */
Route::add('/categories', function() use ($UNAUTHORIZED) {
    if(getJSON()) {
        if(Token::getUsername() != null) {
            $categories = Category::getAll();
            if($categories === null) {
                echo json_encode(array("SQL_DOWN" , Movie::getErrors()));
            } else if (count($categories) == 0) {
                echo json_encode(array("NO_DATA"));
            } else {
                echo json_encode($categories);
            }
        } else {
            echo json_encode(array($UNAUTHORIZED));
        }
    } else {
        if(Token::getUsername() != null) {
            readfile(__DIR__ . "/html/allCategories.html");
        } else {
            header("Location: .");
        }
    }
});

Route::add('/categories/([0-9]*)', function ($id) use ($UNAUTHORIZED) {
    $category = new Category(intval($id));
    if(getJSON()) {
        if(Token::getUsername() != null && $category->hasAccess(Token::getUsername())) {
            if(isset($_GET['type']) && $_GET['type'] == "movie") {
                echo json_encode(array($category->getName(), $category->getMovies()));
            } else {
                echo json_encode(array($category->getName(), $category->getPictures()));
            }
        } else {
            echo json_encode(array($UNAUTHORIZED));
        }
    } else {
        if(Token::getUsername() != null && $category->hasAccess(Token::getUsername())) {
            readfile(__DIR__ . "/html/category.html");
        } else if (Token::getUsername() != null && !$category->hasAccess(Token::getUsername())) {
            header("Location: home");
        } else {
            header("Location: .");
        }
    }
});


/**
 * Movies page
 */
Route::add('/films', function () use ($UNAUTHORIZED) {
    if(getJSON()) {
        if(Token::getUsername() != null) {
            $categories = Movie::getAvailableCategories(Token::getUsername());
            if($categories === null) {
                echo json_encode(array("SQL_DOWN" , Movie::getErrors()));
            } else if (count($categories) == 0) {
                echo json_encode(array("NO_DATA"));
            } else {
                echo json_encode($categories);
            }
        } else {
            echo json_encode(array($UNAUTHORIZED));
        }
    } else {
        if(Token::getUsername() != null) {
            readfile(__DIR__ . "/html/movies.html");
        } else {
            header("Location: .");
        }
    }
});

Route::add('/films/([0-9]*)', function ($id) use ($UNAUTHORIZED) {
    if(getJSON()) {
        if(Token::getUsername() != null) {
            $movie = new Movie(intval($id));
            echo json_encode(array(
                "path" => str_replace(__DIR__, "", $movie->getFilePath()),
                "subtitles" => $movie->getSubtitles(),
                "summary" => $movie->getSummary(),
                "name" => $movie->getName()
            ));
        } else {
            echo json_encode(array($UNAUTHORIZED));
        }
    } else {
        if(Token::getUsername() != null) {
            readfile(__DIR__ . "/html/movie.html");
        } else {
            header("Location: .");
        }
    }
});

/**
 * Pictures page
 */
Route::add('/pictures', function () {
    if(Token::getUsername() != null) {
        readfile(__DIR__ . "/html/pictures.html");
    } else {
        header("Location: .");
    }
});

Route::add('/pictures/([0-9]*)', function () {
    if(Token::getUsername() != null) {
        readfile(__DIR__ . "/html/picture.html");
    } else {
        header("Location: .");
    }
});

Route::add('/pictures/([0-9]*)/file', function ($id) use ($UNAUTHORIZED) {
    if(Token::getUsername() != null) {
        $picture = new Picture(intval($id));
        $path = $picture->getFilePath();
        header('Content-Type: image/jpeg');
        header('Content-Length: ' . filesize($path));
        readfile($path);
    } else {
        echo $UNAUTHORIZED;
    }
});

/**
 * Subtitles
 */
Route::add('/subtitles/([0-9]*)', function ($id) {
    $sub = new Subtitle(intval($id));
    if(!$sub->isVTT()) {
        $sub->convertToVTT();
    }
    header("Content-Type: text");
    readfile($sub->getFilepath());
});

/**
 * Logout.
 */
Route::add('/logout', function () {
    Token::removeToken();
    header("Location: .");
});

// Run the router
Route::run('/media2/');