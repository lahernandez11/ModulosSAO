<?php
require_once 'SQLSrvDBConn.class.php';

abstract class SAODBConn extends SQLSrvDBConn {
	
	public function __construct( SQLSrvDBConf $SQLSrvConf ) {

		parent::__construct($SQLSrvConf);
	}

	public static function getInstance( $name ) {		
		$SQLSrvDBConf;
		
		$conn;

		switch ($name) {
			case 'SUB':
				$SQLSrvConf = new SQLSrvDBConf( 'SAO1814_SUB', 'ModulosSAO' );
				$conn = new SAOSubcontratosDBConn();
				break;
			
			default:
			$SQLSrvConf = new SQLSrvDBConf( 'SAO1814', 'ModulosSAO' );
				$conn = new SAO1814DBConn();
				break;
		}

		return $conn;
	}
}
require_once 'SAO1814DBConn.class.php';
require_once 'SAOSubcontratosDBConn.class.php';
?>