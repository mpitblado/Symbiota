<?php
include_once($SERVER_ROOT . "/traits/Database.php");
include_once($SERVER_ROOT . "/classes/Sanitize.php");


abstract class UploadStrategy {
	abstract public static function getBaseDirPath(): string;
	abstract public static function getBaseUrlPath(): string;
	abstract public function getDirPath(): string;
	abstract public function getUrlPath(): string;
	abstract public function upload($file): bool;
}

class SymbiotaUploadStrategy extends UploadStrategy {
	private string $institutioncode;
	private string $collectioncode;
	private string $catalognumber;

	public function __construct($institutioncode, $collectioncode, $catalognumber) {
		$this->institutioncode = $institutioncode;
		$this->collectioncode = $collectioncode;
		$this->catalognumber = $collectioncode;
	}

	public static function getBaseDirPath(): string {
		return $GLOBALS['IMAGE_ROOT_PATH'] . (substr($GLOBALS['IMAGE_ROOT_PATH'],-1) != "/"? '/': '');
	}

	public static function getBaseUrlPath(): string {
		return $GLOBALS['IMAGE_ROOT_URL'] . (substr($GLOBALS['IMAGE_ROOT_URL'],-1) != "/"? '/': '');
	}

	public function getDirPath(): string {
		return self::getBaseDirPath() . $this->getPathPattern();
	}

	public function getUrlPath(): string {
		return self::getBaseUrlPath() . $this->getPathPattern();
	}

    public static function constructPath(string $institutionCode, string $collectionCode = null, string $catalogNumber = null): string {

		$root = $institutionCode . ($collectionCode? '_'. $collectionCode: '') . '/';

		if($catalogNumber) {
			//Clean out Symbols that would interfere with 
			$derived_cat_num = str_replace(array('/','\\',' '), '', $catalogNumber);

			//Grab any characters in the range of 0-8 then any amount digits
			if(preg_match('/^(\D{0,8}\d{4,})/', $derived_cat_num, $matches)){
				//Parse out everything but last 3 digits
				$derived_cat_num = substr($matches[1], 0, -3);

				//If derived catalog number is a number less then five pad front with 0's
				if(is_numeric($derived_cat_num) && strlen($derived_cat_num) < 5) {
					str_pad($derived_cat_num, 5, "0", STR_PAD_LEFT);
				}

				$root .= $derived_cat_num . '/';
			//backup catalogNumber
			} else {
				$root .= '00000/';
			}
		//Use date as a backup so that main directory doesn't get filled up but can debug
		} else {
			$root .= date('Ym') . '/';
		}

		return $root;
	}

	public function getPathPattern() : string {
		return self::constructPath(
			$this->institutioncode, 
			$this->collectioncode, 
			$this->catalognumber
		);
	}

	public function upload($file): bool {
		$dir_path = $this->getDirPath();
		$file_path = $dir_path . $file['name'];

		// Create Storage Directory If it doesn't exist
		if(!is_dir($dir_path)) {
			mkdir($dir_path, 744, true);
		}
		
		if(file_exists($file_path)) {
			//Handle duplicate file paths	
		}

		//Upload file to server
		move_uploaded_file($file['tmp_name'], $file_path);

		return true;
	}
} 

enum MediaType: string {
	case Image = 'image'; 
	case Audio = 'audio';
	case Video = 'video' ; 

	public static function values(): array {
       return array_column(self::cases(), 'value');
    }
}

enum MediaExceptionCase: string {
	case InvalidMediaType = 'Invalid Media Type';
}

class MediaException extends Exception {
    function __construct(private MediaExceptionCase $case){
        match($case){
            MediaExceptionCase::InvalidMediaType => parent::__construct($case->value),
        };
    }
}

class Media {
	use Database;
	private static $mediaRootPath;
	private static $mediaRootUrl;

	private static function getMediaRootPath(): string {
		if(self::$mediaRootPath) {
			return self::$mediaRootPath;
		}else if(substr($GLOBALS['IMAGE_ROOT_PATH'],-1) != "/") {
			return self::$mediaRootPath = $GLOBALS['IMAGE_ROOT_PATH'] . '/';
		} else {
			return self::$mediaRootPath = $GLOBALS['IMAGE_ROOT_PATH'];
		}
	}

	private static function getMediaRootUrl(): string {
		if(self::$mediaRootUrl) {
			return self::$mediaRootUrl;
		}else if(substr($GLOBALS['IMAGE_ROOT_URL'],-1) != "/") {
			return self::$mediaRootUrl = $GLOBALS['IMAGE_ROOT_URL'] . '/';
		} else {
			return self::$mediaRootUrl = $GLOBALS['IMAGE_ROOT_URL'];
		}
	}

