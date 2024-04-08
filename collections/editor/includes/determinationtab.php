<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceEditorDeterminations.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/includes/determinationtab.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/editor/includes/determinationtab.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/editor/includes/determinationtab.en.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$occId = array_key_exists('occid', $_REQUEST) ? filter_var($_REQUEST['occid'], FILTER_SANITIZE_NUMBER_INT) : '';
$occIndex = array_key_exists('occindex', $_REQUEST) ? filter_var($_REQUEST['occindex'], FILTER_SANITIZE_NUMBER_INT) : false;
$crowdSourceMode = array_key_exists('csmode', $_REQUEST) ? filter_var($_REQUEST['csmode'], FILTER_SANITIZE_NUMBER_INT) : 0;
$editMode = array_key_exists('em', $_REQUEST) ? filter_var($_REQUEST['em'], FILTER_SANITIZE_NUMBER_INT) : 0;

$occManager = new OccurrenceEditorDeterminations();

$occManager->setOccId($occId);
$detArr = $occManager->getDetMap();

$specImgArr = $occManager->getImageMap();  // find out if there are images in order to show/hide the button to display/hide images.

?>
<div id="determdiv" style="width:795px;">
	<div style="clear:both">
		<fieldset style="margin:15px;padding:15px;">
			<legend><b><?= $LANG['DET_HISTORY'] ?></b></legend>
			<div style="float:right;">
				<a href="#" onclick="toggle('newdetdiv');return false;" title="<?= $LANG['ADD_NEW_DET'] ?>" ><img style="border:0px;width:1.5em;" src="../../images/add.png" /></a>
			</div>
			<?php
			if(!$detArr){
				?>
				<div style="font-weight:bold;margin:10px;font-size:120%;">
					<?= $LANG['NO_HIST_ANNOTATIONS'] ?>
				</div>
				<?php
			}
			?>
			<div id="newdetdiv" style="display:<?= ($detArr?'none':''); ?>;">
				<form name="detaddform" action="occurrenceeditor.php" method="post" onsubmit="return verifyDetForm(this)">
					<fieldset style="margin:15px;padding:15px;">
						<legend><b><?= $LANG['ADD_NEW_DET'] ?></b></legend>
						<div style="float:right;margin:-7px -4px 0px 0px;font-weight:bold;">
							<span id="imgProcOnSpanDet" style="display:block;">
								<?php
								if($specImgArr){
									?>
									<a href="#" onclick="toggleImageTdOn();return false;">&gt;&gt;</a>
									<?php
								}
								?>
							</span>
							<span id="imgProcOffSpanDet" style="display:none;">
								<?php
								if($specImgArr){
									?>
									<a href="#" onclick="toggleImageTdOff();return false;">&lt;&lt;</a>
									<?php
								}
								?>
							</span>
						</div>
						<?php
						if($editMode == 3){
							?>
							<div style="color:red;margin:10px;">
								<?= $LANG['NO_RIGHTS'] ?>
							</div>
							<?php
						}
						?>
				<?php
