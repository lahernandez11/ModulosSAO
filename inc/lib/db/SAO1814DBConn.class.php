<?php
require_once 'SAODBConn.class.php';

class SAO1814DBConn extends SAODBConn {
	
	public function __construct() {
		
		$SQLSrvDBConf = new SQLSrvDBConf( 'SAO1814', 'ModulosSAO' );
		
		parent::__construct( $SQLSrvDBConf );
	}
}
?>