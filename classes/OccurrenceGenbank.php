<?php
include_once($SERVER_ROOT . '/classes/Manager.php');

class OccurrenceGenbank extends Manager{

	private $conditionArr = array();
	private $collid;
	private $collMeta = array();
	private $ncbiSummaryLink = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=nuccore&version=2.0&id=';
	private $ncbi = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nuccore&term=';

	public function __construct(){
		parent::__construct(null, 'write');
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function harvestLinks($start, $limit){
		$status = false;
		if($this->collid){
			$sql = 'SELECT catalogNumber, recordedBy FROM omoccurrences WHERE collid = '.$this->collid;

		}
		return $status;
	}

	//Data retrival functions

	// Setters and getters
	public function addCondition($field, $value){
		$this->conditionArr[$field] = $value;
	}

	public function setCollid($id){
		if(is_numeric($id)){
			$this->collid = $id;
			$sql = 'SELECT collectionName, institutionCode, collectionCode FROM omcollections WHERE collid = '.$this->collid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$this->collMeta['collName'] = $r->collectionName;
				$this->collMeta['instCode'] = $r->institutionCode;
				$this->collMeta['collCode'] = $r->collectionCode;
			}
			$rs->free();
		}
	}

	public function getCollMeta(){
		return $this->collMeta;
	}
}
?>