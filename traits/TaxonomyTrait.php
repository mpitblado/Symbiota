<?php
trait TaxonomyTrait {
    function splitSciname(){
		$scinameBase = $this->sciName;
		$returnObj = [];
		if(!empty($this->tradeName)){
			$scinameBase = str_replace($this->tradeName, '', $scinameBase);
		}

		if(!empty($this->cultivarEpithet)){
			$scinameBase = str_replace("'". $this->cultivarEpithet . "'", '', trim($scinameBase)); // @TODO could possibly replace off-target if cultivarEpithet matches some parent taxon exactly. We think extremely unlikely edge case, so ignoring for now.
		}
		$returnObj['base'] = trim($scinameBase ?? '');
		$returnObj['cultivarEpithet'] = $this->cultivarEpithet; // assumes quotes not stored in db
		$returnObj['tradeName'] = $this->tradeName;
		$returnObj['author'] = $this->author;

		return $returnObj;
	}

	function splitScinameFromOccArr($occArr){
		$returnObj = [];
		$specificepithet = array_key_exists('specificepithet', $occArr) ? $occArr['specificepithet'] : '';
		$scientificnameauthorship = array_key_exists('scientificnameauthorship', $occArr) ? $occArr['scientificnameauthorship'] : '';
		$sciname = array_key_exists('sciname', $occArr) ? $occArr['sciname'] : '';
		$tradeName = array_key_exists('tradeName', $occArr) ? $occArr['tradeName'] : '';
		$cultivarEpithet = array_key_exists('cultivarEpithet', $occArr) ? $occArr['cultivarEpithet'] : '';
		$scinameBase = $sciname;
		if(!empty($tradeName)){
			$scinameBase = str_replace($tradeName, '', $scinameBase);
			$scinameBase = str_replace(strtoupper($tradeName), '', $scinameBase);
		}

		if(!empty($cultivarEpithet)){
			$scinameBase = str_replace("'". $cultivarEpithet . "'", '', trim($scinameBase)); // @TODO could possibly replace off-target if cultivarEpithet matches some parent taxon exactly. We think extremely unlikely edge case, so ignoring for now.
			$scinameBase = str_replace($cultivarEpithet, '', trim($scinameBase));
		}
		$splitByAuthorship = explode(ucfirst(strtolower($scientificnameauthorship)), trim($scinameBase));
		// var_dump($splitByAuthorship);
		if(count($splitByAuthorship)==2){
			$scinameBase = trim($splitByAuthorship[0]);
			// var_dump($secondSplit);
			$theStuffBeyondAuthor = trim($splitByAuthorship[1]);
			$secondSplit = preg_split("/(\w+\.)/", $theStuffBeyondAuthor,-1,PREG_SPLIT_DELIM_CAPTURE);
			$returnObj['nonItal'] = $secondSplit[count($secondSplit)-1];
			var_dump($secondSplit);
		}else{
			// $theStuffBeyondAuthor = trim($splitByAuthorship[1]);
			$secondSplit = preg_split("/(\w+\.)/", trim($scinameBase), -1, PREG_SPLIT_DELIM_CAPTURE);
			var_dump($secondSplit);
			$returnObj['nonItal'] = $secondSplit[count($secondSplit)-1];
		}


		// if(!empty($scientificnameauthorship)){
		// 	$scinameBase = str_replace($scientificnameauthorship, '', trim($scinameBase)); // @TODO will not work with complex multi-word last names yet
		// 	$scinameBase = str_replace(strtoupper($scientificnameauthorship), '', trim($scinameBase));
		// 	$scinameBase = str_replace(ucfirst(strtolower($scientificnameauthorship)), '', trim($scinameBase));
		// }


		$returnObj['base'] = trim($scinameBase ?? '');
		$returnObj['cultivarEpithet'] = $cultivarEpithet; // @TODO other options if missing; assumes quotes not stored in db
		$returnObj['tradeName'] = $tradeName;
		$returnObj['author'] = $scientificnameauthorship;

		return $returnObj;
	}

	function splitScinameByProvided($sciName, $cultivarEpithet = '', $tradeName = '', $author = ''){
		
		$returnObj = [];
		if(empty($sciName)) return $returnObj;
		
		$scinameBase = $sciName;
		if(!empty($tradeName)){
			$scinameBase = str_replace($tradeName, '', $scinameBase);
			$returnObj['tradeName'] = $tradeName;
		}

		if(!empty($cultivarEpithet)){
			$scinameBase = str_replace("'". $cultivarEpithet . "'", '', trim($scinameBase)); // @TODO could possibly replace off-target if cultivarEpithet matches some parent taxon exactly. We think extremely unlikely edge case, so ignoring for now.
			$returnObj['cultivarEpithet'] = $cultivarEpithet; // assumes quotes not stored in db
		}

		if(!empty($author)){
			$returnObj['author'] = $author;
		}

		$returnObj['base'] = trim($scinameBase ?? '');
		
		return $returnObj;

	}
}
?>