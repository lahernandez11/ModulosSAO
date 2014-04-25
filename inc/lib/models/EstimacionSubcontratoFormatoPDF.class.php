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
		$conceptos = $this->estimacion->getConceptosEstimados( $this->soloConceptosEstimados );
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

		$sumaContrato = 0;
		$acumuladoEstimacionAnterior = 0;
		$sumaEstaEstimacion = 0;
		$acumuladoEstaEstimacion = 0;
		$sumaSaldoPendiente = 0;

		foreach ( $conceptos as $concepto ) {
			
			$sumaContrato 				 += $concepto->ImporteSubcontratado;
			$acumuladoEstimacionAnterior += $concepto->ImporteEstimadoAcumuladoAnterior;
			$sumaEstaEstimacion 		 += $concepto->ImporteEstimado;
			$acumuladoEstaEstimacion 	 += $concepto->MontoEstimadoTotal;
			$sumaSaldoPendiente 		 += $concepto->MontoSaldo;

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
				Util::formatoNumerico( $sumaContrato ), "",
				Util::formatoNumerico( $acumuladoEstimacionAnterior ), "",
				Util::formatoNumerico( $sumaEstaEstimacion ), "",
				Util::formatoNumerico( $acumuladoEstaEstimacion ), "",
				Util::formatoNumerico( $sumaSaldoPendiente )
			)
		);

		$this->Ln();

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
		
		$sumaDeductivas = 0;
		$subtotalDescuento = 0;
		$subtotalDescuentoAcumuladoAnterior = 0;
		$subtotalDescuentoAcumuladoActual = 0;
		$subtotalDescuentoSaldo = 0;

		$deductivas = $this->estimacion->getDeductivas();

		foreach ( $deductivas as $deductiva ) {
			$descuento = $deductiva->getDescuento( $this->estimacion );

			$cantidad_por_descontar = 
				  $deductiva->getCantidadTotal()
				- $descuento->getCantidadDescontada();

			if ( $cantidad_por_descontar < 0 ) { $cantidad_por_descontar = 0; }

			$importe_por_descontar = $deductiva->getImporteTotal()
				- $descuento->getImporteDescontado();

			if ( $importe_por_descontar < 0 ) {	$importe_por_descontar = 0; }

			$this->Row(
				array(
					$deductiva->material->getDescripcion(),
					$deductiva->getUnidad(),
					Util::formatoNumerico( $deductiva->getPrecio() ),
					Util::formatoNumerico( $deductiva->getCantidadTotal() ),
					Util::formatoNumerico( $deductiva->getImporteTotal() ),
					Util::formatoNumerico( $descuento->getCantidadDescontada() ),
					Util::formatoNumerico( $descuento->getImporteDescontado() ),
					Util::formatoNumerico( $descuento->getCantidad() ),
					Util::formatoNumerico( $descuento->getImporte() ),
					Util::formatoNumerico(
						  $descuento->getCantidadDescontada()
						+ $descuento->getCantidad()
					),
					Util::formatoNumerico(
						  $descuento->getImporteDescontado()
						+ $descuento->getImporte()
					),
					Util::formatoNumerico( $cantidad_por_descontar ),
					Util::formatoNumerico( $importe_por_descontar )
				)
			);

			$sumaDeductivas += $deductiva->getImporteTotal();
			$subtotalDescuentoAcumuladoAnterior += $descuento->getImporteDescontado();
			$subtotalDescuento += $descuento->getImporte();
			$subtotalDescuentoAcumuladoActual += $descuento->getImporteDescontado() + $descuento->getImporte();
			$subtotalDescuentoSaldo += $importe_por_descontar;
		}

		$this->setAligns(array("R"));
		$this->setFontStyle("B");
		$this->Row(
			array(
				"Sub-Totales Deductivas", "", "", "",
				Util::formatoNumerico( $sumaDeductivas ),
				"",
				Util::formatoNumerico( $subtotalDescuentoAcumuladoAnterior ), "",
				Util::formatoNumerico( $subtotalDescuento ),	"",
				Util::formatoNumerico( $subtotalDescuentoAcumuladoActual ), "",
				Util::formatoNumerico( $subtotalDescuentoSaldo )
			)
		);
		$this->Ln();

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
		$sumaRetenciones = 0;

		$retenciones = $this->estimacion->getRetenciones();
		
		foreach ( $retenciones as $retencion ) {
			
			$sumaRetenciones += $retencion->Importe;

			$this->Row(
				array(
					$retencion->Concepto, "", "", "", "", "", "", "",
					Util::formatoNumerico( $retencion->Importe ), "", "", "", ""
				)
			);
		}

		$this->setAligns( array("R") );
		$this->setFontStyle( "B" );
		$this->Row(
			array(
				"Sub-Totales Retenciones", "", "", "",
				Util::formatoNumerico( $this->estimacion->subcontrato->getImporteAcumuladoRetenciones() ),
				"", "", "",
				Util::formatoNumerico( $sumaRetenciones ), "",
				Util::formatoNumerico(
					$this->estimacion->subcontrato->getImporteAcumuladoRetenciones()
					+ $sumaRetenciones
				), "", ""
			)
		);
		$this->Ln();

		$totales = $this->estimacion->getTotalesTransaccion();

		$this->setFontSize(7);
		$this->setFontStyle("B");
		$this->Cell(0, 5, "RESUMEN");
		$this->Ln();
		$this->setFontSize(5);
		$this->setCellHeight(3);

		$this->Row(
			array(
				"Importe asociado a trabajos ejecutados", "", "", "",
				Util::formatoNumerico( $this->estimacion->subcontrato->getSubtotal() ), "",
				Util::formatoNumerico( $acumuladoEstimacionAnterior ), "",
				Util::formatoNumerico( $sumaEstaEstimacion - $sumaDeductivas - $sumaRetenciones ), "",
				Util::formatoNumerico( $acumuladoEstaEstimacion ), "",
				Util::formatoNumerico( $sumaSaldoPendiente )
			)
		);

		$this->Row(
			array(
				"Anticipo", "%", Util::formatoNumerico( $this->estimacion->getPctAnticipo() ), "",
				Util::formatoNumerico( $this->estimacion->subcontrato->getImporteAnticipo() ), "", "", "",
				Util::formatoNumerico( $totales['anticipo_liberar'] ), "",
				0, "",
				0
			)
		);

		$this->Row(
			array(
				"Amortización Anticipo", "", "", "",
				Util::formatoNumerico( $this->estimacion->subcontrato->getImporteAcumuladoAnticipo() ), "",
				Util::formatoNumerico( $totales['ImporteAcumuladoAnticipoAnterior'] ), "",
				Util::formatoNumerico( $totales['amortizacion_anticipo'] ), "",
				Util::formatoNumerico(
					  $totales['ImporteAcumuladoAnticipoAnterior']
					+ $totales['amortizacion_anticipo']
				), "",
				0
			)
		);

		$this->Row(
			array(
				"Fondo de Garantia", "%", Util::formatoNumerico($this->estimacion->getPctFondoGarantia() ), "",
				Util::formatoNumerico( $this->estimacion->subcontrato->getImporteFondoGarantia() ), "",
				Util::formatoNumerico( $totales['ImporteAcumuladoFondoGarantiaAnterior'] ), "",
				Util::formatoNumerico( $totales['fondo_garantia']), "",
				Util::formatoNumerico(
					  $totales['ImporteAcumuladoFondoGarantiaAnterior']
					+ $totales['fondo_garantia']
				), "",
				0
			)
		);

		$subtotal = 0;
		$subtotal = $sumaEstaEstimacion - $sumaDeductivas - $sumaRetenciones 
			- $totales['amortizacion_anticipo']
			- $totales['fondo_garantia'];

		$this->Row(
			array(
				"Sub-total valor de los trabajos", "", "", "",
				Util::formatoNumerico( $this->estimacion->subcontrato->getSubtotal() ), "",
				Util::formatoNumerico(
					  $acumuladoEstimacionAnterior
					- $totales['ImporteAcumuladoAnticipoAnterior']
					- $totales['ImporteAcumuladoFondoGarantiaAnterior']
				), "",
				Util::formatoNumerico($subtotal), "",
				Util::formatoNumerico(
					  $subtotal
					+ (
						  $acumuladoEstimacionAnterior
						- $totales['ImporteAcumuladoAnticipoAnterior']
						- $totales['ImporteAcumuladoFondoGarantiaAnterior']
					  )
				), "",
				0
			)
		);

		$this->Row(
			array(
				"IVA", "%", Util::formatoNumerico($this->estimacion->getPctIVA() ), "",
				Util::formatoNumerico( $this->estimacion->subcontrato->getIVA() ), "",
				Util::formatoNumerico( $totales['IVAAcumuladoAnterior'] ), "",
				Util::formatoNumerico( $totales['iva'] ), "",
				Util::formatoNumerico(
					  $totales['IVAAcumuladoAnterior']
					+ $totales['iva']
				), "",
				0
			)
		);

		$total = 0;
		$total += $subtotal + $totales['iva'];

		$this->Row(
			array(
				"Retención de IVA", "", "", "", "", "",
				0, "",
				Util::formatoNumerico( $totales['retencion_iva'] ), "",
				Util::formatoNumerico(
					$totales['retencion_iva']
				), "",
				0
			)
		);

		$total -= $totales['retencion_iva'];

		$this->Row(
			array(
				"Total pagado y/o a pagar", "", "", "",
				Util::formatoNumerico( $this->estimacion->subcontrato->getTotal() ), "",
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

		parent::Output( self::FORMATO_NOMBRE_ARCHIVO, 'I' );
	}
}
?>