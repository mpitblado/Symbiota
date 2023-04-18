<?php
include_once($SERVER_ROOT . '/classes/Manager.php');

class TaxonomyExporter extends Manager
{

	function __construct()
	{

		parent::__construct();
		if ($GLOBALS['USER_RIGHTS']) {
			if ($GLOBALS['IS_ADMIN'] || array_key_exists("Taxonomy", $GLOBALS['USER_RIGHTS'])) {
				$this->isEditor = true;
			}
		}
	}

	function __destruct()
	{
		parent::__destruct();
	}

	function displayDownloadBtn()
	{
		// if isEditor is true display a button
		if ($this->isEditor) {
			echo '<button>Download Full Portal Taxonomy</button>';
		}
	}

	/** Defines the root node of the tree
	 * @param $node - the node to be used as the root (integer, default = 0 for full portal taxonomy; or tid of a taxon)
	 * @return array - the root node
	 */
	function setRootNode($node)
	{
		if ($node) {
			$rootTid = $node;
			$stmt = 'SELECT tid, sciname, author, rankid FROM taxa WHERE tid = ?';
			$stmt = $this->conn->prepare($stmt);
			$stmt->bind_param('i', $rootTid);
			$stmt->execute();
			$stmt->bind_result($tid, $sciname, $author, $rankid);
			$rootNodeArr = array();
		} else {
			$node = 0;
			// gets the smallest rankid in the taxonomic tree
			$query = 'SELECT MIN(t.rankid) AS rankid FROM taxa t INNER JOIN     taxstatus ts ON t.tid = ts.tid WHERE (t.rankid != 0) AND (ts.taxauthid = 1);';
			$res = $this->conn->query($query);
			$row = $res->fetch_assoc();
			$rootRankId = $row["rankid"];
			// get root taxa of the taxonomic tree in portal (all taxa where rankid is the root)
			$stmt = 'SELECT tid, sciname, author, rankid FROM taxa WHERE rankid = ? ORDER BY sciname';
			$stmt = $this->conn->prepare($stmt);
			$stmt->bind_param('i', $rootRankId);
			$stmt->execute();
			$stmt->bind_result($tid, $sciname, $author, $rankid);
			$rootNodeArr = array();
		}
		while ($stmt->fetch()) {
			$rootNodeArr[] = array("tid" => $tid, "sciname" => $sciname, "author" => $author, "rankid" => $rankid);
		}
		$stmt->close();
		$rootNode = $rootNodeArr[0];
		return $rootNode;
	}

	/** Gets the node data
	 * @param node - the tid to get the data of
	 * @return array - the data of the node
	 */
	function getNode($node)
	{
		$stmt = "SELECT t.tid AS taxonID, t.kingdomName AS kingdom, ts.family, t.sciname, t.author, CONCAT_WS(' ', t.unitind1, t.unitname1) AS genus, CONCAT_WS(' ', t.unitind2, t.unitname2) AS specificepithet, t.unitind3 AS taxonrank, t.unitname3 AS infraspecificepithet, t.rankid, tu.rankname, t.source, CONCAT_WS(' ', p.sciname, p.author) AS parentstr FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid LEFT JOIN taxaenumtree e ON ts.tid = e.tid AND ts.taxauthid = e.taxauthid INNER JOIN taxa p ON ts.parentTid = p.tid INNER JOIN taxa a ON ts.tidaccepted = a.tid INNER JOIN taxonunits tu ON t.rankid = tu.rankid WHERE ts.taxauthid = 1 AND t.tid = ? GROUP BY t.tid ORDER BY t.rankid, ts.family, sciname;";

		$stmt = $this->conn->prepare($stmt);
		$stmt->bind_param('i', $node);
		$stmt->execute();

		$stmt->bind_result($taxonID, $kingdom, $family, $sciname, $author, $genus, $specificepithet, $taxonrank, $infraspecificepithet, $rankid, $rankname, $source, $parentstr);
		$nodeDataArr = array();
		while ($stmt->fetch()) {
			$nodeDataArr[] = array("taxonID" => $taxonID, "kingdom" => $kingdom, "family" => $family, "sciname" => $sciname, "author" => $author, "genus" => $genus, "specificepithet" => $specificepithet, "taxonrank" => $taxonrank, "infraspecificepithet" => $infraspecificepithet, "rankid" => $rankid, "rankname" => $rankname, "source" => $source, "parentstr" => $parentstr);
		}
		$stmt->close();
		return $nodeDataArr;
	}

