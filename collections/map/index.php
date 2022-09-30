<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/content/lang/collections/map/index.' . $LANG_TAG . '.php');
include_once($SERVER_ROOT . '/classes/OccurrenceMapManager.php');

header('Content-Type: text/html; charset=' . $CHARSET);
ob_start('ob_gzhandler');
ini_set('max_execution_time', 180); //180 seconds = 3 minutes

$distFromMe = array_key_exists('distFromMe', $_REQUEST) ? $_REQUEST['distFromMe'] : '';
$recLimit = array_key_exists('recordlimit', $_REQUEST) ? $_REQUEST['recordlimit'] : 15000;
$catId = array_key_exists('catid', $_REQUEST) ? $_REQUEST['catid'] : 0;
$tabIndex = array_key_exists('tabindex', $_REQUEST) ? $_REQUEST['tabindex'] : 0;
$submitForm = array_key_exists('submitform', $_REQUEST) ? $_REQUEST['submitform'] : '';

if (!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;

$mapManager = new OccurrenceMapManager();
$searchVar = $mapManager->getQueryTermStr();

if ($searchVar && $recLimit) $searchVar .= '&reclimit=' . $recLimit;

$obsIDs = $mapManager->getObservationIds();


//Sanitation
if (!is_numeric($recLimit)) $recLimit = 15000;
if (!is_numeric($distFromMe)) $distFromMe = '';
if (!is_numeric($catId)) $catId = 0;
if (!is_numeric($tabIndex)) $tabIndex = 0;

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

		



	</style>
	<script src="../../js/jquery-3.6.0.min.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
	<link href="../../js/jquery-ui/jquery-ui.min.css" type="text/css" rel="Stylesheet" />
	<script src="//maps.googleapis.com/maps/api/js?v=quarterly&libraries=drawing,visualization<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY ? '&key=' . $GOOGLE_MAP_KEY : ''); ?>"></script>
	<script src="../../js/symb/collections.map.index_gm.js?ver=2" type="text/javascript"></script>
	<script src="../../js/symb/collections.map.index_ui.js?ver=2" type="text/javascript"></script>
	<script src="../../js/symb/collections.list.js?ver=1" type="text/javascript"></script>
	<script src="../../js/googlemaps/index.dev.js"></script>
	<script src="../../js/symb/oms.min.js" type="text/javascript"></script>
	<script type="text/javascript">

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
		var selections = [];
		var dsselections = [];
		var selectedds = '';
		var selecteddsrole = '';
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
		var mouseoverTimeout = '';
		var mouseoutTimeout = '';
		var pointBounds = new google.maps.LatLngBounds();
		var occArr = [];
		var panPoint = new google.maps.LatLng();
		var heatMapData = new google.maps.MVCArray();
		var displayMode = 'cluster';
		var taxaKetSet = new Set();

		function initialize(){
			document.getElementById('defaultmarkercolor').value = defaultMarkerColor;
			<?php
			$recordCnt = $mapManager->getRecordCnt();
			if ($searchVar) {
			?>
				if (<?php echo $recordCnt; ?> > 0) {
					var resultCount = <?php echo $recordCnt; ?>;
					if (resultCount <= <?php echo $recLimit; ?>) {
						<?php
						//$coordArr = $mapManager->getCoordinateMap(0,$recLimit);
						$coordArr = $mapManager->getCoordinateMap2(0, 50000);
						echo 'pointObj = ' . json_encode($coordArr) . ";\n";
						?>
					} else {
						alert("Your search produced " + resultCount + " results which exceeds the maximum of <?php echo $recLimit; ?>, please refine your search more.");
						$('#loadingOverlay').hide();
					}
				} else {
					alert('There were no records matching your query.');
				}
			<?php
			}
			?>
			initializeGoogleMap();
			if (pointObj){
				processPoints();
			}
			$('#loadingOverlay').hide();
			setTimeout(function() {
				afterEffects();
			}, 500);

		}

		function initializeGoogleMap() {
			
			var dmOptions = {
				zoom: 6,
				minZoom: 3,
				mapTypeId: google.maps.MapTypeId.TERRAIN,
				mapTypeControl: true,
				mapTypeControlOptions: {
					style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
					position: google.maps.ControlPosition.TOP_RIGHT
				},
				panControl: true,
				panControlOptions: {
					position: google.maps.ControlPosition.RIGHT_TOP
				},
				zoomControl: true,
				zoomControlOptions: {
					style: google.maps.ZoomControlStyle.LARGE,
					position: google.maps.ControlPosition.RIGHT_TOP
				},
				scaleControl: true,
				scaleControlOptions: {
					position: google.maps.ControlPosition.RIGHT_TOP
				},
				streetViewControl: false
			};
			
			map = new google.maps.Map(document.getElementById("map"), dmOptions);
			heatmap = new google.maps.visualization.HeatmapLayer({
				data: heatMapData,
				dissipating: false,
			});
			
			var initBounds = new google.maps.LatLngBounds();
			
			initBounds.extend(new google.maps.LatLng(initBoundsNorthEast[0], initBoundsNorthEast[1]));
			initBounds.extend(new google.maps.LatLng(initBoundsSouthWest[0], initBoundsSouthWest[1]));
			map.fitBounds(initBounds);

			spiderfier = new OverlappingMarkerSpiderfier(map, {
				basicFormatEvents: true
			});

			var polyOptions = {
				strokeWeight: 0,
				fillOpacity: 0.45,
				editable: true,
				draggable: true
			};

			var drawingManager = new google.maps.drawing.DrawingManager({
				drawingMode: null,
				drawingControl: true,
				drawingControlOptions: {
					position: google.maps.ControlPosition.TOP_CENTER,
					drawingModes: [
						google.maps.drawing.OverlayType.POLYGON,
						google.maps.drawing.OverlayType.RECTANGLE,
						google.maps.drawing.OverlayType.CIRCLE
					]
				},
				markerOptions: {
					draggable: true
				},
				polylineOptions: {
					editable: true
				},
				rectangleOptions: polyOptions,
				circleOptions: polyOptions,
				polygonOptions: polyOptions
			});

			drawingManager.setMap(map);

			/* google.maps.event.addListener(
				document.getElementById("distFromMe"),
				'change',
				function(){
					var distance = document.getElementById("distFromMe").value;
					if(distance){
						document.getElementById("pointlat").value = posLat;
						document.getElementById("pointlong").value = posLong;
						document.getElementById("radius").value = distance;
					}
				}
			); */

			google.maps.event.addListener(drawingManager, 'overlaycomplete', function(e) {
				if (e.type != google.maps.drawing.OverlayType.MARKER) {
					// Switch back to non-drawing mode after drawing a shape.
					deleteSelectedShape();
					drawingManager.setDrawingMode(null);

					var newShapeType = '';
					newShapeType = e.type;
					// Add an event listener that selects the newly-drawn shape when the user
					// mouses down on it.
					var newShape = e.overlay;
					newShape.type = e.type;
					google.maps.event.addListener(newShape, 'dragend', function() {
						setSelection(newShape);
					});
					if (newShapeType == 'circle') {
						getCircleCoords(newShape);
						google.maps.event.addListener(newShape, 'radius_changed', function() {
							setSelection(newShape);
						});
						google.maps.event.addListener(newShape, 'center_changed', function() {
							setSelection(newShape);
						});
					}
					if (newShapeType == 'rectangle') {
						getRectangleCoords(newShape);
						google.maps.event.addListener(newShape, 'bounds_changed', function() {
							setSelection(newShape);
						});
					}
					if (newShapeType == 'polygon') {
						getPolygonCoords(newShape);
						google.maps.event.addListener(newShape.getPath(), 'insert_at', function() {
							setSelection(newShape);
						});
						google.maps.event.addListener(newShape.getPath(), 'remove_at', function() {
							setSelection(newShape);
						});
						google.maps.event.addListener(newShape.getPath(), 'set_at', function() {
							setSelection(newShape);
						});
					}
					setSelection(newShape);
				}
			});

			spiderfier.addListener('click', function(marker, event) {
				occid = marker.occid;
				chbox = 'ch' + occid;
				if (selections.indexOf(occid) > -1) {
					var index = selections.indexOf(occid);
					if (index > -1) {
						selections.splice(index, 1);
					}
					if (document.getElementById(chbox)) {
						document.getElementById(chbox).checked = false;
						document.getElementById("selectallcheck").checked = false;
					}
					//removeSelectionRecord(occid);
					//adjustSelectionsTab();
					//deselectMarker(marker);
				} else {
					selections.push(occid);
					if (document.getElementById(chbox)) {
						document.getElementById(chbox).checked = true;
						var f = document.getElementById("selectform");
						var boxesChecked = true;
						for (var i = 0; i < f.length; i++) {
							if (f.elements[i].name == "occid[]") {
								if (f.elements[i].checked == false) {
									boxesChecked = false;
								}
							}
						}
						if (boxesChecked == true) {
							document.getElementById("selectallcheck").checked = true;
						}
					}
					//adjustSelectionsTab();
					selectMarker(marker);
				}
			});

			spiderfier.addListener('spiderfy', function(markers) {
				closeInfoWin();
			});

			createShape();
		}

		function createShape() {
			let newShape = null;
			let f = document.mapsearchform;
			if (f.upperlat.value != "") {
				newShape = new google.maps.Rectangle({
					bounds: new google.maps.LatLngBounds(
						new google.maps.LatLng(f.bottomlat.value, f.leftlong.value),
						new google.maps.LatLng(f.upperlat.value, f.rightlong.value)
					),
					strokeWeight: 0,
					fillOpacity: 0.45,
					editable: true,
					draggable: true,
					map: map
				});
				newShape.type = "rectangle";
				google.maps.event.addListener(newShape, 'bounds_changed', function() {
					setSelection(newShape);
				});
			} else if (f.pointlat.value != "") {
				newShape = new google.maps.Circle({
					center: new google.maps.LatLng(f.pointlat.value, f.pointlong.value),
					radius: (f.radius.value / 0.6214) * 1000,
					strokeWeight: 0,
					fillOpacity: 0.45,
					editable: true,
					draggable: true,
					map: map
				});
				newShape.type = "circle";
				google.maps.event.addListener(newShape, "radius_changed", function() {
					setSelection(newShape);
				});
				google.maps.event.addListener(newShape, "center_changed", function() {
					setSelection(newShape);
				});
			} else if (f.polycoords.value != "") {
				let wkt = f.polycoords.value;
				if (wkt.substring(0, 7) == "POLYGON") wkt = wkt.substring(7).trim();
				while (wkt.substring(0, 1) == "(") wkt = wkt.substring(1).trim();
				while (wkt.substring(wkt.length - 1) == ")") wkt = wkt.slice(0, -1).trim();
				let coordArr = wkt.split(",");
				let pointArr = [];
				for (let n = 0; n < coordArr.length; n++) {
					let ptArr = coordArr[n].trim().split(" ");
					pointArr.push(new google.maps.LatLng(ptArr[0], ptArr[1]));
				}
				newShape = new google.maps.Polygon({
					paths: [pointArr],
					strokeWeight: 0,
					fillOpacity: 0.45,
					editable: true,
					draggable: true,
					map: map
				});
				newShape.type = "polygon";
				google.maps.event.addListener(newShape.getPath(), "insert_at", function() {
					setSelection(newShape);
				});
				google.maps.event.addListener(newShape.getPath(), "remove_at", function() {
					setSelection(newShape);
				});
				google.maps.event.addListener(newShape.getPath(), "set_at", function() {
					setSelection(newShape);
				});
			}
			if (newShape) {
				google.maps.event.addListener(newShape, "click", function() {
					setSelection(newShape);
				});
				google.maps.event.addListener(newShape, "dragend", function() {
					setSelection(newShape);
				});
				setSelection(newShape);
			}
		}

		function afterEffects() {
			if (pointObj) {
				setPanels(true);
				$("#accordion").accordion("option", {
					active: 1
				});
				buildCollKey();
				buildTaxaKey();
				//jscolor.init();
				if (pointBounds) {
					map.fitBounds(pointBounds);
					map.panToBounds(pointBounds);
				}
			}
		}

		function processPoints() {

			taxaKetSet.clear();

			//setup map marker data structure
			if (MarkerGroupings.indexOf('Taxa') < 0){
				MarkerGroupings['Taxa'] = [];
				//MarkerGroupings['Taxa']['iconColors'] = [];
			}
			if (MarkerGroupings.indexOf('Collections') < 0){
				MarkerGroupings['Collections'] = [];
				//MarkerGroupings['Collections']['iconColors'] = [];
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

				if (!taxaKetSet.has(scinameStr)){
					taxaKetSet.add(scinameStr)
				
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
				m.addListener('click', function() {

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
				var markerPos = m.getPosition();
				pointBounds.extend(markerPos);
			}
			//initialize map with clustering turned on
			handleModeRadios({
				value: 'cluster',
			});
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
					markerCluster.clearMarkers();
					for (var i in allMarkers){
						allMarkers[i].setMap(map);
					}
					break;
				case 'heat':
					displayMode = 'heat';
					markerCluster.clearMarkers();
					for (var i in allMarkers){
						allMarkers[i].setMap(null);
					}
					buildHeatMapData();
					heatmap.setMap(map);
					break;
			}
		}


		function setPanels(show) {
			if (document.getElementById("recordstaxaheader")) {
				if (show) {
					document.getElementById("recordstaxaheader").style.display = "block";
					document.getElementById("tabs2").style.display = "block";
				} else {
					document.getElementById("recordstaxaheader").style.display = "none";
					document.getElementById("tabs2").style.display = "none";
				}
			}
		}

		function buildCollKeyPiece(key, iconColor) {
			if (!collNameArr[key['collid']]){
				collNameArr[key['collid']] = [key['CollectionName'], key['collid']];
				keyHTML = '';
				keyHTML += '<div style="display:table-row;">';
				keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-bottom:5px;" ><input type="checkbox" id="chkHideColl' + key['collid'] + '" onchange="hideCollToggle(this.checked,\'' + key['collid'] + '\');" CHECKED><input type="color" id="collColor' + key['collid'] + '" class="small_color_input" value="' + iconColor + '" oninput="changeCollColor(this.value,' + key['collid'] + ');" /></div>';
				keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-left:8px;"> = </div>';
				keyHTML += '<div style="display:table-cell;width:250px;vertical-align:middle;padding-left:8px;">' + key['CollectionName'] + '</div>';
				keyHTML += '</div>';
				collKeyArr[key['collid']] = keyHTML;
			}
		}

		function buildCollKey() {
			keyHTML = '';
			keyHTML += "<div style='margin-left:5px;'><h3 style='margin-top:8px;margin-bottom:5px;'>Collections</h3></div>";
			keyHTML += "<div style='display:table;'>";
			keyHTML += '<div id="toggleHideAllCollectionsRow">';
			keyHTML += '<div style="display:table-row;">';	
			keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-bottom:5px;" ><input type="checkbox" id="chkHideAllColl" onchange="hideAllCollToggle(this.checked);" CHECKED><label for="chkHideAllColl">Show/Hide All Collections</label></div>';
			keyHTML += '</div></div></div>';

			// location aware case insensitive sort
			collNameArr.sort((a, b) => {
				return a[0].localeCompare(b[0], undefined, {sensitivity: 'base'});
			});
			for (var i in collNameArr) {
				keyHTML += collKeyArr[collNameArr[i][1]];
			}
			if (document.getElementById("symbologykeysbox")) document.getElementById("symbologykeysbox").innerHTML = keyHTML;
		}

		function buildTaxaKeyPiece(key, tidinterpreted, family, sciname, iconColor) {

			//setup array structure that will be used to group and sort taxa marker legend during buildTaxaKey()
			if (familyNameArr.indexOf(family) < 0){
				familyNameArr.push(family);
				taxaLegendArr[family] = [];
			}
			if(!taxaLegendArr[family][key]){
				taxaLegendArr[family][key] = sciname;
			}
			else return;

			if (!taxaKeyArr[key]){
				keyHTML = '';
				keyLabel = "'" + key + "'";
				keyHTML += '<li class="mapLegendEntry"><div class="mapLegendEntryInputs"><input type="checkbox" id="chkHideTaxa' + key + '" onchange="hideTaxaToggle(this.checked,\'' + key + '\');" CHECKED><input type="color" id="taxaColor' + key + '" class="small_color_input"  value="' + iconColor + '" onchange="changeTaxaColor(this.value,' + keyLabel + ');" /></div>';
				keyHTML += '<span class="mapLegendEntryText">';
				if (tidinterpreted) keyHTML += '<i> = <a href="#" onclick="openPopup(\'../../taxa/index.php?tid=' + tidinterpreted + '&display=1\');return false;">' + sciname + '</a></i>';
				else keyHTML += "<i> = " + sciname + "</i>";
				keyHTML += '</span></li>';
				taxaKeyArr[key] = keyHTML;
			}
		}
		

		function buildTaxaKey() {

			taxaLegendArr.sort(function(a,b) {
				if(a[1] === b[1]) return a[3] > b[3] ? 1 : -1;
				return a[1] > b[1] ? 1 : -1;
			});

			
			keyHTML = '';
			keyHTML += "<div style='display:table;margin-top:8px;margin-bottom:5px;'>";
			keyHTML += '<div id="toggleHideAllTaxakeyRow">';
			keyHTML += '<div style="display:table-row;">';	
			keyHTML += '</div></div></div>';
			keyHTML += '<ul class="mapGroupLegend">';
			keyHTML += '<li><input type="checkbox" id="chkHideAllTaxa" onchange="hideAllTaxaToggle(this.checked);" CHECKED><label for="chkHideAllTaxa">Show/Hide All Taxa</label></li>';
			
			familyNameArr.sort();
			var tempFamilyGroup = [];
			for (let fam = 0; fam < familyNameArr.length; fam++) {
				if(familyNameArr[fam] !== 'NULL'){

					keyHTML += "<div style='margin-left:5px;'><h3 style='margin-top:8px;margin-bottom:5px;'>" + familyNameArr[fam] + "</h3></div>";
					tempFamilyGroup = [];
					tempFamilyGroup = getSortedKeys(taxaLegendArr[familyNameArr[fam]]);
					//tempFamilyGroup.sort();
		
					for (let i = 0; i < tempFamilyGroup.length; i++) {
						keyHTML += taxaKeyArr[tempFamilyGroup[i]];
					}

					taxaCnt += tempFamilyGroup.length;
					
				}
			}
			if (taxaLegendArr['NULL']) {
				tempFamilyGroup = [];
				tempFamilyGroup = getSortedKeys(taxaLegendArr['NULL']);
				//tempFamilyGroup.sort();
				
				keyHTML += "<div style='margin-left:5px;'><h3 style='margin-top:8px;margin-bottom:5px;'>Family Not Defined</h3></div>";
				keyHTML += "<div style='display:table;'>";
				for (let i = 0; i < tempFamilyGroup.length; i++) {
						keyHTML += taxaKeyArr[tempFamilyGroup[i]];
				}
				taxaCnt += tempFamilyGroup.length;
				
			}
			if (document.getElementById("taxasymbologykeysbox")) document.getElementById("taxasymbologykeysbox").innerHTML = keyHTML;
			if (document.getElementById("taxaCountNum")) document.getElementById("taxaCountNum").innerHTML = taxaCnt;
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

		function findSelection(gCnt, id, dir) {
			if (grpArr[gCnt]) {
				for (i in grpArr[gCnt]) {
					if (grpArr[gCnt][i].occid == id) {
						if (grpArr[gCnt][i].recordType == 'obs') {
							var markerColor = '#' + grpArr[gCnt][i].color;
							if (dir == 'select') {
								var markerIcon = {
									path: "m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",
									fillColor: markerColor,
									fillOpacity: 1,
									scale: 1,
									strokeColor: "#10D8E6",
									strokeWeight: 2
								};
							} else if (dir == 'deselect') {
								var markerIcon = {
									path: "m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",
									fillColor: markerColor,
									fillOpacity: 1,
									scale: 1,
									strokeColor: "#000000",
									strokeWeight: 1
								};
							}
							grpArr[gCnt][i].setIcon(markerIcon);
						}
						if (grpArr[gCnt][i].recordType == 'spec') {
							var markerColor = '#' + grpArr[gCnt][i].color;
							if (dir == 'select') {
								var markerIcon = {
									path: google.maps.SymbolPath.CIRCLE,
									fillColor: markerColor,
									fillOpacity: 1,
									scale: 7,
									strokeColor: "#10D8E6",
									strokeWeight: 2
								};
							} else if (dir == 'deselect') {
								var markerIcon = {
									path: google.maps.SymbolPath.CIRCLE,
									fillColor: markerColor,
									fillOpacity: 1,
									scale: 7,
									strokeColor: "#000000",
									strokeWeight: 1
								};
							}
							grpArr[gCnt][i].setIcon(markerIcon);
						}
						if (dir == 'select') {
							grpArr[gCnt][i].selected = true;
							selected = true;
						} else if (dir == 'deselect') {
							grpArr[gCnt][i].selected = false;
							deselected = true;
						}
						return;
					}
				}
			}
		}

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

		function disableCollectionsLegend() {
			for (var coll in MarkerGroupings['Collections']) {
				var colorKeyName = 'collColor' + coll;
				var chkboxKeyName = 'chkHideColl' + coll
				if (document.getElementById(colorKeyName)) {
					document.getElementById(colorKeyName).style.visibility = 'hidden';
					document.getElementById(chkboxKeyName).disabled= true;
				}
				document.getElementById('chkHideAllColl').disabled= true;

			}
		}

		function enableCollectionsLegend() {
			for (var coll in MarkerGroupings['Collections']) {
				var colorKeyName = 'collColor' + coll;
				var chkboxKeyName = 'chkHideColl' + coll
				if (document.getElementById(colorKeyName)) {
					document.getElementById(colorKeyName).style.visibility = 'visible';
					document.getElementById(chkboxKeyName).disabled = false;
				}
				document.getElementById('chkHideAllColl').disabled = false;

			}
		}

		function disableTaxaLegend() {
			for (var tid in MarkerGroupings['Taxa']) {
				var colorKeyName = 'taxaColor' + tid;
				var chkboxKeyName = 'chkHideTaxa' + tid
				if (document.getElementById(colorKeyName)) {
					document.getElementById(colorKeyName).style.visibility = 'hidden';
					document.getElementById(chkboxKeyName).disabled= true;
				}
				document.getElementById('chkHideAllTaxa').disabled= true;

			}
		}

		function enableTaxaLegend() {
			for (var tid in MarkerGroupings['Taxa']) {
				var colorKeyName = 'taxaColor' + tid;
				var chkboxKeyName = 'chkHideTaxa' + tid
				if (document.getElementById(colorKeyName)) {
					document.getElementById(colorKeyName).style.visibility = 'visible';
					document.getElementById(chkboxKeyName).disabled = false;
				}
				document.getElementById('chkHideAllTaxa').disabled = false;

			}
		}

		function resetTaxaLegend(myColor) {
			enableTaxaLegend();
			if (!myColor){
				myColor = document.getElementById("defaultmarkercolor").value;
			}
			for (var tid in MarkerGroupings['Taxa']) {
				var keyName = 'taxaColor' + tid;
				if (document.getElementById(keyName)) {
					document.getElementById(keyName).value = myColor;
				}
			}
		}

		function resetCollectionsLegend(myColor) {
			enableCollectionsLegend();
			if (!myColor){
				myColor = document.getElementById("defaultmarkercolor").value;
			}
			for (var coll in MarkerGroupings['Collections']) {
				var keyName = 'collColor' + coll;
				document.getElementById(keyName).value = myColor;
			}
		}

		function changeCollColor(color, collid) {
			changeMarkersColor(color, MarkerGroupings['Collections'][collid]);
			mapSymbol = 'coll';
		}

		function changeTaxaColor(color, tidcode) {
			changeMarkersColor(color, MarkerGroupings['Taxa'][tidcode]);
			mapSymbol = 'taxa';
		}

		function autoColorColl() {
			document.getElementById("randomColorColl").disabled = true;
			if (mapSymbol == 'taxa') {
				resetTaxaLegend();
				disableTaxaLegend();
				enableCollectionsLegend();
			}
			var usedColors = [];
			for (var coll in collNameArr) {
				var randColor = generateRandColor();
				while (usedColors.indexOf(randColor) > -1) {
					randColor = generateRandColor();
				}
				usedColors.push(randColor);
				changeMarkersColor(randColor, MarkerGroupings['Collections'][collNameArr[coll][1]]);
				var keyName = 'collColor' + collNameArr[coll][1];
				document.getElementById(keyName).value = randColor;
			}
			mapSymbol = 'coll';
			document.getElementById("randomColorColl").disabled = false;
		}

		function autoColorTaxa() {
			document.getElementById("randomColorTaxa").disabled = true;
			if (mapSymbol == 'coll') {
				resetCollectionsLegend();
				disableCollectionsLegend();
				enableTaxaLegend();
			}
			
			var usedColors = [];
			for (var tid in MarkerGroupings['Taxa']) {
				var randColor = generateRandColor();
				while (usedColors.indexOf(randColor) > -1) {
					randColor = generateRandColor();
				}
				usedColors.push(randColor);
				changeMarkersColor(randColor, MarkerGroupings['Taxa'][tid]);
				var keyName = 'taxaColor' + tid;
				if (document.getElementById(keyName)) {
					document.getElementById(keyName).value = randColor;
				}
			}
			mapSymbol = 'taxa';
			document.getElementById("randomColorTaxa").disabled = false;
		}

		function resetSymbology() {
			document.getElementById("symbolizeReset1").disabled = true;
			document.getElementById("symbolizeReset2").disabled = true;
			
			var color = document.getElementById("defaultmarkercolor").value;
			for (var coll in collNameArr) {
				changeMarkersColor(color, MarkerGroupings['Collections'][collNameArr[coll][1]]);
			}
			mapSymbol = 'coll';
			resetTaxaLegend(color);
			resetCollectionsLegend(color);
			mapSymbol = 'coll';
			document.getElementById("symbolizeReset1").disabled = false;
			document.getElementById("symbolizeReset2").disabled = false;
		}

		/*
		function selectPoints(){
			var selectedpoints = document.getElementById("selectedpoints");
			selected = false;
			var selectedpoint = Number(selectedpoints.value);
			while (selected == false) {
				for(var gcnt in grpArr) {
					findSelection(gcnt,selectedpoint,'select');
					if(clusterOff=="n"){
						findGrpClusterSelection(gcnt,selectedpoint);
					}
				}
				if(clusterOff=="n"){
					findTaxClusterSelection(selectedpoint);
				}
			}
			if(selections.indexOf(selectedpoint) < 0){
				selections.push(selectedpoint);
			}
			adjustSelectionsTab();
		}
		*/

		/*
		function deselectPoints(){
			deselected = false;
			var deselectedpoint = Number(deselectedpoints.value);
			while (deselected == false) {
				for(var gcnt in grpArr) {
					findSelection(gcnt,deselectedpoint,'deselect');
					if(clusterOff=="n"){
						findGrpClusterSelection(gcnt,deselectedpoint);
					}
				}
				if(clusterOff=="n"){
					findTaxClusterSelection(deselectedpoint);
				}
			}
			var index = selections.indexOf(deselectedpoint);
			selections.splice(index, 1);
			adjustSelectionsTab();
		}
		*/

		/*
		function selectDSPoints(){
			selected = false;
			var selectedpoint = Number(selecteddspoints.value);
			while (selected == false) {
				if (dsmarkers) {
					for (i in dsmarkers) {
						if(dsmarkers[i].occid==selectedpoint){
							var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:"#ffffff",fillOpacity:1,scale:5,strokeColor:"#10D8E6",strokeWeight:2};
							dsmarkers[i].setIcon(markerIcon);
							dsmarkers[i].selected = true;
							selected = true;
						}
					}
				}
			}
			if(dsselections.indexOf(selectedpoint) < 0){
				dsselections.push(selectedpoint);
			}
		}
		*/

		/*
		function deselectDSPoints(){
			deselected = false;
			var deselectedpoint = Number(deselecteddspoints.value);
			while (deselected == false) {
				if (dsmarkers) {
					for (i in dsmarkers) {
						if(dsmarkers[i].occid==deselectedpoint){
							var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:"#ffffff",fillOpacity:1,scale:5,strokeColor:"#000000",strokeWeight:2};
							dsmarkers[i].setIcon(markerIcon);
							dsmarkers[i].selected = false;
							deselected = true;
						}
					}
				}
			}
			var index = dsselections.indexOf(deselectedpoint);
			dsselections.splice(index, 1);
		}
		*/

		/*
		function zoomToSelections(){
			var selectZoomBounds = new google.maps.LatLngBounds();
			for(var gcnt in grpArr) {
				for (var i=0; i < selections.length; i++) {
					occid = Number(selections[i]);
					if (grpArr[gcnt]) {
						for (j in grpArr[gcnt]) {
							if(grpArr[gcnt][j].occid==occid){
								var markerPos = grpArr[gcnt][j].getPosition();
								selectZoomBounds.extend(markerPos);
							}
						}
					}
				}
			}
			map.fitBounds(selectZoomBounds);
			map.panToBounds(selectZoomBounds);
		}
		*/

		<?php echo ($activateGeolocation ? "google.maps.event.addListener(window, 'load', getCoords);" : ""); ?>
	</script>
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
					<label for="modeCluster">Cluster</label><input type="radio" id="modeCluster" name="markerDsiplayMode" onclick="handleModeRadios(this);" value="cluster" checked>
					<label for="modePoints">Markers</label><input type="radio" id="modePoints" name="markerDsiplayMode" onclick="handleModeRadios(this);" value="points">
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
						<div style="border:1px black solid;margin-top:10px;padding:5px;">
							<b><?php echo (isset($LANG['MAPSEARCH_DEFAULTS']) ? $LANG['MAPSEARCH_DEFAULTS'] : 'Map Search Defaults' ); ?></b>
							<div style="margin-top:8px;">
								<div>
									<?php echo (isset($LANG['MAPSEARCH_MARKER_COLOR']) ? $LANG['MAPSEARCH_MARKER_COLOR'] : 'Default Marker Color'); ?>:
									<input class="small_color_input" name="defaultmarkercolor" id="defaultmarkercolor" type="color" onchange="resetSymbology();" />
								</div>
							</div>
						</div>
						<div style="border:1px black solid;margin-top:10px;padding:5px;">
							<b><?php echo (isset($LANG['CLUSTERING']) ? $LANG['CLUSTERING'] : 'Clustering Options'); ?></b>
							<div style="margin-top:8px;">
							</div>
							<div style="clear:both;margin-top:8px;">
							</div>
						</div>
						<div>
							<fieldset id="heatmap_options_fs">
									<legend><?php echo (isset($LANG['MAPSEARCH_HEATMAP_OPTIONS']) ? $LANG['MAPSEARCH_HEATMAP_OPTIONS'] : 'Heatmap Options'); ?></legend>
							<div style="margin-top:8px;">
							<label><input name="heatmap_dissipating" type="checkbox" onchange="heatmap_dissipating(this);">Dissipate with zoom level</label>
							<label><input type="number" min="0" step="1" name="heatmap_maxIntensity" onchange="heatmep_changeMaxIntensity(this.value);">Maximum Intensity</label>
							<label><input type="number" min="0" step="1" name="heatmap_radius" onchange="heatmep_changeRadius(this.value);">Radius</label>
							<label><input type="number" name="heatmap_opacity" value="0.60" min="0.10" max="1" step="0.05" onchange="heatmep_changeOpacity(this.value);">Opacity</label>
						</fieldset>
						</div>
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
					<div id="tabs2" style="display:none;width:379px;padding:0px;">
						<ul>
							<li><a href='occurrencelist.php?<?php echo $searchVar; ?>'><span><?php echo (isset($LANG['RECORDS']) ? $LANG['RECORDS'] : 'Records'); ?></span></a></li>
							<li><a href='#symbology'><span><?php echo (isset($LANG['COLLECTIONS']) ? $LANG['COLLECTIONS'] : 'Collections'); ?></span></a></li>
							<li><a href='#maptaxalist'><span><?php echo (isset($LANG['TAXA_LIST']) ? $LANG['TAXA_LIST'] : 'Taxa List'); ?></span></a></li>
						</ul>
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
	<div id='map' style='width:100%;height:100%;'></div>
	<div id="loadingOverlay" style="width:100%;">
		<div id="loadingImage" style="width:100px;height:100px;position:absolute;top:50%;left:50%;margin-top:-50px;margin-left:-50px;">
			<img style="border:0px;width:100px;height:100px;" src="../../images/ajax-loader.gif" />
		</div>
	</div>
</body>

</html>