function initializeGoogleMap() {
			
	pointBounds = new google.maps.LatLngBounds();
	panPoint = new google.maps.LatLng();
	heatMapData = new google.maps.MVCArray();

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
		dissipating: true,
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

	//createShape();
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


function checkRecordLimit(f) {
	var recordLimit = document.getElementById("recordlimit").value;
	if(!isNaN(recordLimit) && recordLimit > 0){
		if (recordLimit > 50000) {
			alert("Record limit cannot exceed 50000.");
			document.getElementById("recordlimit").value = 5000;
			return;
		}
		if (recordLimit <= 50000) {
			if(recordLimit > 5000){
				if(confirm('Increasing the record limit can cause delays in loading the map, or for your browser to crash.')){
					return true;
				}
				else{
					document.getElementById("recordlimit").value = 5000;
				}
			}
		}
	}
	else{
		document.getElementById("recordlimit").value = 5000;
		alert("Record Limit must be set to a whole number greater than zero.");
	}
}

function buildHeatMapData(){
	heatMapData = new google.maps.MVCArray();
	for (var i in allMarkers){
		if (allMarkers[i].visible){
			heatMapData.push(allMarkers[i].getPosition());
		}
	}
	heatmap.setData(heatMapData);			
}

function heatmap_dissipating(checkbox){
	let dissipating = false;
	if (checkbox.checked) dissipating = true;
	heatmap.set("dissipating", dissipating);
}

function heatmep_changeRadius(radius = 20){
	heatmap.set("radius", radius);
  }
  
  function heatmep_changeOpacity(opacity = 0.6){
	heatmap.set("opacity", opacity);
  }

  function heatmep_changeMaxIntensity(maxIntensity = 10){
	heatmap.set("maxIntensity", maxIntensity);
  }


function enableHeatmap(){
	heatmap.setMap(map);
}

function disableHeatmap(){
	heatmap.setMap(null);
}

function setSelection(shape) {
	clearSelection();
	var selectedShapeType = shape.type;
	selectedShape = shape;
	selectedShape.setEditable(true);
	if (selectedShapeType == 'circle') {
		getCircleCoords(shape);
	}
	if (selectedShapeType == 'rectangle') {
		getRectangleCoords(shape);
	}
	if (selectedShapeType == 'polygon') {
		getPolygonCoords(shape);
	}
}

function deleteSelectedShape() {
	if (selectedShape){
		selectedShape.setMap(null);
		clearSelection();
	}
}

function getCircleCoords(circle) {
	var rad = (circle.getRadius());
	var radius = (rad/1000)*0.6214;
	document.getElementById("pointlat").value = (circle.getCenter().lat());
	document.getElementById("pointlong").value = (circle.getCenter().lng());
	document.getElementById("radius").value = radius;
	document.getElementById("upperlat").value = '';
	document.getElementById("leftlong").value = '';
	document.getElementById("bottomlat").value = '';
	document.getElementById("rightlong").value = '';
	document.getElementById("polycoords").value = '';
	document.getElementById("distFromMe").value = '';
	document.getElementById("noshapecriteria").style.display = "none";
	document.getElementById("polygeocriteria").style.display = "none";
	document.getElementById("circlegeocriteria").style.display = "block";
	document.getElementById("rectgeocriteria").style.display = "none";
	document.getElementById("deleteshapediv").style.display = "block";
	pointBounds = circle.getBounds();
}
  
function getRectangleCoords(rectangle) {
	document.getElementById("upperlat").value = (rectangle.getBounds().getNorthEast().lat());
	document.getElementById("rightlong").value = (rectangle.getBounds().getNorthEast().lng());
	document.getElementById("bottomlat").value = (rectangle.getBounds().getSouthWest().lat());
	document.getElementById("leftlong").value = (rectangle.getBounds().getSouthWest().lng());
	document.getElementById("pointlat").value = '';
	document.getElementById("pointlong").value = '';
	document.getElementById("radius").value = '';
	document.getElementById("polycoords").value = '';
	document.getElementById("distFromMe").value = '';
	document.getElementById("noshapecriteria").style.display = "none";
	document.getElementById("polygeocriteria").style.display = "none";
	document.getElementById("circlegeocriteria").style.display = "none";
	document.getElementById("rectgeocriteria").style.display = "block";
	document.getElementById("deleteshapediv").style.display = "block";
	pointBounds = rectangle.getBounds();
}
  
function getPolygonCoords(polygon) {
	var coordinates = [];
	var coordinatesMVC = (polygon.getPath().getArray());
	for(var i=0;i<coordinatesMVC.length;i++){
		pointBounds.extend(coordinatesMVC[i]);
		var mvcString = coordinatesMVC[i].toString();
		mvcString = mvcString.slice(1, -1);
		var latlngArr = mvcString.split(",");
		coordinates.push(parseFloat(latlngArr[0]).toFixed(6)+" "+parseFloat(latlngArr[1]).toFixed(6));
	}
	if(coordinates[0] != coordinates[i]) coordinates.push(coordinates[0]);
	document.getElementById("polycoords").value = "POLYGON(("+coordinates.toString()+"))";
	document.getElementById("pointlat").value = '';
	document.getElementById("pointlong").value = '';
	document.getElementById("radius").value = '';
	document.getElementById("upperlat").value = '';
	document.getElementById("leftlong").value = '';
	document.getElementById("bottomlat").value = '';
	document.getElementById("rightlong").value = '';
	document.getElementById("distFromMe").value = '';
	document.getElementById("noshapecriteria").style.display = "none";
	document.getElementById("polygeocriteria").style.display = "block";
	document.getElementById("circlegeocriteria").style.display = "none";
	document.getElementById("rectgeocriteria").style.display = "none";
	document.getElementById("deleteshapediv").style.display = "block";
}

function changeMarkersColor(iconColor,markers){
	iconColor = iconColor.replace('#','');
	if (markers) {
		for (i in markers) {
			if(markers[i].recordType=='obs'){
				if(markers[i].selected==true){
					var markerIcon = {
						url: clientRoot+'/collections/map/coloricon.php?shape=triangle&color=' + iconColor,
						scaledSize: new google.maps.Size(18, 18), 
					}
				}
				else{
					var markerIcon = {
						url: clientRoot+'/collections/map/coloricon.php?shape=triangle&color=' + iconColor,
						scaledSize: new google.maps.Size(18, 18), 
					}
				}
				//markers[i].color = v;
				markers[i].setIcon(markerIcon);
			}
			if(markers[i].recordType=='spec'){
				if(markers[i].selected==true){
					var markerIcon = {
						url: clientRoot+'/collections/map/coloricon.php?shape=circle&color=' + iconColor,
						scaledSize: new google.maps.Size(18, 18), 
					}
				}
				else{
					var markerIcon = {
						url: clientRoot+'/collections/map/coloricon.php?shape=circle&color=' + iconColor,
						scaledSize: new google.maps.Size(18, 18), 
					}
				}
				//markers[i].color = v;
				markers[i].setIcon(markerIcon);
			}
		}
	}
}

function selectMarker(marker){
	if(marker.recordType=='obs'){
		var markerIcon = {
			url: 'coloricon.php?shape=triangle&color=00ff00',
			scaledSize: new google.maps.Size(20, 20), 
		};
		marker.setIcon(markerIcon);
	}
	if(marker.recordType=='spec'){
		var markerIcon = {
			url: 'coloricon.php?shape=circle&color=00ff00',
			scaledSize: new google.maps.Size(20, 20), 
		};
		marker.setIcon(markerIcon);
	}
	marker.selected = true;
}

function selectDsMarker(marker){
	var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:"#ffffff",fillOpacity:1,scale:5,strokeColor:"#10D8E6",strokeWeight:2};
	marker.setIcon(markerIcon);
	marker.selected = true;
}

function deselectMarker(marker){
	if(marker.recordType=='obs'){
		var markerIcon = {
			url: 'coloricon.php?shape=triangle&color='+marker.color,
			scaledSize: new google.maps.Size(18, 18), 
		};
		marker.setIcon(markerIcon);
	}
	if(marker.recordType=='spec'){
		var markerIcon = {
			url: 'coloricon.php?shape=circle&color='+marker.color,
			scaledSize: new google.maps.Size(18, 18), 
		};
		marker.setIcon(markerIcon);
	}
	marker.selected = false;
}

function deselectDsMarker(marker){
	var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:"#ffffff",fillOpacity:1,scale:5,strokeColor:"#000000",strokeWeight:2};
	marker.setIcon(markerIcon);
	marker.selected = false;
}

