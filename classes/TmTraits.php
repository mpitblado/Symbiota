<?php
include_once('Manager.php');

class TmTraits extends Manager{

	private $traitID = null;
	private $stateID = null;
	private $occid = null;
	private $schemaMap = array();
	private $parameterArr = array();
	private $typeStr = '';

	public function __construct($conn){
		parent::__construct(null, 'write', $conn);
	}

	public function __destruct(){
		parent::__destruct();
	}

	//Trait table
	public function getTraitArr($filterArr = null){
		$retArr = array();
		$uidArr = array();
		$sql = 'SELECT traitID, '.implode(', ', array_keys($this->schemaMap)).', initialTimestamp FROM tmtraits WHERE ';
		if($this->traitID) $sql .= '(traitID = '.$this->traitID.') ';
		foreach($filterArr as $field => $cond){
			$sql .= 'AND '.$field.' = "'.$this->cleanInStr($cond).'" ';
		}
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_assoc()){
				$retArr[$r['traitID']] = $r;
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
			foreach($retArr as $traitID => $traitArr){
				if($traitArr['createdUid'] && array_key_exists($traitArr['createdUid'], $uidArr)) $retArr[$traitID]['createdBy'] = $uidArr[$traitArr['createdUid']];
				if($traitArr['modifiedUid'] && array_key_exists($traitArr['modifiedUid'], $uidArr)) $retArr[$traitID]['modifiedBy'] = $uidArr[$traitArr['modifiedUid']];
			}
		}
		return $retArr;
	}

	public function insertTrait($inputArr){
		$status = false;
		if(!isset($inputArr['createdUid'])) $inputArr['createdUid'] = $GLOBALS['SYMB_UID'];
		$sqlFields = '';
		$sqlValues = '';
		$this->setParameterArr($inputArr);
		foreach($this->parameterArr as $fieldName => $value){
			$sqlFields .= ', '.$fieldName;
			$sqlValues .= '?, ';
			$paramArr[] = $value;
		}
		$sql = 'INSERT INTO tmtraits('.trim( $sqlFields, ',').') VALUES('.trim($sqlValues, ', ').') ';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param($this->typeStr, ...$paramArr);
			if($stmt->execute()){
				if($stmt->affected_rows || !$stmt->error){
					$this->traitID = $stmt->insert_id;
					$status = true;
				}
				else $this->errorMessage = 'ERROR inserting tmtraits record (2): '.$stmt->error;
			}
			else $this->errorMessage = 'ERROR inserting tmtraits record (1): '.$stmt->error;
			$stmt->close();
		}
		else $this->errorMessage = 'ERROR preparing statement for tmtraits insert: '.$this->conn->error;
		return $status;
	}

	public function updateTrait($inputArr){
		$status = false;
		if($this->traitID && $this->conn){
			$this->setParameterArr($inputArr);
			$paramArr = array();
			$sqlFrag = '';
			foreach($this->parameterArr as $fieldName => $value){
				$sqlFrag .= $fieldName . ' = ?, ';
				$paramArr[] = $value;
			}
			$paramArr[] = $this->traitID;
			$this->typeStr .= 'i';
			$sql = 'UPDATE tmtraits SET '.trim($sqlFrag, ', ').' WHERE (traitID = ?)';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param($this->typeStr, ...$paramArr);
				$stmt->execute();
				if($stmt->affected_rows || !$stmt->error) $status = true;
				else $this->errorMessage = 'ERROR updating tmtraits record: '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for updating tmtraits: '.$this->conn->error;
		}
		return $status;
	}

	public function deleteTrait(){
		if($this->traitID){
			$sql = 'DELETE FROM tmtraits WHERE traitID = '.$this->traitID;
			if($this->conn->query($sql)){
				return true;
			}
			else{
				$this->errorMessage = 'ERROR deleting tmtraits record: '.$this->conn->error;
				return false;
			}
		}
	}

	private function setTraitSchema(){
		$this->schemaMap = array('traitName' => 's', 'traitType' => 's', 'units' => 's', 'description' => 's', 'refUrl' => 's', 'notes' => 's', 'projectGroup' => 's',
				'isPublic' => 'i', 'includeInSearch' => 'i', 'dynamicProperties' => 's');
	}

	//States table
	public function getStateArr($filterArr = null){
		$retArr = array();
		$uidArr = array();
		$sql = 'SELECT stateID, '.implode(', ', array_keys($this->schemaMap)).', initialTimestamp FROM tmstates WHERE ';
		if($this->stateID) $sql .= '(stateID = '.$this->stateID.') ';
		foreach($filterArr as $field => $cond){
			$sql .= 'AND '.$field.' = "'.$this->cleanInStr($cond).'" ';
		}
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_assoc()){
				$retArr[$r['stateID']] = $r;
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
			foreach($retArr as $stateID => $stateArr){
				if($stateArr['createdUid'] && array_key_exists($stateArr['createdUid'], $uidArr)) $retArr[$stateID]['createdBy'] = $uidArr[$stateArr['createdUid']];
				if($stateArr['modifiedUid'] && array_key_exists($stateArr['modifiedUid'], $uidArr)) $retArr[$stateID]['modifiedBy'] = $uidArr[$stateArr['modifiedUid']];
			}
		}
		return $retArr;
	}

	public function insertState($inputArr){
		$status = false;
		if($this->traitID){
			if(!isset($inputArr['createdUid'])) $inputArr['createdUid'] = $GLOBALS['SYMB_UID'];
			$sqlFields = '';
			$sqlValues = '';
			$this->setParameterArr($inputArr);
			foreach($this->parameterArr as $fieldName => $value){
				$sqlFields .= ', '.$fieldName;
				$sqlValues .= '?, ';
				$paramArr[] = $value;
			}
			$sql = 'INSERT INTO tmstates('.trim( $sqlFields, ',').') VALUES('.trim($sqlValues, ', ').') ';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param($this->typeStr, ...$paramArr);
				if($stmt->execute()){
					if($stmt->affected_rows || !$stmt->error){
						$this->stateID = $stmt->insert_id;
						$status = true;
					}
					else $this->errorMessage = 'ERROR inserting tmstates record (2): '.$stmt->error;
				}
				else $this->errorMessage = 'ERROR inserting tmstates record (1): '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for tmstates insert: '.$this->conn->error;
		}
		return $status;
	}

	public function updateState($inputArr){
		$status = false;
		if($this->stateID && $this->conn){
			$this->setParameterArr($inputArr);
			$paramArr = array();
			$sqlFrag = '';
			foreach($this->parameterArr as $fieldName => $value){
				$sqlFrag .= $fieldName . ' = ?, ';
				$paramArr[] = $value;
			}
			$paramArr[] = $this->stateID;
			$this->typeStr .= 'i';
			$sql = 'UPDATE tmstates SET '.trim($sqlFrag, ', ').' WHERE (stateID = ?)';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param($this->typeStr, ...$paramArr);
				$stmt->execute();
				if($stmt->affected_rows || !$stmt->error) $status = true;
				else $this->errorMessage = 'ERROR updating tmstates record: '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for updating tmstates: '.$this->conn->error;
		}
		return $status;
	}

	public function deleteState(){
		if($this->stateID){
			$sql = 'DELETE FROM tmstates WHERE stateID = '.$this->stateID;
			if($this->conn->query($sql)){
				return true;
			}
			else{
				$this->errorMessage = 'ERROR deleting tmstates record: '.$this->conn->error;
				return false;
			}
		}
	}

	private function setStateSchema(){
		$this->schemaMap = array('stateCode' => 's', 'stateName' => 's', 'description' => 's', 'refUrl' => 's', 'notes' => 's', 'sortSeq' => 'i');
	}

	//States table
	public function getAttributeArr($filterArr = null){
		$retArr = array();
		$uidArr = array();
		$sql = 'SELECT stateID, '.implode(', ', array_keys($this->schemaMap)).', initialTimestamp FROM tmstates WHERE ';
		if($this->stateID) $sql .= '(stateID = '.$this->stateID.') ';
		foreach($filterArr as $field => $cond){
			$sql .= 'AND '.$field.' = "'.$this->cleanInStr($cond).'" ';
		}
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_assoc()){
				$retArr[$r['stateID']] = $r;
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
			foreach($retArr as $stateID => $stateArr){
				if($stateArr['createdUid'] && array_key_exists($stateArr['createdUid'], $uidArr)) $retArr[$stateID]['createdBy'] = $uidArr[$stateArr['createdUid']];
				if($stateArr['modifiedUid'] && array_key_exists($stateArr['modifiedUid'], $uidArr)) $retArr[$stateID]['modifiedBy'] = $uidArr[$stateArr['modifiedUid']];
			}
		}
		return $retArr;
	}

	public function insertAttribute($inputArr){
		$status = false;
		if($this->stateID && $this->occid){
			if(!isset($inputArr['createdUid'])) $inputArr['createdUid'] = $GLOBALS['SYMB_UID'];
			$sql = 'INSERT INTO tmstates(stateID, occid';
			$sqlValues = '?, ?';
			$this->typeStr = 'ii';
			$this->setParameterArr($inputArr);
			foreach($this->parameterArr as $fieldName => $value){
				$sql .= ', '.$fieldName;
				$sqlValues .= ', ?';
				$paramArr[] = $value;
			}
			$sql .= ') VALUES('.$sqlValues.') ';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param($this->typeStr, ...$paramArr);
				if($stmt->execute()){
					if($stmt->affected_rows || !$stmt->error){
						$status = true;
					}
					else $this->errorMessage = 'ERROR inserting tmattributes record (2): '.$stmt->error;
				}
				else $this->errorMessage = 'ERROR inserting tmattributes record (1): '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for tmattributes insert: '.$this->conn->error;
		}
		return $status;
	}

	public function updateAttribute($inputArr){
		$status = false;
		if($this->stateID && $this->occid && $this->conn){
			$this->setParameterArr($inputArr);
			$paramArr = array();
			$sqlFrag = '';
			foreach($this->parameterArr as $fieldName => $value){
				$sqlFrag .= $fieldName . ' = ?, ';
				$paramArr[] = $value;
			}
			$paramArr[] = $this->stateID;
			$paramArr[] = $this->occid;
			$this->typeStr .= 'ii';
			$sql = 'UPDATE tmattributes SET '.trim($sqlFrag, ', ').' WHERE (stateID = ?) AND (occid = ?)';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param($this->typeStr, ...$paramArr);
				$stmt->execute();
				if($stmt->affected_rows || !$stmt->error) $status = true;
				else $this->errorMessage = 'ERROR updating tmattributes record: '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for updating tmattributes: '.$this->conn->error;
		}
		return $status;
	}

	public function deleteAttribute(){
		if($this->stateID && $this->occid){
			$sql = 'DELETE FROM tmattributes WHERE stateID = '.$this->stateID.' AND occid = '.$this->occid;
			if($this->conn->query($sql)){
				return true;
			}
			else{
				$this->errorMessage = 'ERROR deleting tmattributes record: '.$this->conn->error;
				return false;
			}
		}
	}

	private function setAttributeSchema(){
		$this->schemaMap = array('modifier' => 's', 'xValue' => 's', 'imgid' => 'i', 'imageCoordinates' => 's', 'source' => 's', 'notes' => 's', 'statusCode' => 'i');
	}

	//Data pre functions
	private function setParameterArr($inputArr){
		foreach($this->schemaMap as $field => $type){
			$postField = '';
			if(isset($inputArr[$field])) $postField = $field;
			elseif(isset($inputArr[strtolower($field)])) $postField = strtolower($field);
			if($postField){
				$value = trim($inputArr[$postField]);
				if($value){
					if(strtolower($postField) == 'establisheddate') $value = OccurrenceUtilities::formatDate($value);
					if(strtolower($postField) == 'modifieduid') $value = OccurrenceUtilities::verifyUser($value, $this->conn);
					if(strtolower($postField) == 'createduid') $value = OccurrenceUtilities::verifyUser($value, $this->conn);
				}
				else $value = null;
				$this->parameterArr[$field] = $value;
				$this->typeStr .= $type;
			}
		}
		if(isset($inputArr['occid']) && $inputArr['occid'] && !$this->occid) $this->occid = $inputArr['occid'];
	}

	//Setters and getters
	public function setTraitID($id){
		if(is_numeric($id)) $this->traitID = $id;
	}

	public function getTraitID(){
		return $this->traitID;
	}

	public function setStateID($id){
		if(is_numeric($id)) $this->stateID = $id;
	}

	public function setOccid($id){
		if(is_numeric($id)) $this->occid = $id;
	}

	public function getSchemaMap($type){
		if($type == 'trait') $this->setTraitSchema();
		elseif($type == 'state') $this->setStateSchema();
		return $this->schemaMap;
	}
}
?>