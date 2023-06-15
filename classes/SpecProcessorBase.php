<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class SpecProcessorBase extends Manager {

	protected $collid = 0;
	protected $title;
	protected $collectionName;
	protected $institutionCode;
	protected $collectionCode;
	protected $projectType;
	protected $managementType;
	protected $specKeyPattern;
	protected $patternReplace;
	protected $replaceStr;
	protected $coordX1;
	protected $coordX2;
	protected $coordY1;
	protected $coordY2;
	protected $sourcePath;
	protected $targetPath;
	protected $imgUrlBase;
	protected $webPixWidth = '';
	protected $tnPixWidth = '';
	protected $lgPixWidth = '';
	protected $jpgQuality = 80;
	protected $webMaxFileSize = 500000;
	protected $lgMaxFileSize = 10000000;
	protected $webImg = 1;
	protected $createTnImg = 1;
	protected $createLgImg = 2;
	protected $customStoredProcedure;
	protected $lastRunDate = '';

	protected $dbMetadata = 1;			//Only used when run as a standalone script
	protected $processUsingImageMagick = 0;

	function __construct($connType = 'readonly') {
		parent::__construct(null, $connType);
	}

	function __destruct(){
		parent::__destruct();
	}

	public function setCollId($id){
		$this->collid = $id;
		if($this->collid && is_numeric($this->collid) && !$this->collectionName){
			$sql = 'SELECT collid, collectionname, institutioncode, collectioncode, managementtype FROM omcollections WHERE (collid = '.$this->collid.')';
			if($rs = $this->conn->query($sql)){
				if($row = $rs->fetch_object()){
					$this->collectionName = $row->collectionname;
					$this->institutionCode = $row->institutioncode;
					$this->collectionCode = $row->collectioncode;
					$this->managementType = $row->managementtype;
				}
				else{
					exit('ABORTED: unable to locate collection in data');
				}
				$rs->free();
			}
			else{
				exit('ABORTED: unable run SQL to obtain collectionName');
			}
		}
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

	public function getProjects($cond = null){
		$projArr = array();
		if($this->collid){
			$sql = 'SELECT spprid, title FROM specprocessorprojects  WHERE (collid = ?) AND (title != "OCR Harvest") ';
			if($cond && $cond == 'automatedProcessing') $sql .= 'AND (processingCode = 1)';
			if($stmt = $this->conn->query($sql)){
				$stmt->bind_param('i', $this->collid);
				$stmt->execute();
				$spprid = null;
				$title = null;
				$stmt->bind_result($spprid, $title);
				while($stmt->fetch()){
					$projArr[$spprid] = $title;
				}
				$stmt->close();
			}
		}
		return $projArr;
	}

 	//Misc Stats functions
	public function downloadReportData($target){
		$fileName = 'SymbSpecNoImages_'.time().'.csv';
		header ('Content-Type: text/csv; charset='.$GLOBALS['CHARSET']);
		header ('Content-Disposition: attachment; filename="'.$fileName.'"');
		$headerArr = array('occid','catalogNumber','sciname','recordedBy','recordNumber','eventDate','country','stateProvince','county');
		$sqlFrag = '';
		if($target == 'dlnoimg'){
			$sqlFrag .= 'FROM omoccurrences o LEFT JOIN images i ON o.occid = i.occid WHERE o.collid = '.$this->collid.' AND i.imgid IS NULL ';
		}
		elseif($target == 'unprocnoimg'){
			$sqlFrag .= 'FROM omoccurrences o LEFT JOIN images i ON o.occid = i.occid WHERE (o.collid = '.$this->collid.') AND (i.imgid IS NULL) AND (o.processingstatus = "unprocessed") ';
		}
		elseif($target == 'noskel'){
			$sqlFrag .= 'FROM omoccurrences o WHERE (o.collid = '.$this->collid.') AND (o.processingstatus = "unprocessed") AND (o.sciname IS NULL) AND (o.stateprovince IS NULL)';
		}
		elseif($target == 'unprocwithdata'){
			$headerArr[] = 'locality';
			$sqlFrag .= 'FROM omoccurrences o WHERE (o.collid = '.$this->collid.') AND (o.processingstatus = "unprocessed") AND (stateProvince IS NOT NULL) AND (o.locality IS NOT NULL)';
		}
		$headerArr[] = 'processingstatus';
		$sql = 'SELECT o.'.implode(',',$headerArr).' '.$sqlFrag;
		//echo $sql;
		$rs = $this->conn->query($sql);
		//Write column names out to file
		if($rs){
    		$outstream = fopen("php://output", "w");
			fputcsv($outstream, $headerArr);
			while($row = $rs->fetch_assoc()){
				fputcsv($outstream, $row);
			}
			fclose($outstream);
			$rs->free();
		}
		else{
			echo "Recordset is empty.\n";
		}
	}

	public function getLogListing(){
		$retArr = array();
		if($this->collid){
			$dirArr = array('imgProccessing','cyverse','iplant','processing/imgmap');
			$logPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1) == '/'?'':'/').'content/logs/';
			foreach($dirArr as $dirPath){
				if(file_exists($logPath.$dirPath)){
					if($fh = opendir($logPath.$dirPath)){
						while($fileName = readdir($fh)){
							if(strpos($fileName,$this->collid.'_') === 0){
								$retArr[$dirPath][] = $fileName;
							}
						}
						if(isset($retArr[$dirPath])) rsort($retArr[$dirPath]);
					}
				}
			}
		}
		return $retArr;
	}

	//Set and Get functions
	public function getCollid(){
		return $this->collid;
	}

	public function setTitle($t){
		$this->title = $t;
	}

	public function getTitle(){
		return $this->title;
	}

	public function setCollectionName($cn){
		$this->collectionName = $cn;
	}

	public function getCollectionName(){
		return $this->collectionName;
	}

	public function getInstitutionCode(){
		return $this->institutionCode;
	}

	public function getCollectionCode(){
		return $this->collectionCode;
	}

	public function setProjectType($t){
		$this->projectType = $t;
	}

	public function getProjectType(){
		return $this->projectType;
	}

	public function setManagementType($t){
		$this->managementType = $t;
	}

	public function getManagementType(){
		return $this->managementType;
	}

	public function setSpecKeyPattern($p){
		$this->specKeyPattern = $p;
	}

	public function getSpecKeyPattern(){
		return $this->specKeyPattern;
	}

	public function setPatternReplace($str){
		$this->patternReplace = $str;
	}

	public function getPatternReplace(){
		return $this->patternReplace;
	}

	public function setReplaceStr($str){
		$this->replaceStr = $str;
	}

	public function getReplaceStr(){
		return $this->replaceStr;
	}

	public function setCoordX1($x){
		$this->coordX1 = $x;
	}

	public function getCoordX1(){
		return $this->coordX1;
	}

	public function setCoordX2($x){
		$this->coordX2 = $x;
	}

	public function getCoordX2(){
		return $this->coordX2;
	}

	public function setCoordY1($y){
		$this->coordY1 = $y;
	}

	public function getCoordY1(){
		return $this->coordY1;
	}

	public function setCoordY2($y){
		$this->coordY2 = $y;
	}

	public function getCoordY2(){
		return $this->coordY2;
	}

	public function setSourcePath($p){
		$this->sourcePath = $p;
	}

	public function getSourcePath(){
		return $this->sourcePath;
	}

	public function getSourcePathDefault(){
		$sourcePath = $this->sourcePath;
		if(!$sourcePath && $this->projectType == 'iplant' && $GLOBALS['IPLANT_IMAGE_IMPORT_PATH']){
			$sourcePath = $GLOBALS['IPLANT_IMAGE_IMPORT_PATH'];
			if(strpos($sourcePath, '--INSTITUTION_CODE--')) $sourcePath = str_replace('--INSTITUTION_CODE--', $this->institutionCode, $sourcePath);
			if(strpos($sourcePath, '--COLLECTION_CODE--')) $sourcePath = str_replace('--COLLECTION_CODE--', $this->collectionCode, $sourcePath);
		}
		return $sourcePath;
	}

	public function setTargetPath($p){
		$this->targetPath = $p;
	}

	public function getTargetPath(){
		return $this->targetPath;
	}

	public function setImgUrlBase($u){
		if(substr($u,-1) != '/') $u = '/';
		$this->imgUrlBase = $u;
	}

	public function getImgUrlBase(){
		return $this->imgUrlBase;
	}

	public function setWebPixWidth($w){
		$this->webPixWidth = $w;
	}

	public function getWebPixWidth(){
		return $this->webPixWidth;
	}

	public function setTnPixWidth($tn){
		$this->tnPixWidth = $tn;
	}

	public function getTnPixWidth(){
		return $this->tnPixWidth;
	}

	public function setLgPixWidth($lg){
		$this->lgPixWidth = $lg;
	}

	public function getLgPixWidth(){
		return $this->lgPixWidth;
	}

	public function setJpgQuality($jc){
		$this->jpgQuality = $jc;
	}

	public function getJpgQuality(){
		return $this->jpgQuality;
	}

	public function setWebMaxFileSize($s){
		$this->webMaxFileSize = $s;
	}

	public function getWebMaxFileSize(){
		return $this->webMaxFileSize;
	}

	public function setLgMaxFileSize($s){
		$this->lgMaxFileSize = $s;
	}

	public function getLgMaxFileSize(){
		return $this->lgMaxFileSize;
	}

	public function setWebImg($c){
		$this->webImg = $c;
	}

	public function getWebImg(){
		return $this->webImg;
	}

	public function setCreateTnImg($c){
		$this->createTnImg = $c;
	}

	public function getCreateTnImg(){
		return $this->createTnImg;
	}

	public function setCreateLgImg($c){
		$this->createLgImg = $c;
	}

	public function getCreateLgImg(){
		return $this->createLgImg;
	}

	public function setCustomStoredProcedure($c){
		$this->customStoredProcedure = $c;
	}

	public function getCustomStoredProcedure(){
		return $this->customStoredProcedure;
	}

	public function setLastRunDate($date){
		$this->lastRunDate = $date;
	}

	public function getLastRunDate(){
		return $this->lastRunDate;
	}

	public function setDbMetadata($v){
		$this->dbMetadata = $v;
	}

 	public function setUseImageMagick($useIM){
 		$this->processUsingImageMagick = $useIM;
 	}

 	public function getUseImageMagick(){
 		return $this->processUsingImageMagick;
 	}
}
?>