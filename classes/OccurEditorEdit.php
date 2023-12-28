<?php
include_once($SERVER_ROOT.'/classes/OccurrenceDuplicate.php');
include_once($SERVER_ROOT.'/classes/UuidFactory.php');

class OccurEditorEdit extends OccurEditorBase {

	private $sqlWhere;

	protected $isPersonalManagement = false;	//e.g. General Observations and owned by user
	private $catNumIsNum;
	protected $errorArr = array();
	protected $isShareConn = false;

	private $paleoActivated = false;

	public function __construct($conn = null){
		parent::__construct(null, 'write', $conn);
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function editOccurrence($postArr, $editorStatus){
		global $USER_RIGHTS, $LANG;
		$status = '';
		if($this->occid && $editorStatus){
			$quickHostEntered = false;
			$autoCommit = false;
			if($editorStatus == 1 || $editorStatus == 2){
				//Is assigned admin or editor for collection
				$autoCommit = true;
			}
			elseif($editorStatus == 3){
				//Is a Taxon Editor, but without explicit rights to edit this occurrence
				$autoCommit = false;
			}
			elseif($editorStatus == 4){
				if($this->crowdSourceMode){
					//User can edit this crowdsource record
					$autoCommit = true;
				}
				else{
					//User does not have editing rights, but collection is open to public edits
					$autoCommit = false;
				}
			}
			//Processing edit
			$editedFields = trim($postArr['editedfields']);
			$editArr = array_unique(explode(';',$editedFields));
			foreach($editArr as $k => $fName){
				if(trim($fName) == 'host' || trim($fName) == 'hostassocid'){
					$quickHostEntered = true;
					unset($editArr[$k]);
				}
				if(!trim($fName)){
					unset($editArr[$k]);
				}
				else if(strcasecmp($fName, 'exstitle') == 0) {
					unset($editArr[$k]);
					$editArr[$k] = 'title';
				}
			}
			if($editArr || $quickHostEntered){
				$identArr = $this->getIdentifiers($this->occid);
				$oldValueArr = array();
				//Get current values to be saved within versioning tables
				$editFieldArr = array();
				$editFieldArr['omoccurrences'] = array_intersect($editArr,array_keys($this->fieldArr['omoccurrences']));
				if($editFieldArr['omoccurrences']){
					$sql = 'SELECT o.collid, '.implode(',',$editFieldArr['omoccurrences']).(in_array('processingstatus',$editFieldArr['omoccurrences'])?'':',processingstatus').
						(in_array('recordenteredby',$editFieldArr['omoccurrences'])?'':',recordenteredby').' FROM omoccurrences o WHERE o.occid = '.$this->occid;
					$rs = $this->conn->query($sql);
					$oldValueArr['omoccurrences'] = $rs->fetch_assoc();
					$rs->free();
				}
				//Get current paleo values to be saved within versioning tables
				$editFieldArr['omoccurpaleo'] = array_intersect($editArr, $this->fieldArr['omoccurpaleo']);
				if($this->paleoActivated && $editFieldArr['omoccurpaleo']){
					$sql = 'SELECT '.implode(',',$editFieldArr['omoccurpaleo']).' FROM omoccurpaleo WHERE occid = '.$this->occid;
					$rs = $this->conn->query($sql);
					if($rs->num_rows) $oldValueArr['omoccurpaleo'] = $rs->fetch_assoc();
					$rs->free();
				}
				//Get current identifiers values to be saved within versioning tables
				$editFieldArr['omoccuridentifiers'] = array_intersect($editArr, $this->fieldArr['omoccuridentifiers']);
				if($editFieldArr['omoccuridentifiers'] && $identArr){
					foreach($identArr[$this->occid] as $idKey => $idArr){
						$idStr = '';
						if($idArr['name']) $idStr = $idArr['name'].': ';
						$idStr .= $idArr['value'];
						$oldValueArr['omoccuridentifiers'][$idKey] = $idStr;
					}
				}
				//Get current exsiccati values to be saved within versioning tables
				$editFieldArr['omexsiccatiocclink'] = array_intersect($editArr, $this->fieldArr['omexsiccatiocclink']);
				if($editFieldArr['omexsiccatiocclink']){
					$sql = 'SELECT et.ometid, et.title, exsnumber '.
						'FROM omexsiccatiocclink el INNER JOIN omexsiccatinumbers en ON el.omenid = en.omenid '.
						'INNER JOIN omexsiccatititles et ON en.ometid = et.ometid '.
						'WHERE el.occid = '.$this->occid;
					$rs = $this->conn->query($sql);
					$oldValueArr['omexsiccatiocclink'] = $rs->fetch_assoc();
					$rs->free();
				}
				if($editArr){
					//Deal with scientific name changes if the AJAX code fails
					if(in_array('sciname',$editArr) && $postArr['sciname'] && !$postArr['tidinterpreted']){
						$sql2 = 'SELECT t.tid, t.author, ts.family '.
							'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
							'WHERE ts.taxauthid = 1 AND sciname = "'.$this->cleanInStr($postArr['sciname']).'"';
						$rs2 = $this->conn->query($sql2);
						if($r2 = $rs2->fetch_object()){
							$postArr['tidinterpreted'] = $r2->tid;
							if(!$postArr['scientificnameauthorship']) $postArr['scientificnameauthorship'] = $r2->author;
							if(!$postArr['family']) $postArr['family'] = $r2->family;
						}
						$rs2->free();
					}
					//If additional identifiers exist, NULL otherCatalogNumbers
					if($postArr['idvalue'][0]) $postArr['othercatalognumbers'] = '';

					//If processing status was "unprocessed" and recordEnteredBy is null, populate with user login
					$oldProcessingStatus = isset($oldValueArr['omoccurrences']['processingstatus'])?$oldValueArr['omoccurrences']['processingstatus']:'';
					$oldRecordEnteredBy = isset($oldValueArr['omoccurrences']['recordenteredby'])?$oldValueArr['omoccurrences']['recordenteredby']:'';
					if(!$oldRecordEnteredBy && ($oldProcessingStatus == 'unprocessed' || $oldProcessingStatus == 'stage 1')){
						$postArr['recordenteredby'] = $GLOBALS['USERNAME'];
						$editFieldArr['omoccurrences'][] = 'recordenteredby';
					}
					//Version edits; add edits to omoccuredits
					$sqlEditsBase = 'INSERT INTO omoccuredits(occid,reviewstatus,appliedstatus,uid,fieldname,fieldvaluenew,fieldvalueold) '.
						'VALUES ('.$this->occid.',1,'.($autoCommit?'1':'0').','.$GLOBALS['SYMB_UID'].',';
					foreach($editFieldArr as $tableName => $fieldArr){
						if($tableName == 'omoccuridentifiers'){
							if($fieldArr){
								foreach($postArr['idkey'] as $idIndex => $idKey){
									$newValue = $postArr['idname'][$idIndex].($postArr['idname'][$idIndex]?': ':'').$postArr['idvalue'][$idIndex];
									$oldValue = '';
									if(is_numeric($idKey)) $oldValue = $oldValueArr['omoccuridentifiers'][$idKey];
									if($oldValue != $newValue){
										$sqlEdit = $sqlEditsBase.'"omoccuridentifiers","'.$newValue.'","'.$oldValue.'")';
										if(!$this->conn->query($sqlEdit)){
											$this->errorArr[] = ''.$this->conn->error;
										}
									}
								}
							}
						}
						else{
							foreach($fieldArr as $fieldName){
								$prefix = $tableName.':';
								if($prefix == 'omoccurrences:') $prefix = '';
								if(!array_key_exists($fieldName,$postArr)){
									//Field is a checkbox that is unchecked: cultivationstatus, localitysecurity
									$postArr[$fieldName] = 0;
								}
								$newValue = $postArr[$fieldName];
								$oldValue = '';
								if(isset($oldValueArr[$tableName][$fieldName])) $oldValue = $oldValueArr[$tableName][$fieldName];
								//Version edits only if value has changed
								if($oldValue != $newValue){
									if($fieldName != 'tidinterpreted'){
										$sqlEdit = $sqlEditsBase.'"'.$prefix.$fieldName.'","'.$this->cleanInStr($newValue).'","'.$this->cleanInStr($oldValue).'")';
										if(!$this->conn->query($sqlEdit)){
											$this->errorArr[] = ''.$this->conn->error;
										}
									}
								}
							}
						}
					}
				}
				//Edit record only if user is authorized to autoCommit
				if($autoCommit){
					$status = $LANG['SUCCESS_EDITS_SUBMITTED'].' ';
					$sql = '';
					//Apply autoprocessing status if set
					if(array_key_exists('autoprocessingstatus',$postArr) && $postArr['autoprocessingstatus']){
						$postArr['processingstatus'] = $postArr['autoprocessingstatus'];
					}
					if($this->collMap){
						if(isset($postArr['institutioncode']) && $postArr['institutioncode'] == $this->collMap['institutioncode']) $postArr['institutioncode'] = '';
						if(isset($postArr['collectioncode']) && $postArr['collectioncode'] == $this->collMap['collectioncode']) $postArr['collectioncode'] = '';
						if(isset($postArr['ownerinstitutioncode']) && $postArr['ownerinstitutioncode'] == $this->collMap['institutioncode']) $postArr['ownerinstitutioncode'] = '';
					}
					$occurFieldArr = array_keys($this->fieldArr['omoccurrences']);
					foreach($postArr as $oField => $ov){
						if(in_array($oField,$occurFieldArr) && $oField != 'observeruid'){
							$vStr = $this->cleanInStr($ov);
							$sql .= ','.$oField.' = '.($vStr!==''?'"'.$vStr.'"':'NULL');
							//Adjust occurrenceMap which was generated but edit was submitted and will not be re-harvested afterwards
							if(array_key_exists($this->occid,$this->occurrenceMap) && array_key_exists($oField,$this->occurrenceMap[$this->occid])){
								$this->occurrenceMap[$this->occid][$oField] = $vStr;
							}
						}
					}
					//If sciname was changed, update image tid link
					if(in_array('tidinterpreted',$editArr)){
						//Remap images
						$sqlImgTid = 'UPDATE images SET tid = '.(is_numeric($postArr['tidinterpreted'])?$postArr['tidinterpreted']:'NULL').' WHERE occid = ('.$this->occid.')';
						$this->conn->query($sqlImgTid);
					}
					//If host was entered in quickhost field, update record
					if($quickHostEntered){
						if($postArr['hostassocid']){
							if($postArr['host']) $sqlHost = 'UPDATE omoccurassociations SET verbatimsciname = "'.$postArr['host'].'" WHERE associd = '.$postArr['hostassocid'].' ';
							else $sqlHost = 'DELETE FROM omoccurassociations WHERE associd = '.$postArr['hostassocid'].' ';
						}
						else $sqlHost = 'INSERT INTO omoccurassociations(occid,relationship,verbatimsciname) VALUES('.$this->occid.',"host","'.$postArr['host'].'")';
						$this->conn->query($sqlHost);
					}
					//Update occurrence record
					$sql = 'UPDATE omoccurrences SET '.substr($sql,1).' WHERE (occid = '.$this->occid.')';
					if($this->conn->query($sql)){
						if(strtolower($postArr['processingstatus']) != 'unprocessed'){
							//UPDATE uid within omcrowdsourcequeue, only if not yet processed
							$isVolunteer = true;
							if(array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($this->collId, $USER_RIGHTS['CollAdmin'])) $isVolunteer = false;
							elseif(array_key_exists('CollEditor',$USER_RIGHTS) && in_array($this->collId, $USER_RIGHTS['CollEditor'])) $isVolunteer = false;
							$sql = 'UPDATE omcrowdsourcequeue SET uidprocessor = '.$GLOBALS['SYMB_UID'].', reviewstatus = 5 ';
							if(!$isVolunteer) $sql .= ', isvolunteer = 0 ';
							$sql .= 'WHERE (uidprocessor IS NULL) AND (occid = '.$this->occid.')';
							if(!$this->conn->query($sql)){
								$status = $LANG['ERROR_TAGGING_USER'].' (#'.$this->occid.'): '.$this->conn->error.' ';
							}
						}
						//Deal with additional identifiers
						if(isset($postArr['idvalue'])) $this->updateIdentifiers($postArr, $identArr);
						//Deal with paleo fields
						if($this->paleoActivated && array_key_exists('eon',$postArr)){
							//Check to see if paleo record already exists
							$paleoRecordExist = false;
							$paleoSql = 'SELECT paleoid FROM omoccurpaleo WHERE occid = '.$this->occid;
							$paleoRS = $this->conn->query($paleoSql);
							if($paleoRS){
								if($paleoRS->num_rows) $paleoRecordExist = true;
								$paleoRS->free();
							}
							if($paleoRecordExist){
								//Edit existing record
								$paleoHasValue = false;
								$paleoFrag = '';
								foreach($this->fieldArr['omoccurpaleo'] as $paleoField){
									if(array_key_exists($paleoField,$postArr)){
										$paleoFrag .= ','.$paleoField.' = '.($postArr[$paleoField]?'"'.$this->cleanInStr($postArr[$paleoField]).'"':'NULL');
										if($postArr[$paleoField]) $paleoHasValue = true;
									}
								}
								$paleoSql = '';
								if($paleoHasValue){
									if($paleoFrag) $paleoSql = 'UPDATE omoccurpaleo SET '.substr($paleoFrag, 1).' WHERE occid = '.$this->occid;
									$this->conn->query('UPDATE omoccurpaleo SET '.substr($paleoFrag, 1).' WHERE occid = '.$this->occid);
								}
								else{
									$paleoSql = 'DELETE FROM omoccurpaleo WHERE occid = '.$this->occid;
								}
								if($paleoSql){
									if(!$this->conn->query($paleoSql)){
										$status = $LANG['ERROR_EDITING_PALEO'].': '.$this->conn->error;
									}
								}
							}
							else{
								//Add new record
								$paleoFrag1 = '';
								$paleoFrag2 = '';
								foreach($this->fieldArr['omoccurpaleo'] as $paleoField){
									if(array_key_exists($paleoField,$postArr) && $postArr[$paleoField]){
										$paleoFrag1 .= ','.$paleoField;
										$paleoFrag2 .= ',"'.$this->cleanInStr($postArr[$paleoField]).'" ';
									}
								}
								if($paleoFrag1){
									$paleoSql = 'INSERT INTO omoccurpaleo(occid'.$paleoFrag1.') VALUES('.$this->occid.$paleoFrag2.')';
									if(!$this->conn->query($paleoSql)){
										$status = $LANG['ERROR_ADDING_PALEO'].': '.$this->conn->error;
									}
								}
							}
						}

						//Deal with exsiccati
						if(in_array('ometid',$editArr) || in_array('exsnumber',$editArr)){
							$ometid = $this->cleanInStr($postArr['ometid']);
							$exsNumber = $this->cleanInStr($postArr['exsnumber']);
							if($ometid && $exsNumber){
								//Values have been submitted, thus try to add ometid and omenid
								//Get exsiccati number id
								$exsNumberId = '';
								$sql = 'SELECT omenid FROM omexsiccatinumbers WHERE ometid = '.$ometid.' AND exsnumber = "'.$exsNumber.'"';
								$rs = $this->conn->query($sql);
								if($r = $rs->fetch_object()){
									$exsNumberId = $r->omenid;
								}
								$rs->free();
								if(!$exsNumberId){
									//There is no exsnumber for that title, thus lets add it and grab new omenid
									$sqlNum = 'INSERT INTO omexsiccatinumbers(ometid,exsnumber) VALUES('.$ometid.',"'.$exsNumber.'")';
									if($this->conn->query($sqlNum)){
										$exsNumberId = $this->conn->insert_id;
									}
									else{
										$status = $LANG['ERROR_ADDING_EXS_NO'].': '.$this->conn->error.' ';
									}
								}
								//Exsiccati was editted
								if($exsNumberId){
									//Use REPLACE rather than INSERT so that if record with occid already exists, it will be removed before insert
									$sql1 = 'REPLACE INTO omexsiccatiocclink(omenid, occid) VALUES('.$exsNumberId.','.$this->occid.')';
									//echo $sql1;
									if(!$this->conn->query($sql1)){
										$status = $LANG['ERROR_ADDING_EXS'].': '.$this->conn->error.' ';
									}
								}
							}
							else{
								//No exsiccati title or number values, thus need to remove
								$sql = 'DELETE FROM omexsiccatiocclink WHERE occid = '.$this->occid;
								$this->conn->query($sql);
							}
						}
						//Deal with duplicate clusters
						if(isset($postArr['linkdupe']) && $postArr['linkdupe']){
							$dupTitle = $postArr['recordedby'].' '.$postArr['recordnumber'].' '.$postArr['eventdate'];
							$status .= $this->linkDuplicates($postArr['linkdupe'],$dupTitle);
						}
					}
					else{
						$status = $LANG['FAILED_TO_EDIT_OCC'].' (#'.$this->occid.'): '.$this->conn->error;
					}
				}
				else{
					$status = $LANG['EDIT_SUBMITTED_NOT_ACTIVATED'];
				}
			}
			else{
				$status = $LANG['ERROR_EDITS_EMPTY'].' #'.$this->occid.': '.$this->conn->error;
			}
		}
		return $status;
	}

	private function getIdentifiers($occidStr){
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

	public function addOccurrence($postArr){
		global $LANG;
		$status = $LANG['SUCCESS_NEW_OCC_SUBMITTED'];
		if($postArr){
			$guid = UuidFactory::getUuidV4();
			$sql = 'INSERT INTO omoccurrences(collid, recordID, '.implode(',',array_keys($this->fieldArr['omoccurrences'])).') VALUES ('.$postArr['collid'].', "'.$guid.'"';
			//if(array_key_exists('cultivationstatus',$postArr) && $postArr['cultivationstatus']) $postArr['cultivationstatus'] = $postArr['cultivationstatus'];
			//if(array_key_exists('localitysecurity',$postArr) && $postArr['localitysecurity']) $postArr['localitysecurity'] = $postArr['localitysecurity'];
			if(!isset($postArr['dateentered']) || !$postArr['dateentered']) $postArr['dateentered'] = date('Y-m-d H:i:s');
			if(!isset($postArr['basisofrecord']) || !$postArr['basisofrecord']) $postArr['basisofrecord'] = (strpos($this->collMap['colltype'],'Observations') !== false?'HumanObservation':'PreservedSpecimen');
			if(isset($postArr['institutioncode']) && $postArr['institutioncode'] == $this->collMap['institutioncode']) $postArr['institutionCode'] = '';
			if(isset($postArr['collectioncode']) && $postArr['collectioncode'] == $this->collMap['collectioncode']) $postArr['collectionCode'] = '';

			foreach($this->fieldArr['omoccurrences'] as $fieldStr => $fieldType){
				$fieldValue = '';
				if(array_key_exists($fieldStr,$postArr)) $fieldValue = $postArr[$fieldStr];
				if($fieldValue){
					if($fieldType == 'n'){
						if(is_numeric($fieldValue)) $sql .= ', '.$fieldValue;
						else $sql .= ', NULL';
					}
					else $sql .= ', "'.$this->cleanInStr($fieldValue).'"';		//Is string or date
				}
				else $sql .= ', NULL';
			}
			$sql .= ')';
			if($this->conn->query($sql)){
				$this->occid = $this->conn->insert_id;
				//Update collection stats
				$this->conn->query('UPDATE omcollectionstats SET recordcnt = recordcnt + 1 WHERE collid = '.$this->collId);
				//Deal with identifiers
				if(isset($postArr['idvalue'])) $this->updateIdentifiers($postArr);
				//Deal with paleo
				if($this->paleoActivated && array_key_exists('eon',$postArr)){
					//Add new record
					$paleoFrag1 = '';
					$paleoFrag2 = '';
					foreach($this->fieldArr['omoccurpaleo'] as $paleoField){
						if(array_key_exists($paleoField,$postArr)){
							$paleoFrag1 .= ','.$paleoField;
							$paleoFrag2 .= ','.($postArr[$paleoField]?'"'.$this->cleanInStr($postArr[$paleoField]).'"':'NULL');
						}
					}
					if($paleoFrag1){
						$paleoSql = 'INSERT INTO omoccurpaleo(occid'.$paleoFrag1.') VALUES('.$this->occid.$paleoFrag2.')';
						$this->conn->query($paleoSql);
					}
				}
				//Deal with Exsiccati
				if(isset($postArr['ometid']) && isset($postArr['exsnumber'])){
					//If exsiccati titie is submitted, trim off first character that was used to force Google Chrome to sort correctly
					$ometid = $this->cleanInStr($postArr['ometid']);
					$exsNumber = $this->cleanInStr($postArr['exsnumber']);
					if($ometid && $exsNumber){
						$exsNumberId = '';
						$sql = 'SELECT omenid FROM omexsiccatinumbers WHERE ometid = '.$ometid.' AND exsnumber = "'.$exsNumber.'"';
						$rs = $this->conn->query($sql);
						if($r = $rs->fetch_object()){
							$exsNumberId = $r->omenid;
						}
						$rs->free();
						if(!$exsNumberId){
							//There is no exsnumber for that title, thus lets add it and record exsomenid
							$sqlNum = 'INSERT INTO omexsiccatinumbers(ometid,exsnumber) '.
								'VALUES('.$ometid.',"'.$exsNumber.'")';
							if($this->conn->query($sqlNum)){
								$exsNumberId = $this->conn->insert_id;
							}
							else{
								$status .= '('.$LANG['WARNING_ADD_EXS_NO'].': '.$this->conn->error.') ';
							}
						}
						if($exsNumberId){
							//Add exsiccati
							$sql1 = 'INSERT INTO omexsiccatiocclink(omenid, occid) '.
								'VALUES('.$exsNumberId.','.$this->occid.')';
							if(!$this->conn->query($sql1)){
								$status .= '('.$LANG['WARNING_ADD_EXS'].': '.$this->conn->error.') ';
							}
						}
					}
				}
				//Deal with host data
				if(array_key_exists('host',$postArr)){
					$sql = 'INSERT INTO omoccurassociations(occid,relationship,verbatimsciname) VALUES('.$this->occid.',"host","'.$this->cleanInStr($postArr['host']).'")';
					if(!$this->conn->query($sql)){
						$status .= '(WARNING adding host: '.$this->conn->error.') ';
					}
				}

				if(isset($postArr['confidenceranking']) && $postArr['confidenceranking']){
					$this->editIdentificationRanking($postArr['confidenceranking'],'');
				}
				//Deal with checklist voucher
				if(isset($postArr['clidvoucher']) && isset($postArr['tidinterpreted'])){
					$status .= $this->linkChecklistVoucher($postArr['clidvoucher'],$postArr['tidinterpreted']);
				}
				//Deal with duplicate clustering
				if(isset($postArr['linkdupe']) && $postArr['linkdupe']){
					$dupTitle = $postArr['recordedby'].' '.$postArr['recordnumber'].' '.$postArr['eventdate'];
					$status .= $this->linkDuplicates($postArr['linkdupe'],$dupTitle);
				}
			}
			else{
				$status = $LANG['FAILED_ADD_OCC'].": ".$this->conn->error.'<br/>SQL: '.$sql;
			}
		}
		return $status;
	}

	private function updateIdentifiers($identArr, $existingIdentArr = null){
		foreach($identArr['idvalue'] as $key => $idValue){
			$idValue = trim($idValue);
			if($idValue){
				$idKey = $identArr['idkey'][$key];
				$idName = trim($identArr['idname'][$key]);
				$sql = 'UPDATE omoccuridentifiers
					SET identifierName = "'.$this->cleanInStr($idName).'", identifierValue = "'.$this->cleanInStr($idValue).'", modifiedUid = '.$GLOBALS['SYMB_UID'].
					' WHERE occid = '.$this->occid.' AND idomoccuridentifiers = '.$idKey;
				if(!is_numeric($idKey)){
					if($existingIdentArr){
						foreach($existingIdentArr[$this->occid] as $valueArr){
							//If identifier name and value already exists, thus skip to evaluate next identifier
							if($valueArr['name'] == $idName && $valueArr['value'] == $idValue) continue 2;
						}
					}
					$sql = 'INSERT INTO omoccuridentifiers(occid, identifierName, identifierValue, modifiedUid)
						VALUE('.$this->occid.',"'.$this->cleanInStr($idName).'","'.$this->cleanInStr($idValue).'", '.$GLOBALS['SYMB_UID'].') ';
				}
				if(!$this->conn->query($sql)){
					$this->errorArr[] = 'ERROR updating/adding identifier: '.$this->conn->error;
					echo implode('; ',$this->errorArr);
				}
			}
		}
	}

	public function deleteOccurrence($delOccid){
		global $CHARSET, $USER_DISPLAY_NAME, $LANG;
		$status = true;
		if(is_numeric($delOccid)){
			//Archive data, first grab occurrence data
			$archiveArr = array();
			$sql = 'SELECT * FROM omoccurrences WHERE occid = '.$delOccid;
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_assoc()){
				foreach($r as $k => $v){
					if($v) $archiveArr[$k] = $this->encodeStrTargeted($v,$CHARSET,'utf8');
				}
			}
			$rs->free();
			if($archiveArr){
				//Archive determinations history
				$sql = 'SELECT * FROM omoccurdeterminations WHERE occid = '.$delOccid;
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_assoc()){
					$detId = $r['detid'];
					foreach($r as $k => $v){
						if($v) $archiveArr['dets'][$detId][$k] = $this->encodeStrTargeted($v,$CHARSET,'utf8');
					}
				}
				$rs->free();

				//Archive image history
				$sql = 'SELECT * FROM images WHERE occid = '.$delOccid;
				if($rs = $this->conn->query($sql)){
					$imgidStr = '';
					while($r = $rs->fetch_assoc()){
						$imgId = $r['imgid'];
						$imgidStr .= ','.$imgId;
						foreach($r as $k => $v){
							if($v) $archiveArr['imgs'][$imgId][$k] = $this->encodeStrTargeted($v,$CHARSET,'utf8');
						}
					}
					$rs->free();
					//Delete images
					if($imgidStr){
						$imgidStr = trim($imgidStr, ', ');
						//Remove any OCR text blocks linked to the image
						if(!$this->conn->query('DELETE FROM specprocessorrawlabels WHERE (imgid IN('.$imgidStr.'))')){
							$this->errorArr[] = $LANG['ERROR_REMOVING_OCR'].': '.$this->conn->error;
						}
						//Remove image tags
						if(!$this->conn->query('DELETE FROM imagetag WHERE (imgid IN('.$imgidStr.'))')){
							$this->errorArr[] = $LANG['ERROR_REMOVING_IMAGETAGS'].': '.$this->conn->error;
						}
						//Remove images
						if(!$this->conn->query('DELETE FROM images WHERE (imgid IN('.$imgidStr.'))')){
							$this->errorArr[] = $LANG['ERROR_REMOVING_LINKS'].': '.$this->conn->error;
						}
					}
				}

				//Archive paleo
				if($this->paleoActivated){
					$sql = 'SELECT * FROM omoccurpaleo WHERE occid = '.$delOccid;
					if($rs = $this->conn->query($sql)){
						if($r = $rs->fetch_assoc()){
							foreach($r as $k => $v){
								if($v) $archiveArr['paleo'][$k] = $this->encodeStrTargeted($v,$CHARSET,'utf8');
							}
						}
						$rs->free();
					}
				}

				//Archive Exsiccati info
				$sql = 'SELECT t.ometid, t.title, t.abbreviation, t.editor, t.exsrange, t.startdate, t.enddate, t.source, t.notes as titlenotes, '.
					'n.omenid, n.exsnumber, n.notes AS numnotes, l.notes, l.ranking '.
					'FROM omexsiccatiocclink l INNER JOIN omexsiccatinumbers n ON l.omenid = n.omenid '.
					'INNER JOIN omexsiccatititles t ON n.ometid = t.ometid '.
					'WHERE l.occid = '.$delOccid;
				if($rs = $this->conn->query($sql)){
					if($r = $rs->fetch_assoc()){
						foreach($r as $k => $v){
							if($v) $archiveArr['exsiccati'][$k] = $this->encodeStrTargeted($v,$CHARSET,'utf8');
						}
					}
					$rs->free();
				}

				//Archive associations info
				$sql = 'SELECT * FROM omoccurassociations WHERE occid = '.$delOccid;
				if($rs = $this->conn->query($sql)){
					while($r = $rs->fetch_assoc()){
						$id = $r['associd'];
						foreach($r as $k => $v){
							if($v) $archiveArr['assoc'][$id][$k] = $this->encodeStrTargeted($v,$CHARSET,'utf8');
						}
					}
					$rs->free();
				}

				//Archive Material Sample info
				$sql = 'SELECT * FROM ommaterialsample WHERE occid = '.$delOccid;
				if($rs = $this->conn->query($sql)){
					while($r = $rs->fetch_assoc()){
						foreach($r as $k => $v){
							$id = $r['matSampleID'];
							if($v) $archiveArr['matSample'][$id][$k] = $this->encodeStrTargeted($v,$CHARSET,'utf8');
						}
					}
					$rs->free();
				}

				//Archive complete occurrence record
				$archiveArr['dateDeleted'] = date('r').' by '.$USER_DISPLAY_NAME;
				$archiveObj = json_encode($archiveArr);
				$sqlArchive = 'INSERT INTO omoccurarchive(archiveobj, occid, catalogNumber, occurrenceID, recordID) '.
					'VALUES ("'.$this->cleanInStr($this->encodeStrTargeted($archiveObj,'utf8',$CHARSET)).'", '.$delOccid.','.
					(isset($archiveArr['catalogNumber']) && $archiveArr['catalogNumber']?'"'.$this->cleanInStr($archiveArr['catalogNumber']).'"':'NULL').', '.
					(isset($archiveArr['occurrenceID']) && $archiveArr['occurrenceID']?'"'.$this->cleanInStr($archiveArr['occurrenceID']).'"':'NULL').', '.
					(isset($archiveArr['recordID']) && $archiveArr['recordID']?'"'.$this->cleanInStr($archiveArr['recordID']).'"':'NULL').')';
				$this->conn->query($sqlArchive);
			}

			//Go ahead and delete
			//Associated records will be deleted from: omexsiccatiocclink, omoccurdeterminations, fmvouchers
			$sqlDel = 'DELETE FROM omoccurrences WHERE (occid = '.$delOccid.')';
			if($this->conn->query($sqlDel)){
				//Update collection stats
				$this->conn->query('UPDATE omcollectionstats SET recordcnt = recordcnt - 1 WHERE collid = '.$this->collId);
			}
			else{
				$this->errorArr[] = $LANG['ERROR_TRYING_TO_DELETE'].': '.$this->conn->error;
				$status = false;
			}
		}
		return $status;
	}

