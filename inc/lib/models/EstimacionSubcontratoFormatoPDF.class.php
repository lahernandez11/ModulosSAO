<?php
require_once 'models/FormatoPDF.class.php';
require_once 'models/Util.class.php';

class EstimacionSubcontratoFormatoPDF extends FormatoPDF {

	const TITULO = 'ESTIMACIÓN DE OBRA EJECUTADA';
	const FORMATO_NOMBRE_ARCHIVO = 'EstimacionSubcontrato.pdf';
	const PDF_PAGE_ORIENTATION = 'L';

	private $_estimacion;
	private $_soloConceptosEstimados = 1;

	public function __construct( EstimacionSubcontrato $estimacion, $soloConceptosEstimados = 1 ) {
		parent::__construct();

		$this->_estimacion = $estimacion;
		$this->_soloConceptosEstimados = $soloConceptosEstimados;
	}

	private function writeDatosGeneralesEstimacion() {

		$this->AddPage(self::PDF_PAGE_ORIENTATION);
		$this->SetFont('Arial', '', 7);

		$printBorder = 1;

		// $this->Cell(80, 20, "LOGO PROYECTO", $printBorder);
		// $this->Ln();
		$this->SetFontSize(18);
		$this->setFontStyle("B");
		$this->setFillColorGHI();
		// $this->SetX($this->GetX() + 20);
		$this->Cell(200, 30, "ESTIMACIÓN DE OBRA EJECUTADA", 0, 0, "C");
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
		$this->Cell(0, $cellHeight, Util::formatoNumeroFolio($this->_estimacion->getNumeroFolio()), $printBorder, 0, 'R');
		$this->Ln($cellHeight);

		$this->SetX($xPosData);
		$this->setFontStyle("B");
		$this->Cell($labelCellWidth, $cellHeight, "No. Estimación", $printBorder);
		$this->Cell(0, $cellHeight, $this->_estimacion->getNumeroFolioConsecutivo(), $printBorder, 0, 'R');
		$this->Ln($cellHeight);

		$this->SetFontSize(9);

		$this->SetX($xPosData);
		$this->setFontStyle("B");
		$this->Cell($labelCellWidth, $cellHeight, "Semana de Contrato", $printBorder);
		$this->resetFontStyle();
		$this->Cell(0, $cellHeight, "", $printBorder, 0, 'R');
		$this->Ln($cellHeight);

		$this->SetX($xPosData);
		$this->setFontStyle("B");
		$this->Cell($labelCellWidth, $cellHeight, "Fecha", $printBorder);
		$this->resetFontStyle();
		$this->Cell(0, $cellHeight, Util::formatoFecha($this->_estimacion->getFecha()), $printBorder, 0, 'R');
		$this->Ln($cellHeight);

		$this->SetX($xPosData);
		$this->setFontStyle("B");
		$this->Cell($labelCellWidth, $cellHeight, "Periodo", $printBorder);
		$this->resetFontStyle();
		$this->Cell(0, $cellHeight, 
			  "De: " . Util::formatoFecha($this->_estimacion->getFechaInicio())
			. " A: " . Util::formatoFecha($this->_estimacion->getFechaTermino()),
			$printBorder, 0, 'R'
		);
		$this->Ln(8);

		$this->SetFontSize(10);

		$this->setFontStyle("B");
		$this->Cell(80, 5, "Organización:", $printBorder);
		$this->Cell(0, 5, $this->_estimacion->getNombreObra(), $printBorder, 0, "C");
		$this->Ln();

		$this->Cell(80, 5, "Contratista:", $printBorder, 0, "L");
		$this->Cell(0, 5, $this->_estimacion->getContratista(), $printBorder, 0, "C");
		$this->Ln();

		$this->Cell(80, 5, "No. de Contrato:", $printBorder, 0, "L");
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
		$headerCols[] = array('label' => 'UM', 'cellWidth' => 10, 'cellHeight' => $cellHeight);
		$headerCols[] = array('label' => 'Precio Unitario', 'cellWidth' => 22, 'cellHeight' => $cellHeight);
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
			$this->_estimacion->getTipoMoneda(),
			0, 0, $textCenterAlign
		);

		for ($i = 3; $i < count($headerCols); $i++) { 
			
			$this->Cell(
				( ($pctCantidadWidth * $headerCols[$i]['cellWidth']) / 100 ),
				$cellHeight,
				"",
				0, 0, $textCenterAlign
			);
			$this->Cell(
				( ($pctImporteWidth * $headerCols[$i]['cellWidth']) / 100 ),
				$cellHeight,
				$this->_estimacion->getTipoMoneda(),
				0, 0, $textCenterAlign
			);
		}
		$this->Ln();

