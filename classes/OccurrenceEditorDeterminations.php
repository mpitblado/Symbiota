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
		if($this->detManager->deleteDetermination()){
			$status = true;
		}
		else{
			if($this->detManager->getErrorMessage()) $this->errorArr[] = $this->detManager->getErrorMessage();
		}
		if($this->detManager->getWarningArr()) $this->errorArr = array_merge($this->errorArr, $this->detManager->getWarningArr());
		return $status;
	}

	public function applyDetermination($detID, $isCurrent){
		$status = false;
		$this->detManager->setDetID($detID);
		$inputArr = array('appliedStatus' => 1, 'isCurrent' => $isCurrent);
		$this->detManager->updateDetermination($inputArr);
		if($isCurrent) $this->makeDeterminationCurrent($detID);
		return $status;
	}

	public function makeDeterminationCurrent($detID){
		$status = false;
		//Set all dets for this specimen to not current
		$this->detManager->setOccid($this->occid);
		$this->detManager->updateDetermination(array('isCurrent' => 0));
		//Set targeted det to current
		$this->detManager->setDetID($detID);
		$this->detManager->updateDetermination(array('isCurrent' => 1));
		return $status;
	}

	//Used by batchdeterminations.php (or rpc/getnewdetitem.php)
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