	/** Gets the children of a node
	 * @param $node - the tid to get the children of (the root tid of the tree)
	 * @return array - the children of the node
	 * todo: add a parameter to limit the number of children returned?
	 * todo: add more fields to the returned array
	 */
	function getNodeChildren($node)
	{
		// $stmt = "SELECT t.tid AS taxonID, t.kingdomName AS kingdom, ts.family, t.sciname,CONCAT_WS(' ', t.unitind1, t.unitname1) AS unitname1, CONCAT_WS(' ', t.unitind2, t.unitname2) AS unitname2, t.unitind3, t.unitname3, t.author, t.rankid, t.source, p.tid AS parentNameUsageID,  p.sciname AS parentNameUsage, p.author AS parentNameAuthor, a.tid AS acceptedNameUsageID, a.sciname AS acceptedNameUsage, a.author AS acceptedNameAuthor FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid INNER JOIN taxaenumtree e ON ts.tid = e.tid AND ts.taxauthid = e.taxauthid INNER JOIN taxa p ON ts.parentTid = p.tid INNER JOIN taxa a ON ts.tidaccepted = a.tid WHERE ts.taxauthid = 1 AND e.parentTid = ? ORDER BY t.rankid, ts.family, sciname;";
		$stmt = "SELECT t.tid AS taxonID, t.kingdomName AS kingdom, ts.family, t.sciname, t.author, CONCAT_WS(' ', t.unitind1, t.unitname1) AS genus, CONCAT_WS(' ', t.unitind2, t.unitname2) AS specificepithet, t.unitind3 AS taxonrank, t.unitname3 AS infraspecificepithet, t.rankid, tu.rankname, t.source, CONCAT_WS(' ', p.sciname, p.author) AS parentstr FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid INNER JOIN taxaenumtree e ON ts.tid = e.tid AND ts.taxauthid = e.taxauthid INNER JOIN taxa p ON ts.parentTid = p.tid INNER JOIN taxa a ON ts.tidaccepted = a.tid INNER JOIN taxonunits tu ON t.rankid = tu.rankid WHERE ts.taxauthid = 1 AND e.parentTid = ? AND tu.kingdomName = t.kingdomName GROUP BY t.tid ORDER BY t.rankid, ts.family, sciname;";

		$stmt = $this->conn->prepare($stmt);
		$stmt->bind_param('i', $node);
		$stmt->execute();
		// $stmt->bind_result($tid, $kingdomName, $family, $sciname, $unitname1, $unitname2, $unitind3, $unitname3, $author, $rankid, $source, $parentNameUsageID, $parentNameUsage, $parentNameAuthor, $acceptedNameUsageID, $acceptedNameUsage, $acceptedNameAuthor);
		$stmt->bind_result($tid, $kingdom, $family, $sciname, $author, $genus, $specificepithet, $taxonrank, $infraspecificepithet, $rankid, $rankname, $source, $parentstr);
		$nodeChildrenArr = array();
		// while ($stmt->fetch()) {
		// 	$nodeChildrenArr[] = array("taxonID" => $tid, "kingdom" => $kingdomName, "family" => $family, "sciname" => $sciname, "unitname1" => $unitname1, "unitname2" => $unitname2, "unitind3" => $unitind3, "unitname3" => $unitname3, "author" => $author, "rankid" => $rankid, "source" => $source, "parentNameUsageID" => $parentNameUsageID, "parentNameUsage" => $parentNameUsage, "parentNameAuthor" => $parentNameAuthor, "acceptedNameUsageID" => $acceptedNameUsageID, "acceptedNameUsage" => $acceptedNameUsage, "acceptedNameAuthor" => $acceptedNameAuthor);
		// }
		while ($stmt->fetch()) {
			$nodeChildrenArr[] = array("taxonID" => $tid, "kingdom" => $kingdom, "family" => $family, "sciname" => $sciname, "author" => $author, "genus" => $genus, "specificepithet" => $specificepithet, "taxonrank" => $taxonrank, "infraspecificepithet" => $infraspecificepithet, "rankid" => $rankid, "rankname" => $rankname, "source" => $source, "parentstr" => $parentstr);
		}
		$stmt->close();
		return $nodeChildrenArr;
	}

	/** Writes a csv file
	 * @param $array - the array to be written to the csv file (with header row)
	 * @param $filename - the name of the file to be written
	 */
	function writeCsv($array, $filename)
	{
		$fp = fopen($filename, 'w');
		fputcsv($fp, array_keys($array[0]));
		foreach ($array as $fields) {
			fputcsv($fp, $fields);
		}
		fclose($fp);

		if (file_exists($filename)) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($filename));
			readfile($filename);
			exit;
		} else {
			echo "There was an error downloading the file.";
		}
	}

	// write nex tree
	// write metadata
	// write citeme

}
