<?php
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'db/SAODBConnFactory.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Almacen.class.php';

$data['success'] = true;
$data['message'] = null;
$data['noRows']  = false;

try {

	Sesion::validaSesionAsincrona();

	if ( ! isset($_REQUEST['action']) ) {
		throw new Exception("No fue definida una acción");
	}
	
	switch ( $_REQUEST['action'] ) {

		case 'getListaAlmacenes':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );

			$data['almacenes'] = array();

			$tipo_almacen = (int) $_GET['tipo_almacen'];
			$descripcion  = $_GET['term'];

			$data['almacenes'] = Almacen::getAlmacenes( $obra, $tipo_almacen, $descripcion );

			break;
	}

} catch( Exception $e ) {
	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>