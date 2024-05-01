<?php
trait TaxonomyTrait {
    function splitSciname(){
		$scinameBase = $this->sciName;
		$returnObj = [];
		if(!empty($this->tradeName)){
			$scinameBase = str_replace($this->tradeName, '', $scinameBase);
		}

		if(!empty($this->cultivarEpithet)){
			// $scinameBase = str_replace($this->cultivarEpithet, '', trim($scinameBase)); // @TODO could possibly replace off-target if cultivarEpithet matches some parent taxon exactly.
			$scinameBase = str_replace("'". $this->cultivarEpithet . "'", '', trim($scinameBase)); // @TODO could possibly replace off-target if cultivarEpithet matches some parent taxon exactly.
		}
		$returnObj['base'] = trim($scinameBase ?? '');
		$returnObj['cultivarEpithet'] = $this->cultivarEpithet; // assumes quotes not stored in db
		$returnObj['tradeName'] = $this->tradeName;
		$returnObj['author'] = $this->author;

		return $returnObj;
	}
}
?>