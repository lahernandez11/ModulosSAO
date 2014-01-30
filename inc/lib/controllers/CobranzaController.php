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

	switch ( $_REQUEST['action'] ) {

		case 'nuevaTransaccion':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );
			$id_estimacion_obra = (int) $_GET['id_estimacion_obra'];
			
			$data['conceptos'] = array();

			$conceptos = Cobranza::getConceptosNuevaTransaccion( $obra, $id_estimacion_obra );

			foreach ( $conceptos as $concepto ) {
				
				$data['conceptos'][] = array(
					'IDConcepto'  => $concepto->IDConcepto,
					'NumeroNivel' => $concepto->NumeroNivel,
					'Descripcion' => $concepto->Descripcion,
					'EsActividad' => $concepto->EsActividad,
					'Estimado' 	  => $concepto->Estimado,
					'Unidad' 	  => $concepto->Unidad,
					'CantidadPresupuestada'    => Util::formatoNumerico( $concepto->CantidadPresupuestada ),
					// 'CantidadEstimadaAnterior' => Util::formatoNumerico( $concepto->CantidadEstimadaAnterior ),
					'CantidadEstimada' 		   => Util::formatoNumerico( $concepto->CantidadEstimada ),
					'PrecioUnitarioEstimado'   => Util::formatoNumerico( $concepto->PrecioUnitarioEstimado ),
					'ImporteEstimado' 		   => Util::formatoNumerico( $concepto->ImporteEstimado ),
					'CantidadCobrada'	 	   => Util::formatoNumerico( $concepto->CantidadCobrada ),
					'PrecioUnitarioCobrado'    => Util::formatoNumerico( $concepto->PrecioUnitarioCobrado ),
					'ImporteCobrado' 		   => Util::formatoNumerico( $concepto->ImporteCobrado )
				);
			}
			break;

		case 'eliminaTransaccion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			
			$id_transaccion = (int) $_POST['id_transaccion'];

			$transaccion = new Cobranza( $obra, $id_transaccion );

			$transaccion->eliminaTransaccion();

			break;

		case 'getDatosTransaccion':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );
			$id_transaccion = (int) $_GET['id_transaccion'];

			$data['datos'] 	   = array();
			$data['conceptos'] = array();
			$data['totales']   = array();
			
			$transaccion = new Cobranza( $obra, $id_transaccion );

			$data['datos']['referencia']    = $transaccion->getReferencia();
			$data['datos']['observaciones'] = $transaccion->getObservaciones();
			$data['datos']['folio_factura'] = $transaccion->getFolioFactura();
			$data['datos']['fecha'] 		= Util::formatoFecha( $transaccion->getFecha() );

			$conceptos = $transaccion->getConceptos();

			foreach ( $conceptos as $concepto ) {
				
				$data['conceptos'][] = array(
					'IDConcepto'  => $concepto->IDConcepto,
					'NumeroNivel' => $concepto->NumeroNivel,
					'Descripcion' => $concepto->Descripcion,
					'EsActividad' => $concepto->EsActividad,
					'Estimado' 	  => $concepto->Estimado,
					'Unidad' 	  => $concepto->Unidad,
					'CantidadPresupuestada'    => Util::formatoNumerico( $concepto->CantidadPresupuestada ),
					// 'CantidadEstimadaAnterior' => Util::formatoNumerico( $concepto->CantidadEstimadaAnterior ),
					'CantidadEstimada' 		   => Util::formatoNumerico( $concepto->CantidadEstimada ),
					'PrecioUnitarioEstimado'   => Util::formatoNumerico( $concepto->PrecioUnitarioEstimado ),
					'ImporteEstimado' 		   => Util::formatoNumerico( $concepto->ImporteEstimado ),
					'CantidadCobrada'	 	   => Util::formatoNumerico( $concepto->CantidadCobrada ),
					'PrecioUnitarioCobrado'    => Util::formatoNumerico( $concepto->PrecioUnitarioCobrado ),
					'ImporteCobrado' 		   => Util::formatoNumerico( $concepto->ImporteCobrado )
				);
			}

			$totales = $transaccion->getTotales();

			$data['totales'] = formatoTotales( $totales );
			
			break;

		case 'guardaTransaccion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			$id_estimacion_obra = (int) $_POST['id_estimacion_obra'];

			$fecha 		   = $_POST['fecha'];
			$folio_factura = $_POST['folio_factura'];
			$observaciones = $_POST['observaciones'];
			$conceptos 	   = isset( $_POST['conceptos'] ) ? $_POST['conceptos'] : array();

			$data['errores']  = array();
			$data['totales']  = array();

			if ( isset( $_POST['id_transaccion'] ) ) {

				$transaccion = new Cobranza( $obra, (int) $_POST['id_transaccion'] );
				$transaccion->setFecha( $fecha );
				$transaccion->setObservaciones( $observaciones );
				$transaccion->setConceptos( $conceptos );
				$transaccion->setFolioFactura( $folio_factura );

				$data['errores'] = $transaccion->guardaTransaccion( Sesion::getUser() );
			} else {
				
				$transaccion = new Cobranza(
						$obra,
						$id_estimacion_obra,
						$fecha,
						$folio_factura,
						$observaciones,
						$conceptos
				);

				$data['errores'] 		= $transaccion->guardaTransaccion( Sesion::getUser() );
				$data['IDTransaccion']  = $transaccion->getIDTransaccion();
				$data['numeroFolio']    = Util::formatoNumeroFolio( $transaccion->getNumeroFolio() );
			}

			$totales = $transaccion->getTotales();

			$data['totales'] = formatoTotales( $totales );

			break;

		case 'getTotalesTransaccion':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );
			$id_transaccion = (int) $_GET['id_transaccion'];

			$data['totales'] = array();

			$transaccion = new Cobranza( $obra, $id_transaccion );
			$totales = $transaccion->getTotales();

			$data['totales'] = formatoTotales( $totales );
			break;

		case 'getFoliosTransaccion':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );
			$data['options'] = array();

			$folios = Cobranza::getFoliosTransaccion( $obra );
			
			foreach ( $folios as $folio ) {
				
				$data['options'][] = array(
					'IDCobranza' => $folio->IDCobranza,
					'NumeroFolio'   => Util::formatoNumeroFolio( $folio->NumeroFolio )
				);
			}

			break;

		case 'actualizaTotal':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];

			$tipoTotal = $_POST['tipoTotal'];
			$importe   = Util::limpiaImporte($_POST['importe']);
			
			$transaccion = new Cobranza( $obra, $id_transaccion );

			switch ( $tipoTotal ) {

				case 'txtImporteProgramado':
					$transaccion->setImporteProgramado( $importe );
					break;

				case 'txtImporteDevolucion':
					$transaccion->setImporteDevolucion( $importe );
					break;

				case 'txtImporteRetencionObraNoEjecutada':
					$transaccion->setImporteRetencionObraNoEjecutada( $importe );
					break;

				case 'txtImporteAmortizacionAnticipo':
					$transaccion->setImporteAmortizacionAnticipo( $importe );
					break;

				case 'txtImporteIVAAnticipo':
					$transaccion->setImporteIVAAnticipo( $importe );
					break;

				case 'txtImporteInspeccionVigilancia':
					$transaccion->setImporteInspeccionVigilancia( $importe );
					break;

				case 'txtImporteCMIC':
					$transaccion->setImporteCMIC( $importe );
					break;

				default:
					throw new Exception("Total no válido");
			}

			break;
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