<?php
require_once 'setPath.php';
require_once 'models/App.class.php';

$data['success'] = true;
$data['message'] = null;

try {

	Sesion::validaSesionAsincrona();

	switch ( $_GET['action'] ) {

		case 'getListaProyectos':

			$usuario = Sesion::getUser();
			
			$data['options'] = array();

			foreach( $usuario->getObras() as $obra ) {

				$data['options'][] = array(
					'id' 	    => $obra->getId(),
					'nombre' 	=> $obra->getNombre(),
					'source_id' => $obra->getSourcename()
				);
			}

			// if ( ! count( $data['options'] ) ) {
			// 	throw new Exception("No se encontraron obras asignadas.");
			// }
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