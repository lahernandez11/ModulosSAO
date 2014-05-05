<?php
require_once 'models/FormatoPDF.class.php';
require_once 'models/Util.class.php';

class EstimacionSubcontratoFormatoPDF extends FormatoPDF {

	const FORMATO_NOMBRE_ARCHIVO = 'EstimacionSubcontrato.pdf';
	const PDF_PAGE_ORIENTATION = 'L';

	protected $titulo = 'ESTIMACIÓN DE OBRA EJECUTADA';
	protected $organizacion_label = 'Organización:';
	protected $contratista_label = 'Contratista:';
	protected $numero_de_estimacion_label = 'No. Estimación';
	protected $semana_de_contrato_label = 'Semana de Contrato';
	protected $numero_de_contrato_label = 'No. de Contrato:';
	protected $firma_contratista_titulo_label = 'por el contratista';
	protected $firma_contratista_descripcion_label = 'factor o dependiente';
	protected $firma_cliente_titulo_label = 'por el cliente';
	protected $firma_cliente_descripcion_label = 'gerente de proyecto';

	private $estimacion;
	private $soloConceptosEstimados = 1;

	private $sumaImporteContrato			 = 0;
	private $sumaAcumuladoEstimacionAnterior = 0;
	private $sumaImporteEstaEstimacion		 = 0;
	private $sumaAcumuladoEstaEstimacion	 = 0;
	private $sumaSaldoPendiente				 = 0;

	private $sumaImporteCargoMaterial 			  = 0;	// suma total de importe de cargos de materiales del contratista
	private $sumaAcumuladoDescuentoAnterior 	  = 0;	// suma acumulada de importe de descuentos aplicados anteriores a esta estimacion
	private $sumaImporteDescuentoEstaEstimacion   = 0;	// suma total de importe de descuentos aplicados en esta estimacion
	private $sumaAcumuladoDescuentoEstaEstimacion = 0;	// suma acumulada de importe de descuentos aplicados a esta estimacion
	private $sumaSaldoPorDescontar 				  = 0;	// suma del saldo total por descontar al contratista


	private $suma_retenciones   = 0;	// suma total de retenciones aplicadas en esta estimacion
	private $suma_liberaciones  = 0;	// suma total de liberaciones aplicadas en esta estimacion

	public function __construct( EstimacionSubcontrato $estimacion, $soloConceptosEstimados = 1 ) {
		parent::__construct();

		$this->estimacion = $estimacion;
		$this->soloConceptosEstimados = $soloConceptosEstimados;
	}

