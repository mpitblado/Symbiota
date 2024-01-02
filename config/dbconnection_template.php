<?php
class MySQLiConnectionFactory {
	static $SERVERS = array(
		array(
			'type' => 'readonly',
			'host' => 'localhost',
			'username' => '',
			'password' => '',
			'database' => '',
			'port' => '3306',
			'charset' => 'utf8'		//utf8, latin1, latin2, etc
		),
		array(
			'type' => 'write',
			'host' => 'localhost',
			'username' => '',
			'password' => '',
			'database' => '',
			'port' => '3306',
			'charset' => 'utf8'
		)
	);

	public static function getCon($type) {
		// Figure out which connections are open, automatically opening any connections
		// which are failed or not yet opened but can be (re)established.
		for ($i = 0, $n = count(MySQLiConnectionFactory::$SERVERS); $i < $n; $i++) {
			// BUG FIX for mysqli exceptions thrown for PHP > 8.0.0  TODO - 
			if (version_compare(PHP_VERSION, '8.1.0') >= 0) {
				mysqli_report(MYSQLI_REPORT_OFF);
			}
			$server = MySQLiConnectionFactory::$SERVERS[$i];
			if($server['type'] == $type){
				$connection = new mysqli($server['host'], $server['username'], $server['password'], $server['database'], $server['port']);
				if(mysqli_connect_errno()){
					throw new Exception('Could not connect to any databases! Please try again later.');
				}
				if(isset($server['charset']) && $server['charset']) {
					if(!$connection->set_charset($server['charset'])){
						throw new Exception('Error loading character set '.$server['charset'].': '.$connection->error);
					}
				}
				return $connection;
			}
		}
	}
}
?>