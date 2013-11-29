<?php
require_once 'SQLSrvDBConn.class.php';

class ReportesSAOConn extends SQLSrvDBConn {
	
	public function __construct() {

		$SQLSrvDBConf = new SQLSrvDBConf( 'ReportesSAO', 'ModulosSAO' );
		
		parent::__construct( $SQLSrvDBConf );
	}
}
?>