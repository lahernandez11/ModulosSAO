<?php
require_once 'setPath.php';
require_once 'db/SAODBConnFactory.class.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Subcontrato.class.php';
require_once 'models/EstimacionSubcontrato.class.php';
require_once 'models/RetencionTipo.class.php';
require_once 'models/EstimacionSubcontratoFormatoPDF.class.php';
require_once 'models/EstimacionSubcontratoFormatoPDF_SPM.class.php';
require_once 'models/Util.class.php';

$data['success'] = true;
$data['message'] = null;
$data['noRows']  = false;

try {
	
	Sesion::validaSesionAsincrona();

	if ( ! isset($_REQUEST['action']) ) {
		throw new Exception("No fue definida una acción");
	}
	
	switch ( $_REQUEST['action'] ) {

		case 'getListaTransacciones':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );
			$data['options'] = array();

			$listaTran = EstimacionSubcontrato::getListaTransacciones( $obra );

			foreach ( $listaTran as $tran ) {
				
				$data['options'][] = array(
					'IDTransaccion'  => $tran->IDTransaccion,
					'NumeroFolio' 	 => Util::formatoNumeroFolio( $tran->NumeroFolio ),
					'Fecha'     	 => Util::formatoFecha( $tran->Fecha ),
					'Observaciones'  => $tran->Observaciones
				);
			}
			break;

		case 'getFoliosTransaccion':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );

			$data['options'] = array();

			$folios = EstimacionSubcontrato::getFoliosTransaccion( $obra );
			
			foreach ( $folios as $folio ) {
				
				$data['options'][] = array(
					'IDTransaccion' => $folio->IDTransaccion,
					'NumeroFolio'   => Util::formatoNumeroFolio( $folio->NumeroFolio )
				);
			}
			
			break;

		case 'getListaSubcontratos':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );

			$data['subcontratos'] = array();

			foreach ( Subcontrato::getListaSubcontratos( $obra ) as $item ) {
				$data['subcontratos'][] = array(
					'IDSubcontrato' => $item->IDSubcontrato,
					'Contratista'   => $item->Contratista,
					'NumeroFolio'   => Util::formatoNumeroFolio( $item->NumeroFolio ),
					'Fecha'         => Util::formatoFecha( $item->Fecha ),
					'Referencia'    => $item->Referencia
				);
			}
			break;

		case 'nuevaTransaccion':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );
			$id_subcontrato = (int) $_GET['id_subcontrato'];

			$data['datosSubcontrato'] = array();
			$data['conceptos'] = array();

			$subcontrato = new Subcontrato( $obra, $id_subcontrato );

			$data['datosSubcontrato'] = array(
				'ObjetoSubcontrato' => $subcontrato->getReferencia(),
				'NombreContratista' => $subcontrato->empresa->getNombre()
			);


			$item = array();
			foreach ( EstimacionSubcontrato::getConceptosNuevaEstimacion( $subcontrato ) as $concepto) {

				$item['IDConceptoContrato']    = $concepto->IDConceptoContrato;
				$item['EsActividad'] 		   = $concepto->EsActividad;
				$item['NumeroNivel'] 		   = $concepto->NumeroNivel;
				$item['Descripcion'] 		   = $concepto->Descripcion;
				$item['CantidadSubcontratada'] = $concepto->EsActividad;
				$item['Unidad'] 			   = $concepto->Unidad;
				$item['estimado'] 			   = false;

				if ( $concepto->EsActividad ) {
						$item['CantidadSubcontratada'] = Util::formatoNumerico($concepto->CantidadSubcontratada);
						$item['PrecioUnitario'] 	   = Util::formatoNumerico($concepto->PrecioUnitario);
						$item['CantidadEstimadaTotal'] = Util::formatoNumerico($concepto->CantidadEstimadaTotal);
						$item['MontoEstimadoTotal']	   = Util::formatoNumerico($concepto->MontoEstimadoTotal);
						$item['CantidadSaldo']   	   = Util::formatoNumerico($concepto->CantidadSaldo);
						$item['MontoSaldo']   	 	   = Util::formatoNumerico($concepto->MontoSaldo);
						$item['CantidadEstimada']      = Util::formatoNumerico($concepto->CantidadEstimada);
						$item['ImporteEstimado']   	   = Util::formatoNumerico($concepto->ImporteEstimado);
						$item['PctAvance'] 			   = $concepto->PctAvance;
						$item['PctEstimado']		   = $concepto->PctEstimado;
				} else {
						$item['CantidadSubcontratada'] = "";
						$item['PrecioUnitario'] 	   = "";
						$item['CantidadEstimadaTotal'] = "";
						$item['MontoEstimadoTotal']	   = "";
						$item['CantidadSaldo']    	   = "";
						$item['MontoSaldo']   	  	   = "";
						$item['CantidadEstimada'] 	   = "";
						$item['ImporteEstimado']  	   = "";
						$item['PctAvance'] 		  	   = "";
						$item['PctEstimado'] 	  	   = "";
				}
				
				$item['IDConceptoDestino'] = $concepto->IDConceptoDestino;
				$item['RutaDestino'] 	   = $concepto->RutaDestino;

				$data['conceptos'][] = $item;
			}

			break;

		case 'getTransaccion':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );
			$id_transaccion = (int) $_GET['id_transaccion'];

			$data['datos'] 	   = array();
			$data['conceptos'] = array();
			$data['totales']   = array();
			
			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			
			$data['datos']['NumeroFolioConsecutivo'] = Util::formatoNumeroFolio( $transaccion->getNumeroFolioConsecutivo() );
			$data['datos']['Fecha'] 			 	 = Util::formatoFecha( $transaccion->getFecha() );
			$data['datos']['FechaInicio']   	 	 = Util::formatoFecha( $transaccion->getFechaInicio() );
			$data['datos']['FechaTermino']  	 	 = Util::formatoFecha( $transaccion->getFechaTermino() );
			$data['datos']['Observaciones'] 	 	 = $transaccion->getObservaciones();
			$data['datos']['NombreContratista']  	 = $transaccion->empresa->getNombre();
			$data['datos']['ObjetoSubcontrato']  	 = $transaccion->subcontrato->getReferencia();

			$conceptos = $transaccion->getConceptosEstimacion();
			
			$item = array();
			foreach ( $conceptos as $concepto ) {

				$item['IDConceptoContrato']    = $concepto->IDConceptoContrato;
				$item['EsActividad'] 	       = $concepto->EsActividad;
				$item['NumeroNivel'] 		   = $concepto->NumeroNivel;
				$item['Descripcion'] 		   = $concepto->Descripcion;
				$item['CantidadSubcontratada'] = $concepto->EsActividad;
				$item['Unidad'] 			   = $concepto->Unidad;
				$item['estimado'] 			   = false;

				if ( $concepto->EsActividad ) {

						$item['CantidadSubcontratada'] = Util::formatoNumerico( $concepto->CantidadSubcontratada );
						$item['PrecioUnitario'] 	   = Util::formatoNumerico( $concepto->PrecioUnitario );
						$item['CantidadEstimadaTotal'] = Util::formatoNumerico( $concepto->CantidadEstimadaTotal );
						$item['MontoEstimadoTotal']	   = Util::formatoNumerico( $concepto->MontoEstimadoTotal );
						$item['CantidadSaldo']   	   = Util::formatoNumerico( $concepto->CantidadSaldo );
						$item['MontoSaldo']   	 	   = Util::formatoNumerico( $concepto->MontoSaldo );
						$item['CantidadEstimada']      = Util::formatoNumerico( $concepto->CantidadEstimada );
						$item['ImporteEstimado']   	   = Util::formatoNumerico( $concepto->ImporteEstimado );
						$item['PctAvance'] 			   = $concepto->PctAvance;
						$item['PctEstimado'] 		   = $concepto->PctEstimado;

						if ( $concepto->CantidadEstimada != 0 )
							$item['estimado'] = true;

				} else {
						$item['CantidadSubcontratada'] = "";
						$item['PrecioUnitario'] 	   = "";
						$item['CantidadEstimadaTotal'] = "";
						$item['MontoEstimadoTotal']	   = "";
						$item['CantidadSaldo']    	   = "";
						$item['MontoSaldo']   	  	   = "";
						$item['CantidadEstimada'] 	   = "";
						$item['ImporteEstimado']  	   = "";
						$item['PctAvance'] 		  	   = "";
						$item['PctEstimado'] 	  	   = "";
				}
				
				$item['IDConceptoDestino'] = $concepto->IDConceptoDestino;
				$item['RutaDestino'] 	   = $concepto->RutaDestino;

				$data['conceptos'][] = $item;
			}

			$totales = $transaccion->getTotalesTransaccion();
			$data['totales'] = Util::formatoNumericoTotales( $totales );

			break;

		case 'getTotalesTransaccion':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );
			$id_transaccion = (int) $_GET['id_transaccion'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			$data['totales']   = array();

			$totales = $transaccion->getTotalesTransaccion();
			$data['totales'] = Util::formatoNumericoTotales( $totales );

			break;

		case 'guardaTransaccion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );

			$id_transaccion = (int) $_POST['id_transaccion'];
			$id_subcontrato = (int) $_POST['id_subcontrato'];

			$fecha 		   = $_POST['datosGenerales']['Fecha'];
			$fechaInicio   = $_POST['datosGenerales']['FechaInicio'];
			$fechaTermino  = $_POST['datosGenerales']['FechaTermino'];
			$observaciones = $_POST['datosGenerales']['Observaciones'];
			$conceptos 	   = array();
			
			if ( isset( $_POST['conceptos'] ) && is_array( $_POST['conceptos'] ) ) {
				$conceptos = $_POST['conceptos'];
			}

			$data['errores'] = array();
			$data['totales'] = array();

			if ( ! empty( $id_transaccion ) ) {

				$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
				$transaccion->setFecha( $fecha );
				$transaccion->setFechaInicio( $fechaInicio );
				$transaccion->setFechaTermino( $fechaTermino );
				$transaccion->setObservaciones( $observaciones );
				$transaccion->setConceptos( $conceptos );
				$transaccion->setImporteAmortizacionAnticipo( Util::limpiaImporte( $_POST['amortizacion_anticipo'] ) );
				$transaccion->setImporteFondoGarantia( Util::limpiaImporte( $_POST['fondo_garantia'] ) );
				$transaccion->setImporteRetencionIVA( Util::limpiaImporte( $_POST['retencion_iva'] ) );
				$transaccion->setImporteAnticipoLiberar( Util::limpiaImporte( $_POST['anticipo_liberar'] ) );
				
				$data['errores'] = $transaccion->guardaTransaccion( Sesion::getUser() );

			} else {
				$subcontrato = new Subcontrato( $obra, $id_subcontrato );
				
				$transaccion = new EstimacionSubcontrato(
					$obra, $subcontrato, $fecha, $fechaInicio, 
					$fechaTermino, $observaciones, $conceptos
				);
				
				$data['errores']				= $transaccion->guardaTransaccion( Sesion::getUser() );
				$data['IDTransaccion']  		= $transaccion->getIDTransaccion();
				$data['NumeroFolio']    		= Util::formatoNumeroFolio( $transaccion->getNumeroFolio() );
				$data['NumeroFolioConsecutivo'] = Util::formatoNumeroFolio( $transaccion->getNumeroFolioConsecutivo() );
			}

			$totales = $transaccion->getTotalesTransaccion();
			$data['totales'] = Util::formatoNumericoTotales( $totales );
			
			break;

		case 'apruebaTransaccion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );

			$id_transaccion = (int) $_POST['id_transaccion'];
			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			$transaccion->setAprobada( Sesion::getUser() );
			break;

		case 'revierteAprobacion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );

			$id_transaccion = (int) $_POST['id_transaccion'];
			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			$transaccion->revierteAprobacion();
			break;

		case 'eliminaTransaccion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			$transaccion->eliminaTransaccion();
			break;

		case 'getDeductivas':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );

			$id_transaccion = (int) $_GET['id_transaccion'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			$data['deductivas'] = array();

			foreach ( $transaccion->empresa->deductivas as $deductiva ) {
				$descuento = $deductiva->getDescuento( $transaccion );

				$cantidad_por_descontar = 
					  $deductiva->getCantidadTotal()
					- $descuento->getCantidadDescontada();

				if ( $cantidad_por_descontar < 0 ) { $cantidad_por_descontar = 0; }

				$importe_por_descontar = $deductiva->getImporteTotal()
					- $descuento->getImporteDescontado();

				if ( $importe_por_descontar < 0 ) {	$importe_por_descontar = 0; }

				$data['deductivas'][] = array(
					'id_item'   	 		 => $deductiva->getId(),
					'descripcion'    		 => $deductiva->material->getDescripcion(),
					'cantidad_total' 		 => Util::formatoNumerico($deductiva->getCantidadTotal()),
					'unidad' 				 => $deductiva->getUnidad(),
					'precio' 				 => Util::formatoNumerico($deductiva->getPrecio()),
					'importe_total' 		 => Util::formatoNumerico($deductiva->getImporteTotal()),
					'cantidad_descontada' 	 => Util::formatoNumerico($descuento->getCantidadDescontada()),
					'importe_descontado' 	 => Util::formatoNumerico($descuento->getImporteDescontado()),
					'cantidad_por_descontar' => Util::formatoNumerico($cantidad_por_descontar),
					'importe_por_descontar'  => Util::formatoNumerico($importe_por_descontar),
					'id_descuento'			 => $descuento->getId(),
					'cantidad_descuento'	 => $descuento->getCantidad(),
					'precio_descuento'	 	 => Util::formatoNumerico( $descuento->getPrecio() === 0 ? $deductiva->getPrecio() : $descuento->getPrecio() ),
					'importe_descuento'	 	 => Util::formatoNumerico($descuento->getImporte())
				);
			}
			break;

		case 'guardaDescuento':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];
			$deductivas 	= $_POST['descuentos'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			$desc = array();

			foreach ( $deductivas as $key => $item ) {
				$deductiva = new EstimacionDeductiva( $transaccion->empresa, $item['id_item'] );

				$descuento = $transaccion->addDescuento(
					$deductiva, Util::limpiaImporte( $item['cantidad_descuento'] ), Util::limpiaImporte( $item['precio_descuento'] )
				);
				
				$desc[$item['id_item']] = array(
					'id_descuento' 		 => $descuento->getId(),
					'cantidad_descuento' => $item['cantidad_descuento'],
					'precio_descuento'   => $item['precio_descuento'],
					'importe_descuento'  => $descuento->getImporte()
				);
			}

			$data['deductivas'] = $desc;
			break;

		case 'getRetenciones':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );
			$id_transaccion = (int) $_GET['id_transaccion'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			$data['retenciones'] = array();
			$data['liberaciones'] = array();

			foreach ( $transaccion->retenciones as $retencion ) {
				$data['retenciones'][] = array(
					'id'   			 => $retencion->getId(),
					'tipo_retencion' => $retencion->tipo_retencion->getDescripcion(),
					'concepto'       => $retencion->getConcepto(),
					'importe'        => Util::formatoNumerico( $retencion->getImporte() ),
				);
			}

			foreach ( $transaccion->liberaciones as $liberacion ) {
				$data['liberaciones'][] = array(
					'id'   	   => $liberacion->getId(),
					'importe'  => Util::formatoNumerico( $liberacion->getImporte() ),
					'concepto' => $liberacion->getConcepto()
				);
			}
			break;

		case 'getTiposRetencion':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );

			$data['tipos_retencion'] = array();
			$tipos = RetencionTipo::getInstance( $conn );

			foreach ( $tipos as $tipo ) {
				$data['tipos_retencion'][] = array(
					'id' => $tipo->getId(),
					'descripcion' => $tipo->getDescripcion()
				);
			}
			break;

		case 'guardaRetencion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];

			$data['retencion'] = array();

			if ( ! isset( $_POST['id_tipo_retencion'] ) ) {
				throw new Exception("El tipo de retencion es incorrecto.", 1);
			}

			$id_tipo_retencion = (int) $_POST['id_tipo_retencion'];
			$importe 		   = Util::limpiaImporte( $_POST['importe'] );
			$concepto 		   = isset( $_POST['concepto'] ) ? $_POST['concepto'] : "";

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			$tipo_retencion = RetencionTipo::getInstance( $conn, $id_tipo_retencion );
			$retencion = $transaccion->addRetencion( $tipo_retencion, $importe, $concepto );

			$data['retencion']['id'] = $retencion->getId();
			$data['retencion']['tipo_retencion'] = $retencion->tipo_retencion->getDescripcion();
			$data['retencion']['concepto'] = $retencion->getConcepto();
			$data['retencion']['importe'] = Util::formatoNumerico( $retencion->getImporte() );
			break;

		case 'eliminaRetencion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			$retencion = EstimacionRetencion::getInstance( $transaccion, (int) $_POST['id_retencion'] );
			$retencion->delete();
			break;

		// case 'getImportePorLiberar':
		// 	$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
		// 	$obra = new Obra( $conn, (int) $_GET['id_obra'] );
		// 	$id_transaccion = (int) $_GET['id_transaccion'];

		// 	$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );

		// 	$data['importePorLiberar'] = 0;

		// 	$data['importePorLiberar'] = Util::formatoNumerico( $transaccion->getImporteRetenidoPorLiberar() );
		// 	break;
		
		case 'guardaLiberacion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];

			$data['liberacion'] = array();

			$importe 	   = Util::limpiaImporte( $_POST['importe'] );
			$observaciones = isset( $_POST['concepto'] ) ? $_POST['concepto'] : "";

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			$liberacion = $transaccion->addLiberacion( $importe, $observaciones, Sesion::getUser() );

			$data['liberacion']['id'] = $liberacion->getId();
			$data['liberacion']['importe'] = Util::formatoNumerico( $liberacion->getImporte() );
			$data['liberacion']['concepto'] = $liberacion->getConcepto();
			break;

		case 'eliminaLiberacion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];

			$id_liberacion = (int) $_POST['id_liberacion'];
			// echo $id_liberacion;

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			$liberacion = EstimacionRetencionLiberacion::getInstance( $transaccion, $id_liberacion );
			$liberacion->delete();
			break;

		case 'generaFormato':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );
			$id_transaccion = (int) $_GET['id_transaccion'];

			$soloEstimados = (int) (isset($_GET['soloEstimados']) ? $_GET['soloEstimados'] : 0);

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );

			if ( $transaccion->obra->getDBName() === 'SAO1814_SPM_MOBILIARIO' )
				$formatoPDF = new EstimacionSubcontratoFormatoPDF_SPM( $transaccion, $soloEstimados );
			else
				$formatoPDF = new EstimacionSubcontratoFormatoPDF( $transaccion, $soloEstimados );

			$formatoPDF->Output();
			break;

		default:
			throw new Exception("Accion desconocida.");
		break;
	}

} catch( Exception $e ) {
	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>