<?php
require_once 'setPath.php';
require_once 'db/ModulosSAOConn.class.php';
require_once 'models/ModulosSAO.class.php';

$data['success'] = true;
$data['message'] = null;

try {

	if ( ! isset($_GET['action']) ) {
		throw new Exception("No fue definida una acción");
	}

	switch ( $_GET['action'] ) {

		case 'logueaUsuario':

			$MSAOConn = new ModulosSAOConn();

			$cuentaUsuario = $_GET['usr'];
			$password = $_GET['pwd'];

			ModulosSAO::logueaUsuario( $cuentaUsuario, $password, $MSAOConn );

			$data['sess'] = array(
				  'uid'   => Sesion::getIDUsuarioSesion()
				, 'uName' => Sesion::getNombreUsuarioSesion()
			);

			break;

		case 'terminaSesion':

			ModulosSAO::finalizaSesion();
			break;
	}
} catch( Exception $e ) {

	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>