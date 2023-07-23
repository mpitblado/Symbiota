<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceGenbank.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/classes/OccurrenceGenbank.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/classes/OccurrenceGenbank.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/classes/OccurrenceGenbank.en.php');
header('Content-Type: text/html; charset='.$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/tools/genbankharvester.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$catalogNumber = array_key_exists('catalogNumber', $_POST) ? 1 : 0;
$collCode = array_key_exists('collCode', $_POST) ? $_POST['collCode'] : '';
$lastName = array_key_exists('lastName', $_POST) ? 1 : 0;
$startIndex = array_key_exists('startIndex', $_POST) ? $_POST['startIndex'] : '';
$limit = array_key_exists('limit', $_POST) ? $_POST['limit'] : '';
$action = array_key_exists('formsubmit', $_POST) ? $_POST['formsubmit'] : '';

$isEditor = 0;
if($IS_ADMIN || array_key_exists('CollAdmin', $USER_RIGHTS) && in_array($collId, $USER_RIGHTS['CollAdmin'])){
	$isEditor = 1;
}

$genbankManager = new OccurrenceGenbank();
$genbankManager->setCollid($collid);
$collMeta = $genbankManager->getCollMeta();
?>
<html>
<head>
	<title><?php echo $LANG['GENBANK_HARVESTER']; ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript">
		function verifyGenbankForm(){

		}
    </script>
</head>
<body>
<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/includes/header.php');
?>
<!-- This is inner text! -->
<div id="innertext">
	<?php
	if($isEditor){
		?>
		<div style="margin:10px;">

		</div>
		<?php
		if($collid){
			if($action == 'harvestLinks'){
				echo '<ul>';
				$genbankManager->populateGuids();
				$genbankManager->setCollid($collid);
				echo '</ul>';
			}
			?>
			<form name="genbankForm" action="genbankharvester.php" method="post" onsubmit="return verifyGenbankForm(this)">
				<fieldset style="padding:15px;">
					<legend><b><?php echo $LANG['GENBANK_HARVESTER']; ?></b></legend>
					<div class="row-div">
						<div class="field-div">
							<input name="catalogNumber" type="checkbox" value="1" >
							<label>Catalog Number:</label>
						</div>
					</div>
					<div class="row-div">
						<div class="field-div">
							<label>Collection Code:</label>
							<input name="collCode" type="text" value="<?php echo $collCode; ?>" >
						</div>
					</div>
					<div class="row-div">
						<div class="field-div">
							<input name="lastName" type="checkbox" value="1" >
							<label>Collector's Last Name:</label>
						</div>
					</div>
					<div class="row-div">
						<div class="field-div">
							<input name="unlinked" type="checkbox" value="1" >
							<label>Only target records without links:</label>
						</div>
					</div>
					<div class="row-div">
						<div class="field-div">
							<label>Start Index:</label>
							<input name="startIndex" type="text" value="<?php echo $startIndex; ?>" >
						</div>
					</div>
					<div class="row-div">
						<div class="field-div">
							<label>Limit:</label>
							<input name="limit" type="text" value="<?php echo $limit; ?>" >
						</div>
					</div>
					<div style="clear:both;">
						<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
						<button name="formsubmit" type="submit" value="harvestLinks"><?php echo $LANG['HARVEST_LINKS']; ?></button>
					</div>
				</fieldset>
			</form>
			<?php
		}
	}
	else echo '<h2>'.$LANG['NOT_AUTH'].'</h2>';
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>