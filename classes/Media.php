<?php
include_once($SERVER_ROOT . "/traits/Database.php");
include_once($SERVER_ROOT . "/classes/Sanitize.php");

abstract class UploadStrategy {
    /**
     * If a file is given then return the storage path for that resource otherwise just return the root path.
	 * @param array $file {name: string, type: string, tmp_name: string, error: int, size: int} 
     * @return string 
     */
	abstract public function getDirPath(array|string $file): string;

    /**
     * If a file is given then return the url path to that resource otherwise just return the root url path.
	 * @param array $file {name: string, type: string, tmp_name: string, error: int, size: int} 
     * @return string 
     */
	abstract public function getUrlPath(array|string $file): string;

    /**
     * Function to check if a file exists for the storage location of the upload strategy.
	 * @param array $file {name: string, type: string, tmp_name: string, error: int, size: int} 
     * @return bool 
     */
	abstract public function file_exists(array|string $file): bool;

    /**
     * Function to handle how a file should be uploaded.
	 * @param array $file {name: string, type: string, tmp_name: string, error: int, size: int} 
     * @return bool 
	 * @throws MediaException(MediaExceptionCase::DuplicateMediaFile)
     */
	abstract public function upload(array $file): bool;

    /**
     * Function to handle how a file should be removed.
	 * @param array $file {name: string, type: string, tmp_name: string, error: int, size: int} 
     * @return bool 
	 * @throws MediaException(MediaExceptionCase::DuplicateMediaFile)
     */
	abstract public function remove(string $file): bool;
}

class MediaFile {
	public string $name;
	public string $filepath;
	public string $extension;
	public string $mime_type;
	public MediaType $media_type;
	public string $is_remote;

	public function __construct(string | array $filepath) {
		//Array is assumed to be from $_FILES
		if(is_array($filepath)) {
			$this->name = $filepath['name'];
			$this->mime_type = $filepath['type'];
			$this->extension = Media::mime2ext($filepath['type']);

		} else {
			$file_name = $filepath;

			//Filepath maybe a url so clear out url query if it exists
			$query_pos = strpos($file_name,'?');
			if($query_pos) $file_name = substr($file_name, 0, $query_pos);

			$file_type_pos = strrpos($file_name,'.');
			$dir_path_pos = strrpos($file_name,'/');

			if($dir_path_pos !== false) $dir_path_pos += 1;
			if($file_type_pos === false || $file_type_pos < $dir_path_pos) {
				$file_type_pos = strlen($file_name);
			}

			$this->name = $Media::cleanFileName(
				substr($file_name, $dir_path_pos, $file_type_pos - $dir_path_pos)
			);

			$this->filepath = $filepath;
			$this->extension = substr($file_name, $file_type_pos + 1);
		}
	}
}

class SymbiotaUploadStrategy extends UploadStrategy {
	private string $institutioncode;
	private string | null $collectioncode;
	private string | null $catalognumber;

	public function __construct(string $institutioncode, string | null $collectioncode = null, string | null $catalognumber = null) {
		$this->institutioncode = $institutioncode;
		$this->collectioncode = $collectioncode;
		$this->catalognumber = $catalognumber;
	}

	public function getDirPath(array | string $file = null): string {
		$file_name = is_array($file)? $file['name']: $file;
		return $GLOBALS['IMAGE_ROOT_PATH'] . 
			(substr($GLOBALS['IMAGE_ROOT_PATH'],-1) != "/"? '/': '') . 
			$this->getPathPattern() . $file_name;
	}

	public function getUrlPath(array | string $file = null): string {
		$file_name = is_array($file)? $file['name']: $file;
		return $GLOBALS['IMAGE_ROOT_URL'] .
		   	(substr($GLOBALS['IMAGE_ROOT_URL'],-1) != "/"? '/': '') .
		   	$this->getPathPattern() . $file_name;
	}