identifiedBy, dateIdentified, dateIdentifiedInterpreted, family, sciname, verbatimIdentification, scientificNameAuthorship, tidInterpreted, identificationUncertain,
identificationQualifier, isCurrent, printQueue, appliedStatus, verificationStatus, publishOverride, securityStatus, securityStatusReason,
identificationReferences, identificationRemarks, taxonRemarks, identificationVerificationStatus, sourceIdentifier, sortSequence, recordID,
createdUid, modifiedUid, dateLastModified, initialTimestamp
				?>
						<div style='margin:3px;'>
							<label><?= $LANG['SCI_NAME'] ?>:</label>
							<input type="text" id="dafsciname" name="sciname" style="background-color:lightyellow;width:350px;" onfocus="initDetAutocomplete(this.form)" />
							<input type="hidden" id="daftidtoadd" name="tidtoadd" value="" />
							<input type="hidden" name="family" value="" />
						</div>
						<div style='margin:3px;'>
							<label><?= $LANG['AUTHOR']; ?>:</label>
							<input type="text" name="scientificnameauthorship" style="width:200px;" />
						</div>
						<div style='margin:3px;'>
							<label><?= $LANG['CONFIDENCE_IN_DET']; ?>:</label>
							<select name="confidenceranking">
								<option value="8"><?= $LANG['HIGH']; ?></option>
								<option value="5" selected><?= $LANG['MEDIUM']; ?></option>
								<option value="2"><?= $LANG['LOW']; ?></option>
							</select>
						</div>
						<div style='margin:3px;'>
							<label><?= $LANG['DETERMINER']; ?>:</label>
							<input type="text" name="identifiedby" style="background-color:lightyellow;width:200px;" />
						</div>
						<div style='margin:3px;'>
							<label><?= $LANG['DATE']; ?>:</label>
							<input type="text" name="dateidentified" style="background-color:lightyellow;" onchange="detDateChanged(this.form);" />
						</div>
						<div style='margin:3px;'>
							<label><?= $LANG['REFERENCE']; ?>:</label>
							<input type="text" name="identificationreferences" style="width:350px;" />
						</div>
						<div style='margin:3px;'>
							<label><?= $LANG['NOTES']; ?>:</label>
							<input type="text" name="identificationremarks" style="width:350px;" />
						</div>
						<div style='margin:3px;'>
							<label><?= $LANG['ID_QUALIFIER']; ?>:</label>
							<input type="text" name="identificationqualifier" title="e.g. cf, aff, etc" />
						</div>
						<div style='margin:3px;'>
							<input type="checkbox" name="iscurrent" value="1" /> <?= $LANG['MAKE_THIS_CURRENT']; ?>
						</div>
						<div style='margin:3px;'>
							<input type="checkbox" name="printqueue" value="1" /> <?= $LANG['ADD_TO_PRINT']; ?>
						</div>
						<div style='margin:15px;'>
							<input type="hidden" name="occid" value="<?= $occId; ?>" />
							<input type="hidden" name="occindex" value="<?= $occIndex; ?>" />
							<input type="hidden" name="csmode" value="<?= $crowdSourceMode; ?>" />
							<div style="float:left;">
								<button type="submit" name="submitaction" value="submitDetermination" ><?= $LANG['SUBMIT_DET']; ?></button>
							</div>
						</div>
					</fieldset>
				</form>
			</div>
			<?php
			foreach($detArr as $detId => $detRec){
				$canEdit = 0;
				if($editMode < 3 || !$detRec['appliedstatus']) $canEdit = 1;
				?>
				<div id="detdiv-<?= $detId;?>">
					<div>
						<?php
						if($detRec['identificationqualifier']) echo $detRec['identificationqualifier'].' ';
						echo '<b><i>'.$detRec['sciname'].'</i></b> '.$detRec['scientificnameauthorship'];
						if($detRec['iscurrent']){
							if($detRec['appliedstatus']){
								echo '<span style="margin-left:10px;color:red;">'.$LANG['CURRENT_DET'].'</span>';
							}
						}
						if($canEdit){
							?>
							<a href="#" onclick="toggle('editdetdiv-<?= $detId;?>');return false;" title="<?= $LANG['EDIT_DET']; ?>"><img style="border:0px;width:1.2em;" src="../../images/edit.png" /></a>
							<?php
						}
						if(!$detRec['appliedstatus']){
							?>
							<span style="color:red;margin-left:15px;">
								<?= $LANG['APPLIED_STATUS_PENDING']; ?>
							</span>
							<?php
						}
						?>
					</div>
					<div style='margin:3px 0px 0px 15px;'>
						<b><?= $LANG['DETERMINER']; ?>:</b> <?= $detRec['identifiedby']; ?>
						<span style="margin-left:40px;">
							<b><?= $LANG['DATE']; ?>:</b> <?= $detRec['dateidentified']; ?>
						</span>
					</div>
					<?php
					if($detRec['identificationreferences']){
						?>
						<div style='margin:3px 0px 0px 15px;'>
							<b><?= $LANG['REFERENCE']; ?>:</b> <?= $detRec['identificationreferences']; ?>
						</div>
						<?php
					}
					if($detRec['identificationremarks']){
						?>
						<div style='margin:3px 0px 0px 15px;'>
							<b><?= $LANG['NOTES']; ?>:</b> <?= $detRec['identificationremarks']; ?>
						</div>
						<?php
					}
					if($detRec['printqueue']){
						?>
						<div style='margin:3px 0px 0px 15px;color:orange'>
							<?= $LANG['ADDED_TO_QUEUE']; ?>
						</div>
						<?php
					}
					?>
				</div>
				<?php
				if($canEdit){
					?>
					<div id="editdetdiv-<?= $detId;?>" style="display:none;margin:15px 5px;">
						<fieldset>
							<legend><b><?= $LANG['EDIT_DET']; ?></b></legend>
							<form name="deteditform" action="occurrenceeditor.php" method="post" onsubmit="return verifyDetForm(this);">
								<div style='margin:3px;'>
									<b><?= $LANG['ID_QUALIFIER']; ?>:</b>
									<input type="text" name="identificationqualifier" value="<?= $detRec['identificationqualifier']; ?>" title="e.g. cf, aff, etc" />
								</div>
								<div style='margin:3px;'>
									<b><?= $LANG['SCI_NAME']; ?>:</b>
									<input type="text" id="defsciname-<?= $detId;?>" name="sciname" value="<?= $detRec['sciname']; ?>" style="background-color:lightyellow;width:350px;" onfocus="initDetAutocomplete(this.form)" />
									<input type="hidden" id="deftidtoadd" name="tidtoadd" value="" />
									<input type="hidden" name="family" value="" />
								</div>
								<div style='margin:3px;'>
									<b><?= $LANG['AUTHOR']; ?>:</b>
									<input type="text" name="scientificnameauthorship" value="<?= $detRec['scientificnameauthorship']; ?>" style="width:200px;" />
								</div>
								<div style='margin:3px;'>
									<b><?= $LANG['DETERMINER']; ?>:</b>
									<input type="text" name="identifiedby" value="<?= $detRec['identifiedby']; ?>" style="background-color:lightyellow;width:200px;" />
								</div>
								<div style='margin:3px;'>
									<b><?= $LANG['DATE']; ?>:</b>
									<input type="text" name="dateidentified" value="<?= $detRec['dateidentified']; ?>" style="background-color:lightyellow;" />
								</div>
								<div style='margin:3px;'>
									<b><?= $LANG['REFERENCE']; ?>:</b>
									<input type="text" name="identificationreferences" value="<?= $detRec['identificationreferences']; ?>" style="width:350px;" />
								</div>
								<div style='margin:3px;'>
									<b><?= $LANG['NOTES']; ?>:</b>
									<input type="text" name="identificationremarks" value="<?= $detRec['identificationremarks']; ?>" style="width:350px;" />
								</div>
								<div style='margin:3px;'>
									<b><?= $LANG['SORT_SEQUENCE']; ?>:</b>
									<input type="text" name="sortsequence" value="<?= $detRec['sortsequence']; ?>" style="width:40px;" />
								</div>
								<div style='margin:3px;'>
									<input type="checkbox" name="printqueue" value="1" <?php if($detRec['printqueue']) echo 'checked'; ?> /> <?= $LANG['ADDED_TO_QUEUE']; ?>
								</div>
								<div style='margin:15px;'>
									<input type="hidden" name="occid" value="<?= $occId; ?>" />
									<input type="hidden" name="detid" value="<?= $detId; ?>" />
									<input type="hidden" name="occindex" value="<?= $occIndex; ?>" />
									<input type="hidden" name="csmode" value="<?= $crowdSourceMode; ?>" />
									<button type="submit" name="submitaction" value="submitDeterminationEdit"><?= $LANG['SUBMIT_DET_EDITS']; ?></button>
								</div>
							</form>
							<?php
							if($editMode < 3 && !$detRec['iscurrent']){
								?>
								<div style="padding:15px;background-color:lightgreen;width:280px;margin:15px;">
									<form name="detremapform" action="occurrenceeditor.php" method="post">
										<input type="hidden" name="occid" value="<?= $occId; ?>" />
										<input type="hidden" name="detid" value="<?= $detId; ?>" />
										<input type="hidden" name="occindex" value="<?= $occIndex; ?>" />
										<input type="hidden" name="csmode" value="<?= $crowdSourceMode; ?>" />
										<?php
										if($detRec['appliedstatus']){
											?>
											<button type="submit" name="submitaction" value="makeDeterminationCurrent" ><?= $LANG['MAKE_DET_CURRENT'] ?></button>
											<?php
										}
										else{
											?>
											<button type="submit" name="submitaction" value="applyDetermination"><?= $LANG['APPLY_DETERMINATION'] ?></button><br>
											<input type="checkbox" name="iscurrent" value="1" <?= ($detRec['iscurrent'] ? 'checked' : '') ?> /> <?= $LANG['MAKE_CURRENT'] ?>
											<?php
										}
										?>
									</form>
								</div>
								<?php
							}
							?>
							<div style="padding:15px;background-color:lightblue;width:155px;margin:15px;">
								<form name="detdelform" action="occurrenceeditor.php" method="post" onsubmit="return window.confirm('<?= $LANG['SURE_DELETE']; ?>');">
									<input type="hidden" name="occid" value="<?= $occId; ?>" />
									<input type="hidden" name="detid" value="<?= $detId; ?>" />
									<input type="hidden" name="occindex" value="<?= $occIndex; ?>" />
									<input type="hidden" name=" <?= $crowdSourceMode; ?>" />
									<button type="submit" name="submitaction" value="Delete Determination" ><?= $LANG['DELETE_DET']; ?></button>
								</form>
							</div>
						</fieldset>
					</div>
					<?php
				}
				?>
				<hr style='margin:10px 0px 10px 0px;' />
				<?php
			}
			?>
		</fieldset>
	</div>
</div>