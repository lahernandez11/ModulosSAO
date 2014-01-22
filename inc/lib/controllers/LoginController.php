<?php
require_once 'setPath.php';
require_once 'models/App.class.php';
require_once 'models/Usuario.class.php';
require_once 'db/ModulosSAOConn.class.php';

$data['success'] = true;
$data['message'] = null;

try {

	switch ( $_GET['action'] ) {

		case 'logueaUsuario':

			$username = $_GET['usr'];
			$pwd 	  = $_GET['pwd'];

			$usuario = new Usuario( $username );

			App::logueaUsuario( $usuario, $pwd );

			break;

		case 'terminaSesion':

			App::finalizaSesion();
			break;

		default:
			throw new Exception("No fue definida una acción");
	}
} catch( Exception $e ) {

	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>