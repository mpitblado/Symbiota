<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class OccurrenceEditorBuilder extends Manager{

	private $collid = false;
	private $fieldMap = array();
	private $LANG = array();
	private $displayImageControl = false;
	private $displayCrowdsourceControl = false;
	private $blockCnt = 0;

	public function __construct(){
		parent::__construct();
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function echoFieldMap(){
		if(!$this->fieldMap) $this->setDefaultFieldMap();
		$this->echoFieldBlock($this->fieldMap);
	}

	private function echoFieldBlock($fieldBlock){
		foreach($fieldBlock as $elemNum => $elemArr){
			$elemType = 'div';
			if(isset($elemArr['type'])) $elemType = $elemArr['type'];
			echo '<'.$elemType.'>';
			if(isset($elemArr['legend'])) echo '<legend>'.$elemArr['legend'].'</legend>';
			if(!$this->blockCnt) $this->echoControlElements();
			if(isset($elemArr['fields'])){
				foreach($elemArr['fields'] as $fieldName){
					echo '<div id="'.$fieldName.'Div">';
					echo '<label>'.$this->LANG[$fieldName].'</label>';
					echo '<a href="#" onclick="return dwcDoc(\''.$fieldName.'\')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>';
					$fieldName = strtolower($fieldName);
					$fieldValue = '';
					if(array_key_exists($fieldName, $occArr)) $fieldValue = $occArr[$fieldName];
					echo '<input type="text" id="'.$fieldName.'" name="'.$fieldName.'" value="'.$fieldValue.'" onchange="fieldChanged(\''.$fieldValue.'\');" autocomplete="off" >';
					echo '</div>';
				}
			}
			if(isset($elemArr['block'])) $this->echoFieldBlock($elemArr['block']);
			echo '</'.$elemType.'>';
			$this->blockCnt++;
		}
	}

	private function echoControlElements(){
		global $LANG;
		if($this->displayImageControl){
			echo '<div style="float:right;margin:-7px -4px 0px 0px;font-weight:bold;">';
			echo '<span id="imgProcOnSpan" style="display:block;"><a href="#" onclick="toggleImageTdOn();return false;">&gt;&gt;</a></span>';
			echo '<span id="imgProcOffSpan" style="display:none;"><a href="#" onclick="toggleImageTdOff();return false;">&lt;&lt;</a></span>';
			echo '</div>';
		}
		if($this->displayCrowdsourceControl){
			echo '<div style="float:right;margin:-7px 10px 0px 0px;font-weight:bold;">';
			echo '<span id="longtagspan"><a href="#" onclick="toggleCsMode(0);return false;">'.(isset($this->LANG['LONG_FORM'])?$this->LANG['LONG_FORM']:'Long Form').'</a></span>';
			echo '<span id="shorttagspan" style="display:none;"><a href="#" onclick="toggleCsMode(1);return false;">'.(isset($this->LANG['SHORT_FORM'])?$this->LANG['SHORT_FORM']:'Short Form').'</a></span>';
			echo '</div>';
		}
	}

	private function setDefaultFieldMap(){
		global $LANG;
		$this->fieldMap['block'][0]['type'] = 'fieldset';
		$this->fieldMap['block'][0]['legend'] = (isset($LANG['COLLECTOR_INFO'])?$LANG['COLLECTOR_INFO']:'Collector Info');
		$this->fieldMap['block'][0]['block'][0]['fields'][] = 'catalogNumber';
		$this->fieldMap['block'][0]['block'][0]['fields'][] = 'otherCatalogNumbers';
		$this->fieldMap['block'][0]['block'][1]['fields'][] = 'recordedBy';
		$this->fieldMap['block'][0]['block'][1]['fields'][] = 'recordNumber';
		$this->fieldMap['block'][0]['block'][1]['fields'][] = 'eventDate';
		$this->fieldMap['block'][0]['block'][1]['fields'][] = 'eventDate2';
		$this->fieldMap['block'][0]['block'][1]['fields'][] = 'autoDupeButton';
		$this->fieldMap['block'][0]['block'][2]['fields'][] = 'associatedCollectors';
		$this->fieldMap['block'][0]['block'][2]['fields'][] = 'verbatimEventDate';
		$this->fieldMap['block'][0]['block'][2]['fields'][] = 'onLoan';
		$this->fieldMap['block'][0]['block'][2]['fields'][] = 'dateExtra';
		$this->fieldMap['block'][0]['block'][2]['fields'][] = 'exsiccati';

	}

	//Setters and getters
	public function setCollID($collid){
		if(is_numeric($collid)) $this->collid = $collid;
	}

	public function setFieldMap($map){
		$this->fieldMap = $map;
	}

	public function setLangArr($langTag){
		global $SERVER_ROOT;
		if($langTag != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/occurrenceeditor.'.$langTag.'.php'))
			include_once($SERVER_ROOT.'/content/lang/collections/editor/occurrenceeditor.'.$langTag.'.php');
		else include_once($SERVER_ROOT.'/content/lang/collections/editor/occurrenceeditor.en.php');
	}

	public function setDisplayImageControls($bool){
		if($bool) $this->displayImageControl = true;
	}

	public function setDisplayCrowdsourceControl($bool){
		if($bool) $this->displayCrowdsourceControl = true;
	}
}
?>