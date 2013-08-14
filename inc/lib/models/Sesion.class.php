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
		session_start();
	}

	public static function createSesion( $IDUsuario, $nombreCuenta, $nombreUsuario, $appName ) {
		
		self::startSesion();

		$_SESSION['uid'] 	 	   = $IDUsuario;
		$_SESSION['nombreUsuario'] = $nombreUsuario;
		$_SESSION['cuentaUsuario'] = $nombreCuenta;
		$_SESSION['AppName'] 	   = $appName;
	}

	public static function getIDUsuarioSesion() {

		self::startSesion();

		return $_SESSION['uid'];		
	}

	public static function getNombreUsuarioSesion() {

		self::startSesion();

		return $_SESSION['nombreUsuario'];		
	}

	public static function getCuentaUsuarioSesion() {

		self::startSesion();

		return $_SESSION['cuentaUsuario'];		
	}

	public static function getAplicacionSesion() {
		
		self::startSesion();

		return $_SESSION['AppName'];
	}

	public static function validaSesion() {

		self::startSesion();

		if ( ! isset($_SESSION['uid']) ) {

			header('Location:login.html');
		}
	}

	public static function validaSesionAsincrona() {

		self::startSesion();

		if ( ! isset($_SESSION['uid']) ) {

			throw new SesionNoIniciadaException();
		}
	}

	public static function terminaSesion() {

		self::startSesion();

		session_destroy();
	}
}

class SesionNoIniciadaException extends Exception {

	private $_message = "La sesion no fue iniciada o ha caducado.";

	public function __construct() {
		parent::__construct( $this->_message, 1);
	}
}
?>