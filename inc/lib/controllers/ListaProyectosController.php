<?php
require_once 'setPath.php';
require_once 'models/App.class.php';

$data['success'] = true;
$data['message'] = null;

try {

	Sesion::validaSesionAsincrona();
	
	if ( ! isset($_GET['action']) ) {
		throw new Exception("No fue definida una acción");
	}

	switch ( $_GET['action'] ) {

		case 'getListaProyectos':

			$data['options'] = array();
			
			$proyectos = App::getListaProyectos();

			if ( ! count( $proyectos ) ) {
				throw new Exception("No se encontraron proyectos asignados.");
			}

			$data['options'] = $proyectos;
	}
} catch( Exception $e ) {
	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>