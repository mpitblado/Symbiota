<?php
/*
 * This Trait is used for classes that do not extend Manager;
 */
include_once($SERVER_ROOT.'/config/dbconnection.php');

// Currently will make a connection evertime new class is used TODO (Logan) change this to a static class?
trait Database {
	protected static $conn = null;
    /**
     * @param string $connection_type
     * @param bool $override_connection
	 * @return mysqli
     */
    public static function connect(string $connection_type, bool $override_connection = false): Object {
		if(!self::$conn || $override_connection) {
			echo 'created new connection<br>';
			self::$conn = MySQLiConnectionFactory::getCon($connection_type);
			return self::$conn;
		} else {
			echo 'used old one<br>';
			return self::$conn;
		}
	}
}
?>
