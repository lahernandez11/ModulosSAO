<?php
require_once 'SQLSrvDBConn.class.php';

class SAODBConn extends SQLSrvDBConn {
	
	public static function getInstance( SQLSrvDBConf $conf ) {
		
		if ( empty( self::$instance ) ) {
			self::$instance = new self( $conf );
		} elseif ( self::$instance->dbConf->getSourceName() !== $conf->getSourceName() ) {
			self::$instance = new self( $conf );
		}

		return self::$instance;
	}

	protected function __construct( SQLSrvDBConf $SQLSrvConf ) {

		parent::__construct( $SQLSrvConf );
	}

	public function __toString() {
		return parent::__toString();
	}
}
?>