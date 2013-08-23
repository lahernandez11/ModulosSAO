<?php
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Util.class.php';
require_once 'db/SAODBConn.class.php';
require_once 'models/AvanceObra.class.php';

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

		case 'eliminaTransaccion':

			$IDTransaccion = (int) $_GET['IDTransaccion'];

			$avanceObra = new AvanceObra( $IDTransaccion , $conn );

			$avanceObra->eliminaTransaccion();

			break;

		case 'getConceptosNuevoAvance':
			
			$IDConceptoRaiz = (int) $_GET['IDConceptoRaiz'];
			
			$data['conceptos'] = array();

			$conceptos = AvanceObra::getConceptosNuevoAvance( $IDObra, $IDConceptoRaiz, $conn );

			foreach ($conceptos as $concepto) {
				
				$data['conceptos'][] = array(
					'IDConcepto'  => $concepto->IDConcepto,
					'NumeroNivel' => $concepto->NumeroNivel,
					'Descripcion' => $concepto->Descripcion,
					'Unidad' 	  => $concepto->Unidad,
					'EsActividad' => $concepto->EsActividad,
					'CantidadPresupuestada'  => Util::formatoNumerico($concepto->CantidadPresupuestada),
					'CantidadAvanceAnterior' => Util::formatoNumerico($concepto->CantidadAvanceAnterior),
					'CantidadAvanceActual'   => Util::formatoNumerico($concepto->CantidadAvanceActual),
					'PrecioVenta' 		 	 => Util::formatoNumerico($concepto->PrecioVenta),
					'MontoAvance'			 => Util::formatoNumerico($concepto->MontoAvance),
					'CantidadAvance' 		 => Util::formatoNumerico($concepto->CantidadAvance),
					'MontoAvanceActual'   	 => Util::formatoNumerico($concepto->MontoAvanceActual),
					'Cumplido' 		 		 => $concepto->Cumplido
				);
			}

			break;

		case 'getFoliosTransaccion':

			$data['options'] = array();

			$folios = AvanceObra::getFoliosTransaccion( $IDObra, $conn );
			
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
			
			$transaccion = new AvanceObra( $IDTransaccion , $conn );

			$data['datos']['Observaciones'] = $transaccion->getObservaciones();
			$data['datos']['Fecha'] 		= Util::formatoFecha($transaccion->getFecha());
			$data['datos']['FechaInicio']   = Util::formatoFecha($transaccion->getFechaInicio());
			$data['datos']['FechaTermino']  = Util::formatoFecha($transaccion->getFechaTermino());
			$data['datos']['ConceptoRaiz']  = $transaccion->getConceptoRaiz();

			$conceptos = $transaccion->getConceptosAvance();

			foreach ($conceptos as $concepto) {
				
				$data['conceptos'][] = array(
					'IDConcepto'  => $concepto->IDConcepto,
					'NumeroNivel' => $concepto->NumeroNivel,
					'Descripcion' => $concepto->Descripcion,
					'Unidad' 	  => $concepto->Unidad,
					'EsActividad' => $concepto->EsActividad,
					'CantidadPresupuestada'  => Util::formatoNumerico($concepto->CantidadPresupuestada),
					'CantidadAvanceAnterior' => Util::formatoNumerico($concepto->CantidadAvanceAnterior),
					'CantidadAvance' 		 => Util::formatoNumerico($concepto->CantidadAvance),
					'PrecioVenta' 		 	 => Util::formatoNumerico($concepto->PrecioVenta),
					'MontoAvance'			 => Util::formatoNumerico($concepto->MontoAvance),
					'CantidadAvanceActual'   => Util::formatoNumerico($concepto->CantidadAvanceActual),
					'MontoAvanceActual'   	 => Util::formatoNumerico($concepto->MontoAvanceActual),
					'Cumplido' 		 		 => $concepto->Cumplido
				);
			}

			$totales = $transaccion->getTotalesTransaccion();

			foreach ($totales as $total) {
				
				$data['totales'] = array(
					'Subtotal'  => Util::formatoNumerico($total->Subtotal),
					'IVA' 		=> Util::formatoNumerico($total->IVA),
					'Total'   	=> Util::formatoNumerico($total->Total)					
				);
			}
			
			break;

		case 'guardaTransaccion':

			$IDTransaccion = (int) $_REQUEST['IDTransaccion'];
			$IDConceptoRaiz = (int) $_REQUEST['IDConceptoRaiz'];
			$fecha 		   = $_REQUEST['fecha'];
			$fechaInicio   = $_REQUEST['fechaInicio'];
			$fechaTermino  = $_REQUEST['fechaTermino'];
			$observaciones = $_REQUEST['observaciones'];
			$conceptos 	   = is_array($_REQUEST['conceptos']) ? $_REQUEST['conceptos'] : array();
			$data['errores'] = array();
			$data['totales']   = array();

			if ( ! empty($IDTransaccion) ) {

				$transaccion = new AvanceObra( $IDTransaccion , $conn );
				$transaccion->setFecha( $fecha );
				$transaccion->setFechaInicio( $fechaInicio );
				$transaccion->setFechaTermino( $fechaTermino );
				$transaccion->setObservaciones( $observaciones );
				$transaccion->setConceptos( $conceptos );

				$data['errores'] = $transaccion->guardaTransaccion();
			} else {
				
				$transaccion = new AvanceObra(
						$IDObra,
						$fecha,
						$fechaInicio,
						$fechaTermino,
						$observaciones,
						$IDConceptoRaiz,
						$conceptos,
						$conn
				);
			
				$data['errores'] = $transaccion->guardaTransaccion();
				$data['IDTransaccion']  = $transaccion->getIDTransaccion();
				$data['numeroFolio']    = Util::formatoNumeroFolio($transaccion->getNumeroFolio());
			}

			$totales = $transaccion->getTotalesTransaccion();

			foreach ($totales as $total) {
				
				$data['totales'] = array(
					'Subtotal'  => Util::formatoNumerico($total->Subtotal),
					'IVA' 		=> Util::formatoNumerico($total->IVA),
					'Total'   	=> Util::formatoNumerico($total->Total)					
				);
			}

			break;

		case 'apruebaTransaccion':

			$IDTransaccion = (int) $_REQUEST['IDTransaccion'];

			$avanceObra = new AvanceObra( $IDTransaccion , $conn );

			$avanceObra->apruebaTransaccion();
			
			break;

		case 'revierteAprobacion':

			$IDTransaccion = (int) $_REQUEST['IDTransaccion'];

			$avanceObra = new AvanceObra( $IDTransaccion , $conn );

			$avanceObra->revierteAprobacion();

			break;

		case 'getTotalesTransaccion':

			$IDTransaccion = (int) $_GET['IDTransaccion'];

			$data['totales'] = array();

			$avanceObra = new AvanceObra( $IDTransaccion , $conn );

			$totales = $avanceObra->getTotalesTransaccion();

			foreach ($totales as $total) {
				
				$data['totales'] = array(
					'Subtotal'  => Util::formatoNumerico($total->Subtotal),
					'IVA' 		=> Util::formatoNumerico($total->IVA),
					'Total'     => Util::formatoNumerico($total->Total)					
				);
			}
			break;

		case 'getListaTransacciones':

			$data['options'] = array();

			$listaTran = AvanceObra::getListaTransacciones( $IDObra , $conn );

			foreach ($listaTran as $tran) {
				
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