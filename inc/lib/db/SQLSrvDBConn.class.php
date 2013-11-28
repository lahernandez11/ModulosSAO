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

	private $dbConn;

	//static $instance;

	/*
	public static function getInstance() {

		if( ! self::$instance instanceof self ) {

			self::$instance = new self();
		}

		return self::$instance;
	}
	*/

	public function __construct( SQLSrvDBConf $SQLSrvConf ) {
		
		$this->dbConn = sqlsrv_connect( $SQLSrvConf->getDBServer(), $SQLSrvConf->getConnectionInfo() );

		if( ! $this->dbConn )
			throw new DBServerConnectionException($this->getStatementExecutionError());
	}

	public function executeQuery( $tsql, $params = array() ) {

		$stmt = sqlsrv_query( $this->dbConn, $tsql, $params );

		if( ! $stmt ) {
			throw new DBServerStatementExecutionException($this->getStatementExecutionError());
		}

		$rowsCollection = array();

		while( $obj = sqlsrv_fetch_object( $stmt ) )
			$rowsCollection[] = $obj;

		return $rowsCollection;
	}

	public function executeSP( $tsql, $params = array() ) {

		$stmt = sqlsrv_query( $this->dbConn, $tsql, $params );

		if( ! $stmt ) {
			throw new DBServerStatementExecutionException($this->getStatementExecutionError());
		}

		$rowsCollection = array();

		while( $obj = sqlsrv_fetch_object( $stmt ) )
			$rowsCollection[] = $obj;

		return $rowsCollection;
	}

	private function getStatementExecutionError() {

		$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
	
		//$errorNumber = getErrorNumber();
		
		$errorMessage = '';
		
		//if( $errorNumber >= 50000 ) {
		$errorMessage = utf8_encode(str_replace("[Microsoft][SQL Server Native Client 10.0][SQL Server]", "", $errors[0]['message']));
		//}
		//else
		//	$errorMessage = 'Ocurrió un error al realizar la petición. Intentelo nuevamente.';

		$this->logDBError();
	
		return $errorMessage;
	}

	private function logDBError() {

		$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
	
		$errorMessage = date('d.m.Y h:i:s').' - [SQLSTATE]=>'.$errors[0]['SQLSTATE'].'[CODE]=>'.$errors[0]['code'].'[MESSAGE]=>'.$errors[0]['message'];
		
		error_log($errorMessage);
	}

	public function __destruct() {
		
		if( is_resource( $this->dbConn ) )
			sqlsrv_close($this->dbConn);
	}
}
?>