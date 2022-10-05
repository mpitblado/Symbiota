<?php
//*******************************************************************
//  This is called from WhereInTheWorld.html by AJAX.  WITW sends the visible rectangle.  This program chooses a random location within that visible rectangle and then returns specimens found in a localized spot within that rectangle.
if(isset($_GET['Debug']))
	$Debug = true;
//$Debug=true;
if($Debug)
	{
	echo "Error: ";
	echo "MinLat=".substr($_GET['MinLat'],0,5)."&MaxLat=".substr($_GET['MaxLat'],0,5)."&MinLon=".substr($_GET['MinLon'],0,5)."&MaxLon=".substr($_GET['MaxLon'],0,5)."<br>";
	if($_GET['Debug'] == 2)
		die();
	}

//*************************************************************************
//Update hit list
$Endtime = getdate(strtotime('tomorrow-7 hours'));
$Today = getdate(time()-3600*7);
$Date = $Today[month]."-".$Today[mday]."-".$Today[year];
$Sessions = json_decode(file_get_contents("Sessions.txt"),true);

if(!isset($_COOKIE['Where']) || !isset($Sessions[$Date]))
	{
	if(array_key_exists($Date,$Sessions))
		$Sessions[$Date]++;
	else
		$Sessions[$Date] = 1;
	setcookie("Where","1", strtotime('tomorrow'));
	}
$Hits = json_decode(file_get_contents("Hits.txt"),true);
if(array_key_exists($Date,$Hits))
	$Hits[$Date]++;
else
	$Hits[$Date] = 1;
$OutString = json_encode($Hits);
$Result = file_put_contents("Hits.txt",$OutString);
$DB = array();
//*************************************************************************



//*************************************************************************
//********************* Set up databases ****************************
include_once('../Correlation/config/dbconnection.php');
try
	{
	$DB[0] = MySQLiConnectionFactory::getCon('seinet');
	$DB[1] = MySQLiConnectionFactory::getCon('neotrop');
	} catch (Exception $e)
		{
		echo 'Error: ',  $e->getMessage(), "\n";
		}

//*************************************************************************
//SearchArea is a class containing the borders of a region, and sometimes the number of specimens found in that region.
//*************************************************************************
class SearchArea
	{
	var $MinLat;
	var $MaxLat;
	var $MinLon;
	var $MaxLon;

	function SpanLat()
		{
		return $this->MaxLat - $this->MinLat;
		}
	function SpanLon()
		{
		if($this->MinLon >0 && $this->MaxLon < 0)//Straddling the 180 meridian
			return 360 - $this->MinLon +$this->MaxLon;
		else
			return $this->MaxLon - $this->MinLon;
		}
	function __construct($MinLat=0,$MaxLat=0,$MinLon=0,$MaxLon=0)
		{ //If the user has "spun the globe" (i.e. moved it all the way around) degrees can be greater than 180 or even 360.
		//This compensates for that.

		while($MinLon < -180)
			$MinLon = 360+$MinLon;
		while($MaxLon < -180)
			$MaxLon = 360+$MaxLon;

		while($MinLon > 180)
			$MinLon = $MinLon-360;
		while($MaxLon >180)
			$MaxLon = $MaxLon-360;
		if($MaxLon < $MinLon &&($MaxLon > 0 || $MinLon < 0))
			die("Error: Map too large.");
		if($MinLon > $MaxLon)
			$MinLon = -180;
		$this->MinLat = $MinLat;
		$this->MaxLat = $MaxLat;
		$this->MinLon = $MinLon;
		$this->MaxLon = $MaxLon;
		}
	}

//*************************************************************************

//*****************  Initialize Initial Search Area ***********************
$FullArea = new SearchArea($_GET['MinLat'], $_GET['MaxLat'], $_GET['MinLon'], $_GET['MaxLon']);
if($Debug)
	printr($FullArea,"FullArea as input");