	private function writeDatosGeneralesEstimacion() {

		$this->AddPage( self::PDF_PAGE_ORIENTATION );
		$this->SetFont('Arial', '', 7);

		$printBorder = 1;

		// $this->Cell(80, 20, "LOGO PROYECTO", $printBorder);
		// $this->Ln();
		$this->SetFontSize(18);
		$this->setFontStyle("B");
		$this->setFillColorGHI();
		// $this->SetX($this->GetX() + 20);
		$this->Cell(200, 30, $this->titulo, 0, 0, "C");
		$this->resetFontSize();
		$this->resetTextColor();
		
		
		$labelCellWidth = 40;
		$dataCellWidth = 40;
		$cellHeight = 6;

		$this->SetFontSize(12);
		
		// $this->SetXY($xPosData + 200, 0);
		$xPosData = $this->GetX();
		$this->setFontStyle("B");
		$this->Cell($labelCellWidth, $cellHeight, "Folio SAO", $printBorder);
		$this->Cell(0, $cellHeight, Util::formatoNumeroFolio($this->estimacion->getNumeroFolio()), $printBorder, 0, 'R');
		$this->Ln($cellHeight);

		$this->SetX($xPosData);
		$this->setFontStyle("B");
		$this->Cell($labelCellWidth, $cellHeight, $this->numero_de_estimacion_label, $printBorder);
		$this->Cell(0, $cellHeight, $this->estimacion->getNumeroFolioConsecutivo(), $printBorder, 0, 'R');
		$this->Ln($cellHeight);

		$this->SetFontSize(9);

		$this->SetX($xPosData);
		$this->setFontStyle("B");
		$this->Cell($labelCellWidth, $cellHeight, $this->semana_de_contrato_label, $printBorder);
		$this->resetFontStyle();
		$this->Cell(0, $cellHeight, "", $printBorder, 0, 'R');
		$this->Ln($cellHeight);

		$this->SetX($xPosData);
		$this->setFontStyle("B");
		$this->Cell($labelCellWidth, $cellHeight, "Fecha", $printBorder);
		$this->resetFontStyle();
		$this->Cell(0, $cellHeight, Util::formatoFecha($this->estimacion->getFecha()), $printBorder, 0, 'R');
		$this->Ln($cellHeight);

		$this->SetX($xPosData);
		$this->setFontStyle("B");
		$this->Cell($labelCellWidth, $cellHeight, "Periodo", $printBorder);
		$this->resetFontStyle();
		$this->Cell(0, $cellHeight, 
			  "De: " . Util::formatoFecha($this->estimacion->getFechaInicio())
			. " A: " . Util::formatoFecha($this->estimacion->getFechaTermino()),
			$printBorder, 0, 'R'
		);
		$this->Ln(8);

		$this->SetFontSize(10);

		$this->setFontStyle("B");
		$this->Cell(80, 5, $this->organizacion_label, $printBorder);
		$this->Cell(0, 5, $this->estimacion->obra->getNombre(), $printBorder, 0, "C");
		$this->Ln();

		$this->Cell(80, 5, $this->contratista_label, $printBorder, 0, "L");
		$this->Cell(0, 5, $this->estimacion->empresa->getNombre(), $printBorder, 0, "C");
		$this->Ln();

		$this->Cell(80, 5, $this->numero_de_contrato_label, $printBorder, 0, "L");
		$this->Cell(0, 5, "", $printBorder, 0, "C");
		$this->Ln(10);
	}

