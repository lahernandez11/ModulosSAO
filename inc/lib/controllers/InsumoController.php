<?php
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Material.class.php';
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
	
	switch ( $_REQUEST['action'] ) {

		case 'listaInsumos':

			$data['insumos'] = array();

			$tipo_material = (int) $_REQUEST['IDTipoInsumo'];
			$descripcion  = $_REQUEST['term'];

			$data['insumos'] = Material::getMateriales( $conn, $descripcion, $tipo_material );

			break;
	}

} catch( Exception $e ) {

	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>