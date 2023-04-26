<?php
class SpecProcessorReports extends Manager{

	private $collid;

	function __construct() {
		parent::__construct();
	}

	function __destruct(){
		parent::__destruct();
	}

	public function getProcessingStats(){
		$retArr = array();
		$retArr['total'] = $this->getTotalCount();
		$retArr['ps'] = $this->getProcessingStatusCountArr();
		$retArr['noimg'] = $this->getSpecNoImageCount();
		$retArr['unprocnoimg'] = $this->getUnprocSpecNoImage();
		$retArr['noskel'] = $this->getSpecNoSkel();
		$retArr['unprocwithdata'] = $this->getUnprocWithData();
		return $retArr;
	}

	private function getTotalCount(){
		$totalCnt = 0;
		if($this->collid){
			//Get processing status counts
			$sql = 'SELECT count(*) AS cnt '.
				'FROM omoccurrences '.
				'WHERE collid = '.$this->collid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$totalCnt = $r->cnt;
			}
			$rs->free();
		}
		return $totalCnt;
	}

	public function getProcessingStatusCountArr(){
		$retArr = array();
		if($this->collid){
			//Get processing status counts
			$psArr = array();
			$sql = 'SELECT processingstatus, count(*) AS cnt '.
				'FROM omoccurrences '.
				'WHERE collid = '.$this->collid.' GROUP BY processingstatus';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$psArr[strtolower($r->processingstatus)] = $r->cnt;
			}
			$rs->free();
			//Load into $retArr in a specific order
			$statusArr = array('unprocessed','stage 1','stage 2','stage 3','pending duplicate','pending review-nfn','pending review','expert required','reviewed','closed','empty status');
			foreach($statusArr as $v){
				if(array_key_exists($v,$psArr)){
					$retArr[$v] = $psArr[$v];
					unset($psArr[$v]);
				}
			}
			//Grab untraditional processing statuses
			foreach($psArr as $k => $cnt){
				$retArr[$k] = $cnt;
			}
		}
		return $retArr;
	}

	private function getSpecNoImageCount(){
		//Count specimens without images
		$cnt = 0;
		if($this->collid){
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM omoccurrences o LEFT JOIN images i ON o.occid = i.occid '.
				'WHERE o.collid = '.$this->collid.' AND i.imgid IS NULL ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	private function getUnprocSpecNoImage(){
		//Count unprocessed specimens without images (e.g. generated from skeletal file)
		$cnt = 0;
		if($this->collid){
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM omoccurrences o LEFT JOIN images i ON o.occid = i.occid '.
				'WHERE (o.collid = '.$this->collid.') AND (i.imgid IS NULL) AND (o.processingstatus = "unprocessed") ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	private function getSpecNoSkel(){
		//Count unprocessed specimens without skeletal data
		$cnt = 0;
		if($this->collid){
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM omoccurrences o '.
				'WHERE (o.collid = '.$this->collid.') AND (o.processingstatus = "unprocessed") '.
				'AND (o.sciname IS NULL) AND (o.stateprovince IS NULL)';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	private function getUnprocWithData(){
		$cnt = 0;
		if($this->collid){
			$sql = 'SELECT count(*) AS cnt FROM omoccurrences '.
				'WHERE (processingstatus = "unprocessed") AND (stateProvince IS NOT NULL) AND (locality IS NOT NULL) AND (collid = '.$this->collid.')';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	public function getUserList(){
		$retArr = array();
		$sql = 'SELECT DISTINCT e.uid FROM omoccurrences o INNER JOIN omoccuredits e ON o.occid = e.occid WHERE (o.collid = '.$this->collid.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->uid] = '';
		}
		$rs->free();

		$sql = 'SELECT DISTINCT uid, CONCAT(CONCAT_WS(", ", lastname, firstname)," (", username,")") AS username FROM users WHERE (uid IN('.implode(',', array_keys($retArr)).')) ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->uid] = $r->username;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	public function getFullStatReport($getArr){
		$retArr = array();
		$startDate = (preg_match('/^[\d-]+$/', $getArr['startdate'])?$getArr['startdate']:'');
		$endDate = (preg_match('/^[\d-]+$/', $getArr['enddate'])?$getArr['enddate']:'');
		$uid = (is_numeric($getArr['uid'])?$getArr['uid']:'');
		$interval = $getArr['interval'];
		$processingStatus = $this->cleanInStr($getArr['processingstatus']);

		$dateFormat = '';
		$dfgb = '';
		if($interval == 'hour'){
			$dateFormat = '%Y-%m-%d %Hhr, %W';
			$dfgb = '%Y-%m-%d %H';
		}
		elseif($interval == 'day'){
			$dateFormat= '%Y-%m-%d, %W';
			$dfgb = '%Y-%m-%d';
		}
		elseif($interval == 'week'){
			$dateFormat= '%Y-%m week %U';
			$dfgb = '%Y-%m-%U';
		}
		elseif($interval == 'month'){
			$dateFormat= '%Y-%m';
			$dfgb = '%Y-%m';
		}
		$sql = 'SELECT DATE_FORMAT(e.initialtimestamp, "'.$dateFormat.'") AS timestr, u.username';
		if($processingStatus) $sql .= ', e.fieldvalueold, e.fieldvaluenew, o.processingstatus';
		$sql .= ', count(DISTINCT o.occid) AS cnt ';
		$hasEditType = $this->hasEditType();
		if($hasEditType){
			$sql .= ', COUNT(DISTINCT CASE WHEN e.editType = 0 THEN o.occid ELSE NULL END) as cntexcbatch ';
		}
		$sql .= 'FROM omoccurrences o INNER JOIN omoccuredits e ON o.occid = e.occid '.
			'INNER JOIN users u ON e.uid = u.uid '.
			'WHERE (o.collid = '.$this->collid.') ';
		if($startDate && $endDate){
			$sql .= 'AND (e.initialtimestamp BETWEEN "'.$startDate.'" AND "'.$endDate.'") ';
		}
		elseif($startDate){
			$sql .= 'AND (DATE(e.initialtimestamp) > "'.$startDate.'") ';
		}
		elseif($endDate){
			$sql .= 'AND (DATE(e.initialtimestamp) < "'.$endDate.'") ';
		}
		if($uid){
			$sql .= 'AND (e.uid = '.$uid.') ';
		}
		if($processingStatus){
			$sql .= 'AND e.fieldname = "processingstatus" ';
			if($processingStatus != 'all'){
				$sql .= 'AND (e.fieldvaluenew = "'.$processingStatus.'") ';
			}
		}
		$sql .= 'GROUP BY DATE_FORMAT(e.initialtimestamp, "'.$dfgb.'"), u.username ';
		if($processingStatus) $sql .= ', e.fieldvalueold, e.fieldvaluenew, o.processingstatus ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->timestr][$r->username]['cnt'] = $r->cnt;
			if($hasEditType) $retArr[$r->timestr][$r->username]['cntexcbatch'] = $r->cntexcbatch;
			if($processingStatus){
				$retArr[$r->timestr][$r->username]['os'] = $r->fieldvalueold;
				$retArr[$r->timestr][$r->username]['ns'] = $r->fieldvaluenew;
				$retArr[$r->timestr][$r->username]['cs'] = $r->processingstatus;
			}
		}
		$rs->free();
		return $retArr;
	}

	public function hasEditType(){
		$hasEditType = false;
		$rsTest = $this->conn->query('SHOW COLUMNS FROM omoccuredits WHERE field = "editType"');
		if($rsTest->num_rows) $hasEditType = true;
		$rsTest->free();
		return $hasEditType;
	}

	public function setCollid($collid){
		$this->collid = $collid;
	}
}
?>