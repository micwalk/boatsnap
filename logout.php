<?php

error_reporting(~0);
ini_set('display_errors', 1);

require_once('php-snapchat/src/snapchat.php');

session_start();

if(isset($_SESSION['snapchat'])) {
	$snapchat = $_SESSION['snapchat'];
	$snapchat->logout();
}

if(isset($_SESSION['snapchat'])) unset($_SESSION['snapchat']);
if(isset($_SESSION['snapchat_username'])) unset($_SESSION['snapchat_username']);
if(isset($_SESSION['snapchat_password'])) unset($_SESSION['snapchat_password']);

header('Location: https://shipfam.com/boatsnap/');

?>