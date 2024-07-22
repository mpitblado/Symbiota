<?php

include_once($SERVER_ROOT.'/classes/OccurrenceTaxaManager.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUtilities.php');

class AssociationManager extends OccurrenceTaxaManager{

	private $isEditor = false;

	function __construct(){
		parent::__construct();
		if($GLOBALS['USER_RIGHTS']){
			if($GLOBALS['IS_ADMIN'] || array_key_exists("Taxonomy",$GLOBALS['USER_RIGHTS'])){
				$this->isEditor = true;
			}
		}
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function getRelationshipTypes(){
		$sql = "SELECT DISTINCT relationship from omoccurassociations WHERE relationship IN (SELECT term from ctcontrolvocabterm WHERE cvID='1')";
		// $sql = "SELECT DISTINCT relationship from omoccurassociations";
		if($statement = $this->conn->prepare($sql)){
			$statement->execute();
			$result = $statement->get_result();
			$relationshipTypes = [];
			while ($row = $result->fetch_assoc()) {
				$relationshipTypes[] = $row['relationship'];
				$inverseRelationship = $this->getInverseRelationshipOf($row['relationship']);
				// var_dump($inverseRelationship);
				if(!in_array($inverseRelationship, $relationshipTypes)){
					$relationshipTypes[] = $inverseRelationship;
				}
			}
			$statement->close();
			return $relationshipTypes;
		}else{
			return [];
		}
	}

	public function getAssociatedRecords($associationArr){
		$sql='';

		if(array_key_exists('relationship', $associationArr) && $associationArr['relationship'] !== 'none'){
			// "Forward" association
			$relationshipType = (array_key_exists('relationship', $associationArr) && $associationArr['relationship'] !== 'any') ? $associationArr['relationship'] : 'IS NOT NULL';
			$relationshipStr = (array_key_exists('relationship', $associationArr) && $associationArr['relationship'] !== 'any') ?  ("='" . $relationshipType . "'")  : (' IS NOT NULL');
			$sql .= "AND (o.occid IN (SELECT DISTINCT o.occid FROM omoccurrences o INNER JOIN omoccurassociations oa on o.occid=oa.occid WHERE oa.relationship" . $relationshipStr . " ";
			$sql .= $this->getAssociatedTaxonWhereFrag($associationArr) . ')';
	
			// @TODO handle situation where the associationType is external and there's no occidAssociate; pull resourceUrl instead?
			//something like:
			// $sql =. 'SELECT DISTINCT resourceUrl from omoccurassociations WHERE associationType="externalOccurrence" AND occidAssociate IS NULL AND relationship = ' . $relationshipType . ' AND ';
	
			// "Reverse" association
			$reverseAssociationType = (array_key_exists('relationship', $associationArr) && $associationArr['relationship'] !== 'any') ? $this->getInverseRelationshipOf($relationshipType) : 'IS NOT NULL';
			$reverseRelationshipStr = (array_key_exists('relationship', $associationArr) && $associationArr['relationship'] !== 'any') ?  ("='" . $reverseAssociationType . "'")  : (' IS NOT NULL');
			$sql .= " OR o.occid IN (SELECT DISTINCT oa.occidAssociate FROM omoccurrences o INNER JOIN omoccurassociations oa on o.occid=oa.occid INNER JOIN omoccurdeterminations od ON oa.occid=od.occid where oa.relationship " . $reverseRelationshipStr . " "; //isCurrent="1" AND my thought was that we want these results to be as relaxed as possible
			$sql .= $this->getAssociatedTaxonWhereFrag($associationArr) . ')';
		}
		return $sql;
	}

	public function getAssociatedTaxonWhereFrag($associationArr){
		$sqlWhereTaxa = '';
		if(isset($associationArr['taxa'])){
			$tidInArr = array();
			$taxonType = $associationArr['associated-taxa'];
			foreach($associationArr['taxa'] as $searchTaxon => $searchArr){
				if(isset($searchArr['taxontype'])) $taxonType = $searchArr['taxontype'];
				if($taxonType == TaxaSearchType::TAXONOMIC_GROUP){
					//Class, order, or other higher rank
					if(isset($searchArr['tid'])){
						$tidArr = array_keys($searchArr['tid']);
						$sqlWhereTaxa .= 'OR (e.parenttid IN('.implode(',', $tidArr).') ';
						$sqlWhereTaxa .= 'OR (e.tid IN('.implode(',', $tidArr).')) ';
						if(isset($searchArr['synonyms'])) $sqlWhereTaxa .= 'OR (e.tid IN('.implode(',',array_keys($searchArr['synonyms'])).')) ';
						$sqlWhereTaxa .= ') ';
					}
					else{
						//Unable to find higher taxon within taxonomic tree, thus return nothing
						$sqlWhereTaxa .= 'OR (o.tidinterpreted = 0) ';
					}
				}
				elseif($taxonType == TaxaSearchType::FAMILY_ONLY){
					if(isset($searchArr['tid'])){
						$tidArr = array_keys($searchArr['tid']);
						$sqlWhereTaxa .= 'OR ((ts.family = "'.$searchTaxon.'") OR (ts.tid IN('.implode(',', $tidArr).'))) ';
					}
					else{
						$sqlWhereTaxa .= 'OR ((o.family = "'.$searchTaxon.'") OR (o.sciname = "'.$searchTaxon.'")) ';
					}
				}
				else{
					if($taxonType == TaxaSearchType::COMMON_NAME){
						$famArr = $this->setCommonNameWhereTerms($searchArr, $tidInArr);
						if($famArr) $sqlWhereTaxa .= 'OR (o.family IN("'.implode('","',$famArr).'")) ';
					}
					if(isset($searchArr['TID_BATCH'])){
						$tidInArr = array_merge($tidInArr, array_keys($searchArr['TID_BATCH']));
						if(isset($searchArr['tid'])) $tidInArr = array_merge($tidInArr, array_keys($searchArr['tid']));
					}
					else{
						$term = $this->cleanInStr(trim($searchTaxon,'%'));
						$term = preg_replace(array('/\s{1}x\s{1}/','/\s{1}X\s{1}/','/\s{1}\x{00D7}\s{1}/u'), ' _ ', $term);
						if(array_key_exists('tid',$searchArr)){
							$rankid = current($searchArr['tid']);
							$tidArr = array_keys($searchArr['tid']);
							$tidInArr = array_merge($tidInArr, $tidArr);
							//Return matches that are not linked to thesaurus
							if($rankid > 179){
								if($this->exactMatchOnly) $sqlWhereTaxa .= 'OR (o.sciname = "' . $term . '") ';
								else $sqlWhereTaxa .= "OR (o.sciname LIKE '" . $term . "%') ";
							}
						}
						else{
							//Protect against someone trying to download big pieces of the occurrence table through the user interface
							if(strlen($term) < 4) $term .= ' ';
							if($this->exactMatchOnly){
								$sqlWhereTaxa .= 'OR (o.sciname = "' . $term . '") ';
							}
							else{
								$sqlWhereTaxa .= 'OR (o.sciname LIKE "' . $term . '%") ';
								if(!strpos($term,' _ ')){
									//Accommodate for formats of hybrid designations within input and target data (e.g. x, multiplication sign, etc)
									$term2 = preg_replace('/^([^\s]+\s{1})/', '$1 _ ', $term);
									$sqlWhereTaxa .= "OR (o.sciname LIKE '" . $term2 . "%') ";
								}
							}
						}
					}
					if(array_key_exists('synonyms',$searchArr)){
						$synArr = $searchArr['synonyms'];
						if($synArr){
							if($taxonType == TaxaSearchType::SCIENTIFIC_NAME || $taxonType == TaxaSearchType::COMMON_NAME){
								foreach($synArr as $synTid => $sciName){
									if(strpos($sciName,'aceae') || strpos($sciName,'idae')){
										$sqlWhereTaxa .= 'OR (o.family = "' . $sciName . '") ';
									}
								}
							}
							$tidInArr = array_merge($tidInArr,array_keys($synArr));
						}
					}
				}
			}
			if($tidInArr) $sqlWhereTaxa .= 'OR (o.tidinterpreted IN('.implode(',',array_unique($tidInArr)).')) ';
			$sqlWhereTaxa = 'AND ('.trim(substr($sqlWhereTaxa,3)).') ';
			if(strpos($sqlWhereTaxa,'e.parenttid')) $sqlWhereTaxa .= 'AND (e.taxauthid = '.$this->taxAuthId.') ';
			if(strpos($sqlWhereTaxa,'ts.family')) $sqlWhereTaxa .= 'AND (ts.taxauthid = '.$this->taxAuthId.') ';
		}
		return $sqlWhereTaxa;
	}


	public function getInverseRelationshipOf($relationship){
		$sql = "SELECT inverseRelationship FROM ctcontrolvocabterm where cvID='1' AND term = ?";
		if($statement = $this->conn->prepare($sql)){
			$statement->bind_param('s', $relationship);
			$statement->execute();
			$result = $statement->get_result();
			$returnVal = '';
			if ($row = $result->fetch_assoc()) {
				$returnVal = $row['inverseRelationship'];
			}
			$statement->close();
			return $returnVal;
		}else{
			return '';
		}
	}

}
?>
