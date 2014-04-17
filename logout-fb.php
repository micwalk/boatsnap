<?php

error_reporting(~0);
ini_set('display_errors', 1);

require_once('facebook-php-sdk/src/facebook.php');

session_start();

$facebook = new Facebook(array(
    'appId' => get_cfg_var('facebook.appid'),
    'secret' => get_cfg_var('facebook.secret')
  ));
$facebook->destroySession();

header('Location: https://shipfam.com/boatsnap/');
?>