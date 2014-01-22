<?php
abstract class Sesion {

/*	
	private $_usuario = null;
	private $_aplicacion = null;

	public function __construct( Usuario $usuario, Aplicacion $aplicacion ) {

		$this->_usuario = $usuario;
		$this->_aplicacion = $aplicacion;
	}
*/
	public static function startSesion() {
		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}
	}

	public static function createSesion( Usuario $usuario, $appName ) {
		
		self::startSesion();

		$_SESSION['user'] = $usuario;
	}

	public static function getAplicacionSesion() {
		
		self::startSesion();

		return $_SESSION['AppName'];
	}

	public static function validaSesion() {

		self::startSesion();

		if ( ! isset( $_SESSION['user'] ) ) {
			header( 'Location:' . APP::LOGIN_PAGE );
		}
	}

	public static function validaSesionAsincrona() {

		self::startSesion();

		if ( ! isset($_SESSION['user']) ) {

			throw new SesionNoIniciadaException();
		}
	}

	public static function terminaSesion() {

		self::startSesion();

		session_destroy();
	}

	public static function getUser() {
		self::startSesion();

		if ( ! isset( $_SESSION['user'] ) ) {

			throw new SesionNoIniciadaException();
		} else {
			return $_SESSION['user'];
		}
	}
}

class SesionNoIniciadaException extends Exception {

	private $_message = "La sesion no fue iniciada o ha caducado.";

	public function __construct() {
		parent::__construct( $this->_message, 1);
	}
}
?>