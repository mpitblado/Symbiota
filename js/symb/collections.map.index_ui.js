$(document).ready(function() {
	$('#defaultmarkercolor').value = defaultMarkerColor;
	
	$('#tabs1').tabs({
		beforeLoad: function( event, ui ) {
			$(ui.panel).html("<p>Loading...</p>");
		},
		active: 1
	});
    //var hijax = function(panel) {
    //    $('.pagination a', panel).click(function(){
    //        $(panel).load(this.href, {}, function() {
    //            hijax(this);
    //        });
    //        return false;
    //    });
    //};
	$('#tabs2').tabs({
		beforeLoad: function( event, ui ) {
			$(ui.panel).html("<p>Loading...</p>");
		}//,
        //load: function(event, ui) {
        //    hijax(ui.panel);
        //}
	});
	$('#tabs3').tabs({
		beforeLoad: function( event, ui ) {
			$(ui.panel).html("<p>Loading...</p>");
		}
	});

	$("#accordion").accordion({
		collapsible: true,
		heightStyle: "fill"
	});
});

$(window).resize(function(){
	$("#accordion").accordion("refresh");
});

$(document).on("pageloadfailed", function(event, data){
    event.preventDefault();
});

function openNav() {
	document.getElementById("defaultpanel").style.width = "380px";
}

function closeNav() {
	document.getElementById("defaultpanel").style.width = "0";
}

function checkUpperLat(){
	if(document.mapsearchform.upperlat.value != ""){
		if(document.mapsearchform.upperlat_NS.value=='N'){
			document.mapsearchform.upperlat.value = Math.abs(parseFloat(document.mapsearchform.upperlat.value));
		}
		else{
			document.mapsearchform.upperlat.value = -1*Math.abs(parseFloat(document.mapsearchform.upperlat.value));
		}
	}
}
		
function checkBottomLat(){
	if(document.mapsearchform.bottomlat.value != ""){
		if(document.mapsearchform.bottomlat_NS.value == 'N'){
			document.mapsearchform.bottomlat.value = Math.abs(parseFloat(document.mapsearchform.bottomlat.value));
		}
		else{
			document.mapsearchform.bottomlat.value = -1*Math.abs(parseFloat(document.mapsearchform.bottomlat.value));
		}
	}
}

function checkRightLong(){
	if(document.mapsearchform.rightlong.value != ""){
		if(document.mapsearchform.rightlong_EW.value=='E'){
			document.mapsearchform.rightlong.value = Math.abs(parseFloat(document.mapsearchform.rightlong.value));
		}
		else{
			document.mapsearchform.rightlong.value = -1*Math.abs(parseFloat(document.mapsearchform.rightlong.value));
		}
	}
}

function checkLeftLong(){
	if(document.mapsearchform.leftlong.value != ""){
		if(document.mapsearchform.leftlong_EW.value=='E'){
			document.mapsearchform.leftlong.value = Math.abs(parseFloat(document.mapsearchform.leftlong.value));
		}
		else{
			document.mapsearchform.leftlong.value = -1*Math.abs(parseFloat(document.mapsearchform.leftlong.value));
		}
	}
}

function checkPointLat(){
	if(document.mapsearchform.pointlat.value != ""){
		if(document.mapsearchform.pointlat_NS.value=='N'){
			document.mapsearchform.pointlat.value = Math.abs(parseFloat(document.mapsearchform.pointlat.value));
		}
		else{
			document.mapsearchform.pointlat.value = -1*Math.abs(parseFloat(document.mapsearchform.pointlat.value));
		}
	}
}

function checkPointLong(){
	if(document.mapsearchform.pointlong.value != ""){
		if(document.mapsearchform.pointlong_EW.value=='E'){
			document.mapsearchform.pointlong.value = Math.abs(parseFloat(document.mapsearchform.pointlong.value));
		}
		else{
			document.mapsearchform.pointlong.value = -1*Math.abs(parseFloat(document.mapsearchform.pointlong.value));
		}
	}
}

function reSymbolizeMap(type) {
	var searchForm = document.getElementById("mapsearchform");
	if (type == 'taxa') {
		document.getElementById("mapsymbology").value = 'taxa';
	}
	if (type == 'coll') {
		document.getElementById("mapsymbology").value = 'coll';
	}
	searchForm.submit();
}

