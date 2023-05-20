<?php
include_once($SERVER_ROOT.'/classes/RpcBase.php');

class RpcPortalIndex extends RpcBase{

	private $portalID = 1;

	function __construct(){
		parent::__construct();
	}

	function __destruct(){
		parent::__destruct();
	}

	public function getTaxaSuggest($term, $rankLimit, $rankLow, $rankHigh){

	}

	//Setters and getters
	public function setPortalID($id){
		if(is_numeric($id)) $this->portalID = $id;
	}
}
?>