<?php
require_once 'setPath.php';
require_once 'db/ModulosSAOConn.class.php';
require_once 'models/ModulosSAO.class.php';

$data['success'] = true;
$data['message'] = null;

try {

	Sesion::validaSesionAsincrona();
	
	if ( ! isset($_GET['action']) ) {
		throw new Exception("No fue definida una acción");
	}

	$MSAOConn = new ModulosSAOConn();

	switch ( $_GET['action'] ) {

		case 'getListaProyectos':

			$data['options'] = array();
			
			$proyectos = ModulosSAO::getListaProyectos( $MSAOConn );

			if ( ! count($proyectos) ) {
				throw new Exception("No se encontraron proyectos asignados.");
			}

			$data['options'] = $proyectos;
	}

	unset($MSAOConn);
} catch( Exception $e ) {

	unset($MSAOConn);
	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>