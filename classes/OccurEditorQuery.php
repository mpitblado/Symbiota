<?php
include_once($SERVER_ROOT.'/classes/OccurrenceDuplicate.php');

class OccurEditorQuery extends OccurEditorBase {

	private $occIndex = 0;
	private $direction = '';
	private $occidIndexArr = array();

	private $sqlWhere;
	private $qryArr = array();
	private $catNumIsNum;
	private $otherCatNumIsNum = false;
	protected $errorArr = array();

	public function __construct($conn = null){
		parent::__construct(null, 'write', $conn);
	}

	public function __destruct(){
		parent::__destruct();
	}

	//Query functions
	public function setQueryVariables($overrideQry = false){
		if($overrideQry){
			$this->qryArr = $overrideQry;
			unset($_SESSION['editorquery']);
		}
		elseif(array_key_exists('q_catalognumber',$_REQUEST) || array_key_exists('reset',$_REQUEST)){
			if(array_key_exists('q_catalognumber',$_REQUEST) && $_REQUEST['q_catalognumber']) $this->qryArr['cn'] = trim($_REQUEST['q_catalognumber']);
			if(array_key_exists('q_othercatalognumbers',$_REQUEST) && $_REQUEST['q_othercatalognumbers']) $this->qryArr['ocn'] = trim($_REQUEST['q_othercatalognumbers']);
			if(array_key_exists('q_recordedby',$_REQUEST) && $_REQUEST['q_recordedby']) $this->qryArr['rb'] = trim($_REQUEST['q_recordedby']);
			if(array_key_exists('q_recordnumber',$_REQUEST) && $_REQUEST['q_recordnumber']) $this->qryArr['rn'] = trim($_REQUEST['q_recordnumber']);
			if(array_key_exists('q_eventdate',$_REQUEST) && $_REQUEST['q_eventdate']) $this->qryArr['ed'] = trim($_REQUEST['q_eventdate']);
			if(array_key_exists('q_recordenteredby',$_REQUEST) && $_REQUEST['q_recordenteredby']) $this->qryArr['eb'] = trim($_REQUEST['q_recordenteredby']);
			if(array_key_exists('q_returnall',$_REQUEST) && is_numeric($_REQUEST['q_returnall'])) $this->qryArr['returnall'] = $_REQUEST['q_returnall'];
			if(array_key_exists('q_processingstatus',$_REQUEST) && $_REQUEST['q_processingstatus']) $this->qryArr['ps'] = trim($_REQUEST['q_processingstatus']);
			if(array_key_exists('q_datelastmodified',$_REQUEST) && $_REQUEST['q_datelastmodified']) $this->qryArr['dm'] = trim($_REQUEST['q_datelastmodified']);
			if(array_key_exists('q_exsiccatiid',$_REQUEST) && is_numeric($_REQUEST['q_exsiccatiid'])) $this->qryArr['exsid'] = $_REQUEST['q_exsiccatiid'];
			if(array_key_exists('q_dateentered',$_REQUEST) && $_REQUEST['q_dateentered']) $this->qryArr['de'] = trim($_REQUEST['q_dateentered']);
			if(array_key_exists('q_ocrfrag',$_REQUEST) && $_REQUEST['q_ocrfrag']) $this->qryArr['ocr'] = trim($_REQUEST['q_ocrfrag']);
			if(array_key_exists('q_imgonly',$_REQUEST) && $_REQUEST['q_imgonly']) $this->qryArr['io'] = 1;
			if(array_key_exists('q_withoutimg',$_REQUEST) && $_REQUEST['q_withoutimg']) $this->qryArr['woi'] = 1;
			for($x=1; $x<9; $x++){
				if(array_key_exists('q_customandor'.$x,$_REQUEST) && $_REQUEST['q_customandor'.$x]) $this->qryArr['cao'.$x] = $_REQUEST['q_customandor'.$x];
                if(array_key_exists('q_customopenparen'.$x,$_REQUEST) && $_REQUEST['q_customopenparen'.$x]) $this->qryArr['cop'.$x] = $_REQUEST['q_customopenparen'.$x];
				if(array_key_exists('q_customfield'.$x,$_REQUEST) && $_REQUEST['q_customfield'.$x]) $this->qryArr['cf'.$x] = $_REQUEST['q_customfield'.$x];
				if(array_key_exists('q_customtype'.$x,$_REQUEST) && $_REQUEST['q_customtype'.$x]) $this->qryArr['ct'.$x] = $_REQUEST['q_customtype'.$x];
				if(array_key_exists('q_customvalue'.$x,$_REQUEST)) $this->qryArr['cv'.$x] = trim($_REQUEST['q_customvalue'.$x]);
				if(array_key_exists('q_customcloseparen'.$x,$_REQUEST) && $_REQUEST['q_customcloseparen'.$x]) $this->qryArr['ccp'.$x] = $_REQUEST['q_customcloseparen'.$x];
			}
			if(array_key_exists('orderby',$_REQUEST)) $this->qryArr['orderby'] = trim($_REQUEST['orderby']);
			if(array_key_exists('orderbydir',$_REQUEST)) $this->qryArr['orderbydir'] = trim($_REQUEST['orderbydir']);

			if(array_key_exists('occidlist',$_POST) && $_POST['occidlist']) $this->setOccidIndexArr($_POST['occidlist']);
			if(array_key_exists('direction',$_POST)) $this->direction = trim($_POST['direction']);
			unset($_SESSION['editorquery']);
		}
		elseif(isset($_SESSION['editorquery'])){
			$this->qryArr = json_decode($_SESSION['editorquery'],true);
		}
		$this->setSqlWhere();
	}

