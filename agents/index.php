<?php
include_once ('../config/symbini.php');
include_once ($SERVER_ROOT . '/classes/AgentManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$agentID = array_key_exists('agentID', $_REQUEST) ? $_REQUEST['agentID'] : '';
$submitAction = array_key_exists('submitaction', $_POST) ? $_POST['submitaction'] : '';

// Sanitation
if(!is_numeric($agentID)) $agentID = 0;
$submitAction = filter_var($submitAction, FILTER_SANITIZE_STRING);

$agentManager = new AgentManager();

$isEditor = false;
if($IS_ADMIN || array_key_exists('CollAdmin',$USER_RIGHTS)) $isEditor = true;

$statusStr = '';
if($isEditor && $submitAction) {
	if($submitAction == 'submitAgentEdits'){
		$status = $agentManager->editAgent($_POST);
		if(!$status) $statusStr = $agentManager->getErrorMessage();
	}
	elseif($submitAction == 'deleteAgent'){
		$status = $agentManager->deleteAgent($_POST['delAgentID']);
		if(!$status) $statusStr = $agentManager->getErrorMessage();
	}
		elseif($submitAction == 'addAgent'){
		$status = $agentManager->addAgent($_POST);
		if(!$status) $statusStr = $agentManager->getErrorMessage();
	}
}

$agentsArr = $agentManager->getAgentList();

?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> - Agent Manager</title>
	<?php
	$activateJQuery = true;
	include_once ($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.js" type="text/javascript"></script>
	<script type="text/javascript">
		function toggleEditor(){
			$(".editTerm").toggle();
			$(".editFormElem").toggle();
			$("#editButton-div").toggle();
			$("#edit-legend").toggle();
			$("#unitDel-div").toggle();
		}
	</script>
	<style type="text/css">
		fieldset{ margin: 10px; padding: 15px; width: 800px }
		legend{ font-weight: bold; }
		label{ text-decoration: underline; }
		#edit-legend{ display: none }
		.field-div{ margin: 3px 0px }
		.editIcon{  }
		.editTerm{ }
		.editFormElem{ display: none }
		#editButton-div{ display: none }
		#unitDel-div{ display: none }
		.button-div{ margin: 15px }
		.link-div{ margin:0px 30px }
		#status-div{ margin:15px; padding: 15px; color: red; }
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($profile_indexMenu)?$profile_indexMenu:'true');
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href="../index.php">Home</a> &gt;&gt;
		<b><a href="index.php">Agent List</a></b>
	</div>
	<div id='innertext'>
		<?php
		if($statusStr){
			echo '<div id="status-div">'.$statusStr.'</div>';
		}
		if($agentID){
			$Agent = $agentManager->getAgent($agentID);
			?>
			<div id="updateAgent-div" style="clear:both;margin-bottom:10px;">
				<fieldset id="edit-fieldset">
					<legend>Edit <span id="edit-legend"> Agent</span></legend>
					<div style="float:right">
						<span class="editIcon"><a href="#" onclick="toggleEditor()"><img class="editimg" src="../images/edit.png" /></a></span>

					</div>
					<form name="agentEditForm" action
               ="index.php" method="post">
						<div class="field-div">
							<label>First Name</label>:
							<span class="editTerm"><?php echo $Agent['firstName']; ?></span>
							<span class="editFormElem"><input type="text" name="firstName" value="<?php echo $Agent['firstName'] ?>" style="width:200px;" required /></span>
						</div>
						<div class="field-div">
							<label>Family Name</label>:
							<span class="editTerm"><?php echo $Agent['familyName']; ?></span>
							<span class="editFormElem"><input type="text" name="familyName" value="<?php echo $Agent['familyName'] ?>" style="width:50px;" required /></span>
						</div>
						<div class="field-div">
							<label>Middle Name</label>:
							<span class="editTerm"><?php echo $Agent['middleName']; ?></span>
							<span class="editFormElem"><input type="text" name="middleName" value="<?php echo $Agent['middleName'] ?>" style="width:50px;" /></span>
						</div>
						<div class="field-div">
							<label>Active Start Year</label>:
							<span class="editTerm"><?php echo $Agent['startYearActive']; ?></span>
							<span class="editFormElem"><input type="text" name="startYearActive" value="<?php echo $Agent['startYearActive'] ?>"style="width:50px;" /></span>
						</div>
						<div class="field-div">
							<label>Active End Year</label>:
							<span class="editTerm"><?php echo $Agent['endYearActive']; ?></span>
							<span class="editFormElem"><input type="text" name="endYearActive" value="<?php echo $Agent['endYearActive'] ?>" style="width:50px;" /></span>
						</div>
						<div class="field-div">
							<label>Notes</label>:
							<span class="editTerm"><?php echo $Agent['notes']; ?></span>
							<span class="editFormElem"><input type="text" name="notes" value="<?php echo $Agent['notes'] ?>" maxlength="250" style="width:650px;" /></span>
						</div>
						<div id="editButton-div" class="button-div">
							<input name="agentID" type="hidden" value="<?php echo $agentID; ?>" />
							<button type="submit" name="submitaction" value="submitAgentEdits">Save Edits</button>
						</div>
					</form>
				</fieldset>
			</div>
			<div id="agentDel-div">
				<form name="agentDeleteForm" action="index.php" method="post">
					<fieldset>
						<legend>Delete Agemt</legend>
						<div class="button-div">
							<input name="delAgentID" type="hidden"  value="<?php echo $agentID; ?>" />
							<button type="submit" name="submitaction" value="deleteAgent" onclick="return confirm('Are you sure you want to delete this record?')">Delete Agent</button>
						</div>
					</fieldset>
				</form>
			</div>
			<?php
		}
		else{
			?>
			<div style="float:right">
				<span class="editIcon"><a href="#" onclick="$('#addAgent-div').toggle();"><img class="editimg" src="../images/add.png" /></a></span>
			</div>
			<div id="addAgent-div" style="clear:both;margin-bottom:10px;display:none">
				<!--This should also be visible when !$agentID -->
				<fieldset id="new-fieldset">
					<legend>Add Agent</legend>
					<form name="agentAddForm" action="index.php" method="post">
						<div class="field-div">
							<label>First Name</label>:
							<span><input type="text" name="firstName" style="width:200px;" required /></span>
						</div>
						<div class="field-div">
							<label>Family Name</label>:
							<span><input type="text" name="familyName" style="width:200px;" required /></span>
						</div>
						<div class="field-div">
							<label>Middle Name</label>:
							<span><input type="text" name="middleName" style="width:50px;" /></span>
						</div>
						<div class="field-div">
							<label>Active Start Year</label>:
							<span><input type="text" name="startYearActive" style="width:50px;" /></span>
						</div>
						<div class="field-div">
							<label>Active End Year</label>:
							<span><input type="text" name="endYearActive" style="width:50px;" /></span>
						</div>
						<div class="field-div">
							<label>Notes</label>:
							<span><input type="text" name="notes" maxlength="250" style="width:200px;" /></span>
						</div>
						<div id="addButton-div" class="button-div">
							<button type="submit" name="submitaction" value="addAgent">Add Agent</button>
						</div>
					</form>
				</fieldset>
			</div>
			<?php
			if($agentsArr){
				$titleStr = '';
				echo '<div style=";font-size:1.3em;margin: 10px 0px">'.$titleStr.'</div>';
				echo '<ul>';
				foreach($agentsArr as $agentID => $agentArr){
					$termDisplay = '<a href="index.php?agentID='.$agentID.'">'.$agentArr['familyName'].','.$agentArr['firstName'].'</a>';
					echo '<li>'.$termDisplay.'</li>';
				}
				echo '</ul>';
			}
			else echo '<div>No records returned</div>';
			if($agentID) echo '<div class="link-div"><a href="index.php">Show Agent list</a></div>';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>