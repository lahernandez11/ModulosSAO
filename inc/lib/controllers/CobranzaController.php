<?php
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Util.class.php';
require_once 'db/SAODBConn.class.php';
require_once 'models/Cobranza.class.php';

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

		case 'nuevaTransaccion':

			$data['conceptos'] = array();
			$IDEstimacionObra = (int) $_REQUEST['IDEstimacionObra'];

			$conceptos = Cobranza::getConceptosNuevaTransaccion( $IDObra, $IDEstimacionObra, $conn );

			foreach ($conceptos as $concepto) {
				
				$data['conceptos'][] = array(
					'IDConcepto'  => $concepto->IDConcepto,
					'NumeroNivel' => $concepto->NumeroNivel,
					'Descripcion' => $concepto->Descripcion,
					'EsActividad' => $concepto->EsActividad,
					'Estimado' 	  => $concepto->Estimado,
					'Unidad' 	  => $concepto->Unidad,
					'CantidadPresupuestada'    => Util::formatoNumerico($concepto->CantidadPresupuestada),
					'CantidadEstimadaAnterior' => Util::formatoNumerico($concepto->CantidadEstimadaAnterior),
					'CantidadEstimada' 		   => Util::formatoNumerico($concepto->CantidadEstimada),
					'PrecioUnitarioEstimado'   => Util::formatoNumerico($concepto->PrecioUnitarioEstimado),
					'ImporteEstimado' 		   => Util::formatoNumerico($concepto->ImporteEstimado),
					'CantidadCobrada'	 	   => Util::formatoNumerico($concepto->CantidadCobrada),
					'PrecioUnitarioCobrado'    => Util::formatoNumerico($concepto->PrecioUnitarioCobrado),
					'ImporteCobrado' 		   => Util::formatoNumerico($concepto->ImporteCobrado)
				);
			}

			break;

		case 'eliminaTransaccion':

			$IDTransaccion = (int) $_GET['IDTransaccion'];

			$transaccion = new Cobranza( $IDTransaccion , $conn );

			$transaccion->eliminaTransaccion();

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

		case 'getDatosTransaccion':

			$IDTransaccion = (int) $_GET['IDTransaccion'];

			$data['datos'] 	   = array();
			$data['conceptos'] = array();
			$data['totales']   = array();
			
			$transaccion = new Cobranza( $IDTransaccion , $conn );

			$data['datos']['fecha'] 		= Util::formatoFecha($transaccion->getFecha());
			$data['datos']['referencia']    = $transaccion->getReferencia();
			$data['datos']['observaciones'] = $transaccion->getObservaciones();

			$conceptos = $transaccion->getConceptos();

			foreach ($conceptos as $concepto) {
				
				$data['conceptos'][] = array(
					'IDConcepto'  => $concepto->IDConcepto,
					'NumeroNivel' => $concepto->NumeroNivel,
					'Descripcion' => $concepto->Descripcion,
					'EsActividad' => $concepto->EsActividad,
					'Estimado' 	  => $concepto->Estimado,
					'Unidad' 	  => $concepto->Unidad,
					'CantidadPresupuestada'    => Util::formatoNumerico($concepto->CantidadPresupuestada),
					'CantidadEstimadaAnterior' => Util::formatoNumerico($concepto->CantidadEstimadaAnterior),
					'CantidadEstimada' 		   => Util::formatoNumerico($concepto->CantidadEstimada),
					'PrecioUnitarioEstimado'   => Util::formatoNumerico($concepto->PrecioUnitarioEstimado),
					'ImporteEstimado' 		   => Util::formatoNumerico($concepto->ImporteEstimado),
					'CantidadCobrada'	 	   => Util::formatoNumerico($concepto->CantidadCobrada),
					'PrecioUnitarioCobrado'    => Util::formatoNumerico($concepto->PrecioUnitarioCobrado),
					'ImporteCobrado' 		   => Util::formatoNumerico($concepto->ImporteCobrado)
				);
			}

			$totales = $transaccion->getTotales();

			$data['totales'] = formatoTotales( $totales );
			
			break;

		case 'guardaTransaccion':

			$IDTransaccion    = (int) $_REQUEST['IDTransaccion'];
			$IDEstimacionObra = (int) $_REQUEST['IDEstimacionObra'];
			$fecha 		      = $_REQUEST['fecha'];
			$observaciones    = $_REQUEST['observaciones'];
			$conceptos 	      = is_array($_REQUEST['conceptos']) ? $_REQUEST['conceptos'] : array();
			$data['errores']  = array();
			$data['totales']  = array();

			if ( ! empty($IDTransaccion) ) {

				$transaccion = new Cobranza( $IDTransaccion, $conn );
				$transaccion->setFecha( $fecha );
				$transaccion->setObservaciones( $observaciones );
				$transaccion->setConceptos( $conceptos );

				$data['errores'] = $transaccion->guardaTransaccion();
			} else {
				
				$transaccion = new Cobranza(
						$IDObra,
						$IDEstimacionObra,
						$fecha,
						$observaciones,
						$conceptos,
						$conn
				);

				$data['errores'] 		= $transaccion->guardaTransaccion();
				$data['IDTransaccion']  = $transaccion->getIDTransaccion();
				$data['numeroFolio']    = Util::formatoNumeroFolio($transaccion->getNumeroFolio());
			}

			$totales = $transaccion->getTotales();

			$data['totales'] = formatoTotales( $totales );

			break;

		case 'getTotalesTransaccion':

			$IDTransaccion = (int) $_GET['IDTransaccion'];

			$data['totales'] = array();

			$transaccion = new Cobranza( $IDTransaccion , $conn );

			$totales = $transaccion->getTotales();

			$data['totales'] = formatoTotales( $totales );

			break;

		case 'getTransacciones':

			$data['options'] = array();

			$folios = Cobranza::getTransacciones( $IDObra, $conn );
			
			foreach ($folios as $folio) {
				
				$data['options'][] = array(
					'IDCobranza' => $folio->IDCobranza,
					'NumeroFolio'   => Util::formatoNumeroFolio($folio->NumeroFolio)
				);
			}

			break;

		case 'actualizaTotal':

			$IDTransaccion = (int) $_POST['IDTransaccion'];
			$tipoTotal 	   = $_POST['tipoTotal'];
			$importe 	   = Util::limpiaImporte($_POST['importe']);
			
			$transaccion = new Cobranza( $IDTransaccion , $conn );

			switch ( $tipoTotal ) {

				case 'txtImporteProgramado':
					$transaccion->setImporteProgramado($importe);
					break;

				case 'txtImporteDevolucion':
					$transaccion->setImporteDevolucion($importe);
					break;

				case 'txtImporteRetencionObraNoEjecutada':
					$transaccion->setImporteRetencionObraNoEjecutada($importe);
					break;

				case 'txtImporteAmortizacionAnticipo':
					$transaccion->setImporteAmortizacionAnticipo($importe);
					break;

				case 'txtImporteIVAAnticipo':
					$transaccion->setImporteIVAAnticipo($importe);
					break;

				case 'txtImporteInspeccionVigilancia':
					$transaccion->setImporteInspeccionVigilancia($importe);
					break;

				case 'txtImporteCMIC':
					$transaccion->setImporteCMIC($importe);
					break;

				default:
					throw new Exception("Total no válido");
			}

			break;

		// case 'getListaTransacciones':

		// 	$data['options'] = array();

		// 	$listaTran = AvanceObra::getListaTransacciones( $IDObra , $conn );

		// 	foreach ($listaTran as $tran) {
				
		// 		$data['options'][] = array(
		// 			'IDTransaccion'  => $tran->IDTransaccion,
		// 			'NumeroFolio' 	 => Util::formatoNumeroFolio($tran->NumeroFolio),
		// 			'Fecha'     	 => Util::formatoFecha($tran->Fecha),
		// 			'Referencia'  	 => $tran->Referencia
		// 		);
		// 	}
		// 	break;
	}

} catch( Exception $e ) {

	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

function formatoTotales( $totales ) {

	$data = array();

	foreach ($totales as $total) {

		$data = array(
			'subtotal'  => Util::formatoNumerico($total->Subtotal),
			'iva' 		=> Util::formatoNumerico($total->IVA),
			'total'   	=> Util::formatoNumerico($total->Total),
			'importeProgramado'   			   => Util::formatoNumerico($total->ImporteProgramado),
			'importeEstimadoAcumuladoAnterior' => Util::formatoNumerico($total->ImporteEstimadoAcumuladoAnterior),
			'importeObraEjecutadaEstimada'	   => Util::formatoNumerico($total->ImporteObraEjecutadaEstimada),
			'importeObraAcumuladaNoEjecutada'  => Util::formatoNumerico($total->ImporteObraAcumuladaNoEjecutada),
			'importeDevolucion'   			   => Util::formatoNumerico($total->ImporteDevolucion),
			'pctObraNoEjecutada'			   => Util::aPorcentaje($total->PctObraNoEjecutada),
			'importeRetencionObraNoEjecutada'  => Util::formatoNumerico($total->ImporteRetencionObraNoEjecutada),
			'subtotalFacturar'				   => Util::formatoNumerico($total->SubtotalFacturar),
			'ivaFacturar'					   => Util::formatoNumerico($total->IVAFacturar),
			'totalFacturar'					   => Util::formatoNumerico($total->TotalFacturar),
			'pctIVAAnticipo'				   => Util::aPorcentaje($total->PctIVAAnticipo),
			'pctInspeccion'					   => Util::aPorcentaje($total->PctInspeccion),
			'importeAmortizacionAnticipo'      => Util::formatoNumerico($total->ImporteAmortizacionAnticipo),
			'importeIVAAnticipo'			   => Util::formatoNumerico($total->ImporteIVAAnticipo),
			'importeInspeccionVigilancia'      => Util::formatoNumerico($total->ImporteInspeccionVigilancia),
			'importeCMIC'      				   => Util::formatoNumerico($total->ImporteCMIC),
			'totalDeducciones'				   => Util::formatoNumerico($total->TotalDeducciones),
			'alcanceLiquidoContratista'		   => Util::formatoNumerico($total->AlcanceLiquidoContratista)
		);
	}

	return $data;
}

echo json_encode($data);
?>