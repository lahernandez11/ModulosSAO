<?php
require_once 'Sesion.class.php';
require_once 'Usuario.class.php';
require_once 'db/ModulosSAOConn.class.php';

abstract class App {
	
	const APP_ID = 1;
	const APP_NAME = 'ModulosSAO';
	const LOGIN_PAGE = 'login.html';

	public static function logueaUsuario( Usuario $usuario, $pwd ) {
		
		$conn = ModulosSAOConn::getInstance();

		$tsql = "{call [Seguridad].[uspValidaUsuario2]( ?, ?, ? )}";

		$params = array(
			  array( $usuario->getUsername(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('50') )
			, array( $pwd, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('20') )
			, array( self::APP_ID, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		);

		$conn->executeSP( $tsql, $params );

		Sesion::createSesion( $usuario, self::APP_NAME );

	}

	public static function finalizaSesion() {
		Sesion::terminaSesion();
	}

	public static function getMenu() {
		
		$tsql = "{call [Seguridad].[uspMenuUsuario]( ?, ? )}";

		$usuario = Sesion::getUser();

		$conn = ModulosSAOConn::getInstance();

		$params = array( self::APP_ID, $usuario->getId() );

		$menu = $conn->executeSP( $tsql, $params );

		return $menu;
	}

	public static function getListaProyectos() {

		$tsql = "{call [Seguridad].[uspProyectosUsuario]( ?, ? )}";

		$params = array( Sesion::getUser()->getUsername(), self::APP_ID );

		$conn = ModulosSAOConn::getInstance();

		$proyectos = $conn->executeSP( $tsql, $params );

		return $proyectos;
	}
}
?>