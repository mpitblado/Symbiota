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
		$this->setTraitSchema();
		$sql = 'SELECT traitID, '.implode(', ', array_keys($this->schemaMap)).', initialTimestamp FROM tmtraits ';
		$sqlWhere = '';
		if($this->traitID){
			$sqlWhere .= 'AND (traitID = ?) ';
			$this->parameterArr[] = $this->traitID;
			$this->typeStr = 'i';
		}
		if($filterArr){
			$sqlWhere .= $this->appendFilterElements($filterArr);
		}
		if($sqlWhere) $sql .= 'WHERE ' . substr($sqlWhere, 4);
		if($stmt = $this->conn->prepare($sql)){
			if($this->parameterArr) $stmt->bind_param($this->typeStr, ...$this->parameterArr);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				while($r = $rs->fetch_assoc()){
					$retArr[$r['traitID']] = $r;
					if($r['createdUid']) $uidArr[$r['createdUid']] = '';
					if($r['modifiedUid']) $uidArr[$r['modifiedUid']] = '';
				}
				$rs->free();
			}
			$stmt->close();
		}
		$this->translateUserIDs($uidArr, $retArr);
		return $retArr;
	}

	public function insertTrait($inputArr){
		$status = false;
		if($this->occid && $this->traitID){
			if(!isset($inputArr['createdUid'])) $inputArr['createdUid'] = $GLOBALS['SYMB_UID'];
			$sql = 'INSERT INTO tmtraits(occid, traitID';
			$sqlValues = '?,?';
			$paramArr = array($this->occid, $this->traitID);
			$this->typeStr = 'ii';
			$this->setTraitSchema();
			$this->setParameterArr($inputArr);
			foreach($this->parameterArr as $fieldName => $value){
				$sql .= ', ' . $fieldName;
				$sqlValues .= ',?';
				$paramArr[] = $value;
			}
			$sql .= ') VALUES(' . $sqlValues . ') ';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param($this->typeStr, ...$paramArr);
				if($stmt->execute()){
					if(!$stmt->error){
						$this->traitID = $stmt->insert_id;
						$status = true;
					}
					else $this->errorMessage = 'ERROR inserting tmtraits record (2): ' . $stmt->error;
				}
				else $this->errorMessage = 'ERROR inserting tmtraits record (1): ' . $stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for tmtraits insert: ' . $this->conn->error;
		}
		return $status;
	}

	public function updateTrait($inputArr){
		$status = false;
		if($this->traitID && $this->conn){
			if(!isset($inputArr['modifiedUid'])) $inputArr['modifiedUid'] = $GLOBALS['SYMB_UID'];
			$this->setTraitSchema();
			$this->setParameterArr($inputArr);
			$paramArr = array();
			$sqlFrag = '';
			foreach($this->parameterArr as $fieldName => $value){
				$sqlFrag .= $fieldName . ' = ?, ';
				$paramArr[] = $value;
			}
			$paramArr[] = $this->traitID;
			$this->typeStr .= 'i';
			$sql = 'UPDATE tmtraits SET ' . trim($sqlFrag, ', ') . ', datelastmodified = NOW() WHERE (traitID = ?)';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param($this->typeStr, ...$paramArr);
				$stmt->execute();
				if(!$stmt->error) $status = true;
				else $this->errorMessage = 'ERROR updating tmtraits record: ' . $stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for updating tmtraits: ' . $this->conn->error;
		}
		return $status;
	}

	public function deleteTrait(){
		$status = false;
		if($this->traitID){
			$sql = 'DELETE FROM tmtraits WHERE traitID = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->traitID);
				$stmt->execute();
				if(!$stmt->error) $status = true;
				else $this->errorMessage = 'ERROR deleting tmtraits record: ' . $stmt->error;
				$stmt->close();
			}
		}
		return $status;
	}

	private function setTraitSchema(){
		$this->schemaMap = array('traitName' => 's', 'traitType' => 's', 'units' => 's', 'description' => 's', 'refUrl' => 's', 'notes' => 's', 'projectGroup' => 's',
			'isPublic' => 'i', 'includeInSearch' => 'i', 'dynamicProperties' => 's', 'modifiedUid' => 'i', 'createdUid' => 'i');
	}

	//States table
	public function getStateArr($filterArr = null){
		$retArr = array();
		$uidArr = array();
		$this->setStateSchema();
		$sql = 'SELECT stateID, '.implode(', ', array_keys($this->schemaMap)).', initialTimestamp FROM tmstates ';
		$sqlWhere = '';
		if($this->stateID){
			$sqlWhere .= 'AND (stateID = ?) ';
			$this->parameterArr[] = $this->stateID;
			$this->typeStr = 'i';
		}
		if($filterArr){
			$sqlWhere .= $this->appendFilterElements($filterArr);
		}
		if($sqlWhere) $sql .= 'WHERE ' . substr($sqlWhere, 4);
		if($stmt = $this->conn->prepare($sql)){
			if($this->parameterArr) $stmt->bind_param($this->typeStr, ...$this->parameterArr);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				while($r = $rs->fetch_assoc()){
					$retArr[$r['stateID']] = $r;
					if($r['createdUid']) $uidArr[$r['createdUid']] = '';
					if($r['modifiedUid']) $uidArr[$r['modifiedUid']] = '';
				}
				$rs->free();
			}
			$stmt->close();
		}
		$this->translateUserIDs($uidArr, $retArr);
		return $retArr;
	}

	public function insertState($inputArr){
		$status = false;
		if($this->traitID){
			if(!isset($inputArr['createdUid'])) $inputArr['createdUid'] = $GLOBALS['SYMB_UID'];
			$sqlFields = '';
			$sqlValues = '';
			$this->setStateSchema();
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
			if(!isset($inputArr['modifiedUid'])) $inputArr['modifiedUid'] = $GLOBALS['SYMB_UID'];
			$this->setStateSchema();
			$this->setParameterArr($inputArr);
			$paramArr = array();
			$sqlFrag = '';
			foreach($this->parameterArr as $fieldName => $value){
				$sqlFrag .= $fieldName . ' = ?, ';
				$paramArr[] = $value;
			}
			$paramArr[] = $this->stateID;
			$this->typeStr .= 'i';
			$sql = 'UPDATE tmstates SET '.trim($sqlFrag, ', ').', datelastmodified = NOW() WHERE (stateID = ?)';
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
		$this->schemaMap = array('stateCode' => 's', 'stateName' => 's', 'description' => 's', 'refUrl' => 's', 'notes' => 's', 'sortSeq' => 'i', 'modifiedUid' => 'i', 'createdUid' => 'i');
	}

	//Attribute table
	public function getAttributeArr($filterArr = null){
		$retArr = array();
		$uidArr = array();
		$this->setAttributeSchema();
		$sql = 'SELECT occid, stateID, ' . implode(', ', array_keys($this->schemaMap)) . ', initialTimestamp FROM tmattributes ';
		$sqlWhere = '';
		if($this->occid){
			$sqlWhere .= 'AND (occid = ?) ';
			$this->parameterArr[] = $this->occid;
			$this->typeStr = 'i';
		}
		if($this->stateID){
			$sqlWhere .= 'AND (stateID = ?) ';
			$this->parameterArr[] = $this->stateID;
			$this->typeStr .= 'i';
		}
		if($filterArr){
			$sqlWhere .= $this->appendFilterElements($filterArr);
		}
		if($sqlWhere) $sql .= 'WHERE ' . substr($sqlWhere, 4);
		if($stmt = $this->conn->prepare($sql)){
			if($this->parameterArr) $stmt->bind_param($this->typeStr, ...$this->parameterArr);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				while($r = $rs->fetch_assoc()){
					$retArr[$r['stateID']] = $r;
					if($r['createdUid']) $uidArr[$r['createdUid']] = '';
					if($r['modifiedUid']) $uidArr[$r['modifiedUid']] = '';
				}
				$rs->free();
			}
			$stmt->close();
		}
		$this->translateUserIDs($uidArr, $retArr);
		return $retArr;
	}

	public function insertAttribute($inputArr){
		$status = false;
		if($this->stateID && $this->occid){
			if(!isset($inputArr['createdUid'])) $inputArr['createdUid'] = $GLOBALS['SYMB_UID'];
			$sql = 'INSERT INTO tmattributes(stateID, occid';
			$sqlValues = '?, ?';
			$this->typeStr = 'ii';
			$this->setAttributeSchema();
			$this->setParameterArr($inputArr);
			foreach($this->parameterArr as $fieldName => $value){
				$sql .= ', '.$fieldName;
				$sqlValues .= ', ?';
				$paramArr[] = $value;
			}
			$sql .= ') VALUES('.$sqlValues.') ';
			echo $sql;
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
			if(!isset($inputArr['modifiedUid'])) $inputArr['modifiedUid'] = $GLOBALS['SYMB_UID'];
			$this->setAttributeSchema();
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
			$sql = 'UPDATE tmattributes SET '.trim($sqlFrag, ', ').', datelastmodified = NOW() WHERE (stateID = ?) AND (occid = ?)';
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
		$this->schemaMap = array('modifier' => 's', 'xValue' => 's', 'imgid' => 'i', 'imageCoordinates' => 's', 'source' => 's', 'notes' => 's', 'statusCode' => 'i', 'modifiedUid' => 'i', 'createdUid' => 'i');
	}

	//Data prep and support functions
	private function setParameterArr($inputArr){
		foreach($this->schemaMap as $field => $type){
			$postField = '';
			if(isset($inputArr[$field])) $postField = $field;
			elseif(isset($inputArr[strtolower($field)])) $postField = strtolower($field);
			if($postField){
				$value = trim($inputArr[$postField]);
				if($value){
					if(strtolower($postField) == 'establisheddate') $value = OccurrenceUtilities::formatDate($value);
					//if(strtolower($postField) == 'modifieduid') $value = OccurrenceUtilities::verifyUser($value, $this->conn);
					//if(strtolower($postField) == 'createduid') $value = OccurrenceUtilities::verifyUser($value, $this->conn);
				}
				else $value = null;
				$this->parameterArr[$field] = $value;
				$this->typeStr .= $type;
			}
		}
		if(!empty($inputArr['occid']) && !$this->occid) $this->occid = $inputArr['occid'];
	}

	private function appendFilterElements($filterArr){
		$sqlFrag = '';
		$schemaMap = array_change_key_case($this->schemaMap);
		foreach($filterArr as $field => $cond){
			$field = strtolower($field);
			if(array_key_exists($field, $schemaMap)){
				$sqlFrag .= 'AND '.$field.' = ? ';
				$this->parameterArr[] = $cond;
				$this->typeStr .= $schemaMap[$field];
			}
		}
		return $sqlFrag;
	}

	private function translateUserIDs($uidArr, &$dataArr){
		if($uidArr){
			//Add user names for modified and created by
			$sql = 'SELECT uid, firstname, lastname, username FROM users WHERE uid IN(' . implode(',', array_keys($uidArr)) . ')';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$uidArr[$r->uid] = $r->lastname . ($r->firstname ? ', ' . $r->firstname : '');
				}
				$rs->free();
			}
			foreach($dataArr as $dataID => $unitArr){
				if($unitArr['createdUid'] && array_key_exists($unitArr['createdUid'], $uidArr)) $dataArr[$dataID]['createdBy'] = $uidArr[$unitArr['createdUid']];
				if($unitArr['modifiedUid'] && array_key_exists($unitArr['modifiedUid'], $uidArr)) $dataArr[$dataID]['modifiedBy'] = $uidArr[$unitArr['modifiedUid']];
			}
		}
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

	public function getStateID(){
		return $this->stateID;
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