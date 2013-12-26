<?php 
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'db/SAO1814DBConn.class.php';
require_once 'models/AgrupadorInsumo.class.php';
require_once 'models/Util.class.php';

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

		case 'getAgrupadoresNaturaleza':
		case 'getAgrupadoresFamilia':
		case 'getAgrupadoresGenerico':

			$descripcion = $_GET['term'];
			$data['options'] = array();
			$tipo = null;

			switch ($_GET['action']) {
				case 'getAgrupadoresNaturaleza':
					$tipo = AgrupadorInsumo::TIPO_NATURALEZA;
					break;
				
				case 'getAgrupadoresFamilia':
					$tipo = AgrupadorInsumo::TIPO_FAMILIA;
					break;

				case 'getAgrupadoresGenerico':
					$tipo = AgrupadorInsumo::TIPO_GENERICO;
					break;
			}

			$agrupadores = AgrupadorInsumo::getAgrupadoresInsumo(
				$conn, $descripcion, $tipo);

			foreach ($agrupadores as $agrupador) {
				$data['options'][] = array(
					'id' => $agrupador->id_agrupador,
					'label' => $agrupador->agrupador,
				);
			}
			break;

		default:
			throw new Exception("Acción desconocida");
	}

} catch( Exception $e ) {

	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>