    /**
     * @param string $institutionCode
     * @param string $collectionCode
     * @param string $catalogNumber
     */
    public static function getCollectionMediaRoot(string $institutionCode, string $collectionCode = null, string $catalogNumber = null): string {

		$root = $institutionCode . ($collectionCode? '_'. $collectionCode: '') . '/';

		if($catalogNumber) {
			//Clean out Symbols that would interfere with 
			$derived_cat_num = str_replace(array('/','\\',' '), '', $catalogNumber);

			//Grab any characters in the range of 0-8 then any amount digits
			if(preg_match('/^(\D{0,8}\d{4,})/', $derived_cat_num, $matches)){
				//Parse out everything but last 3 digits
				$derived_cat_num = substr($matches[1], 0, -3);

				//If derived catalog number is a number less then five pad front with 0's
				if(is_numeric($derived_cat_num) && strlen($derived_cat_num) < 5) {
					str_pad($derived_cat_num, 5, "0", STR_PAD_LEFT);
				}

				$root .= $derived_cat_num . '/';
			//backup catalogNumber
			} else {
				$root .= '00000/';
			}
		//Use date as a backup so that main directory doesn't get filled up but can debug
		} else {
			$root .= date('Ym') . '/';
		}

		return $root;
	}

    /**
     * @param MediaType $media_type
     * @return string
     */
    private static function getMediaTypeString(MediaType $media_type): string {
		switch($media_type) {
			case MediaType::Image: return 'image';
			case MediaType::Audio: return 'audio';
			case MediaType::Video: return 'video';
			default: throw new Exception("Invalid Media Type", 1);
		}
	}

