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

	switch ( $_REQUEST['action'] ) {

		case 'eliminaTransaccion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];

			$avanceObra = new AvanceObra( $obra, $id_transaccion );

			$avanceObra->eliminaTransaccion();

			break;

		case 'getConceptosNuevoAvance':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, $_GET['id_obra'] );
			$id_concepto_raiz = (int) $_GET['id_concepto_raiz'];
			
			$data['conceptos'] = array();

			$conceptos = AvanceObra::getConceptosNuevoAvance( $obra, $id_concepto_raiz );

			foreach ( $conceptos as $concepto ) {
				
				$data['conceptos'][] = array(
					'IDConcepto'  => $concepto->IDConcepto,
					'NumeroNivel' => $concepto->NumeroNivel,
					'Descripcion' => $concepto->Descripcion,
					'Unidad' 	  => $concepto->Unidad,
					'EsActividad' => $concepto->EsActividad,
					'Cumplido' 	  => $concepto->Cumplido,
					'CantidadPresupuestada'  => Util::formatoNumerico( $concepto->CantidadPresupuestada ),
					'CantidadAvanceAnterior' => Util::formatoNumerico( $concepto->CantidadAvanceAnterior ),
					'CantidadAvanceActual'   => Util::formatoNumerico( $concepto->CantidadAvanceActual ),
					'PrecioVenta' 		 	 => Util::formatoNumerico( $concepto->PrecioVenta ),
					'MontoAvance'			 => Util::formatoNumerico( $concepto->MontoAvance ),
					'CantidadAvance' 		 => Util::formatoNumerico( $concepto->CantidadAvance ),
					'MontoAvanceActual'   	 => Util::formatoNumerico( $concepto->MontoAvanceActual )

				);
			}

			break;

		case 'getFoliosTransaccion':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, $_GET['id_obra'] );
			
			$data['options'] = array();

			$folios = AvanceObra::getFoliosTransaccion( $obra );
			
			foreach ( $folios as $folio ) {
				
				$data['options'][] = array(
					'IDTransaccion' => $folio->IDTransaccion,
					'NumeroFolio'   => Util::formatoNumeroFolio( $folio->NumeroFolio )
				);
			}
			break;

		case 'getDatosTransaccion':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, $_GET['id_obra'] );
			$id_transaccion = (int) $_GET['id_transaccion'];

			$data['datos'] 	   = array();
			$data['conceptos'] = array();
			$data['totales']   = array();
			
			$transaccion = new AvanceObra( $obra, $id_transaccion);

			$data['datos']['ConceptoRaiz']  = $transaccion->getConceptoRaiz();
			$data['datos']['Observaciones'] = $transaccion->getObservaciones();
			$data['datos']['Fecha'] 		= Util::formatoFecha( $transaccion->getFecha() );
			$data['datos']['FechaInicio']   = Util::formatoFecha( $transaccion->getFechaInicio() );
			$data['datos']['FechaTermino']  = Util::formatoFecha( $transaccion->getFechaTermino() );

			$conceptos = $transaccion->getConceptosAvance();

			foreach ( $conceptos as $concepto ) {
				
				$data['conceptos'][] = array(
					'IDConcepto'  => $concepto->IDConcepto,
					'NumeroNivel' => $concepto->NumeroNivel,
					'Descripcion' => $concepto->Descripcion,
					'Unidad' 	  => $concepto->Unidad,
					'EsActividad' => $concepto->EsActividad,
					'Cumplido' 	  => $concepto->Cumplido,
					'CantidadPresupuestada'  => Util::formatoNumerico( $concepto->CantidadPresupuestada ),
					'CantidadAvanceAnterior' => Util::formatoNumerico( $concepto->CantidadAvanceAnterior ),
					'CantidadAvance' 		 => Util::formatoNumerico( $concepto->CantidadAvance ),
					'PrecioVenta' 		 	 => Util::formatoNumerico( $concepto->PrecioVenta ),
					'MontoAvance'			 => Util::formatoNumerico( $concepto->MontoAvance ),
					'CantidadAvanceActual'   => Util::formatoNumerico( $concepto->CantidadAvanceActual ),
					'MontoAvanceActual'   	 => Util::formatoNumerico( $concepto->MontoAvanceActual )
				);
			}

			$totales = $transaccion->getTotalesTransaccion();

			foreach ( $totales as $total ) {
				
				$data['totales'] = array(
					'subtotal' => Util::formatoNumerico( $total->Subtotal ),
					'iva' 	   => Util::formatoNumerico( $total->IVA ),
					'total'    => Util::formatoNumerico( $total->Total )					
				);
			}
			break;

		case 'guardaTransaccion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, $_POST['id_obra'] );
			
			$id_concepto_raiz = (int) $_POST['id_concepto_raiz'];
			$fecha 		   = $_POST['fecha'];
			$fechaInicio   = $_POST['fechaInicio'];
			$fechaTermino  = $_POST['fechaTermino'];
			$observaciones = $_POST['observaciones'];
			$conceptos 	   = isset( $_POST['conceptos'] ) ? $_POST['conceptos'] : array();

			$data['errores'] = array();
			$data['totales']   = array();

			if ( isset( $_POST['id_transaccion'] ) ) {

				$transaccion = new AvanceObra( $obra, (int) $_POST['id_transaccion'] );
				$transaccion->setFecha( $fecha );
				$transaccion->setFechaInicio( $fechaInicio );
				$transaccion->setFechaTermino( $fechaTermino );
				$transaccion->setObservaciones( $observaciones );
				$transaccion->setConceptos( $conceptos );

				$data['errores'] = $transaccion->guardaTransaccion( Sesion::getUser() );
			} else {
				
				$transaccion = new AvanceObra(
						$obra,
						$fecha,
						$fechaInicio,
						$fechaTermino,
						$observaciones,
						$id_concepto_raiz,
						$conceptos
				);
			
				$data['errores'] 	   = $transaccion->guardaTransaccion( Sesion::getUser() );
				$data['IDTransaccion'] = $transaccion->getIDTransaccion();
				$data['numeroFolio']   = Util::formatoNumeroFolio($transaccion->getNumeroFolio());
			}

			$totales = $transaccion->getTotalesTransaccion();

			foreach ( $totales as $total ) {
				
				$data['totales'] = array(
					'subtotal' => Util::formatoNumerico( $total->Subtotal ),
					'iva' 	   => Util::formatoNumerico( $total->IVA ),
					'total'    => Util::formatoNumerico( $total->Total )					
				);
			}

			break;

		case 'apruebaTransaccion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];

			$transaccion = new AvanceObra( $obra, $id_transaccion );
			$transaccion->apruebaTransaccion( Sesion::getUser() );
			break;

		case 'revierteAprobacion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];

			$transaccion = new AvanceObra( $obra, $id_transaccion );
			$transaccion->revierteAprobacion( Sesion::getUser() );
			break;

		case 'getTotalesTransaccion':

			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, $_GET['id_obra'] );
			$id_transaccion = (int) $_GET['id_transaccion'];

			$data['totales'] = array();

			$avanceObra = new AvanceObra( $obra, $id_transaccion);
			$totales = $avanceObra->getTotalesTransaccion();

			foreach ( $totales as $total ) {
				
				$data['totales'] = array(
					'Subtotal' => Util::formatoNumerico( $total->Subtotal ),
					'IVA' 	   => Util::formatoNumerico( $total->IVA ),
					'Total'    => Util::formatoNumerico( $total->Total )					
				);
			}
			break;

		case 'getListaTransacciones':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, $_GET['id_obra'] );
			
			$data['options'] = array();

			$listaTran = AvanceObra::getListaTransacciones( $obra );

			foreach ( $listaTran as $tran ) {
				
				$data['options'][] = array(
					'IDTransaccion'  => $tran->IDTransaccion,
					'NumeroFolio' 	 => Util::formatoNumeroFolio( $tran->NumeroFolio ),
					'Fecha'     	 => Util::formatoFecha( $tran->Fecha ),
					'Observaciones'  => $tran->Observaciones
				);
			}
			break;
	}

} catch( Exception $e ) {
	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>