function updateRadius(){
	var radiusUnits = document.getElementById("radiusunits").value;
	var radiusInMiles = document.getElementById("radiustemp").value;
	if(radiusUnits == "km"){
		radiusInMiles = radiusInMiles*0.6214; 
	}
	document.getElementById("radius").value = radiusInMiles;
}

function clearSelection() {
	if (selectedShape) {
		selectedShape.setEditable(false);
		selectedShape = null;
	}
	document.getElementById("pointlat").value = '';
	document.getElementById("pointlong").value = '';
	document.getElementById("radius").value = '';
	document.getElementById("upperlat").value = '';
	document.getElementById("leftlong").value = '';
	document.getElementById("bottomlat").value = '';
	document.getElementById("rightlong").value = '';
	document.getElementById("polycoords").value = '';
	document.getElementById("distFromMe").value = '';
	document.getElementById("noshapecriteria").style.display = "block";
	document.getElementById("polygeocriteria").style.display = "none";
	document.getElementById("circlegeocriteria").style.display = "none";
	document.getElementById("rectgeocriteria").style.display = "none";
	document.getElementById("deleteshapediv").style.display = "none";
}

function generateRandColor(){
	var hexColor = '';
	var x = Math.round(0xffffff * Math.random()).toString(16);
	var y = (6-x.length);
	var z = '000000';
	var z1 = z.substring(0,y);
	hexColor = z1 + x;
	return '#'+hexColor;
}

/*
function findSelections(c){
	if(c.checked == true){
		var activeTab = $('#tabs2').tabs("option","active");
		if(activeTab==1){
			if($('.occcheck:checked').length==$('.occcheck').length){
				document.getElementById("selectallcheck").checked = true;
			}
		}
		var selectedbox = document.getElementById("selectedpoints");
		selectedbox.value = c.value;
		selectPoints();
	}
	else if(c.checked == false){
		var activeTab = $('#tabs2').tabs("option","active");
		if(activeTab==1){
			document.getElementById("selectallcheck").checked = false;
		}
		removeSelectionRecord(c.value);
		var deselectedbox = document.getElementById("deselectedpoints");
		deselectedbox.value = c.value;
		deselectPoints();
	}
}
*/

/*
function findDsSelections(c){
	if(c.checked == true){
		var activeTab = $('#tabs3').tabs("option","active");
		if(activeTab==1){
			if($('.dsocccheck:checked').length==$('.dsocccheck').length){
				document.getElementById("dsselectallcheck").checked = true;
			}
		}
		var selectedbox = document.getElementById("selecteddspoints");
		selectedbox.value = c.value;
		selectDSPoints();
	}
	else if(c.checked == false){
		var activeTab = $('#tabs3').tabs("option","active");
		if(activeTab==1){
			document.getElementById("dsselectallcheck").checked = false;
		}
		var deselectedbox = document.getElementById("deselecteddspoints");
		deselectedbox.value = c.value;
		deselectDSPoints();
	}
}
*/

function toggleLatLongDivs(){
	var divs = document.getElementsByTagName("div");
	for (i = 0; i < divs.length; i++) {
		var obj = divs[i];
		if(obj.getAttribute("class") == "latlongdiv" || obj.getAttribute("className") == "latlongdiv"){
			if(obj.style.display=="none"){
				obj.style.display="block";
			}
			else{
				obj.style.display="none";
			}
		}
	}
}

function toggle(target){
	var ele = document.getElementById(target);
	if(ele){
		if(ele.style.display=="none"){
			ele.style.display="block";
		}
		else {
			ele.style.display="none";
		}
	}
	else{
		var divObjs = document.getElementsByTagName("div");
		for (i = 0; i < divObjs.length; i++) {
			var divObj = divObjs[i];
			if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
				if(divObj.style.display=="none"){
					divObj.style.display="block";
				}
				else {
					divObj.style.display="none";
				}
			}
		}
	}
}

function toggleCat(catid){
	toggle("minus-"+catid);
	toggle("plus-"+catid);
	toggle("cat-"+catid);
}

function selectAll(cb){
	var boxesChecked = true;
	if(!cb.checked){
		boxesChecked = false;
	}
	var f = cb.form;
	for(var i=0;i<f.length;i++){
		if(f.elements[i].name == "db[]" || f.elements[i].name == "cat[]" || f.elements[i].name == "occid[]"){ 
			f.elements[i].checked = boxesChecked;
		}
		if(f.elements[i].name == "occid[]"){
			f.elements[i].onchange();
		}
	}
}

