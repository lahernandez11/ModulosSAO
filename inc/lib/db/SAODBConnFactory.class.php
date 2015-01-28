<?php
require_once 'models/App.class.php';
require_once 'db/SQLSrvDBConf.class.php';
require_once 'db/SAODBConn.class.php';

abstract class SAODBConnFactory {

	// Crea una instancia de conexion al sao utilizando datos de
	// conexion que se encuentren con el $source_name definidos
	// en db_config
	public static function getInstance( $conf_name )
	{
		$conf = new SQLSrvDBConf( $conf_name, App::APP_NAME );

		$conn = SAODBConn::getInstance( $conf );

		return $conn;
	}
}