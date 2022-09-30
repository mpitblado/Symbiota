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

