<?php
require_once 'SQLSrvDBConn.class.php';
require_once 'models/App.class.php';

class ModulosSAOConn extends SQLSrvDBConn {

	const SOURCE_NAME = 'ModulosSAO';
	protected static $conn;

	protected function __construct( SQLSrvDBConf $conf ) {

		$this->dbConf = $conf;
		
		$this->dbConn = sqlsrv_connect( $conf->getHost(), $conf->getConnectionInfo() );

		if( ! $this->dbConn )
			throw new DBServerConnectionException( $this->getStatementExecutionError() );
	}

	public static function getInstance( SQLSrvDBConf $conf=null ) {
		$conf = new SQLSrvDBConf( self::SOURCE_NAME, App::APP_NAME );

		if ( empty( self::$conn ) ) {
			self::$conn = new self( $conf );
		}

		return self::$conn;
	}
}
?>