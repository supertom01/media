<?php
session_start();

$username = $_POST['username'];
$password = $_POST['password'];

$credentials_xxx = array(
 "tom" => "aeflamf,EFJlkjef8^&)3"
);

$credentials_no_xxx = array(
 "robin" => "meulenkamp10"
);

if ($username != '' && $password != '') {
 if(array_key_exists($username, $credentials_xxx) && $credentials_xxx[$username] == $password) {
  $_SESSION['signedIn'] = true;
  $_SESSION['viewXXX'] = true;
  header("Location: ../media.php");
 } else if (array_key_exists($username, $credentials_no_xxx) && $credentials_no_xxx[$username] == $password) {
  $_SESSION['signedIn'] = true;
  $_SESSION['viewXXX'] = false;
  header("Location: ../media.php");
 } else {
  $_SESSION['signedIn'] = false;
  $_SESSION['viewXXX'] = false;
  header("Location: ../");
 }
} else {
 $_SESSION['signedIn'] = false;
 header("Location: ../");
}

exit();
