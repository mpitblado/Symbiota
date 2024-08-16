<?php
/*
 * This Trait is used for classes that do not extend Manager;
 */
include_once($SERVER_ROOT.'/config/dbconnection.php');

// Currently will make a connection evertime new class is used TODO (Logan) change this to a static class?
// Maybe we want differnt connections per class? It is like this already anyway
trait Database {
	protected static $conns = [];
    /**
     * @param string $conn_type Type of db connection either 'write' or 'read'
     * @param bool $override_conn Flag if you want to for reset the connection
	 * @return mysqli
     */
    public static function connect(string $conn_type, bool $override_conn = false): Object {
		if(!isset(self::$conns[$conn_type]) || !self::$conns[$conn_type] || $override_conn) {
			$conn = MySQLiConnectionFactory::getCon($conn_type);
			self::$conns[$conn_type] = $conn; 
			return $conn;
		} else {
			return self::$conns[$conn_type];
		}
	}
}
?>
