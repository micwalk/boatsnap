<?php
error_reporting(~0);
ini_set('display_errors', 1);

require_once('php-snapchat/src/snapchat.php');
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



/* save user/pass to session */
if(isset($_POST['snapchat_username']) && isset($_POST['snapchat_password'])) {
	$_SESSION['snapchat_username'] = trim(strtolower($_POST['snapchat_username']));
	$_SESSION['snapchat_password'] = $_POST['snapchat_password'];
}

/* login with user/pass from session and save into session */
if(isset($_SESSION['snapchat_username']) && isset($_SESSION['snapchat_password'])) {
	$trial_connect = new Snapchat();

	if($rc = $trial_connect->login($_SESSION['snapchat_username'],$_SESSION['snapchat_password'])) {
		$_SESSION['snapchat'] = $trial_connect;
	}
	else {
		$err = "Invalid Snapchat Username or Password.";
		unset($_SESSION['snapchat']);
		unset($_SESSION['snapchat_username']);
		unset($_SESSION['snapchat_password']);
	}

}

/* load snapchat out of session */
if(isset($_SESSION['snapchat'])) {
	$snapchat = $_SESSION['snapchat'];
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
	$stmt = $db->prepare("UPDATE user SET date_updated=now(), snapchat_username=?, realname=? WHERE user_id=?");
	if(isset($_SESSION['snapchat_username'])) {
		$s = $_SESSION['snapchat_username'];
	}
	else {
		$s = $stored_username;
	}
	$stmt->bind_param("ssi", $s, $user_profile['name'], $user_id);
	$stmt->execute();
	$stmt->close();
}

?>

<!doctype html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
	<head>
		<title>Shipfam.com #Boatsnap</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta charset="UTF-8">
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
	<a href="#content" class="sr-only">Skip to main content</a>
	<div class="container">
		<div class="page-header">


			<h2>Shipfam.com #Boatsnap</h2>
			<p>
			<?php if($user): ?>
			You are <i><?=$user_profile['name']?></i> on the Facebook
			<?php endif ?>

			<?php if(isset($snapchat)): ?>
			and <i><?=$snapchat->username?></i> on Snapchat <a class="btn btn-default btn-xs" href="logout.php">Logout</a>
			<?php endif ?>
			</p>


		<?php if(isset($snapchat)): ?>
		<ul class="nav nav-pills" id="tabs">
			<li class="active"><a href="#add" data-toggle="pill">Add Users</a></li>
			<li><a href="#rename" data-toggle="pill">Rename Users</a></li>
			<li><a href="#settings" data-toggle="pill">Settings</a></li>
		</ul>
		<?php endif ?>
		</div>
	</div>

	<div class="container tab-content" id="content">
		<?php
		if(isset($err)) {
			?>
		<div class="alert alert-danger"><?=$err?></div>
			<?php
		}
		?>

		<?php if (!$user): ?>
		<p><a href="<?=$loginUrl?>"><img src="img/connect-fb.png" /></a></p>
		<?php elseif( !isset($snapchat)): ?>
		<form class="form-horizontal" action="<?=$_SERVER['PHP_SELF']?>" method="POST" role="form">
			<div class="form-group">
				<label class="col-sm-4 control-label" for="snapchat_username">Snapchat Username</label>
				<div class="col-sm-4">
					<input type="text" class="form-control" name="snapchat_username" id="snapchat_username" placeholder="Username" value="<?=(isset($stored_username))?$stored_username:''?>">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-4 control-label" for="snapchat_password">Snapchat Password</label>
				<div class="col-sm-4">
					<input type="password" class="form-control" name="snapchat_password" id="snapchat_password" placeholder="Password" />
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-4 col-sm-10">
					<button type="submit" class="btn btn-primary">Login to Snapchat</button>
					<a href="nologin.php" class="btn btn-default">Skip</a>
				</div>
			</div>
			<div>
				<i>Why do you need my password?</i><br />
				Snapchat doesn't expose a public API, so this webpage has to pretend to be the actual snapchat app and login to
				their servers.
		Your password isn't stored. The <a href="https://github.com/shipfam/boatsnap">source code</a> for this is open, if you don't believe me. If you're super-paranoid, you can <a href="nologin.php">skip</a>
		the login, but you'll have to type in everyone's usernames again manually, which sucks, but whatever buddy, you're the masochist, not me.<br />
		<br />
				<i>Why does it say my password is wrong?</i><br />
				We occasionally get thrown into Snapchat-Jail for having too many connections to Snapchat. It sucks, but hopefully they'll whitelist us eventually.
			</div>
		</form>

		<?php else: ?>

<?php
################## GET SNAPCHAT FRIENDS ###########################

$snapFriends = [];
foreach($snapchat->getFriends() as $friend) {
	$snapFriends[$friend->name] = TRUE;
}
?>



