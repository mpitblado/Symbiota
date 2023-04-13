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

$tree = $taxonExp->getNodeChildren($rootNode["tid"]);

// only downloads if the tree is not empty
if (!empty($tree)) {
	$taxFileName = date("Y-m-d") . "_";

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
}
