<?php
include_once('Manager.php');
include_once('OccurrenceUtilities.php');
include_once('UuidFactory.php');

class OmDeterminations extends Manager{

	private $detID = null;
	private $occid = null;
	private $schemaMap = array();
	private $parameterArr = array();
	private $typeStr = '';

	public function __construct($conn){
		parent::__construct(null, 'write', $conn);
		$this->schemaMap = array('identifiedBy' => 's', 'dateIdentified' => 's', 'higherClassification' => 's', 'family' => 's', 'sciname' => 's', 'verbatimIdentification' => 's',
			'scientificNameAuthorship' => 's', 'identificationUncertain' => 'i', 'identificationQualifier' => 's', 'isCurrent' => 'i', 'printQueue' => 'i', 'appliedStatus' => 'i',
			'securityStatus' => 'i', 'securityStatusReason' => 's', 'detType' => 's', 'identificationReferences' => 's', 'identificationRemarks' => 's', 'taxonRemarks' => 's',
			'identificationVerificationStatus' => 's', 'taxonConceptID' => 's', 'sourceIdentifier' => 's', 'sortSequence' => 'i');
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function getDeterminationArr($conditions = null){
		$retArr = array();
		$uidArr = array();
		$sql = 'SELECT detID, occid, '.implode(', ', array_keys($this->schemaMap)).', initialTimestamp FROM omoccurdeterminations WHERE ';
		if($this->detID && !$conditions) $sql .= '(detID = '.$this->detID.') ';
		elseif($this->occid) $sql .= '(occid = '.$this->occid.') ';
		if(is_array($conditions)){
			foreach($conditions as $field => $cond){
				$sql .= 'AND '.$field.' = "'.$this->cleanInStr($cond).'" ';
			}
		}
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_assoc()){
				$retArr[$r['detID']] = $r;
				$uidArr[$r['createdUid']] = $r['createdUid'];
				$uidArr[$r['modifiedUid']] = $r['modifiedUid'];
			}
			$rs->free();
		}
		if($uidArr){
			//Add user names for modified and created by
			$sql = 'SELECT uid, firstname, lastname, username FROM users WHERE uid IN('.implode(',', $uidArr).')';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$uidArr[$r->uid] = $r->lastname . ($r->firstname ? ', ' . $r->firstname : '');
				}
				$rs->free();
			}
			foreach($retArr as $detID => $detArr){
				if($detArr['createdUid'] && array_key_exists($detArr['createdUid'], $uidArr)) $retArr[$detID]['createdBy'] = $uidArr[$detArr['createdUid']];
				if($detArr['modifiedUid'] && array_key_exists($detArr['modifiedUid'], $uidArr)) $retArr[$detID]['modifiedBy'] = $uidArr[$detArr['modifiedUid']];
			}
		}
		return $retArr;
	}

	public function insertDetermination($inputArr){
		$status = false;
		if($this->occid){
			$this->typeStr = 'is';
			$this->setParameterArr($inputArr);
			if(!isset($this->parameterArr['createdUid'])) $this->parameterArr['createdUid'] = $GLOBALS['SYMB_UID'];
			if(!empty($this->parameterArr['isCurrent']) && (!isset($this->parameterArr['appliedStatus']) || !$this->parameterArr['appliedStatus'])){
				//Set all other sister determination to not current; later we might rework this to allow multiple identifications to be current
				$setArr = ['isCurrent' => 0];
				$condArr = ['appliedStatus' => ['value' => 1, 'type' => 'i']];
				if(!$this->updateDetermination($setArr, $condArr)){
					$this->warningArr[] = 'ERROR_DETS_NOT_CURRENT|' . $this->conn->error;
				}
			}
			if(!isset($this->parameterArr['sortSequence'])){
				//Set a default sort sequence
				$sortSeq = 1;
				if(preg_match('/([1,2]{1}\d{3})/', $this->parameterArr['dateidentified'], $matches)){
					$sortSeq = date('Y') + 1 - $matches[1];
				}
				$this->parameterArr['sortSequence'] = $sortSeq;
			}
			//Insert determination
			$sql = 'INSERT INTO omoccurdeterminations(occid, recordID';
			$sqlValues = '?, ?, ';
			$paramArr = array($this->occid);
			$paramArr[] = UuidFactory::getUuidV4();
			foreach($this->parameterArr as $fieldName => $value){
				$sql .= ', '.$fieldName;
				$sqlValues .= '?, ';
				$paramArr[] = $value;
			}
			$sql .= ') VALUES('.trim($sqlValues, ', ').') ';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param($this->typeStr, ...$paramArr);
				if($stmt->execute()){
					if($stmt->affected_rows || !$stmt->error){
						$this->detID = $stmt->insert_id;
						//TODO: If inserted determination isCurrent, we need to reset all other sister determinations to 0
						$status = true;
					}
					else $this->errorMessage = $stmt->error;
				}
				else $this->errorMessage = $stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = $this->conn->error;
		}
		return $status;
	}

	public function updateDetermination($inputArr, $conditions = null){
		$status = false;
		if(($this->detID || $this->occid) && $this->conn){
			$this->setParameterArr($inputArr);
			$paramArr = array();
			$sqlFrag = '';
			foreach($this->parameterArr as $fieldName => $value){
				$sqlFrag .= $fieldName . ' = ?, ';
				$paramArr[] = $value;
			}
			$sql = 'UPDATE omoccurdeterminations SET ' . trim($sqlFrag, ', ') . ' ';
			if($this->detID){
				$sql .= 'WHERE (detID = ?)';
				$paramArr[] = $this->detID;
			}
			else{
				$sql .= 'WHERE (occid = ?)';
				$paramArr[] = $this->occid;
			}
			$this->typeStr .= 'i';
			if($conditions){
				foreach($conditions as $condField => $condValueArr){
					$sql .= 'WHERE (' . $condField . ' = ?)';
					$this->typeStr .= $condValueArr['type'];
					$paramArr[] = $condValueArr['value'];
				}
			}
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param($this->typeStr, ...$paramArr);
				$stmt->execute();
				if($stmt->affected_rows || !$stmt->error){
					//TODO: If updated determination isCurrent value is changed, we need to adjust other determinations appropriately, and update tid of linked images
					$status = true;
				}
				else $this->errorMessage = $stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = $this->conn->error;
		}
		return $status;
	}

	private function setParameterArr($inputArr){
		foreach($this->schemaMap as $field => $type){
			$postField = '';
			if(isset($inputArr[$field])) $postField = $field;
			elseif(isset($inputArr[strtolower($field)])) $postField = strtolower($field);
			if($postField){
				$value = trim($inputArr[$postField]);
				if($value){
					$postField = strtolower($postField);
					if($postField == 'establisheddate') $value = OccurrenceUtilities::formatDate($value);
					if($postField == 'modifieduid') $value = OccurrenceUtilities::verifyUser($value, $this->conn);
					if($postField == 'createduid') $value = OccurrenceUtilities::verifyUser($value, $this->conn);
					if($postField == 'identificationuncertain' || $postField == 'iscurrent' || $postField == 'printqueue' || $postField == 'appliedstatus' || $postField == 'securitystatus'){
						if(!is_numeric($value)){
							$value = strtolower($value);
							if($value == 'yes' || $value == 'true') $value = 1;
							else $value = 0;
						}
					}
					if($postField == 'sortsequence'){
						if(!is_numeric($value)) $value = 10;
					}
				}
				else $value = null;
				$this->parameterArr[$field] = $value;
				$this->typeStr .= $type;
			}
		}
		if(isset($inputArr['occid']) && $inputArr['occid'] && !$this->occid) $this->occid = $inputArr['occid'];
	}

	public function deleteDetermination(){
		$status = false;
		if($this->detID){
			$targetID = $this->detID;
			//If target determination isCurrent, need to reset a new isCurrent determination
			$detArr = $this->getDeterminationArr('all');
			if($detArr[$this->detID]['isCurrent']){
				//Set a new default isCurrent
				$newIsCurrentID = 0;
				$rank = 0;
				foreach($detArr as $id => $recordArr){
					if($recordArr['appliedStatus'] == 0 || $recordArr['securityStatus'] == 1){
						continue;
					}
					if($recordArr['isCurrent']){
						//Abort, since another isCurrent already exists
						$newIsCurrentID = 0;
						break;
					}
					if($recordArr['sortSequence'] > $rank){
						$rank = $recordArr['sortSequence'];
						$newIsCurrentID = $id;
					}
				}
				if($newIsCurrentID){
					$this->detID = $newIsCurrentID;
					$this->updateDetermination(array('isCurrent' => 1));
					//TODO: Need to adjust TIDs of linked images
				}
			}
			//Delete target determination
			$sql = 'DELETE FROM omoccurdeterminations WHERE detID = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $targetID);
				$stmt->execute();
				if($stmt->affected_rows || !$stmt->error){
					//TODO: If updated determination isCurrent value is changed, we need to adjust other determinations appropriately, and update tid of linked images
					$status = true;
				}
				else $this->errorMessage = $stmt->error;
				$stmt->close();
			}
		}
		return $status;
	}

	//Setters and getters
	public function setDetID($id){
		$this->detID = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
	}

	public function getDetID(){
		return $this->detID;
	}

	public function setOccid($id){
		$this->occid = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
	}

	public function getSchemaMap(){
		return $this->schemaMap;
	}
}
?>