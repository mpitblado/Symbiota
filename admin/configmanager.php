<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/AdminConfig.php');
header("Content-Type: text/html; charset=".$CHARSET);

$action = isset($_POST['action']) ? $_POST['action'] : '';

if(!$SYMB_UID) header('Location: ../profile/index.php?refurl=../admin/adminconfig.php');

$adminConfig = new AdminConfig();

?>
<html lang="en">
	<head>
		<title>Configuration Variable Manager</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<style type="text/css">
			label{ font-weight:bold; }
			fieldset{ padding: 15px }
			fieldset legend{ font-weight:bold; }
			.info-div{ margin:5px 5px 20px 5px; }
			.form-section{ margin: 5px 10px; }
			button{ margin: 15px; }
		</style>
	</head>
	<body>
		<?php
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div role="main" id="innertext">
			<h1>Configuration Variable Manager</h1>
			<?php
			if($IS_ADMIN){

			}
			else{
				echo '<div>Not Authorized</div>';
			}
			?>
		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>
