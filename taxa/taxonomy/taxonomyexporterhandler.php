<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/TaxonomyExporter.php');

$taxonExp = new TaxonomyExporter();

// Check for older versions of Symbiota that don't have the $TEMP_DIR_ROOT variable
if (empty($TEMP_DIR_ROOT)) {
	$TEMP_DIR_ROOT = $SERVER_ROOT . '/temp';
}

$filesDir = $TEMP_DIR_ROOT . "/downloads/";

if (array_key_exists("node", $_REQUEST)) {
	$node = filter_var($_REQUEST["node"], FILTER_SANITIZE_NUMBER_INT);
} else {
	$node = 0;
}

$rootNodes = $taxonExp->setRootNode($node);
// echo ("<h2>Root Nodes</h2>");
// echo "<pre>" . print_r($rootNodes, true) . "</pre>";
// echo ("<br><br>");
// echo ("<hr>");
// echo ("<hr>");

/****************************************/
/** Use below to traverse tree node per node */
// foreach ($rootNodes as $rootNode) {
// 	$firstChildren = $taxonExp->getNextChildren($rootNode["tid"], $rootNode["rankid"]);
// 	print_r($firstChildren);
// 	echo ("<br><br>");

// 	$children = $firstChildren;
// 	while (!empty($children)) {
// 		$children = $taxonExp->getNextChildren($rootNode["tid"], $children[0]["rankid"]);
// 		print_r($children);
// 		echo ("<br><br>");
// 	}
// }
/****************************************/

/****************************************/
/** Use below to get single query with all nodes */
// $ranksRange = $taxonExp->getRanksRange();
// echo ("<h2>Ranks Range</h2>");
// echo "<pre>" . print_r($ranksRange, true) . "</pre>";

$kingdomRankId = $taxonExp->getRankId("kingdom");
$kingdomRankId = $kingdomRankId[0];
// echo ("<h2>Kingdom Rank ID " . $kingdomRankId . "</h2>");


$rootNodesData = array();
$kingdomsData = array();
// get root node data, children and tree for each root node
foreach ($rootNodes as $rootNode) {
	$rootNodeData = $taxonExp->getNode($rootNode["tid"]);
	// echo ("<h2>Root Node: " . $rootNode["sciname"] . "</h2>");
	// echo "<pre> . print_r($rootNodeData) . </pre>";
	// echo ("<br><br>");
	// add to $rootNodesData
	$rootNodesData[] = $rootNodeData;

	$children = $taxonExp->getNodeChildren($rootNode["tid"], 1, $kingdomRankId);
	// echo ("<h3>Children:</h3>");
	// echo $children ?  "<pre>" . print_r($children, true) . "</pre>" : "No children found.";
	// echo ("<br><br>");
	// echo ("<hr>");
	$kingdomsData[] = $children;
}

// echo "<h2>Root Nodes Data:</h2>";
// echo "<pre>" . print_r($rootNodesData, true) . "</pre>";
// echo "<h3>Children Data (All Children):</h3>";
// echo "<pre>" . print_r($childrenData, true) . "</pre>";

/** 
 * File names primers
 */
$fileDate = date("Y-m-d");
$portalName = $DEFAULT_TITLE = str_replace(" ", "_", $DEFAULT_TITLE);
$zipFilename = $filesDir . $fileDate . '_' . $portalName . "_taxonomy.zip";
$zip = new ZipArchive;

/** 
 * Symbiota files */

// 1. Create CSV with higher taxa (Organisms -> Kingdoms)
$higherTree = array_merge($rootNodesData, array_merge(...$kingdomsData));
$higherTree = $taxonExp->conformTree($higherTree, "symbiota");
// echo "<pre>" . print_r($higherTree, true) . "</pre>";
if (!empty($higherTree)) {
	$higherTreeFilename = $filesDir . $fileDate . "_" . $portalName . "_symb_higher.csv";
	$taxonExp->writeCsv($higherTree, $higherTreeFilename);
	// if (file_exists($higherTreeFilename)) {
	// 	header('Content-Description: File Transfer');
	// 	header('Content-Type: application/octet-stream');
	// 	header('Content-Disposition: attachment; filename="' . basename($higherTreeFilename) . '"');
	// 	header('Expires: 0');
	// 	header('Cache-Control: must-revalidate');
	// 	header('Pragma: public');
	// 	header('Content-Length: ' . filesize($higherTreeFilename));
	// 	readfile($higherTreeFilename);
	// 	exit;
	// } else {
	// 	echo "There was an error downloading the file.";
	// }
}
// echo $higherTreeFilename;
if ($zip->open($zipFilename, ZipArchive::CREATE) === TRUE) {
	$zip->addFile($higherTreeFilename, basename($higherTreeFilename));
	$zip->addFromString('new.txt', 'text to be added to the new.txt file');
	$zip->close();
}

if (file_exists($zipFilename)) {
	header('Content-Type: application/zip');
	header('Content-Disposition: attachment; filename="' . basename($zipFilename) . '"');
	header('Content-Length: ' . filesize($zipFilename));

	flush();
	readfile($zipFilename);
	unlink($zipFilename);
}

// 2. Create one CSV per kingdom with all the children in that given kingdom

// $tree = array_merge($rootNodeData, $children);
// echo ("<h2>Full Tree for " . $rootNodeData["rankname"] . " " . $rootNodeData["sciname"] . ":</h2>");
// echo "<pre>" . print_r($tree, true) . "</pre>";
// echo ("<hr>");
// echo ("<br><br>");

/********/

	// Writes full Symbiota taxonomy to a single csv file
	// $symbtree = $taxonExp->conformTree($tree, "symbiota");

	// print_r($symbtree);

	// if (!empty($symbtree)) {

	// 	$taxFileName .= date("Y-m-d") . "_";

	// 	if ($node == 0) {
	// 		$DEFAULT_TITLE = str_replace(" ", "_", $DEFAULT_TITLE);
	// 		$taxFileName .= $DEFAULT_TITLE . "_full_";
	// 	} else {
	// 		$DEFAULT_TITLE = str_replace(" ", "_", $DEFAULT_TITLE);
	// 		$rootNode["sciname"] = str_replace(" ", "_", $rootNode["sciname"]);
	// 		$taxFileName .= $DEFAULT_TITLE . "_" . $rootNode["sciname"] . "_";
	// 	}

	// 	$taxFileName .= "taxonomy.csv";

	// 	$taxonExp->writeCsv($symbtree, $taxFileName);

	// 	// Download $symbcsv file if it exists

	// 	if (file_exists($taxFileName)) {
	// 		header('Content-Description: File Transfer');
	// 		header('Content-Type: application/octet-stream');
	// 		header('Content-Disposition: attachment; filename="' . basename($taxFileName) . '"');
	// 		header('Expires: 0');
	// 		header('Cache-Control: must-revalidate');
	// 		header('Pragma: public');
	// 		header('Content-Length: ' . filesize($taxFileName));
	// 		readfile($taxFileName);
	// 		exit;
	// 	} else {
	// 		echo "There was an error downloading the file.";
	// 	}

	// 	// Filter tree to include only higher taxa (rankid between 1 and 160)

	// 	// // Add file to zip file
	// 	// $zipFileName = $TEMP_DIR_ROOT . "/downloads/" . date("Y-m-d") . "_" . $DEFAULT_TITLE . "_taxonomy.zip";
	// 	// $zip = new ZipArchive();
	// 	// $zip->open($zipFileName, ZipArchive::CREATE);
	// 	// $zip->addFile($taxFileName, basename($taxFileName));
	// 	// $zip->close();
