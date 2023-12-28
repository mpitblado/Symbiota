<?php

class OccurEditorBase extends Manager{

	protected $occid = false;
	protected $collid = false;
	protected $occurrenceMap = array();
	protected $collMap = array();
	protected $fieldArr = array();
	protected $crowdSourceMode = 0;

	public function __construct($conn = null){
		parent::__construct(null, 'write', $conn);
		$this->fieldArr['omoccurrences'] = array('basisofrecord' => 's', 'catalognumber' => 's', 'othercatalognumbers' => 's', 'occurrenceid' => 's', 'ownerinstitutioncode' => 's',
			'institutioncode' => 's', 'collectioncode' => 's', 'eventid' => 's',
			'family' => 's', 'sciname' => 's', 'tidinterpreted' => 'n', 'scientificnameauthorship' => 's', 'identifiedby' => 's', 'dateidentified' => 's',
			'identificationreferences' => 's', 'identificationremarks' => 's', 'taxonremarks' => 's', 'identificationqualifier' => 's', 'typestatus' => 's',
			'recordedby' => 's', 'recordnumber' => 's', 'associatedcollectors' => 's', 'eventdate' => 'd', 'eventdate2' => 'd', 'year' => 'n', 'month' => 'n', 'day' => 'n', 'startdayofyear' => 'n',
			'enddayofyear' => 'n', 'verbatimeventdate' => 's', 'habitat' => 's', 'substrate' => 's', 'fieldnumber' => 's', 'occurrenceremarks' => 's', 'datageneralizations' => 's',
			'associatedtaxa' => 's', 'verbatimattributes' => 's', 'dynamicproperties' => 's', 'reproductivecondition' => 's', 'cultivationstatus' => 's', 'establishmentmeans' => 's',
			'lifestage' => 's', 'sex' => 's', 'individualcount' => 's', 'samplingprotocol' => 's', 'preparations' => 's',
			'country' => 's', 'stateprovince' => 's', 'county' => 's', 'municipality' => 's', 'locationid' => 's', 'locality' => 's', 'localitysecurity' => 'n', 'localitysecurityreason' => 's',
			'locationremarks' => 'n', 'decimallatitude' => 'n', 'decimallongitude' => 'n', 'geodeticdatum' => 's', 'coordinateuncertaintyinmeters' => 'n', 'verbatimcoordinates' => 's',
			'footprintwkt' => 's', 'georeferencedby' => 's', 'georeferenceprotocol' => 's', 'georeferencesources' => 's', 'georeferenceverificationstatus' => 's',
			'georeferenceremarks' => 's', 'minimumelevationinmeters' => 'n', 'maximumelevationinmeters' => 'n','verbatimelevation' => 's',
			'minimumdepthinmeters' => 'n', 'maximumdepthinmeters' => 'n', 'verbatimdepth' => 's','disposition' => 's', 'language' => 's', 'duplicatequantity' => 'n',
			'labelproject' => 's','processingstatus' => 's', 'recordenteredby' => 's', 'observeruid' => 'n', 'dateentered' => 'd');
		$this->fieldArr['omoccurpaleo'] = array('eon','era','period','epoch','earlyinterval','lateinterval','absoluteage','storageage','stage','localstage','biota',
				'biostratigraphy','lithogroup','formation','taxonenvironment','member','bed','lithology','stratremarks','element','slideproperties','geologicalcontextid');
		$this->fieldArr['omoccuridentifiers'] = array('idname','idvalue');
		$this->fieldArr['omexsiccatiocclink'] = array('ometid','exstitle','exsnumber');
	}

	public function __destruct(){
		parent::__destruct();
	}