// Reduce the search area slightly from the visible area so that the target won't fall right on the edge
//It still might if the random plant selected only has neighbors towards the edge, but unlikely.
$FullArea->MinLat += 0.05*$FullArea->SpanLat();//Raise it a little more because of the text on the bottom of the map.
$FullArea->MaxLat -= 0.03*$FullArea->SpanLat();
$FullArea->MinLon += 0.03*$FullArea->SpanLon();
$FullArea->MaxLon -= 0.03*$FullArea->SpanLon();

if($Debug)
	printr($FullArea,"FullArea after trimming");


//*************************************************************************
//   State limits
//*************************************************************************

//  If any states were selected, limit the search to a rectangle bounded by those states borders.
$MinLatBound = 1000;
$MinLonBound = 1000;
$MaxLatBound = -1000;
$MaxLatBound = -1000;

if(isset($_GET['States']) && $_GET['States'] != "")
	{
	$StateString = $_GET['States'];
	if($StateString == "Full Region")
		$QueryAdd = "";
	else
		{
		$QueryAdd = "AND stateProvince in ('".str_replace(", ","','",$StateString)."') ";
		$States = explode(", ",$StateString);
		$Bounds = file("StateBoundingBoxes.txt");

		//Checks to see that the state selected is actually in view based on rectangular bounds
		foreach($States as $OneState)
			{
			foreach($Bounds as $OneBound)
				{
				$BoundState = explode(",",$OneBound);
				if($BoundState[0] != $OneState)
					continue;

				if($BoundState[1] < $MinLonBound)
					$MinLonBound = $BoundState[1];
				if($BoundState[2]<$MinLatBound)
					$MinLatBound = $BoundState[2];
				if($BoundState[3]>$MaxLonBound)
					$MaxLonBound = $BoundState[3];
				if($BoundState[4] > $MaxLatBound)
					$MaxLatBound = $BoundState[4];
				}
			}
		if($FullArea->MinLon < $MinLonBound)
			$FullArea->MinLon = $MinLonBound;
		if($FullArea->MinLat < $MinLatBound)
			$FullArea->MinLat = $MinLatBound;
		if($FullArea->MaxLon > $MaxLonBound)
			$FullArea->MaxLon = $MaxLonBound;
		if($FullArea->MaxLat > $MaxLatBound)
			$FullArea->MaxLat = $MaxLatBound;
		}
	}
else
	$QueryAdd = "";

//********************

if($StateString != "" && ($FullArea->MinLon > $FullArea->MaxLon || $FullArea->MinLat > $FullArea->MaxLat))
	die("Error: Invalid region.  State ($StateString) not visible?");

//************************************************************************************************************
//**************************** Start of main routine *********************************************************
//************************************************************************************************************

//Load the coordinates that are known to be artifically georeferenced to the middle of a town, state, country, etc.
$TabooCoor = file_get_contents("TabooCoor.txt");
$QueryAdd .= "and CONCAT(decimalLatitude,'#',decimalLongitude) NOT IN ($TabooCoor)";

//Load the coordinates of the ocean exclusion rectangles.
$Oceans = file("Oceans.txt",FILE_IGNORE_NEW_LINES);
if(!CheckOcean($FullArea))
	die("Error: No significant specimens in the selected area.");

//Check to make sure there are enough specimens in the entire region
$query = "SELECT 1 FROM omoccurrences WHERE sciname NOT LIKE '' AND sciname NOT LIKE 'indet.%' AND decimalLatitude != '' AND decimalLatitude between ".$FullArea->MinLat." AND ".$FullArea->MaxLat." AND decimalLongitude between ".$FullArea->MinLon." AND ".$FullArea->MaxLon." $QueryAdd LIMIT 20";
$ResultArray = array();
QDB($ResultArray,$DB[0],$query);
//If there aren't enough using SEINet, and the region includes Latin America, check Neotrop.
if(count($ResultArray) < 5 && $CurrentArea->MinLat < 30 && $CurrentArea->MinLon < -34 && $CurrentArea->MaxLon > -117)
	QDB($ResultArray,$DB[1],$query);