function uncheckAll(f){
	document.getElementById('dballcb').checked = false;
}

function selectAllCat(cb,target){
	var boxesChecked = true;
	if(!cb.checked){
		boxesChecked = false;
	}
	var inputObjs = document.getElementsByTagName("input");
	for (i = 0; i < inputObjs.length; i++) {
		var inputObj = inputObjs[i];
		if(inputObj.getAttribute("class") == target || inputObj.getAttribute("className") == target){
			inputObj.checked = boxesChecked;
		}
	}
}

function unselectCat(catTarget){
	var catObj = document.getElementById(catTarget);
	catObj.checked = false;
	uncheckAll();
}

function verifyCollForm(f){
	var formVerified = false;
	for(var h=0;h<f.length;h++){
		if(f.elements[h].name == "db[]" && f.elements[h].checked){
			formVerified = true;
			break;
		}
		if(f.elements[h].name == "cat[]" && f.elements[h].checked){
			formVerified = true;
			break;
		}
	}
	if(!formVerified){
		alert("Please choose at least one collection!");
		return false;
	}
	else{
		for(var i=0;i<f.length;i++){
			if(f.elements[i].name == "cat[]" && f.elements[i].checked){
				if(document.getElementById('cat-'+f.elements[i].value)){
					//Uncheck all db input elements within cat div 
					var childrenEle = document.getElementById('cat-'+f.elements[i].value).children;
					for(var j=0;j<childrenEle.length;j++){
						if(childrenEle[j].tagName == "DIV"){
							var divChildren = childrenEle[j].children;
							for(var k=0;k<divChildren.length;k++){
								var divChildren2 = divChildren[k].children;
								for(var l=0;l<divChildren2.length;l++){
									if(divChildren2[l].tagName == "INPUT"){
										divChildren2[l].checked = false;
									}
								}
							}
						}
					}
				}
			}
		}
	}
	//make sure they have filled out at least one field.
	if((f.taxa.value == '') && (f.country.value == '') && (f.state.value == '') && (f.county.value == '') && 
		(f.locality.value == '') && (f.upperlat.value == '') && (f.pointlat.value == '') && 
		(f.collector.value == '') && (f.collnum.value == '') && (f.eventdate1.value == '')){
        alert("Please fill in at least one search parameter!");
        return false;
    }
 
    if(f.upperlat.value != '' || f.bottomlat.value != '' || f.leftlong.value != '' || f.rightlong.value != ''){
        // if Lat/Long field is filled in, they all should have a value!
        if(f.upperlat.value == '' || f.bottomlat.value == '' || f.leftlong.value == '' || f.rightlong.value == ''){
			alert("Error: Please make all Lat/Long bounding box values contain a value or all are empty");
			return false;
        }

		// Check to make sure lat/longs are valid.
		if(Math.abs(f.upperlat.value) > 90 || Math.abs(f.bottomlat.value) > 90 || Math.abs(f.pointlat.value) > 90){
			alert("Latitude values can not be greater than 90 or less than -90.");
			return false;
		} 
		if(Math.abs(f.leftlong.value) > 180 || Math.abs(f.rightlong.value) > 180 || Math.abs(f.pointlong.value) > 180){
			alert("Longitude values can not be greater than 180 or less than -180.");
			return false;
		} 
		if(parseFloat(f.upperlat.value) < parseFloat(f.bottomlat.value)){
			alert("Your northern latitude value is less then your southern latitude value. Please correct this.");
			return false;
		}
		if(parseFloat(f.leftlong.value) > parseFloat(f.rightlong.value)){
			alert("Your western longitude value is greater then your eastern longitude value. Please correct this. Note that western hemisphere longitudes in the decimal format are negitive.");
			return false;
		}
    }

	//Same with point radius fields
    if(f.pointlat.value != '' || f.pointlong.value != '' || f.radius.value != ''){
    	if(f.pointlat.value == '' || f.pointlong.value == '' || f.radius.value == ''){
    		alert("Error: Please make all Lat/Long point-radius values contain a value or all are empty");
			return false;
		}
	}
    return true;
}

