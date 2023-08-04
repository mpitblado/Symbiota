<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/content/lang/collections/map/index.' . $LANG_TAG . '.php');
include_once($SERVER_ROOT . '/classes/OccurrenceMapManager.php');

$distFromMe = array_key_exists('distFromMe', $_REQUEST) ? $_REQUEST['distFromMe'] : '';
$recLimit = array_key_exists('recordlimit', $_REQUEST) ? $_REQUEST['recordlimit'] : 15000;
$catId = array_key_exists('catid', $_REQUEST) ? $_REQUEST['catid'] : 0;
$tabIndex = array_key_exists('tabindex', $_REQUEST) ? $_REQUEST['tabindex'] : 0;
$submitForm = array_key_exists('submitform', $_REQUEST) ? $_REQUEST['submitform'] : '';

if (!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;

//Sanitation
if (!is_numeric($recLimit)) $recLimit = 15000;
if (!is_numeric($distFromMe)) $distFromMe = '';
if (!is_numeric($catId)) $catId = 0;
if (!is_numeric($tabIndex)) $tabIndex = 0;

//Handle AJAX request for more records from a previous partial load
if(array_key_exists('loadMoreMapData',$_REQUEST) && $_SESSION['map_current_query']){

	$mapManager = OccurrenceMapManager::initAJAX();
	$retreivedRecords = json_encode($mapManager->getCoordinateMap2($_SESSION['map_retrieved_record_count']+1,$recLimit));
	$rerievedRecordCount = $mapManager->getRetrievedRecordCnt();
	$_SESSION['map_retrieved_record_count'] = $_SESSION['map_retrieved_record_count'] + $rerievedRecordCount;

	//generate JSON encoded records.
	$requestResponse = '{"recordCount": "' . $rerievedRecordCount . '", "records": ' . $retreivedRecords .'}';
	echo $requestResponse;
	exit();

} else {
	unset($_SESSION['map_current_query']);
	unset($_SESSION['map_retrieved_record_count']);
}

header('Content-Type: text/html; charset=' . $CHARSET);
ob_start('ob_gzhandler');
ini_set('max_execution_time', 180); //180 seconds = 3 minutes



$mapManager = OccurrenceMapManager::init();
$searchVar = $mapManager->getQueryTermStr();

if ($searchVar && $recLimit) $searchVar .= '&reclimit=' . $recLimit;

$obsIDs = $mapManager->getObservationIds();




$activateGeolocation = 0;
if (isset($ACTIVATE_GEOLOCATION) && $ACTIVATE_GEOLOCATION == 1) $activateGeolocation = 1;

$bound_NorthEast = ['41.0','-95.0'];
$bound_SouthWest = [];

if (isset($MAPPING_BOUNDARIES) && $MAPPING_BOUNDARIES) {
	$coorArr = explode(";", $MAPPING_BOUNDARIES);
	if ($coorArr && count($coorArr) == 4) {
		$bound_NorthEast = [$coorArr[0],$coorArr[1]];
		$bound_SouthWest = [$coorArr[2],$coorArr[3]];
	}
}
else {
	$bound_SouthWest = $bound_NorthEast;
}


?>
<html>

<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $DEFAULT_TITLE; ?> - Map Interface</title>
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	include_once($SERVER_ROOT . '/includes/googleanalytics.php');
	?>
	<link href="<?php echo $CLIENT_ROOT; ?>/css/symb/collection.css" type="text/css" rel="stylesheet" />
	<style type="text/css">
		.ui-front {
			z-index: 9999999 !important;
		}

		/* The sidepanel menu */
		.sidepanel {
			resize: horizontal;
			border-left: 2px, solid, black;
			height: 100%;
			width: 380;
			position: fixed;
			z-index: 20;
			top: 0;
			left: 0;
			background-color: #ffffff;
			overflow: hidden;
			transition: 0.5s;
		}

		.selectedrecord{
			border: solid thick greenyellow;
			font-weight: bold;
		}

		input[type=color]{
			border: none;
			background: none;
		}
		input[type="color"]::-webkit-color-swatch-wrapper {
			padding: 0;
		}
		input[type="color"]::-webkit-color-swatch {
			border: solid 1px #000; /*change color of the swatch border here*/
		}

		.small_color_input{
			margin: 0,0,-2px,0;
			height: 16px;
			width: 16px;
		}

		.mapGroupLegend{
			list-style-type: none;
  			margin: 0;
  			padding: 0;
		}

		.mapLegendEntry {
			display: grid;
			grid-template-columns: max-content auto;
		}

		.mapLegendEntryInputs {
			grid-column: 1;
		}

		.mapLegendEntryText {
			grid-column: 2;
		}

		table#mapSearchRecordsTable.styledtable tr:nth-child(odd) td{
			background-color: #ffffff;
		}

		#divMapSearchRecords{
			grid-column: 1;
			height: 100%;
		}
		#mapLoadMoreRecords{
			display: none;
		}

		#tabs2Items{
			grid-column: 1;
		}

		#records{
			display: grid;
    		grid-template-columns:	1;
			grid-auto-rows: minmax(min-content, max-content);
			height: 100%;
		}

		#mapSearchDownloadData {
			grid-column: 1;
		}

		#mapSearchRecordsTable {

			font-family:Arial;
			font-size:12px;
		}

		#mapSearchRecordsTable th {
			top: 0;
			position: sticky;
		}

		#tabs2 {
			display:none;
			padding:0px;
			display: block;
			height: 100%;
			/* overflow: scroll; */
		}

	</style>
	<script src="../../js/jquery-3.6.0.min.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
	<link href="../../js/jquery-ui/jquery-ui.min.css" type="text/css" rel="Stylesheet" />
	<script src="//maps.googleapis.com/maps/api/js?v=quarterly&libraries=drawing,visualization<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY ? '&key=' . $GOOGLE_MAP_KEY : ''); ?>" defer></script>
	<script src="../../js/symb/collections.map.index_gm.js?ver=2" type="text/javascript"></script>
	<script src="../../js/symb/collections.map.index_ui.js?ver=2" type="text/javascript"></script>
	<script src="../../js/symb/collections.list.js?ver=1" type="text/javascript"></script>
	<script src="../../js/googlemaps/index.dev.js"></script>
	<script src="../../js/symb/oms.min.js" type="text/javascript"></script>
	<script src="../../js/symb/api.taxonomy.taxasuggest.js?ver=4" type="text/javascript"></script>
</head>