if(count($ResultArray) < 5)
	die("Error:  ".count($SearchArray)." specimens found in whole area.");

//************************************************************************
//  Region zeroed in to.  Find specific location and a list of plants.
//************************************************************************

$SearchArray = array();
$InputArea = clone $FullArea;
do
	{
	$Tries = 0;
	do
		{
		MainQuery($SearchArray,$InputArea);//Find at least 10 and up to 200 plants from a random rectangle inside this region
		}while(count($SearchArray) < 10);

	$Count = count($SearchArray);
	for($RandomChoice = 0;$RandomChoice < 50;$RandomChoice++)
		{//RandomChoice is just a counter.  50 trials should be plenty.
		//Randomly choose one of the specimens from this region.
		$Select = rand(0,$Count-1);
		$Degrees = .1;
		//Search for all plants within 0.1 degrees of this random specimen (about 10 kilometers).
		$CurrentArea = new SearchArea($SearchArray[$Select]['decimalLatitude'] - $Degrees, $SearchArray[$Select]['decimalLatitude'] + $Degrees,$SearchArray[$Select]['decimalLongitude'] - $Degrees, $SearchArray[$Select]['decimalLongitude'] + $Degrees);
		$ResultArray = array();
		$query = "SELECT sciname, decimalLatitude, decimalLongitude, habitat from omoccurrences WHERE sciname NOT LIKE '' AND sciname NOT LIKE 'indet.%' AND decimalLatitude != '' AND (decimalLatitude between {$CurrentArea->MinLat} AND {$CurrentArea->MaxLat}) AND (decimalLongitude between {$CurrentArea->MinLon} AND {$CurrentArea->MaxLon}) $QueryAdd";
		if($Debug)
			echo "Query = $query<br><br>";

		//First query SEINet
		QDB($ResultArray,$DB[0],$query);

		//If appropriate, query Neotropica too.
		if($CurrentArea->MinLat < 30 && $CurrentArea->MinLon < -34 && $CurrentArea->MaxLon > -117)
			QDB($ResultArray,$DB[1],$query);

		if($Debug)
			echo "Then Found nearby ".count($ResultArray)."<br>";
		if(count($ResultArray) >= 10)
			break; //We found a specimen that has at least 10 specimens nearby.
		}
	$Tries++;
	if($Tries > 200)
		die("Error: Search failed.  Try again. ");//I haven't see this happen...
	}while(count($ResultArray) < 5);
if($Debug)
	echo $query."<br><br>";

//************************************************************************
//  Assemble the list of plants to return to the html program.
//************************************************************************
$Plants = array();//This is an array where the keys are scinames, and the values are counts.
$Names = array();
$Count = array();
$LonArray = array(); //Used to find the median coordinates that determine the target location.
$LatArray = array();
$Content = ""; //The string that gets returned to WITW.
$Hint="";
foreach($ResultArray as $One)
	{
	$SciName = $One['sciname'];
	$Preg = preg_split("/[\s]+/", $One['sciname']); //Convert any sciname to "GENUS species", ignoring var., ssp. etc.
	$SciName = $Preg[0]." ".$Preg[1];
	if(array_key_exists($SciName,$Plants))
		$Plants[$SciName]++; //Already in the array, increment.
	else
		$Plants[$SciName] = 1; //Add to the array.

	if($Debug)
		printr($Plants,"Plants");

	$LonArray[] = $One['decimalLongitude'];
	$LatArray[] = $One['decimalLatitude'];
	if(strlen($Hint) < strlen($One['habitat']))
		{ //There might be ways to define the character set that would better resolve this.
		$Hint = str_replace('Ã','a',$One['habitat']);
		$Hint = str_replace('a§','c',$Hint);
		$Hint = str_replace('a£','a',$Hint);
		$Hint = str_replace('a¡','A',$Hint);
		$Hint = str_replace('©','c',$Hint);
		$Hint = str_replace('a³','a',$Hint);
		$Hint = str_replace('aº','a',$Hint);
		}
	}

