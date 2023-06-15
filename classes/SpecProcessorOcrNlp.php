<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class SpecProcessorOcrNlp extends Manager {

	protected $collid;
	protected $specKeyPattern;
	protected $sourcePath;

	function __construct($connType = 'readonly') {
		parent::__construct(null, $connType);
	}

	function __destruct(){
		parent::__destruct();
	}

	public function setProjVariables($crit){
		$sqlWhere = '';
		if(is_numeric($crit)){
			$sqlWhere .= 'WHERE (spprid = '.$crit.')';
		}
		elseif($crit == 'OCR Harvest' && $this->collid){
			$sqlWhere .= 'WHERE (collid = '.$this->collid.') ';
		}
		if($sqlWhere){
			$sql = 'SELECT collid, title, speckeypattern, patternreplace, replacestr, projecttype, coordx1, coordx2, coordy1, coordy2, sourcepath, targetpath, '.
				'imgurl, webpixwidth, tnpixwidth, lgpixwidth, jpgcompression, createtnimg, createlgimg, source, lastrundate '.
				'FROM specprocessorprojects '.$sqlWhere;
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				if(!$this->collid) $this->setCollId($row->collid);
				$this->title = $row->title;
				$this->specKeyPattern = $row->speckeypattern;
				$this->patternReplace = $row->patternreplace;
				$this->replaceStr = $row->replacestr;
				$this->projectType = $row->projecttype;
				$this->coordX1 = $row->coordx1;
				$this->coordX2 = $row->coordx2;
				$this->coordY1 = $row->coordy1;
				$this->coordY2 = $row->coordy2;
				$this->sourcePath = $row->sourcepath;
				$this->targetPath = $row->targetpath;
				$this->imgUrlBase = $row->imgurl;
				if($row->webpixwidth) $this->webPixWidth = $row->webpixwidth;
				if($row->tnpixwidth) $this->tnPixWidth = $row->tnpixwidth;
				if($row->lgpixwidth) $this->lgPixWidth = $row->lgpixwidth;
				if($row->jpgcompression) $this->jpgQuality = $row->jpgcompression;
				$this->createTnImg = $row->createtnimg;
				$this->createLgImg = $row->createlgimg;
				$this->lastRunDate = $row->lastrundate;
				if(!$this->lastRunDate && preg_match('/\d{4}-\d{2}-\d{2}/', $row->source)) $this->lastRunDate = $row->source;
				if(!$this->projectType){
					if($this->title == 'iDigBio CSV upload'){
						$this->projectType = 'idigbio';
					}
					elseif($this->title == 'IPlant Image Processing'){
						$this->projectType = 'iplant';
					}
					elseif($this->title == 'OCR Harvest'){
						break;
					}
					else{
						$this->projectType = 'local';
					}
				}
			}
			$rs->free();

			//Temporary code until customStoredProcedure field is offically integrated into specprocessorprojects table
			$sql = 'SELECT customStoredProcedure FROM specprocessorprojects '.$sqlWhere;
			if($rs = $this->conn->query($sql)){
				if($r = $rs->fetch_object()) $this->customStoredProcedure = $r->customStoredProcedure;
				$rs->free();
			}

			//if(!$this->targetPath) $this->targetPath = $GLOBALS['imageRootPath'];
			//if(!$this->imgUrlBase) $this->imgUrlBase = $GLOBALS['imageRootUrl'];
			if($this->sourcePath && substr($this->sourcePath,-1) != '/' && substr($this->sourcePath,-1) != '\\') $this->sourcePath .= '/';
			if($this->targetPath && substr($this->targetPath,-1) != '/' && substr($this->targetPath,-1) != '\\') $this->targetPath .= '/';
			if($this->imgUrlBase && substr($this->imgUrlBase,-1) != '/') $this->imgUrlBase .= '/';
		}
	}

	//OCR related counts
	public function getSpecWithImage($procStatus = ''){
		//Count of specimens with images but no OCR
		$cnt = 0;
		if($this->collid){
			$sql = 'SELECT COUNT(DISTINCT o.occid) AS cnt FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid WHERE (o.collid = '.$this->collid.') ';
			if($procStatus){
				if($procStatus == 'null') $sql .= 'AND processingstatus IS NULL';
				else $sql .= 'AND processingstatus = "'.$this->cleanInStr($procStatus).'"';
			}
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	public function getSpecNoOcr($procStatus = ''){
		//Count of specimens with images but no OCR
		$cnt = 0;
		if($this->collid){
			$sql = 'SELECT COUNT(DISTINCT o.occid) AS cnt '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'LEFT JOIN specprocessorrawlabels r ON i.imgid = r.imgid '.
				'WHERE o.collid = '.$this->collid.' AND r.imgid IS NULL ';
			if($procStatus){
				if($procStatus == 'null'){
					$sql .= 'AND processingstatus IS NULL';
				}
				else{
					$sql .= 'AND processingstatus = "'.$this->cleanInStr($procStatus).'"';
				}
			}
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	public function getProcessingStatusCount($ps){
		$cnt = 0;
		if($this->collid){
			//Get processing status counts
			$sql = 'SELECT count(*) AS cnt '.
				'FROM omoccurrences '.
				'WHERE collid = '.$this->collid.' AND processingstatus = "'.$this->cleanInStr($ps).'"';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	public function getUnprocSpecNoImage(){
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

	public function getProcessingStatusList(){
		$retArr = array();
		if($this->collid){
			$sql = 'SELECT DISTINCT processingstatus FROM omoccurrences WHERE collid = '.$this->collid;
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->processingstatus) $retArr[] = $r->processingstatus;
			}
			$rs->free();
			sort($retArr);
		}
		return $retArr;
	}

	//Setters and getters
	public function setCollid($id){
		$this->collid = $id;
	}

	public function getSpecKeyPattern(){
		return $this->specKeyPattern;
	}

	public function getSourcePath(){
		return $this->sourcePath;
	}
}
?>