    /**
     * @param array<int,mixed> $post_arr
     * @param array<mixed> $occur_map
     * @return bool
     */
    public static function addMedia(array $post_arr, UploadStrategy $uploadStrategy): bool {
		$clean_post_arr = Sanitize::in($post_arr);

		/*$collection_media_path = Self::getCollectionMediaRoot(
			$occur_map['institutioncode'], 
			$occur_map['collectioncode'],
			$occur_map['catalognumber']
		);*/

		$copy_to_server = $clean_post_arr['copytoserver']?? false;
		$mapLargeImg = !($clean_post_arr['nolgimage']?? true);

		//  What we need to do:
		// 1. we need to create a media path record
		// 2. We need to figure out if the resourece is being stored on the server if not we are done
		// 3. If we are storing the media use the upload strategy provided
		
		// Basic Upload Stratgety
		// get path following pattern institutioncode(?_collectioncode)/catalognumber | ym
		// check if path exists if not create path
		// make sure path is writable 
		// attempt to write file
		// if any of this fails rollback media table insert and send the appropriate error
	//
		$media_type = explode('/',$_FILES['imgfile']['type'])[0];

		if(empty($_FILES)) {

		}

		//Not type currently sent
		switch(MediaType::tryFrom($media_type)) {
			case MediaType::Image:
				//If Media is a Image we nee
			break;
			case MediaType::Audio:

			break;
			case MediaType::Video:
				// Currently Not supported
			break;

			//Do execeptions for Media
			Default: return false;
		}

		/** Keys:
		 * imgurl 
		 * weburl
		 * sourceurl
		 * tnurl
		 * photographeruid
		 * photographer
		 * notes
		 * copyright
		 * sortoccurrence
		 * occid
		 * occidindex
		 * csmode
		 * tabindex
		 * action = "Submit New Images"
		**/

		//thumbnailurl, imgweburl, and imgLgurl need https:// and https:// checked
		//thumbnailurl 
		//Gen thumbnailurl if it doesn't exist and its a photo
		$conn = self::connect('write');

		$sql = <<< SQL
		SELECT tidinterpreted 
		FROM omoccurrences 
		WHERE tidinterpreted IS NOT NULL AND occid = ? 
		SQL;

		$taxon_result = mysqli_execute_query(
			$conn,
			$sql, 
			[$clean_post_arr['occid']]
		);

		if($row = $taxon_result->fetch_object()) {
			$clean_post_arr['tid'] = $row->tidinterpreted;
		}
		$clean_post_arr["sourceurl"] = $uploadStrategy->getUrlPath() . $_FILES['imgfile']['name'];
		//don't generate thumbnails for now
		$clean_post_arr["tnurl"] = $clean_post_arr["sourceurl"];

		$keyValuePairs = [
			"tid" => $clean_post_arr["tid"],
			"occid" => $clean_post_arr["occid"],
			"url" => $clean_post_arr["weburl"]?? $clean_post_arr["sourceurl"],
			"thumbnailUrl" => $clean_post_arr["tnurl"],
			//This is a very bad name that refers to source or downloaded url
			"originalUrl" => $clean_post_arr["sourceurl"]?? null,
			"archiveUrl" => $clean_post_arr["archiverul"]?? null,// Only Occurrence import
			"sourceUrl" => $clean_post_arr["sourceurl"]?? null,// TPImageEditorManager / Occurrence import
			"referenceUrl" => $clean_post_arr["referenceurl"]?? null,// check keys again might not be one,
			"creator" => $clean_post_arr["photographer"],
			"creatorUid" => OccurrenceUtilities::verifyUser($clean_post_arr["photographeruid"], $conn) ,
			"format" => $_FILES["imgfile"]["type"],
			"caption" => $clean_post_arr["caption"]?? null,
			"owner" => $clean_post_arr["owner"]??null, //TPImageEditorManager / Occurrence import
			"locality" => $clean_post_arr["locality"]?? null, //Only in the TPImageEditorManager
			"anatomy" => null, //Only Occurrent import
			"notes" => $clean_post_arr["notes"]?? null,
			"username" => Sanitize::in($GLOBALS['USERNAME']),
			"sortsequence" => array_key_exists('sortsequence', $clean_post_arr) && is_numeric($clean_post_arr['sortsequence']) ? $clean_post_arr['sortsequence'] : null,
			//check if its is_numeric?
			"sortOccurrence" => $clean_post_arr['sortoccurrence']?? null,
			"sourceIdentifier" => 'filename: ' . $_FILES['imgfile']['name'],
			"rights" => null, // Only Occurrence import
			"accessrights" => null, // Only Occurrence import
			"copyright" => $clean_post_arr['copyright'],
			"hashFunction" => null, // Only Occurrence import
			"hashValue" => null, // Only Occurrence import
			"mediaMD5" => null,// Only Occurrence import
			"recordID" => UuidFactory::getUuidV4(),
			"media_type" => $media_type,
		];
		
		$keys = implode(",", array_keys($keyValuePairs));
		$parameters = str_repeat('?,', count($keyValuePairs) - 1) . '?';

		/*
		foreach ($keyValuePairs as $key => $value) {
			echo '<div style="display:flex"><span>'. $key .'</span>=><span>'. $value .'</span></div>';
		}*/

		$sql = <<< SQL
		INSERT INTO media($keys) VALUES ($parameters)
		SQL;

		$conn = self::connect('write');
		mysqli_begin_transaction($conn);
		try {
			//insert media
			$result = mysqli_execute_query($conn, $sql, array_values($keyValuePairs));

			//Insert to other tables as needed like imagetags
			$media_id = $conn->insert_id;

			// Upload media if we need to 
			// TODO (Logan) implment upload strategy
			$uploadStrategy->upload($_FILES['imgfile']);

			mysqli_commit($conn);
		} catch(Exception $e) {
			mysqli_rollback($conn);
			echo 'Rollback because' . $e->getMessage();
			//TODO (Logan) decide on how this is going to get handled above
		}


		//echo 'uploads file to ' . self::getMediaRootPath() . $collection_media_path . $_FILES['imgfile']['name'];
		
		// Upload thumbnailurl to thumbnail path
		// Upload thumbnailurl to thumbnail path
		return true;
	}

    /**
     * @param int $occid
     * @param MediaType $media_type
     */
    public static function fetchOccurrenceMedia(int $occid, MediaType $media_type = null): Array {
		if(!$occid) return [];
		$parameters = [$occid];
		$sql = <<< SQL
		SELECT 
			media_id, 
			media_type, 
			url, 
			thumbnailurl, 
			originalurl, 
			caption, 
			creator, 
			creatorUid, 
			sourceurl,
			copyright, 
			notes, 
			occid, 
			username, 
			sortoccurrence, 
			initialtimestamp 
		FROM media 
		WHERE occid = ? 
		SQL;

		if($media_type) {
			$sql .= ' AND media_type = ?';
			array_push($parameters, self::getMediaTypeString($media_type));
		}

		$sql .= ' ORDER BY sortoccurrence ASC';

		$results = mysqli_execute_query(self::connect('write'), $sql, $parameters);

		$media_items = Array();

		while($row = $results->fetch_object()){
			$media_items[$row->media_id]['url'] = $row->url;
			$media_items[$row->media_id]['tnurl'] = $row->thumbnailurl;
			$media_items[$row->media_id]['media_type'] = $row->media_type;
			$media_items[$row->media_id]['origurl'] = $row->originalurl;
			$media_items[$row->media_id]['caption'] = $row->caption;
			$media_items[$row->media_id]['creator'] = $row->creator;
			$media_items[$row->media_id]['creatorUid'] = $row->creatorUid;
			$media_items[$row->media_id]['sourceurl'] = $row->sourceurl;
			$media_items[$row->media_id]['copyright'] = $row->copyright;
			$media_items[$row->media_id]['notes'] = $row->notes;
			$media_items[$row->media_id]['occid'] = $row->occid;
			$media_items[$row->media_id]['username'] = $row->username;
			$media_items[$row->media_id]['sort'] = $row->sortoccurrence;
		}
		$results->free();
		return $media_items;
	}

