<?php
error_reporting(-1);
error_log('Greg debugging barcodes.php');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLabel.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = $_POST['collid'];
$action = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';

//Sanitation
if(!is_numeric($collid)) $collid = 2;
$action = filter_var($action, FILTER_SANITIZE_STRING);

$labelManager = new OccurrenceLabel();
$labelManager->setCollid($collid);

$isEditor = 0;
if($SYMB_UID){
	if($IS_ADMIN) $isEditor = 1;
	elseif(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($labelManager->getCollid(),$USER_RIGHTS["CollAdmin"])) $isEditor = 1;
	elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($labelManager->getCollid(),$USER_RIGHTS["CollEditor"])) $isEditor = 1;
}
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> Labels</title>
		<style type="text/css">
			body { background-color:#ffffff;font-family:arial,sans-serif; font-size:10pt; }
			.barcode { width:220px; height:50px; float:left; padding:10px; text-align:center; }
		</style>
	</head>
	<body>
		<div>
			<?php
			if($action && $isEditor){
				$labelArr = $labelManager->getLabelArray($_POST['occid']);
				$labelCnt = 0;
				echo '<table class="labels"><tr>';
				foreach($labelArr as $occid => $occArr){
					if($occArr['catalognumber']){
						?>
						<div class="barcode">
							<img src="getBarcode.php?bcheight=40&bctext=<?php echo $occArr['catalognumber']; ?>" />
						</div>
						<?php
						$labelCnt++;
					}
				}
				if(!$labelCnt) echo '<div style="font-weight:bold;text-size: 120%">No records were retrieved. Perhaps the quantity values were all set to 0?</div>';
			}
			?>
		</div>
	</body>
</html>
