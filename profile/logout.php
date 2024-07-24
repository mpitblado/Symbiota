<?php
include_once('../config/symbini.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$action = array_key_exists('action', $_REQUEST) ? htmlspecialchars($_REQUEST['action'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$userId = array_key_exists('userid', $_REQUEST) ? filter_var($_REQUEST['userid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$tabIndex = array_key_exists('tabindex',$_REQUEST) ? filter_var($_REQUEST['tabindex'], FILTER_SANITIZE_NUMBER_INT) : 0;

?>

<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>

</head>
<body>
	<?php
	$displayLeftMenu = (isset($profile_viewprofileMenu)?$profile_viewprofileMenu:"true");
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href='../index.php'><?php echo htmlspecialchars((isset($LANG['HOME'])?$LANG['HOME']:'Home'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
		<a href="../profile/viewprofile.php"><?php echo htmlspecialchars((isset($LANG['MY_PROFILE'])?$LANG['MY_PROFILE']:'My Profile'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
	</div>
	<div role="main" id="innertext">
		<h1 class="page-heading">Logged Out</h1>
		
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>