<?php
include_once('OccurrenceEditorManager.php');
include_once('OmDeterminations.php');

class OccurrenceEditorDeterminations extends OccurrenceEditorManager{

	private $detManager;

	public function __construct(){
 		parent::__construct();
 		$this->detManager = new OmDeterminations($this->conn);
	}

	public function __destruct(){
 		parent::__destruct();
	}

	public function getDetMap(){
		$this->detManager->setOccid($this->occid);
		$retArr = $this->detManager->getDeterminationArr();
		return $retArr;
	}

	public function addDetermination($detArr){
		$status = false;
		$this->detManager->setOccid($this->occid);
		if($this->detManager->insertDetermination($detArr)){
			$status = true;
			$this->errorArr = $this->detManager->getWarningArr();
		}
		else{
			$this->errorArr[] = $this->conn->error;
		}
		return $status;
	}

	public function editDetermination($detArr){
		$status = false;
		if(!empty($detArr['detid'])){
			$this->detManager->setDetID($detArr['detid']);
			if($this->detManager->updateDetermination($detArr)){
				$status = true;
			}
			else{
				$this->errorArr[] = $this->conn->error;
			}
		}
		return $status;
	}

	public function deleteDetermination($detID){
		$status = false;
		$this->detManager->setDetID($detID);
		$this->detManager->setOccid($this->occid);
		$detArr = $this->detManager->getDeterminationArr();
		if($detArr[$detID]['isCurrent']){
			$occid = $detArr[$detID]['occid'];

		}

		return $status;


		global $LANG;
		$status = $LANG['DET_DEL_SUCCESS'];
		$isCurrent = 0;
		$occid = 0;

		$sql = 'SELECT occid, identifiedBy, dateIdentified, family, sciname, scientificNameAuthorship, tidInterpreted, identificationQualifier, isCurrent, printQueue,
			appliedStatus, detType, identificationReferences, identificationRemarks, taxonRemarks, sortSequence
			FROM omoccurdeterminations WHERE detid = '.$detId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_assoc()){
			$isCurrent = $r['isCurrent'];
			$occid = $r['occid'];
		}
		$rs->free();

		if($isCurrent){
			$prevDetId = 0;
			$sql2 = 'SELECT detid FROM omoccurdeterminations WHERE occid = '.$occid.' AND detid <> '.$detId.' ORDER BY detid DESC LIMIT 1 ';
			$rs = $this->conn->query($sql2);
			if($r = $rs->fetch_object()){
				$prevDetId = $r->detid;
			}
			if($prevDetId) $this->applyDetermination($prevDetId, 1);
		}

		$sql = 'DELETE FROM omoccurdeterminations WHERE (detid = '.$detId.')';
		if(!$this->conn->query($sql)){
			$status = $LANG['DET_DEL_FAIL'].': '.$this->conn->error;
		}

