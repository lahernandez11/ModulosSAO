<?php
require_once 'setPath.php';
require_once 'db/SAODBConnFactory.class.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Subcontrato.class.php';
require_once 'models/EstimacionSubcontrato.class.php';
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
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );
			$id_transaccion = (int) $_GET['id_transaccion'];

			$data['datos'] 	   = array();
			$data['conceptos'] = array();
			$data['totales']   = array();
			
			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );

			$data['datos']['NumeroFolioConsecutivo'] = Util::formatoNumeroFolio( $transaccion->getNumeroFolioConsecutivo() );
			$data['datos']['Fecha'] 			 = Util::formatoFecha( $transaccion->getFecha() );
			$data['datos']['FechaInicio']   	 = Util::formatoFecha( $transaccion->getFechaInicio() );
			$data['datos']['FechaTermino']  	 = Util::formatoFecha( $transaccion->getFechaTermino() );
			$data['datos']['Observaciones'] 	 = $transaccion->getObservaciones();
			$data['datos']['NombreContratista']  = $transaccion->empresa->getNombre();
			$data['datos']['ObjetoSubcontrato']  = $transaccion->subcontrato->getReferencia();

			$conceptos = $transaccion->getConceptosEstimacion();
			
			$item = array();
			foreach ( $conceptos as $concepto ) {

				$item['IDConceptoContrato']    = $concepto->IDConceptoContrato;
				$item['EsActividad'] 	       = $concepto->EsActividad;
				$item['NumeroNivel'] 		   = $concepto->NumeroNivel;
				$item['Descripcion'] 		   = $concepto->Descripcion;
				$item['CantidadSubcontratada'] = $concepto->EsActividad;
				$item['Unidad'] 			   = $concepto->Unidad;

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

			foreach ( $totales as $total ) {
				
				$data['totales'][] = array(
					'SumaImportes'  			=> Util::formatoNumerico( $total->SumaImportes ),
					'ImporteFondoGarantia'  	=> Util::formatoNumerico( $total->ImporteFondoGarantia ),
					'ImporteAmortizacionAnticipo' => Util::formatoNumerico( $total->ImporteAmortizacionAnticipo ),
					'ImporteAnticipoLiberar'  	=> Util::formatoNumerico( $total->ImporteAnticipoLiberar ),
					'SumaDeductivas'  			=> Util::formatoNumerico( $total->SumaDeductivas ),
					'SumaRetenciones'  			=> Util::formatoNumerico( $total->SumaRetenciones ),
					'SumaRetencionesLiberadas'  => Util::formatoNumerico( $total->SumaRetencionesLiberadas ),
					'Subtotal' 					=> Util::formatoNumerico( $total->Subtotal ),
					'IVA' 						=> Util::formatoNumerico( $total->IVA ),
					'ImporteRetencionIVA'  		=> Util::formatoNumerico( $total->ImporteRetencionIVA ),
					'Total'     				=> Util::formatoNumerico( $total->Total )
				);
			}
			break;

		case 'getTotalesTransaccion':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );
			$id_transaccion = (int) $_GET['id_transaccion'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			$data['totales']   = array();

			$totales = $transaccion->getTotalesTransaccion();

			foreach ( $totales as $total ) {
				
				$data['totales'][] = array(
					'SumaImportes'  			=> Util::formatoNumerico( $total->SumaImportes ),
					'ImporteFondoGarantia'  	=> Util::formatoNumerico( $total->ImporteFondoGarantia ),
					'ImporteAmortizacionAnticipo' => Util::formatoNumerico( $total->ImporteAmortizacionAnticipo ),
					'ImporteAnticipoLiberar'  	=> Util::formatoNumerico( $total->ImporteAnticipoLiberar ),
					'SumaDeductivas'  			=> Util::formatoNumerico( $total->SumaDeductivas ),
					'SumaRetenciones'  			=> Util::formatoNumerico( $total->SumaRetenciones ),
					'SumaRetencionesLiberadas'  => Util::formatoNumerico( $total->SumaRetencionesLiberadas ),
					'Subtotal' 					=> Util::formatoNumerico( $total->Subtotal ),
					'IVA' 						=> Util::formatoNumerico( $total->IVA ),
					'ImporteRetencionIVA'  		=> Util::formatoNumerico( $total->ImporteRetencionIVA ),
					'Total'     				=> Util::formatoNumerico( $total->Total )
				);
			}
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

			foreach ( $totales as $total ) {
				
				$data['totales'][] = array(
					'SumaImportes'  			=> Util::formatoNumerico( $total->SumaImportes ),
					'ImporteFondoGarantia'  	=> Util::formatoNumerico( $total->ImporteFondoGarantia ),
					'ImporteAmortizacionAnticipo' => Util::formatoNumerico( $total->ImporteAmortizacionAnticipo ),
					'SumaDeductivas'  			=> Util::formatoNumerico( $total->SumaDeductivas ),
					'Subtotal' 					=> Util::formatoNumerico( $total->Subtotal ),
					'IVA' 						=> Util::formatoNumerico( $total->IVA ),
					'Total'     				=> Util::formatoNumerico( $total->Total )
				);
			}
			break;

		case 'actualizaImporte':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );

			$id_transaccion = (int) $_POST['id_transaccion'];
			$tipoTotal = $_POST['tipoTotal'];
			$importe = Util::limpiaImporte( $_POST['importe'] );
			
			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );

			switch ( $tipoTotal ) {

				case 'txtAmortAnticipo':
					$transaccion->setImporteAmortizacionAnticipo( $importe );
					break;

				case 'txtFondoGarantia':
					$transaccion->setImporteFondoGarantia( $importe );
					break;

				case 'txtRetencionIVA':
					$transaccion->setImporteRetencionIVA( $importe );
					break;

				case 'txtAnticipoLiberar':
					$transaccion->setImporteAnticipoLiberar( $importe );
					break;

				default:
					throw new Exception("Total no válido");
			}

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

			$deductivas = $transaccion->getDeductivas();

			foreach ( $deductivas as $deductiva ) {
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
					'precio_descuento'	 	 => $descuento->getPrecio() === 0 ? $deductiva->getPrecio() : $descuento->getPrecio(),
					'importe_descuento'	 	 => Util::formatoNumerico($descuento->getImporte())
				);
			}
			break;

		case 'guardaDescuento':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];
			$descuentos = $_POST['descuentos'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			$desc = array();
			foreach ( $descuentos as $key => $item ) {
				$descuento = new EstimacionDescuento( 
					$transaccion, 
					new EstimacionDeductiva( $transaccion->empresa, $item['id_item'] )
				);

				$descuento->setCantidad( $item['cantidad_descuento'] );
				$descuento->setPrecio( $item['precio_descuento'] );
				$descuento->save();
				// echo $descuento;
				
				$desc[$item['id_item']] = array(
					'id_descuento' => $descuento->getId(),
					'cantidad_descuento' => $item['cantidad_descuento'],
					'precio_descuento'   => $item['precio_descuento'],
					'importe_descuento'  => $descuento->getImporte()
				);
			}

			$data['descuentos'] = $desc;
			break;

		case 'guardaDeductiva':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];
			$id_material    = (int) $_POST['id_material'];

			$data['IDDeductiva'] = null;

			$IDTipoDeductiva = (int) $_POST['IDTipoDeductiva'];

			$importe       = Util::limpiaImporte($_POST['importe']);
			$concepto      = $_POST['concepto'];
			$observaciones = $_POST['observaciones'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );

			switch( $IDTipoDeductiva ) {

				case 1:
					$data['IDDeductiva'] = 
						$transaccion->agregaDeductivaMaterial( $id_material, $importe, $observaciones );
					break;
				case 2:
					$data['IDDeductiva'] =
						$transaccion->agregaDeductivaManoObra( $id_material, $importe, $observaciones );
					break;
				case 3:
					$data['IDDeductiva'] =
						$transaccion->agregaDeductivaMaquinaria( $id_material, $importe, $observaciones );
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
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];
			$IDDeductiva 	= (int) $_POST['IDDeductiva'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			$transaccion->eliminaDeductiva( $IDDeductiva );
			break;

		case 'getRetenciones':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );
			$id_transaccion = (int) $_GET['id_transaccion'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );
			$data['retenciones'] = array();
			$data['liberaciones'] = array();

			$retenciones = $transaccion->getRetenciones();

			foreach ( $retenciones as $retencion ) {
				$data['retenciones'][] = array(
					'IDRetencion'   => $retencion->IDRetencion,
					'TipoRetencion' => $retencion->TipoRetencion,
					'concepto'      => $retencion->Concepto,
					'importe'       => Util::formatoNumerico( $retencion->Importe ),
					'observaciones' => $retencion->Observaciones
				);
			}

			$liberaciones = $transaccion->getLiberaciones();

			foreach ( $liberaciones as $liberacion ) {
				$data['liberaciones'][] = array(
					'IDLiberacion'   => $liberacion->IDLiberacion,
					'importe'       => Util::formatoNumerico($liberacion->Importe),
					'observaciones' => $liberacion->Observaciones
				);
			}
			break;

		case 'getTiposRetencion':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );

			$data['options'] = array();
			$data['options'] = EstimacionSubcontrato::getTiposRetencion( $conn );
			break;

		case 'guardaRetencion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];

			$data['IDRetencion'] = null;

			$IDTipoRetencion = (int) $_POST['IDTipoRetencion'];
			$importe 		 = Util::limpiaImporte( $_POST['importe'] );
			$concepto 		 = $_POST['concepto'];
			$observaciones 	 = $_POST['observaciones'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );

			$data['IDRetencion'] = $transaccion->agregaRetencion( $IDTipoRetencion, $importe, $concepto, $observaciones );
			break;

		case 'eliminaRetencion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];

			$IDRetencion 	 = (int) $_POST['IDRetencion'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );

			$transaccion->eliminaRetencion( $IDRetencion );
			break;

		case 'getImportePorLiberar':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );
			$id_transaccion = (int) $_GET['id_transaccion'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );

			$data['importePorLiberar'] = 0;

			$data['importePorLiberar'] = Util::formatoNumerico( $transaccion->getImporteRetenidoPorLiberar() );
			break;
		
		case 'guardaLiberacion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];

			$data['IDLiberacion'] = null;

			$importe 	   = Util::limpiaImporte( $_POST['importe'] );
			$observaciones = $_POST['observaciones'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );

			$data['IDLiberacion'] =	$transaccion->agregaLiberacion(
					$importe,
					$observaciones,
					Sesion::getUser()
				);
			break;

		case 'eliminaLiberacion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );
			$id_transaccion = (int) $_POST['id_transaccion'];

			$IDLiberacion   = (int) $_POST['IDLiberacion'];

			$transaccion = new EstimacionSubcontrato( $obra, $id_transaccion );

			$transaccion->eliminaLiberacion( $IDLiberacion );
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