	public function cloneOccurrence($postArr){
		global $LANG;
		$retArr = array();
		if(isset($postArr['clonecount']) && $postArr['clonecount']){
			$postArr['recordenteredby'] = $GLOBALS['USERNAME'];
			$sourceOccid = $this->occid;
			$clearAllArr = array('ownerinstitutioncode','institutioncode','collectioncode','catalognumber','othercatalognumbers','occurrenceid','individualcount','duplicatequantity','processingstatus','dateentered');
			$postArr = array_diff_key($postArr,array_flip($clearAllArr));
			if(isset($postArr['targetcollid']) && $postArr['targetcollid'] && $postArr['targetcollid'] != $this->collId){
				$clearCollArr = array('basisofrecord');
				$postArr = array_diff_key($postArr,array_flip($clearCollArr));
				$postArr['collid'] = $postArr['targetcollid'];
			}
			if(isset($postArr['carryover']) && $postArr['carryover'] == 1){
				$clearEventArr = array('family','sciname','tidinterpreted','scientificnameauthorship','identifiedby','dateidentified','identificationreferences','identificationremarks',
					'taxonremarks','identificationqualifier','recordnumber','occurrenceremarks','verbatimattributes','dynamicproperties','lifestage','sex','reproductivecondition','behavior','preparations');
				$postArr = array_diff_key($postArr,array_flip($clearEventArr));
			}
			$cloneCatNum = array();
			if(isset($postArr['clonecatnum'])) $cloneCatNum = $postArr['clonecatnum'];
			for($i=0; $i < $postArr['clonecount']; $i++){
				if(isset($cloneCatNum[$i]) && $cloneCatNum[$i]) $postArr['catalognumber'] = $cloneCatNum[$i];
				$this->addOccurrence($postArr);
				if($sourceOccid != $this->occid && !in_array($this->occid,$retArr)){
					$retArr[$this->occid] = $this->occid;
					if(isset($postArr['assocrelation']) && $postArr['assocrelation']){
						$sql = 'INSERT INTO omoccurassociations(occid, occidAssociate, relationship,createdUid) '.
							'values('.$this->occid.','.$sourceOccid.',"'.$postArr['assocrelation'].'",'.$GLOBALS['SYMB_UID'].') ';
						if(!$this->conn->query($sql)){
							$this->errorArr[] = $LANG['ERROR_ADDING_REL'].': '.$this->conn->error;
						}
					}
					if(isset($postArr['carryoverimages']) && $postArr['carryoverimages']){
						$sql = 'INSERT INTO images(occid, tid, url, thumbnailurl, originalurl, archiveurl, photographer, photographeruid, imagetype, format, caption, owner,
							sourceurl, referenceUrl, copyright, rights, accessrights, locality, notes, anatomy, username, sourceIdentifier, mediaMD5, dynamicProperties,
							defaultDisplay, sortsequence, sortOccurrence)
							SELECT '.$this->occid.', tid, url, thumbnailurl, originalurl, archiveurl, photographer, photographeruid, imagetype, format, caption, owner, sourceurl, referenceUrl,
							copyright, rights, accessrights, locality, notes, anatomy, username, sourceIdentifier, mediaMD5, dynamicProperties, defaultDisplay, sortsequence, sortOccurrence
							FROM images WHERE occid = '.$sourceOccid;
						if(!$this->conn->query($sql)){
							$this->errorArr[] = $LANG['ERROR_ADDING_IMAGES'].': '.$this->conn->error;
						}
					}
				}
			}
			$this->occid = $sourceOccid;
		}
		return $retArr;
	}

	public function mergeRecords($targetOccid,$sourceOccid){
		global $LANG;
		$status = true;
		if(!$targetOccid || !$sourceOccid){
			$this->errorArr[] = $LANG['TARGET_SOURCE_NULL'];
			return false;
		}
		if($targetOccid == $sourceOccid){
			$this->errorArr[] = $LANG['TARGET_SOURCE_EQUAL'];
			return false;
		}

		$oArr = array();
		//Merge records
		$sql = 'SELECT * FROM omoccurrences WHERE occid = '.$targetOccid.' OR occid = '.$sourceOccid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_assoc()){
			$tempArr = array_change_key_case($r);
			$id = $tempArr['occid'];
			unset($tempArr['occid']);
			unset($tempArr['collid']);
			unset($tempArr['dbpk']);
			unset($tempArr['datelastmodified']);
			$oArr[$id] = $tempArr;
		}
		$rs->free();

		$tArr = $oArr[$targetOccid];
		$sArr = $oArr[$sourceOccid];
		$sqlFrag = '';
		foreach($sArr as $k => $v){
			if(($v != '') && $tArr[$k] == ''){
				$sqlFrag .= ','.$k.'="'.$this->cleanInStr($v).'"';
			}
		}
		if($sqlFrag){
			//Remap source to target
			$sqlIns = 'UPDATE omoccurrences SET '.substr($sqlFrag,1).' WHERE occid = '.$targetOccid;
			//echo $sqlIns;
			if(!$this->conn->query($sqlIns)){
				$this->errorArr[] = $LANG['ABORT_DUE_TO_ERROR'].': '.$this->conn->error;
				return false;
			}
		}

		//Remap determinations
		$sql = 'UPDATE IGNORE omoccurdeterminations SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			//$this->errorArr[] .= '; '.$LANG['ERROR_REMAPPING_DETS'].': '.$this->conn->error;
			//$status = false;
		}

		//Remap images
		$sql = 'UPDATE images SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$this->errorArr[] .= '; '.$LANG['ERROR_REMAPPING_IMAGES'].': '.$this->conn->error;
			$status = false;
		}

		//Remap paleo
		if($this->paleoActivated){
			$sql = 'UPDATE omoccurpaleo SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
			if(!$this->conn->query($sql)){
				//$this->errorArr[] .= '; '.$LANG['ERROR_REMAPPING_PALEOS'].': '.$this->conn->error;
				//$status = false;
			}
		}

		//Delete source occurrence edits
		$sql = 'DELETE FROM omoccuredits WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$this->errorArr[] .= '; '.$LANG['ERROR_REMAPPING_OCC_EDITS'].': '.$this->conn->error;
			$status = false;
		}

		//Remap associations
		$sql = 'UPDATE omoccurassociations SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$this->errorArr[] .= '; '.$LANG['ERROR_REMAPPING_ASSOCS_1'].': '.$this->conn->error;
			$status = false;
		}
		$sql = 'UPDATE omoccurassociations SET occidAssociate = '.$targetOccid.' WHERE occidAssociate = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$this->errorArr[] .= '; '.$LANG['ERROR_REMAPPING_ASSOCS_2'].': '.$this->conn->error;
			$status = false;
		}

		//Remap comments
		$sql = 'UPDATE omoccurcomments SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$this->errorArr[] .= '; '.$LANG['ERROR_REMAPPING_COMMENTS'].': '.$this->conn->error;
			$status = false;
		}

		//Remap genetic resources
		$sql = 'UPDATE omoccurgenetic SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$this->errorArr[] .= '; '.$LANG['ERROR_REMAPPING_GENETIC'].': '.$this->conn->error;
			$status = false;
		}

		//Remap identifiers
		$sql = 'UPDATE omoccuridentifiers SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$this->errorArr[] .= '; '.$LANG['ERROR_REMAPPING_OCCIDS'].': '.$this->conn->error;
			$status = false;
		}

		//Remap exsiccati
		$sql = 'UPDATE omexsiccatiocclink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			if(strpos($this->conn->error,'Duplicate') !== false){
				$this->conn->query('DELETE FROM omexsiccatiocclink WHERE occid = '.$sourceOccid);
			}
			else{
				$this->errorArr[] .= '; '.$LANG['ERROR_REMAPPING_EXS'].': '.$this->conn->error;
				$status = false;
			}
		}

		//Remap occurrence dataset links
		$sql = 'UPDATE omoccurdatasetlink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			if(strpos($this->conn->error,'Duplicate') !== false){
				$this->conn->query('DELETE FROM omoccurdatasetlink WHERE occid = '.$sourceOccid);
			}
			else{
				$this->errorArr[] .= '; '.$LANG['ERROR_REMAPPING_DATASET'].': '.$this->conn->error;
				$status = false;
			}
		}

		//Remap loans
		$sql = 'UPDATE omoccurloanslink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			if(strpos($this->conn->error,'Duplicate') !== false){
				$this->conn->query('DELETE FROM omoccurloanslink WHERE occid = '.$sourceOccid);
			}
			else{
				$this->errorArr[] .= '; '.$LANG['ERROR_REMAPPING_LOANS'].': '.$this->conn->error;
				$status = false;
			}
		}

		//Remap checklists voucher links
		$sql = 'UPDATE fmvouchers SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			if(strpos($this->conn->error,'Duplicate') !== false){
				$this->conn->query('DELETE FROM fmvouchers WHERE occid = '.$sourceOccid);
			}
			else{
				$this->errorArr[] .= '; '.$LANG['ERROR_REMAPPING_VOUCHER'].': '.$this->conn->error;
				$status = false;
			}
		}

		if(!$this->deleteOccurrence($sourceOccid)){
			$status = false;
		}
		return $status;
	}

	public function transferOccurrence($targetOccid,$transferCollid){
		global $LANG;
		$status = true;
		if(is_numeric($targetOccid) && is_numeric($transferCollid)){
			$sql = 'UPDATE omoccurrences SET collid = '.$transferCollid.' WHERE occid = '.$targetOccid;
			if(!$this->conn->query($sql)){
				$this->errorArr[] = $LANG['ERROR_TRYING_TO_DELETE'].': '.$this->conn->error;
				$status = false;
			}
		}
		return $status;
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

	public function getObserverUid(){
		$obsId = 0;
		if($this->occurrenceMap && array_key_exists('observeruid',$this->occurrenceMap[$this->occid])){
			$obsId = $this->occurrenceMap[$this->occid]['observeruid'];
		}
		elseif($this->occid){
			$sql = 'SELECT observeruid FROM omoccurrences WHERE occid = '.$this->occid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$obsId = $r->observeruid;
			}
			$rs->free();
		}
		return $obsId;
	}

	public function carryOverValues($fArr){
		$locArr = Array('recordedby','associatedcollectors','eventdate','eventdate2','verbatimeventdate','month','day','year',
			'startdayofyear','enddayofyear','country','stateprovince','county','municipality','locationid','locality','decimallatitude','decimallongitude',
			'verbatimcoordinates','coordinateuncertaintyinmeters','footprintwkt','geodeticdatum','georeferencedby','georeferenceprotocol',
			'georeferencesources','georeferenceverificationstatus','georeferenceremarks',
			'minimumelevationinmeters','maximumelevationinmeters','verbatimelevation','minimumdepthinmeters','maximumdepthinmeters','verbatimdepth',
			'habitat','substrate','lifestage', 'sex', 'individualcount', 'samplingprotocol', 'preparations',
			'associatedtaxa','basisofrecord','language','labelproject','eon','era','period','epoch','earlyinterval','lateinterval','absoluteage','storageage','stage','localstage','biota',
			'biostratigraphy','lithogroup','formation','taxonenvironment','member','bed','lithology','stratremarks','element');
		$retArr = array_intersect_key($fArr,array_flip($locArr));
		$this->cleanOutArr($retArr);
		return $retArr;
	}

	//Verification functions
	public function getIdentificationRanking(){
		//Get Identification ranking
		$retArr = array();
		$sql = 'SELECT v.ovsid, v.ranking, v.notes, u.username '.
			'FROM omoccurverification v LEFT JOIN users u ON v.uid = u.uid '.
			'WHERE v.category = "identification" AND v.occid = '.$this->occid;
		$rs = $this->conn->query($sql);
		//There can only be one identification ranking per specimen
		if($r = $rs->fetch_object()){
			$retArr['ovsid'] = $r->ovsid;
			$retArr['ranking'] = $r->ranking;
			$retArr['notes'] = $r->notes;
			$retArr['username'] = $r->username;
		}
		$rs->free();
		return $retArr;
	}

	public function editIdentificationRanking($ranking,$notes=''){
		global $LANG;
		$statusStr = '';
		if(is_numeric($ranking)){
			//Will be replaced if an identification ranking already exists for occurrence record
			$sql = 'REPLACE INTO omoccurverification(occid,category,ranking,notes,uid) '.
				'VALUES('.$this->occid.',"identification",'.$ranking.','.($notes?'"'.$this->cleanInStr($notes).'"':'NULL').','.$GLOBALS['SYMB_UID'].')';
			if(!$this->conn->query($sql)){
				$statusStr .= $LANG['WARNING_EDIT_ADD_FAILED'].' ('.$this->conn->error.') ';
				//echo $sql;
			}
		}
		return $statusStr;
	}

	public function linkChecklistVoucher($clid,$tid){
		global $LANG;
		$status = '';
		if(is_numeric($clid) && is_numeric($tid)){
			//Check to see it the name is in the list, if not, add it
			$clTaxaID = 0;
			$sql = 'SELECT cl.clTaxaID '.
				'FROM fmchklsttaxalink cl INNER JOIN taxstatus ts1 ON cl.tid = ts1.tid '.
				'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = ?) AND (cl.clid = ?)';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('ii', $tid, $clid);
				$stmt->execute();
				$stmt->bind_result($clTaxaID);
				$stmt->fetch();
				$stmt->close();
			}
			if(!$clTaxaID){
				$sql = 'INSERT INTO fmchklsttaxalink(tid,clid) VALUES(?,?)';
				if($stmt = $this->conn->prepare($sql)){
					$stmt->bind_param('ii', $tid, $clid);
					$stmt->execute();
					if($stmt->affected_rows) $clTaxaID = $stmt->insert_id;
					else $status .= '('.$LANG['WARNING_ADD_SCINAME'].': '.$stmt->error.'); ';
					$stmt->close();
				}
			}
			//Add voucher
			if($clTaxaID){
				$sql = 'INSERT INTO fmvouchers(clTaxaID, occid) VALUES(?,?) ';
				if($stmt = $this->conn->prepare($sql)){
					$stmt->bind_param('ii', $clTaxaID, $this->occid);
					$stmt->execute();
					if(!$stmt->affected_rows) $status .= '('.$LANG['WARNING_ADD_VOUCHER'].': '.$stmt->error.'); ';
					$stmt->close();
				}
			}
		}
		return $status;
	}

	public function deleteChecklistVoucher($clid){
		global $LANG;
		$status = '';
		if(is_numeric($clid)){
			$sql = 'DELETE v.* FROM fmvouchers v INNER JOIN fmchklsttaxalink c ON v.clTaxaID = c.clTaxaID WHERE c.clid = '.$clid.' AND v.occid = '.$this->occid;
			if(!$this->conn->query($sql)){
				$status = $LANG['ERROR_DELETING_VOUCHER'].': '.$this->conn->error;
			}
		}
		return $status;
	}

	public function getUserChecklists(){
		// Return list of checklists to which user has editing writes
		$retArr = Array();
		if(ISSET($GLOBALS['USER_RIGHTS']['ClAdmin'])){
			$sql = 'SELECT clid, name, access FROM fmchecklists WHERE (clid IN('.implode(',',$GLOBALS['USER_RIGHTS']['ClAdmin']).')) ';
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->clid] = $r->name.($r->access == 'private'?' (private)':'');
			}
			$rs->free();
			asort($retArr);
		}
		return $retArr;
	}

	//Duplicate functions
	private function linkDuplicates($occidStr,$dupTitle){
		$status = '';
		$dupManager = new OccurrenceDuplicate();
		$dupManager->linkDuplicates($this->occid,$occidStr,$dupTitle);
		return $status;
	}

	//Genetic link functions
	public function getGeneticArr(){
		global $LANG;
		$retArr = array();
		if($this->occid){
			$sql = 'SELECT idoccurgenetic, identifier, resourcename, locus, resourceurl, notes FROM omoccurgenetic WHERE occid = '.$this->occid;
			$result = $this->conn->query($sql);
			if($result){
				while($r = $result->fetch_object()){
					$retArr[$r->idoccurgenetic]['id'] = $r->identifier;
					$retArr[$r->idoccurgenetic]['name'] = $r->resourcename;
					$retArr[$r->idoccurgenetic]['locus'] = $r->locus;
					$retArr[$r->idoccurgenetic]['resourceurl'] = $r->resourceurl;
					$retArr[$r->idoccurgenetic]['notes'] = $r->notes;
				}
				$result->free();
			}
			else{
				trigger_error($LANG['UNABLE_GENETIC_DATA'].'; '.$this->conn->error,E_USER_WARNING);
			}
		}
		return $retArr;
	}

	public function editGeneticResource($genArr){
		global $LANG;
		$genId = $genArr['genid'];
		if(is_numeric($genId)){
			$sql = 'UPDATE omoccurgenetic SET '.
				'identifier = "'.$this->cleanInStr($genArr['identifier']).'", '.
				'resourcename = "'.$this->cleanInStr($genArr['resourcename']).'", '.
				'locus = '.($genArr['locus']?'"'.$this->cleanInStr($genArr['locus']).'"':'NULL').', '.
				'resourceurl = '.($genArr['resourceurl']?'"'.$genArr['resourceurl'].'"':'NULL').', '.
				'notes = '.($genArr['notes']?'"'.$this->cleanInStr($genArr['notes']).'"':'NULL').' '.
				'WHERE idoccurgenetic = '.$genArr['genid'];
			if(!$this->conn->query($sql)){
				return $LANG['ERROR_EDITING_GENETIC'].' #'.$genArr['genid'].': '.$this->conn->error;
			}
			return $LANG['GEN_RESOURCE_EDIT_SUCCESS'];
		}
		return false;
	}

	public function deleteGeneticResource($id){
		global $LANG;
		if(is_numeric($id)){
			$sql = 'DELETE FROM omoccurgenetic WHERE idoccurgenetic = '.$id;
			if(!$this->conn->query($sql)){
				return $LANG['ERROR_DELETING_GENETIC'].' #'.$id.': '.$this->conn->error;
			}
			return $LANG['GEN_RESOURCE_DEL_SUCCESS'];
		}
		return false;
	}

	public function addGeneticResource($genArr){
		global $LANG;
		$sql = 'INSERT INTO omoccurgenetic(occid, identifier, resourcename, locus, resourceurl, notes) '.
			'VALUES('.$this->cleanInStr($genArr['occid']).',"'.$this->cleanInStr($genArr['identifier']).'","'.
			$this->cleanInStr($genArr['resourcename']).'",'.
			($genArr['locus']?'"'.$this->cleanInStr($genArr['locus']).'"':'NULL').','.
			($genArr['resourceurl']?'"'.$this->cleanInStr($genArr['resourceurl']).'"':'NULL').','.
			($genArr['notes']?'"'.$this->cleanInStr($genArr['notes']).'"':'NULL').')';
		if(!$this->conn->query($sql)){
			return $LANG['ERROR_ADDING_GEN'].': '.$this->conn->error;
		}
		return $LANG['GEN_RES_ADD_SUCCESS'];
	}

	//OCR label processing methods
	public function getRawTextFragments(){
		$retArr = array();
		if($this->occid){
			$sql = 'SELECT r.prlid, r.imgid, r.rawstr, r.notes, r.source '.
				'FROM specprocessorrawlabels r INNER JOIN images i ON r.imgid = i.imgid '.
				'WHERE i.occid = '.$this->occid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->imgid][$r->prlid]['raw'] = $this->cleanOutStr($r->rawstr);
				$retArr[$r->imgid][$r->prlid]['notes'] = $this->cleanOutStr($r->notes);
				$retArr[$r->imgid][$r->prlid]['source'] = $this->cleanOutStr($r->source);
			}
			$rs->free();
		}
		return $retArr;
	}

	public function insertTextFragment($imgId,$rawFrag,$notes,$source){
		global $LANG;
		if($imgId && $rawFrag){
			$statusStr = '';
			//$rawFrag = preg_replace('/[^(\x20-\x7F)]*/','', $rawFrag);
			$sql = 'INSERT INTO specprocessorrawlabels(imgid,rawstr,notes,source) '.
				'VALUES ('.$imgId.',"'.$this->cleanRawFragment($rawFrag).'",'.
				($notes?'"'.$this->cleanInStr($notes).'"':'NULL').','.
				($source?'"'.$this->cleanInStr($source).'"':'NULL').')';
			//echo $sql;
			if($this->conn->query($sql)){
				$statusStr = $this->conn->insert_id;
			}
			else{
				$statusStr = $LANG['ERROR_UNABLE_INSERT'].'; '.$this->conn->error;
				$statusStr .= '; SQL = '.$sql;
			}
			return $statusStr;
		}
	}

	public function saveTextFragment($prlId,$rawFrag,$notes,$source){
		global $LANG;
		if(is_numeric($prlId) && $rawFrag){
			$statusStr = '';
			//$rawFrag = preg_replace('/[^(\x20-\x7F)]*/','', $rawFrag);
			$sql = 'UPDATE specprocessorrawlabels '.
				'SET rawstr = "'.$this->cleanRawFragment($rawFrag).'", '.
				'notes = '.($notes?'"'.$this->cleanInStr($notes).'"':'NULL').', '.
				'source = '.($source?'"'.$this->cleanInStr($source).'"':'NULL').' '.
				'WHERE (prlid = '.$prlId.')';
			if(!$this->conn->query($sql)){
				$statusStr = $LANG['ERROR_UNABLE_UPDATE'].'; '.$this->conn->error;
				$statusStr .= '; SQL = '.$sql;
			}
			return $statusStr;
		}
	}

	public function deleteTextFragment($prlId){
		global $LANG;
		if(is_numeric($prlId)){
			$statusStr = '';
			$sql = 'DELETE FROM specprocessorrawlabels WHERE (prlid = '.$prlId.')';
			if(!$this->conn->query($sql)){
				$statusStr = $LANG['ERROR_UNABLE_DELETE'].'; '.$this->conn->error;
			}
			return $statusStr;
		}
	}

	public function getImageMap($imgId = 0){
		$imageMap = Array();
		if($this->occid){
			$sql = 'SELECT imgid, url, thumbnailurl, originalurl, caption, photographer, photographeruid, sourceurl, copyright, notes, occid, username, sortoccurrence, initialtimestamp FROM images ';
			if($imgId) $sql .= 'WHERE (imgid = '.$imgId.') ';
			else $sql .= 'WHERE (occid = '.$this->occid.') ';
			$sql .= 'ORDER BY sortoccurrence';
			//echo $sql;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$imageMap[$row->imgid]['url'] = $row->url;
				$imageMap[$row->imgid]['tnurl'] = $row->thumbnailurl;
				$imageMap[$row->imgid]['origurl'] = $row->originalurl;
				$imageMap[$row->imgid]['caption'] = $this->cleanOutStr($row->caption);
				$imageMap[$row->imgid]['photographer'] = $this->cleanOutStr($row->photographer);
				$imageMap[$row->imgid]['photographeruid'] = $row->photographeruid;
				$imageMap[$row->imgid]['sourceurl'] = $row->sourceurl;
				$imageMap[$row->imgid]['copyright'] = $this->cleanOutStr($row->copyright);
				$imageMap[$row->imgid]['notes'] = $this->cleanOutStr($row->notes);
				$imageMap[$row->imgid]['occid'] = $row->occid;
				$imageMap[$row->imgid]['username'] = $this->cleanOutStr($row->username);
				$imageMap[$row->imgid]['sort'] = $row->sortoccurrence;
			}
			$result->free();
		}
		return $imageMap;
	}

	protected function getImageTags($imgIdStr){
		$retArr = array();
		$sql = 'SELECT t.imgid, k.tagkey, k.shortlabel, k.description_en FROM imagetag t INNER JOIN imagetagkey k ON t.keyvalue = k.tagkey WHERE t.imgid IN('.$imgIdStr.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->imgid][$r->tagkey] = $r->shortlabel;
		}
		$rs->free();
		return $retArr;
	}

	public function getEditArr(){
		$retArr = array();
		$this->setOccurArr();
		$sql = 'SELECT e.ocedid, e.fieldname, e.fieldvalueold, e.fieldvaluenew, e.reviewstatus, e.appliedstatus, '.
			'CONCAT_WS(", ",u.lastname,u.firstname) as editor, e.initialtimestamp '.
			'FROM omoccuredits e INNER JOIN users u ON e.uid = u.uid '.
			'WHERE e.occid = '.$this->occid.' ORDER BY e.initialtimestamp DESC ';
		$result = $this->conn->query($sql);
		if($result){
			while($r = $result->fetch_object()){
				$k = substr($r->initialtimestamp,0,16);
				if(!isset($retArr[$k])){
					$retArr[$k]['editor'] = $r->editor;
					$retArr[$k]['ts'] = $r->initialtimestamp;
					$retArr[$k]['reviewstatus'] = $r->reviewstatus;
				}
				$retArr[$k]['edits'][$r->appliedstatus][$r->ocedid]['fieldname'] = $r->fieldname;
				$retArr[$k]['edits'][$r->appliedstatus][$r->ocedid]['old'] = $r->fieldvalueold;
				$retArr[$k]['edits'][$r->appliedstatus][$r->ocedid]['new'] = $r->fieldvaluenew;
				$currentCode = 0;
				if(isset($this->occurrenceMap[$this->occid][strtolower($r->fieldname)])){
					$fName = $this->occurrenceMap[$this->occid][strtolower($r->fieldname)];
					if($fName == $r->fieldvaluenew) $currentCode = 1;
					elseif($fName == $r->fieldvalueold) $currentCode = 2;
				}
				$retArr[$k]['edits'][$r->appliedstatus][$r->ocedid]['current'] = $currentCode;
			}
			$result->free();
		}
		return $retArr;
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

	//Edit locking functions (session variables)
	public function getLock(){
		$isLocked = false;
		//Check lock
		$delSql = 'DELETE FROM omoccureditlocks WHERE (ts < '.(time()-900).') OR (uid = '.$GLOBALS['SYMB_UID'].')';
		if(!$this->conn->query($delSql)) return false;
		//Try to insert lock for , existing lock is assumed if fails
		$sql = 'INSERT INTO omoccureditlocks(occid,uid,ts) VALUES ('.$this->occid.','.$GLOBALS['SYMB_UID'].','.time().')';
		if(!$this->conn->query($sql)){
			$isLocked = true;
		}
		return $isLocked;
	}

	//Misc data support functions
	public function getCollectionList($limitToUser = true){
		$retArr = array();
		$sql = 'SELECT collid, collectionname FROM omcollections ';
		if($limitToUser){
			$collArr = array('0');
			if(isset($GLOBALS['USER_RIGHTS']['CollAdmin'])) $collArr = $GLOBALS['USER_RIGHTS']['CollAdmin'];
			$sql .= 'WHERE (collid IN('.implode(',',$collArr).')) ';
			if(isset($GLOBALS['USER_RIGHTS']['CollEditor'])){
				$sql .= 'OR (collid IN('.implode(',',$GLOBALS['USER_RIGHTS']['CollEditor']).') AND colltype = "General Observations")';
			}
		}
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid] = $r->collectionname;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	public function getPaleoGtsTerms(){
		$retArr = array();
		if($this->paleoActivated){
			$sql = 'SELECT gtsterm, rankid FROM omoccurpaleogts ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->gtsterm] = $r->rankid;
			}
			$rs->free();
			ksort($retArr);
		}
		return $retArr;
	}

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

	public function getQuickHost(){
		$retArr = Array();
		if($this->occid){
			$sql = 'SELECT associd, verbatimsciname FROM omoccurassociations WHERE relationship = "host" AND occid = '.$this->occid.' ';
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['associd'] = $r->associd;
				$retArr['verbatimsciname'] = $r->verbatimsciname;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getAssociationControlVocab(){
		$retArr = array();
		$sql = 'SELECT t.cvTermID, t.term '.
			'FROM ctcontrolvocabterm t INNER JOIN ctcontrolvocab v ON t.cvID = v.cvID '.
			'WHERE v.tablename = "omoccurassociations" AND v.fieldName = "relationship" ORDER BY term';
		$rs = $this->conn->query($sql);
		if($rs){
			while($r = $rs->fetch_object()){
				$retArr[$r->cvTermID] = $r->term;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function isCrowdsourceEditor(){
		$isEditor = false;
		if($this->occid){
			$sql = 'SELECT reviewstatus, uidprocessor FROM omcrowdsourcequeue WHERE occid = '.$this->occid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->reviewstatus == 0){
					//crowdsourcing status is open for editing
					$isEditor = true;
				}
				elseif($r->reviewstatus == 5 && $r->uidprocessor == $GLOBALS['SYMB_UID']){
					//CS status is pending (=5) and active user was original editor
					$isEditor = true;
				}
			}
			$rs->free();
		}
		return $isEditor;
	}

	public function traitCodingActivated(){
		$bool = false;
		$sql = 'SELECT traitid FROM tmtraits LIMIT 1';
		$rs = $this->conn->query($sql);
		if($rs->num_rows) $bool = true;
		$rs->free();
		return $bool;
	}

	//Setters and getters
	public function isPersonalManagement(){
		return $this->isPersonalManagement;
	}

	//Misc functions
	private function encodeStrTargeted($inStr,$inCharset,$outCharset){
		if($inCharset == $outCharset) return $inStr;
		$retStr = $inStr;
		if($inCharset == "latin" && $outCharset == 'utf8'){
			if(mb_detect_encoding($retStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
				$retStr = utf8_encode($retStr);
			}
		}
		elseif($inCharset == "utf8" && $outCharset == 'latin'){
			if(mb_detect_encoding($retStr,'UTF-8,ISO-8859-1') == "UTF-8"){
				$retStr = utf8_decode($retStr);
			}
		}
		return $retStr;
	}

}
?>