	private function writeConceptosEstimados() {

		// -------------------------------------------------------------------
		// ------ Encabezados de la lista de conceptos estimados
		// -------------------------------------------------------------------
		$this->SetFontSize(7);
		$printBorder = 1;
		$cellHeight = 10;
		$textCenterAlign = "C";
		$colSpansWidth = 5;

		$headerCols = array();
		$headerCols[] = array('label' => 'Concepto', 'cellWidth' => 80, 'cellHeight' => $cellHeight);
		$headerCols[] = array('label' => 'U.M.', 'cellWidth' => 17, 'cellHeight' => $cellHeight);
		$headerCols[] = array('label' => 'P.U.', 'cellWidth' => 15, 'cellHeight' => $cellHeight);
		$headerCols[] = array('label' => 'Contrato y Aditamentos', 'cellWidth' => 35, 'cellHeight' => $colSpansWidth);
		$headerCols[] = array('label' => 'Acum. A Estimacion Anterior', 'cellWidth' => 35, 'cellHeight' => $colSpansWidth);
		$headerCols[] = array('label' => 'Esta Estimación', 'cellWidth' => 35, 'cellHeight' => $colSpansWidth);
		$headerCols[] = array('label' => 'Acum. A Esta Estimación', 'cellWidth' => 35, 'cellHeight' => $colSpansWidth);
		$headerCols[] = array('label' => 'Saldo por Estimar', 'cellWidth' => 35, 'cellHeight' => $colSpansWidth);

		$lastXPosition = 0;
		$lastYPosition = 0;
		$pctCantidadWidth = 40;
		$pctImporteWidth = 60;
		$this->setFontStyle("B");
		$this->setFillColorDefault();

		foreach ( $headerCols as $key => $formatParams ) {

			$lastXPosition = $this->GetX();
			$lastYPosition = $this->GetY();

			$this->Cell(
				$formatParams['cellWidth'],
				$formatParams['cellHeight'],
				$formatParams['label'],
				$printBorder, 0, $textCenterAlign, true
			);

			if ( $key >= 3 ) {
				$this->SetXY($lastXPosition, $lastYPosition + $colSpansWidth);
				$this->Cell(
					( ($pctCantidadWidth * $formatParams['cellWidth']) / 100 ),
					($cellHeight - $formatParams['cellHeight']),
					"Cantidad",
					$printBorder, 0, $textCenterAlign, true
				);
				$this->Cell(
					( ($pctImporteWidth * $formatParams['cellWidth']) / 100 ),
					($cellHeight - $formatParams['cellHeight']),
					"Importe",
					$printBorder, 0, $textCenterAlign, true
				);
				$this->SetXY($this->GetX(), $lastYPosition);
			}
		}
		$this->resetFontStyle();
		$this->Ln(10);

		// -------------------------------------------------------------------
		// ------ Fila con datos de la moneda utilizada en la estimacion
		// -------------------------------------------------------------------
		$cellWidth = $headerCols[0]['cellWidth'] + $headerCols[1]['cellWidth'];
		$cellHeight = 5;

		$this->setFontStyle("B");
		$this->Cell($cellWidth , $cellHeight, "OBRA EJECUTADA");
		$this->resetFontStyle();
		$this->setFontSize(5);

		$this->Cell(
			$headerCols[2]['cellWidth'],
			$cellHeight,
			$this->estimacion->moneda->getAbreviatura(),
			0, 0, $textCenterAlign
		);

		for ( $i = 3; $i < count($headerCols); $i++ ) { 
			
			$this->Cell(
				( ($pctCantidadWidth * $headerCols[$i]['cellWidth']) / 100 ),
				$cellHeight,
				"",
				0, 0, $textCenterAlign
			);
			$this->Cell(
				( ($pctImporteWidth * $headerCols[$i]['cellWidth']) / 100 ),
				$cellHeight,
				$this->estimacion->moneda->getAbreviatura(),
				0, 0, $textCenterAlign
			);
		}
		$this->Ln();

		// -------------------------------------------------------------------
		// ------ Lista de conceptos estimados
		// -------------------------------------------------------------------
		$this->setCellHeight(3);
		$this->setAligns(
			array("L", "C", "R")
		);

		$this->SetWidths(
			array( $headerCols[0]['cellWidth'], $headerCols[1]['cellWidth'], $headerCols[2]['cellWidth'],
				( ($pctCantidadWidth * $headerCols[3]['cellWidth']) / 100 ), ( ($pctImporteWidth * $headerCols[3]['cellWidth']) / 100 ),
				( ($pctCantidadWidth * $headerCols[4]['cellWidth']) / 100 ), ( ($pctImporteWidth * $headerCols[4]['cellWidth']) / 100 ),
				( ($pctCantidadWidth * $headerCols[5]['cellWidth']) / 100 ), ( ($pctImporteWidth * $headerCols[5]['cellWidth']) / 100 ),
				( ($pctCantidadWidth * $headerCols[6]['cellWidth']) / 100 ), ( ($pctImporteWidth * $headerCols[6]['cellWidth']) / 100 ),
				( ($pctCantidadWidth * $headerCols[7]['cellWidth']) / 100 ), ( ($pctImporteWidth * $headerCols[7]['cellWidth']) / 100 )
			)
		);

		$conceptos = $this->estimacion->getConceptosEstimados( $this->soloConceptosEstimados );

		foreach ( $conceptos as $concepto ) {
			
			$this->sumaImporteContrato			   += $concepto->ImporteSubcontratado;
			$this->sumaAcumuladoEstimacionAnterior += $concepto->ImporteEstimadoAcumuladoAnterior;
			$this->sumaImporteEstaEstimacion 	   += $concepto->ImporteEstimado;
			$this->sumaAcumuladoEstaEstimacion 	   += $concepto->MontoEstimadoTotal;
			$this->sumaSaldoPendiente 		 	   += $concepto->MontoSaldo;

			$this->Row(
				array(
					$concepto->Descripcion,
					$concepto->Unidad,
					Util::formatoNumerico( $concepto->PrecioUnitario ),
					Util::formatoNumerico( $concepto->CantidadSubcontratada ),
					Util::formatoNumerico( $concepto->ImporteSubcontratado ),
					Util::formatoNumerico( $concepto->CantidadEstimadaAcumuladaAnterior ),
					Util::formatoNumerico( $concepto->ImporteEstimadoAcumuladoAnterior ),
					Util::formatoNumerico( $concepto->CantidadEstimada ),
					Util::formatoNumerico( $concepto->ImporteEstimado ),
					Util::formatoNumerico( $concepto->CantidadEstimadaTotal ),
					Util::formatoNumerico( $concepto->MontoEstimadoTotal ),
					Util::formatoNumerico( $concepto->CantidadSaldo ),
					Util::formatoNumerico( $concepto->MontoSaldo )
				)
			);
		}

		$this->setAligns( array("R") );
		$this->setFontStyle( "B" );
		$this->Row(
			array(
				"Sub-Totales Obra Ejecutada", "", "", "",
				Util::formatoNumerico( $this->sumaImporteContrato ), "",
				Util::formatoNumerico( $this->sumaAcumuladoEstimacionAnterior ), "",
				Util::formatoNumerico( $this->sumaImporteEstaEstimacion ), "",
				Util::formatoNumerico( $this->sumaAcumuladoEstaEstimacion ), "",
				Util::formatoNumerico( $this->sumaSaldoPendiente )
			)
		);

		$this->Ln();
	}

