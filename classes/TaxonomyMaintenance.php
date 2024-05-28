<?php
include_once($SERVER_ROOT.'/classes/Manager.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUtilities.php');

class TaxonomyMaintenance extends Manager{

	private $taxAuthID = 1;
	private $kingdomID;
	private $filterNodeTid = 0;
	private $rankArr = array();

	function __construct() {
		parent::__construct(null,'write');
	}

	function __destruct(){
		parent::__destruct();
	}

	public function getTaxonomyReport(){
		$retArr = array();
		$retArr['orphanedTaxa'] = $this->getOrphanedTaxaCount();
		$retArr['mismatchedFamilies'] = $this->getMismatchedFamilyCount();
		$retArr['illegalParents'] = $this->getIllegalParentCount();
		$retArr['illegalAccepted'] = $this->getIllegalAcceptedCount();
		$retArr['infraspIssues'] = $this->getMislinkedInfraspecificCount();
		$retArr['speciesIssues'] = $this->getMislinkedSpeciesCount();
		$retArr['generaIssues'] = $this->getMislinkedGeneraCount();
		return $retArr;
	}

	//Get lists of problematic counts
	public function getOrphanedTaxaCount(){
		//Count of taxa entered into taxa table, but without hierarchy or acceptance defined within taxstatus table
		$retCnt = 0;
		$sql = 'SELECT COUNT(tid) as cnt FROM taxa WHERE tid NOT IN(SELECT tid FROM taxstatus WHERE taxauthid = ?)';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->taxAuthID);
			$stmt->execute();
			$stmt->bind_result($retCnt);
			$stmt->fetch();
			$stmt->close();
		}
		return $retCnt;
	}

	public function getMismatchedFamilyCount(){
		//Count of taxa with mismatched families (family quick lookup field mismatched with family defined within hierarchy)
		$retCnt = 0;
		$sql = 'SELECT COUNT(ts.tid) as cnt
			FROM taxstatus ts INNER JOIN taxaenumtree e ON ts.tid = e.tid
			INNER JOIN taxa t ON ts.tid = t.tid
			INNER JOIN taxa p ON e.parenttid = p.tid
			WHERE e.taxauthid = ? AND ts.taxauthid = ? AND p.rankid = 140 AND ts.family != p.sciname';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ii', $this->taxAuthID, $this->taxAuthID);
			$stmt->execute();
			$stmt->bind_result($retCnt);
			$stmt->fetch();
			$stmt->close();
		}
		return $retCnt;
	}

	public function getIllegalParentCount(){
		//Taxa whose direct parent is of an equal or higher rankid
		$retCnt = 0;
		$sql = 'SELECT COUNT(t.tid) AS cnt
			FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
			INNER JOIN taxa p ON ts.parenttid = p.tid
			WHERE ts.taxauthid = ? AND t.rankid < p.rankid';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->taxAuthID);
			$stmt->execute();
			$stmt->bind_result($retCnt);
			$stmt->fetch();
			$stmt->close();
		}
		return $retCnt;
	}

	public function getIllegalAcceptedCount(){
		//Accepted taxa with a non-accepted parent
		$retCnt = 0;
		$sql = 'SELECT COUNT(t.tid) AS cnt
			FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
			INNER JOIN taxstatus pts ON ts.parentTid = pts.tid
			WHERE ts.taxauthid = ? AND pts.taxauthid = ? AND ts.tid = ts.tidAccepted AND pts.tid != pts.tidAccepted';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ii', $this->taxAuthID, $this->taxAuthID);
			$stmt->execute();
			$stmt->bind_result($retCnt);
			$stmt->fetch();
			$stmt->close();
		}
		return $retCnt;
	}

	public function getMislinkedInfraspecificCount(){
		//Infraspecific taxa linked to a parent of a rank < species rank
		$retCnt = 0;
		$sql = 'SELECT COUNT(t.tid) AS cnt
			FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
			INNER JOIN taxstatus pts ON ts.parentTid = pts.tid
			INNER JOIN taxa p ON pts.tid = p.tid
			WHERE ts.taxauthid = ? AND pts.taxauthid = ? AND t.rankid > 220 AND p.rankid < 220';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ii', $this->taxAuthID, $this->taxAuthID);
			$stmt->execute();
			$stmt->bind_result($retCnt);
			$stmt->fetch();
			$stmt->close();
		}
		return $retCnt;
	}

	public function getMislinkedSpeciesCount(){
		//Species ranked taxa (rankid = 220) that are linked to a parent of a rank < genus rank
		$retCnt = 0;
		$sql = 'SELECT COUNT(t.tid) AS cnt
			FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
			INNER JOIN taxstatus pts ON ts.parentTid = pts.tid
			INNER JOIN taxa p ON pts.tid = p.tid
			WHERE ts.taxauthid = ? AND pts.taxauthid = ? AND t.rankid = 220 AND p.rankid < 180';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ii', $this->taxAuthID, $this->taxAuthID);
			$stmt->execute();
			$stmt->bind_result($retCnt);
			$stmt->fetch();
			$stmt->close();
		}
		return $retCnt;
	}

	public function getMislinkedGeneraCount(){
		//Genera that are linked to a parent of a rank < family rank (this might not be a problem, but should be checked)
		$retCnt = 0;
		$sql = 'SELECT COUNT(t.tid) AS cnt
			FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
			INNER JOIN taxstatus pts ON ts.parentTid = pts.tid
			INNER JOIN taxa p ON pts.tid = p.tid
			WHERE ts.taxauthid = ? AND pts.taxauthid = ? AND t.rankid = 180 AND p.rankid < 140';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ii', $this->taxAuthID, $this->taxAuthID);
			$stmt->execute();
			$stmt->bind_result($retCnt);
			$stmt->fetch();
			$stmt->close();
		}
		return $retCnt;
	}

	//Get lists of problematic taxa
	public function getOrphanedTaxa(){
		$retArr = array();
		$this->setRankArr();
		$sql = 'SELECT tid, sciname, author, rankid FROM taxa WHERE tid NOT IN(SELECT tid FROM taxstatus WHERE taxauthid = ?)';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->taxAuthID);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				if($r = $rs->fetch_object()){
					$retArr[$r->tid]['sciname'] = $r->sciname;
					$retArr[$r->tid]['author'] = $r->author;
					$retArr[$r->tid]['rankid'] = $r->rankid;
					if($this->rankArr[$r->rankid]) $retArr[$r->tid]['rankName'] = $this->rankArr[$r->rankid];
				}
				$rs->free();
			}
			$stmt->close();
		}
		return $retArr;
	}

	public function getIllegalParentTaxa(){
		//Taxa whose direct parent is of an equal or higher rankid
		$retArr = array();
		$this->setRankArr();
		$sql = 'SELECT t.tid, t.sciname, t.rankid, p.tid AS parentTid, p.sciname as parent, p.rankID AS parentRankID
			FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
			INNER JOIN taxa p ON ts.parenttid = p.tid
			WHERE ts.taxauthid = ? AND t.rankid < p.rankid';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->taxAuthID);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				if($r = $rs->fetch_object()){
					$retArr[$r->tid]['sciname'] = $r->sciname;
					$retArr[$r->tid]['rankid'] = $r->rankid;
					if($this->rankArr[$r->rankid]) $retArr[$r->tid]['rankName'] = $this->rankArr[$r->rankid];
					$retArr[$r->tid]['parentTid'] = $r->parentTid;
					$retArr[$r->tid]['parent'] = $r->parent;
					$retArr[$r->tid]['parentRankID'] = $r->parentRankID;
				}
				$rs->free();
			}
			$stmt->close();
		}
		return $retArr;
	}

	public function getIllegalAcceptedTaxa(){
		//Accepted taxa with a non-accepted parent
		$retArr = array();
		$this->setRankArr();
		$sql = 'SELECT t.tid, t.sciname, t.rankid, p.tid AS parentTid, p.sciname AS parent, p.rankid AS parentRankID
			FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
			INNER JOIN taxstatus pts ON ts.parentTid = pts.tid
			INNER JOIN taxa p ON pts.tid = p.tid
			WHERE ts.taxauthid = ? AND pts.taxauthid = ? AND ts.tid = ts.tidAccepted AND pts.tid != pts.tidAccepted';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ii', $this->taxAuthID, $this->taxAuthID);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				if($r = $rs->fetch_object()){
					$retArr[$r->tid]['sciname'] = $r->sciname;
					$retArr[$r->tid]['rankid'] = $r->rankid;
					if($this->rankArr[$r->rankid]) $retArr[$r->tid]['rankName'] = $this->rankArr[$r->rankid];
					$retArr[$r->tid]['parentTid'] = $r->parentTid;
					$retArr[$r->tid]['parent'] = $r->parent;
					$retArr[$r->tid]['parentRankID'] = $r->parentRankID;
				}
				$rs->free();
			}
			$stmt->close();
		}
		return $retArr;
	}

	public function getMislinkedInfraspecificTaxa(){
		//Accepted taxa with a non-accepted parent
		$retArr = array();
		$this->setRankArr();
		$sql = 'SELECT t.tid, t.sciname, t.rankid, ts.tidaccepted, p.tid AS parentTid, p.sciname AS parent, p.rankid AS parentRankid
			FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
			INNER JOIN taxstatus pts ON ts.parentTid = pts.tid
			INNER JOIN taxa p ON pts.tid = p.tid
			WHERE ts.taxauthid = ? AND pts.taxauthid = ? AND t.rankid > 220 AND p.rankid < 220';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ii', $this->taxAuthID, $this->taxAuthID);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				if($r = $rs->fetch_object()){
					$acceptance = 1;
					if($r->tid != $r->tidaccepted) $acceptance = 0;
					$retArr[$r->tid][$acceptance]['sciname'] = $r->sciname;
					$retArr[$r->tid][$acceptance]['rankid'] = $r->rankid;
					if($this->rankArr[$r->rankid]) $retArr[$r->tid]['rankName'] = $this->rankArr[$r->rankid];
					$retArr[$r->tid][$acceptance]['parentTid'] = $r->parentTid;
					$retArr[$r->tid][$acceptance]['parent'] = $r->parent;
					$retArr[$r->tid][$acceptance]['parentRankID'] = $r->parentRankID;
				}
				$rs->free();
			}
			$stmt->close();
		}
		return $retArr;
	}

	public function getMislinkedSpeciesTaxa(){
		//Species ranked taxa (rankid = 220) that are linked to a parent of a rank < genus rank
		$retArr = array();
		$this->setRankArr();
		$sql = 'SELECT t.tid, t.sciname, t.rankid, ts.tidaccepted, p.tid AS parentTid, p.sciname AS parent, p.rankid AS parentRankid
			FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
			INNER JOIN taxstatus pts ON ts.parentTid = pts.tid
			INNER JOIN taxa p ON pts.tid = p.tid
			WHERE ts.taxauthid = ? AND pts.taxauthid = ? AND t.rankid = 220 AND p.rankid < 180';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ii', $this->taxAuthID, $this->taxAuthID);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				if($r = $rs->fetch_object()){
					$acceptance = 1;
					if($r->tid != $r->tidaccepted) $acceptance = 0;
					$retArr[$r->tid][$acceptance]['sciname'] = $r->sciname;
					$retArr[$r->tid][$acceptance]['rankid'] = $r->rankid;
					if($this->rankArr[$r->rankid]) $retArr[$r->tid]['rankName'] = $this->rankArr[$r->rankid];
					$retArr[$r->tid][$acceptance]['parentTid'] = $r->parentTid;
					$retArr[$r->tid][$acceptance]['parent'] = $r->parent;
					$retArr[$r->tid][$acceptance]['parentRankID'] = $r->parentRankID;
				}
				$rs->free();
			}
			$stmt->close();
		}
		return $retArr;
	}

	public function getMislinkedGeneraTaxa(){
		//Species ranked taxa (rankid = 220) that are linked to a parent of a rank < genus rank
		$retArr = array();
		$this->setRankArr();
		$sql = 'SELECT t.tid, t.sciname, t.rankid, ts.tidaccepted, p.tid AS parentTid, p.sciname AS parent, p.rankid AS parentRankid
			FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
			INNER JOIN taxstatus pts ON ts.parentTid = pts.tid
			INNER JOIN taxa p ON pts.tid = p.tid
			WHERE ts.taxauthid = ? AND pts.taxauthid = ? AND t.rankid = 180 AND p.rankid < 140';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ii', $this->taxAuthID, $this->taxAuthID);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				if($r = $rs->fetch_object()){
					$acceptance = 1;
					if($r->tid != $r->tidaccepted) $acceptance = 0;
					$retArr[$r->tid][$acceptance]['sciname'] = $r->sciname;
					$retArr[$r->tid][$acceptance]['rankid'] = $r->rankid;
					if($this->rankArr[$r->rankid]) $retArr[$r->tid]['rankName'] = $this->rankArr[$r->rankid];
					$retArr[$r->tid][$acceptance]['parentTid'] = $r->parentTid;
					$retArr[$r->tid][$acceptance]['parent'] = $r->parent;
					$retArr[$r->tid][$acceptance]['parentRankID'] = $r->parentRankID;
				}
				$rs->free();
			}
			$stmt->close();
		}
		return $retArr;
	}

	//Data repair functions
	public function synchronizeFamilyQuickLookup(){
		$status = false;
		//Delete enumeration index for mismatched taxa
		$sql = 'DELETE e.*
			FROM taxstatus ts INNER JOIN taxaenumtree e on ts.tid = e.tid
			INNER JOIN taxa p on e.parenttid = p.tid
			WHERE e.taxauthid = ? and p.rankid = 140 and ts.taxauthid = ? AND ts.family != p.sciname';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ii', $this->taxAuthID, $this->taxAuthID);
			$stmt->execute();
			$stmt->close();
		}

		//Reset enumeration index for all taxa
		TaxonomyUtilities::buildHierarchyEnumTree($this->conn, $this->taxAuthID);

		//Reset family quick lookup field based on hierarchy
		$sql = 'UPDATE taxstatus ts INNER JOIN taxaenumtree e ON ts.tid = e.tid
			INNER JOIN taxa p ON e.parenttid = p.tid
			SET ts.family = p.sciname
			WHERE e.taxauthid = ? AND p.rankid = 140 AND ts.taxauthid = ? AND ts.family != p.sciname';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ii', $this->taxAuthID, $this->taxAuthID);
			$stmt->execute();
			$status = $stmt->affected_rows;
			$stmt->close();
		}
		return $status;
	}

	public function pruneBadParentNodes(){
		$status = false;
		$taxaArr = $this->getIllegalParentTaxa();
		foreach($taxaArr as $tid => $taxaArr){

		}
		return $status;
	}

	private function pruneTaxonNodes($tid){
		$previousTid = 0;
		$previousRankID = 0;
		do{
			$currentTid = 0;
			$currentRankID = 0;
			$sql = 'SELECT ts.tid, ts.parentTid, t.rankid FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE ts.taxauthid = ? AND t.tid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('ii', $this->taxAuthID, $tid);
				$stmt->execute();
				$stmt->bind_result($currentTid, $tid, $currentRankID);
			}
		}
		while($currentRankID > 11);

	}

	public function rebuildHierarchyEnumTree(){
		TaxonomyUtilities::rebuildHierarchyEnumTree($this->conn);
	}

	//Data set functions
	private function setRankArr(){
		if(!$this->rankArr && $this->kingdomID){
			$sql = 'SELECT u.rankID, u.rankName
				FROM taxonunits u INNER JOIN taxa t ON u.kingdomName = t.sciname
				WHERE t.tid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->kingdomID);
				$stmt->execute();
				$rankID = 0;
				$rankName = '';
				$stmt->bind_result($rankID, $rankName);
				while($stmt->fetch()){
					$this->rankArr[$rankID] = $rankName;
				}
				$stmt->close();
			}
		}
	}

	//Setters and getters
	public function setTaxAuthID($authID){
		$this->taxAuthID = filter_var($authID, FILTER_SANITIZE_NUMBER_INT);
	}

	public function setFilterNodeTid($filterTid){
		$this->filterNodeTid = filter_var($filterTid, FILTER_SANITIZE_NUMBER_INT);
	}

	public function setKingdomID($id){
		$this->kingdomID = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
	}
}
?>