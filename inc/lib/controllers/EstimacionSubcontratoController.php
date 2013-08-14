<?php
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Util.class.php';
require_once 'db/SAODBConn.class.php';
require_once 'models/EstimacionSubcontrato.class.php';
require_once 'models/EstimacionSubcontratoFormatoPDF.class.php';
require_once 'models/Subcontrato.class.php';

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

		case 'getListaTransacciones':

			$data['options'] = array();

			$listaTran = EstimacionSubcontrato::getListaTransacciones( $IDObra , $conn );

			foreach ($listaTran as $tran) {
				
				$data['options'][] = array(
					'IDTransaccion'  => $tran->IDTransaccion,
					'NumeroFolio' 	 => Util::formatoNumeroFolio($tran->NumeroFolio),
					'Fecha'     	 => Util::formatoFecha($tran->Fecha),
					'Observaciones'  => $tran->Observaciones
				);
			}
			break;

		case 'getFoliosTransaccion':

			$data['options'] = array();

			$folios = EstimacionSubcontrato::getFoliosTransaccion( $IDObra, $conn );
			
			foreach ($folios as $folio) {
				
				$data['options'][] = array(
					'IDTransaccion' => $folio->IDTransaccion,
					'NumeroFolio'   => Util::formatoNumeroFolio($folio->NumeroFolio)
				);
			}
			
			break;

		case 'getListaSubcontratos':

			$data['subcontratos'] = array();

			$lista = EstimacionSubcontrato::getListaSubcontratos( $IDObra, $conn );

			foreach ($lista as $item) {
				
				$data['subcontratos'][] = array(
					'IDSubcontrato'  => $item->IDSubcontrato,
					'Contratista'  => $item->Contratista,
					'NumeroFolio' 	 => Util::formatoNumeroFolio($item->NumeroFolio),
					'Fecha'     	 => Util::formatoFecha($item->Fecha),
					'Referencia'  => $item->Referencia
				);
			}
			break;

		case 'nuevaTransaccion':
			
			$IDSubcontrato = (int) $_GET['IDSubcontrato'];
			$data['datosSubcontrato'] = array();
			$data['conceptos'] = array();

			$subcontrato = new Subcontrato( $IDSubcontrato, $conn );

			$data['datosSubcontrato'] = array(
				'ObjetoSubcontrato' => $subcontrato->getObjetoSubcontrato(),
				'NombreContratista' => $subcontrato->getNombreContratista()
			);

			$conceptos = EstimacionSubcontrato::getConceptosNuevaEstimacion( $IDObra, $IDSubcontrato, $conn );

			$item = array();
			foreach ($conceptos as $concepto) {

				$item['IDConceptoContrato'] = $concepto->IDConceptoContrato;
				$item['EsActividad'] = $concepto->EsActividad;
				$item['NumeroNivel'] = $concepto->NumeroNivel;
				$item['Descripcion'] = $concepto->Descripcion;
				$item['CantidadSubcontratada'] = $concepto->EsActividad;
				$item['Unidad'] = $concepto->Unidad;

				if ( $concepto->EsActividad ) {
						$item['CantidadSubcontratada'] = Util::formatoNumerico($concepto->CantidadSubcontratada);
						$item['PrecioUnitario'] 	   = Util::formatoNumerico($concepto->PrecioUnitario);
						$item['CantidadEstimadaTotal'] = Util::formatoNumerico($concepto->CantidadEstimadaTotal);
						$item['MontoEstimadoTotal']	 = Util::formatoNumerico($concepto->MontoEstimadoTotal);
						$item['CantidadSaldo']   	 = Util::formatoNumerico($concepto->CantidadSaldo);
						$item['MontoSaldo']   	 	 = Util::formatoNumerico($concepto->MontoSaldo);
						$item['CantidadEstimada']    = Util::formatoNumerico($concepto->CantidadEstimada);
						$item['ImporteEstimado']   	 = Util::formatoNumerico($concepto->ImporteEstimado);
						$item['PctAvance'] = $concepto->PctAvance;
						$item['PctEstimado'] = $concepto->PctEstimado;
				} else {
						$item['CantidadSubcontratada'] = "";
						$item['PrecioUnitario'] 	   = "";
						$item['CantidadEstimadaTotal'] = "";
						$item['MontoEstimadoTotal']	= "";
						$item['CantidadSaldo']    = "";
						$item['MontoSaldo']   	  = "";
						$item['CantidadEstimada'] = "";
						$item['ImporteEstimado']  = "";
						$item['PctAvance'] 		  = "";
						$item['PctEstimado'] 	  = "";
				}
				
				$item['IDConceptoDestino'] = $concepto->IDConceptoDestino;
				$item['RutaDestino'] = $concepto->RutaDestino;

				$data['conceptos'][] = $item;
			}

			break;

		case 'getDatosTransaccion':

			$IDTransaccion = (int) $_GET['IDTransaccion'];

			$data['datos'] 	   = array();
			$data['conceptos'] = array();
			$data['totales']   = array();
			
			$transaccion = new EstimacionSubcontrato( $IDTransaccion , $conn );

			$data['datos']['NumeroFolioConsecutivo'] = Util::formatoNumeroFolio($transaccion->getNumeroFolioConsecutivo());
			$data['datos']['Fecha'] 			 = Util::formatoFecha($transaccion->getFecha());
			$data['datos']['FechaInicio']   	 = Util::formatoFecha($transaccion->getFechaInicio());
			$data['datos']['FechaTermino']  	 = Util::formatoFecha($transaccion->getFechaTermino());
			$data['datos']['Observaciones'] 	 = $transaccion->getObservaciones();
			$data['datos']['NombreContratista']  = $transaccion->getContratista();
			$data['datos']['ObjetoSubcontrato']  = $transaccion->getObjetoSubcontrato();

			$conceptos = $transaccion->getConceptosEstimacion();
			
			$item = array();
			foreach ($conceptos as $concepto) {

				$item['IDConceptoContrato'] = $concepto->IDConceptoContrato;
				$item['EsActividad'] = $concepto->EsActividad;
				$item['NumeroNivel'] = $concepto->NumeroNivel;
				$item['Descripcion'] = $concepto->Descripcion;
				$item['CantidadSubcontratada'] = $concepto->EsActividad;
				$item['Unidad'] = $concepto->Unidad;

				if ( $concepto->EsActividad ) {
						$item['CantidadSubcontratada'] = Util::formatoNumerico($concepto->CantidadSubcontratada);
						$item['PrecioUnitario'] 	   = Util::formatoNumerico($concepto->PrecioUnitario);
						$item['CantidadEstimadaTotal'] = Util::formatoNumerico($concepto->CantidadEstimadaTotal);
						$item['MontoEstimadoTotal']	 = Util::formatoNumerico($concepto->MontoEstimadoTotal);
						$item['CantidadSaldo']   	 = Util::formatoNumerico($concepto->CantidadSaldo);
						$item['MontoSaldo']   	 	 = Util::formatoNumerico($concepto->MontoSaldo);
						$item['CantidadEstimada']    = Util::formatoNumerico($concepto->CantidadEstimada);
						$item['ImporteEstimado']   	 = Util::formatoNumerico($concepto->ImporteEstimado);
						$item['PctAvance'] = $concepto->PctAvance;
						$item['PctEstimado'] = $concepto->PctEstimado;
				} else {
						$item['CantidadSubcontratada'] = "";
						$item['PrecioUnitario'] 	   = "";
						$item['CantidadEstimadaTotal'] = "";
						$item['MontoEstimadoTotal']	= "";
						$item['CantidadSaldo']    = "";
						$item['MontoSaldo']   	  = "";
						$item['CantidadEstimada'] = "";
						$item['ImporteEstimado']  = "";
						$item['PctAvance'] 		  = "";
						$item['PctEstimado'] 	  = "";
				}
				
				$item['IDConceptoDestino'] = $concepto->IDConceptoDestino;
				$item['RutaDestino'] = $concepto->RutaDestino;

				$data['conceptos'][] = $item;
			}

			$totales = $transaccion->getTotalesTransaccion();

			foreach ($totales as $total) {
				
				$data['totales'][] = array(
					'SumaImportes'  			=> Util::formatoNumerico($total->SumaImportes),
					'ImporteFondoGarantia'  	=> Util::formatoNumerico($total->ImporteFondoGarantia),
					'ImporteAmortizacionAnticipo'  => Util::formatoNumerico($total->ImporteAmortizacionAnticipo),
					'ImporteAnticipoLiberar'  	=> Util::formatoNumerico($total->ImporteAnticipoLiberar),
					'SumaDeductivas'  			=> Util::formatoNumerico($total->SumaDeductivas),
					'SumaRetenciones'  			=> Util::formatoNumerico($total->SumaRetenciones),
					'SumaRetencionesLiberadas'  => Util::formatoNumerico($total->SumaRetencionesLiberadas),
					'Subtotal' 					=> Util::formatoNumerico($total->Subtotal),
					'IVA' 						=> Util::formatoNumerico($total->IVA),
					'ImporteRetencionIVA'  		=> Util::formatoNumerico($total->ImporteRetencionIVA),
					'Total'     				=> Util::formatoNumerico($total->Total)
				);
			}
			break;

		case 'getTotalesTransaccion':

			$IDTransaccion = (int) $_GET['IDTransaccion'];
			$transaccion = new EstimacionSubcontrato( $IDTransaccion , $conn );
			$data['totales']   = array();

			$totales = $transaccion->getTotalesTransaccion();

			foreach ($totales as $total) {
				
				$data['totales'][] = array(
					'SumaImportes'  			=> Util::formatoNumerico($total->SumaImportes),
					'ImporteFondoGarantia'  	=> Util::formatoNumerico($total->ImporteFondoGarantia),
					'ImporteAmortizacionAnticipo'  => Util::formatoNumerico($total->ImporteAmortizacionAnticipo),
					'ImporteAnticipoLiberar'  	=> Util::formatoNumerico($total->ImporteAnticipoLiberar),
					'SumaDeductivas'  			=> Util::formatoNumerico($total->SumaDeductivas),
					'SumaRetenciones'  			=> Util::formatoNumerico($total->SumaRetenciones),
					'SumaRetencionesLiberadas'  => Util::formatoNumerico($total->SumaRetencionesLiberadas),
					'Subtotal' 					=> Util::formatoNumerico($total->Subtotal),
					'IVA' 						=> Util::formatoNumerico($total->IVA),
					'ImporteRetencionIVA'  		=> Util::formatoNumerico($total->ImporteRetencionIVA),
					'Total'     				=> Util::formatoNumerico($total->Total)
				);
			}
			break;

		case 'guardaTransaccion':

			$IDSubcontrato = (int) $_REQUEST['IDSubcontrato'];
			$IDTransaccion = (int) $_REQUEST['IDTransaccion'];

			$fecha = $_REQUEST['datosGenerales']['Fecha'];
			$fechaInicio = $_REQUEST['datosGenerales']['FechaInicio'];
			$fechaTermino = $_REQUEST['datosGenerales']['FechaTermino'];
			$observaciones = $_REQUEST['datosGenerales']['Observaciones'];
			$conceptos = is_array($_REQUEST['conceptos']) ? $_REQUEST['conceptos'] : array();
			$data['errores'] = array();
			$data['totales'] = array();

			if ( ! empty($IDTransaccion) ) {

				$transaccion = new EstimacionSubcontrato( $IDTransaccion , $conn );

				$transaccion->setFecha( $fecha );
				$transaccion->setFechaInicio( $fechaInicio );
				$transaccion->setFechaTermino( $fechaTermino );
				$transaccion->setObservaciones( $observaciones );
				$transaccion->setConceptos( $conceptos );
				
				$data['errores'] = $transaccion->guardaTransaccion();
			} else {
				
				$transaccion = new EstimacionSubcontrato(
					$IDObra, $IDSubcontrato, $fecha, $fechaInicio, $fechaTermino, $observaciones, $conceptos, $conn
				);
				
				$data['errores']				= $transaccion->guardaTransaccion();
				$data['IDTransaccion']  		= $transaccion->getIDTransaccion();
				$data['NumeroFolio']    		= Util::formatoNumeroFolio($transaccion->getNumeroFolio());
				$data['NumeroFolioConsecutivo'] = Util::formatoNumeroFolio($transaccion->getNumeroFolioConsecutivo());
			}

			$totales = $transaccion->getTotalesTransaccion();

			foreach ($totales as $total) {
				
				$data['totales'][] = array(
					'SumaImportes'  			=> Util::formatoNumerico($total->SumaImportes),
					'ImporteFondoGarantia'  	=> Util::formatoNumerico($total->ImporteFondoGarantia),
					'ImporteAmortizacionAnticipo'  => Util::formatoNumerico($total->ImporteAmortizacionAnticipo),
					'SumaDeductivas'  			=> Util::formatoNumerico($total->SumaDeductivas),
					'Subtotal' 					=> Util::formatoNumerico($total->Subtotal),
					'IVA' 						=> Util::formatoNumerico($total->IVA),
					'Total'     				=> Util::formatoNumerico($total->Total)
				);
			}

			break;

		case 'actualizaImporte':

			$IDTransaccion = (int) $_GET['IDTransaccion'];
			$tipoTotal = $_GET['tipoTotal'];
			$importe = Util::limpiaImporte($_GET['importe']);
			
			$transaccion = new EstimacionSubcontrato( $IDTransaccion , $conn );

			switch ( $tipoTotal ) {

				case 'txtAmortAnticipo':
					$transaccion->setImporteAmortizacionAnticipo($importe);
					break;

				case 'txtFondoGarantia':
					$transaccion->setImporteFondoGarantia($importe);
					break;

				case 'txtRetencionIVA':
					$transaccion->setImporteRetencionIVA($importe);
					break;

				case 'txtAnticipoLiberar':
					$transaccion->setImporteAnticipoLiberar($importe);
					break;

				default:
					throw new Exception("Total no válido");
			}

			break;

		case 'eliminaTransaccion':

			$IDTransaccion = (int) $_GET['IDTransaccion'];

			$transaccion = new EstimacionSubcontrato( $IDTransaccion , $conn );

			$transaccion->eliminaTransaccion();

			break;

		case 'getDeductivas':

			$IDTransaccion = (int) $_REQUEST['IDTransaccion'];
			$transaccion = new EstimacionSubcontrato( $IDTransaccion , $conn );
			$data['deductivas'] = array();

			$deductivas = $transaccion->getDeductivas();

			foreach ($deductivas as $deductiva) {
				$data['deductivas'][] = array(
					'IDDeductiva' => $deductiva->IDDeductiva,
					'TipoDeductiva' => $deductiva->TipoDeductiva,
					'Concepto'    => $deductiva->Concepto,
					'Importe'     => Util::formatoNumerico($deductiva->Importe),
					'Observaciones'    => $deductiva->Observaciones
				);
			}
			break;

		case 'guardaDeductiva':

			$data['IDDeductiva'] = null;

			$IDTransaccion   = (int) $_REQUEST['IDTransaccion'];
			$IDTipoDeductiva = (int) $_REQUEST['IDTipoDeductiva'];
			$IDReferencia    = (int) $_GET['IDInsumo'];

			$importe       = Util::limpiaImporte($_GET['importe']);
			$concepto      = $_GET['concepto'];
			$observaciones = $_GET['observaciones'];

			$transaccion   = new EstimacionSubcontrato( $IDTransaccion , $conn );

			switch( $IDTipoDeductiva ) {

				case 1:
					$data['IDDeductiva'] = 
						$transaccion->agregaDeductivaMaterial( $IDReferencia, $importe, $observaciones );
					break;
				case 2:
					$data['IDDeductiva'] =
						$transaccion->agregaDeductivaManoObra( $IDReferencia, $importe, $observaciones );
					break;
				case 3:
					$data['IDDeductiva'] =
						$transaccion->agregaDeductivaMaquinaria( $IDReferencia, $importe, $observaciones );
					break;
				case 4:
					$data['IDDeductiva'] =
						$transaccion->agregaDeductivaSubcontratos( $concepto, $importe, $observaciones );
					break;
				case 5:
					$data['IDDeductiva'] =
						$transaccion->agregaDeductivaOtros( $concepto, $importe, $observaciones );
					break;
			}

			break;

		case 'eliminaDeductiva':

			$IDTransaccion   = (int) $_REQUEST['IDTransaccion'];
			$IDDeductiva 	 = (int) $_REQUEST['IDDeductiva'];

			$transaccion = new EstimacionSubcontrato( $IDTransaccion , $conn );

			$transaccion->eliminaDeductiva( $IDDeductiva );
			break;

		case 'getRetenciones':

			$IDTransaccion = (int) $_REQUEST['IDTransaccion'];
			$transaccion = new EstimacionSubcontrato( $IDTransaccion , $conn );
			$data['retenciones'] = array();
			$data['liberaciones'] = array();

			$retenciones = $transaccion->getRetenciones();

			foreach ($retenciones as $retencion) {
				$data['retenciones'][] = array(
					'IDRetencion'   => $retencion->IDRetencion,
					'TipoRetencion' => $retencion->TipoRetencion,
					'concepto'      => $retencion->Concepto,
					'importe'       => Util::formatoNumerico($retencion->Importe),
					'observaciones' => $retencion->Observaciones
				);
			}

			$liberaciones = $transaccion->getLiberaciones();

			foreach ($liberaciones as $liberacion) {
				$data['liberaciones'][] = array(
					'IDLiberacion'   => $liberacion->IDLiberacion,
					'importe'       => Util::formatoNumerico($liberacion->Importe),
					'observaciones' => $liberacion->Observaciones
				);
			}
			break;

		case 'getTiposRetencion':

			$data['options'] = array();
			$data['options'] = EstimacionSubcontrato::getTiposRetencion( $conn );
			break;

		case 'guardaRetencion':

			$data['IDRetencion'] = null;

			$IDTransaccion   = (int) $_GET['IDTransaccion'];
			$IDTipoRetencion = (int) $_GET['IDTipoRetencion'];
			$importe 		 = Util::limpiaImporte($_GET['importe']);
			$concepto 		 = $_GET['concepto'];
			$observaciones 	 = $_GET['observaciones'];

			$transaccion = new EstimacionSubcontrato( $IDTransaccion , $conn );

			$data['IDRetencion'] = $transaccion->agregaRetencion( $IDTipoRetencion, $importe, $concepto, $observaciones );
			break;

		case 'eliminaRetencion':

			$IDTransaccion   = (int) $_GET['IDTransaccion'];
			$IDRetencion 	 = (int) $_GET['IDRetencion'];

			$transaccion = new EstimacionSubcontrato( $IDTransaccion , $conn );

			$transaccion->eliminaRetencion( $IDRetencion );
			break;

		case 'getImportePorLiberar':

			$IDTransaccion   = (int) $_REQUEST['IDTransaccion'];

			$transaccion = new EstimacionSubcontrato( $IDTransaccion , $conn );

			$data['importePorLiberar'] = 0;

			$data['importePorLiberar'] = Util::formatoNumerico($transaccion->getImporteRetenidoPorLiberar());
			break;
		
		case 'guardaLiberacion':

			$data['IDLiberacion'] = null;

			$IDTransaccion   = (int) $_GET['IDTransaccion'];
			$importe 		 = Util::limpiaImporte($_GET['importe']);
			$observaciones 	 = $_GET['observaciones'];

			$transaccion = new EstimacionSubcontrato( $IDTransaccion , $conn );

			$data['IDLiberacion'] =
				$transaccion->agregaLiberacion(
					$importe,
					$observaciones,
					Sesion::getCuentaUsuarioSesion()
				);
			break;

		case 'eliminaLiberacion':

			$IDTransaccion   = (int) $_GET['IDTransaccion'];
			$IDLiberacion   = (int) $_GET['IDLiberacion'];

			$transaccion = new EstimacionSubcontrato( $IDTransaccion , $conn );

			$transaccion->eliminaLiberacion( $IDLiberacion );
			break;

		case 'generaFormato':

			$IDTransaccion = (int) $_GET['IDTransaccion'];
			$soloEstimados = (int) (isset($_GET['soloEstimados']) ? $_GET['soloEstimados'] : 0);

			$transaccion = new EstimacionSubcontrato( $IDTransaccion , $conn );

			$formatoPDF = new EstimacionSubcontratoFormatoPDF( $transaccion, $soloEstimados );
			$formatoPDF->Output();
			break;

		default:
			throw new Exception("Accion desconocida");
		break;
	}

} catch( Exception $e ) {

	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>