    /**
     * Private help function for interal use that holds logic for how storage paths are created.
     * @return string 
     */
    function getPathPattern(): string {

		$root = $this->institutioncode . ($this->collectioncode? '_'. $this->collectioncode: '') . '/';

		if($this->catalognumber) {
			//Clean out Symbols that would interfere with 
			$derived_cat_num = str_replace(array('/','\\',' '), '', $this->catalognumber);

			//Grab any characters in the range of 0-8 then any amount digits
			if(preg_match('/^(\D{0,8}\d{4,})/', $derived_cat_num, $matches)){
				// TODO (Logan) figure out why this is here
				//$derived_cat_num = substr($matches[1], 0, -3);

				//If derived catalog number is a number less then five pad front with 0's
				if(is_numeric($derived_cat_num) && strlen($derived_cat_num) < 5) {
					$derived_cat_num = str_pad($derived_cat_num, 5, "0", STR_PAD_LEFT);
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

	public function file_exists(array|string $file): bool {
		if(is_array($file)) {
			return file_exists($this->getDirPath() . $file['name']);
		} else { 
			return file_exists($this->getDirPath() . $file);
		}
	}

    /**
     * Upload implemenation stores files on the server and expect duplicate files to be handled by the caller
     */
	public function upload(array $file): bool {
		$dir_path = $this->getDirPath();
		$file_path = $dir_path . $file['name'];

		// Create Storage Directory If it doesn't exist
		if(!is_dir($dir_path)) {
			mkdir($dir_path, 744, true);
		}
		
		if(file_exists($file_path)) {
			throw new MediaException(MediaExceptionCase::DuplicateMediaFile);
		}

		//If Uploaded from $_POST then move file to new path
		if(is_uploaded_file($file['tmp_name'])) {
			move_uploaded_file($file['tmp_name'], $file_path);
		//If temp path is on server then just move to new location;
		} else if(file_exists($file['tmp_name'])) {
			rename($file['tmp_name'], $file_path);
		//Otherwise assume tmp_name a url and stream file contents over
		} else {
			error_log("Moving" . $file['tmp_name'] . ' to ' . $file_path );
			file_put_contents($file_path, fopen($file['tmp_name'], 'r'));
		}

		return true;
	}

	static private function on_system($path) {
		//Check if path is absoulte path
		if(file_exists($path)) {
			return true;
		}
		//Convert url path to dir_path
		$dir_path = str_replace(
			$GLOBALS['IMAGE_ROOT_URL'], 
			$GLOBALS['IMAGE_ROOT_PATH'], 
			$path
		);

		return file_exists($dir_path);
	}

	//url -> some_domain/ srat_path / filename
	//remap 1 to 2 which could be
	//	- remote upload from somewhere
	//	- moving a file
	//		- file exists on same system
	//	- nothing
	//		- file exists on same path

	public function remove(string $filename): bool {
		//Check Relative Path
		if($this->file_exists($filename)) {
			if(!unlink($this->getDirPath($filename))) {
				error_log("WARNING: File (path: " . $this->getDirPath($filename) . ") failed to delete from server in SymbiotaUploadStrategy->remove");
				return false;
			};
			return true;
		}

		//Get Absoulte Path
		$dir_path = str_replace(
			$GLOBALS['IMAGE_ROOT_URL'], 
			$GLOBALS['IMAGE_ROOT_PATH'], 
			$filename
		);

		//Check Absolute path
		if($dir_path !== $filename && file_exists($dir_path)) {
			if(!unlink($dir_path)) {
				error_log("WARNING: File (path: " . $dir_path. ") failed to delete from server in SymbiotaUploadStrategy->remove");
				return false;
			}
			return true;
		}

		return false;
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
	case DuplicateMediaFile = 'Duplicate Media File';
}

class MediaException extends Exception {
    function __construct(private MediaExceptionCase $case){
		parent::__construct($case->value);
    }
}

class Media {
	use Database;
	private static $mediaRootPath;
	private static $mediaRootUrl;

	private const DEFAULT_THUMBNAIL_WIDTH_PX = 200;
	private const DEFAULT_WEB_WIDTH_PX = 1600;
	private const DEFAULT_LARGE_WIDTH_PX = 3168;
	private const WEB_FILE_SIZE_LIMIT = 300000;
	private const DEFAULT_JPG_COMPRESSION = 70;
	private const DEFAULT_TEST_ORIENTATION = false;

	private const DEFAULT_GEN_LARGE_IMG = true;
	private const DEFAULT_GEN_WEB_IMG = true;
	private const DEFAULT_GEN_THUMBNAIL_IMG = true;

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
	 * Pulls file name out of directory path or url
	 * 
	 * Note: The url parsing expects the filename to not be in the query or hash
	 *
	 * @param string $filepath Can be a file or url path
	 * return array<string,mixed> 
	 */
	public static function parseFileName(string $filepath): array {
		$file_name = $filepath;

		//Filepath maybe a url so clear out url query if it exists
		$query_pos = strpos($file_name,'?');
		if($query_pos) $file_name = substr($file_name, 0, $query_pos);

		$file_type_pos = strrpos($file_name,'.');
		$dir_path_pos = strrpos($file_name,'/');

		if($dir_path_pos !== false) $dir_path_pos += 1;
		if($file_type_pos === false || $file_type_pos < $dir_path_pos) {
			$file_type_pos = strlen($file_name);
		}
		return [
			'name' => substr($file_name, $dir_path_pos, $file_type_pos - $dir_path_pos),
			'tmp_name' => $filepath,
			'extension' => substr($file_name, $file_type_pos + 1),
		];
	}

	public static function mime2ext(string $mime): string | bool {
		$mime_map = [
			'video/3gpp2' => '3g2',
			'video/3gp'=> '3gp',
			'video/3gpp'=> '3gp',
			'application/x-compressed'=> '7zip',
			'audio/x-acc'=> 'aac',
			'audio/ac3'=> 'ac3',
			'application/postscript' => 'ai',
			'audio/x-aiff' => 'aif',
			'audio/aiff' => 'aif',
			'audio/x-au' => 'au',
			'video/x-msvideo' => 'avi',
			'video/msvideo' => 'avi',
			'video/avi' => 'avi',
			'application/x-troff-msvideo' => 'avi',
			'application/macbinary' => 'bin',
			'application/mac-binary' => 'bin',
			'application/x-binary' => 'bin',
			'application/x-macbinary' => 'bin',
			'image/bmp' => 'bmp',
			'image/x-bmp' => 'bmp',
			'image/x-bitmap' => 'bmp',
			'image/x-xbitmap' => 'bmp',
			'image/x-win-bitmap' => 'bmp',
			'image/x-windows-bmp' => 'bmp',
			'image/ms-bmp' => 'bmp',
			'image/x-ms-bmp' => 'bmp',
			'application/bmp' => 'bmp',
			'application/x-bmp' => 'bmp',
			'application/x-win-bitmap' => 'bmp',
			'application/cdr' => 'cdr',
			'application/coreldraw' => 'cdr',
			'application/x-cdr' => 'cdr',
			'application/x-coreldraw' => 'cdr',
			'image/cdr' => 'cdr',
			'image/x-cdr' => 'cdr',
			'zz-application/zz-winassoc-cdr' => 'cdr',
			'application/mac-compactpro' => 'cpt',
			'application/pkix-crl' => 'crl',
			'application/pkcs-crl' => 'crl',
			'application/x-x509-ca-cert' => 'crt',
			'application/pkix-cert' => 'crt',
			'text/css' => 'css',
			'text/x-comma-separated-values' => 'csv',
			'text/comma-separated-values' => 'csv',
			'application/vnd.msexcel' => 'csv',
			'application/x-director' => 'dcr',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
			'application/x-dvi' => 'dvi',
			'message/rfc822' => 'eml',
			'application/x-msdownload' => 'exe',
			'video/x-f4v' => 'f4v',
			'audio/x-flac' => 'flac',
			'video/x-flv' => 'flv',
			'image/gif' => 'gif',
			'application/gpg-keys' => 'gpg',
			'application/x-gtar' => 'gtar',
			'application/x-gzip' => 'gzip',
			'application/mac-binhex40' => 'hqx',
			'application/mac-binhex' => 'hqx',
			'application/x-binhex40' => 'hqx',
			'application/x-mac-binhex40' => 'hqx',
			'text/html' => 'html',
			'image/x-icon' => 'ico',
			'image/x-ico' => 'ico',
			'image/vnd.microsoft.icon' => 'ico',
			'text/calendar' => 'ics',
			'application/java-archive' => 'jar',
			'application/x-java-application' => 'jar',
			'application/x-jar' => 'jar',
			'image/jp2' => 'jp2',
			'video/mj2' => 'jp2',
			'image/jpx' => 'jp2',
			'image/jpm' => 'jp2',
			'image/jpeg' => 'jpg',
			'image/pjpeg' => 'jpg',
			'application/x-javascript' => 'js',
			'application/json' => 'json',
			'text/json' => 'json',
			'application/vnd.google-earth.kml+xml' => 'kml',
			'application/vnd.google-earth.kmz' => 'kmz',
			'text/x-log' => 'log',
			'audio/x-m4a' => 'm4a',
			'audio/mp4' => 'm4a',
			'application/vnd.mpegurl' => 'm4u',
			'audio/midi' => 'mid',
			'application/vnd.mif' => 'mif',
			'video/quicktime' => 'mov',
			'video/x-sgi-movie' => 'movie',
			'audio/mpeg' => 'mp3',
			'audio/mpg' => 'mp3',
			'audio/mpeg3' => 'mp3',
			'audio/mp3' => 'mp3',
			'video/mp4' => 'mp4',
			'video/mpeg' => 'mpeg',
			'application/oda' => 'oda',
			'audio/ogg' => 'ogg',
			'video/ogg' => 'ogg',
			'application/ogg' => 'ogg',
			'font/otf' => 'otf',
			'application/x-pkcs10' => 'p10',
			'application/pkcs10' => 'p10',
			'application/x-pkcs12' => 'p12',
			'application/x-pkcs7-signature' => 'p7a',
			'application/pkcs7-mime' => 'p7c',
			'application/x-pkcs7-mime' => 'p7c',
			'application/x-pkcs7-certreqresp' => 'p7r',
			'application/pkcs7-signature' => 'p7s',
			'application/pdf' => 'pdf',
			'application/octet-stream' => 'pdf',
			'application/x-x509-user-cert' => 'pem',
			'application/x-pem-file' => 'pem',
			'application/pgp' => 'pgp',
			'application/x-httpd-php' => 'php',
			'application/php' => 'php',
			'application/x-php' => 'php',
			'text/php' => 'php',
			'text/x-php' => 'php',
			'application/x-httpd-php-source' => 'php',
			'image/png' => 'png',
			'image/x-png' => 'png',
			'application/powerpoint' => 'ppt',
			'application/vnd.ms-powerpoint' => 'ppt',
			'application/vnd.ms-office' => 'ppt',
			'application/msword' => 'doc',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
			'application/x-photoshop' => 'psd',
			'image/vnd.adobe.photoshop' => 'psd',
			'audio/x-realaudio' => 'ra',
			'audio/x-pn-realaudio' => 'ram',
			'application/x-rar' => 'rar',
			'application/rar' => 'rar',
			'application/x-rar-compressed' => 'rar',
			'audio/x-pn-realaudio-plugin' => 'rpm',
			'application/x-pkcs7' => 'rsa',
			'text/rtf' => 'rtf',
			'text/richtext' => 'rtx',
			'video/vnd.rn-realvideo' => 'rv',
			'application/x-stuffit' => 'sit',
			'application/smil' => 'smil',
			'text/srt' => 'srt',
			'image/svg+xml' => 'svg',
			'application/x-shockwave-flash' => 'swf',
			'application/x-tar' => 'tar',
			'application/x-gzip-compressed' => 'tgz',
			'image/tiff' => 'tiff',
			'font/ttf' => 'ttf',
			'text/plain' => 'txt',
			'text/x-vcard' => 'vcf',
			'application/videolan' => 'vlc',
			'text/vtt' => 'vtt',
			'audio/x-wav' => 'wav',
			'audio/wave' => 'wav',
			'audio/wav' => 'wav',
			'application/wbxml' => 'wbxml',
			'video/webm' => 'webm',
			'image/webp' => 'webp',
			'audio/x-ms-wma' => 'wma',
			'application/wmlc' => 'wmlc',
			'video/x-ms-wmv' => 'wmv',
			'video/x-ms-asf' => 'wmv',
			'font/woff' => 'woff',
			'font/woff2' => 'woff2',
			'application/xhtml+xml' => 'xhtml',
			'application/excel' => 'xl',
			'application/msexcel' => 'xls',
			'application/x-msexcel' => 'xls',
			'application/x-ms-excel' => 'xls',
			'application/x-excel' => 'xls',
			'application/x-dos_ms_excel' => 'xls',
			'application/xls' => 'xls',
			'application/x-xls' => 'xls',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
			'application/vnd.ms-excel' => 'xlsx',
			'application/xml' => 'xml',
			'text/xml' => 'xml',
			'text/xsl' => 'xsl',
			'application/xspf+xml' => 'xspf',
			'application/x-compress' => 'z',
			'application/x-zip' => 'zip',
			'application/zip' => 'zip',
			'application/x-zip-compressed' => 'zip',
			'application/s-compressed' => 'zip',
			'multipart/x-zip' => 'zip',
			'text/x-scriptzsh' => 'zsh',
		];

		return isset($mime_map[$mime]) ? $mime_map[$mime] : false;
	}

	/* 
	 * Curls url for header information and returns a $_FILES like file array
	 * @param string $url
	 * return array | bool
	 */
	public static function getRemoteFileInfo(string $url): array|bool {
		//TODO (Logan) Alternative Method exists to curl?
		if(!function_exists('curl_init')) throw new Exception('Curl is not installed');
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 3);

		$data = curl_exec($ch);

		//If there is no data then throw error
		if($data === false && $errno = curl_errno($ch)) { 
			$message = curl_strerror($errno);
			curl_close($ch);
			throw new Exception($message);
		}

		$retCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if($retCode >= 400) {
			error_log(
				'Error Status ' . $retCode . ' in getRemoteFileInfo LINE:' . __LINE__ . 
				' URL:' . $url
			);
			return false;
		}

		$file_size_bytes = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		$file_type_mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

		curl_close($ch);

		$parsed_file = self::parseFileName($url);
		$parsed_file['name'] = self::cleanFileName($parsed_file['name']);

		if(!$parsed_file['extension'] && $file_type_mime) {
			$parsed_file['extension'] = self::mime2ext($file_type_mime);
		}

		return [
			'name' => $parsed_file['name'] . ($parsed_file['extension'] ? '.' .$parsed_file['extension']: ''),
			'tmp_name' => $url,
			'error' => 0,
			'type' => $file_type_mime,
			'size' => intval($file_size_bytes)
		];
	}

	/**
	 * Strips out undesired characters from from a pure file name string
	 *
	 * @param string $file_name A file name without the extension
	 * return string
	 */
	private static function cleanFileName(string $file_name):string {
		$file_name = str_replace(".","", $file_name);
		$file_name = str_replace(array("%20","%23"," ","__"),"_",$file_name);
		$file_name = str_replace("__","_",$file_name);
		$file_name = str_replace(array(chr(231),chr(232),chr(233),chr(234),chr(260)),"a",$file_name);
		$file_name = str_replace(array(chr(230),chr(236),chr(237),chr(238)),"e",$file_name);
		$file_name = str_replace(array(chr(239),chr(240),chr(241),chr(261)),"i",$file_name);
		$file_name = str_replace(array(chr(247),chr(248),chr(249),chr(262)),"o",$file_name);
		$file_name = str_replace(array(chr(250),chr(251),chr(263)),"u", $file_name);
		$file_name = str_replace(array(chr(264),chr(265)),"n",$file_name);
		$file_name = preg_replace("/[^a-zA-Z0-9\-_]/", "", $file_name);
		$file_name = trim($file_name,' _-');

		if(strlen($file_name) > 30) {
			$file_name = substr($file_name, 0, 30);
		}

		//TODO (Logan) Make sure this is not needed
		//$file_name .= '_'.time();

		return $file_name;
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
      * This function returns the maximum files size that can be uploaded 
      * in PHP
      * @returns int File size in bytes
      **/
	public static function getMaximumFileUploadSize(): int {  
		return min(
			self::size_2_bytes(ini_get('post_max_size')), 
			self::size_2_bytes(ini_get('upload_max_filesize'))
		);  
	}  

	private static function size_2_bytes(string $size):int {
		// Remove the non-unit characters from the size.
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); 
		// Remove the non-numeric characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size); 
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		}
		else {
			return round($size);
		}
	}

	private static function isValidFile(array | null $file): bool {
		return $file && !empty($file) && isset($file['error']) && $file['error'] === 0;
	}

	/** $post_arr Keys:
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
	 * action = "Submit New Images
	**/

    /**
     * @param array<int,mixed> $post_arr
     * @param UploadStrategy $upload_strategy
	 * @param array $file {name: string, type: string, tmp_name: string, error: int, size: int} 
     * @return bool
     */
    public static function add(array $post_arr, UploadStrategy $upload_strategy, array $file): void {
		$clean_post_arr = Sanitize::in($post_arr);

		$copy_to_server = $clean_post_arr['copytoserver']?? false;
		$mapLargeImg = !($clean_post_arr['nolgimage']?? true);
		$isRemoteMedia = isset($clean_post_arr['imgurl']) && $clean_post_arr['imgurl'];
		$should_upload_file = self::isValidFile($file) || $copy_to_server;

		//If no file is given and downloads from urls are enabled
		if(!self::isValidFile($file) && $isRemoteMedia) {
			$file = self::getRemoteFileInfo($clean_post_arr['imgurl']);
		}

		//If that didn't popluate then return;
		if(!self::isValidFile($file)) {
			throw new Exception('Error: Uploaded/Remote media missing');
		}

		//If file being uploaded is too big throw error 
		else if($should_upload_file && self::getMaximumFileUploadSize() < intval($file['size'])) {
			throw new Exception('Error: File is to large to upload');
		}

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

		$media_type_str = explode('/', $file['type'])[0];
		$media_type = MediaType::tryFrom($media_type_str);

		if(!$media_type) throw new MediaException(MediaExceptionCase::InvalidMediaType);
		
		$keyValuePairs = [
			"tid" => $clean_post_arr["tid"] ?? null,
			"occid" => $clean_post_arr["occid"],
			"url" => null,
			"thumbnailUrl" => $clean_post_arr["tnurl"]?? null,
			//This is a very bad name that refers to source or downloaded url
			"originalUrl" => $clean_post_arr["sourceurl"]?? null,
			"archiveUrl" => $clean_post_arr["archiverurl"]?? null,// Only Occurrence import
			"sourceUrl" => $clean_post_arr["sourceurl"]?? null,// TPImageEditorManager / Occurrence import
			"referenceUrl" => $clean_post_arr["referenceurl"]?? null,// check keys again might not be one,
			"creator" => $clean_post_arr["photographer"],
			"creatorUid" => OccurrenceUtilities::verifyUser($clean_post_arr["photographeruid"], $conn) ,
			"format" => $file["type"],
			"caption" => $clean_post_arr["caption"]?? null,
			"owner" => $clean_post_arr["owner"]??null, //TPImageEditorManager / Occurrence import
			"locality" => $clean_post_arr["locality"]?? null, //Only in the TPImageEditorManager
			"anatomy" => null, //Only Occurrent import
			"notes" => $clean_post_arr["notes"]?? null,
			"username" => Sanitize::in($GLOBALS['USERNAME']),
			"sortsequence" => array_key_exists('sortsequence', $clean_post_arr) && is_numeric($clean_post_arr['sortsequence']) ? $clean_post_arr['sortsequence'] : null,
			//check if its is_numeric?
			"sortOccurrence" => $clean_post_arr['sortoccurrence']?? null,
			"sourceIdentifier" => 'filename: ' . $file['name'],
			"rights" => null, // Only Occurrence import
			"accessrights" => null, // Only Occurrence import
			"copyright" => $clean_post_arr['copyright'],
			"hashFunction" => null, // Only Occurrence import
			"hashValue" => null, // Only Occurrence import
			"mediaMD5" => null,// Only Occurrence import
			"recordID" => UuidFactory::getUuidV4(),
			"media_type" => $media_type_str,
		];

		//What is url for files
		if($isRemoteMedia && $media_type === MediaType::Image) {
			//Required to exist
			$source_url = $clean_post_arr['imgurl'];
			$keyValuePairs['originalUrl'] =  $source_url;
			$keyValuePairs['url'] = $clean_post_arr['weburl']?? $source_url;

		} else {
			$keyValuePairs['url'] = $upload_strategy->getUrlPath() . $file['name'];
			$keyValuePairs['originalUrl'] = $upload_strategy->getUrlPath() . $file['name'];
		}
		
		$keys = implode(",", array_keys($keyValuePairs));
		$parameters = str_repeat('?,', count($keyValuePairs) - 1) . '?';

		$sql = <<< SQL
		INSERT INTO media($keys) VALUES ($parameters)
		SQL;

		$conn = self::connect('write');
		mysqli_begin_transaction($conn);
		try {
			//insert media
			$result = mysqli_execute_query($conn, $sql, array_values($keyValuePairs));
			//Insert to other tables as needed like imagetags...

			$media_id = $conn->insert_id;

			if($should_upload_file) {
				//Check if file exists
				if($upload_strategy->file_exists($file)) {
					//Add media_id onto end of file name which should be unique within portal
					$file['name'] = self::addToFilename($file['name'], '_' . $media_id);

					//Fail case the appended media_id is taken stops after 10 
					$cnt = 1;
					while($upload_strategy->file_exists($file) && $cnt < 10) {
						$file['name'] = self::addToFilename($file['name'], '_' . $cnt);
						$cnt++;
					}
					$updated_path = $upload_strategy->getUrlPath() . $file['name'];

					//Update source url to reflect new filename
					self::update_metadata([
						'url' => $updated_path, 
						'sourceUrl' => $updated_path, 
						'originalUrl' => $updated_path
					], $media_id, $conn);
				}

				$upload_strategy->upload($file);

				//Generate Deriatives if needed
				if($media_type === MediaType::Image) {
					//Will download file if its remote. 
					//This is a naive solution assuming we are upload to our server 
					$size = getimagesize($upload_strategy->getDirPath($file));
					$metadata = [
						'pixelXDimension' => $size[0],
						'pixelXDimension' => $size[1]
					];

					$width = $size[0];
					$height = $size[1];

					$thumb_url = $clean_post_arr['tnurl']?? null;
					if(!$thumb_url) {
						$thumb_name = self::addToFilename($file['name'], '_tn');
						self::create_image(
							$file['name'],
							self::addToFilename($file['name'], '_tn'),
							$upload_strategy,
							$GLOBALS['IMG_TN_WIDTH']?? 200,
							0
					   	);

						if($upload_strategy->file_exists($thumb_name)) {
							$metadata['thumbnailUrl'] = $upload_strategy->getUrlPath($thumb_name);
						}
					}

					$med_url = $clean_post_arr['weburl']?? null;
					if(!$med_url) {
						$med_name =	self::addToFilename($file['name'], '_lg');
						self::create_image(
							$file['name'],
							$med_name,
							$upload_strategy,
							$GLOBALS['IMG_LG_WIDTH']?? 1400,
							0
					   	);

						if($upload_strategy->file_exists($med_name)) {
							$metadata['url'] = $upload_strategy->getUrlPath($med_name);
						}
					}
					
					self::update_metadata($metadata, $media_id, $conn);
				}
			}

			mysqli_commit($conn);
		} catch(Exception $e) {
			mysqli_rollback($conn);
			//TODO (Logan) figure out if this is too lazy
			//TODO (Logan) maybe add file cleanup on failure? 
			throw new Exception($e->getMessage());
		} 
	}

	private static function addToFilename(string $filename, string $ext): string {
		return substr_replace(
			$filename, 
			$ext, 
			strrpos($filename, '.'), 
			0
		);
	}

	public static function remap(
		int $media_id, int $new_occid, 
		UploadStrategy $old_strategy,
		UploadStrategy $new_strategy
	) {
		$media_arr = self::getMedia($media_id);
		$update_arr = ['occid' => $new_occid];
		$move_files = [];

		if($media_arr['url']) {
			$file = self::parseFileName($media_arr['url']);
			$filename = $file['name'] . $file['extension'];

			//Check if stored in our system if so move to path
			if($old_strategy->file_exists($filename)) {
				$update_arr['url'] = $new_strategy->getUrlPath($filename);
				array_push($move_files, $filename);
			}
		}

		$remap_urls = ['url', 'originalUrl', 'thumbnailUrl'];
		foreach($remap_urls as $url) {
			if($media_arr[$url]) {
				$file = self::parseFileName($media_arr[$url]);
				$filename = $file['name'] . '.' . $file['extension'];

				//Check if stored in our system if so move to path
				if($old_strategy->file_exists($filename) && $old_strategy->getDirPath() !== $new_strategy->getDirPath()) {
					$url_path = $new_strategy->getUrlPath($filename);

					if(!in_array($url_path, $update_arr)) {
						$file = [
							'name' => $filename, 
							'tmp_name' => $old_strategy->getDirPath($filename)
						];
						array_push($move_files, $file);
					}

					$update_arr[$url] = $url_path;
				}
			}
		}

		self::update_metadata($update_arr, $media_id);
		
		foreach($move_files as $file) {
			$new_strategy->upload($file);
			$old_strategy->remove($file['name']);
		}
	}

	//TODO (Logan) Just make a public interface for update_metadata
	public static function disassociate($media_id) {
		self::update_metadata(['occid' => null], $media_id);
	}

	public static function update($media_id, $media_arr) {
		//$clean_arr = Sanitize::in($post_arr);
	
		$meta_data = [
			"tid",
			"occid",
			"url",
			"thumbnailUrl",
			"originalUrl",
			"archiveUrl",
			"sourceUrl",
			"referenceUrl",
			"creator",
			"creatorUid",
			"format",
			"caption",
			"owner",
			"locality",
			"anatomy",
			"notes",
			"username",
			"sortsequence",
			"sortOccurrence",
			"sourceIdentifier",
			"rights",
			"accessrights",
			"copyright",
			"hashFunction",
			"hashValue",
			"mediaMD5",
			"recordID",
			"media_type",
		];

		$data = [];

		//Map keys to values
		foreach ($meta_data as $key) {
			if(array_key_exists($key, $media_arr)) {
				$update_metadata[$key] = $media_arr[$key];
			}
		}
		$conn = self::connect('write');
		mysqli_begin_transaction($conn);
		try {
			self::update_metadata($data, $media_id, $conn);

			//url
			if(array_key_exists("renameweburl", $media_arr)) {
				//self::remap()
			}

			//thumbnailUrl
			if(array_key_exists("renametnurl", $media_arr)) {
				//self::remap()
			}

			//originalUrl
			if(array_key_exists("renameorigurl", $media_arr)) {
				//self::remap()
			}

			mysqli_commit($conn);
			return true;
		} catch(Exception $e) {
			mysqli_rollback($conn);
			error_log('ERROR: Media update failed on media_id ' 
				. $media_id . ' ' .$e->getMessage()
			);
			return false;
		}
	}

	/*
	 * While the function does create an image it does so to resize it
	 *
	 * This function is a wrapper to call the correct image generation function based on what image handler is configured in a given Symbiota Portal. Most use gd 
	 *
	 * @param string $src_file Filename to image base
	 * @param string $new_file Filename for newly resized image
	 * @param UploadStrategy $upload_strategy Class that instructs where how how an image should be stored
	 * @param int $new_width Maximum width for the new image if zero will box to height
	 * @param int $new_height Maximum height for the new image if zero will box to width
	 */
	public static function create_image($src_file, $new_file, UploadStrategy $upload_strategy, $new_width, $new_height): void {
		global $USE_IMAGE_MAGICK;

		if($USE_IMAGE_MAGICK) {
			self::create_image_imagick($src_file, $new_file, $upload_strategy, $new_width, $new_height);
		} elseif(extension_loaded('gd') && function_exists('gd_info')) {
			self::create_image_gd($src_file, $new_file, $upload_strategy, $new_width, $new_height);
		} else {
			throw new Exception('No image handler for image conversions');
		}

		//If file doesn't according to the upload strategy then upload it to the correct place. This will only run if the media storage is remote to the server
		if(!$upload_strategy->file_exists($new_file)) {
			$upload_strategy->upload([
				'name' => $new_file,
				'tmp_name' => $upload_strategy->getDirPath($new_file),
			]);
		}
	}

	/*
	 * While the function does create an image it does so to resize it
	 *
	 * This function is implemenation for Symbiota Portals using imagick.
	 * Most portals using imagick have ImageMagick installed on server and make system calls in order to use it.
	 * At the time of making this function no know portals have the imagick pecl package installed but and implemenation was made as we are potentially heading in that direction.
	 * 
	 * @param string $src_file Filename to image base
	 * @param string $new_file Filename for newly resized image
	 * @param UploadStrategy $upload_strategy Class that instructs where how how an image should be stored
	 * @param int $new_width Maximum width for the new image if zero will box to height
	 * @param int $new_height Maximum height for the new image if zero will box to width
	 */
	private static function create_image_imagick(
		string $src_file, string $new_file, 
		UploadStrategy $upload_strategy, 
		int $new_width, int $new_height
	): void {
		$src_path = $upload_strategy->getDirPath($src_file);
		$new_path = $upload_strategy->getDirPath($new_file);

		if($new_height === 0 && $new_width === 0) {
			throw new Exception('Must have width or height as non zero values');
		} else if($new_height === 0) {
			$new_height = $new_width;
		} else if($new_width === 0) {
			$new_width = $new_height;
		}

		if(extension_loaded('imagick')) {
			$new_image = new Imagick();
			$new_image->readImage($src_path);
			$new_image->resizeImage($new_height, $new_width, Imagick::FILTER_LANCZOS, 1, TRUE);
			$new_image->writeImage($new_path);
			$new_image->destroy();
		} else {
			$qualityRating = self::DEFAULT_JPG_COMPRESSION;

			if($new_width < 300) {
				$ct = system('convert '. $src_path . ' -thumbnail ' . $new_width .' x ' . ($new_width * 1.5).' '.$new_path);
			} else {
				$ct = system('convert '. $src_path . ' -resize ' . $new_width.'x' . ($new_width * 1.5) . ($qualityRating?' -quality '.$qualityRating:'').' '.$new_path);
			}

			if(!file_exists($new_path)){
				error_log('ERROR: Image failed to be created in Imagick function (target path: '.$new_path.')');
			}
		}
	}

	/*
	 * While the function does create an image it does so to resize it
	 *
	 * This function is implemenation for Symbiota Portals using gd.
	 * Gd is the typical default configuration for most portals
	 * 
	 * @param string $src_file Filename to image base
	 * @param string $new_file Filename for newly resized image
	 * @param UploadStrategy $upload_strategy Class that instructs where how how an image should be stored
	 * @param int $new_width Maximum width for the new image if zero will box to height
	 * @param int $new_height Maximum height for the new image if zero will box to width
	 */
	private static function create_image_gd(
		string $src_file, string $new_file, 
		UploadStrategy $upload_strategy, 
		int $new_width, int $new_height
	): void {

		$src_path = $upload_strategy->getDirPath($src_file);
		$new_path = $upload_strategy->getDirPath($new_file);

		if($new_width === 0 && $new_height === 0) {
			throw new Exception('Must have width or height as non zero values');
		}

		$size = getimagesize($src_path);
		$width = $size[0];
		$height = $size[1];
		$mime_type = $size['mime'];

		$orig_width = $width;
		$orig_height = $height;

		if($height > $new_height && $new_height !== 0) {
			$width = intval(($new_height / $height) * $width);
			$height = $new_height;
		}

		if($width > $new_width && $new_width !== 0) {
			$height = intval(($new_width / $width) * $height);
			$width = $new_width;
		}

		$new_image = imagecreatetruecolor($width, $height);

		$image = match($mime_type) {
			'image/jpeg' => imagecreatefromjpeg($src_path),
			'image/png' => imagecreatefrompng($src_path),
			'image/gif' => imagecreatefromgif($src_path),
			default => throw new Exception(
				'Mime Type: ' . $mime_type . ' not supported for creation'
			)
		};

		//This is need to maintain transparency if this is here
		if($mime_type === 'image/png') {
			imagealphablending($new_image, false);
			imagesavealpha($new_image, true);
		}

		imagecopyresampled($new_image, $image, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);

		//Handle Specific file types here
		if($mime_type === 'image/png') {
			imagepng($new_image, $new_path);
		} else {
			imagejpeg($new_image, $new_path);
		}

		imagedestroy($image);
	}

	/**
	 * For updating metadata in the media table only
	 *
	 * This function is assumes clean data because it is interal
	 *
	 * @param array $metadata_arr Key value array of Media table attributes
	 * @return void
	 * @throws Exception
	 **/
	private static function update_metadata(array $metadata_arr, int $media_id, mysqli $conn = null): void {
		$values = [];
		$parameter_str = '';

		foreach ($metadata_arr as $key => $value) {
			if($parameter_str !== '') $parameter_str .= ', ';
			$parameter_str .= $key . " = ?";
			array_push($values, $value);
		}
		array_push($values, $media_id);

		$sql = 'UPDATE media set '. $parameter_str . ' where media_id = ?';
		mysqli_execute_query(
			$conn ?? self::connect('write'), 
			$sql, 
			$values
		);
	}

	//TODO (Logan) rework to use new remove function in upload strategy
	public static function delete($media_id, $remove_files = true): void {
		$conn = self::connect('write');
		$result = mysqli_execute_query(
			$conn, 
			'SELECT url, thumbnailUrl, originalUrl from media where media_id = ?', 
			[$media_id]
		);
		$media_urls = $result->fetch_assoc();

		$queries = [
			'DELETE FROM specprocessorrawlabels WHERE imgid = ?',
			'DELETE FROM imagetag WHERE imgid = ?',
			'DELETE FROM media WHERE media_id = ?'
		];
		mysqli_begin_transaction($conn);
		try {
			foreach ($queries as $query) {
				mysqli_execute_query($conn, $query, [$media_id]);
			}

			//Unlink all files
			if($remove_files) {
				foreach($media_urls as $url) {
					if($url && file_exists($GLOBALS['SERVER_ROOT'] . $url)) {
						if(!unlink($GLOBALS['SERVER_ROOT'] . $url)) {
							error_log("WARNING: File (path: " . $url . ") failed to delete from server");
						}
					}
				}
			}
			mysqli_commit($conn);
		} catch(Exception $e) {
			error_log("Error: couldnt' remove media of media_id " . $media_id .": " . $e->getMessage());
			mysqli_rollback($conn);
		}
	}

    /**
     * @param int $occid
     * @param MediaType $media_type
     */
    public static function getMedia(int $media_id, MediaType $media_type = null): Array {
		if(!$media_id) return [];
		$parameters = [$media_id];
		$select = [
			'm.*',
			"IFNULL(m.creator,CONCAT_WS(' ',u.firstname,u.lastname)) AS creatorDisplay",
			't.sciname',
			't.author',
			't.rankid'
		];

		$sql ='SELECT ' . implode(', ', $select) .' FROM media m ' .
		'LEFT JOIN taxa t ON t.tid = m.tid ' .
		'LEFT JOIN users u on u.uid = m.creatorUid ' .
		'WHERE media_id = ?';

		if($media_type) {
			$sql .= ' AND media_type = ?';
			array_push($parameters, self::getMediaTypeString($media_type));
		}

		$sql .= ' ORDER BY sortoccurrence ASC';
		$results = mysqli_execute_query(self::connect('readonly'), $sql, $parameters);
		$media = self::get_media_items($results);
		if(count($media) <= 0) {
			return [];
		} else {
			return Sanitize::out($media[$media_id]);
		}
	}

    /**
     * @param int $occid
     * @param MediaType $media_type
     */
    public static function fetchOccurrenceMedia(int $occid, MediaType $media_type = null): Array {
		if(!$occid) return [];
		$parameters = [$occid];
		$sql = 'SELECT * FROM media WHERE occid = ?';

		if($media_type) {
			$sql .= ' AND media_type = ?';
			array_push($parameters, self::getMediaTypeString($media_type));
		}

		$sql .= ' ORDER BY sortoccurrence ASC';

		$results = mysqli_execute_query(self::connect('readonly'), $sql, $parameters);

		return Sanitize::out(self::get_media_items($results));
	}

	private static function get_media_items($results): array {
		$media_items = Array();

		while($row = $results->fetch_assoc()){
			$media_items[$row['media_id']] = $row;
		}
		$results->free();

		return $media_items;
	}
	
	public static function getMediaTags(int|array $media_id, mysqli $conn = null): array {
		$sql = 'SELECT t.imgid, k.tagkey, k.shortlabel, k.description_en FROM imagetag t INNER JOIN imagetagkey k ON t.keyvalue = k.tagkey WHERE t.imgid ';

		if(is_array($media_id)) {
			$count = count($media_id);
			if($count <= 0) {
				return [];
			}
			$sql .= 'IN (' . str_repeat('?,', $count - 1) . '?)';
		} else {
			$sql .= '= ?';
		}

		$res = mysqli_execute_query(
			$conn?? self::connect('readonly'), 
			$sql, 
			is_array($media_id)? $media_id: [$media_id]
		);
		$tags = [];
		while($row = $res->fetch_object()) {
			$tags[$row->imgid][$row->tagkey] = $row->shortlabel;
		}
		$res->free();

		return Sanitize::out($tags);
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
		if($media_arr['originalUrl']) $testArr[] = $media_arr['originalUrl'];
		if($media_arr['url']) $testArr[] = $media_arr['url'];
		if($media_arr['thumbnailUrl']) $testArr[] = $media_arr['thumbnailUrl'];

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
		if($media_arr['originalUrl']) $testArr[] = $media_arr['originalUrl'];
		if($media_arr['url']) $testArr[] = $media_arr['url'];
		if($media_arr['thumbnailUrl']) $testArr[] = $media_arr['thumbnailUrl'];
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