<body style='width:100%;max-width:100%;min-width:500px;' <?php echo (!$activateGeolocation ? 'onload="initialize()"' : ''); ?>>
	<div>
		<div>
			<button onclick="openNav()" style="position:absolute;top:0;left:0;margin:0px;z-index:10;font-size: 14px;">&#9776; <b>Open Search Panel</b></button>
		</div>
		<div id="defaultpanel" class="sidepanel">
			<button type="button" onclick="closeNav()" style="float:right;top:5px;right:5px;margin:1px;padding:3px;z-index:10;font-weight:bold">&lt;&lt;</button>
			<div id="maptopoptions" style="clear:right;">

				<fieldset>
					<legend>Display Mode:</legend>
					<label for="modePoints">Markers</label><input type="radio" id="modePoints" name="markerDsiplayMode" onclick="handleModeRadios(this);" value="points">
					<label for="modeCluster">Cluster</label><input type="radio" id="modeCluster" name="markerDsiplayMode" onclick="handleModeRadios(this);" value="cluster">
					<label for="modeHeat">Heat Map</label><input type="radio" id="modeHeat" name="markerDsiplayMode" onclick="handleModeRadios(this);" value="heat">
					<br><a href="#" onclick="activateMapOtions();">View map options</a>
				</div>

			<div id="accordion">
				<?php
				/*
			echo "MySQL Version: ".$mysqlVersion;
			echo "Request: ".json_encode($_REQUEST);
			echo "mapWhere: ".$mapWhere;
			echo "coordArr: ".json_encode($coordArr);
			echo "clusteringOff: ".$clusterOff;
			echo "coordArr: ".$coordArr;
			echo "tIdArr: ".json_encode($tIdArr);
			echo "minLat:".$minLat."maxLat:".$maxLat."minLng:".$minLng."maxLng:".$maxLng;
			*/
				?>
				<h3 style="padding-left:30px;"><?php echo (isset($LANG['SEARCH_CRITERIA']) ? $LANG['SEARCH_CRITERIA'] : 'Search Criteria and Options'); ?></h3>
				<div id="tabs1" style="width:379px;padding:0px;">
					<form name="mapsearchform" id="mapsearchform" action="index.php" method="post" onsubmit="return verifyCollForm(this);">
						<ul>
							<li><a href="#searchcollections"><span><?php echo (isset($LANG['COLLECTIONS']) ? $LANG['COLLECTIONS'] : 'Collections'); ?></span></a></li>
							<li><a href="#searchcriteria"><span><?php echo (isset($LANG['CRITERIA']) ? $LANG['CRITERIA'] : 'Criteria'); ?></span></a></li>
							<li><a href="#mapoptions"><span><?php echo (isset($LANG['MAP_OPTIONS']) ? $LANG['MAP_OPTIONS'] : 'Map Options'); ?></span></a></li>
						</ul>
						<div id="searchcollections" >
							<div class="mapinterface">
								<?php
								$collList = $mapManager->getFullCollectionList($catId);
								$specArr = (isset($collList['spec']) ? $collList['spec'] : null);
								$obsArr = (isset($collList['obs']) ? $collList['obs'] : null);
								if ($specArr || $obsArr) {
								?>
									<div id="specobsdiv">
										<div style="margin:0px 0px 10px 5px;">
											<input id="dballcb" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this);" <?php echo (!$mapManager->getSearchTerm('db') || $mapManager->getSearchTerm('db') == 'all' ? 'checked' : '') ?> />
											<?php echo $LANG['SELECT_DESELECT'] . ' <a href="misc/collprofiles.php">' . $LANG['ALL_COLLECTIONS'] . '</a>'; ?>
										</div>
										<?php
										if ($specArr) {
											$mapManager->outputFullCollArr($specArr, $catId, false, false);
										}
										if ($specArr && $obsArr) echo '<hr style="clear:both;margin:20px 0px;"/>';
										if ($obsArr) {
											$mapManager->outputFullCollArr($obsArr, $catId, false, false);
										}
										?>
										<div style="clear:both;">&nbsp;</div>
									</div>
								<?php
								}
								?>
							</div>
						</div>
						<div id="searchcriteria" >
							<div style="height:25px;">

								<div style="float:right;">
									<input type="hidden" id="selectedpoints" value="" />
									<input type="hidden" id="deselectedpoints" value="" />
									<input type="hidden" id="selecteddspoints" value="" />
									<input type="hidden" id="deselecteddspoints" value="" />

									<?php
									$pointArr = explode(';', $mapManager->getSearchTerm('llpoint'));
									$pointLat = (isset($pointArr[0]) ? $pointArr[0] : '');
									$pointLong = (isset($pointArr[1]) ? $pointArr[1] : '');
									$pointRadius = (isset($pointArr[2]) ? $pointArr[2] : '');
									?>
									<input type="hidden" id="pointlat" name="pointlat" value='<?php echo $pointLat; ?>' />
									<input type="hidden" id="pointlong" name="pointlong" value='<?php echo $pointLong; ?>' />
									<input type="hidden" id="radius" name="radius" value='<?php echo $pointRadius; ?>' />
									<?php
									$boundArr = explode(';', $mapManager->getSearchTerm('llbound'));
									$upperLat = (isset($boundArr[0]) ? $boundArr[0] : '');
									$bottomLat = (isset($boundArr[1]) ? $boundArr[1] : '');
									$leftLong = (isset($boundArr[2]) ? $boundArr[2] : '');
									$rightLong = (isset($boundArr[3]) ? $boundArr[3] : '');
									?>
									<input type="hidden" id="upperlat" name="upperlat" value='<?php echo $upperLat; ?>' />
									<input type="hidden" id="rightlong" name="rightlong" value='<?php echo $rightLong; ?>' />
									<input type="hidden" id="bottomlat" name="bottomlat" value='<?php echo $bottomLat; ?>' />
									<input type="hidden" id="leftlong" name="leftlong" value='<?php echo $leftLong; ?>' />
									<input type="hidden" id="polycoords" name="polycoords" value='<?php echo $mapManager->getSearchTerm('polycoords'); ?>' />
									<button type="button" name="resetbutton" onclick="resetQueryForm(this.form)"><?php echo (isset($LANG['RESET']) ? $LANG['RESET'] : 'Reset'); ?></button>
									<button type="submit" name="submitform"><?php echo (isset($LANG['SEARCH']) ? $LANG['SEARCH'] : 'Search'); ?></button>
								</div>
							</div>
							<div style="margin:5 0 5 0;">
								<hr />
							</div>
							<div>
								<label for="recordlimit"><?php echo (isset($LANG['MAP_RECORD_LIMIT']) ? $LANG['MAP_RECORD_LIMIT'] : 'Maximum number of records to retreive:'); ?></label>
								<input id="recordlimit" name="recordlimit" type="number" step="1" min="1000" value=<?php echo ($recLimit ? $recLimit : ""); ?> onchange="return checkRecordLimit(this.form);"/>
							</div>
							<div style="margin:5 0 5 0;">
								<hr />
							</div>
							<div>
								<span ><input type="checkbox" name="usethes" value="1" <?php if ($mapManager->getSearchTerm('usethes') || !$submitForm) echo "CHECKED"; ?>><?php echo (isset($LANG['INCLUDE_SYNONYMS']) ? $LANG['INCLUDE_SYNONYMS'] : 'Include Synonyms'); ?></span>
							</div>
							<div>
								<div style="margin-top:5px;">
									<select id="taxontype" name="taxontype">
										<?php
										$taxonType = 2;
										if (isset($DEFAULT_TAXON_SEARCH) && $DEFAULT_TAXON_SEARCH) $taxonType = $DEFAULT_TAXON_SEARCH;
										if ($mapManager->getSearchTerm('taxontype')) $taxonType = $mapManager->getSearchTerm('taxontype');
										for ($h = 1; $h < 6; $h++) {
											echo '<option value="' . $h . '" ' . ($taxonType == $h ? 'SELECTED' : '') . '>' . $LANG['SELECT_1-' . $h] . '</option>';
										}
										?>
									</select>
								</div>
								<div style="margin-top:5px;">
									<?php echo (isset($LANG['TAXA']) ? $LANG['TAXA'] : 'Taxa'); ?>:
									<input id="taxa" name="taxa" type="text" style="width:275px;" value="<?php echo $mapManager->getTaxaSearchTerm(); ?>" title="<?php echo (isset($LANG['SEPARATE_MULTIPLE']) ? $LANG['SEPARATE_MULTIPLE'] : 'Separate multiple taxa w/ commas'); ?>" />
								</div>
							</div>
							<div style="margin:5 0 5 0;">
								<hr />
							</div>
							<?php
							if ($mapManager->getSearchTerm('clid')) {
							?>
								<div>
									<div style="clear:both;text-decoration: underline;">Species Checklist:</div>
									<div style="clear:both;margin:5px 0px">
										<?php echo $mapManager->getClName(); ?><br />
										<input type="hidden" id="checklistname" name="checklistname" value="<?php echo $mapManager->getClName(); ?>" />
										<input id="clid" name="clid" type="hidden" value="<?php echo $mapManager->getSearchTerm('clid'); ?>" />
									</div>
									<div style="clear:both;margin-top:5px;">
										<div style="float:left">
											Display:
										</div>
										<div style="float:left;margin-left:10px;">
											<input name="cltype" type="radio" value="all" <?php if ($mapManager->getSearchTerm('cltype') == 'all') echo 'checked'; ?> />
											all specimens within polygon<br />
											<input name="cltype" type="radio" value="vouchers" <?php if (!$mapManager->getSearchTerm('cltype') || $mapManager->getSearchTerm('cltype') == 'vouchers') echo 'checked'; ?> />
											vouchers only
										</div>
										<div style="clear: both"></div>
									</div>
								</div>
								<div style="clear:both;margin:0 0 5 0;">
									<hr />
								</div>
							<?php
							}
							?>
							<div>
								<?php echo (isset($LANG['COUNTRY']) ? $LANG['COUNTRY'] : 'Country'); ?>: <input type="text" id="country" style="width:225px;" name="country" value="<?php echo $mapManager->getSearchTerm('country'); ?>" title="<?php echo (isset($LANG['SEPARATE_MULTIPLE']) ? $LANG['SEPARATE_MULTIPLE'] : 'Separate multiple taxa w/ commas'); ?>" />
							</div>
							<div style="margin-top:5px;">
								<?php echo (isset($LANG['STATE']) ? $LANG['STATE'] : 'State/Province'); ?>: <input type="text" id="state" style="width:150px;" name="state" value="<?php echo $mapManager->getSearchTerm('state'); ?>" title="<?php echo (isset($LANG['SEPARATE_MULTIPLE']) ? $LANG['SEPARATE_MULTIPLE'] : 'Separate multiple taxa w/ commas'); ?>" />
							</div>
							<div style="margin-top:5px;">
								<?php echo (isset($LANG['COUNTY']) ? $LANG['COUNTY'] : 'County'); ?>: <input type="text" id="county" style="width:225px;" name="county" value="<?php echo $mapManager->getSearchTerm('county'); ?>" title="<?php echo (isset($LANG['SEPARATE_MULTIPLE']) ? $LANG['SEPARATE_MULTIPLE'] : 'Separate multiple taxa w/ commas'); ?>" />
							</div>
							<div style="margin-top:5px;">
								<?php echo (isset($LANG['LOCALITY']) ? $LANG['LOCALITY'] : 'Locality'); ?>: <input type="text" id="locality" style="width:225px;" name="local" value="<?php echo $mapManager->getSearchTerm('local'); ?>" />
							</div>
							<div style="margin:5 0 5 0;">
								<hr />
							</div>
							<div id="shapecriteria">
								<div id="noshapecriteria" style="display:<?php echo ((!$mapManager->getSearchTerm('polycoords') && !$mapManager->getSearchTerm('upperlat')) ? 'block' : 'none'); ?>;">
									<div id="geocriteria" style="display:<?php echo ((!$mapManager->getSearchTerm('polycoords') && !$distFromMe && !$mapManager->getSearchTerm('pointlat') && !$mapManager->getSearchTerm('upperlat')) ? 'block' : 'none'); ?>;">
										<div>
											<?php echo (isset($LANG['SHAPE_TOOLS']) ? $LANG['SHAPE_TOOLS'] : 'Use the shape tools on the map to select occurrences within a given shape'); ?>.
										</div>
									</div>
									<div id="distancegeocriteria" style="display:<?php echo ($distFromMe ? 'block' : 'none'); ?>;">
										<div>
											<?php echo (isset($LANG['WITHIN']) ? $LANG['WITHIN'] : 'Within'); ?>
											<input type="text" id="distFromMe" style="width:40px;" name="distFromMe" value="<?php $distFromMe; ?>" /> miles from me, or
											<?php echo (isset($LANG['SHAPE_TOOLS']) ? strtolower($LANG['SHAPE_TOOLS']) : 'use the shape tools on the map to select occurrences within a given shape'); ?>.
										</div>
									</div>
								</div>
								<div id="polygeocriteria" style="display:<?php echo (($mapManager->getSearchTerm('polycoords')) ? 'block' : 'none'); ?>;">
									<div>
										<?php echo (isset($LANG['WITHIN_POLYGON']) ? $LANG['WITHIN_POLYGON'] : 'Within the selected polygon'); ?>.
									</div>
								</div>
								<div id="circlegeocriteria" style="display:<?php echo (($mapManager->getSearchTerm('pointlat') && !$distFromMe) ? 'block' : 'none'); ?>;">
									<div>
										<?php echo (isset($LANG['WITHIN_CIRCLE']) ? $LANG['WITHIN_CIRCLE'] : 'Within the selected circle'); ?>.
									</div>
								</div>
								<div id="rectgeocriteria" style="display:<?php echo ($mapManager->getSearchTerm('upperlat') ? 'block' : 'none'); ?>;">
									<div>
										<?php echo (isset($LANG['WITHIN_RECTANGLE']) ? $LANG['WITHIN_RECTANGLE'] : 'Within the selected rectangle'); ?>.
									</div>
								</div>
								<div id="deleteshapediv" style="margin-top:5px;display:<?php echo (($mapManager->getSearchTerm('pointlat') || $mapManager->getSearchTerm('upperlat') || $mapManager->getSearchTerm('polycoords')) ? 'block' : 'none'); ?>;">
									<button type=button onclick="deleteSelectedShape()"><?php echo (isset($LANG['DELETE_SHAPE']) ? $LANG['DELETE_SHAPE'] : 'Delete Selected Shape'); ?></button>
								</div>
							</div>
							<div style="margin:5 0 5 0;">
								<hr />
							</div>
							<div>
								<?php echo (isset($LANG['COLLECTOR_LASTNAME']) ? $LANG['COLLECTOR_LASTNAME'] : "Collector's Last Name"); ?>:
								<input type="text" id="collector" style="width:125px;" name="collector" value="<?php echo $mapManager->getSearchTerm('collector'); ?>" title="" />
							</div>
							<div style="margin-top:5px;">
								<?php echo (isset($LANG['COLLECTOR_NUMBER']) ? $LANG['COLLECTOR_NUMBER'] : "Collector's Number"); ?>:
								<input type="text" id="collnum" style="width:125px;" name="collnum" value="<?php echo $mapManager->getSearchTerm('collnum'); ?>" title="Separate multiple terms by commas and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750" />
							</div>
							<div style="margin-top:5px;">
								<?php echo (isset($LANG['COLLECTOR_DATE']) ? $LANG['COLLECTOR_DATE'] : 'Collection Date'); ?>:
								<input type="text" id="eventdate1" style="width:80px;" name="eventdate1" style="width:100px;" value="<?php echo $mapManager->getSearchTerm('eventdate1'); ?>" title="Single date or start date of range" /> -
								<input type="text" id="eventdate2" style="width:80px;" name="eventdate2" style="width:100px;" value="<?php echo $mapManager->getSearchTerm('eventdate2'); ?>" title="End date of range; leave blank if searching for single date" />
							</div>
							<div style="margin:10 0 10 0;">
								<hr>
							</div>
							<div>
								<?php echo (isset($LANG['CATALOG_NUMBER']) ? $LANG['CATALOG_NUMBER'] : 'Catalog Number'); ?>:
								<input type="text" id="catnum" style="width:150px;" name="catnum" value="<?php echo $mapManager->getSearchTerm('catnum'); ?>" title="" />
							</div>
							<div style="margin-left:15px;">
								<input name="includeothercatnum" type="checkbox" value="1" checked /> <?php echo (isset($LANG['INCLUDE_OTHER_CATNUM']) ? $LANG['INCLUDE_OTHER_CATNUM'] : 'Include other catalog numbers and GUIDs') ?>
							</div>
							<div style="margin-top:10px;">
								<input type='checkbox' name='typestatus' value='1' <?php if ($mapManager->getSearchTerm('typestatus')) echo "CHECKED"; ?>>
								<?php echo (isset($LANG['LIMIT_TO_TYPE']) ? $LANG['LIMIT_TO_TYPE'] : 'Limit to Type Specimens Only'); ?>
							</div>
							<div style="margin-top:5px;">
								<input type='checkbox' name='hasimages' value='1' <?php if ($mapManager->getSearchTerm('hasimages')) echo "CHECKED"; ?>>
								<?php echo (isset($LANG['LIMIT_IMAGES']) ? $LANG['LIMIT_IMAGES'] : 'Limit to Specimens with Images Only'); ?>
							</div>
							<div style="margin-top:5px;">
								<input type='checkbox' name='hasgenetic' value='1' <?php if ($mapManager->getSearchTerm('hasgenetic')) echo "CHECKED"; ?>>
								<?php echo (isset($LANG['LIMIT_GENETIC']) ? $LANG['LIMIT_GENETIC'] : 'Limit to Specimens with Genetic Data Only'); ?>
							</div>
							<div style="margin-top:5px;">
								<input type='checkbox' name='includecult' value='1' <?php if ($mapManager->getSearchTerm('includecult')) echo "CHECKED"; ?>>
								<?php echo (isset($LANG['INCLUDE_CULTIVATED']) ? $LANG['INCLUDE_CULTIVATED'] : 'Include cultivated/captive specimens'); ?>
							</div>
							<div>
								<hr>
							</div>
							<input type="hidden" name="reset" value="1" />
						</div>
					</form>
					<div id="mapoptions" >
						<fieldset id="map_options_fs">
							<legend><?php echo (isset($LANG['MAPSEARCH_DEFAULTS']) ? $LANG['MAPSEARCH_DEFAULTS'] : 'Map Search Defaults' ); ?></legend>
							<label for="defaultmarkercolor"><?php echo (isset($LANG['MAPSEARCH_MARKER_COLOR']) ? $LANG['MAPSEARCH_MARKER_COLOR'] : 'Default Marker Color'); ?>:<label>
							<input class="small_color_input" name="defaultmarkercolor" id="defaultmarkercolor" type="color" onchange="resetSymbology();" />
						</fieldset>
						<fieldset id="cluster_options_fs">
						<legend><?php echo (isset($LANG['CLUSTERING']) ? $LANG['CLUSTERING'] : 'Clustering Options'); ?></legend>
						</fieldset>
						<fieldset id="heatmap_options_fs">
							<legend><?php echo (isset($LANG['MAPSEARCH_HEATMAP_OPTIONS']) ? $LANG['MAPSEARCH_HEATMAP_OPTIONS'] : 'Heatmap Options'); ?></legend>
							<div style="margin-top:8px;">
							<label><input name="heatmap_dissipating" type="checkbox" onchange="heatmap_dissipating(this);" checked>Dissipate with zoom level</label>
							<label><input type="number" min="0" step="1" name="heatmap_maxIntensity" onchange="heatmep_changeMaxIntensity(this.value);">Maximum Intensity</label>
							<label><input type="number" min="0" step="1" name="heatmap_radius" onchange="heatmep_changeRadius(this.value);">Radius</label>
							<label><input type="number" name="heatmap_opacity" value="0.60" min="0.10" max="1" step="0.05" onchange="heatmep_changeOpacity(this.value);">Opacity</label>
						</fieldset>
						<?php
						if (true) {
						?>
							<div style="clear:both;">
								<div style="float:right;margin-top:10px;">
									<button id="refreshCluster" name="refreshCluster" onclick=""><?php echo (isset($LANG['REFRESH_MAP']) ? $LANG['REFRESH_MAP'] : 'Refresh Map'); ?></button>
								</div>
							</div>
						<?php
						}
						?>
					</div>
					<form style="display:none;" name="csvcontrolform" id="csvcontrolform" action="csvdownloadhandler.php" method="post" onsubmit="">
						<input name="selectionscsv" id="selectionscsv" type="hidden" value="" />
						<input name="starrcsv" id="starrcsv" type="hidden" value="" />
						<input name="typecsv" id="typecsv" type="hidden" value="" />
						<input name="schema" id="schemacsv" type="hidden" value="" />
						<input name="identifications" id="identificationscsv" type="hidden" value="" />
						<input name="images" id="imagescsv" type="hidden" value="" />
						<input name="format" id="formatcsv" type="hidden" value="" />
						<input name="cset" id="csetcsv" type="hidden" value="" />
						<input name="zip" id="zipcsv" type="hidden" value="" />
						<input name="csvreclimit" id="csvreclimit" type="hidden" value="<?php echo $recLimit; ?>" />
					</form>
				</div>
				<?php
				if ($searchVar) {
				?>
					<h3 id="recordstaxaheader" style="display:none;padding-left:30px;"><?php echo (isset($LANG['RECORDS_TAXA']) ? $LANG['RECORDS_TAXA'] : 'Records and Taxa'); ?></h3>
					<div id="tabs2">
						<div id="tabs2Items">
							<ul>
								<li><a href='#records'><span><?php echo (isset($LANG['RECORDS']) ? $LANG['RECORDS'] : 'Records'); ?></span></a></li>
								<li><a href='#symbology'><span><?php echo (isset($LANG['COLLECTIONS']) ? $LANG['COLLECTIONS'] : 'Collections'); ?></span></a></li>
								<li><a href='#maptaxalist'><span><?php echo (isset($LANG['TAXA_LIST']) ? $LANG['TAXA_LIST'] : 'Taxa List'); ?></span></a></li>
							</ul>
						</div>
						<div id="records" >
						</div>
						<div id="symbology" >
							<div style="height:40px;margin-bottom:15px;">
								<?php
								if ($obsIDs) {
								?>
									<div style="float:left;">
										<div>
											<svg xmlns="http://www.w3.org/2000/svg" style="height:15px;width:15px;margin-bottom:-2px;">">
												<g>
													<circle cx="7.5" cy="7.5" r="7" fill="white" stroke="#000000" stroke-width="1px"></circle>
												</g>
											</svg> = <?php echo (isset($LANG['COLLECTION']) ? $LANG['COLLECTION'] : 'Collection'); ?>
										</div>
										<div style="margin-top:5px;">
											<svg style="height:14px;width:14px;margin-bottom:-2px;">" xmlns="http://www.w3.org/2000/svg">
												<g>
													<path stroke="#000000" d="m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z" stroke-width="1px" fill="white" />
												</g>
											</svg> = <?php echo (isset($LANG['OBSERVATION']) ? $LANG['OBSERVATION'] : 'Observation'); ?>
										</div>
									</div>
								<?php
								}
								?>
								<div id="symbolizeResetButt" style='float:right;margin-bottom:5px;'>
									<div>
										<button id="symbolizeReset1" name="symbolizeReset1" onclick='resetSymbology();'><?php echo (isset($LANG['RESET_SYMBOLOGY']) ? $LANG['RESET_SYMBOLOGY'] : 'Reset Symbology'); ?></button>
									</div>
									<div style="margin-top:5px;">
										<button id="randomColorColl" name="randomColorColl" onclick='autoColorColl();'><?php echo (isset($LANG['AUTO_COLOR']) ? $LANG['AUTO_COLOR'] : 'Auto Color'); ?></button>
									</div>
								</div>
							</div>
							<div style="margin:5 0 5 0;clear:both;">
								<hr />
							</div>
							<div >
								<div style="margin-top:8px;">
									<div style="display:table;">
										<div id="symbologykeysbox"></div>
									</div>
								</div>
							</div>
						</div>
						<div id="maptaxalist">
							<div style="height:40px;margin-bottom:15px;">
								<?php
								if ($obsIDs) {
								?>
									<div style="float:left;">
										<div>
											<svg xmlns="http://www.w3.org/2000/svg" style="height:15px;width:15px;margin-bottom:-2px;">">
												<g>
													<circle cx="7.5" cy="7.5" r="7" fill="white" stroke="#000000" stroke-width="1px"></circle>
												</g>
											</svg> = <?php echo (isset($LANG['COLLECTION']) ? $LANG['COLLECTION'] : 'Collection'); ?>
										</div>
										<div style="margin-top:5px;">
											<svg style="height:14px;width:14px;margin-bottom:-2px;">" xmlns="http://www.w3.org/2000/svg">
												<g>
													<path stroke="#000000" d="m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z" stroke-width="1px" fill="white" />
												</g>
											</svg> = <?php echo (isset($LANG['OBSERVATION']) ? $LANG['OBSERVATION'] : 'Observation'); ?>
										</div>
									</div>
								<?php
								}
								?>
								<div id="symbolizeResetButt" style='float:right;margin-bottom:5px;'>
									<div>
										<button id="symbolizeReset2" name="symbolizeReset2" onclick='resetSymbology();'><?php echo (isset($LANG['RESET_SYMBOLOGY']) ? $LANG['RESET_SYMBOLOGY'] : 'Reset Symbology'); ?></button>
									</div>
									<div style="margin-top:5px;">
										<button id="randomColorTaxa" name="randomColorTaxa" onclick='autoColorTaxa();'><?php echo (isset($LANG['AUTO_COLOR']) ? $LANG['AUTO_COLOR'] : 'Auto Color'); ?></button>
									</div>
								</div>
							</div>
							<div style="margin:5 0 5 0;clear:both;">
								<hr />
							</div>
							<div style='font-weight:bold;'><?php echo (isset($LANG['TAXA_COUNT']) ? $LANG['TAXA_COUNT'] : 'Taxa Count'); ?>: <span id="taxaCountNum">0</span></div>
							<div id="taxasymbologykeysbox"></div>
						</div>
					</div>
				<?php
				}
				?>
			</div>
		</div><!-- /defaultpanel -->
	</div>
	<div id="loadingOverlay" style="width:100%; z-index:999999;">
		<div id="loadingImage" style="width:100px;height:100px;position:absolute;top:50%;left:50%;margin-top:-50px;margin-left:-50px;">
			<img style="border:0px;width:100px;height:100px;" src="../../images/ajax-loader.gif" />
		</div>
	</div>
	<div id='map' style='width:100%;height:100%;'></div>

	<script defer type="text/javascript">

		var clientRoot = "<?php echo $CLIENT_ROOT; ?>";

		$(document).ready(function() {
			<?php
			if ($searchVar) echo 'sessionStorage.querystr = "' . $searchVar . '";';
			?>
		});

		var map;
		var heatmap;
		var pointObj;
		var initBoundsNorthEast = <?php echo json_encode($bound_NorthEast); ?>;
		var initBoundsSouthWest = <?php echo json_encode($bound_SouthWest); ?>;
		var defaultMarkerColor = '#f6ae77';
		var markerCluster = null;
		//var infoWins = [];
		//var puWin;
		//var markers = [];
		//var dsmarkers = [];
		//var dsoccids = [];
		//var selections = [];
		//var dsselections = [];
		//var selectedds = '';
		//var selecteddsrole = '';
		var allMarkers = [];
		var drawingManager = null;
		var spiderfier;
		var selectedShape = null;
		var gotCoords = <?php echo ($activateGeolocation ? 'true' : 'false'); ?>;
		var mapSymbol = 'coll';
		var selected = false;
		var deselected = false;
		var positionFound = false;
		var clid = '<?php echo ($mapManager->getSearchTerm('clid') ? $mapManager->getSearchTerm('clid') : 0); ?>';
		var obsIDs = JSON.parse('<?php echo json_encode($obsIDs); ?>');
		var MarkerGroupings = []; //stores all marker groupings
		var markerArr = [];
		var tidArr = [];
		var taxaLegendArr = [];
		var taxaCnt = 0;
		var collKeyArr = []; // stores HTML elements of the Group by Collection key
		var collNameArr = [];
		var familyNameArr = []; //populated with list of Families represented in the results and used to group taxa on the map legend/key
		var htmlTidArr = []; //maps html id to taxa group
		var taxaKeyArr = []; //stores HTML elements of the Group by Taxanomy key
		var clusterCollArr = [];
		var clusterTaxArr = [];
		var optionsCollArr = [];
		var optionsTaxArr = [];
		var grpCnt = 1;
		var InformationWindow = '';
		//var mouseoverTimeout = '';
		//var mouseoutTimeout = '';
		var pointBounds = null;
		//var occArr = [];
		var panPoint = null;
		var heatMapData = null;
		var displayMode = 'cluster';
		var taxaKeySet = new Set();
		var recordsArr = [];
		var resultCount = 0;
		var retrievedRecordCount = 0;
		var totalRecordCount = 0;

		function initialize(){
			document.getElementById('defaultmarkercolor').value = defaultMarkerColor;
			<?php
			$coordArr = $mapManager->getCoordinateMap2(0,$recLimit);
			//$coordArr = $mapManager->getCoordinateMap2(0, 50000);
			$totalRecordCnt = $mapManager->getTotalRecordCnt();
			$_SESSION['map_total_record_count'] = $totalRecordCnt;
			$retrievedRecordCnt = $mapManager->getRetrievedRecordCnt();
			$_SESSION['map_retrieved_record_count'] = $retrievedRecordCnt;
			if ($searchVar) {
				?>
				totalRecordCount  = <?php echo $totalRecordCnt; ?>;
				retrievedRecordCount = <?php echo $retrievedRecordCnt; ?>;
				if (totalRecordCount < 1) {
					alert('There were no records matching your query.');
				}
				else {
					<?php
					echo 'pointObj = ' . json_encode($coordArr) . ";\n";
					?>
				}

				<?php
			}
			?>
			initializeGoogleMap();
			if (pointObj){
				processPoints();
			}
			loadSidePanel();
			handleModeRadios({
				value: 'points',
			});


			if (totalRecordCount > retrievedRecordCount) {
					alert("Your search exceeds the current maximum of <?php echo $recLimit; ?> records. Not all records will be shown on the map. Press the 'Load more records' to add additional matching records.");
					document.getElementById('mapLoadMoreRecords').style.display = 'block';
			}
			document.getElementById('recordTotalCount').textContent = totalRecordCount;
			document.getElementById('recordLoadedCount').textContent = retrievedRecordCount;
			document.getElementById('loadingOverlay').style.display = 'none';
		}



		function loadSidePanel() {
			if (pointObj) {
				setPanels(true);
				$("#accordion").accordion("option", {
					active: 1
				});
				buildCollKey();
				buildTaxaKey();
				buildRecordsTable();
				document.getElementById("mapLoadMoreRecords").addEventListener("click", function(){
					requestMoreRecords();
				});
				recordsArr = [];
				if (!pointBounds.isEmpty) {
					map.fitBounds(pointBounds);
					map.panToBounds(pointBounds);
				}
			}

			switch(displayMode){
				case 'points':
					document.getElementById('modePoints').checked = true;
				break;
				case 'cluster':
					document.getElementById('modeCluster').checked = true;
				break;
				case 'heat':
					document.getElementById('modeHeat').checked = true;
				break;
			}


		}

		function processPoints() {

			taxaKeySet.clear();

			//setup map marker data structure
			if (MarkerGroupings.indexOf('Taxa') < 0){
				MarkerGroupings['Taxa'] = [];
			}
			if (MarkerGroupings.indexOf('Collections') < 0){
				MarkerGroupings['Collections'] = [];
			}
			var iconColor = document.getElementById('defaultmarkercolor').value;
			for (let i = 0; i < pointObj.length; i++) {

				var markerIcon = null;

				//create collection based marker legend
				buildCollKeyPiece(pointObj[i],iconColor);

				//create taxonomy based marker legend

				var tempFamArr = [];
				var scinameStr = '';
				//The scinameStr value is used for DIV element id

				if (pointObj[i]['tidinterpreted'] && pointObj[i]['tidinterpreted'] != 'NULL'){
					scinameStr = pointObj[i]['sciname']+pointObj[i]['tidinterpreted'];
				}
				else scinameStr = pointObj[i]['sciname'];

				scinameStr = scinameStr.replace(/[ .]/g, "").toLowerCase();

				if (!taxaKeySet.has(scinameStr)){
					taxaKeySet.add(scinameStr)

					var family = '';
					if(!pointObj[i]['family']){
						family = 'NULL'
					}
					else family = pointObj[i]['family']
					family = family.toUpperCase();
					buildTaxaKeyPiece(scinameStr, pointObj[i]['tidinterpreted'], family, pointObj[i]['sciname'],iconColor);
				}

				//set marker icon based on record type
				if (pointObj[i]["collType"].includes('Observations')){
					type = 'obs';
					markerIcon = {
						url: '<?= $CLIENT_ROOT?>/collections/map/coloricon.php?shape=triangle&color=' + iconColor,
						scaledSize: new google.maps.Size(18, 18),
					}
				}
				else {
					type = 'spec';
					markerIcon = {
						url: '<?= $CLIENT_ROOT?>/collections/map/coloricon.php?shape=circle&color=' + iconColor,
						scaledSize: new google.maps.Size(18, 18),
					}

				}

				var identifier = pointObj[i]['recordedby'];
				if (pointObj[i]['recordnumber']){
					identifier += ' ' +pointObj[i]['recordnumber'];
				}
				else identifier += ' ' + pointObj[i]['eventdate']

				//Create Marker & Add occurance data to Marker Object

				var m = new google.maps.Marker({
					position: new google.maps.LatLng(pointObj[i]["DecimalLatitude"], pointObj[i]["DecimalLongitude"]),
					optimized: true,
					text: "Collection: " + pointObj[i]["CollectionName"] + " Collector: " + identifier,
					icon: markerIcon,
					selected: false,
					color: iconColor,
					taxaKeyHtmlID: scinameStr,
					recordType: type,
					collid: pointObj[i]['collid'],
					identifier: identifier,
					taxatid: pointObj[i]['sciname'],
					tidinterpreted: pointObj[i]['tidinterpreted'],
					family: family,
					occid: pointObj[i]['occid'],
					clid: 0,
				});
				allMarkers.push(m)
				if (!MarkerGroupings['Taxa'][scinameStr]) MarkerGroupings['Taxa'][scinameStr] = [];
				MarkerGroupings['Taxa'][scinameStr].push(m);
				if (!MarkerGroupings['Collections'][pointObj[i]['collid']]) MarkerGroupings['Collections'][pointObj[i]['collid']] = [];
				MarkerGroupings['Collections'][pointObj[i]['collid']].push(m);
				heatMapData.push(m.getPosition());

				// Add marker listener
				m.addListener('spider_click', function() {

					// prevent multiple information windows from opening at the same time
					if(InformationWindow){
						InformationWindow.close();
					}

					panPoint = this.position;
					var myOptions = {
						content: '<div>' + this.text + '<br /><a href="#" onclick="openIndPopup(' + this.occid + ',' + this.clid +
							 ');return false;"><span style="color:blue;">See Details</span></a></div><div><a onclick="map.panTo(panPoint); map.setZoom(13); return false">Focus</a></div>',
					};

					InformationWindow = new google.maps.InfoWindow(myOptions);
					InformationWindow.open(map, this);


				});

				spiderfier.addMarker(m);
				//var markerPos = m.getPosition();
				pointBounds.extend(m.getPosition());

				buildRecordTableRow(pointObj[i], allMarkers.length-1);

			}

		}


		function handleModeRadios(myMode){

			switch(myMode.value) {
				case 'cluster':
					displayMode = 'cluster';
					heatmap.setMap(null);
					for (var i in allMarkers){
						allMarkers[i].setMap(null);
					}
					markerCluster = new markerClusterer.MarkerClusterer({
						map: map,
						markers: allMarkers,
					});
					break;
				case 'points':
					displayMode = 'points';
					heatmap.setMap(null);
					if (markerCluster){
						markerCluster.clearMarkers();
					}
					for (var i in allMarkers){
						allMarkers[i].setMap(map);
					}
					break;
				case 'heat':
					displayMode = 'heat';
					if (markerCluster){
						markerCluster.clearMarkers();
					}
					for (var i in allMarkers){
						allMarkers[i].setMap(null);
					}
					buildHeatMapData();
					heatmap.setMap(map);
					break;
			}
		}

		function hideTaxaToggle(checked, myTaxa, redraw = true){
			if(MarkerGroupings['Taxa'][myTaxa]){
				for (var i in MarkerGroupings['Taxa'][myTaxa]){
					if(checked){
						MarkerGroupings['Taxa'][myTaxa][i].setOptions({visible:true});
					}
					else{
						MarkerGroupings['Taxa'][myTaxa][i].setOptions({visible:false});
					}
					if(displayMode == 'points'){
						MarkerGroupings['Taxa'][myTaxa][i].setMap(map);
					}
				}
				if(displayMode == 'cluster' && redraw){
					redrawMarkerClusters();
				}
				else if (displayMode == 'heat'){
					buildHeatMapData();
				}

			}
		}

		function hideAllTaxaToggle(checked, myTaxa){
			for (var t in MarkerGroupings['Taxa']){
				hideTaxaToggle(checked,t, false);
				document.getElementById('chkHideTaxa'+t).checked = checked;
			}
			redrawMarkerClusters();
		}

		function hideCollToggle(checked, myColl, redraw = true){
			if(MarkerGroupings['Collections'][myColl]){
				for (var i in MarkerGroupings['Collections'][myColl]){
					if(checked){
						MarkerGroupings['Collections'][myColl][i].setOptions({visible:true});
					}
					else{
						MarkerGroupings['Collections'][myColl][i].setOptions({visible:false});
					}
					if(displayMode == 'points'){
						MarkerGroupings['Collections'][myColl][i].setMap(map);
					}
				}
				if(displayMode == 'cluster' && redraw){
					redrawMarkerClusters();
				}
				else if (displayMode == 'heat'){
					buildHeatMapData();
				}
			}
		}

		function hideAllCollToggle(checked, myColl){
			for (var t in MarkerGroupings['Collections']){
				hideCollToggle(checked,t, false);
				document.getElementById('chkHideColl'+t).checked = checked;
			}
			redrawMarkerClusters();
		}

		function redrawMarkerClusters(){
			if (displayMode == 'cluster'){
				markerCluster.clearMarkers();
				markerCluster.addMarkers(allMarkers, false);
			}
		}

		function buildRecordsTable(){
			let recordsTableHTMLTempplate = `
				<div id="mapSearchDownloadData">
					<div>
						<div style="float:left;">
							<form name="downloadForm" action="../download/index.php" method="post" onsubmit="targetPopup(this)" style="float:left">
								<button class="ui-button ui-widget ui-corner-all" style="margin:5px;padding:5px;cursor: pointer" title="<?php echo $LANG['DOWNLOAD_SPECIMEN_DATA']; ?>">
									<img src="../../images/dl2.png" srcset="../../images/download.svg" class="svg-icon" style="width:15px" />
								</button>
								<input name="reclimit" type="hidden" value="<?php echo $recLimit; ?>" />
								<input name="sourcepage" type="hidden" value="map" />
								<input name="searchvar" type="hidden" value="<?php echo $searchVar; ?>" />
								<input name="dltype" type="hidden" value="specimen" />
							</form>
							<form name="fullquerykmlform" action="kmlhandler.php" method="post" target="_blank" style="float:left;">
								<input name="reclimit" type="hidden" value="<?php echo $recLimit; ?>" />
								<input name="sourcepage" type="hidden" value="map" />
								<input name="searchvar" type="hidden" value="<?php echo $searchVar; ?>" />
								<button name="submitaction" type="submit" class="ui-button ui-widget ui-corner-all" style="margin:5px;padding:5px;cursor: pointer" title="Download KML file">
									<img src="../../images/dl2.png" srcset="../../images/download.svg" class="svg-icon" style="width:15px; padding-right: 5px; vertical-align:top" />KML
								</button>
							</form>
							<button class="ui-button ui-widget ui-corner-all" style="margin:5px;padding:5px;cursor: pointer;" onclick="copyUrl()" title="<?php echo (isset($LANG['COPY_TO_CLIPBOARD'])?$LANG['COPY_TO_CLIPBOARD']:'Copy URL to Clipboard'); ?>">
								<img src="../../images/dl2.png" srcset="../../images/link.svg" class="svg-icon" style="width:15px" />
							</button>
						</div>
					</div>
				</div>
				<div id="divMapSearchRecords">
					<button id="mapLoadMoreRecords" type="button">Load more records</button>
					<div id="recordState"><span id="recordLoadedCount"></span> of <span id="recordTotalCount"></span></div>
					<table class="styledtable" id="mapSearchRecordsTable">
						<thead>
						<tr>
							<th>Catalog #</th>
							<th>Collector</th>
							<th>Date</th>
							<th>Scientific Name</th>
						</tr>
						</thead>
						<tbody>
						${renderRecordsRow()}
						</tbody>
					</table>
				</div>
			`;

			if (document.getElementById("records")) document.getElementById("records").innerHTML = recordsTableHTMLTempplate;
		}

		function rebuildRecordsTable(){
			let mapSearchRecordsTableTemplate = `
				<table class="styledtable" id="mapSearchRecordsTable">
					<thead>
					<tr>
						<th>Catalog #</th>
						<th>Collector</th>
						<th>Date</th>
						<th>Scientific Name</th>
					</tr>
					</thead>
					<tbody>
					${renderRecordsRow()}
					</tbody>
				</table>`;

			let newRecordsTable = document.createElement('table');
			newRecordsTable.classList.add('styledtable');
			newRecordsTable.setAttribute('id', 'mapSearchRecordsTable');
			newRecordsTable.innerHTML = mapSearchRecordsTableTemplate;
			let oldTable = document.getElementById("mapSearchRecordsTable");
			oldTable.parentNode.replaceChild(newRecordsTable, oldTable);

		}



		function requestMoreRecords(){
			var xhr = new XMLHttpRequest();
			xhr.open("POST", "index.php")
			let data = new FormData();
			data.set('loadMoreMapData', true);
			data.set('recordlimit', document.getElementById('recordlimit').value);
			xhr.send(data);

    		xhr.onload = function() {

				if (xhr.readyState === xhr.DONE) {
					if (xhr.status == 200) {
						let response = JSON.parse(xhr.responseText);
						if (response.recordCount == 0){
							alert("No records returned");
							document.getElementById('mapLoadMoreRecords').disabled = true;
						}
						else {
							Array.prototype.push.apply(pointObj, response.records);
							processPoints();
							buildCollKey();
							buildTaxaKey();
							rebuildRecordsTable();
							retrievedRecordCount += response.recordCount
						}
					} else {
						alert('Error loading more records');
						console.log('Response Code: ' + xhr.status + ' Server Response: ' +  xhr.responseText);
					}
				}
    		};
		}


		// function findSelection(gCnt, id, dir) {
		// 	if (grpArr[gCnt]) {
		// 		for (i in grpArr[gCnt]) {
		// 			if (grpArr[gCnt][i].occid == id) {
		// 				if (grpArr[gCnt][i].recordType == 'obs') {
		// 					var markerColor = '#' + grpArr[gCnt][i].color;
		// 					if (dir == 'select') {
		// 						var markerIcon = {
		// 							path: "m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",
		// 							fillColor: markerColor,
		// 							fillOpacity: 1,
		// 							scale: 1,
		// 							strokeColor: "#10D8E6",
		// 							strokeWeight: 2
		// 						};
		// 					} else if (dir == 'deselect') {
		// 						var markerIcon = {
		// 							path: "m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",
		// 							fillColor: markerColor,
		// 							fillOpacity: 1,
		// 							scale: 1,
		// 							strokeColor: "#000000",
		// 							strokeWeight: 1
		// 						};
		// 					}
		// 					grpArr[gCnt][i].setIcon(markerIcon);
		// 				}
		// 				if (grpArr[gCnt][i].recordType == 'spec') {
		// 					var markerColor = '#' + grpArr[gCnt][i].color;
		// 					if (dir == 'select') {
		// 						var markerIcon = {
		// 							path: google.maps.SymbolPath.CIRCLE,
		// 							fillColor: markerColor,
		// 							fillOpacity: 1,
		// 							scale: 7,
		// 							strokeColor: "#10D8E6",
		// 							strokeWeight: 2
		// 						};
		// 					} else if (dir == 'deselect') {
		// 						var markerIcon = {
		// 							path: google.maps.SymbolPath.CIRCLE,
		// 							fillColor: markerColor,
		// 							fillOpacity: 1,
		// 							scale: 7,
		// 							strokeColor: "#000000",
		// 							strokeWeight: 1
		// 						};
		// 					}
		// 					grpArr[gCnt][i].setIcon(markerIcon);
		// 				}
		// 				if (dir == 'select') {
		// 					grpArr[gCnt][i].selected = true;
		// 					selected = true;
		// 				} else if (dir == 'deselect') {
		// 					grpArr[gCnt][i].selected = false;
		// 					deselected = true;
		// 				}
		// 				return;
		// 			}
		// 		}
		// 	}
		// }

		// function findGrpClusterSelection(gCnt, id) {
		// 	if (clusterCollArr[gCnt]) {
		// 		var clusters = clusterCollArr[gCnt].getClusters();
		// 		for (var i = 0, l = clusters.length; i < l; i++) {
		// 			var selCluster = false;
		// 			var oldHtml = clusters[i].clusterIcon_.div_.innerHTML;
		// 			for (var j = 0, le = clusters[i].markers_.length; j < le; j++) {
		// 				if (clusters[i].markers_[j].selected == true) {
		// 					selCluster = true;
		// 				}
		// 			}
		// 			if (selCluster == true) {
		// 				var newHtml = oldHtml.replace('></circle>', ' stroke="#10D8E6" stroke-width="3px"></circle>');
		// 			}
		// 			if (selCluster == false) {
		// 				var newHtml = oldHtml.replace(' stroke="#10D8E6" stroke-width="3px"></circle>', '></circle>');
		// 			}
		// 			clusters[i].clusterIcon_.div_.innerHTML = newHtml;
		// 		}
		// 	}
		// }

		// function findTaxClusterSelection(id) {
		// 	for (var tid in tidArr) {
		// 		if (clusterTaxArr[tid]) {
		// 			var clusters = clusterTaxArr[tid].getClusters();
		// 			for (var i = 0, l = clusters.length; i < l; i++) {
		// 				var selCluster = false;
		// 				var oldHtml = clusters[i].clusterIcon_.div_.innerHTML;
		// 				for (var j = 0, le = clusters[i].markers_.length; j < le; j++) {
		// 					if (clusters[i].markers_[j].selected == true) {
		// 						selCluster = true;
		// 					}
		// 				}
		// 				if (selCluster == true) {
		// 					var newHtml = oldHtml.replace('></circle>', ' stroke="#10D8E6" stroke-width="3px"></circle>');
		// 				}
		// 				if (selCluster == false) {
		// 					var newHtml = oldHtml.replace(' stroke="#10D8E6" stroke-width="3px"></circle>', '></circle>');
		// 				}
		// 				clusters[i].clusterIcon_.div_.innerHTML = newHtml;
		// 			}
		// 		}
		// 	}
		// }



		<?php echo ($activateGeolocation ? "google.maps.event.addListener(window, 'load', getCoords);" : ""); ?>
	</script>

</body>

</html>