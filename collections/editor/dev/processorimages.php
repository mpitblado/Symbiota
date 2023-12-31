<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorBuilder.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorImages.php');
header('Content-Type: text/html; charset='.$CHARSET);

$occId = array_key_exists('occid', $_REQUEST) ? filter_var($_REQUEST['occid'], FILTER_SANITIZE_NUMBER_INT) : '';
$collId = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : false;
$tabTarget = array_key_exists('tabtarget', $_REQUEST) ? filter_var($_REQUEST['tabtarget'], FILTER_SANITIZE_NUMBER_INT) : 0;
$goToMode = array_key_exists('gotomode', $_REQUEST) ? filter_var($_REQUEST['gotomode'], FILTER_SANITIZE_NUMBER_INT) : 0;
$occIndex = array_key_exists('occindex', $_REQUEST) ? filter_var($_REQUEST['occindex'], FILTER_SANITIZE_NUMBER_INT) : false;
$crowdSourceMode = array_key_exists('csmode', $_REQUEST) ? filter_var($_REQUEST['csmode'], FILTER_SANITIZE_NUMBER_INT) : 0;
$action = array_key_exists('submitaction', $_REQUEST) ? $_REQUEST['submitaction'] : '';
if(!$action && array_key_exists('carryover', $_REQUEST)) $goToMode = 2;

$occManager = new OccurrenceEditorImages();

$isGenObs = 0;
$collMap = Array();
$collType = 'spec';
$occArr = array();
$imgArr = array();
$specImgArr = array();
$fragArr = array();
$qryCnt = false;
$moduleActivation = array();
$statusStr = '';
$navStr = '';

