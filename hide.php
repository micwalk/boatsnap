<?php
error_reporting(~0);
ini_set('display_errors', 1);

require_once('facebook-php-sdk/src/facebook.php');

$facebook = new Facebook(array(
		'appId' => get_cfg_var('facebook.appid'),
		'secret' => get_cfg_var('facebook.secret')
	));
$user = $facebook->getUser();

if($_POST['method'] == 'hidden') {
	$hidden = 1;
}
else {
	$hidden = 0;
}

$db = new mysqli('localhost','boatsnap','boatsnap1','boatsnap');
if($db->connect_errno > 0) {
	die(json_encode('Could not connect to MySQL. Please contact the Holy Ship IT Department.', JSON_PRETTY_PRINT));
}

if(isset($user) && $stmt = $db->prepare("SELECT user_id FROM user WHERE facebook_id = ? ORDER BY date_updated DESC")) {
	$stmt->bind_param("s", $user);
	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result($user_id);
	$stmt->fetch();
	$stmt->close();
}
else die(json_encode("Failed to find user with ID "+$user, JSON_PRETTY_PRINT));

if($stmt = $db->prepare("UPDATE user SET date_updated=now(), hidden=? WHERE user_id=?")) {
	$stmt->bind_param("ii", $hidden, $user_id);
	$stmt->execute();
	$stmt->close();
}
else die(json_encode("Failed to update hidden field."));

echo json_encode("OK", JSON_PRETTY_PRINT);

?>