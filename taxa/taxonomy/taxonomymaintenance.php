<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyMaintenance.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomymaintenance.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/taxa/taxonomy/taxonomymaintenance.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomymaintenance.en.php');

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../taxa/taxonomy/taxonomymaintenance.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));


$kingdomID = array_key_exists('kingdomid', $_REQUEST) ? filter_var($_REQUEST['kingdomid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$taxAuthID = array_key_exists('taxauthid', $_REQUEST) ? filter_var($_REQUEST['taxauthid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : '';

$harvesterManager = new TaxonomyMaintenance();

$isEditor = false;
if($IS_ADMIN || array_key_exists('Taxonomy', $USER_RIGHTS)) $isEditor = true;

$reportArr = array();
$statusStr = '';
if($isEditor && $action){
	if($action == 'syncFamilies'){
		if($cnt = $harvesterManager->synchronizeFamilyQuickLookup()){
			$statusStr = 'Success batch synchronized ' . $cnt . ' records';
		}
	}
	elseif($action == 'autoPruneBadNode'){
		if($cnt = $harvesterManager->pruneBadParentNodes()){
			$statusStr = 'Successfully pruned bad nodes';
		}
	}
	$reportArr = $harvesterManager->getTaxonomyReport();

}

?>
<!DOCTYPE html>
<html lang="<?= $LANG_TAG ?>">
<head>
	<title><?= $DEFAULT_TITLE . ' ' . $LANG['TAX_MAINT'] ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= $CHARSET ?>"/>
	<link href="<?= $CSS_BASE_PATH ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?= $CLIENT_ROOT ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?= $CLIENT_ROOT ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript" src="../js/symb/shared.js"></script>
</head>
<body>
	<?php
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href="../index.php"><?= $LANG['HOME'] ?></a> &gt;&gt;
		<b><?= $LANG['TAX_TREE_VIEW'] ?></b>
	</div>
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['TAX_MAINT']; ?></h1>
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="color:<?= (strpos($statusStr,'SUCCESS') !== false ? 'green' : 'red') ?>;margin:15px;">
				<?= $statusStr ?>
			</div>
			<hr/>
			<?php
		}
		if($isEditor){
			if($reportArr){
				?>
				<div>
					<div>
						<label>Mismatched families: </label> <?= $reportArr['mismatchedFamilies'] ?> <img class="icon" src="../../images/triangledown.png" onclick="toggleElement('mismatchFamilies-div', 'block')">
						<div id="mismatchFamilies-div" style="display: none">
							<div>
								Quick-lookup family field is out of sync with defined hierarchy. The button below will syncronize the quick-lookup field.
								If all taxon records fail to update, there is likely an issue with the hierarchy (e.g. taxa linked to multiple families).
							</div>
							<form name="mismatchFamilyForm" method="post" action="taxonomymaintenance.php">
								<button name="action" type="submit" value="syncFamilies">Synchronize Families</button>
								<input name="kingdomid" value="<?= $kingdomID ?>" >
								<input name="taxauthid" value="<?= $taxAuthID ?>" >
							</form>
						</div>
					</div>
					<div>
						<label>Illegal parents: </label> <?= $reportArr['illegalParents'] ?> <img class="icon" src="../../images/triangledown.png" onclick="toggleElement('illegalParents-div', 'block')">
						<div id="illegalParents-div" style="display: none">
							<div>
								Taxa linked to parents that have a greater rankID is not allowed. Repair options include:
								<ul>
									<li>List taxa for manual cleaning</li>
									<li>Automatically prune out bad nodes</li>
								</ul>
							</div>
							<form name="listBadParentsForm" method="post" action="taxonomymaintenance.php">
								<button name="action" type="submit" value="listBadParents">List Bad Parents</button>
								<input name="kingdomid" value="<?= $kingdomID ?>" >
								<input name="taxauthid" value="<?= $taxAuthID ?>" >
							</form>
							<form name="autoPruneBadNodeForm" method="post" action="taxonomymaintenance.php">
								<button name="action" type="submit" value="autoPruneBadNode">Automatically Prune Bad Node</button>
								<input name="kingdomid" value="<?= $kingdomID ?>" >
								<input name="taxauthid" value="<?= $taxAuthID ?>" >
							</form>
						</div>
					</div>


				</div>


		$retArr['illegalParents'] = $this->getIllegalParentCount();
		$retArr['illegalAccepted'] = $this->getIllegalAcceptedCount();
		$retArr['infraspIssues'] = $this->getMislinkedInfraspecificCount();
		$retArr['speciesIssues'] = $this->getMislinkedSpeciesCount();
		$retArr['generaIssues'] = $this->getMislinkedGeneraCount();

				<?php
			}
			elseif($action == 'listBadParents'){

			}
		}
		else{
			?>
			<div style="margin:30px;font-weight:bold;font-size:120%;">
				<?= $LANG['NOT_AUTH'] ?>
			</div>
			<?php
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>