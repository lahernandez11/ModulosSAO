<?php
require_once 'SQLSrvDBConn.class.php';

class ModulosSAOConn extends SQLSrvDBConn {
	
	public function __construct() {

		$SQLSrvDBConf = new SQLSrvDBConf( 'ModulosSAO', 'ModulosSAO' );
		
		parent::__construct( $SQLSrvDBConf );
	}
}
?>