	private function writeDeductivas() {

		// -------------------------------------------------------------------
		// ------ Lista de deductivas
		// -------------------------------------------------------------------
		$this->setFontSize(7);
		$this->setFontStyle("B");
		$this->Cell(0, 5, "DEDUCTIVAS");
		$this->Ln();

		$this->resetFontStyle();
		$this->setFontSize(5);
		$this->setCellHeight(3);
		$this->setAligns( array("L", "C", "R") );

		foreach ( $this->estimacion->empresa->cargos_material as $cargo_material ) {
			$descuento_aplicado = null;
			$cantidad_descontada_anterior = EstimacionDescuentoMaterial::getCantidadDescontadaAnterior( $this->estimacion, $cargo_material->material );
			$importe_descontado_anterior  = EstimacionDescuentoMaterial::getImporteDescontadoAnterior( $this->estimacion, $cargo_material->material );
			$cantidad_por_descontar		  = 0;
			$importe_por_descontar		  = 0;

			foreach ( $this->estimacion->descuentos as $descuento ) {

				if ( $descuento->material->getId() == $cargo_material->material->getId() ) {
					$descuento_aplicado = $descuento;
					break;
				}
			}

			if ( $cantidad_descontada_anterior <= $cargo_material->getCantidad() ) {
				$cantidad_por_descontar = $cargo_material->getCantidad() - $cantidad_descontada_anterior;
				$importe_por_descontar	= $cargo_material->getImporte() - $importe_descontado_anterior;
			}

			$cantidad = 0;
			$precio   = $cargo_material->getPrecio();
			$importe  = 0;
			
			if ( ! is_null( $descuento_aplicado ) ) {
				$cantidad = $descuento_aplicado->getCantidad();
				$precio   = $descuento_aplicado->getPrecio();
				$importe  = $descuento_aplicado->getImporte();
			}

			$this->Row(
				array(
					$cargo_material->material->getDescripcion(),
					$cargo_material->material->getUnidad(),
					Util::formatoNumerico( $cargo_material->getPrecio() ),
					Util::formatoNumerico( $cargo_material->getCantidad() ),
					Util::formatoNumerico( $cargo_material->getImporte() ),
					Util::formatoNumerico( $cantidad_descontada_anterior ),
					Util::formatoNumerico( $importe_descontado_anterior ),
					Util::formatoNumerico( $cantidad ),
					Util::formatoNumerico( $importe ),
					Util::formatoNumerico(
						  $cantidad_descontada_anterior
						+ $cantidad
					),
					Util::formatoNumerico(
						  $importe_descontado_anterior
						+ $importe
					),
					Util::formatoNumerico( $cantidad_por_descontar ),
					Util::formatoNumerico( $importe_por_descontar )
				)
			);

			$this->sumaImporteCargoMaterial 		    += $cargo_material->getImporte();
			$this->sumaAcumuladoDescuentoAnterior       += $importe_descontado_anterior;
			$this->sumaImporteDescuentoEstaEstimacion   += $importe;
			$this->sumaAcumuladoDescuentoEstaEstimacion	+= $importe_descontado_anterior + $importe;
			$this->sumaSaldoPorDescontar 				+= $importe_por_descontar;
		}

		$this->setAligns(array("R"));
		$this->setFontStyle("B");
		$this->Row(
			array(
				"Sub-Totales Deductivas", "", "", "",
				Util::formatoNumerico( $this->sumaImporteCargoMaterial ),
				"",
				Util::formatoNumerico( $this->sumaAcumuladoDescuentoAnterior ), "",
				Util::formatoNumerico( $this->sumaImporteDescuentoEstaEstimacion ),	"",
				Util::formatoNumerico( $this->sumaAcumuladoDescuentoEstaEstimacion ), "",
				Util::formatoNumerico(
					  $this->sumaImporteCargoMaterial
					- $this->sumaAcumuladoDescuentoEstaEstimacion
				)
			)
		);
		$this->Ln();
	}

