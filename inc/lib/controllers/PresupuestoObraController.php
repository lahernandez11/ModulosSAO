<?php 
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Util.class.php';
require_once 'models/PresupuestoObra.class.php';

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

		case 'getConceptos':
			$id_concepto = (int) $_GET['id_concepto'];
			$presupuesto = new PresupuestoObra($IDObra, $conn);
			$data['conceptos'] = array();
			$conceptos = $presupuesto->getConceptos($id_concepto);

			foreach ($conceptos as $concepto) {

				$data['conceptos'][] = formatDatosConcepto($concepto);
			}

			break;

		case 'getDatosConcepto':
			$id_concepto = (int) $_GET['id_concepto'];
			$presupuesto = new PresupuestoObra($IDObra, $conn);
			$data['concepto'] = array();
			$data['concepto'] = formatDatosConcepto($presupuesto->getDatosConcepto($id_concepto));

			break;

		case 'getAgrupadoresPartida':
		case 'getAgrupadoresSubpartida':
		case 'getAgrupadoresActividad':

			$descripcion  = $_GET['term'];
			$data['agrupadores'] = array();
			$data['agrupadores'] = PresupuestoObra::$_GET['action']($conn, $IDObra, $descripcion);

			break;

		case 'setAgrupadorPartida':
		case 'setAgrupadorSubpartida':
		case 'setAgrupadorActividad':
			$presupuesto = new PresupuestoObra($IDObra, $conn);
			$conceptos = $_POST['conceptos'];
			$id_agrupador = $_POST['id_agrupador'];

			foreach ($conceptos as $concepto) {
				$presupuesto->{$_POST['action']}($concepto['id_concepto'], $id_agrupador);
				$data['x'] = $concepto['id_concepto'];
			}

			break;

		default:
			throw new Exception("Accion desconocida");
	}

} catch( Exception $e ) {

	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

function formatDatosConcepto( $concepto ) {
	return array(
		'id_concepto' 			  => $concepto->id_concepto,
		'id_concepto_padre' 	  => $concepto->id_concepto_padre,
		'tipo_material' 		  => $concepto->tipo_material,
		'id_material' 			  => $concepto->id_material,
		'nivel' 				  => $concepto->nivel,
		'numero_nivel' 			  => $concepto->numero_nivel,
		'clave_concepto' 		  => $concepto->clave_concepto,
		'descripcion' 			  => $concepto->descripcion,
		'unidad' 				  => $concepto->unidad,
		'cantidad_presupuestada'  => Util::formatoNumerico($concepto->cantidad_presupuestada),
		'monto_presupuestado' 	  => Util::formatoNumerico($concepto->monto_presupuestado),
		'precio_unitario' 		  => Util::formatoNumerico($concepto->precio_unitario),
		'concepto_medible' 		  => $concepto->concepto_medible,
		'estado' 				  => $concepto->estado,
		'subnivel' 				  => $concepto->subnivel,
		'id_agrupador_partida' 	  => $concepto->id_agrupador_partida,
		'agrupador_partida' 	  => $concepto->agrupador_partida,
		'id_agrupador_subpartida' => $concepto->id_agrupador_subpartida,
		'agrupador_subpartida' 	  => $concepto->agrupador_subpartida,
		'id_agrupador_actividad'  => $concepto->id_agrupador_actividad,
		'agrupador_actividad' 	  => $concepto->agrupador_actividad
	);
}

echo json_encode($data);
?>