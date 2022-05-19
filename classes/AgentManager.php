<?php
include_once ($SERVER_ROOT . '/classes/Manager.php');

class AgentManager extends Manager{

	function __construct(){
		parent::__construct(null, 'write');
	}

	function __destruct(){
		parent::__destruct();
	}

	public function getAgentList(){
		$retArr = array();
		$sql = 'SELECT a.agentID, a.familyName, a.firstName	
			FROM agents a  
			ORDER BY a.familyName';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->agentID]['familyName'] = $r->familyName;
			$retArr[$r->agentID]['firstName'] = $r->firstName;
		}
		$rs->free();

		return $retArr;
	}

	public function getAgent($agentID){
		$retArr = array();
		if(is_numeric($agentID)){
			$sql = 'SELECT a.agentID, a.familyName, a.firstName, a.middleName, a.startYearActive, a.endYearActive, a.notes, a.rating, a.guid, a.preferredRecByID, a.biography, a.taxonomicgroups, 
			a.collectionsat, a.curated, a.nototherwisespecified, a.type, a.prefix, a.suffix, a.nameString, a.mbox_sha1sum, a.yearOfBirth, a.yearOfBirthModifier, a.yearOfDeath, 
			a.yearOfDeathModifier, a.living, a.recordID
				FROM agents a 
				WHERE a.agentID = '.$agentID;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retArr['familyName'] = $r->familyName;
				$retArr['firstName'] = $r->firstName;
				$retArr['middleName'] = $r->familyName;
				$retArr['startYearActive'] = $r->startYearActive;
				$retArr['endYearActive'] = $r->endYearActive;
				$retArr['notes'] = $r->notes;
				$retArr['rating'] = $r->rating;
				$retArr['guid'] = $r->guid;
				$retArr['preferredRecByID'] = $r->preferredRecByID;
				$retArr['biography'] = $r->biography;
				$retArr['taxonomicgroups'] = $r->taxonomicgroups;
				$retArr['collectionsat'] = $r->collectionsat;
				$retArr['curated'] = $r->curated;
				$retArr['nototherwisespecified'] = $r->nototherwisespecified;
				$retArr['type'] = $r->type;
				$retArr['prefix'] = $r->prefix;
				$retArr['suffix'] = $r->suffix;
				$retArr['nameString'] = $r->nameString;
				$retArr['mbox_sha1sum'] = $r->mbox_sha1sum;
				$retArr['yearOfBirth'] = $r->yearOfBirth;
				$retArr['yearOfBirthModifier'] = $r->yearOfBirthModifier;
				$retArr['yearOfDeath'] = $r->yearOfDeath;
				$retArr['yearOfDeathModifier'] = $r->yearOfDeathModifier;
				$retArr['living'] = $r->living;
				$retArr['recordID'] = $r->recordID;
			}
			else $this->errorMessage = 'ERROR getting Agent';
			$rs->free();
			
		}
		return $retArr;
	}

	public function editAgent($postArr){
		if(is_numeric($postArr['agentID'])){
			if(!$postArr['firstName']){
				$this->errorMessage = 'ERROR editing Agent: First Name must have a value';
				return false;
			}
			if(!$postArr['familyName']){
				$this->errorMessage = 'ERROR editing Agent: Family Name must have a value';
				return false;
			}
			$sql = 'UPDATE agents '.
				'SET firstName = "'.$this->cleanInStr($postArr['firstName']).'", '.
				'familyName = "'.$this->cleanInStr($postArr['familyName']).'", '.
				'middleName = '.($postArr['middleName']?'"'.$this->cleanInStr($postArr['middleName']).'"':'NULL').', '.
				'startYearActive = '.($postArr['startYearActive']?'"'.$this->cleanInStr($postArr['startYearActive']).'"':'NULL').', '.
				'endYearActive = '.(is_numeric($postArr['endYearActive'])?'"'.$this->cleanInStr($postArr['endYearActive']).'"':'NULL').', '.
				'notes = '.($postArr['notes']?'"'.$this->cleanInStr($postArr['notes']).'"':'NULL').' '.
				'WHERE (agentID = '.$postArr['agentID'].')';
			if(!$this->conn->query($sql)){
				$this->errorMessage = 'ERROR saving edits: '.$this->conn->error;
				return false;
			}
		}
		return true;
	}

	public function addAgent($postArr){
		if(!$postArr['firstName'] && !$postArr['familyName']){
			$this->errorMessage = 'ERROR adding geoUnit: Agent must have a first name and last name value';
			return false;
		}
		else{
			$sql = 'INSERT INTO agents(firstName, familyName, middleName, startYearActive, endYearActive, notes) '.
				'VALUES("'.$this->cleanInStr($postArr['firstName']).'", '.
				'"'.$this->cleanInStr($postArr['familyName']).'", '.
				($postArr['middleName']?'"'.$this->cleanInStr($postArr['middleName']).'"':'NULL').', '.
				(is_numeric($postArr['startYearActive'])?'"'.$this->cleanInStr($postArr['startYearActive']).'"':'NULL').', '.
				(is_numeric($postArr['endYearActive'])?'"'.$this->cleanInStr($postArr['endYearActive']).'"':'NULL').', '.
				($postArr['notes']?'"'.$this->cleanInStr($postArr['notes']).'"':'NULL').')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$this->errorMessage = 'ERROR adding unit: '.$this->conn->error;
				return false;
			}
		}
		return true;
	}

	// Setters and getters
}
?>