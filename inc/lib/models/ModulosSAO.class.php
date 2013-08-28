<?php
require_once 'Aplicacion.class.php';
require_once 'Sesion.class.php';

abstract class ModulosSAO extends Aplicacion {
	
	const ID_APLICACION = 1;
	const APP_NAME = "ModulosSAO";

	public static function logueaUsuario( $cuentaUsuario, $password, ModulosSAOConn $conn ) {

		$tsql = "{call [Seguridad].[uspValidaUsuario2]( ?, ?, ?, ?, ? )}";

		$IDUsuario = null;
		$nombreUsuario = null;

		$params = array(
			  array( $cuentaUsuario, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('50') )
			, array( $password, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('20') )
			, array( self::ID_APLICACION, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
			, array( &$IDUsuario, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_INT )
			, array( &$nombreUsuario, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_VARCHAR('100') )
		);

		$conn->executeSP( $tsql, $params );

		Sesion::createSesion( $IDUsuario, $cuentaUsuario, $nombreUsuario, self::APP_NAME );
	}

	public static function getListaProyectos( ModulosSAOConn $conn ) {

		$tsql = "{call [Seguridad].[uspProyectosUsuario]( ?, ? )}";

		$params = array( Sesion::getCuentaUsuarioSesion(), self::ID_APLICACION );

		$proyectos = $conn->executeSP( $tsql, $params );

		return $proyectos;
	}

	public static function getMenu( ModulosSAOConn $conn ) {
		
		$tsql = "{call [Seguridad].[uspMenuUsuario]( ?, ? )}";

		$params = array( self::ID_APLICACION, Sesion::getIDUsuarioSesion());

		$menu = $conn->executeSP( $tsql, $params );

		return $menu;
	}

	public static function finalizaSesion() {
		Sesion::terminaSesion();
	}
}
?>