	private function writeRetenciones() {
		// -------------------------------------------------------------------
		// ------ Lista de retenciones
		// -------------------------------------------------------------------
		$this->setFontSize(7);
		$this->setFontStyle( "B" );
		$this->Cell( 0, 5, "RETENCIONES" );
		$this->Ln();

		$this->resetFontStyle();
		$this->setFontSize(5);
		$this->setCellHeight(3);
		$this->setAligns( array("L", "R") );
		
		foreach ( $this->estimacion->retenciones as $retencion ) {
			
			$this->suma_retenciones += $retencion->getImporte();

			$this->Row(
				array(
					$retencion->getConcepto(), "", "", "", "", "", "", "",
					Util::formatoNumerico( $retencion->getImporte() ), "", "", "", ""
				)
			);
		}

		$this->setAligns( array("R") );
		$this->setFontStyle( "B" );
		$this->Row(
			array(
				"Sub-Totales Retenciones", "", "", "",
				Util::formatoNumerico( $this->estimacion->subcontrato->getImporteAcumuladoRetenciones() ),
				"",
				Util::formatoNumerico(
					  $this->estimacion->empresa->getImporteTotalRetenido()
				),
				"",
				Util::formatoNumerico( $this->suma_retenciones ), "",
				Util::formatoNumerico(
					  $this->estimacion->empresa->getImporteTotalRetenido()
					+ $this->suma_retenciones
				),
				"", ""
			)
		);
		$this->Ln();
	}