$isEditor = 0;
$LOCALITY_AUTO_LOOKUP = 1;
$CATNUM_DUPE_CHECK = true;
$OTHER_CATNUM_DUPE_CHECK = true;
if($SYMB_UID){
	//Set variables
	$occManager->setOccId($occId);
	$occManager->setCollId($collId);
	$collMap = $occManager->getCollMap();
	if($collId && isset($collMap['collid']) && $collId != $collMap['collid']){
		$collId = $collMap['collid'];
		$occManager->setCollId($collId);
	}
	if($collMap){
		if($collMap['colltype']=='General Observations'){
			$isGenObs = 1;
			$collType = 'obs';
		}
		elseif($collMap['colltype']=='Observations'){
			$collType = 'obs';
		}
		$propArr = $occManager->getDynamicPropertiesArr();
		if(isset($propArr['modules-panel'])){
			foreach($propArr['modules-panel'] as $module){
				if(isset($module['paleo']['status']) && $module['paleo']['status']) $moduleActivation[] = 'paleo';
				elseif(isset($module['matSample']['status']) && $module['matSample']['status']){
					$moduleActivation[] = 'matSample';
					if($tabTarget > 3) $tabTarget++;
				}
			}
		}
	}


	//0 = not editor, 1 = admin, 2 = editor, 3 = taxon editor, 4 = crowdsource editor or collection allows public edits
	//If not editor, edits will be submitted to omoccuredits table but not applied to omoccurrences
	if($IS_ADMIN || ($collId && array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollAdmin']))){
		$isEditor = 1;
	}
	else{
		if($isGenObs){
			if(!$occId && array_key_exists('CollEditor',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollEditor'])){
				//Approved General Observation editors can add records
				$isEditor = 2;
			}
			elseif($action){
				//Lets assume that Edits where submitted and they remain on same specimen, user is still approved
				 $isEditor = 2;
			}
			elseif($occManager->getObserverUid() == $SYMB_UID){
				//Users can edit their own records
				$isEditor = 2;
			}
		}
		elseif(array_key_exists('CollEditor',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollEditor'])){
			//Is an assigned editor for this collection
			$isEditor = 2;
		}
		elseif($crowdSourceMode && $occManager->isCrowdsourceEditor()){
			//Is a crowdsourcing editor (CS status is open (=0) or CS status is pending (=5) and active user was original editor
			$isEditor = 4;
		}
		elseif($collMap && $collMap['publicedits']){
			//Collection is set as allowing public edits
			$isEditor = 4;
		}
		elseif(array_key_exists('CollTaxon',$USER_RIGHTS) && $occId){
			//Check to see if this user is authorized to edit this occurrence given their taxonomic editing authority
			$isEditor = $occManager->isTaxonomicEditor();
		}
	}
	include_once 'editProcessor.php';
	if($action == 'saveOccurEdits'){
		$statusStr = $occManager->editOccurrence($_POST,$isEditor);
	}
	if($isEditor && $isEditor != 3){
		if($action == 'Save OCR'){
			$statusStr = $occManager->insertTextFragment($_POST['imgid'],$_POST['rawtext'],$_POST['rawnotes'],$_POST['rawsource']);
			if(is_numeric($statusStr)){
				$newPrlid = $statusStr;
				$statusStr = '';
			}
		}
		elseif($action == 'Save OCR Edits'){
			$statusStr = $occManager->saveTextFragment($_POST['editprlid'],$_POST['rawtext'],$_POST['rawnotes'],$_POST['rawsource']);
		}
		elseif($action == 'Delete OCR'){
			$statusStr = $occManager->deleteTextFragment($_POST['delprlid']);
		}
	}
	if($isEditor){
		//Available to full editors and taxon editors
		if($action == 'submitDetermination'){
			//Adding a new determination
			$statusStr = $occManager->addDetermination($_POST,$isEditor);
			$tabTarget = 1;
		}
		elseif($action == 'submitDeterminationEdit'){
			$statusStr = $occManager->editDetermination($_POST);
			$tabTarget = 1;
		}
		elseif($action == 'Delete Determination'){
			$statusStr = $occManager->deleteDetermination($_POST['detid']);
			$tabTarget = 1;
		}
		//Only full editors can perform following actions
		if($isEditor == 1 || $isEditor == 2){
			if($action == 'addOccurRecord'){
				if($occManager->addOccurrence($_POST)){
					$occManager->setQueryVariables();
					$qryCnt = $occManager->getQueryRecordCount();
					$qryCnt++;
					if($goToMode) $occIndex = $qryCnt;			//Go to new record
					else $occId = $occManager->getOccId();		//Stay on record and get $occId
				}
				else $statusStr = $occManager->getErrorStr();
			}
			elseif($action == 'Delete Occurrence'){
				if($occManager->deleteOccurrence($occId)){
					$occId = 0;
					$occManager->setOccId(0);
				}
				else $statusStr = $occManager->getErrorStr();
			}
			elseif($action == 'Transfer Record'){
				$transferCollid = $_POST['transfercollid'];
				if($transferCollid){
					if($occManager->transferOccurrence($occId,$transferCollid)){
						if(!isset($_POST['remainoncoll']) || !$_POST['remainoncoll']){
							$occManager->setCollId($transferCollid);
							$collId = $transferCollid;
							$collMap = $occManager->getCollMap();
						}
					}
					else{
						$statusStr = $occManager->getErrorStr();
					}
				}
			}
			elseif($action == 'cloneRecord'){
				$cloneArr = $occManager->cloneOccurrence($_POST);
				if($cloneArr){
					$statusStr = (isset($LANG['CLONES_CREATED'])?$LANG['CLONES_CREATED']:'Success! The following new clone record(s) have been created').' ';
					$statusStr .= '<div style="margin:5px 10px;color:black">';
					$statusStr .= '<div><a href="occureditor.php?occid='.$occId.'" target="_blank">#'.$occId.'</a> - '.(isset($LANG['CLONE_SOURCE'])?$LANG['CLONE_SOURCE']:'clone source').'</div>';
					$occId = current($cloneArr);
					$occManager->setOccId($occId);
					foreach($cloneArr as $cloneOccid){
						if($cloneOccid==$occId) $statusStr .= '<div>#'.$cloneOccid.' - '.(isset($LANG['THIS_RECORD'])?$LANG['THIS_RECORD']:'this record').'</div>';
						else $statusStr .= '<div><a href="occureditor.php?occid='.$cloneOccid.'" target="_blank">#'.$cloneOccid.'</a></div>';
					}
					$statusStr .= '</div>';
					if(isset($_POST['targetcollid']) && $_POST['targetcollid'] && $_POST['targetcollid'] != $collId){
						$collId = $_POST['targetcollid'];
						$occManager->setCollId($collId);
						$collMap = $occManager->getCollMap();
					}
					$occManager->setQueryVariables(array('eb'=>$PARAMS_ARR['un'],'de'=>date('Y-m-d')));
					$qryCnt = $occManager->getQueryRecordCount();
					$occIndex = $qryCnt - count($cloneArr);
				}
			}
			elseif($action == 'Submit Image Edits'){
				$occManager->editImage($_POST);
				if($errArr = $occManager->getErrorArr()){
					if(isset($errArr['web'])){
						if(!$errArr['web']) $statusStr .= $LANG['ERROR_UPDATING_IMAGE'].': web image<br />';
					}
					if(isset($errArr['tn'])){
						if(!$errArr['tn']) $statusStr .= $LANG['ERROR_UPDATING_IMAGE'].': thumbnail<br />';
					}
					if(isset($errArr['orig'])){
						if(!$errArr['orig']) $statusStr .= $LANG['ERROR_UPDATING_IMAGE'].': large image<br />';
					}
					if(isset($errArr['error'])) $statusStr .= $LANG['ERROR_EDITING_IMAGE'].': '.$errArr['error'];
				}
				$tabTarget = 2;
			}
			elseif($action == 'Submit New Image'){
				if($occManager->addImage($_POST)){
					$statusStr = (isset($LANG['IMAGE_ADD_SUCCESS'])?$LANG['IMAGE_ADD_SUCCESS']:'Image added successfully');
					$tabTarget = 2;
				}
				if($occManager->getErrorStr()){
					$statusStr .= $occManager->getErrorStr();
				}
			}
			elseif($action == 'Delete Image'){
				$removeImg = (array_key_exists('removeimg',$_POST)?$_POST['removeimg']:0);
				if($occManager->deleteImage($_POST["imgid"], $removeImg)){
					$statusStr = (isset($LANG['IMAGE_DEL_SUCCESS'])?$LANG['IMAGE_DEL_SUCCESS']:'Image deleted successfully');
					$tabTarget = 2;
				}
				else{
					$statusStr = $occManager->getErrorStr();
				}
			}
			elseif($action == 'Remap Image'){
				if($occManager->remapImage($_POST['imgid'], $_POST['targetoccid'])){
					$statusStr = (isset($LANG['IMAGE_REMAP_SUCCESS'])?$LANG['IMAGE_REMAP_SUCCESS']:'SUCCESS: Image remapped to record').' <a href="occureditor.php?occid='.$_POST["targetoccid"].'" target="_blank">'.$_POST["targetoccid"].'</a>';
				}
				else{
					$statusStr = (isset($LANG['IMAGE_REMAP_ERROR'])?$LANG['IMAGE_REMAP_ERROR']:'ERROR linking image to new specimen').': '.$occManager->getErrorStr();
				}
			}
			elseif($action == 'remapImageToNewRecord'){
				$newOccid = $occManager->remapImage($_POST['imgid'], 'new');
				if($newOccid){
					$statusStr = (isset($LANG['IMAGE_REMAP_SUCCESS'])?$LANG['IMAGE_REMAP_SUCCESS']:'SUCCESS: Image remapped to record').' <a href="occureditor.php?occid='.$newOccid.'" target="_blank">'.$newOccid.'</a>';
				}
				else{
					$statusStr = (isset($LANG['NEW_IMAGE_ERROR'])?$LANG['NEW_IMAGE_ERROR']:'ERROR linking image to new blank specimen').': '.$occManager->getErrorStr();
				}
			}
			elseif($action == "Disassociate Image"){
				if($occManager->remapImage($_POST["imgid"])){
					$statusStr = (isset($LANG['DISASS_SUCCESS'])?$LANG['DISASS_SUCCESS']:'SUCCESS disassociating image').' <a href="../../imagelib/imgdetails.php?imgid='.$_POST["imgid"].'" target="_blank">#'.$_POST["imgid"].'</a>';
				}
				else{
					$statusStr = (isset($LANG['DISASS_ERORR'])?$LANG['DISASS_ERORR']:'ERROR disassociating image').': '.$occManager->getErrorStr();
				}

			}
			elseif($action == "Apply Determination"){
				$makeCurrent = 0;
				if(array_key_exists('makecurrent',$_POST)) $makeCurrent = 1;
				$statusStr = $occManager->applyDetermination($_POST['detid'],$makeCurrent);
				$tabTarget = 1;
			}
			elseif($action == "Make Determination Current"){
				$statusStr = $occManager->makeDeterminationCurrent($_POST['detid']);
				$tabTarget = 1;
			}
			elseif($action == "Submit Verification Edits"){
				$statusStr = $occManager->editIdentificationRanking($_POST['confidenceranking'],$_POST['notes']);
				$tabTarget = 1;
			}
			elseif($action == 'Link to Checklist as Voucher'){
				$statusStr = $occManager->linkChecklistVoucher($_POST['clidvoucher'],$_POST['tidvoucher']);
			}
			elseif($action == 'deletevoucher'){
				$statusStr = $occManager->deleteChecklistVoucher($_REQUEST['delclid']);
			}
			elseif($action == 'editgeneticsubmit'){
				$statusStr = $occManager->editGeneticResource($_POST);
			}
			elseif($action == 'deletegeneticsubmit'){
				$statusStr = $occManager->deleteGeneticResource($_POST['genid']);
			}
			elseif($action == 'addgeneticsubmit'){
				$statusStr = $occManager->addGeneticResource($_POST);
			}
		}
	}

	if($goToMode){
		//Adding new record, override query form and prime for current user's dataentry for the day
		$occId = 0;
		$occManager->setQueryVariables(array('eb'=>$PARAMS_ARR['un'],'de'=>date('Y-m-d')));
		if(!$qryCnt){
			$qryCnt = $occManager->getQueryRecordCount();
			$occIndex = $qryCnt;
		}
	}
	if(is_numeric($occIndex)){
		//Query Form has been activated
		$occManager->setQueryVariables();
		if($action == 'Delete Occurrence'){
			//Reset query form index to one less, unless it's already 1, then just reset
			$qryCnt = $occManager->getQueryRecordCount();		//Value won't be returned unless set in cookies in previous query
			if($qryCnt > 1){
				if(($occIndex + 1) >= $qryCnt) $occIndex = $qryCnt - 2;
				$qryCnt--;
			}
			else{
				unset($_SESSION['editorquery']);
				$occIndex = false;
			}
		}
		elseif($action == 'saveOccurEdits'){
			//Get query count and then reset; don't use new count for this display
			$qryCnt = $occManager->getQueryRecordCount();
			$occManager->getQueryRecordCount(1);
		}
		else{
			$qryCnt = $occManager->getQueryRecordCount();
		}
	}
	elseif(isset($_SESSION['editorquery'])){
		//Make sure query variables are null
		unset($_SESSION['editorquery']);
	}
	$occManager->setOccIndex($occIndex);

	if($occId || (!$goToMode && $occIndex !== false)){
		$oArr = $occManager->getOccurMap();
		if($oArr){
			$occId = $occManager->getOccId();
			$occArr = $oArr[$occId];
			$occIndex = $occManager->getOccIndex();
			if(!$collMap){
				$collMap = $occManager->getCollMap();
				if(!$isEditor){
					if(isset($USER_RIGHTS["CollAdmin"]) && in_array($collMap['collid'],$USER_RIGHTS["CollAdmin"])){
						$isEditor = 1;
					}
					elseif(isset($USER_RIGHTS["CollEditor"]) && in_array($collMap['collid'],$USER_RIGHTS["CollEditor"])){
						$isEditor = 1;
					}
				}
			}
		}
	}
	elseif($goToMode == 2) $occArr = $occManager->carryOverValues($_REQUEST);
	if(!$isEditor && $crowdSourceMode && $occManager->isCrowdsourceEditor()) $isEditor = 4;
}
?>
