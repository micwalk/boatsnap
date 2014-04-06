<?php
error_reporting(~0);
ini_set('display_errors', 1);

require_once('php-snapchat/src/snapchat.php');

session_start();


/* load snapchat out of session */
if(isset($_SESSION['snapchat'])) {
	$snapchat = $_SESSION['snapchat'];
}
else {
	die('Not logged into Snapchat');
}

if($_POST['method'] == 'add') {
	$response = $snapchat->addFriend($_POST['user']);
}
else if($_POST['method'] == 'delete') {
	$response = $snapchat->deleteFriend($_POST['user']);
}
else {
	$response = "Unknown Method";
}

echo json_encode($response, JSON_PRETTY_PRINT);

?>