//Determine median coordinates
sort($LonArray);
sort($LatArray);
$Lon = $LonArray[count($LonArray)/2];
$Lat = $LatArray[count($LatArray)/2];

//Randomize, then sort the array of species in descending order of quantity found.
//Randomize first to break up the alphabetical order.
uasort($Plants,RandomSort); //CallBack routine to pseudo randomize the array.
arsort($Plants);

//Take the 20 with the highest count, or if fewer take as many as there are
$Plants = array_slice($Plants,0,20);

//Break the Plants array into Names and Count
$Names = array_keys($Plants);
$Count = array_values($Plants);
$Max = count($Names);

$Content = $Lon.",".$Lat."\r\n";
$Content .= implode(",",$Names)."\r\n";
$Content .= implode(",",$Count)."\r\n";

//************************************************************************
//  Find image urls
//************************************************************************
$Images = array();

for($i=0;$i<$Max;$i++)
	{
	//First look for a sortsequence = 1 image.
	$Images[$i] = GetImageURL($Names[$i],0,true);
	if($Images[$i] == "")
		$Images[$i] = GetImageURL($Names[$i],1,true);
	if($Images[$i] == "")
		{ //Didn't find a 1, now look for another, in diminishing order of likelihood.
		$Images[$i] = GetImageURL($Names[$i],0,false);
		if($Images[$i] == "")
			$Images[$i] = GetImageURL($Names[$i],1,false);
		}
	if($Images[$i] == "")
		$Images[$i] = "images/None.jpg";
	}
$Content .= implode(",",$Images)."\r\n";


$Content .= $Hint."\r\n";
echo $Content;

//************************************************************************
//  End of program.  Functions follow.
//************************************************************************

function RandomSort($Plant1,$Plant2)
	{ //Pseudo-randomizes the list of plants while preserving the keys (SciName)
	return rand(-1,1);
	}

function MainQuery(&$ResultArray,$CurrentArea)
	{//Main query routine.  Returns the array by reference.

	global $DB,$QueryAdd,$Debug;
	if($Debug)
		printr($CurrentArea,"CurrentArea");

	$Fields = "sciname, decimalLatitude, decimalLongitude, habitat";
	$Mult = 100; //Basically how many decimal places to leave in the floating Lat and Long when finding a random square.  Probably doesn't matter much
	$LatSize = ($CurrentArea->MaxLat - $CurrentArea->MinLat)/50;//Break the region down into rectangles, 50x50 in the full region.
	$LonSize = ($CurrentArea->MaxLon - $CurrentArea->MinLon)/50;
	$RandomArea = new SearchArea();
	do
		{ //Find an area that is not in the ocean.
		$RandomArea->MinLat = rand(floor($Mult*$CurrentArea->MinLat),floor($Mult*$CurrentArea->MaxLat)-$LatSize)/$Mult;
		$RandomArea->MaxLat = $RandomArea->MinLat+$LatSize;
		$RandomArea->MinLon = rand(floor($Mult*$CurrentArea->MinLon),floor($Mult*$CurrentArea->MaxLon)-$LonSize)/$Mult;
		$RandomArea->MaxLon = $RandomArea->MinLon+$LonSize;
		} while(!CheckOcean($RandomArea));

	$query = "SELECT $Fields FROM omoccurrences WHERE sciname NOT LIKE '' AND sciname NOT LIKE 'indet.%' AND decimalLatitude != '' AND decimalLatitude between ".$RandomArea->MinLat." AND ".$RandomArea->MaxLat." AND decimalLongitude between ".$RandomArea->MinLon." AND ".$RandomArea->MaxLon." $QueryAdd LIMIT 200";

	QDB($ResultArray, $DB[0],$query);

	if($RandomArea->MinLat < 30 && $RandomArea->MinLon < -34 && $RandomArea->MaxLon > -117)
		{//If it could be Latin America, include Neotrop database.
		QDB($ResultArray,$DB[1],$query);
		}
	if($Debug)
		echo "MQ Count = ".count($ResultArray)."<br>";
	}

