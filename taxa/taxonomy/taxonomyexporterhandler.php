<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/TaxonomyExporter.php');

$taxonExp = new TaxonomyExporter();

if (array_key_exists("node", $_REQUEST)) {
	$node = filter_var($_REQUEST["node"], FILTER_SANITIZE_NUMBER_INT);
} else {
	$node = 0;
}

$rootNode = $taxonExp->setRootNode($node);

$rootNodeData = $taxonExp->getNode($rootNode["tid"]);
$children = $taxonExp->getNodeChildren($rootNode["tid"]);
$tree = array_merge($rootNodeData, $children);

// Check for older versions of Symbiota that don't have the $TEMP_DIR_ROOT variable
if (empty($TEMP_DIR_ROOT)) {
	$TEMP_DIR_ROOT = $SERVER_ROOT . '/temp';
}

$taxFileName = $TEMP_DIR_ROOT . "/downloads/";

// Writes full Symbiota taxonomy to a single csv file
if (!empty($tree)) {

	$taxFileName .= date("Y-m-d") . "_";

	if ($node == 0) {
		$DEFAULT_TITLE = str_replace(" ", "_", $DEFAULT_TITLE);
		$taxFileName .= $DEFAULT_TITLE . "_full_";
	} else {
		$DEFAULT_TITLE = str_replace(" ", "_", $DEFAULT_TITLE);
		$rootNode["sciname"] = str_replace(" ", "_", $rootNode["sciname"]);
		$taxFileName .= $DEFAULT_TITLE . "_" . $rootNode["sciname"] . "_";
	}

	$taxFileName .= "taxonomy.csv";

	$taxonExp->writeCsv($tree, $taxFileName);

	// Filter tree to include only higher taxa (rankid between 1 and 160)

	// // Add file to zip file
	// $zipFileName = $TEMP_DIR_ROOT . "/downloads/" . date("Y-m-d") . "_" . $DEFAULT_TITLE . "_taxonomy.zip";
	// $zip = new ZipArchive();
	// $zip->open($zipFileName, ZipArchive::CREATE);
	// $zip->addFile($taxFileName, basename($taxFileName));
	// $zip->close();
}
