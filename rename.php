<?php
ob_start();
error_reporting(~0);
ini_set('display_errors', 1);

require_once('php-snapchat/src/snapchat.php');

session_start();

$db = new mysqli('localhost','boatsnap','boatsnap1','boatsnap');
if($db->connect_errno > 0) {
	die('Could not connect to MySQL. Please contact the Holy Ship IT Department.');
}
$sql = <<<SQL
	SELECT snapchat_username, realname
	  FROM user
	  WHERE snapchat_username IS NOT NULL
	    AND hidden = 0
SQL;
if(!$result = $db->query($sql)) {
	die('Unable to query database. Please contact Holy Ship IT Department.');
}
while($row = $result->fetch_assoc()) {
	$lookup[$row['snapchat_username']] = $row['realname'];
}

/* load snapchat out of session */
if(isset($_SESSION['snapchat'])) {
	$snapchat = $_SESSION['snapchat'];
}
else {
	die('Not logged into Snapchat');
}

$friends = $snapchat->getFriends();
$prefix = isset($_POST['prefix'])?$_POST['prefix']:"";

foreach($snapchat->getFriends() as $friend) {
	if(!isset($lookup[$friend->name])) {
		echo "Skipping \"{$friend->name}\"; don't know user's real name.<br />\n";
		continue;
	}
	$display = $prefix.($lookup[$friend->name]);
	if($friend->display == $display) {
		echo "Skipping \"{$friend->name}\"; name is already set to $display.<br />\n";
		continue;
	}

	$o = new stdClass();
	$o->snapchat_username = $friend->name;
	$o->newDisplayName = $display;
	$o->returned = $snapchat->setDisplayName($friend->name, $display)?"OK":"Failed!";
	echo "Setting \"$friend->name\" to \"$display\"... $o->returned<br />\n";
	flush();
	ob_flush();
    usleep(100);
}

ob_end_flush();
?>