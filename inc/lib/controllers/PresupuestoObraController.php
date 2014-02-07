<?php 
require_once 'setPath.php';
require_once 'db/SAODBConnFactory.class.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/PresupuestoObra.class.php';
require_once 'models/AgrupadorConceptoPresupuesto.class.php';
require_once 'models/Util.class.php';

$data['success'] = true;
$data['message'] = null;
$data['noRows']  = false;

try {

	Sesion::validaSesionAsincrona();
	
	switch ( $_REQUEST['action'] ) {

		case 'getConceptos':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, $_GET['id_obra'] );
			$id_concepto = isset( $_GET['id_concepto'] ) ? (int) $_GET['id_concepto'] : null;

			$presupuesto = new PresupuestoObra( $obra );
			$conceptos = $presupuesto->getConceptos( $id_concepto );

			$data['conceptos'] = array();

			foreach ( $conceptos as $concepto ) {
				$data['conceptos'][] = formatDatosConcepto( $concepto );
			}
			break;

		case 'getDatosConcepto':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, $_GET['id_obra'] );
			
			$id_concepto = (int) $_GET['id_concepto'];
			$presupuesto = new PresupuestoObra( $obra );

			$data['concepto'] = array();
			$data['concepto'] = $presupuesto->getDatosConcepto( $id_concepto );
			break;

		case 'getAgrupadoresPartida':
		case 'getAgrupadoresSubpartida':
		case 'getAgrupadoresActividad':
		case 'getAgrupadoresTramo':
		case 'getAgrupadoresSubtramo':

			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, $_GET['id_obra'] );
			
			$descripcion  = $_GET['term'];
			$data['agrupadores'] = array();
			$data['agrupadores'] = AgrupadorConceptoPresupuesto::$_GET['action']( $obra, $descripcion );

			break;

		case 'setAgrupadorPartida':
		case 'setAgrupadorSubpartida':
		case 'setAgrupadorActividad':
		case 'setAgrupadorTramo':
		case 'setAgrupadorSubtramo':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, $_POST['id_obra'] );
			
			$presupuesto = new PresupuestoObra( $obra );
			$conceptos = isset( $_POST['conceptos'] ) ? $_POST['conceptos'] : array() ;
			$id_agrupador = $_POST['id_agrupador'];

			foreach ( $conceptos as $concepto ) {
				$presupuesto->setAgrupador( $concepto['id_concepto'], $id_agrupador, $_POST['action'] );
			}

			break;

		case 'setClaveConcepto':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, $_POST['id_obra'] );
			$clave       = $_POST['clave'];
			$id_concepto    = $_POST['id_concepto'];
			
			$presupuesto = new PresupuestoObra( $obra );
			$presupuesto->setClaveConcepto( $id_concepto, $clave );
			break;

		case 'addAgrupadorPartida':
		case 'addAgrupadorSubpartida':
		case 'addAgrupadorActividad':
		case 'addAgrupadorTramo':
		case 'addAgrupadorSubtramo':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, $_POST['id_obra'] );
			
			$clave 		 = $_POST['clave'];
			$descripcion = $_POST['descripcion'];

			$data['id_agrupador'] = AgrupadorConceptoPresupuesto::$_POST['action']( $obra, $clave, $descripcion );
			break;

		default:
			throw new Exception("Accion desconocida");
	}

} catch( Exception $e ) {
	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode( $data );

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
?>