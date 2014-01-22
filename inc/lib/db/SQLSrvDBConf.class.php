<?php
require_once 'DBConf.class.php';

/**
* Clase de configuracion para realizar una conexion al
* servidor de bases de datos tipo Microsoft SQL Server
* utilizando el driver Microsoft SQL Server Driver for PHP
*
* @author Uziel Bueno
* @date 07.02.2013
*/
class SQLSrvDBConf extends DBConf {

	private $appName;
	private $connectionInfo = array();

	public function __construct( $confKey, $appName ) {

		parent::__construct( $confKey );
		
		$this->appName = $appName;

		$this->setConnectionInfo();
	}

	public function getAppName() {
		return $this->appName;
	}

	public function getConnectionInfo() {
		return $this->connectionInfo;
	}

	private function setConnectionInfo() {

		sqlsrv_configure("WarningsReturnAsErrors", 0);

		$this->connectionInfo["UID"] 	  			  = $this->getUser();
		$this->connectionInfo["PWD"] 	  			  = $this->getPwd();
		$this->connectionInfo["Database"] 			  = $this->getDBName();
		$this->connectionInfo["APP"] 	  			  = $this->getAppName();
		$this->connectionInfo["ReturnDatesAsStrings"] = "1";
		$this->connectionInfo["CharacterSet"] 		  = "UTF-8";
	}
	
	public function __toString() {
		
		$conf  = parent::__toString();

		$conf .= "APP={$this->getAppName()};\n"
			  ."ReturnDatesAsStrings=".$this->connectionInfo["ReturnDatesAsStrings"].";\n"
			  ."CharacterSet=".$this->connectionInfo["CharacterSet"];

		return $conf;
	}
}
?>