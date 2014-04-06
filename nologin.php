<?php
error_reporting(~0);
ini_set('display_errors', 1);

require_once('facebook-php-sdk/src/facebook.php');

session_start();

$facebook = new Facebook(array(
		'appId' => get_cfg_var('facebook.appid'),
		'secret' => get_cfg_var('facebook.secret')
	));
$user = $facebook->getUser();

if($user) {
	try {
		// Proceed knowing you have a logged in user who's authenticated.
		$user_profile = $facebook->api('/me');
	} catch (FacebookApiException $e) {
		error_log($e);
		$user = null;
	}
}

if ($user) {
  $logoutUrl = $facebook->getLogoutUrl();
} else {
  $loginUrl = $facebook->getLoginUrl();
}

$db = new mysqli('localhost','boatsnap','boatsnap1','boatsnap');
if($db->connect_errno > 0) {
	die('Could not connect to MySQL. Please contact the Holy Ship IT Department.');
}


if(isset($user_profile['id']) && $stmt = $db->prepare("SELECT user_id, snapchat_username, hidden FROM user WHERE facebook_id = ? ORDER BY date_updated DESC")) {
	$stmt->bind_param("s", $user_profile['id']);
	$stmt->execute();
	$stmt->store_result();
	if($stmt->num_rows == 0) {
		if($stmt2 = $db->prepare("INSERT INTO user (date_created, date_updated, realname, facebook_id) VALUES (now(), now(), ?, ?)")) {
			$stmt2->bind_param("ss", $user_profile['name'], $user_profile['id']);
			$stmt2->execute();
			$stmt2->close();
		}
	}
	else {
		$stmt->bind_result($user_id, $stored_username, $hidden);
		$stmt->fetch();
	}
	$stmt->close();
}


if(isset($user_id)) {
	$stmt = $db->prepare("UPDATE user SET date_updated=now(), realname=? WHERE user_id=?");
	$stmt->bind_param("si", $user_profile['name'], $user_id);
	$stmt->execute();
	$stmt->close();
}

?>

<!doctype html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
	<head>
		<title>Shipfam.com #Boatsnap</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
    	<link href="css/bootstrap.min.css" rel="stylesheet">
		<link href="css/stickyfooter.css" rel="stylesheet">
		<link href="css/bootstrap-switch.css" rel="stylesheet">
		<link href="css/bootstrap-tour.min.css" rel="stylesheet">
		<style>
#users td {
	padding-right: 10px;
}
		</style>
	</head>

<body>
	<div class="container">
		<div class="page-header">
			<h2>Shipfam.com #Boatsnap</h2>
			<p>
			You are 
			<?php if($user): ?>
			<i><?=$user_profile['name']?></i> on the Facebook
			<?php else: ?>
			<i>not connected to the Facebook</i>
			<?php endif ?>
			and not logged into Snapchat.
			</p>
		</div>
			<div class="alert alert-warning">You are not logged into Snapchat. List is read-only. <a href="index.php">Click Here</a> to login and enable one-click adding.</div>
	</div>

	<div class="container">

		<?php if (!$user): ?>
			<p><a href="<?=$loginUrl?>"><img src="img/connect-fb.png" /></a></p>
		<?php else: ?>

<div class="table-responsive">
	<table class="table-hover" id="users">
		<tbody>
<?php
################ BUILD TABLE ###################################

$sql = <<<SQL
	SELECT *
	  FROM user
	  WHERE snapchat_username IS NOT NULL
	    AND hidden = 0
	  ORDER BY date_updated DESC
SQL;

if(!$result = $db->query($sql)) {
	die('Unable to query database. Please contact Holy Ship IT Department.');
}

while($row = $result->fetch_assoc()) {
	?>
			<tr>
				<td><a href="//facebook.com/<?=$row['facebook_id']?>" target="_blank"><img src="//graph.facebook.com/<?=$row['facebook_id']?>/picture" /></a></td>
				<td><?=$row['realname']?></td>
				<td><?=$row['snapchat_username']?></td>
			</tr>
	<?php
}
?>
		</tbody>
	</table>
</div>

			<?php endif ?>
	</div>

    <div id="footer">
    	<div class="container">
    		<p class="text-muted">This was written by <a href="https://philihp.com">Philihp Busby</a> to make your life easier.</p>
    	</div>
    </div>
	<script src="js/jquery.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/bootstrap-switch.js"></script>
	<script src="js/bootstrap-tour.min.js"></script>
  </body>
</html>
<?php

$db->close();

?>
