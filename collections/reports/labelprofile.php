<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLabel.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/reports/labelprofile.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/reports/labelprofile.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/reports/labelprofile.en.php');
header('Content-Type: text/html; charset='.$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/reports/labelprofile.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

//Sanitation
if(!is_numeric($collid)) $collid = 0;

$labelManager = new OccurrenceLabel();
$labelManager->setCollid($collid);

$isEditor = 0;
if($SYMB_UID) $isEditor = 1;
if($collid && array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin'])) $isEditor = 2;
if($IS_ADMIN) $isEditor = 3;
$statusStr = '';
if($isEditor && $action){
	if($action == 'cloneProfile'){
		if(isset($_POST['cloneTarget']) && $_POST['cloneTarget']){
			if(!$labelManager->cloneLabelJson($_POST)){
				$statusStr = implode('; ', $labelManager->getErrorArr());
			}
		}
		else $statusStr = $LANG['ERROR_SELECT_TARGET'];
	}
	$applyEdits = true;
	$group = (isset($_POST['group'])?$_POST['group']:'');
	if($group == 'g' && $isEditor < 3) $applyEdits = false;
	if($group == 'c' && $isEditor < 2) $applyEdits = false;
	if($applyEdits){
		if($action == 'saveProfile'){
			if(!$labelManager->saveLabelJson($_POST)){
				$statusStr = implode('; ', $labelManager->getErrorArr());
			}
		}
		elseif($action == 'deleteProfile'){
			if(!$labelManager->deleteLabelFormat($_POST['group'],$_POST['index'])){
				$statusStr = implode('; ', $labelManager->getErrorArr());
			}
		}
	}
}
$isGeneralObservation = (($labelManager->getMetaDataTerm('colltype') == 'General Observations')?true:false);
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
		<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['SPEC_LABEL_MNG']; ?></title>
		<link href="<?php echo htmlspecialchars($CSS_BASE_PATH, HTML_SPECIAL_CHARS_FLAGS); ?>/jquery-ui.css" type="text/css" rel="stylesheet">
		<?php
		include_once($SERVER_ROOT . '/includes/head.php');
		?>
		<script src="../../js/jquery.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.js" type="text/javascript"></script>
		<script type="text/javascript">
			var activeProfileCode = "";

			function toggleEditDiv(classTag){
				$('#display-'+classTag).toggle();
				$('#edit-'+classTag).toggle();
			}

			function makeJsonEditable(classTag){
				alert("<?php echo $LANG['NOW_EDIT_JSON']; ?>");
				$('#json-'+classTag).prop('readonly', false);
				activeProfileCode = classTag;
			}

			function setJson(json){
				$('#json-'+activeProfileCode).val(json);
			}

			function verifyClone(f){
				if(f.cloneTarget.value == ""){
					alert("<?php echo $LANG['SEL_TARGET']; ?>");
					return false;
				}
				return true;
			}

			/**
			* Adds current profile JSON to visual interface
			*/
			function openJsonEditorPopup(classTag){
				activeProfileCode = classTag;
				let editorWindow = window.open('labeljsongui.php','scrollbars=1,toolbar=0,resizable=1,width=1000,height=700,left=20,top=20');
				(editorWindow.opener == null) ? editorWindow.opener = self : '';
				let formatId = "#json-"+classTag;
				let currJson = $("#json-"+classTag).val();
				editorWindow.focus();
				editorWindow.onload = function(){
					let dummy = editorWindow.document.getElementById("dummy");
					dummy.value = currJson;
					dummy.dataset.formatId = formatId;
					editorWindow.loadJson();
				}
			}
		</script>
		<style>
			fieldset{ width:800px; padding:15px; }
			fieldset legend{ font-weight:bold; }
			textarea{ width: 800px; height: 150px }
			input[type=text]{ width:500px }
			hr{ margin:15px 0px; }
			.fieldset-block{ width:700px }
			.field-block{ margin:3px 0px }
			.label{ font-weight: bold; }
			.label-inline{ font-weight: bold; }
			.field-value{  }
			.field-inline{  }
      .edit-icon{ width:13px; }
      #preview-label{ border: 1px solid gray; min-height: 100px; padding: 0.5em; }
      #preview-label.field-block{ line-height: 1.1rem; }
      #preview-label>.field-block>div{ display: inline; }
		</style>
	</head>
	<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'>Home</a> &gt;&gt;
		<?php
		if($isGeneralObservation) echo '<a href="../../profile/viewprofile.php?tabindex=1">' . $LANG['PERS_MNG_MENU'] . '</a> &gt;&gt; ';
		elseif($collid){
			echo '<a href="../misc/collprofiles.php?collid=' . htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS) . '&emode=1">' . $LANG['COL_MNG_PANEL'] . '</a> &gt;&gt; ';
		}
		?>
		<a href="labelmanager.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>&emode=1"><?php echo $LANG['LABEL_MANAGER']; ?></a> &gt;&gt;
		<b><?php echo $LANG['LABEL_PROFILE_EDIT']; ?></b>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<div style="width:700px"><span style="color:orange;font-weight:bold;"><?php echo $LANG['IN_DEV']; ?></span></div>
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="margin:15px;color:red;">
				<?php echo $statusStr; ?>
			</div>
			<?php
		}
		echo '<h2>Specimen Label Profiles</h2>';
		$labelFormatArr = $labelManager->getLabelFormatArr();
		foreach($labelFormatArr as $group => $groupArr){
			$fieldsetTitle = '';
			if($group == 'g') $fieldsetTitle = $LANG['PORTAL_PROFILES'] . ' ';
			elseif($group == 'c') $fieldsetTitle = $labelManager->getCollName() . ' ' . $LANG['LABEL_PROFILES'] . ' ';
			elseif($group == 'u') $fieldsetTitle = $LANG['USER_PROFILES'] . ' ';
			$fieldsetTitle .= '(' . count($groupArr) . ' ' . $LANG['FORMATS'] . ')';
			?>
			<fieldset>
				<legend><?php echo $fieldsetTitle; ?></legend>
				<?php
				if($isEditor == 3 || $group == 'u' || ($group == 'c' && $isEditor > 1))
					echo '<div style="float:right;" title="' . $LANG['CREATE_NEW_PROFILE'] . '"><img class="edit-icon" src="../../images/add.png" onclick="$(\'#edit-' . $group . '\').toggle()" /></div>';
				$index = '';
				$formatArr = array();
				do{
					$midText = '';
					$labelType = 2;
					$pageSize = '';
					if($formatArr){
						if($index) echo '<hr/>';
						?>
						<div id="display-<?php echo $group . '-' . $index; ?>">
							<div class="field-block">
								<span class="label">Title:</span>
								<span class="field-value"><?php echo htmlspecialchars($formatArr['title']); ?></span>
								<?php
								if($isEditor == 3 || $group == 'u' || ($group == 'c' && $isEditor > 1))
									echo '<span title="' . $LANG['EDIT_LABEL_PROFILE'] . '"> <a href="#" onclick="toggleEditDiv(\'' . $group . '-' . $index . '\');return false;"><img class="edit-icon" src="../../images/edit.png" /></a></span>';
								?>
							</div>
							<?php
							if(isset($formatArr['labelHeader']['midText'])) $midText = $formatArr['labelHeader']['midText'];
							$headerStr = $formatArr['labelHeader']['prefix'] . ' ';
							if($midText==1) $headerStr .= '[COUNTRY];';
							elseif($midText==2) $headerStr .= '[STATE]';
							elseif($midText==3) $headerStr .= '[COUNTY]';
							elseif($midText==4) $headerStr .= '[FAMILY]';
							$headerStr .= ' ' . $formatArr['labelHeader']['suffix'];
							if(trim($headerStr)){
								?>
								<div class="field-block">
									<span class="label"><?php echo $LANG['HEADER']; ?>: </span>
									<span class="field-value"><?php echo htmlspecialchars(trim($headerStr)); ?></span>
								</div>
								<?php
							}
							if($formatArr['labelFooter']['textValue']){
								?>
								<div class="field-block">
									<span class="label"><?php echo $LANG['FOOTER']; ?>: </span>
									<span class="field-value"><?php echo htmlspecialchars($formatArr['labelFooter']['textValue']); ?></span>
								</div>
								<?php
							}
							if($formatArr['labelType']){
								$labelType = $formatArr['labelType'];
								?>
								<div class="field-block">
									<span class="label"><?php echo $LANG['TYPE']; ?>: </span>
									<span class="field-value"><?php echo $labelType.(is_numeric($labelType)?' column per page':''); ?></span>
								</div>
								<?php
							}
							if($formatArr['pageSize']){
								$pageSize = $formatArr['pageSize'];
								?>
								<div class="field-block">
									<span class="label"><?php echo $LANG['PAGE_SIZE']; ?>: </span>
									<span class="field-value"><?php echo $pageSize; ?></span>
								</div>
								<?php
							}
							?>
						</div>
						<?php
					}
					?>
					<form name="labelprofileeditor-<?php echo $group . (is_numeric($index)?'-' . $index:''); ?>" action="labelprofile.php" method="post" onsubmit="return validateJsonForm(this)">
						<div id="edit-<?php echo $group . (is_numeric($index)?'-' . $index:''); ?>" style="display:none">
							<div class="field-block">
								<span class="label"><?php echo $LANG['TITLE']; ?>:</span>
								<span class="field-elem"><input name="title" type="text" value="<?php echo ($formatArr?htmlspecialchars($formatArr['title']):''); ?>" required /> </span>
								<?php
								if($formatArr) echo '<span title="' . $LANG['EDIT_LABEL_PROFILE'] . '"> <img class="edit-icon" src="../../images/edit.png" onclick="toggleEditDiv(\'' . $group . '-' . $index . '\')" /></span>';
								?>
							</div>
							<fieldset class="fieldset-block">
								<legend><?php echo $LANG['LABEL_HEADER']; ?></legend>
								<div class="field-block">
									<span class="label"><?php echo $LANG['PREFIX']; ?>:</span>
									<span class="field-elem">
										<input name="hPrefix" type="text" value="<?php echo (isset($formatArr['labelHeader']['prefix'])?htmlspecialchars($formatArr['labelHeader']['prefix']):''); ?>" />
									</span>
								</div>
								<div class="field-block">
									<div class="field-elem">
										<span class="field-inline">
											<input name="hMidText" type="radio" value="1" <?php echo ($midText==1?'checked':''); ?> />
											<span class="label-inline"><?php echo $LANG['COUNTRY']; ?></span>
										</span>
										<span class="field-inline">
											<input name="hMidText" type="radio" value="2" <?php echo ($midText==2?'checked':''); ?> />
											<span class="label-inline"><?php echo $LANG['STATE']; ?></span>
										</span>
										<span class="field-inline">
											<input name="hMidText" type="radio" value="3" <?php echo ($midText==3?'checked':''); ?> />
											<span class="label-inline"><?php echo $LANG['COUNTY']; ?></span>
										</span>
										<span class="field-inline">
											<input name="hMidText" type="radio" value="4" <?php echo ($midText==4?'checked':''); ?> />
											<span class="label-inline"><?php echo $LANG['FAMILY']; ?></span>
										</span>
										<span class="field-inline">
											<input name="hMidText" type="radio" value="0" <?php echo (!$midText?'checked':''); ?> />
											<span class="label-inline"><?php echo $LANG['BLANK']; ?></span>
										</span>
									</div>
								</div>
								<div class="field-block">
									<span class="label"><?php echo $LANG['SUFFIX']; ?>:</span>
									<span class="field-elem"><input name="hSuffix" type="text" value="<?php echo ($formatArr?htmlspecialchars($formatArr['labelHeader']['suffix']):''); ?>" /></span>
								</div>
								<div class="field-block">
									<span class="label"><?php echo $LANG['CLASS_NAMES']; ?>:</span>
									<span class="field-elem"><input name="hClassName" type="text" value="<?php echo ($formatArr?htmlspecialchars($formatArr['labelHeader']['className']):''); ?>" /></span>
								</div>
								<div class="field-block">
									<span class="label"><?php echo $LANG['STYLE']; ?>:</span>
									<span class="field-elem"><input name="hStyle" type="text" value="<?php echo ($formatArr?htmlspecialchars($formatArr['labelHeader']['style']):''); ?>" /></span>
								</div>
							</fieldset>
							<fieldset  class="fieldset-block">
								<legend><?php echo $LANG['LABEL_FOOTER']; ?></legend>
								<div class="field-block">
									<span class="label-inline"><?php echo $LANG['FOOTER_TEXT']; ?>:</span>
									<input name="fTextValue" type="text" value="<?php echo (isset($formatArr['labelFooter']['textValue'])?htmlspecialchars($formatArr['labelFooter']['textValue']):''); ?>" />
								</div>
								<div class="field-block">
									<span class="label-inline"><?php echo $LANG['CLASS_NAMES']; ?>:</span>
									<input name="fClassName" type="text" value="<?php echo (isset($formatArr['labelFooter']['className'])?$formatArr['labelFooter']['className']:''); ?>" />
								</div>
								<div class="field-block">
									<span class="label-inline"><?php echo $LANG['STYLE']; ?>:</span>
									<input name="fStyle" type="text" value="<?php echo (isset($formatArr['labelFooter']['style'])?$formatArr['labelFooter']['style']:''); ?>" />
								</div>
							</fieldset>
							<div class="field-block">
								<div class="label"><?php echo $LANG['CUSTOM_STYLES']; ?>:</div>
								<div class="field-block">
									<input name="customStyles" type="text" value="<?php echo (isset($formatArr['customStyles'])?$formatArr['customStyles']:''); ?>" />
								</div>
							</div>
							<div class="field-block">
								<div class="label"><?php echo $LANG['DEFAULT_CSS']; ?>:</div>
								<div class="field-block">
									<input name="defaultCss" type="text" value="<?php echo (isset($formatArr['defaultCss']) ? $formatArr['defaultCss'] : $CSS_BASE_PATH . '/symbiota/collections/reports/labelhelpers.css'); ?>" />
								</div>
							</div>
							<div class="field-block">
								<div class="label"><?php echo $LANG['CUSTOM_CSS']; ?>:</div>
								<div class="field-block">
									<input name="customCss" type="text" value="<?php echo (isset($formatArr['customCss'])?$formatArr['customCss']:''); ?>" />
								</div>
							</div>
							<div class="field-block">
								<div class="label"><?php echo $LANG['CUSTOM_JS']; ?>:</div>
								<div class="field-block">
									<input name="customJS" type="text" value="<?php echo (isset($formatArr['customJS'])?$formatArr['customJS']:''); ?>" />
								</div>
							</div>
							<fieldset class="fieldset-block">
								<legend><?php echo $LANG['OPTIONS']; ?></legend>
								<div class="field-block">
									<span class="label-inline"><?php echo $LANG['LABEL_TYPE']; ?>:</span>
									<select name="labelType">
										<option value="1" <?php echo ($labelType==1?'selected':''); ?>><?php echo '1 ' . $LANG['COLUMNS_PER_PAGE']; ?></option>
										<option value="2" <?php echo ($labelType==2?'selected':''); ?>><?php echo '2 ' . $LANG['COLUMNS_PER_PAGE']; ?></option>
										<option value="3" <?php echo ($labelType==3?'selected':''); ?>><?php echo '3 ' . $LANG['COLUMNS_PER_PAGE']; ?></option>
										<option value="4" <?php echo ($labelType==4?'selected':''); ?>><?php echo '4 ' . $LANG['COLUMNS_PER_PAGE']; ?></option>
										<option value="5" <?php echo ($labelType==5?'selected':''); ?>><?php echo '5 ' . $LANG['COLUMNS_PER_PAGE']; ?></option>
										<option value="6" <?php echo ($labelType==6?'selected':''); ?>><?php echo '6 ' . $LANG['COLUMNS_PER_PAGE']; ?></option>
										<option value="7" <?php echo ($labelType==7?'selected':''); ?>><?php echo '7 ' . $LANG['COLUMNS_PER_PAGE']; ?></option>
										<option value="packet" <?php echo ($labelType=='packet'?'selected':''); ?>><?php echo '1 ' . $LANG['PACKET_LABELS']; ?></option>
									</select>
								</div>
								<div class="field-block">
									<span class="label-inline"><?php echo $LANG['PAGE_SIZE']; ?>:</span>
									<select name="pageSize">
										<option value="letter">Letter</option>
										<option value="a4" <?php echo ($pageSize=='a4'?'SELECTED':''); ?>><?php echo $LANG['A4']; ?></option>
										<option value="legal" <?php echo ($pageSize=='legal'?'SELECTED':''); ?>><?php echo $LANG['LEGAL']; ?></option>
										<option value="tabloid" <?php echo ($pageSize=='tabloid'?'SELECTED':''); ?>><?php echo $LANG['LEDGER']; ?></option>
									</select>
								</div>
								<div class="field-block">
									<input name="displaySpeciesAuthor" type="checkbox" value="1" <?php echo (isset($formatArr['displaySpeciesAuthor'])&&$formatArr['displaySpeciesAuthor']?'checked':''); ?> />
									<span class="label-inline"><?php echo $LANG['DISPLAY_SP_AUTH_FOR_INFRA']; ?></span>
								</div>
								<div class="field-block">
									<input name="displayBarcode" type="checkbox" value=1" <?php echo (isset($formatArr['displayBarcode'])&&$formatArr['displayBarcode']?'checked':''); ?> />
									<span class="label-inline"><?php echo $LANG['DISPLAY_BARCODE']; ?></span>
								</div>
							</fieldset>
							<div class="field-block">
								<div class="label"><?php echo $LANG['JSON']; ?>: <span title="<?php echo $LANG['EDIT_JSON']; ?>"><a href="#" onclick="makeJsonEditable('<?php echo $group.(is_numeric($index)?'-' . $index:''); ?>');return false"><img  class="edit-icon" src="../../images/edit.png" /></a></span><span title="<?php echo $LANG['EDIT_JSON_DEFINITION']; ?>"><a href="#" onclick="openJsonEditorPopup('<?php echo $group.(is_numeric($index)?'-' . $index:''); ?>');return false"><img  class="edit-icon" src="../../images/edit.png" />(visual interface)</a></span>
								</div>
								<div class="field-block">
									<textarea id="json-<?php echo $group.(is_numeric($index)?'-' . $index:''); ?>" name="json" readonly><?php echo (isset($formatArr['labelBlocks'])?json_encode($formatArr['labelBlocks'],JSON_PRETTY_PRINT):''); ?></textarea>
								</div>
							</div>
							<div style="margin-left:20px;">
								<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
								<input type="hidden" name="group" value="<?php echo $group; ?>" />
								<input type="hidden" name="index" value="<?php echo $index; ?>" />
								<?php
								if($isEditor == 3 || $group == 'u' || ($group == 'c' && $isEditor > 1))
									echo '<span><button name="submitaction" type="submit" value="saveProfile">' . (is_numeric($index)?$LANG['SAVE_LABEL_PROFILE']:$LANG['CREATE_LABEL_PROFILE']) . '</button></span>';
								if(is_numeric($index)){
									if($isEditor == 3 || $group == 'u' || ($group == 'c' && $isEditor > 1))
										echo '<span style="margin-left:15px"><button name="submitaction" type="submit" value="deleteProfile" onclick="return confirm(\'' . $LANG['SURE_DELETE_PROFILE'] . '\')">' . $LANG['DEL_PROFILE'] . '</button></span>';
									?>
									<?php
								}
								?>
							</div>
							<?php if(!is_numeric($index)) echo '<hr/>'; ?>
						</div>
						<?php
						if(is_numeric($index)){
							?>
							<div style="margin:5px;">
								<span style="margin-left:15px"><button name="submitaction" type="submit" value="cloneProfile" onclick="return verifyClone(this.form)">Clone Profile</button></span> to
								<select name="cloneTarget">
									<option value=""><?php echo $LANG['SELECT_TARGET']; ?></option>
									<option value="">----------------</option>
									<?php
									if($isEditor == 3) echo '<option value="g">' . $LANG['PORTAL_GLOBAL_PROFILE'] . '</option>';
									if($isEditor > 1) echo '<option value="c">' . $LANG['COLL_PROFILE'] . '</option>';
									?>
									<option value="u"><?php echo $LANG['USER_PROFILE']; ?></option>
								</select>
							</div>
							<?php
						}
						?>
					</form>
					<?php
					if($groupArr){
						$index = key($groupArr);
						if(is_numeric($index)){
							$formatArr = $groupArr[$index];
							next($groupArr);
						}
					}
				} while(is_numeric($index));
				if(!$formatArr){
					echo '<div>' . $LANG['NO_PROFILE_DEFINED'] . ' ';
					if($isEditor == 3 || $group == 'u' || ($group == 'c' && $isEditor > 1)) echo $LANG['CLICK_GREEN_PLUS'];
					echo '</div>';
				}
				?>
			</fieldset>
			<?php
		}
		if(!$labelFormatArr) echo '<div>' . $LANG['NOT_AUTH_LABEL_PROF'] . '</div>';
		?>
	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
	</body>
</html>