	private function writeTotales() {

		$totales = $this->estimacion->getTotalesTransaccion();

		$this->setFontSize(7);
		$this->setFontStyle("B");
		$this->Cell(0, 5, "RESUMEN");
		$this->Ln();
		$this->setFontSize(5);
		$this->setCellHeight(3);

		$this->setAligns( array("L", "C", "R") );

		$this->Row(
			array(
				"Importe asociado a trabajos ejecutados", "", "", "",
				Util::formatoNumerico( $this->estimacion->subcontrato->getSubtotal() ), "",
				Util::formatoNumerico( $this->sumaAcumuladoEstimacionAnterior ), "",
				Util::formatoNumerico( $this->sumaImporteEstaEstimacion ), "",
				Util::formatoNumerico( $this->sumaAcumuladoEstaEstimacion ), "",
				Util::formatoNumerico( $this->sumaSaldoPendiente )
			)
		);

		$this->Row(
			array(
				"Anticipo", "%", Util::formatoPorcentaje( $this->estimacion->getPctAnticipo() ), "",
				"", "", "", "",	"", "", "", "", ""
			)
		);

		$this->Row(
			array(
				"Amortización Anticipo", "", "", "",
				Util::formatoNumerico( $this->estimacion->subcontrato->getImporteAnticipo() ), "",
				Util::formatoNumerico( $totales['amortizacion_anticipo_acumulado_anterior'] ), "",
				Util::formatoNumerico( $totales['amortizacion_anticipo'] ), "",
				Util::formatoNumerico(
					  $totales['amortizacion_anticipo_acumulado_anterior']
					+ $totales['amortizacion_anticipo']
				), "",
				Util::formatoNumerico(
					  $this->estimacion->subcontrato->getImporteAnticipo()
					- $totales['amortizacion_anticipo_acumulado_anterior']
					- $totales['amortizacion_anticipo']
				)
			)
		);

		$this->Row(
			array(
				"Fondo de Garantia", "%", Util::formatoPorcentaje( $this->estimacion->getPctFondoGarantia() ), "",
				Util::formatoNumerico( $this->estimacion->subcontrato->getImporteFondoGarantia() ), "",
				Util::formatoNumerico( $totales['fondo_garantia_acumulado_anterior'] ), "",
				Util::formatoNumerico( $totales['fondo_garantia'] ), "",
				Util::formatoNumerico(
					  $totales['fondo_garantia_acumulado_anterior']
					+ $totales['fondo_garantia']
				), "",
				Util::formatoNumerico(
					  $this->estimacion->subcontrato->getImporteFondoGarantia()
					- $totales['fondo_garantia_acumulado_anterior']
					- $totales['fondo_garantia']
				)
			)
		);

		$subtotal = 0;
		$subtotal = 
			  $this->sumaImporteEstaEstimacion
			- $totales['amortizacion_anticipo']
			- $totales['fondo_garantia'];

		$this->Row(
			array(
				"Sub-total valor de los trabajos", "", "", "",
				Util::formatoNumerico(
					  $this->estimacion->subcontrato->getSubtotal()
					- $this->estimacion->subcontrato->getImporteAnticipo()
					- $this->estimacion->subcontrato->getImporteFondoGarantia()
				), "",
				Util::formatoNumerico(
					  $this->sumaAcumuladoEstimacionAnterior
					- $totales['amortizacion_anticipo_acumulado_anterior']
					- $totales['fondo_garantia_acumulado_anterior']
				), "",
				Util::formatoNumerico( $subtotal ), "",
				Util::formatoNumerico(
					  $subtotal
					+ (
						  $this->sumaAcumuladoEstimacionAnterior
						- $totales['amortizacion_anticipo_acumulado_anterior']
						- $totales['fondo_garantia_acumulado_anterior']
					  )
				), "",
				0
			)
		);

		$this->Row(
			array(
				"IVA", "%", Util::formatoPorcentaje( $this->estimacion->getPctIVA() ), "",
				Util::formatoNumerico( $this->estimacion->subcontrato->getIVA() ), "",
				Util::formatoNumerico( $totales['iva_acumulado_anterior'] ), "",
				Util::formatoNumerico( $totales['iva'] ), "",
				Util::formatoNumerico(
					  $totales['iva_acumulado_anterior']
					+ $totales['iva']
				), "",
				Util::formatoNumerico(
					  $this->estimacion->subcontrato->getIVA()
					- $totales['iva_acumulado_anterior']
					- $totales['iva']
				)
			)
		);

		$this->Row(
			array(
				"Total", $this->estimacion->moneda->getNombre(),
				"", "",
				Util::formatoNumerico(
					(
					  	  $this->estimacion->subcontrato->getSubtotal()
						- $this->estimacion->subcontrato->getImporteAnticipo()
						- $this->estimacion->subcontrato->getImporteFondoGarantia()
					) * ( 1 + $this->estimacion->getPctIVA() )
				), "",
				Util::formatoNumerico(
					  $this->sumaAcumuladoEstimacionAnterior
					- $totales['amortizacion_anticipo_acumulado_anterior']
					- $totales['fondo_garantia_acumulado_anterior']
					+ $totales['iva_acumulado_anterior']

				), "",
				Util::formatoNumerico(
					  $this->sumaImporteEstaEstimacion
					- $totales['amortizacion_anticipo']
					- $totales['fondo_garantia']
					+ $totales['iva']
				), "",
				Util::formatoNumerico(
					  $totales['iva_acumulado_anterior']
					+ $totales['iva']
				), "",
				Util::formatoNumerico(
					  $this->estimacion->subcontrato->getImporteAnticipo()
					- $totales['iva_acumulado_anterior']
					- $totales['iva']
				)
			)
		);

		$total = 0;
		$total += $subtotal + $totales['iva'];

		$this->Row(
			array(
				"Retención de IVA", "", "", "", "", "",
				Util::formatoNumerico( $totales['iva_retenido_acumulado_anterior'] ), "",
				Util::formatoNumerico( $totales['retencion_iva'] ), "",
				Util::formatoNumerico(
					  $totales['iva_retenido_acumulado_anterior']
					+ $totales['retencion_iva']
				), "",
				0
			)
		);

		$total -= $totales['retencion_iva'];

		$this->Row(
			array(
				"Descuentos", "", "", "",
				Util::formatoNumerico( $this->estimacion->empresa->getImporteAcumuladoCargos() ),
				"",
				Util::formatoNumerico( $totales['descuento_acumulado_anterior'] ), "",
				Util::formatoNumerico( $this->sumaImporteDescuentoEstaEstimacion ), "",
				Util::formatoNumerico(
					  $totales['descuento_acumulado_anterior']
					+ $this->sumaImporteDescuentoEstaEstimacion
				), "",
				Util::formatoNumerico(
					  $this->estimacion->empresa->getImporteAcumuladoCargos()
					- $totales['descuento_acumulado_anterior']
					- $this->sumaImporteDescuentoEstaEstimacion
				)
			)
		);

		$total -= $this->sumaImporteDescuentoEstaEstimacion;

		$this->Row(
			array(
				"Retenciones", "", "", "",
				Util::formatoNumerico( $this->estimacion->subcontrato->getImporteAcumuladoRetenciones() ),
				"",
				Util::formatoNumerico( $totales['retencion_acumulada_anterior'] ), "",
				Util::formatoNumerico( $this->suma_retenciones ), "",
				Util::formatoNumerico(
					  $totales['retencion_acumulada_anterior']
					+ $this->suma_retenciones
				), "",
				Util::formatoNumerico(
					  $this->estimacion->subcontrato->getImporteAcumuladoRetenciones()
					-  $totales['retencion_acumulada_anterior']
					- $this->suma_retenciones
				)
			)
		);

		$total -= $this->suma_retenciones;

		$this->Row(
			array(
				"Anticipo a Liberar", "", "", "",
				"", "",
				0, "",
				Util::formatoNumerico( $this->estimacion->getAnticipoLiberar() ), "",
				Util::formatoNumerico(
					$this->estimacion->getAnticipoLiberar()
				), "",
				0
			)
		);

		$total += $this->estimacion->getAnticipoLiberar();


		foreach ( $this->estimacion->liberaciones as $liberacion ) {
			$this->suma_liberaciones += $liberacion->getImporte();
		}

		$this->Row(
			array(
				"Retenciones Liberadas", "", "", "",
				"", "",
				0, "",
				Util::formatoNumerico( $this->suma_liberaciones ),
				"",
				0, "",
				0
			)
		);

		$total += $this->suma_liberaciones;

		$this->Row(
			array(
				"Total pagado y/o a pagar", $this->estimacion->moneda->getNombre(),
				"", "",
				Util::formatoNumerico(
					(
					  	  $this->estimacion->subcontrato->getSubtotal()
						- $this->estimacion->subcontrato->getImporteAnticipo()
						- $this->estimacion->subcontrato->getImporteFondoGarantia()
					) * ( 1 + $this->estimacion->getPctIVA() )
					- $this->estimacion->subcontrato->getImporteAcumuladoRetenciones()
					- $this->estimacion->empresa->getImporteAcumuladoCargos()
				), "",
				0, "",
				Util::formatoNumerico( $total ), "",
				0, "",
				0
			)
		);
	}

