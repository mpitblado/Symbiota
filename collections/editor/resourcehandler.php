<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorResource.php');
header("Content-Type: text/html; charset=".$CHARSET);

$occid = array_key_exists('occid', $_POST) ? filter_var($_POST['occid'], FILTER_SANITIZE_NUMBER_INT) : 0 ;
$collid = array_key_exists('collid', $_POST) ? filter_var($_POST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$occIndex = array_key_exists('occindex', $_POST) ? filter_var($_POST['occindex'], FILTER_SANITIZE_NUMBER_INT) : 0;
$action = (isset($_POST['submitaction']) ? $_POST['submitaction'] : '');

//Sanitation
if(!is_numeric($occid)) $occid = 0;
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($occIndex)) $occIndex = 0;

if($occid && $SYMB_UID){
	$occManager = new OccurrenceEditorResource();
	$occManager->setOccId($occid);
	$occManager->setCollId($collid);
	$occManager->getOccurMap();
	$isEditor = false;
	if($IS_ADMIN) $isEditor = true;
	elseif($collid && array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin'])) $isEditor = true;
	elseif($collid && array_key_exists('CollEditor',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollEditor'])) $isEditor = true;
	elseif($occManager->isPersonalManagement()) $isEditor = true;
	if($isEditor){
		if($action == 'createAssociation'){
			$occManager->addAssociation($_POST);
		}
		elseif(array_key_exists('delassocid', $_POST)){
			$occManager->deleteAssociation($_POST['delassocid']);
		}
	}
	header('Location: occurrenceeditor.php?tabtarget=3&occid='.$occid.'&occindex='.$occIndex.'&collid='.$collid);
}

?>