function CheckOcean($Area)
	{ //Make sure the rectangle $Area does not lie within an ocean
	//Done before querying the database, much faster than a bunch of queries that turn up empty.
	global $Oceans, $Debug; //$Oceans is read from file early in the program.
	foreach($Oceans as $O)
		{
		$O = explode(",",$O);
		if(($Area->MinLat > $O[0]) && ($Area->MaxLat < $O[1]) && ($Area->MinLon > $O[2]) && ($Area->MaxLon < $O[3]))
			{
			if($Debug)
				echo "Rejected<br>";
			return false;
			}
		}
	return true;
	}


function GetImageURL($SciName, $DBNum, $Sort)
	{//Kind of messy.  Determining the best image is not scientific.  If the image sortsequence is 1, then it's probably a good one.  But in many cases the sortsequence is 50 for every image.
	global $DB,$Debug;
	$TIDQuery = "SELECT TID FROM taxa WHERE SciName LIKE '$SciName%'";
	$TIDresult = $DB[$DBNum]->query($TIDQuery);
	if($TIDresult->num_rows > 0)
		{
		while($OneTID = $TIDresult->fetch_assoc())
			{
			if($TID != "")
				$TID .= ",";
			$TID .= $OneTID['TID'];
			}
		}
	else
		return "";//No TID was found for this plant, so unable to search for an image.

	if($TID == "")
		return "";
	$ImageQuery = "SELECT * FROM images WHERE tid IN ($TID) AND sortsequence = 1 LIMIT 1";
	$ImageResult = $DB[$DBNum]->query($ImageQuery);
	if($ImageResult->num_rows == 0)
		{//Look for the best possible image first.
		if($Sort)
			return "";
		$ImageQuery = "SELECT * FROM images WHERE tid IN ($TID) AND sortsequence > 0 ORDER BY sortsequence";
		$ImageResult = $DB[$DBNum]->query($ImageQuery);
		}

	if($ImageResult->num_rows == 0)
		{
		//die("Error: $SciName, $ImageQuery");
		$ImageQuery = "SELECT * FROM images WHERE tid IN ($TID) AND url != 'empty' AND sortsequence > 0 ORDER BY sortsequence";
		$ImageResult = $DB[$DBNum]->query($ImageQuery);
		}
	if($ImageResult->num_rows == 0)
		{ //Last try.  Any image will be accepted.
		$ImageQuery = "SELECT * FROM images WHERE tid IN ($TID) AND url != 'empty' LIMIT 1";
		$ImageResult = $DB[$DBNum]->query($ImageQuery);
		}
	$OneImageURL = $ImageResult->fetch_assoc();

	$URL = $OneImageURL['url'];
	if(strlen($URL) < 10)
		$URL = $OneImageURL['originalurl'];
	if($URL == "")
		return "";

	if(strstr($URL,"http") === false && strlen($URL) > 10)
		{ //Images that are on the swbiodiversity server are often stored with just local location.  Need to add the http://...
		$URL = "http://swbiodiversity.org".$URL;
		}
	return $URL;
	}

function QDB(&$ResultArray,$DB,$query)
	{//Query DataBase, return results in array $ResultArray
	$result = $DB->query($query);
	while($One = $result->fetch_assoc())
		$ResultArray[] = $One;
	}

function printr($ArrayName, $Caption="")
{//Encapsulates the print_r() function, adds the name of the array before and a line feed after
echo "<br>";
if($Caption != "")
	echo "$Caption<br>";
print_r($ArrayName);
echo "<br>";
}

?>
