<?php
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Insumo.class.php';
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

			$IDTipoInsumo = (int) $_REQUEST['IDTipoInsumo'];
			$descripcion  = $_REQUEST['term'];

			switch( $IDTipoInsumo ) {

				case 1:
					$data['insumos'] = Insumo::getInsumosMateriales( $descripcion, $conn );
					break;
				case 2:
					$data['insumos'] = Insumo::getInsumosManoObra( $descripcion, $conn );
					break;
				case 3:
					$data['insumos'] = Insumo::getInsumosServicios( $descripcion, $conn );
					break;
				case 4:
					$data['insumos'] = Insumo::getInsumosHerramienta( $descripcion, $conn );
					break;
				case 8:
					$data['insumos'] = Insumo::getInsumosMaquinaria( $descripcion, $conn );
					break;
			}

			break;
	}

} catch( Exception $e ) {

	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>