		return $status;
	}

	public function applyDetermination($detId, $makeCurrent){
		global $LANG;
		$statusStr = $LANG['DET_APPLIED'];
		//Get ConfidenceRanking value
		$iqStr = '';
		$sqlcr = 'SELECT identificationremarks FROM omoccurdeterminations WHERE detid = '.$detId;
		$rscr = $this->conn->query($sqlcr);
		if($rcr = $rscr->fetch_object()){
			$iqStr = $rcr->identificationremarks;
			if(preg_match('/ConfidenceRanking: (\d{1,2})/',$iqStr,$m)){
				if($makeCurrent) $this->editIdentificationRanking($m[1],'');
				$iqStr = trim(str_replace('ConfidenceRanking: '.$m[1],'',$iqStr),' ;');
			}
		}
		$rscr->free();

		//Update applied status of det
		$sql = 'UPDATE omoccurdeterminations
			SET appliedstatus = 1, iscurrent = '.$makeCurrent.', identificationremarks = '.($iqStr?'"'.$this->cleanInStr($iqStr).'"':'NULL').
			' WHERE detid = '.$detId;
		if(!$this->conn->query($sql)){
			$statusStr = $LANG['ERROR_ATTEMPT_DET'].': '.$this->conn->error;
		}
		if($makeCurrent){
			$this->makeDeterminationCurrent($detId);
		}
		return $statusStr;
	}

	public function makeDeterminationCurrent($detId){
		global $LANG;
		$status = $LANG['DET_NOW_CURRENT'];
		//Make sure determination data within omoccurrences is in omoccurdeterminations. If already there, INSERT will fail and nothing lost
		$guid = UuidFactory::getUuidV4();
		$sqlInsert = 'INSERT IGNORE INTO omoccurdeterminations(occid, identifiedBy, dateIdentified, sciname, scientificNameAuthorship, '.
			'identificationQualifier, identificationReferences, identificationRemarks, recordID, sortsequence) '.
			'SELECT occid, IFNULL(identifiedby,"unknown") AS idby, IFNULL(dateidentified,"s.d.") AS iddate, sciname, scientificnameauthorship, '.
			'identificationqualifier, identificationreferences, identificationremarks, "'.$guid.'", 10 AS sortseq '.
			'FROM omoccurrences WHERE (occid = '.$this->occid.') AND (identifiedBy IS NOT NULL OR dateIdentified IS NOT NULL OR sciname IS NOT NULL)';
		$this->conn->query($sqlInsert);

		//Set all dets for this specimen to not current
		$sqlSetCur1 = 'UPDATE omoccurdeterminations SET iscurrent = 0 WHERE occid = '.$this->occid;
		if(!$this->conn->query($sqlSetCur1)){
			$status = $LANG['ERROR_DETS_NOT_CURRENT'].': '.$this->conn->error;
			//$status .= '; '.$sqlSetCur1;
		}
		//Set targeted det to current
		$sqlSetCur2 = 'UPDATE omoccurdeterminations SET iscurrent = 1 WHERE detid = '.$detId;
		if(!$this->conn->query($sqlSetCur2)){
			$status = $LANG['ERROR_SETTING_CURRENT'].': '.$this->conn->error;
			//$status .= '; '.$sqlSetCur2;
		}

		return $status;
	}

	private function getTaxonVariables($detId){
		$retArr = array();
		$sqlTid = 'SELECT t.tid, t.securitystatus, ts.family
			FROM omoccurdeterminations d INNER JOIN taxa t ON d.sciname = t.sciname
			INNER JOIN taxstatus ts ON t.tid = ts.tid
			WHERE (d.detid = '.$detId.') AND (taxauthid = 1)';
		$rs = $this->conn->query($sqlTid);
		if($r = $rs->fetch_object()){
			$retArr['tid'] = $r->tid;
			$retArr['family'] = $r->family;
			$retArr['security'] = ($r->securitystatus == 1 ? 1 : 0);
		}
		$rs->free();
		if($retArr && !$retArr['security'] && $retArr['tid']){
			$sql2 = 'SELECT c.clid
				FROM fmchecklists c INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid
				INNER JOIN taxstatus ts1 ON cl.tid = ts1.tid
				INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted
				INNER JOIN omoccurrences o ON c.locality = o.stateprovince
				WHERE c.type = "rarespp" AND ts1.taxauthid = 1 AND ts2.taxauthid = 1
				AND (ts2.tid = '.$retArr['tid'].') AND (o.occid = '.$this->occid.')';
			$rs2 = $this->conn->query($sql2);
			if($rs2->num_rows){
				$retArr['security'] = 1;
			}
			$rs2->free();
		}
		return $retArr;
	}

	public function addNomAdjustment($detArr){
		$sql = 'SELECT identificationQualifier FROM omoccurrences WHERE occid = '.$this->occid;
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$detArr['identificationqualifier'] = $r->identificationQualifier;
		}
		$rs->free();
		$detArr['identifiedby'] = 'Nomenclatural Adjustment';
		$detArr['dateidentified'] = date('F').' '.date('j').', '.date('Y');
		$this->addDetermination($detArr);
	}

	public function getNewDetItem($catNum, $sciName, $allCatNum = 0){
		$retArr = array();
		if($catNum || $sciName){
			$sql = 'SELECT o.occid, o.catalogNumber, o.otherCatalogNumbers, o.sciname, CONCAT_WS(" ", o.recordedby, IFNULL(o.recordnumber, o.eventdate)) AS collector, '.
				'CONCAT_WS(", ", o.country, o.stateprovince, o.county, o.locality) AS locality ';
			$catNumArr = explode(',',$catNum);
			if($catNum){
				foreach($catNumArr as $k => $u){
					$u = trim($u);
					if($u) $catNumArr[$k] = $this->cleanInStr($u);
					else unset($catNumArr[$k]);
				}
				if($allCatNum){
					$sql .= ', i.identifierValue FROM omoccurrences o LEFT JOIN omoccuridentifiers i ON o.occid = i.occid ';
				}
				else{
					$sql .= 'FROM omoccurrences o ';
				}
				$catNumStr = implode('","',$catNumArr);
				$sql .= 'WHERE o.collid = '.$this->collId.' AND (o.catalogNumber IN("'.$catNumStr.'") ';
				if($allCatNum){
					$sql .= 'OR o.otherCatalogNumbers IN("'.$catNumStr.'") OR i.identifierValue IN("'.$catNumStr.'") ';
				}
				$sql .= ') ';
			}
			elseif($sciName){
				$sql .= 'FROM omoccurrences o WHERE o.collid = '.$this->collId.' AND o.sciname = "'.$this->cleanInStr($sciName).'" ';
			}
			$sql .= 'LIMIT 400 ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if(!array_key_exists($r->occid, $retArr)){
					$retArr[$r->occid]['sn'] = $r->sciname;
					$retArr[$r->occid]['coll'] = $r->collector;
					$loc = $r->locality;
					if(strlen($loc) > 500) $loc = substr($loc,400);
					$retArr[$r->occid]['loc'] = $loc;
					$cn = $r->catalogNumber;
					if($r->otherCatalogNumbers){
						if(!$cn || in_array($r->otherCatalogNumbers, $catNumArr)) $cn = $r->otherCatalogNumbers;
					}
					$retArr[$r->occid]['cn'] = $cn;
				}
				if(!empty($r->identifierValue)){
					if(!$retArr[$r->occid]['cn'] || in_array($r->identifierValue, $catNumArr)) $retArr[$r->occid]['cn'] = $r->identifierValue;
				}
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getCollName(){
		$retStr = '';
		if($this->collMap) $retStr = $this->collMap['collectionname'].' ('.$this->collMap['institutioncode'].($this->collMap['collectioncode']?':'.$this->collMap['collectioncode']:'').')';
		return $retStr;
	}
}
?>