		// -------------------------------------------------------------------
		// ------ Lista de conceptos estimados
		// -------------------------------------------------------------------
		$conceptos = $this->_estimacion->getConceptosEstimados($this->_soloConceptosEstimados);
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
					Util::formatoNumerico($concepto->PrecioUnitario),
					Util::formatoNumerico($concepto->CantidadSubcontratada),
					Util::formatoNumerico($concepto->ImporteSubcontratado),
					Util::formatoNumerico($concepto->CantidadEstimadaAcumuladaAnterior),
					Util::formatoNumerico($concepto->ImporteEstimadoAcumuladoAnterior),
					Util::formatoNumerico($concepto->CantidadEstimada),
					Util::formatoNumerico($concepto->ImporteEstimado),
					Util::formatoNumerico($concepto->CantidadEstimadaTotal),
					Util::formatoNumerico($concepto->MontoEstimadoTotal),
					Util::formatoNumerico($concepto->CantidadSaldo),
					Util::formatoNumerico($concepto->MontoSaldo)
				)
			);
		}

		$this->setAligns(array("R"));
		$this->setFontStyle("B");
		$this->Row(
			array(
				"Sub-Totales Obra Ejecutada", "", "", "",
				Util::formatoNumerico($sumaContrato), "",
				Util::formatoNumerico($acumuladoEstimacionAnterior), "",
				Util::formatoNumerico($sumaEstaEstimacion), "",
				Util::formatoNumerico($acumuladoEstaEstimacion), "",
				Util::formatoNumerico($sumaSaldoPendiente)
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
		$this->setAligns(array("L", "R"));
		$sumaDeductivas = 0;

		$deductivas = $this->_estimacion->getDeductivas();

		foreach ( $deductivas as $deductiva ) {
			
			$sumaDeductivas += $deductiva->Importe;

			$this->Row(
				array(
					$deductiva->Concepto, "", "", "", "", "", "", "",
					Util::formatoNumerico($deductiva->Importe), "", "", "", ""
				)
			);
		}

		$this->setAligns(array("R"));
		$this->setFontStyle("B");
		$this->Row(
			array(
				"Sub-Totales Deductivas", "", "", "",
				Util::formatoNumerico($this->_estimacion->subcontrato->getImporteAcumuladoDeductivas()),
				"", "", "",
				Util::formatoNumerico($sumaDeductivas),	"",
				Util::formatoNumerico(
					$this->_estimacion->subcontrato->getImporteAcumuladoDeductivas()
					+ $sumaDeductivas
				), "", ""
			)
		);
		$this->Ln();

		// -------------------------------------------------------------------
		// ------ Lista de retenciones
		// -------------------------------------------------------------------
		$this->setFontSize(7);
		$this->setFontStyle("B");
		$this->Cell(0, 5, "RETENCIONES");
		$this->Ln();

		$this->resetFontStyle();
		$this->setFontSize(5);
		$this->setCellHeight(3);
		$this->setAligns(array("L", "R"));
		$sumaRetenciones = 0;

		$retenciones = $this->_estimacion->getRetenciones();
		
		foreach ( $retenciones as $retencion ) {
			
			$sumaRetenciones += $retencion->Importe;

			$this->Row(
				array(
					$retencion->Concepto, "", "", "", "", "", "", "",
					Util::formatoNumerico($retencion->Importe), "", "", "", ""
				)
			);
		}

		$this->setAligns(array("R"));
		$this->setFontStyle("B");
		$this->Row(
			array(
				"Sub-Totales Retenciones", "", "", "",
				Util::formatoNumerico($this->_estimacion->subcontrato->getImporteAcumuladoRetenciones()),
				"", "", "",
				Util::formatoNumerico($sumaRetenciones), "",
				Util::formatoNumerico(
					$this->_estimacion->subcontrato->getImporteAcumuladoRetenciones()
					+ $sumaRetenciones
				), "", ""
			)
		);
		$this->Ln();

		$totalesEstimacion = $this->_estimacion->getTotalesTransaccion();
		$totales = array();

		foreach ($totalesEstimacion as $total) {
			
			$totales = array(
				'SumaImportes'  			  => $total->SumaImportes,
				'ImporteFondoGarantia'  	  => $total->ImporteFondoGarantia,
				'ImporteAmortizacionAnticipo' => $total->ImporteAmortizacionAnticipo,
				'ImporteAnticipoLiberar'  	  => $total->ImporteAnticipoLiberar,
				'SumaDeductivas'  			  => $total->SumaDeductivas,
				'SumaRetenciones'  			  => $total->SumaRetenciones,
				'SumaRetencionesLiberadas'    => $total->SumaRetencionesLiberadas,
				'Subtotal' 					  => $total->Subtotal,
				'IVA' 						  => $total->IVA,
				'ImporteRetencionIVA'  		  => $total->ImporteRetencionIVA,
				'Total'     				  => $total->Total,
				'ImporteAcumuladoEstimacionAnterior' => $total->ImporteAcumuladoEstimacionAnterior,
				'ImporteAcumuladoAnticipoAnterior'   => $total->ImporteAcumuladoAnticipoAnterior,
				'ImporteAcumuladoFondoGarantiaAnterior' => $total->ImporteAcumuladoFondoGarantiaAnterior,
				'IVAAcumuladoAnterior'				 => $total->IVAAcumuladoAnterior,
			);
		}

		$this->setFontSize(7);
		$this->setFontStyle("B");
		$this->Cell(0, 5, "RESUMEN");
		$this->Ln();
		$this->setFontSize(5);
		$this->setCellHeight(3);

		$this->Row(
			array(
				"Importe asociado a trabajos ejecutados", "", "", "",
				Util::formatoNumerico($this->_estimacion->subcontrato->getSubtotal()), "",
				Util::formatoNumerico($acumuladoEstimacionAnterior), "",
				Util::formatoNumerico($sumaEstaEstimacion - $sumaDeductivas - $sumaRetenciones), "",
				Util::formatoNumerico($acumuladoEstaEstimacion), "",
				Util::formatoNumerico($sumaSaldoPendiente)
			)
		);

		$this->Row(
			array(
				"Anticipo", "%", Util::formatoNumerico($this->_estimacion->getPctAnticipo()), "",
				Util::formatoNumerico($this->_estimacion->subcontrato->getImporteAnticipo()), "", "", "",
				Util::formatoNumerico($totales['ImporteAnticipoLiberar']), "",
				0, "",
				0
			)
		);

		$this->Row(
			array(
				"Amortización Anticipo", "", "", "",
				Util::formatoNumerico($this->_estimacion->subcontrato->getImporteAcumuladoAnticipo()), "",
				Util::formatoNumerico($totales['ImporteAcumuladoAnticipoAnterior']), "",
				Util::formatoNumerico($totales['ImporteAmortizacionAnticipo']), "",
				Util::formatoNumerico(
					  $totales['ImporteAcumuladoAnticipoAnterior']
					+ $totales['ImporteAmortizacionAnticipo']
				), "",
				0
			)
		);

		$this->Row(
			array(
				"Fondo de Garantia", "%", Util::formatoNumerico($this->_estimacion->getPctFondoGarantia()), "",
				Util::formatoNumerico($this->_estimacion->subcontrato->getImporteFondoGarantia()), "",
				Util::formatoNumerico($totales['ImporteAcumuladoFondoGarantiaAnterior']), "",
				Util::formatoNumerico($totales['ImporteFondoGarantia']), "",
				Util::formatoNumerico(
					  $totales['ImporteAcumuladoFondoGarantiaAnterior']
					+ $totales['ImporteFondoGarantia']
				), "",
				0
			)
		);

		$subtotal = $sumaEstaEstimacion - $sumaDeductivas - $sumaRetenciones 
			- $totales['ImporteAmortizacionAnticipo']
			- $totales['ImporteFondoGarantia'];

		$this->Row(
			array(
				"Sub-total valor de los trabajos", "", "", "",
				Util::formatoNumerico($this->_estimacion->subcontrato->getSubtotal()), "",
				Util::formatoNumerico(
					  $acumuladoEstimacionAnterior
					- $totales['ImporteAcumuladoAnticipoAnterior']
					- $totales['ImporteAcumuladoFondoGarantiaAnterior']
				), "",
				Util::formatoNumerico($subtotal), "",
				0, "",
				0
			)
		);

		$this->Row(
			array(
				"IVA", "%", Util::formatoNumerico($this->_estimacion->getPctIVA()), "",
				Util::formatoNumerico($this->_estimacion->subcontrato->getIVA()), "",
				Util::formatoNumerico($totales['IVAAcumuladoAnterior']), "",
				Util::formatoNumerico($totales['IVA']), "",
				0, "",
				0
			)
		);

		$total += $subtotal + $totales['IVA'];

		$this->Row(
			array(
				"Retención de IVA", "", "", "", "", "",
				0, "",
				Util::formatoNumerico($totales['ImporteRetencionIVA']), "",
				0, "",
				0
			)
		);

		$total -= $totales['ImporteRetencionIVA'];

		$this->Row(
			array(
				"Total pagado y/o a pagar", "", "", "",
				Util::formatoNumerico($this->_estimacion->subcontrato->getTotal()), "",
				0, "",
				Util::formatoNumerico($total), "",
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
		$this->Cell(50, 3, "por el contratista", 1, 0, "C", true);
		$this->Cell(50, 3, "", 0, 0);
		$this->Cell(50, 3, "por el cliente", 1, 0, "C", true);
		$this->Ln();
		$this->Cell(70, 3, "", 0, 0);
		$this->Cell(50, 8, "", 1, 0, "C");
		$this->Cell(50, 8, "", 0, 0);
		$this->Cell(50, 8, "", 1, 0, "C");
		$this->Ln();
		$this->Cell(70, 3, "", 0, 0);
		$this->Cell(50, 3, "factor o dependiente", 1, 0, "C", true);
		$this->Cell(50, 3, "", 0, 0);
		$this->Cell(50, 3, "gerente de proyecto", 1, 0, "C", true);
	}

	public function Output() {

		$this->writeDatosGeneralesEstimacion();
		$this->writeConceptosEstimados();

		parent::Output(self::FORMATO_NOMBRE_ARCHIVO, 'I');
	}
}
?>