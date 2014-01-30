<?php
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Util.class.php';
require_once 'db/SAODBConn.class.php';
require_once 'models/EstimacionObra.class.php';

$data['success'] = true;
$data['message'] = null;
$data['noRows']  = false;

try {

	Sesion::validaSesionAsincrona();

	if ( ! isset($_REQUEST['action']) ) {
		throw new Exception("No fue definida una acción");
	}

	// $conn = new SAO1814DBConn();

	// $IDProyecto = (int) $_REQUEST['IDProyecto'];
	// $IDObra 	= Obra::getIDObraProyecto($IDProyecto);
	
	switch ( $_REQUEST['action'] ) {

		case 'nuevaTransaccion':
			
			$data['conceptos'] = array();

			$conceptos = EstimacionObra::getConceptosNuevaEstimacion( $IDObra, $conn );

			foreach ($conceptos as $concepto) {
				
				$data['conceptos'][] = array(
					'IDConcepto'  => $concepto->IDConcepto,
					'NumeroNivel' => $concepto->NumeroNivel,
					'Descripcion' => $concepto->Descripcion,
					'EsActividad' => $concepto->EsActividad,
					'Unidad' 	  => $concepto->Unidad,
					'CantidadPresupuestada'    => Util::formatoNumerico($concepto->CantidadPresupuestada),
					'CantidadEstimadaAnterior' => Util::formatoNumerico($concepto->CantidadEstimadaAnterior),
					'CantidadEstimada' 		   => Util::formatoNumerico($concepto->CantidadEstimada),
					'PrecioVenta' 		 	   => Util::formatoNumerico($concepto->PrecioVenta),
					'Total' 		 		   => Util::formatoNumerico($concepto->Total)
				);
			}

			break;

		case 'getFoliosTransaccion':

			$data['options'] = array();

			$folios = EstimacionObra::getFoliosTransaccion( $IDObra, $conn );
			
			foreach ($folios as $folio) {
				
				$data['options'][] = array(
					'IDTransaccion' => $folio->IDTransaccion,
					'NumeroFolio' => Util::formatoNumeroFolio($folio->NumeroFolio)
				);
			}
			
			break;

		case 'getDatosTransaccion':

			$IDTransaccion = (int) $_GET['IDTransaccion'];

			$data['datos'] 	   = array();
			$data['conceptos'] = array();
			$data['totales']   = array();
			
			$transaccion = new EstimacionObra( $IDTransaccion , $conn );

			$data['datos']['Fecha'] 		= Util::formatoFecha($transaccion->getFecha());
			$data['datos']['FechaInicio']   = Util::formatoFecha($transaccion->getFechaInicio());
			$data['datos']['FechaTermino']  = Util::formatoFecha($transaccion->getFechaTermino());
			$data['datos']['Observaciones'] = $transaccion->getObservaciones();
			$data['datos']['Referencia']    = $transaccion->getReferencia();

			$conceptos = $transaccion->getConceptos();

			foreach ($conceptos as $concepto) {
				
				$data['conceptos'][] = array(
					'IDConcepto'  => $concepto->IDConcepto,
					'NumeroNivel' => $concepto->NumeroNivel,
					'Descripcion' => $concepto->Descripcion,
					'EsActividad' => $concepto->EsActividad,
					'Unidad' 	  => $concepto->Unidad,
					'CantidadPresupuestada'    => Util::formatoNumerico($concepto->CantidadPresupuestada),
					'CantidadEstimadaAnterior' => Util::formatoNumerico($concepto->CantidadEstimadaAnterior),
					'CantidadEstimada' 		   => Util::formatoNumerico($concepto->CantidadEstimada),
					'PrecioVenta' 		 	   => Util::formatoNumerico($concepto->PrecioVenta),
					'Total' 		 		   => Util::formatoNumerico($concepto->Total),
					'Cumplido' 		 		   => $concepto->Cumplido
				);
			}

			$totales = $transaccion->getTotalesTransaccion();

			foreach ($totales as $total) {

				$data['totales'][] = array(
					'Subtotal'  => Util::formatoNumerico($total->Subtotal),
					'IVA' 		=> Util::formatoNumerico($total->IVA),
					'Total'   	=> Util::formatoNumerico($total->Total)
				);
			}
			break;

		case 'guardaTransaccion':

			$IDTransaccion = (int) $_POST['IDTransaccion'];

			$fecha 		   = $_POST['datosGenerales']['fecha'];
			$fechaInicio   = $_POST['datosGenerales']['fechaInicio'];
			$fechaTermino  = $_POST['datosGenerales']['fechaTermino'];
			$referencia    = $_POST['datosGenerales']['referencia'];
			$observaciones = $_POST['datosGenerales']['observaciones'];
			$conceptos     = is_array($_POST['conceptos']) ? $_POST['conceptos'] : array();

			$data['errores'] = array();
			$data['totales'] = array();

			if ( ! empty($IDTransaccion) ) {

				$transaccion = new EstimacionObra( $IDTransaccion , $conn );

				$transaccion->setFecha( $fecha );
				$transaccion->setFechaInicio( $fechaInicio );
				$transaccion->setFechaTermino( $fechaTermino );
				$transaccion->setReferencia( $referencia );
				$transaccion->setObservaciones( $observaciones );
				$transaccion->setConceptos( $conceptos );
				
				$data['errores'] = $transaccion->guardaTransaccion();
			} else {
				
				$transaccion = new EstimacionObra(
					$IDObra, $fecha, $fechaInicio, $fechaTermino,
					$observaciones, $referencia, $conceptos, $conn
				);
				
				$data['errores']		= $transaccion->guardaTransaccion();
				$data['IDTransaccion']  = $transaccion->getIDTransaccion();
				$data['NumeroFolio']    = Util::formatoNumeroFolio($transaccion->getNumeroFolio());
			}

			$totales = $transaccion->getTotalesTransaccion();

			foreach ($totales as $total) {

				$data['totales'][] = array(
					'Subtotal'  => Util::formatoNumerico($total->Subtotal),
					'IVA' 		=> Util::formatoNumerico($total->IVA),
					'Total'   	=> Util::formatoNumerico($total->Total)
				);
			}

			break;

		case 'eliminaTransaccion':

			$IDTransaccion = (int) $_GET['IDTransaccion'];

			$transaccion = new EstimacionObra( $IDTransaccion , $conn );

			$transaccion->eliminaTransaccion();

			break;
			
		case 'apruebaTransaccion':

			$IDTransaccion = (int) $_REQUEST['IDTransaccion'];

			$transaccion = new EstimacionObra( $IDTransaccion , $conn );

			$transaccion->apruebaTransaccion();
			
			break;

		case 'revierteAprobacion':

			$IDTransaccion = (int) $_REQUEST['IDTransaccion'];

			$transaccion = new EstimacionObra( $IDTransaccion , $conn );

			$transaccion->revierteAprobacion();

			break;

		case 'getTotalesTransaccion':

			$IDTransaccion = (int) $_GET['IDTransaccion'];

			$data['totales'] = array();

			$transaccion = new EstimacionObra( $IDTransaccion , $conn );

			$totales = $transaccion->getTotalesTransaccion();

			foreach ($totales as $total) {
				
				$data['totales'][] = array(
					'Subtotal'  => Util::formatoNumerico($total->Subtotal),
					'IVA' 		=> Util::formatoNumerico($total->IVA),
					'Total'     => Util::formatoNumerico($total->Total)					
				);
			}
			break;

		case 'getListaTransacciones':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );

			$data['options'] = array();

			$listaTran = EstimacionObra::getListaTransacciones( $obra );

			foreach ( $listaTran as $tran ) {
				
				$data['options'][] = array(
					'IDTransaccion'  => $tran->IDTransaccion,
					'NumeroFolio' 	 => Util::formatoNumeroFolio($tran->NumeroFolio),
					'Fecha'     	 => Util::formatoFecha($tran->Fecha),
					'Observaciones'  => $tran->Observaciones
				);
			}
			break;
	}

	unset($conn);
} catch( Exception $e ) {

	unset($conn);
	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>