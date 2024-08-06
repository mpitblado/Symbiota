<?php
include_once($SERVER_ROOT . "/traits/Database.php");
include_once($SERVER_ROOT . "/classes/Sanitize.php");

enum MediaType {
	case Image; 
	case Audio;
	case Video; 
}
class TestDatabasePooling {
	use Database;
    /**
     * @return void
     */
    public static function test_connect(): void {
		self::connect('write');
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

	private static function mediaRootUrl(): string {
		if(self::$mediaRootUrl) {
			return self::$mediaRootUrl;
		}else if(substr($GLOBALS['IMAGE_ROOT_URL'],-1) != "/") {
			return self::$mediaRootUrl = $GLOBALS['IMAGE_ROOT_URL'] . '/';
		} else {
			return self::$mediaRootUrl = $GLOBALS['IMAGE_ROOT_URL'];
		}
	}

	private function getMediaRootUrl() {
		if(substr($GLOBALS['IMAGE_ROOT_PATH'],-1) != "/") {
			self::$mediaRootPath = $GLOBALS['IMAGE_ROOT_PATH'] . '/';
		} else {
			self::$mediaRootPath = $GLOBALS['IMAGE_ROOT_PATH'];
		}
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

	public static function isRemappable($imgArr, $occid){
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