<?php
################# GET #BOATSNAP MEMBER IDS ########################
try {
	$members = $facebook->api('/144692042393217/members');
	#var_dump($members);
	#nevermind, fuck it. don't try and limit it to the #snapchat group just yet. this shit ain't working.
}
catch(FacebookApiException $e) {
	d($e);
}
?>


			<div class="tab-pane fade active in" id="add">
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
	if($row['snapchat_username'] == $_SESSION['snapchat_username']) continue;
	?>
							<tr>
								<td><a href="//facebook.com/<?=$row['facebook_id']?>" target="_blank"><img src="//graph.facebook.com/<?=$row['facebook_id']?>/picture" /></a></td>
								<td><?=$row['realname']?></td>
								<td><?=$row['snapchat_username']?></td>
								<td class="switcher-box">
									<div class="btn-group">
										<input type="checkbox" class="switcher" data-user="<?=$row['snapchat_username']?>" <?=(isset($snapFriends[$row['snapchat_username']]))?"checked":""?>>
									</div>
								</td>
							</tr>
	<?php
}
?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="tab-pane fade" id="rename">
				<h4>Batch Rename</h4>
				<p>
					This tool will rename all of your snapchat people, and setting their names to their Facebook name, optionally with a prefix like an anchor.
					Sometimes it fails; if it does, you'll see some lines end with "false". I'm not sure why this happens sometimes, but if you try again,
					it should work. I'm sorry it's not very pretty. &#x2693;

					<form class="form-horizontal" action="rename.php" method="POST" role="form" accept-charset="UTF-8">
						<div class="form-group">
							<label class="col-sm-2 control-label" for="prefix">Prefix</label>
							<div class="col-sm-4">
								<input type="text" class="form-control" name="prefix" id="prefix" placeholder="Prefix (optional)">
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-2 col-sm-10">
								<button type="submit" class="btn btn-primary">Rename</button>
							</div>
						</div>
					</form>
				</p>
			</div>
			<div class="tab-pane fade" id="settings">
				<p>
					You'll automatically be added to the list of users when you come to this page, and other shippers can add you to
					their friends. But maybe you're a creeper and you don't want anyone to add you. That's cool, bro, just turn yourself
					hidden.
				</p>
				<div class="form-group">
					<label class="col-sm-2 control-label" for="prefix">Visible</label>
					<div class="col-sm-4">
						<div class="btn-group">
							<input type="checkbox" class="hider" <?=(!$hidden)?"checked":""?>>
						</div>
					</div>
				</div>
				<div class="clearfix"></div>
			</div>
			<?php endif ?>


	</div>

    <div id="footer">
    	<div class="container">
    		<p class="text-muted">Created by <a href="https://philihp.com">Philihp Busby</a> to make your life easier. Design by <a href="http://getbootstrap.com/">Twitter Bootstrap</a>.</p>
    	</div>
    </div>
	<script src="js/jquery.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/bootstrap-switch.js"></script>
	<script src="js/bootstrap-tour.min.js"></script>
	<script>
		$(".switcher").bootstrapSwitch({
			onText : "Added",
			onColor: "success",
			offText : "Nope",
			offColor: "danger",
			onSwitchChange: function(event, state) {
				var user = $(this).data('user');
				$.ajax({
					type: 'POST',
					url: 'edit.php',
					data: {
						user: $(this).data('user'),
						method: (state)?'add':'delete'
					},
					success: function(data) {
						console.log(data);
					}
				})
			}
		});
		$(".hider").bootstrapSwitch({
			labelText: "<span class=\"glyphicon glyphicon-eye-open\"></span>",
			onText : "Visible",
			onColor: "success",
			offText : "Hidden",
			offColor: "warning",
			onSwitchChange: function(event, state) {
				$.ajax({
					type: 'POST',
					url: 'hide.php',
					data: {
						method: (state)?'visible':'hidden'
					},
					success: function(data) {
						console.log(data);
					}
				})
			}
		});

		<?php if(isset($snapchat)): ?>
		$('#tabs a').click(function (e) {
			e.preventDefault()
			$(this).tab('show')
		})


		<?php endif ?>
/*
		$(function() {
			var tour = new Tour({
				steps: [
					{
						element:".visibility-box",
						title:"Visibility",
						content: "You're automatically added to this list. You can remove yourself from it if you don't like having friends."
					},
					{
						element:".switcher-box:eq(0)",
						title:"Friends",
						content: "Switch this on if you want these people to be your friend.",
						placement:"top"
					}
				]
			});
			tour.init();
			tour.start();
		});
*/

    </script>
 <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-47237641-1', 'shipfam.com');
  ga('send', 'pageview');

</script>	
  </body>
</html>
<?php

$db->close();

?>
