<?php
require_once 'SAODBConn.class.php';

class SAOSubcontratosDBConn extends SAODBConn {
	
	public function __construct() {
		
		$SQLSrvDBConf = new SQLSrvDBConf( 'SAO1814_SUB', 'ModulosSAO' );
		
		parent::__construct( $SQLSrvDBConf );
	}
}
?>