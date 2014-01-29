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
	
	switch ( $_REQUEST['action'] ) {

		case 'listaInsumos':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$tipo_material = (int) $_GET['IDTipoInsumo'];
			$descripcion  = $_GET['term'];
			
			$data['insumos'] = array();

			$data['insumos'] = Material::getMateriales( $conn, $descripcion, $tipo_material );

			break;
	}

} catch( Exception $e ) {
	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>