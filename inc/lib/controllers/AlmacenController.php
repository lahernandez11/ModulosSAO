<?php
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Almacen.class.php';
require_once 'db/SAODBConn.class.php';

$data['success'] = true;
$data['message'] = null;
$data['noRows']  = false;

try {

	Sesion::validaSesionAsincrona();

	if ( ! isset($_REQUEST['action']) ) {
		throw new Exception("No fue definida una acción");
	}

	$conn = new SAO1814DBConn();
	$IDProyecto = (int) $_REQUEST['IDProyecto'];
	$IDObra 	= Obra::getIDObraProyecto($IDProyecto);
	
	switch ( $_REQUEST['action'] ) {

		case 'listaAlmacenes':

			$data['almacenes'] = array();

			$IDTipoAlmacen = (int) $_REQUEST['IDTipoAlmacen'];
			$descripcion  = $_REQUEST['term'];

			$data['almacenes'] = Almacen::getAlmacenes( $IDObra, $IDTipoAlmacen, $descripcion, $conn );

			break;
	}

} catch( Exception $e ) {

	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>