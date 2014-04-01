<?php
require_once 'SQLSrvDBConf.class.php';
require_once 'DBExceptions.class.php';

/**
* Clase que crea una conexion a un servidore de bases de datos
* de tipo Microsoft SQL Server
*
* @author Uziel Bueno
* @date 07.02.2013
*/
class SQLSrvDBConn {

	protected static $instance;

	protected $dbConn;
	public $dbConf;
	protected $stmt;

	public static function getInstance( SQLSrvDBConf $conf ) {
		
		if ( empty( self::$instance ) ) {
			self::$instance = new self( $conf );
		} elseif ( self::$instance->dbConf->getSourceName() !== $conf->getSourceName() ) {
			self::$instance = new self( $conf );
		}

		return self::$instance;
	}

	protected function __construct( SQLSrvDBConf $conf ) {

		$this->dbConf = $conf;
		
		$this->dbConn = sqlsrv_connect( $conf->getHost(), $conf->getConnectionInfo() );

		if( ! $this->dbConn )
			throw new DBServerConnectionException( $this->getStatementExecutionError() );
	}

	public function prepare( $stmt, array $values ) {
		return sqlsrv_prepare( $this->dbConn, $stmt, $values );
	}

	public function getLastStmt() {
		return $this->stmt;
	}

	public function executeQuery( $tsql, $params = array() ) {

		$stmt = sqlsrv_query( $this->dbConn, $tsql, $params );

		if( ! $stmt ) {
			throw new DBServerStatementExecutionException( $this->getStatementExecutionError() );
		}

		$this->stmt = $stmt;

		$rowsCollection = array();

		while ( $obj = sqlsrv_fetch_object( $stmt ) )
			$rowsCollection[] = $obj;

		return $rowsCollection;
	}

	public function executeQueryGetId( $tsql, $params = array() ) {

		$tsql .= "SELECT SCOPE_IDENTITY() AS [SCOPE_IDENTITY];";

		$stmt = sqlsrv_query( $this->dbConn, $tsql, $params );

		if( ! $stmt ) {
			throw new DBServerStatementExecutionException( $this->getStatementExecutionError() );
		}

		sqlsrv_next_result($stmt);
        
        sqlsrv_fetch($stmt);

        return sqlsrv_get_field($stmt, 0);
	}

	public function executeSP( $tsql, $params = array() ) {

		$stmt = sqlsrv_query( $this->dbConn, $tsql, $params );

		if ( ! $stmt ) {
			throw new DBServerStatementExecutionException($this->getStatementExecutionError());
		}

		$rowsCollection = array();

		while( $obj = sqlsrv_fetch_object( $stmt ) )
			$rowsCollection[] = $obj;

		return $rowsCollection;
	}

	protected function getStatementExecutionError() {

		$errors = sqlsrv_errors( SQLSRV_ERR_ERRORS );
		$message = $errors[0]["message"];
		$message = substr( $message, strrpos( $errors[0]["message"], "]" ) + 1 );
		
		// $message = utf8_encode(str_replace("[Microsoft][SQL Server Native Client 10.0][SQL Server]", "", $errors[0]['message']));

		$this->logDBError();
	
		return $message;
	}

	private function logDBError() {

		$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
	
		$errorMessage = date('d.m.Y h:i:s').' - [SQLSTATE]=>'.$errors[0]['SQLSTATE'].'[CODE]=>'.$errors[0]['code'].'[MESSAGE]=>'.$errors[0]['message'];
		
		error_log( $errorMessage );
	}

	public function __toString() {

		return $this->dbConf->__toString();
	}

	public function __destruct() {
		
		if( is_resource( $this->dbConn ) )
			sqlsrv_close($this->dbConn);
	}
}
?>