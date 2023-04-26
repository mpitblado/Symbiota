<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorManager.php');
include_once($SERVER_ROOT.'/classes/ImageLocalProcessor.php');

//-------------------------------------------------------------------------------------------//
$processManager  = new SpecProcessorManager();
$imageProcessor = new ImageLocalProcessor();

$projArr = $processManager->getProjects('automatedProcessing');
foreach($projArr as $spprid => $processingTitle){
	$processManager->setProjVariables($spprid);
	$collid = $processManager->getCollid();
	$collArr = array(
			$collid => array(
			'pmterm' => $processManager->getSpecKeyPattern(),
			'prpatt' => $processManager->getPatternReplace(),
			'prrepl' => $processManager->getReplaceStr()
		)
	);
	$imageProcessor->setCollArr($collArr);
	$imageProcessor->setLogMode(2);
	$logPath = $SERVER_ROOT.(substr($SERVER_ROOT,-1) == '/'?'':'/').'content/logs/imgProccessing';
	if(!file_exists($logPath)) mkdir($logPath);
	$imageProcessor->setLogPath($logPath);
	$logFile = $collid.'_'.$processManager->getInstitutionCode();
	if($processManager->getCollectionCode()) $logFile .= '-'.$processManager->getCollectionCode();
	$imageProcessor->initProcessor($logFile);

	$imageProcessor->setMatchCatalogNumber((array_key_exists('matchcatalognumber', $_POST)?1:0));
	$imageProcessor->setMatchOtherCatalogNumbers((array_key_exists('matchothercatalognumbers', $_POST)?1:0));
	$imageProcessor->setDbMetadata(1);
	$imageProcessor->setSourcePathBase($specManager->getSourcePath());
	$imageProcessor->setTargetPathBase($specManager->getTargetPath());
	$imageProcessor->setImgUrlBase($specManager->getImgUrlBase());
	$imageProcessor->setServerRoot($SERVER_ROOT);
	if($specManager->getWebPixWidth()) $imageProcessor->setWebPixWidth($specManager->getWebPixWidth());
	if($specManager->getTnPixWidth()) $imageProcessor->setTnPixWidth($specManager->getTnPixWidth());
	if($specManager->getLgPixWidth()) $imageProcessor->setLgPixWidth($specManager->getLgPixWidth());
	if($specManager->getWebMaxFileSize()) $imageProcessor->setWebFileSizeLimit($specManager->getWebMaxFileSize());
	if($specManager->getLgMaxFileSize()) $imageProcessor->setLgFileSizeLimit($specManager->getLgMaxFileSize());
	if($specManager->getJpgQuality()) $imageProcessor->setJpgQuality($specManager->getJpgQuality());
	$imageProcessor->setUseImageMagick($specManager->getUseImageMagick());
	$imageProcessor->setWebImg($_POST['webimg']);
	$imageProcessor->setTnImg($_POST['createtnimg']);
	$imageProcessor->setLgImg($_POST['createlgimg']);
	$imageProcessor->setCreateNewRec($_POST['createnewrec']);
	$imageProcessor->setImgExists($_POST['imgexists']);
	$imageProcessor->setKeepOrig(0);
	$imageProcessor->setCustomStoredProcedure($specManager->getCustomStoredProcedure());
	$imageProcessor->setSkeletalFileProcessing($_POST['skeletalFileProcessing']);

	//Run process
	$imageProcessor->batchLoadSpecimenImages();






	$this->title = $row->title;
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

$imageProcessor->setLogMode($logMode);
$imageProcessor->setLogPath($logPath);

$imageProcessor->initProcessor($logTitle);
$imageProcessor->setCollArr($collArr);

//Run process
$imageProcessor->batchLoadSpecimenImages();


//Initiate log file
if(isset($silent) && $silent) $logMode = 2;
$imageProcessor->setLogMode($logMode);
if(!$logProcessorPath && $logPath) $logProcessorPath = $logPath;
$imageProcessor->setLogPath($logProcessorPath);

//Set remaining variables
$imageProcessor->setDbMetadata($dbMetadata);
$imageProcessor->setSourcePathBase($sourcePathBase);
$imageProcessor->setTargetPathBase($targetPathBase);
$imageProcessor->setImgUrlBase($imgUrlBase);
$imageProcessor->setServerRoot($serverRoot);
if($webPixWidth) $imageProcessor->setWebPixWidth($webPixWidth);
if($tnPixWidth) $imageProcessor->setTnPixWidth($tnPixWidth);
if($lgPixWidth) $imageProcessor->setLgPixWidth($lgPixWidth);
if($webFileSizeLimit) $imageProcessor->setWebFileSizeLimit($webFileSizeLimit);
if($lgFileSizeLimit) $imageProcessor->setLgFileSizeLimit($lgFileSizeLimit);
$imageProcessor->setJpgQuality($jpgQuality);

if(isset($webImg) && $webImg) $imageProcessor->setWebImg($webImg);
elseif(isset($createWebImg) && $createWebImg) $imageProcessor->setCreateWebImg($createWebImg);
if(isset($tnImg) && $tnImg) $imageProcessor->setTnImg($tnImg);
elseif(isset($createTnImg) && $createTnImg) $imageProcessor->setCreateTnImg($createTnImg);
if(isset($lgImg) && $lgImg) $imageProcessor->setLgImg($lgImg);
elseif(isset($createLgImg) && $createLgImg) $imageProcessor->setCreateLgImg($createLgImg);
$imageProcessor->setKeepOrig($keepOrig);
$imageProcessor->setCreateNewRec($createNewRec);
if(isset($imgExists)) $imageProcessor->setImgExists($imgExists);
elseif(isset($copyOverImg)) $imageProcessor->setCopyOverImg($copyOverImg);
if(isset($matchOtherCatalogNumbers)) $imageProcessor->setMatchOtherCatalogNumbers($matchOtherCatalogNumbers);

$imageProcessor->initProcessor($logTitle);
$imageProcessor->setCollArr($collArr);

//Run process
$imageProcessor->batchLoadSpecimenImages();
?>