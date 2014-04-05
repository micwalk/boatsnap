<?php
error_reporting(~0);
ini_set('display_errors', 1);

require_once('php-snapchat/src/snapchat.php');
require_once('facebook-php-sdk/src/facebook.php');

session_start();



$db = new mysqli('localhost','boatsnap','boatsnap1','boatsnap');
if($db->connect_errno > 0) {
	die('Could not connect to MySQL. Please contact the Holy Ship IT Department.');
}


$facebook = new Facebook(array(
		'appId' => get_cfg_var('facebook.appid'),
		'secret' => get_cfg_var('facebook.secret')
	));

/* Since we require SSL, it doesn't matter *AS MUCH* that we're sending passwords in plaintext over the wire */
if(isset($_POST['snapchat_username']) && isset($_POST['snapchat_password'])) {
	$_SESSION['snapchat_username'] = $_POST['snapchat_username'];
	$_SESSION['snapchat_password'] = $_POST['snapchat_password'];

}
if(isset($_SESSION['snapchat_username']) && isset($_SESSION['snapchat_password'])) {
	$snapchat = new Snapchat($_SESSION['snapchat_username'],$_SESSION['snapchat_password']);
}

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

?>

<!doctype html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
	<head>
		<title>Shipfam.com #Boatsnap Tools</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
    	<link href="css/bootstrap.min.css" rel="stylesheet">
		<link href="css/stickyfooter.css" rel="stylesheet">
		<link href="css/bootstrap-switch.css" rel="stylesheet">
		<style>
#users td {
	padding-right: 10px;
	min-height: 50px;
}
#users th {
	min-height: 50px;
}

		</style>
	</head>

<body>
	<div class="container">
		<div class="page-header">
			<h2>Shipfam.com #Boatsnap Tools</h2>
			<p>
			You are 
			<?php if($user): ?>
			<i><?=$user_profile['name']?></i> on the Facebook
			<?php else: ?>
			<i>not connected to the Facebook</i>
			<?php endif ?>

			<?php if(isset($snapchat)): ?>
			and <i><?=$snapchat->username?></i> on Snapchat
			<?php endif ?>
			</p>
		</div>
	</div>

	<div class="container">

		<?php if (!$user): ?>
			<p><a href="<?=$loginUrl?>"><img src="img/connect-fb.png" /></a></p>
		<?php elseif( !isset($snapchat)): ?>
<form class="form-horizontal" action="index.php" method="POST" role="form">
	<div class="form-group">
		<label class="col-sm-2 control-label" for="snapchat_username">Snapchat Username</label>
		<div class="col-sm-4">
			<input type="text" class="form-control" name="snapchat_username" id="snapchat_username" placeholder="Username" />
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-2 control-label" for="snapchat_password">Snapchat Password</label>
		<div class="col-sm-4">
			<input type="password" class="form-control" name="snapchat_password" id="snapchat_password" placeholder="Password" />
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<button type="submit" class="btn btn-default">Login to Snapchat</button>
		</div>
	</div>
	<div>
		<i>Why the fuck do you need my password?</i><br />
		Snapchat doesn't expose a public API, so this webpage has to pretend to be the actual snapchat app and login to
		their servers. It wasn't until <a href="http://gibsonsec.org/snapchat/fulldisclosure/">GibSec</a> reverse-engineered their app that
		anyone was able to mess around like this. Snapchat <i>probably</i> doesn't like us doing this.<br />
</form>

		<?php else: ?>

<?php
$snapFriends = [];
foreach($snapchat->getFriends() as $friend) {
	$snapFriends[$friend->name] = TRUE;
}
?>

<div class="table-responsive">
	<table class="table-hover" id="users">
		<tbody>
<?php

$sql = <<<SQL
	SELECT *
	  FROM user
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
		<td>
			<div class="btn-group">
				<input type="checkbox" class="switcher" <?=(isset($snapFriends[$row['snapchat_username']]))?"checked":""?>>
			</div>

		</td>
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
	<script>
		$(".switcher").bootstrapSwitch({
			onText : "Added",
			onColor: "success",
			offText : "Nope",
			offColor: "warning"
		});
    </script>

  </body>
</html>
<?php

$db->close();

?>