	protected function setOccurArr($conditionFragment = null, $localIndex = null){
		$retArr = Array();
		$sql = 'SELECT DISTINCT o.occid, o.collid, o.'.implode(',o.',array_keys($this->fieldArr['omoccurrences'])).', datelastmodified FROM omoccurrences o ';
		if($conditionFragment){
			$sql = $conditionFragment;
		}
		elseif($this->occid){
			$sql .= 'WHERE (o.occid = '.$this->occid.')';
		}
		else{
			return null;
		}
		$previousOccid = 0;
		$rs = $this->conn->query($sql);
		$rsCnt = 0;
		$indexArr = array();
		while($row = $rs->fetch_assoc()){
			if($conditionFragment && $previousOccid == $row['occid']) continue;
			if($row['localitysecurityreason'] == '<Security Setting Locked>') $row['localitysecurityreason'] = '[Security Setting Locked]';
			if(is_numeric($localIndex)){
				if($localIndex == $rsCnt || (($rsCnt+1) == $rs->num_rows && !$this->occid)){
					$retArr[$row['occid']] = $row;
					$this->occid = $row['occid'];
				}
			}
			else{
				$retArr[$row['occid']] = $row;
			}
			$previousOccid = $row['occid'];
			$rsCnt++;
		}
		$rs->free();
		if($this->occid && isset($retArr[$this->occid])){
			if($this->collMap && $this->collMap['colltype'] == 'General Observations' && $retArr[$this->occid]['observeruid'] == $GLOBALS['SYMB_UID']) $this->isPersonalManagement = true;
		}

		if($retArr && count($retArr) == 1){
			if(!$this->occid) $this->occid = key($retArr);
			if(!$this->collMap) $this->setCollMap();
			if($this->collMap){
				if(!$retArr[$this->occid]['institutioncode']) $retArr[$this->occid]['institutioncode'] = $this->collMap['institutioncode'];
				if(!$retArr[$this->occid]['collectioncode']) $retArr[$this->occid]['collectioncode'] = $this->collMap['collectioncode'];
				if(!$retArr[$this->occid]['ownerinstitutioncode']) $retArr[$this->occid]['ownerinstitutioncode'] = $this->collMap['institutioncode'];
			}
		}
		$this->setAdditionalIdentifiers($retArr);
		$this->cleanOutArr($retArr);
		$this->occurrenceMap = $retArr;
		if($this->occid) $this->setPaleoData();
	}

	private function setAdditionalIdentifiers(&$occurrenceArr){
		if($occurrenceArr){
			//Set identifiers for all occurrences
			$identifierArr = $this->getIdentifiers(implode(',',array_keys($occurrenceArr)));
			foreach($identifierArr as $occid => $iArr){
				$occurrenceArr[$occid]['identifiers'] = $iArr;
			}
			//Iterate through occurrences and merge addtional identifiers and otherCatalogNumbers field values
			foreach($occurrenceArr as $occid => $occurArr){
				$otherCatNumArr = array();
				$trimmableOccurArr = $occurArr['othercatalognumbers'] ?? "";
				if($ocnStr = trim($trimmableOccurArr,',;| ')){
					$ocnStr = str_replace(array(',',';'),'|',$ocnStr);
					$ocnArr = explode('|',$ocnStr);
					foreach($ocnArr as $identUnit){
						$trimmableIdentUnit = $identUnit ?? "";
						$unitArr = explode(':',trim($trimmableIdentUnit,': '));
						$safeUnitArr = $unitArr ?? array();
						$tag = '';
						$trimmableShiftedUnitArr = array_shift($safeUnitArr) ?? "";
						if(count($safeUnitArr) > 1) $tag = trim($trimmableShiftedUnitArr);
						$value = trim(implode(', ',$safeUnitArr));
						$otherCatNumArr[$value] = $tag;
					}
				}
				if(isset($occurArr['identifiers'])){
					//Remove otherCatalogNumber values that are already within the omoccuridentifiers
					foreach($occurArr['identifiers'] as $idKey => $idArr){
						$idName = $idArr['name'];
						$idValue = $idArr['value'];
						if(array_key_exists($idValue, $otherCatNumArr)){
							if(!$idName && $otherCatNumArr[$idValue]) $occurrenceArr[$occid]['identifiers'][$idKey]['name'] = $otherCatNumArr[$idValue];
							unset($otherCatNumArr[$idValue]);
						}
					}
				}
				$newCnt = 0;
				foreach($otherCatNumArr as $newValue => $newTag){
					$occurrenceArr[$occid]['identifiers']['ocnid-'.$newCnt]['value'] = $newValue;
					$occurrenceArr[$occid]['identifiers']['ocnid-'.$newCnt]['name'] = $newTag;
					$newCnt++;
				}
			}
			foreach($occurrenceArr as $occid => $occurArr){
				if(isset($occurArr['identifiers'])){
					$idStr = '';
					foreach($occurArr['identifiers'] as $idValueArr){
						if($idValueArr['name']) $idStr .= $idValueArr['name'].': ';
						$idStr .= $idValueArr['value'].', ';
					}
					$occurrenceArr[$occid]['othercatalognumbers'] = trim($idStr,', ');
				}
			}
		}
	}