    /**
     * @return array<string>
     */
    public static function getCreatorArray(): array {
		$sql = <<< SQL
		SELECT u.uid, CONCAT_WS(', ',u.lastname,u.firstname) AS fullname 
		FROM users u 
		ORDER BY u.lastname, u.firstname 
		SQL;

		$result = mysqli_execute_query(self::connect('readonly'), $sql);
		$creators = array();

		while($row = $result->fetch_object()){
			$creators[$row->uid] = Sanitize::out($row->fullname);
		}
		$result->free();
		return $creators;
	}

	// TODO (Logan) change this to getMediaTagKeys (will need to rework this table)
    /**
     * @return array<string>
     */
	public static function getImageTagKeys(): array {
		$retArr = Array();

		$sql = <<< SQL
		SELECT tagkey, description_en FROM imagetagkey ORDER BY sortorder;
		SQL;

		$result = mysqli_execute_query(self::connect('readonly'), $sql);
		while($r = $result->fetch_object()){
			$retArr[$r->tagkey] = Sanitize::out($r->description_en);
		}
		$result->free();
		return $retArr;
	}
    /**
     * @param mixed $media_arr
     */
    private static function imagesAreWritable($media_arr): bool{
		$bool = false;
		$testArr = array();
		if($media_arr['origurl']) $testArr[] = $media_arr['origurl'];
		if($media_arr['url']) $testArr[] = $media_arr['url'];
		if($media_arr['tnurl']) $testArr[] = $media_arr['tnurl'];

		$rootPath = self::getMediaRootPath();
		$rootUrl = self::getMediaRootUrl();

		foreach($testArr as $url) {
			if(strpos($url, $rootPath) === 0) {
				if(is_writable($rootPath.substr($url, strlen($rootUrl)))) {
					$bool = true;
				} else {
					$bool = false;
					break;
				}
			}
		}
		return $bool;
	}
    /**
     * @param array<int,mixed> $media_arr
     */
    private static function imageNotCatalogNumberLimited(array $media_arr, int $occid): bool{
		$bool = true;
		$testArr = array();
		if($media_arr['origurl']) $testArr[] = $media_arr['origurl'];
		if($media_arr['url']) $testArr[] = $media_arr['url'];
		if($media_arr['tnurl']) $testArr[] = $media_arr['tnurl'];
		//Load identifiers
		$idArr = array();
		$sql = 'SELECT o.catalogNumber, o.otherCatalogNumbers, i.identifierValue FROM omoccurrences o LEFT JOIN omoccuridentifiers i ON o.occid = i.occid WHERE o.occid = ?';
		$rs = mysqli_execute_query(self::connect('readonly'), $sql, [$occid]);
		$cnt = 0;
		while($r = $rs->fetch_object()){
			if(!$cnt){
				if($r->catalogNumber) $idArr[] = $r->catalogNumber;
				if($r->otherCatalogNumbers) $idArr[] = $r->otherCatalogNumbers;
			}
			if($r->identifierValue) $idArr[] = $r->identifierValue;
			$cnt++;
		}
		$rs->free();
		//Iterate through identifiers and check for identifiers in name
		foreach($idArr as $idStr){
			foreach($testArr as $url){
				if($fileName = substr($url, strrpos($url, '/'))){
					if(strpos($fileName, $idStr) !== false && !preg_match('/_\d{10}[_\.]{1}/', $fileName)){
						$bool = false;
						break 2;
					}
				}
			}
		}
		return $bool;
	}
    /**
     * @return bool
     * @param mixed $imgArr
     * @param mixed $occid
     */
    public static function isRemappable($imgArr, $occid): bool{
		$bool = false;
		//If all images are writable, then we can rename the images to ensure they will not match incoming images
		$bool = self::imagesAreWritable($imgArr);
		if(!$bool){
			//Or if the image name doesn't contain the catalog number or there is a timestamp added to filename
			$bool = self::imageNotCatalogNumberLimited($imgArr, $occid);
		}
		return $bool;
	}
}

?>