function resetQueryForm(f){
	$('input[name=taxa]').val('');
	$('input[name=country]').val('');
	$('input[name=state]').val('');
	$('input[name=county]').val('');
	$('input[name=local]').val('');
	$('input[name=collector]').val('');
	$('input[name=collnum]').val('');
	$('input[name=eventdate1]').val('');
	$('input[name=eventdate2]').val('');
	$('input[name=catnum]').val('');
	$('input[name=othercatnum]').val('');
	$('input[name=typestatus]').attr('checked', false);
	$('input[name=hasimages]').attr('checked', false);
	$('input[name=hasgenetic]').attr('checked', false);
	$('input[name=includecult]').attr('checked', false);
	deleteSelectedShape();
}

function checkKey(e){
	var key;
	if(window.event){
		key = window.event.keyCode;
	}else{
		key = e.keyCode;
	}
	if(key == 13){
		document.collections.submit();
	}
}

function shiftKeyBox(tid){
	var currentkeys = document.getElementById("symbologykeysbox").innerHTML;
	var keyDivName = tid+"keyrow";
	var colorBoxName = "taxaColor"+tid;
	var newKeyToAdd = document.getElementById(keyDivName).innerHTML;
	document.getElementById(colorBoxName).color.hidePicker();
	document.getElementById("symbologykeysbox").innerHTML = currentkeys+newKeyToAdd;
}

function prepSelectionKml(f){
	if(f.kmltype.value=='dsselectionquery'){
		if(dsselections.length!=0){
			var jsonSelections = JSON.stringify(dsselections);
		}
		else{
			alert("Please select records from the dataset to create KML file.");
			return;
		}
	}
	else{
		var jsonSelections = JSON.stringify(selections);
	}
	f.selectionskml.value = jsonSelections;
	f.starrkml.value = starr;
	f.submit();
}

function closeInfoWin(){
	if(InformationWindow){
        InformationWindow.close();
	}
}

function openInfoWin(occid){
	google.maps.event.trigger(markerArr[occid], 'click');
}

function openIndPopup(occid,clid){
	openPopup('../individual/index.php?occid='+occid+'&clid='+clid);
}

function openPopup(urlStr){
	var wWidth = 1000;
	try{
		if(opener.document.body.offsetWidth) wWidth = opener.document.body.offsetWidth*0.95;
		if(wWidth > 1400) wWidth = 1400;
	}
	catch(err){
	}
	newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
	if (newWindow.opener == null) newWindow.opener = self;
	return false;
}

function setClusterGridSize(myGridSize){
	markerGrid.grdSize = myGridSize;
	if(!document.getElementById("clusteroff").checked){
		clusterMap();
	}

}

function setClustering(){
	
	if(document.getElementById("clusteroff").checked==true){
		unclusterMap();
	}
	else{
		clusterMap();
	}
	
}

function getSortedKeys(obj) {
	// this function is used to retreive a "sorted" associative array - sorted by value (key:value)
	// By iterating through the returned array sequentially you can access the original array in sorted order. 
    var keys = []; for(var key in obj) keys.push(key);
    return keys.sort(function(a,b){return obj[a].localeCompare(obj[b]);});
}

function activateMapOtions(){
	$("#accordion").accordion("option", {
		active: 0
	});
	document.getElementById('ui-id-3').click();
}

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

function renderRecordsRow(){
	let tableHTML = '';
	for(let i = 0; i < recordsArr.length; i++){
		tableHTML += recordsArr[i];
	}
	return tableHTML;
}

function buildRecordsTable(){
	let recordsTableHTMLTempplate = `
		<div style="height:25px;margin-top:-5px;">
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


function buildRecordTableRow(myRecord){
	let rowHTML = "";
	rowHTML += `
		<tr id="tr${myRecord.occid}" >
			<td id="cat${myRecord.occid}" >${myRecord.catalognumber}</td>
			<td id="label${myRecord.occid}" ><a href="#" onclick="openIndPopup(${myRecord.occid}); return false;">${myRecord.recordedby}${(myRecord.recordnumber ? ' ' + myRecord.recordnumber : '')}</a></td>
			<td id="e${myRecord.occid}" >${myRecord.eventdate}</td>
			<td id="s${myRecord.occid}" >${myRecord.sciname}</td>
		</tr>
	`;

	recordsArr.push(rowHTML);
	
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