	public function footer() {
		$this->Ln(5);
		$this->resetFontStyle();
		$this->SetFontSize(7);
		$this->Cell(70, 3, "", 0, 0);
		$this->Cell(50, 3, $this->firma_contratista_titulo_label, 1, 0, "C", true);
		$this->Cell(50, 3, "", 0, 0);
		$this->Cell(50, 3, $this->firma_cliente_titulo_label, 1, 0, "C", true);
		$this->Ln();
		$this->Cell(70, 3, "", 0, 0);
		$this->Cell(50, 8, "", 1, 0, "C");
		$this->Cell(50, 8, "", 0, 0);
		$this->Cell(50, 8, "", 1, 0, "C");
		$this->Ln();
		$this->Cell(70, 3, "", 0, 0);
		$this->Cell(50, 3, $this->firma_contratista_descripcion_label, 1, 0, "C", true);
		$this->Cell(50, 3, "", 0, 0);
		$this->Cell(50, 3, $this->firma_cliente_descripcion_label, 1, 0, "C", true);
	}

	public function Output( $nombre=null, $i='I' ) {

		$this->writeDatosGeneralesEstimacion();
		$this->writeConceptosEstimados();
		$this->writeDeductivas();
		$this->writeRetenciones();
		$this->writeTotales();

		parent::Output( self::FORMATO_NOMBRE_ARCHIVO, 'I' );
	}
}
?>