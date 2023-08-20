<?php
include_once('../../config/symbini.php');
@include_once('Image/Barcode.php');
@include_once('Image/Barcode2.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLabel.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/reports/labelmanager.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/reports/labelmanager.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/reports/labelmanager.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/reports/labelmanager.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = $_REQUEST['collid'];
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

//Sanitation
if(!is_numeric($collid)) $collid = 0;

$labelManager = new OccurrenceLabel();
$labelManager->setCollid($collid);

$isEditor = 0;
$occArr = array();
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
	$isEditor = 1;
}
elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"])){
	$isEditor = 1;
}
if($isEditor){
	if($action == "Filter Specimen Records"){
		$occArr = $labelManager->queryOccurrences($_POST);
	}
}
$labelFormatArr = $labelManager->getLabelFormatArr(true);
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
		<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['SPEC_LABEL_MNG']; ?></title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<script type="text/javascript">
			<?php
			if($labelFormatArr) echo "var labelFormatObj = ".json_encode($labelFormatArr).";";
			?>

			function selectAll(cb){
				boxesChecked = true;
				if(!cb.checked){
					boxesChecked = false;
				}
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					dbElement.checked = boxesChecked;
				}
			}

			function validateQueryForm(f){
				if(!validateDateFields(f)){
					return false;
				}
				return true;
			}

			function validateDateFields(f){
				var status = true;
				var validformat1 = /^\s*\d{4}-\d{2}-\d{2}\s*$/ //Format: yyyy-mm-dd
				if(f.date1.value !== "" && !validformat1.test(f.date1.value)) status = false;
				if(f.date2.value !== "" && !validformat1.test(f.date2.value)) status = false;
				if(!status) alert("<?php echo $LANG['DATE_ERROR']; ?>");
				return status;
			}

			function validateSelectForm(f){
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked){
						var quantityObj = document.getElementsByName("q-"+dbElement.value);
						if(quantityObj && quantityObj[0].value > 0) return true;
					}
				}
			   	alert("<?php echo $LANG['CHECKBOX_ERROR']; ?>");
			  	return false;
			}

			function openIndPopup(occid){
				openPopup('../individual/index.php?occid=' + occid);
			}

			function openEditorPopup(occid){
				openPopup('../editor/occurrenceeditor.php?occid=' + occid);
			}

			function openPopup(urlStr){
				var wWidth = 900;
				if(document.body.offsetWidth) wWidth = document.body.offsetWidth*0.9;
				if(wWidth > 1200) wWidth = 1200;
				newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
				if (newWindow.opener == null) newWindow.opener = self;
				return false;
			}

			function changeFormExport(buttonElem, action, target){
				var f = buttonElem.form;
				if(action == "labeldynamic.php" && buttonElem.value == "<?php echo $LANG['PRINT_BROWSER']; ?>"){
					if(!f["labelformatindex"] || f["labelformatindex"].value == ""){
						alert("<?php echo $LANG['SEL_LABEL_PROFILE']; ?>");
						return false;
					}
				}
				else if(action == "labelsword.php" && f.labeltype.valye == "packet"){
					alert("<?php echo $LANG['PACKETS_NOT_AVAILABLE']; ?>");
					return false;
				}
				if(f.bconly && f.bconly.checked && action == "labeldynamic.php") action = "barcodes.php";
				f.action = action;
				f.target = target;
				return true;
			}

			function checkPrintOnlyCheck(f){
				if(f.bconly.checked){
					f.speciesauthors.checked = false;
					f.catalognumbers.checked = false;
					f.bc.checked = false;
					f.symbbc.checked = false;
				}
			}

			function checkBarcodeCheck(f){
				if(f.bc.checked || f.symbbc.checked || f.speciesauthors.checked || f.catalognumbers.checked){
					f.bconly.checked = false;
				}
			}

			function labelFormatChanged(selObj){
				if(selObj && labelFormatObj){
					var catStr = selObj.value.substring(0,1);
					var labelIndex = selObj.value.substring(2);
					var f = document.selectform;
					if(catStr != ''){
						f.hprefix.value = labelFormatObj[catStr][labelIndex].labelHeader.prefix;
						var midIndex = labelFormatObj[catStr][labelIndex].labelHeader.midText;
						document.getElementById("hmid"+midIndex).checked = true;
						f.hsuffix.value = labelFormatObj[catStr][labelIndex].labelHeader.suffix;
						f.lfooter.value = labelFormatObj[catStr][labelIndex].labelFooter.textValue;
						if(labelFormatObj[catStr][labelIndex].displaySpeciesAuthor == 1) f.speciesauthors.checked = true;
						else f.speciesauthors.checked = false;
						if(f.bc){
							if(labelFormatObj[catStr][labelIndex].displayBarcode == 1) f.bc.checked = true;
							else f.bc.checked = false;
						}
						f.labeltype.value = labelFormatObj[catStr][labelIndex].labelType;
					}
				}
			}
		</script>
		<style>
			fieldset{ margin:10px; padding:15px; }
			fieldset legend{ font-weight:bold; }
			.fieldDiv{ clear:both; padding:5px 0px; margin:5px 0px }
			.fieldLabel{ font-weight: bold; display:block }
			.checkboxLabel{ font-weight: bold; }
			.fieldElement{  }
		</style>
	</head>
	<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'><?php echo $LANG['HOME']; ?></a> &gt;&gt;
		<?php
		if(stripos(strtolower($labelManager->getMetaDataTerm('colltype')), "observation") !== false){
			echo '<a href="../../profile/viewprofile.php?tabindex=1">' . $LANG['PERS_MNG_MENU'] . '</a> &gt;&gt; ';
		}
		else{
			echo '<a href="../misc/collprofiles.php?collid=' . htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS) . '&emode=1">' . $LANG['COL_MNG_PANEL'] . '</a> &gt;&gt; ';
		}
		?>
		<b><?php echo $LANG['LABEL_PRINTING']; ?></b>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($isEditor){
			$reportsWritable = false;
			if(is_writable($SERVER_ROOT.'/temp/report')) $reportsWritable = true;
			if(!$reportsWritable){
				?>
				<div style="padding:5px;">
					<span style="color:red;"><?php echo $LANG['MAKE_WRITABLE']; ?></span>
				</div>
				<?php
			}
			$isGeneralObservation = (($labelManager->getMetaDataTerm('colltype') == 'General Observations')?true:false);
			echo '<h2>'.$labelManager->getCollName().'</h2>';
			?>
			<div>
				<form name="datasetqueryform" action="labelmanager.php" method="post" onsubmit="return validateQueryForm(this)">
					<fieldset>
						<legend><b><?php echo $LANG['DEFINE_RECORDSET']; ?></b></legend>
						<div style="margin:3px;">
							<div title="<?php echo $LANG['SCINAME_AS_ENTERED']; ?>">
								<?php echo $LANG['SCINAME']; ?>:
								<input type="text" name="taxa" id="taxa" size="60" value="<?php echo (array_key_exists('taxa',$_REQUEST)?$_REQUEST['taxa']:''); ?>" />
							</div>
						</div>
						<div style="margin:3px;clear:both;">
							<div style="float:left;" title="<?php echo $LANG['COLLECTOR_AS_ENTERED']; ?>">
								<?php echo $LANG['COLLECTOR']; ?>:
								<input type="text" name="recordedby" style="width:150px;" value="<?php echo (array_key_exists('recordedby',$_REQUEST)?$_REQUEST['recordedby']:''); ?>" />
							</div>
							<div style="float:left;margin-left:20px;" title="<?php echo $LANG['SEPARATE_RANGES']; ?>">
								<?php echo $LANG['RECORD_NUMBERS']; ?>:
								<input type="text" name="recordnumber" style="width:150px;" value="<?php echo (array_key_exists('recordnumber',$_REQUEST)?$_REQUEST['recordnumber']:''); ?>" />
							</div>
							<div style="float:left;margin-left:20px;" title="<?php echo $LANG['SEPARATE_MULTIPLE_TERMS']; ?>">
								<?php echo $LANG['CATNOS']; ?>:
								<input type="text" name="identifier" style="width:150px;" value="<?php echo (array_key_exists('identifier',$_REQUEST)?$_REQUEST['identifier']:''); ?>" />
							</div>
						</div>
						<div style="margin:3px;clear:both;">
							<div style="float:left;">
								<?php echo $LANG['ENTERED_BY']; ?>:
								<input type="text" name="recordenteredby" value="<?php echo (array_key_exists('recordenteredby',$_REQUEST)?$_REQUEST['recordenteredby']:''); ?>" style="width:100px;" title="<?php echo $LANG['ENTERED_BY_EXPLAIN']; ?>" />
							</div>
							<div style="margin-left:20px;float:left;" title="">
								<?php echo $LANG['DATE_RANGE']; ?>:
								<input type="text" name="date1" style="width:100px;" value="<?php echo (array_key_exists('date1',$_REQUEST)?$_REQUEST['date1']:''); ?>" onchange="validateDateFields(this.form)" /> to
								<input type="text" name="date2" style="width:100px;" value="<?php echo (array_key_exists('date2',$_REQUEST)?$_REQUEST['date2']:''); ?>" onchange="validateDateFields(this.form)" />
								<select name="datetarget">
									<option value="dateentered"><?php echo $LANG['DATE_ENTERED']; ?></option>
									<option value="datelastmodified" <?php echo (isset($_POST['datetarget']) && $_POST['datetarget'] == 'datelastmodified'?'SELECTED':''); ?>><?php echo $LANG['DATE_MODIFIED']; ?></option>
									<option value="eventdate"<?php echo (isset($_POST['datetarget']) && $_POST['datetarget'] == 'eventdate'?'SELECTED':''); ?>><?php echo $LANG['DATE_COLLECTED']; ?></option>
								</select>
							</div>
						</div>
						<div style="margin:3px;clear:both;">
							<?php echo $LANG['LABEL_PROJECTS']; ?>:
							<select name="labelproject" >
								<option value=""><?php echo $LANG['ALL_PROJECTS']; ?></option>
								<option value="">-------------------------</option>
								<?php
								$lProj = '';
								if(array_key_exists('labelproject',$_REQUEST)) $lProj = $_REQUEST['labelproject'];
								$lProjArr = $labelManager->getLabelProjects();
								foreach($lProjArr as $projStr){
									echo '<option '.($lProj==$projStr?'SELECTED':'').'>'.$projStr.'</option>'."\n";
								}
								?>
							</select>
							<!--
							Dataset Projects:
							<select name="datasetproject" >
								<option value=""></option>
								<option value="">-------------------------</option>
								<?php
								/*
								$datasetProj = '';
								if(array_key_exists('datasetproject',$_REQUEST)) $datasetProj = $_REQUEST['datasetproject'];
								$dProjArr = $labelManager->getDatasetProjects();
								foreach($dProjArr as $dsid => $dsProjStr){
									echo '<option id="'.$dsid.'" '.($datasetProj==$dsProjStr?'SELECTED':'').'>'.$dsProjStr.'</option>'."\n";
								}
								*/
								?>
							</select>
							-->
							<?php
							echo '<span style="margin-left:15px;"><input name="extendedsearch" type="checkbox" value="1" '.(array_key_exists('extendedsearch', $_POST)?'checked':'').' /></span> ';
							if($isGeneralObservation) echo $LANG['SEARCH_OUTSIDE_USER'];
							else echo $LANG['SEARCH_WITHIN_COLS'];
							?>
						</div>
						<div style="clear:both;">
							<div style="margin-left:20px;float:left;">
								<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
								<button type="submit" name="submitaction" value="Filter Specimen Records" ><?php echo $LANG['FILTER_SPEC_RECORDS']; ?></button>
							</div>
							<div style="margin-left:20px;float:left;">
								* <?php echo $LANG['SPEC_RETRUN_LIMIT']; ?>
							</div>
						</div>
					</fieldset>
				</form>
				<div style="clear:both;">
					<?php
					if($action == "Filter Specimen Records"){
						if($occArr){
							?>
							<form name="selectform" id="selectform" action="labeldynamic.php" method="post" onsubmit="return validateSelectForm(this);">
								<table class="styledtable" style="font-family:Arial;font-size:12px;">
									<tr>
										<th title="Select/Deselect all Specimens"><input type="checkbox" onclick="selectAll(this);" /></th>
										<th title="Label quantity"><?php echo $LANG['QUANTITY']; ?></th>
										<th><?php echo $LANG['COLLECTOR']; ?></th>
										<th><?php echo $LANG['SCINAME']; ?></th>
										<th><?php echo $LANG['LOCALITY']; ?></th>
									</tr>
									<?php
									$trCnt = 0;
									foreach($occArr as $occId => $recArr){
										$trCnt++;
										?>
										<tr <?php echo ($trCnt%2?'class="alt"':''); ?>>
											<td>
												<input type="checkbox" name="occid[]" value="<?php echo $occId; ?>" />
											</td>
											<td>
												<input type="text" name="q-<?php echo $occId; ?>" value="<?php echo $recArr["q"]; ?>" style="width:20px;border:inset;" title="<?php echo $LANG['LABEL_QTY']; ?>" />
											</td>
											<td>
												<a href="#" onclick="openIndPopup(<?php echo $occId; ?>); return false;">
													<?php echo $recArr["c"]; ?>
												</a>
												<?php
												if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($recArr["collid"],$USER_RIGHTS["CollAdmin"])) || (array_key_exists("CollEditor",$USER_RIGHTS) && in_array($recArr["collid"],$USER_RIGHTS["CollEditor"]))){
													if(!$isGeneralObservation || $recArr['uid'] == $SYMB_UID){
														?>
														<a href="#" onclick="openEditorPopup(<?php echo $occId; ?>); return false;">
															<img src="../../images/edit.png" />
														</a>
														<?php
													}
												}
												?>
											</td>
											<td>
												<?php echo $recArr["s"]; ?>
											</td>
											<td>
												<?php echo $recArr["l"]; ?>
											</td>
										</tr>
										<?php
									}
									?>
								</table>
								<fieldset style="margin-top:15px;">
									<legend><?php echo $LANG['LABEL_PRINTING']; ?></legend>
										<div class="fieldDiv">
											<div class="fieldLabel"><?php echo $LANG['LABEL_PROFILES']; ?>:
												<?php
												echo '<span title="Open label profile manager"><a href="labelprofile.php?collid=' . htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS) . '"><img src="../../images/edit.png" style="width:13px" /></a></span>';
												?>
											</div>
											<div class="fieldElement">
												<div>
													<select name="labelformatindex" onchange="labelFormatChanged(this)">
														<option value=""><?php echo $LANG['SEL_LABEL_FORMAT']; ?></option>
														<?php
														foreach($labelFormatArr as $cat => $catArr){
															echo '<option value="">---------------------------</option>';
															foreach($catArr as $k => $labelArr){
																echo '<option value="'.$cat.'-'.$k.'">'.$labelArr['title'].'</option>';
															}
														}
														?>
													</select>
												</div>
												<?php
												if(!$labelFormatArr) echo '<b>' . $LANG['NO_LABEL_PROFILES'] . '</b>';
												?>
											</div>
										</div>
									<div class="fieldDiv">
										<div class="fieldLabel"><?php echo $LANG['HEADING_PREFIX']; ?>:</div>
										<div class="fieldElement">
											<input type="text" name="hprefix" value="" style="width:450px" /> <?php echo $LANG['HEADING_EXAMPLES']; ?>
										</div>
									</div>
									<div class="fieldDiv">
										<div class="checkboxLabel"><?php echo $LANG['HEADING_MIDSECTION']; ?>:</div>
										<div class="fieldElement">
											<input type="radio" id="hmid1" name="hmid" value="1" /><?php echo $LANG['COUNTRY']; ?>
											<input type="radio" id="hmid2" name="hmid" value="2" /><?php echo $LANG['STATE']; ?>
											<input type="radio" id="hmid3" name="hmid" value="3" /><?php echo $LANG['COUNTY']; ?>
											<input type="radio" id="hmid4" name="hmid" value="4" /><?php echo $LANG['FAMILY']; ?>
											<input type="radio" id="hmid0" name="hmid" value="0" checked/><?php echo $LANG['BLANK']; ?>
										</div>
									</div>
									<div class="fieldDiv">
										<span class="fieldLabel"><?php echo $LANG['HEADING_SUFFIX']; ?>:</span>
										<span class="fieldElement">
											<input type="text" name="hsuffix" value="" style="width:450px" />
										</span>
									</div>
									<div class="fieldDiv">
										<span class="fieldLabel"><?php echo $LANG['LABEL_FOOTER']; ?>:</span>
										<span class="fieldElement">
											<input type="text" name="lfooter" value="" style="width:450px" />
										</span>
									</div>
									<div class="fieldDiv">
										<input type="checkbox" name="speciesauthors" value="1" onclick="checkBarcodeCheck(this.form);" />
										<span class="checkboxLabel"><?php echo $LANG['PRINT_AUTHORS']; ?></span>
									</div>
									<div class="fieldDiv">
										<input type="checkbox" name="catalognumbers" value="1" onclick="checkBarcodeCheck(this.form);" />
										<span class="checkboxLabel"><?php echo $LANG['PRINT_CATNO']; ?></span>
									</div>
									<?php
									if(class_exists('Image_Barcode2') || class_exists('Image_Barcode')){
										?>
										<div class="fieldDiv">
											<input type="checkbox" name="bc" value="1" onclick="checkBarcodeCheck(this.form);" />
											<span class="checkboxLabel"><?php echo $LANG['INCLUDE_CATNO_BARCODE']; ?></span>
										</div>
										<!--
										<div class="fieldDiv">
											<input type="checkbox" name="symbbc" value="1" onclick="checkBarcodeCheck(this.form);" />
											<span class="checkboxLabel">Include barcode of Symbiota Identifier</span>
										</div>
										 -->
										<div class="fieldDiv">
											<input type="checkbox" name="bconly" value="1" onclick="checkPrintOnlyCheck(this.form);" />
											<span class="checkboxLabel"><?php echo $LANG['PRINT_ONLY_BARCODE']; ?></span>
										</div>
										<?php
									}
									?>
									<div class="fieldDiv">
										<span class="fieldLabel"><?php echo $LANG['LABEL_TYPE']; ?>:</span>
										<span class="fieldElement">
											<select name="labeltype">
												<option value="1"><?php echo "1 " . $LANG['COLUMNS_PER_PAGE']; ?></option>
												<option value="2" selected><?php echo "2 " . $LANG['COLUMNS_PER_PAGE']; ?></option>
												<option value="3"><?php echo "3 " . $LANG['COLUMNS_PER_PAGE']; ?></option>
												<option value="4"><?php echo "4 " . $LANG['COLUMNS_PER_PAGE']; ?></option>
												<option value="5"><?php echo "5 " . $LANG['COLUMNS_PER_PAGE']; ?></option>
												<option value="6"><?php echo "6 " . $LANG['COLUMNS_PER_PAGE']; ?></option>
												<option value="7"><?php echo "7 " . $LANG['COLUMNS_PER_PAGE']; ?></option>
												<option value="packet"><?php echo $LANG['PACKET_LABELS']; ?></option>
											</select>
										</span>
									</div>
									<div style="float:left;margin: 15px 50px;">
										<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
										<div style="margin:10px">
											<button type="submit" name="submitaction" onclick="return changeFormExport(this,'labeldynamic.php','_blank');" value="Print in Browser" <?php echo ($labelFormatArr?'':'DISABLED title="' . $LANG['BROWSER_NOT_ACTIVATED'] . '"'); ?> ><?php echo $LANG['PRINT_BROWSER']; ?></button>
										</div>
										<div style="margin:10px">
											<button type="submit" name="submitaction" onclick="return changeFormExport(this,'labeldynamic.php','_self');" value="Export to CSV" ><?php echo $LANG['EXPORT_CSV']; ?></button>
										</div>
										<?php
										if($reportsWritable){
											?>
											<div style="margin:10px">
												<button type="submit" name="submitaction" onclick="return changeFormExport(this,'labelsword.php','_self');" value="Export to DOCX" ><?php echo $LANG['EXPORT_DOCX']; ?></button>
											</div>
											<?php
										}
										?>
									</div>
										<?php
										if($reportsWritable){
											?>
											<div style="clear:both;padding:10px 0px">
												<?php echo $LANG['DOCX_EXPLAIN']; ?>
											</div>
											<?php
										}
										?>
								</fieldset>
							</form>
							<?php
						}
						else{
							?>
							<div style="font-weight:bold;margin:20px;font-weight:150%;">
								<?php echo $LANG['QUERY_RETURNED_NONE']; ?>
							</div>
							<?php
						}
					}
					?>
				</div>
			</div>
			<?php
		}
		else{
			?>
			<div style="font-weight:bold;margin:20px;font-weight:150%;">
				<?php echo $LANG['NO_LABEL_PERMISSIONS']; ?>
			</div>
			<?php
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
	</body>
</html>