	public function getLoanData(){
		$retArr = array();
		if($this->occid){
			$sql = 'SELECT l.loanid, l.datedue, i.institutioncode '.
					'FROM omoccurloanslink ll INNER JOIN omoccurloans l ON ll.loanid = l.loanid '.
					'INNER JOIN institutions i ON l.iidBorrower = i.iid '.
					'WHERE ll.returndate IS NULL AND l.dateclosed IS NULL AND occid = '.$this->occid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['id'] = $r->loanid;
				$retArr['date'] = $r->datedue;
				$retArr['code'] = $r->institutioncode;
			}
			$rs->free();
		}
		return $retArr;
	}

	private function setPaleoData(){
		if($this->paleoActivated){
			$sql = 'SELECT '.implode(',',$this->fieldArr['omoccurpaleo']).' FROM omoccurpaleo WHERE occid = '.$this->occid;
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_assoc()){
				foreach($this->fieldArr['omoccurpaleo'] as $term){
					$this->occurrenceMap[$this->occid][$term] = $r[$term];
				}
			}
			$rs->free();
		}
	}

	public function getExsiccati(){
		$retArr = array();
		if(isset($GLOBALS['ACTIVATE_EXSICCATI']) && $GLOBALS['ACTIVATE_EXSICCATI'] && $this->occid){
			$sql = 'SELECT l.notes, l.ranking, l.omenid, n.exsnumber, t.ometid, t.title, t.abbreviation, t.editor '.
					'FROM omexsiccatiocclink l INNER JOIN omexsiccatinumbers n ON l.omenid = n.omenid '.
					'INNER JOIN omexsiccatititles t ON n.ometid = t.ometid '.
					'WHERE l.occid = '.$this->occid;
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retArr['ometid'] = $r->ometid;
				$retArr['exstitle'] = $r->title.($r->abbreviation?' ['.$r->abbreviation.']':'');
				$retArr['exsnumber'] = $r->exsnumber;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getExsiccatiTitleArr(){
		$retArr = array();
		$sql = 'SELECT ometid, title, abbreviation FROM omexsiccatititles ORDER BY title ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()) {
			$retArr[$r->ometid] = $this->cleanOutStr($r->title.($r->abbreviation?' ['.$r->abbreviation.']':''));
		}
		return $retArr;
	}

	//Permission functions
	public function getPermission(){
		//0 = not editor, 1 = admin, 2 = editor, 3 = taxon editor, 4 = crowdsource editor or collection allows public edits
		//If not editor, edits will be submitted to omoccuredits table but not applied to omoccurrences
		$isEditor = 0;
		$userRights = $GLOBALS['USER_RIGHTS'];
		if($GLOBALS['IS_ADMIN']){
			$isEditor = 1;
		}
		elseif(($this->collid && array_key_exists('CollAdmin', $userRights) && in_array($this->collid, $userRights['CollAdmin']))){
			$isEditor = 1;
		}
		else{
			if($this->collMap['colltype'] == 'General Observations'){
				if(!$this->occid && array_key_exists('CollEditor', $userRights) && in_array($this->collid, $userRights['CollEditor'])){
					//Approved General Observation editors can add records
					$isEditor = 2;
				}
				elseif($action){
					//Lets assume that Edits where submitted and they remain on same specimen, user is still approved
					$isEditor = 2;
				}
				elseif($occManager->getObserverUid() == $SYMB_UID){
					//Users can edit their own records
					$isEditor = 2;
				}
			}
			elseif(array_key_exists('CollEditor',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollEditor'])){
				//Is an assigned editor for this collection
				$isEditor = 2;
			}
			elseif($this->crowdSourceMode && $occManager->isCrowdsourceEditor()){
				//Is a crowdsourcing editor (CS status is open (=0) or CS status is pending (=5) and active user was original editor
				$isEditor = 4;
			}
			elseif($collMap && $collMap['publicedits']){
				//Collection is set as allowing public edits
				$isEditor = 4;
			}
			elseif(array_key_exists('CollTaxon',$USER_RIGHTS) && $occId){
				//Check to see if this user is authorized to edit this occurrence given their taxonomic editing authority
				$isEditor = $this->isTaxonomicEditor();
			}
		}
		return $isEditor;
	}

	/*
	 * Return: 0 = false, 2 = full editor, 3 = taxon editor, but not for this collection
	 */
	private function isTaxonomicEditor(){
		global $USER_RIGHTS;
		$isEditor = 0;

		//Get list of userTaxonomyIds that user has been approved for this collection
		$udIdArr = array();
		if(array_key_exists('CollTaxon',$USER_RIGHTS)){
			foreach($USER_RIGHTS['CollTaxon'] as $vStr){
				$tok = explode(':',$vStr);
				if($tok[0] == $this->collId){
					//Collect only userTaxonomyIds that are relevant to current collid
					$udIdArr[] = $tok[1];
				}
			}
		}
		//Grab taxonomic node id and geographic scopes
		$editTidArr = array();
		$sqlut = 'SELECT idusertaxonomy, tid, geographicscope
			FROM usertaxonomy
			WHERE editorstatus = "OccurrenceEditor" AND uid = '.$GLOBALS['SYMB_UID'];
		//echo $sqlut;
		$rsut = $this->conn->query($sqlut);
		while($rut = $rsut->fetch_object()){
			if(in_array('all',$udIdArr) || in_array($rut->idusertaxonomy,$udIdArr)){
				//Is an approved editor for given collection
				$editTidArr[2][$rut->tid] = $rut->geographicscope;
			}
			else{
				//Is a taxonomic editor, but not explicitly approved for this collection
				$editTidArr[3][$rut->tid] = $rut->geographicscope;
			}
		}
		$rsut->free();
		//Get relevant tids for active occurrence
		if($editTidArr){
			$occTidArr = array();
			$tid = 0;
			$sciname = '';
			$family = '';
			if($this->occurrenceMap && $this->occurrenceMap['tidinterpreted']){
				$tid = $this->occurrenceMap['tidinterpreted'];
				$sciname = $this->occurrenceMap['sciname'];
				$family = $this->occurrenceMap['family'];
			}
			//Get relevant tids
			if($tid){
				$occTidArr[] = $tid;
				$rs2 = $this->conn->query('SELECT parenttid FROM taxaenumtree WHERE (taxauthid = 1) AND (tid = '.$tid.')');
				while($r2 = $rs2->fetch_object()){
					$occTidArr[] = $r2->parenttid;
				}
				$rs2->free();
			}
			elseif($sciname || $family){
				//Get all relevant tids within the taxonomy hierarchy
				$sqlWhere = '';
				if($sciname){
					//Try to isolate genus
					$taxon = $sciname;
					$tok = explode(' ',$sciname);
					if(count($tok) > 1){
						if(strlen($tok[0]) > 2) $taxon = $tok[0];
					}
					$sqlWhere .= '(t.sciname = "'.$this->cleanInStr($taxon).'") ';
				}
				elseif($family){
					$sqlWhere .= '(t.sciname = "'.$this->cleanInStr($family).'") ';
				}
				if($sqlWhere){
					$sql2 = 'SELECT e.parenttid FROM taxaenumtree e INNER JOIN taxa t ON e.tid = t.tid WHERE e.taxauthid = 1 AND ('.$sqlWhere.')';
					//echo $sql2;
					$rs2 = $this->conn->query($sql2);
					while($r2 = $rs2->fetch_object()){
						$occTidArr[] = $r2->parenttid;
					}
					$rs2->free();
				}
			}
			if($occTidArr){
				//Check to see if approved tids have overlap
				if(array_key_exists(2,$editTidArr) && array_intersect(array_keys($editTidArr[2]),$occTidArr)){
					$isEditor = 2;
					//TODO: check to see if specimen is within geographic scope
				}
				//If not, check to see if unapproved tids have overlap (e.g. taxon editor, but w/o explicit rights
				if(!$isEditor){
					if(array_key_exists(3,$editTidArr) && array_intersect(array_keys($editTidArr[3]),$occTidArr)){
						$isEditor = 3;
						//TODO: check to see if specimen is within geographic scope
					}
				}
			}
		}
		return $isEditor;
	}

	//Data and variable functions
	protected function setCollMap(){
		if(!$this->collMap){
			if(!$this->collId && $this->occid) $this->setCollectionIdentifier();
			if($this->collId){
				$sql = 'SELECT collid, collectionname, institutioncode, collectioncode, colltype, managementtype, publicedits, dynamicproperties
					FROM omcollections
					WHERE (collid = '.$this->collid.')';
				$rs = $this->conn->query($sql);
				if($row = $rs->fetch_assoc()){
					$this->collMap = array_change_key_case($row);
					$this->collMap['collectionname'] = $this->cleanOutStr($this->collMap['collectionname']);
				}
				$rs->free();
			}
		}
	}

	public function getDynamicPropertiesArr(){
		$retArr = array();
		$propArr = array();
		if(array_key_exists('dynamicproperties', $this->collMap)){
			$propArr = json_decode($this->collMap['dynamicproperties'],true);
			if(isset($propArr['editorProps'])){
				$retArr = $propArr['editorProps'];
				if(isset($retArr['modules-panel'])){
					foreach($retArr['modules-panel'] as $module){
						if(isset($module['paleo']['status']) && $module['paleo']['status']){
							$this->paleoActivated = true;
						}
					}
				}
			}
		}
		return $retArr;
	}

	private function setCollectionIdentifier(){
		if($this->occid){
			if($this->collId===false){
				$sql = 'SELECT collid, observeruid FROM omoccurrences WHERE occid = '.$this->occid;
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$this->collid = $r->collid;
				}
				$rs->free();
			}
		}
	}

	protected function getIdentifiers($occidStr){
		$retArr = array();
		if($occidStr){
			$sql = 'SELECT occid, idomoccuridentifiers, identifierName, identifierValue FROM omoccuridentifiers WHERE occid IN('.$occidStr.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->occid][$r->idomoccuridentifiers]['name'] = $r->identifierName;
				$retArr[$r->occid][$r->idomoccuridentifiers]['value'] = $r->identifierValue;
			}
			$rs->free();
		}
		return $retArr;
	}

	//Setters and getters
	public function setOccid($id){
		if(is_numeric($id)) $this->occid = $id;
	}

	public function setCollid($collid){
		if(is_numeric($collid)) $this->collid = $collid;
	}

	public function getCollMap(){
		if(!$this->collMap) $this->setCollMap();
		return $this->collMap;
	}

	public function setCrowdSourceMode($m){
		if(is_numeric($m)) $this->crowdSourceMode = $m;
	}

	//Misc functions
	protected function cleanOutArr(&$arr){
		foreach($arr as $k => $v){
			if(is_array($v)) $this->cleanOutArr($arr[$k]);
			else $arr[$k] = $this->cleanOutStr($v);
		}
	}
}
?>