	private function setSqlWhere(){
		$this->setCollMap();
		if ($this->qryArr==null) {
			// supress warnings on array_key_exists(key,null) calls below
			$this->qryArr=array();
		}
		$sqlWhere = '';
		$this->catNumIsNum = false;
		if(array_key_exists('cn',$this->qryArr)){
			$idTerm = $this->qryArr['cn'];
			if(strtolower($idTerm) == 'is null'){
				$sqlWhere .= 'AND (o.catalognumber IS NULL) ';
			}
			else{
				$isOccid = false;
				if(substr($idTerm,0,5) == 'occid'){
					$idTerm = trim(substr($idTerm,5));
					$isOccid = true;
				}
				$iArr = explode(',',$idTerm);
				$iBetweenFrag = array();
				$iInFrag = array();
				foreach($iArr as $v){
					$v = trim($v);
					if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$v)){
						//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
						$v = str_ireplace(array('>',' and ','<'),array('',' - ',''),$v);
					}
					if($p = strpos($v,' - ')){
						$term1 = $this->cleanInStr(substr($v,0,$p));
						$term2 = $this->cleanInStr(substr($v,$p+3));
						if(is_numeric($term1) && is_numeric($term2)){
							$this->catNumIsNum = true;
							if($isOccid){
								$iBetweenFrag[] = '(o.occid BETWEEN '.$term1.' AND '.$term2.')';
							}
							else{
								$iBetweenFrag[] = '(o.catalogNumber BETWEEN '.$term1.' AND '.$term2.')';
							}
						}
						else{
							$catTerm = 'o.catalogNumber BETWEEN "'.$term1.'" AND "'.$term2.'"';
							if(strlen($term1) == strlen($term2)) $catTerm .= ' AND length(o.catalogNumber) = '.strlen($term2);
							$iBetweenFrag[] = '('.$catTerm.')';
						}
					}
					else{
						$vStr = $this->cleanInStr($v);
						if(is_numeric($vStr)){
							if($iInFrag){
								//Only tag as numeric if there are more than one term (if not, it doesn't match what the sort order is)
								$this->catNumIsNum = true;
							}
							if(substr($vStr,0,1) == '0'){
								//Add value with left padded zeros removed
								$iInFrag[] = ltrim($vStr,0);
							}
						}
						$iInFrag[] = $vStr;
					}
				}
				$iWhere = '';
				if($iBetweenFrag){
					$iWhere .= 'OR '.implode(' OR ',$iBetweenFrag);
				}
				if($iInFrag){
					if($isOccid){
						foreach($iInFrag as $term){
							if(substr($term,0,1) == '<' || substr($term,0,1) == '>'){
								$iWhere .= 'OR (o.occid '.substr($term,0,1).' '.trim(substr($term,1)).') ';
							}
							else{
								$iWhere .= 'OR (o.occid = '.$term.') ';
							}
						}
					}
					else{
						foreach($iInFrag as $term){
							if(substr($term,0,1) == '<' || substr($term,0,1) == '>'){
								$tStr = trim(substr($term,1));
								if(!is_numeric($tStr)) $tStr = '"'.$tStr.'"';
								$iWhere .= 'OR (o.catalognumber '.substr($term,0,1).' '.$tStr.') ';
							}
							elseif(strpos($term,'%')){
								$iWhere .= 'OR (o.catalognumber LIKE "'.$term.'") ';
							}
							else{
								$iWhere .= 'OR (o.catalognumber = "'.$term.'") ';
							}
						}
					}
				}
				$sqlWhere .= 'AND ('.substr($iWhere,3).') ';
			}
		}
		//otherCatalogNumbers
		$this->otherCatNumIsNum = false;
		if(array_key_exists('ocn',$this->qryArr)){
			if(strtolower($this->qryArr['ocn']) == 'is null'){
				$sqlWhere .= 'AND (o.othercatalognumbers IS NULL) AND (id.identifierValue IS NULL) ';
			}
			else{
				$ocnArr = explode(',',$this->qryArr['ocn']);
				$ocnBetweenFrag = array();
				$ocnInFrag = array();
				foreach($ocnArr as $v){
					$v = $this->cleanInStr($v);
					if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$v)){
						//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
						$v = str_ireplace(array('>',' and ','<'),array('',' - ',''),$v);
					}
					if(strpos('%',$v) !== false){
						$ocnBetweenFrag[] = '((o.othercatalognumbers LIKE "'.$v.'") OR (id.identifierValue LIKE "'.$v.'"))';
					}
					elseif($p = strpos($v,' - ')){
						$term1 = trim(substr($v,0,$p));
						$term2 = trim(substr($v,$p+3));
						if(is_numeric($term1) && is_numeric($term2)){
							$this->otherCatNumIsNum = true;
							$ocnBetweenFrag[] = '((o.othercatalognumbers BETWEEN '.$term1.' AND '.$term2.') OR (id.identifierValue BETWEEN '.$term1.' AND '.$term2.'))';
						}
						else{
							$ocnTerm = '(o.othercatalognumbers BETWEEN "'.$term1.'" AND "'.$term2.'"';
							if(strlen($term1) == strlen($term2)) $ocnTerm .= ' AND length(o.othercatalognumbers) = '.strlen($term2);
							$ocnTerm .= ') OR (id.identifierValue BETWEEN "'.$term1.'" AND "'.$term2.'"';
							if(strlen($term1) == strlen($term2)) $ocnTerm .= ' AND length(id.identifierValue) = '.strlen($term2);
							$ocnTerm .= ')';
							$ocnBetweenFrag[] = '('.$ocnTerm.')';
						}
					}
					else{
						$ocnInFrag[] = $v;
						if(is_numeric($v)){
							$this->otherCatNumIsNum = true;
							if(substr($v,0,1) == '0'){
								//Add value with left padded zeros removed
								$ocnInFrag[] = ltrim($v,0);
							}
						}
					}
				}
				$ocnWhere = '';
				if($ocnBetweenFrag){
					$ocnWhere .= 'OR '.implode(' OR ',$ocnBetweenFrag);
				}
				if($ocnInFrag){
					foreach($ocnInFrag as $term){
						if(substr($term,0,1) == '<' || substr($term,0,1) == '>'){
							$tStr = trim(substr($term,1));
							if(!is_numeric($tStr)) $tStr = '"'.$tStr.'"';
							$ocnWhere .= 'OR (o.othercatalognumbers '.substr($term,0,1).' '.$tStr.') OR (id.identifierValue '.substr($term,0,1).' '.$tStr.') ';
						}
						elseif(strpos($term,'%') !== false){
							$ocnWhere .= 'OR (o.othercatalognumbers LIKE "'.$term.'") OR (id.identifierValue LIKE "'.$term.'") ';
						}
						else{
							$ocnWhere .= 'OR (o.othercatalognumbers = "'.$term.'") OR (id.identifierValue = "'.$term.'") ';
						}
					}
				}
				$sqlWhere .= 'AND ('.substr($ocnWhere,3).') ';
			}
		}
		//recordNumber: collector's number
		if(array_key_exists('rn',$this->qryArr)){
			if(strtolower($this->qryArr['rn']) == 'is null'){
				$sqlWhere .= 'AND (o.recordnumber IS NULL) ';
			}
			else{
				$rnArr = explode(',',$this->qryArr['rn']);
				$rnBetweenFrag = array();
				$rnInFrag = array();
				foreach($rnArr as $v){
					$v = $this->cleanInStr($v);
					if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$v)){
						//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
						$v = str_ireplace(array('>',' and ','<'),array('',' - ',''),$v);
					}
					if($p = strpos($v,' - ')){
						$term1 = trim(substr($v,0,$p));
						$term2 = trim(substr($v,$p+3));
						if(is_numeric($term1) && is_numeric($term2)){
							$rnBetweenFrag[] = '(o.recordnumber BETWEEN '.$term1.' AND '.$term2.')';
						}
						else{
							$catTerm = 'o.recordnumber BETWEEN "'.$term1.'" AND "'.$term2.'"';
							if(strlen($term1) == strlen($term2)) $catTerm .= ' AND length(o.recordnumber) = '.strlen($term2);
							$rnBetweenFrag[] = '('.$catTerm.')';
						}
					}
					else{
						$condStr = '=';
						if(substr($v,0,1) == '<' || substr($v,0,1) == '>'){
							$condStr = substr($v,0,1);
							$v = trim(substr($v,1));
						}
						if(is_numeric($v)){
							$rnInFrag[] = $condStr.' '.$v;
						}
						else{
							$rnInFrag[] = $condStr.' "'.$v.'"';
						}
					}
				}
				$rnWhere = '';
				if($rnBetweenFrag){
					$rnWhere .= 'OR '.implode(' OR ',$rnBetweenFrag);
				}
				if($rnInFrag){
					foreach($rnInFrag as $term){
						$rnWhere .= 'OR (o.recordnumber '.$term.') ';
					}
				}
				$sqlWhere .= 'AND ('.substr($rnWhere,3).') ';
			}
		}
		//recordedBy: collector
		if(array_key_exists('rb',$this->qryArr)){
			if(strtolower($this->qryArr['rb']) == 'is null'){
				$sqlWhere .= 'AND (o.recordedby IS NULL) ';
			}
			elseif(substr($this->qryArr['rb'],0,1) == '%'){
				$collStr = $this->cleanInStr(substr($this->qryArr['rb'],1));
				if(strlen($collStr) < 4 || in_array(strtolower($collStr),array('best','little'))){
					//Need to avoid FULLTEXT stopwords interfering with return
					$sqlWhere .= 'AND (o.recordedby LIKE "%'.$collStr.'%") ';
				}
				else{
					$sqlWhere .= 'AND (MATCH(f.recordedby) AGAINST("'.$collStr.'")) ';
				}
			}
			else{
				$sqlWhere .= 'AND (o.recordedby LIKE "'.$this->cleanInStr($this->qryArr['rb']).'%") ';
			}
		}
		//eventDate: collection date
		if(array_key_exists('ed',$this->qryArr)){
			if(strtolower($this->qryArr['ed']) == 'is null'){
				$sqlWhere .= 'AND (o.eventdate IS NULL) ';
			}
			else{
				$edv = $this->cleanInStr($this->qryArr['ed']);
				if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$edv)){
					//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
					$edv = str_ireplace(array('>',' and ','<'),array('',' - ',''),$edv);
				}
				$edv = str_replace(' to ',' - ',$edv);
				if($p = strpos($edv,' - ')){
					$sqlWhere .= 'AND (o.eventdate BETWEEN "'.trim(substr($edv,0,$p)).'" AND "'.trim(substr($edv,$p+3)).'") ';
				}
				elseif(substr($edv,0,1) == '<' || substr($edv,0,1) == '>'){
					$sqlWhere .= 'AND (o.eventdate '.substr($edv,0,1).' "'.trim(substr($edv,1)).'") ';
				}
				else{
					$sqlWhere .= 'AND (o.eventdate = "'.$edv.'") ';
				}
			}
		}
		if(array_key_exists('eb',$this->qryArr)){
			if(strtolower($this->qryArr['eb']) == 'is null'){
				$sqlWhere .= 'AND (o.recordEnteredBy IS NULL) ';
			}
			else{
				$sqlWhere .= 'AND (o.recordEnteredBy = "'.$this->cleanInStr($this->qryArr['eb']).'") ';
			}
		}
		if(array_key_exists('de',$this->qryArr)){
			$de = $this->cleanInStr($this->qryArr['de']);
			if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$de)){
				//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
				$de = str_ireplace(array('>',' and ','<'),array('',' - ',''),$de);
			}
			$de = str_replace(' to ',' - ',$de);
			if($p = strpos($de,' - ')){
				$sqlWhere .= 'AND (DATE(o.dateentered) BETWEEN "'.trim(substr($de,0,$p)).'" AND "'.trim(substr($de,$p+3)).'") ';
			}
			elseif(substr($de,0,1) == '<' || substr($de,0,1) == '>'){
				$sqlWhere .= 'AND (o.dateentered '.substr($de,0,1).' "'.trim(substr($de,1)).'") ';
			}
			else{
				$sqlWhere .= 'AND (DATE(o.dateentered) = "'.$de.'") ';
			}
		}
		if(array_key_exists('dm',$this->qryArr)){
			$dm = $this->cleanInStr($this->qryArr['dm']);
			if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$dm)){
				//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
				$dm = str_ireplace(array('>',' and ','<'),array('',' - ',''),$dm);
			}
			$dm = str_replace(' to ',' - ',$dm);
			if($p = strpos($dm,' - ')){
				$sqlWhere .= 'AND (DATE(o.datelastmodified) BETWEEN "'.trim(substr($dm,0,$p)).'" AND "'.trim(substr($dm,$p+3)).'") ';
			}
			elseif(substr($dm,0,1) == '<' || substr($dm,0,1) == '>'){
				$sqlWhere .= 'AND (o.datelastmodified '.substr($dm,0,1).' "'.trim(substr($dm,1)).'") ';
			}
			else{
				$sqlWhere .= 'AND (DATE(o.datelastmodified) = "'.$dm.'") ';
			}
		}
		//Processing status
		if(array_key_exists('ps',$this->qryArr)){
			if($this->qryArr['ps'] == 'isnull'){
				$sqlWhere .= 'AND (o.processingstatus IS NULL) ';
			}
			else{
				$sqlWhere .= 'AND (o.processingstatus = "'.$this->cleanInStr($this->qryArr['ps']).'") ';
			}
		}
		//Without images
		if(array_key_exists('woi',$this->qryArr)){
			$sqlWhere .= 'AND (i.imgid IS NULL) ';
		}
		//OCR
		if(array_key_exists('ocr',$this->qryArr)){
			//Used when OCR frag comes from set field within queryformcrowdsourcing
			$sqlWhere .= 'AND (ocr.rawstr LIKE "%'.$this->cleanInStr($this->qryArr['ocr']).'%") ';
		}
		//Exsiccati ID
		if(array_key_exists('exsid',$this->qryArr) && is_numeric($this->qryArr['exsid'])){
			//Used to find records linked to a specific exsiccati
			$sqlWhere .= 'AND (exn.ometid = '.$this->qryArr['exsid'].') ';
		}
		//Custom search fields
		$customWhere = '';
		for($x=1; $x<9; $x++){
			$cao = (array_key_exists('cao'.$x,$this->qryArr)?$this->cleanInStr($this->qryArr['cao'.$x]):'');
            $cop = (array_key_exists('cop'.$x,$this->qryArr)?$this->cleanInStr($this->qryArr['cop'.$x]):'');
			$customField = (array_key_exists('cf'.$x,$this->qryArr)?$this->cleanInStr($this->qryArr['cf'.$x]):'');
			$customTerm = (array_key_exists('ct'.$x,$this->qryArr)?$this->cleanInStr($this->qryArr['ct'.$x]):'');
			$customValue = (array_key_exists('cv'.$x,$this->qryArr)?$this->cleanInStr($this->qryArr['cv'.$x]):'');
			$ccp = (array_key_exists('ccp'.$x,$this->qryArr)?$this->cleanInStr($this->qryArr['ccp'.$x]):'');
            if(!$cao) $cao = 'AND';
            if($customField){
            	if($customField == 'ocrFragment'){
					//Used when OCR frag comes from custom field search within basic query form
					$customField = 'ocr.rawstr';
				}
				elseif($customField == 'username'){
					//Used when Modified By comes from custom field search within basic query form
					$customField = 'u.username';
				}
				else{
					$customField = 'o.'.$customField;
				}
				if($customField == 'o.otherCatalogNumbers'){
					$customWhere .= $cao.' ('.substr($this->setCustomSqlFragment($customField, $customTerm, $customValue, $cao, $cop, $ccp),3).' ';
					if($customTerm != 'NOT_EQUALS' && $customTerm != 'NOT_LIKE'){
						$caoOverride = 'OR';
						if($customTerm == 'NULL') $caoOverride = 'AND';
						$customWhere .= $this->setCustomSqlFragment('id.identifierValue', $customTerm, $customValue, $caoOverride, $cop, $ccp);
					}
					else{
						$customWhere .= 'AND o.occid NOT IN(SELECT occid FROM omoccuridentifiers WHERE identifierValue ';
						if($customTerm == 'NOT_LIKE') $customWhere .= 'NOT_LIKE';
						else $customWhere .= '!=';
						$customWhere .= ' "'.$this->cleanInStr($customValue).'")';
					}
					$customWhere .= ') ';
				}
				else $customWhere .= $this->setCustomSqlFragment($customField, $customTerm, $customValue, $cao, $cop, $ccp);
			}
			elseif($x > 1 && !$customField && $ccp){
				$customWhere .= ' '.$ccp.' ';
    		}
		}
		if($customWhere) $sqlWhere .= 'AND ('.substr($customWhere,3).') ';
		if($this->crowdSourceMode){
			$sqlWhere .= 'AND (q.reviewstatus = 0) ';
		}
		if($this->collMap && $this->collMap['colltype'] == 'General Observations' && !isset($this->qryArr['returnall'])){
			//Ensure that General Observation projects edits are limited to active user
			$sqlWhere .= 'AND (o.observeruid = '.$GLOBALS['SYMB_UID'].') ';
		}
		if($this->collId) $sqlWhere .= 'AND (o.collid ='.$this->collId.') ';
		if($sqlWhere) $sqlWhere = 'WHERE '.substr($sqlWhere,4);
		$this->sqlWhere = $sqlWhere;
	}

	private function setCustomSqlFragment($customField, $customTerm, $customValue, $cao, $cop, $ccp){
		$sqlFrag = '';
		if($customTerm == 'NULL'){
			$sqlFrag .= $cao.($cop?' '.$cop:'').' ('.$customField.' IS NULL) '.($ccp?$ccp.' ':'');
		}
		elseif($customTerm == 'NOTNULL'){
			$sqlFrag .= $cao.($cop?' '.$cop:'').' ('.$customField.' IS NOT NULL) '.($ccp?$ccp.' ':'');
		}
		elseif($customTerm == 'NOT_EQUALS'){
			if(!is_numeric($customValue)) $customValue = '"'.$customValue.'"';
			$sqlFrag .= $cao.($cop?' '.$cop:'').' (('.$customField.' != '.$customValue.') OR ('.$customField.' IS NULL)) '.($ccp?$ccp.' ':'');
		}
		elseif($customTerm == 'GREATER'){
			if(!is_numeric($customValue)) $customValue = '"'.$customValue.'"';
			$sqlFrag .= $cao.($cop?' '.$cop:'').' ('.$customField.' > '.$customValue.') '.($ccp?$ccp.' ':'');
		}
		elseif($customTerm == 'LESS'){
			if(!is_numeric($customValue)) $customValue = '"'.$customValue.'"';
			$sqlFrag .= $cao.($cop?' '.$cop:'').' ('.$customField.' < '.$customValue.') '.($ccp?$ccp.' ':'');
		}
		elseif($customTerm == 'LIKE' && $customValue){
			$sqlFrag .= $cao.($cop?' '.$cop:'').' ('.$customField.' LIKE "%'.trim($customValue,'%').'%") '.($ccp?$ccp.' ':'');
		}
		elseif($customTerm == 'NOT_LIKE' && $customValue){
			$sqlFrag .= $cao.($cop?' '.$cop:'').' (('.$customField.' NOT LIKE "%'.trim($customValue,'%').'%") OR ('.$customField.' IS NULL)) '.($ccp?$ccp.' ':'');
		}
		elseif($customTerm == 'STARTS' && $customValue){
			$sqlFrag .= $cao.($cop?' '.$cop:'').' ('.$customField.' LIKE "'.trim($customValue,'%').'%") '.($ccp?$ccp.' ':'');
		}
		elseif($customValue){
			$sqlFrag .= $cao.($cop?' '.$cop:'').' ('.$customField.' = "'.$customValue.'") '.($ccp?$ccp.' ':'');
		}
		return $sqlFrag;
	}

	public function getQueryRecordCount($reset = 0){
		if(!$reset && array_key_exists('rc',$this->qryArr)) return $this->qryArr['rc'];
		$recCnt = false;
		if($this->sqlWhere){
			$sql = 'SELECT COUNT(DISTINCT o.occid) AS reccnt FROM omoccurrences o ';
			$this->addTableJoins($sql);
			$sql .= $this->sqlWhere;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$recCnt = $r->reccnt;
			}
			$rs->free();
			$this->qryArr['rc'] = (int)$recCnt;
			$_SESSION['editorquery'] = json_encode($this->qryArr);
		}
		return $recCnt;
	}

	public function getOccurMap($start = 0, $limit = 0){
		if(!is_numeric($start)) $start = 0;
		if(!is_numeric($limit)) $limit = 0;
		if(!$this->occurrenceMap){
			if($this->direction){
				$indexKey = array_search($this->occid, $this->occidIndexArr);
				$repeat = false;
				do{
					if($this->direction == 'forward'){
						if($indexKey !== false) $indexKey++;
						$this->occIndex++;
					}
					elseif($this->direction == 'back'){
						if($indexKey !== false) $indexKey--;
						$this->occIndex--;
					}
					if($indexKey !== false && array_key_exists($indexKey, $this->occidIndexArr)){
						$this->occid = $this->occidIndexArr[$indexKey];
					}
					else{
						$this->occid = 0;
						unset($this->occidIndexArr);
						$this->occidIndexArr = array();
					}
					$this->setOccurArr();
					if(!$this->occurrenceMap && $this->occid && $this->occidIndexArr){
						//echo 'skipping: '.$indexKey.':'.$this->occid.'<br/>';
						//occid no longer belongs within where query domain
						unset($this->occidIndexArr[$indexKey]);
						if($this->direction == 'forward'){
							$this->occIndex--;
						}
						$repeat = true;
					}
					else{
						$repeat = false;
					}
				}while($repeat);
			}
			else{
				$this->setOccurArr($start, $limit);
			}
		}
		return $this->occurrenceMap;
	}

	protected function setOccurArr($start = 0, $limit = 0){
		$retArr = Array();
		$localIndex = false;
		$sqlFrag = '';
		if($this->occid && !$this->direction){
			$sqlFrag .= 'WHERE (o.occid = '.$this->occid.')';
		}
		elseif($this->sqlWhere){
			$this->addTableJoins($sqlFrag);
			$sqlFrag .= $this->sqlWhere;
			if($limit){
				$this->setSqlOrderBy($sqlFrag);
				$sqlFrag .= 'LIMIT '.$start.','.$limit;
			}
			elseif($this->occid){
				$sqlFrag .= 'AND (o.occid = '.$this->occid.') ';
				$this->setSqlOrderBy($sqlFrag);
			}
			elseif(is_numeric($this->occIndex)){
				$this->setSqlOrderBy($sqlFrag);
				$localLimit = 500;
				$localStart = floor($this->occIndex/$localLimit)*$localLimit;
				$localIndex = $this->occIndex - $localStart;
				$sqlFrag .= 'LIMIT '.$localStart.','.$localLimit;
			}
		}
		if($sqlFrag){
			parent::setOccurArr($sqlFrag, $localIndex);
			if($this->occurrenceMap){
				if($this->direction && !$this->occidIndexArr){
					$this->occidIndexArr = array_keys($retArr);
				}
			}
		}
	}

	private function addTableJoins(&$sql){
		if(strpos($this->sqlWhere,'ocr.rawstr')){
			if(strpos($this->sqlWhere,'ocr.rawstr IS NULL') && array_key_exists('io',$this->qryArr)){
				$sql .= 'INNER JOIN images i ON o.occid = i.occid LEFT JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
			}
			elseif(strpos($this->sqlWhere,'ocr.rawstr IS NULL')){
				$sql .= 'LEFT JOIN images i ON o.occid = i.occid LEFT JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
			}
			else{
				$sql .= 'INNER JOIN images i ON o.occid = i.occid INNER JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
			}
		}
		elseif(array_key_exists('io',$this->qryArr)){
			$sql .= 'INNER JOIN images i ON o.occid = i.occid ';
		}
		elseif(array_key_exists('woi',$this->qryArr)){
			$sql .= 'LEFT JOIN images i ON o.occid = i.occid ';
		}
		if(strpos($this->sqlWhere,'id.identifierValue')){
			$sql .= 'LEFT JOIN omoccuridentifiers id ON o.occid = id.occid ';
		}
		if(strpos($this->sqlWhere,'u.username')){
			$sql .= 'LEFT JOIN omoccuredits ome ON o.occid = ome.occid LEFT JOIN users u ON ome.uid = u.uid ';
		}
		if(strpos($this->sqlWhere,'exn.ometid')){
			$sql .= 'INNER JOIN omexsiccatiocclink exocc ON o.occid = exocc.occid INNER JOIN omexsiccatinumbers exn ON exocc.omenid = exn.omenid ';
		}
		if(strpos($this->sqlWhere,'MATCH(f.recordedby)') || strpos($this->sqlWhere,'MATCH(f.locality)')){
			$sql.= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
		}
		if($this->crowdSourceMode){
			$sql .= 'INNER JOIN omcrowdsourcequeue q ON q.occid = o.occid ';
		}
	}

	private function setSqlOrderBy(&$sql){
		if(isset($this->qryArr['orderby'])){
			$sqlOrderBy = '';
			$orderBy = $this->cleanInStr($this->qryArr['orderby']);
			if($orderBy == 'catalognumber'){
				if($this->catNumIsNum){
					$sqlOrderBy = 'catalogNumber+1';
				}
				else{
					$sqlOrderBy = 'catalogNumber';
				}
			}
			elseif($orderBy == 'othercatalognumbers'){
				if($this->otherCatNumIsNum){
					$sqlOrderBy = 'othercatalognumbers+1';
				}
				else{
					$sqlOrderBy = 'othercatalognumbers';
				}
			}
			elseif($orderBy == 'recordnumber'){
				$sqlOrderBy = 'recordnumber+1';
			}
			else{
				$sqlOrderBy = $orderBy;
			}
			if($sqlOrderBy) $sql .= 'ORDER BY (o.'.$sqlOrderBy.') '.$this->qryArr['orderbydir'].' ';
		}
	}

	//Batch update functions
	public function batchUpdateField($fieldName, $oldValue, $newValue, $buMatch){
		global $LANG;
		$statusStr = '';
		$fn = $this->cleanInStr($fieldName);
		$ov = $this->conn->real_escape_string($oldValue);
		$nv = $this->conn->real_escape_string($newValue);
		if($fn && ($ov || $nv)){
			//Get occids (where statement can't be part of UPDATE query without error being thrown)
			$occidArr = array();
			$sqlOccid = 'SELECT DISTINCT o.occid FROM omoccurrences o ';
			$this->addTableJoins($sqlOccid);
			$sqlOccid .= $this->getBatchUpdateWhere($fn,$ov,$buMatch);
			//echo $sqlOccid.'<br/>';
			$rs = $this->conn->query($sqlOccid);
			while($r = $rs->fetch_object()){
				$occidArr[] = $r->occid;
			}
			$rs->free();
			//Batch update records
			if($occidArr){
				//Set full replace or replace fragment
				$nvSqlFrag = '';
				if(!$buMatch || $ov===''){
					$nvSqlFrag = ($nv===''?'NULL':'"'.trim($nv).'"');
				}
				else{
					//Selected "Match any part of field"
					$nvSqlFrag = 'REPLACE('.$fn.',"'.$ov.'","'.$nv.'")';
				}

				$sqlWhere = 'WHERE occid IN('.implode(',',$occidArr).')';
				//Add edits to the omoccuredit table
				$sql = 'INSERT INTO omoccuredits(occid,fieldName,fieldValueOld,fieldValueNew,appliedStatus,uid,editType) '.
					'SELECT occid, "'.$fn.'" AS fieldName, IFNULL('.$fn.',"") AS oldValue, IFNULL('.$nvSqlFrag.',"") AS newValue, '.
					'1 AS appliedStatus, '.$GLOBALS['SYMB_UID'].' AS uid, 1 FROM omoccurrences '.$sqlWhere;
				if(!$this->conn->query($sql)){
					$statusStr = $LANG['ERROR_ADDING_UPDATE'].': '.$this->conn->error;
				}
				//Apply edits to core tables
				if($this->paleoActivated && array_key_exists($fn, $this->fieldArr['omoccurpaleo'])){
					$sql = 'UPDATE omoccurpaleo SET '.$fn.' = '.$nvSqlFrag.' '.$sqlWhere;
				}
				else{
					$sql = 'UPDATE omoccurrences SET '.$fn.' = '.$nvSqlFrag.' '.$sqlWhere;
				}
				if(!$this->conn->query($sql)){
					$statusStr = $LANG['ERROR_APPLYING_BATCH_EDITS'].': '.$this->conn->error;
				}
			}
			else{
				$statusStr = $LANG['ERROR_BATCH_NO_RECORDS'];
			}
		}
		return $statusStr;
	}

	public function getBatchUpdateCount($fieldName,$oldValue,$buMatch){
		$retCnt = 0;

		$fn = $this->cleanInStr($fieldName);
		$ov = $this->conn->real_escape_string($oldValue);

		$sql = 'SELECT COUNT(DISTINCT o.occid) AS retcnt FROM omoccurrences o ';
		$this->addTableJoins($sql);
		$sql .= $this->getBatchUpdateWhere($fn,$ov,$buMatch);

		$result = $this->conn->query($sql);
		while ($row = $result->fetch_object()) {
			$retCnt = $row->retcnt;
		}
		$result->free();
		return $retCnt;
	}

	private function getBatchUpdateWhere($fn,$ov,$buMatch){
		$sql = $this->sqlWhere;

		if(!$buMatch || $ov===''){
			$sql .= ' AND (o.'.$fn.' '.($ov===''?'IS NULL':'= "'.$ov.'"').') ';
		}
		else{
			//Selected "Match any part of field"
			$sql .= ' AND (o.'.$fn.' LIKE "%'.$ov.'%") ';
		}
		return $sql;
	}

	public function getExternalEditArr(){
		$retArr = Array();
		$sql = 'SELECT r.orid, r.oldvalues, r.newvalues, r.externalsource, r.externaleditor, r.reviewstatus, r.appliedstatus, '.
			'CONCAT_WS(", ",u.lastname,u.firstname) AS username, r.externaltimestamp, r.initialtimestamp '.
			'FROM omoccurrevisions r LEFT JOIN users u ON r.uid = u.uid '.
			'WHERE (r.occid = '.$this->occid.') ORDER BY r.initialtimestamp DESC ';
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$editor = $r->externaleditor;
			if($r->username) $editor .= ' ('.$r->username.')';
			$retArr[$r->orid][$r->appliedstatus]['editor'] = $editor;
			$retArr[$r->orid][$r->appliedstatus]['source'] = $r->externalsource;
			$retArr[$r->orid][$r->appliedstatus]['reviewstatus'] = $r->reviewstatus;
			$retArr[$r->orid][$r->appliedstatus]['ts'] = $r->initialtimestamp;

			$oldValues = json_decode($r->oldvalues,true);
			$newValues = json_decode($r->newvalues,true);
			foreach($oldValues as $fieldName => $value){
				$retArr[$r->orid][$r->appliedstatus]['edits'][$fieldName]['old'] = $value;
				$retArr[$r->orid][$r->appliedstatus]['edits'][$fieldName]['new'] = (isset($newValues[$fieldName])?$newValues[$fieldName]:'ERROR');
			}
		}
		$rs->free();
		return $retArr;
	}

	//Misc data support functions
	public function getExsiccatiList(){
		$retArr = array();
		if($this->collId){
			$sql = 'SELECT DISTINCT t.ometid, t.title, t.abbreviation '.
				'FROM omexsiccatititles t INNER JOIN omexsiccatinumbers n ON t.ometid = n.ometid '.
				'INNER JOIN omexsiccatiocclink l ON n.omenid = l.omenid '.
				'INNER JOIN omoccurrences o ON l.occid = o.occid '.
				'WHERE (o.collid = '.$this->collId.') '.
				'ORDER BY t.title ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->ometid] = $r->title.($r->abbreviation?' ['.$r->abbreviation.']':'');
			}
			$rs->free();
		}
		return $retArr;
	}

	//Setters and getters
	public function setOccIndex($index){
		if(is_numeric($index)){
			$this->occIndex = $index;
		}
	}

	public function getOccIndex(){
		return $this->occIndex;
	}

	public function setDirection($cnt){
		if(is_numeric($cnt) && $cnt){
			$this->direction = $cnt;
		}
	}

	private function setOccidIndexArr($occidStr){
		if(preg_match('/^[,\d]+$/', $occidStr)){
			$this->occidIndexArr = explode(',',$occidStr);
		}
	}

	public function getOccidIndexStr(){
		return implode(',', $this->occidIndexArr);
	}

	public function getQueryVariables(){
		return $this->qryArr;
	}
}
?>
