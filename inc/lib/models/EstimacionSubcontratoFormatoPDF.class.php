<?php
require_once 'models/FormatoPDF.class.php';
require_once 'models/Util.class.php';

class EstimacionSubcontratoFormatoPDF extends FormatoPDF
{
	const FORMATO_NOMBRE_ARCHIVO = 'EstimacionSubcontrato.pdf';
	const PDF_PAGE_ORIENTATION = 'L';

	protected $titulo = 'ESTIMACIÓN DE OBRA EJECUTADA';
	protected $organizacion_label = 'Organización:';
	protected $contratista_label = 'Contratista:';
	protected $numero_de_estimacion_label = 'No. Estimación';
	protected $semana_de_contrato_label = 'Semana de Contrato';
	protected $numero_de_contrato_label = 'No. de Contrato:';
	protected $firma_contratista_titulo_label = 'Elaboró';
	protected $firma_contratista_descripcion_label = 'CONTRATISTA';
	protected $firma_control_estimaciones_titulo_label = 'Revisó';
	protected $firma_control_estimaciones_descripcion_label = 'CONTROL DE ESTIMACIONES';
    protected $firma_superintendencia_titulo_label = 'Avaló';
    protected $firma_superintendencia_descripcion_label = 'SUPERINTENDENCIA DE OBRA';
    protected $firma_calidad_titulo_label = 'Vo.Bo.';
    protected $firma_calidad_descripcion_label = 'CALIDAD';
    protected $firma_planeacion_titulo_label = 'Vo.Bo.';
    protected $firma_planeacion_descripcion_label = 'CONTROL DE PLANEACIÓN';
    protected $firma_seguimiento_titulo_label = 'Vo.Bo.';
    protected $firma_seguimiento_descripcion_label = 'CONTROL DE SEGUIMIENTO';
    protected $firma_subcontratos_titulo_label = 'Vo.Bo.';
    protected $firma_subcontratos_descripcion_label = 'SUBCONTRATOS';
    protected $firma_director_proyecto_titulo_label = 'Autorizó';
    protected $firma_director_proyecto_descripcion_label = 'DIRECTOR DE PROYECTO';
	protected $firma_administrador_titulo_label = 'Recibe';
	protected $firma_administrador_descripcion_label = 'ADMINISTRADOR';
	protected $table_body_widths = array();

	private $estimacion;
	private $soloConceptosEstimados = 1;

	private $sumaImporteContrato = 0;
	private $sumaAcumuladoEstimacionAnterior = 0;
	private $sumaImporteEstaEstimacion = 0;
	private $sumaAcumuladoEstaEstimacion = 0;
	private $sumaSaldoPendiente = 0;

    /**
     * suma total de importe de cargos de materiales del contratista
     * @var int
     */
    private $sumaImporteCargoMaterial = 0;

    /**
     * suma acumulada de importe de descuentos aplicados anteriores a esta estimacion
     * @var int
     */
    private $sumaAcumuladoDescuentoAnterior = 0;

    /**
     * suma total de importe de descuentos aplicados en esta estimacion
     * @var int
     */
    private $sumaImporteDescuentoEstaEstimacion = 0;

    /**
     * suma acumulada de importe de descuentos aplicados a esta estimacion
     * @var int
     */
    private $sumaAcumuladoDescuentoEstaEstimacion = 0;

    /**
     * suma del saldo total por descontar al contratista
     * @var int
     */
    private $sumaSaldoPorDescontar = 0;

    /**
     * suma total de retenciones aplicadas en esta estimacion
     * @var int
     */
    private $suma_retenciones = 0;

    /**
     * suma total de liberaciones aplicadas en esta estimacion
     * @var int
     */
    private $suma_liberaciones = 0;

    /**
     * @param EstimacionSubcontrato $estimacion
     * @param int $soloConceptosEstimados
     */
    public function __construct(EstimacionSubcontrato $estimacion, $soloConceptosEstimados = 1)
    {
		parent::__construct();

		$this->estimacion = $estimacion;
		$this->soloConceptosEstimados = $soloConceptosEstimados;
	}

	/**
     * Escribe los datos generales de la estimacion
	 *
     */
	private function writeDatosGeneralesEstimacion()
    {
		$this->AddPage( self::PDF_PAGE_ORIENTATION );
		$this->SetFont('Arial', '', 7);

		$printBorder = 1;

		// $this->Cell(80, 20, "LOGO PROYECTO", $printBorder);
		// $this->Ln();
		$this->SetFontSize(18);
		$this->setFontStyle("B");
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
		$this->Cell(0, 5, $this->estimacion->subcontrato->getReferencia(), $printBorder, 0, "C");
		$this->Ln(10);
	}

	/**
	 * Escribe los conceptos estimados
     *
     */
	private function writeConceptosEstimados()
    {
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

		foreach ($headerCols as $key => $formatParams) {
			$lastXPosition = $this->GetX();
			$lastYPosition = $this->GetY();

			$this->Cell(
				$formatParams['cellWidth'],
				$formatParams['cellHeight'],
				$formatParams['label'],
				$printBorder, 0, $textCenterAlign, true
			);

			if ($key >= 3) {
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
			$this->estimacion->moneda->getNombre(),
			0, 0, $textCenterAlign
		);

		for ($i = 3; $i < count($headerCols); $i++)
        {
			
			$this->Cell(
				( ($pctCantidadWidth * $headerCols[$i]['cellWidth']) / 100 ),
				$cellHeight,
				"",
				0, 0, $textCenterAlign
			);
			$this->Cell(
				( ($pctImporteWidth * $headerCols[$i]['cellWidth']) / 100 ),
				$cellHeight,
				$this->estimacion->moneda->getNombre(),
				0, 0, $textCenterAlign
			);
		}
		$this->Ln();

		// -------------------------------------------------------------------
		// ------ Lista de conceptos estimados
		// -------------------------------------------------------------------
		$this->setCellHeight(3);
		$this->setAligns( array("L", "C", "R") );
		$this->table_body_widths = array(
				$headerCols[0]['cellWidth'], $headerCols[1]['cellWidth'], $headerCols[2]['cellWidth'],
				( ($pctCantidadWidth * $headerCols[3]['cellWidth']) / 100 ), ( ($pctImporteWidth * $headerCols[3]['cellWidth']) / 100 ),
				( ($pctCantidadWidth * $headerCols[4]['cellWidth']) / 100 ), ( ($pctImporteWidth * $headerCols[4]['cellWidth']) / 100 ),
				( ($pctCantidadWidth * $headerCols[5]['cellWidth']) / 100 ), ( ($pctImporteWidth * $headerCols[5]['cellWidth']) / 100 ),
				( ($pctCantidadWidth * $headerCols[6]['cellWidth']) / 100 ), ( ($pctImporteWidth * $headerCols[6]['cellWidth']) / 100 ),
				( ($pctCantidadWidth * $headerCols[7]['cellWidth']) / 100 ), ( ($pctImporteWidth * $headerCols[7]['cellWidth']) / 100 )
		);

		$this->SetWidths($this->table_body_widths);

		$conceptos = $this->estimacion->getConceptosEstimados($this->soloConceptosEstimados);

		foreach ($conceptos as $concepto) {
			$this->sumaImporteContrato += $concepto->ImporteSubcontratado;
			$this->sumaAcumuladoEstimacionAnterior += $concepto->ImporteEstimadoAcumuladoAnterior;
			$this->sumaImporteEstaEstimacion += $concepto->ImporteEstimado;
			$this->sumaAcumuladoEstaEstimacion += $concepto->MontoEstimadoTotal;
			$this->sumaSaldoPendiente += $concepto->MontoSaldo;

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
		$this->setFills(array(true));
		$this->setBorders(array(true));

		$this->Row(
			array(
				"Sub-Totales Obra Ejecutada", "", "", "",
				Util::formatoNumerico($this->sumaImporteContrato), "",
				Util::formatoNumerico($this->sumaAcumuladoEstimacionAnterior), "",
				Util::formatoNumerico($this->sumaImporteEstaEstimacion), "",
				Util::formatoNumerico($this->sumaAcumuladoEstaEstimacion), "",
				Util::formatoNumerico($this->sumaSaldoPendiente)
			)
		);

		$this->resetFills();
		$this->resetBorders();
		$this->Ln();
	}

	/**
     * Escribe las deductivas generadas en esta estimacion
	 *
     */
	private function writeDeductivas()
    {
		// -------------------------------------------------------------------
		// ------ Lista de deductivas
		// -------------------------------------------------------------------
		$this->setFontSize(7);
		$this->setFontStyle("B");
		$this->setCellHeight(5);

		$this->Cell($this->table_body_widths[0], 5, "DEDUCTIVAS Y DESCUENTOS");
		$this->Cell(67, 5, "DEDUCTIVAS", 1, 0, self::TEXT_ALIGN_CENTER, 1);
		$this->Cell(140, 5, "DESCUENTOS", 1, 0, self::TEXT_ALIGN_CENTER, 1);

		$this->Ln();

		$this->resetFontStyle();
		$this->setFontSize(5);
		$this->setCellHeight(3);
		$this->setAligns(array("L", "C", "R"));

		foreach ($this->estimacion->empresa->cargos_material as $cargo_material) {
			$descuento_aplicado = null;
			$cantidad_descontada_anterior = EstimacionDescuentoMaterial::getCantidadDescontadaAnterior($this->estimacion, $cargo_material->material);
			$importe_descontado_anterior = EstimacionDescuentoMaterial::getImporteDescontadoAnterior($this->estimacion, $cargo_material->material);
			$cantidad_por_descontar = 0;
			$importe_por_descontar = 0;

			foreach ($this->estimacion->descuentos as $descuento) {
				if ($descuento->material->getId() == $cargo_material->material->getId()) {
					$descuento_aplicado = $descuento;
					break;
				}
			}

			$cantidad = 0;
			$precio = $cargo_material->getPrecio();
			$importe = 0;

			if ( ! is_null($descuento_aplicado)) {
				$cantidad = $descuento_aplicado->getCantidad();
				$precio = $descuento_aplicado->getPrecio();
				$importe = $descuento_aplicado->getImporte();
			}

			if ($cantidad_descontada_anterior <= $cargo_material->getCantidad()) {
				$cantidad_por_descontar = $cargo_material->getCantidad() - $cantidad_descontada_anterior - $cantidad;
				$importe_por_descontar = $cargo_material->getImporte() - $importe_descontado_anterior -$importe;
			}

			$this->Row(
				array(
					$cargo_material->material->getDescripcion(),
					$cargo_material->material->getUnidad(),
					Util::formatoNumerico($cargo_material->getPrecio()),
					Util::formatoNumerico($cargo_material->getCantidad()),
					Util::formatoNumerico($cargo_material->getImporte()),
					Util::formatoNumerico($cantidad_descontada_anterior),
					Util::formatoNumerico($importe_descontado_anterior),
					Util::formatoNumerico($cantidad),
					Util::formatoNumerico($importe),
					Util::formatoNumerico(
						  $cantidad_descontada_anterior
						+ $cantidad
					),
					Util::formatoNumerico(
						  $importe_descontado_anterior
						+ $importe
					),
					Util::formatoNumerico($cantidad_por_descontar),
					Util::formatoNumerico($importe_por_descontar)
				)
			);

			$this->sumaImporteCargoMaterial += $cargo_material->getImporte();
			$this->sumaAcumuladoDescuentoAnterior += $importe_descontado_anterior;
			$this->sumaImporteDescuentoEstaEstimacion += $importe;
			$this->sumaAcumuladoDescuentoEstaEstimacion	+= $importe_descontado_anterior + $importe;
			$this->sumaSaldoPorDescontar += $importe_por_descontar;
		}

		$this->setAligns(array("R"));
		$this->setFontStyle("B");
		$this->setFills(array(true));
		$this->setBorders(array(true));

		$this->Row(
			array(
				"Sub-Totales Deductivas", "", "", "",
				Util::formatoNumerico($this->sumaImporteCargoMaterial),"",
				Util::formatoNumerico($this->sumaAcumuladoDescuentoAnterior), "",
				Util::formatoNumerico($this->sumaImporteDescuentoEstaEstimacion),	"",
				Util::formatoNumerico($this->sumaAcumuladoDescuentoEstaEstimacion), "",
				Util::formatoNumerico(
					  $this->sumaImporteCargoMaterial
					- $this->sumaAcumuladoDescuentoEstaEstimacion
				)
			)
		);

		$this->resetFills();
		$this->resetBorders();
		$this->Ln();
	}

	/**
     * Escribe las retenciones registradas en la estimacion
	 *
     */
	private function writeRetenciones()
    {
		// -------------------------------------------------------------------
		// ------ Lista de retenciones
		// -------------------------------------------------------------------
		$this->setFontSize(7);
		$this->setFontStyle( "B" );
		$this->Cell(0, 5, "RETENCIONES");
		$this->Ln();

		$this->resetFontStyle();
		$this->setFontSize(5);
		$this->setCellHeight(3);
		$this->setAligns(array("L", "R"));
		
		foreach ($this->estimacion->retenciones as $retencion) {
			$this->suma_retenciones += $retencion->getImporte();

			$this->Row(
				array(
					$retencion->getConcepto(),
					$retencion->tipo_retencion->getDescripcion(), "", "", "", "", "", "",
					Util::formatoNumerico($retencion->getImporte() ), "", "", "", "")
			);
		}

		$this->setAligns(array("R"));
		$this->setFontStyle("B");
		$this->setFills(array(true));
		$this->setBorders(array(true));

		$this->Row(
			array(
				"Sub-Totales Retenciones", "", "", "", "", "",
				Util::formatoNumerico( $this->estimacion->getRetencionAnterior() ), "",
				Util::formatoNumerico( $this->suma_retenciones ), "",
				Util::formatoNumerico(
					  $this->estimacion->getRetencionAnterior()
					+ $this->suma_retenciones
				), "", ""
			)
		);

		$this->resetFills();
		$this->resetBorders();
		$this->Ln();
	}

	/**
     * Escribe las liberaciones registradas en la estimacion
	 *
     */
	private function writeLiberaciones()
    {
		// -------------------------------------------------------------------
		// ------ Lista de liberacion de retenciones
		// -------------------------------------------------------------------
		$this->setFontSize(7);
		$this->setFontStyle( "B" );
		$this->Cell( 0, 5, "LIBERACIONES" );
		$this->Ln();

		$this->resetFontStyle();
		$this->setFontSize(5);
		$this->setCellHeight(3);
		$this->setAligns( array("L", "R") );
		
		foreach ($this->estimacion->liberaciones as $liberacion) {
			$this->suma_liberaciones += $liberacion->getImporte();

			$this->Row(
				array(
					$liberacion->getConcepto(), "", "", "", "", "", "", "",
					Util::formatoNumerico($liberacion->getImporte()), "", "", "", ""
				)
			);
		}

		$this->setAligns(array("R"));
		$this->setFontStyle("B");
		$this->setFills(array(true));
		$this->setBorders(array(true));

		$this->Row(
			array(
				"Sub-Totales Liberaciones", "", "", "", "", "",
				Util::formatoNumerico($this->estimacion->getLiberadoAnterior()), "",
				Util::formatoNumerico($this->suma_liberaciones), "",
				Util::formatoNumerico(
					  $this->estimacion->getLiberadoAnterior()
					+ $this->suma_liberaciones
				), "", ""
			)
		);

		$this->Row(
			array(
				"Por Liberar", "", "", "", "", "",
				Util::formatoNumerico(
					  $this->estimacion->getRetencionAnterior()
					- $this->estimacion->getLiberadoAnterior()
				), "",
				Util::formatoNumerico(
					  $this->suma_retenciones
					- $this->suma_liberaciones
				), "",
				Util::formatoNumerico(
					(
						  $this->estimacion->getRetencionAnterior()
						+ $this->suma_retenciones
					)
					-
					(
						  $this->estimacion->getLiberadoAnterior()
						+ $this->suma_liberaciones
					)
				), "", ""
			)
		);

		$this->resetFills();
		$this->resetBorders();
		$this->Ln();
	}

	/**
     * Escribe los totales como resumen
	 *
     */
	private function writeTotales()
    {
		$totales = $this->estimacion->getTotalesTransaccion();

		$this->setFontSize(7);
		$this->setFontStyle("B");
		$this->Cell(0, 5, "RESUMEN");
		$this->Ln();
		$this->setFontSize(5);
		$this->setCellHeight(3);

		$this->setAligns(array("L", "C", "R"));

		$ejecutado_contrato = $this->estimacion->subcontrato->getSubtotal();
		$ejecutado_anterior = $this->sumaAcumuladoEstimacionAnterior;
		$ejecutado = $this->sumaImporteEstaEstimacion;
		$ejecutado_actual = $this->sumaAcumuladoEstaEstimacion;
		$ejecutado_saldo = $this->sumaSaldoPendiente;

		$this->Row(
			array(
				"Importe asociado a trabajos ejecutados", "", "", "",
				Util::formatoNumerico($ejecutado_contrato), "",
				Util::formatoNumerico($ejecutado_anterior), "",
				Util::formatoNumerico($ejecutado), "",
				Util::formatoNumerico($ejecutado_actual), "",
				Util::formatoNumerico($ejecutado_saldo)
			)
		);

		$anticipo_contrato = $this->estimacion->subcontrato->getImporteAnticipo();
		$anticipo_anterior = $this->estimacion->getAnticipoAnterior();
		$anticipo = $this->estimacion->getAmortizacionAnticipo();
		$anticipo_actual = $anticipo_anterior + $anticipo;
		$anticipo_saldo = $anticipo_contrato - $anticipo_actual;
		//ERNESTO

		$this->Row(
			array(
				"Anticipo Solicitado", "%", Util::formatoPorcentaje($this->estimacion->subcontrato->getPorcentajeAnticipo()),
				"",
				Util::formatoNumerico($anticipo_contrato),
				"", "", "",	"", "", "", "", ""
			)
		);

		$this->Row(
			array(
				"Amortización Anticipo", "%",
				Util::formatoPorcentaje( $this->estimacion->getPctAnticipo() ), "",
				"", "",
				Util::formatoNumerico($anticipo_anterior), "",
				Util::formatoNumerico($anticipo), "",
				Util::formatoNumerico($anticipo_actual), "",
				Util::formatoNumerico($anticipo_saldo)
			)
		);

		$fondo_garantia_contrato = $this->estimacion->subcontrato->getImporteFondoGarantia();
		$fondo_garantia_anterior = $this->estimacion->getFondoGarantiaAnterior();
		$fondo_garantia = $this->estimacion->getFondoGarantia();
		$fondo_garantia_actual = $fondo_garantia_anterior + $fondo_garantia;
		$fondo_garantia_saldo = $fondo_garantia_contrato - $fondo_garantia_actual;

		$subtotal_contrato = $ejecutado_contrato - $anticipo_contrato - $fondo_garantia_contrato;

		$subtotal_anterior = $ejecutado_anterior - $anticipo_anterior - $fondo_garantia_anterior;

		$subtotal = $this->sumaImporteEstaEstimacion - $anticipo;

		$subtotal_actual = $subtotal_anterior + $subtotal;

		$subtotal_saldo = $subtotal_contrato - $subtotal_actual;

		$this->setFills(array(true));
		$this->setBorders(array(true));

		$this->Row(
			array(
				"Sub-total valor de los trabajos",
				$this->estimacion->moneda->getNombre(), "", "",
				Util::formatoNumerico($subtotal_contrato), "",
				Util::formatoNumerico($subtotal_anterior), "",
				Util::formatoNumerico($subtotal), "",
				Util::formatoNumerico($subtotal_actual), "",
				Util::formatoNumerico($subtotal_saldo)
			)
		);

		$this->resetFills(array(true));
		$this->resetBorders(array(true));
		
		$iva_contrato = $this->estimacion->subcontrato->getIVA();  
		$iva_anterior = $this->estimacion->getIVAAnterior(); 
		$iva = $this->estimacion->getIVA();
		$iva_actual = $iva_anterior + $iva; 
		$iva_saldo = $iva_contrato - $iva_actual; 
		$iva_acumulado_anterior = $iva_anterior + $iva_saldo;
		$iva_esta_estimacion = $iva_acumulado_anterior + $iva;
		$iva_saldo_real = $iva_contrato - $iva_esta_estimacion;

		$this->Row(
			array(
				"IVA", "%", 
				Util::formatoPorcentaje($this->estimacion->getPctIVA()), "",
				Util::formatoNumerico($iva_contrato), "",
				Util::formatoNumerico($iva_acumulado_anterior), "",
				Util::formatoNumerico($iva), "",
				Util::formatoNumerico($iva_esta_estimacion), "",
				Util::formatoNumerico($iva_saldo_real)
			)
		);

		$total_contrato = $subtotal_contrato * (1 + $this->estimacion->getPctIVA());
		$total_anterior =  $subtotal_anterior + $iva_anterior;
		$total = $subtotal + $iva;
		$total_actual = $total_anterior + $total;
		$total_saldo = $total_contrato - $total_actual;

        $this->setFills(array(true));
        $this->setBorders(array(true));

        $this->Row(
			array(
				"Total", $this->estimacion->moneda->getNombre(),
				"", "",
				Util::formatoNumerico($total_contrato), "",
				Util::formatoNumerico($total_anterior), "",
				Util::formatoNumerico($total), "",
				Util::formatoNumerico($total_actual), "",
				Util::formatoNumerico($total_saldo)
			)
		);

        $this->resetFills(array(true));
		$this->resetBorders(array(true));

        $this->Row(
            array(
                "Fondo de Garantia", "%", Util::formatoPorcentaje($this->estimacion->getPctFondoGarantia()), "",
                Util::formatoNumerico($fondo_garantia_contrato), "",
                Util::formatoNumerico($fondo_garantia_anterior), "",
                Util::formatoNumerico($fondo_garantia), "",
                Util::formatoNumerico($fondo_garantia_actual), "",
                Util::formatoNumerico($fondo_garantia_saldo)
            )
        );

		$retencion_iva_anterior = $this->estimacion->getRetencionIVAAnterior();
		$retencion_iva = $this->estimacion->getRetencionIVA();
		$retencion_iva_actual = $retencion_iva_anterior + $retencion_iva;
		$retencion_iva_saldo = "";

		$this->Row(
			array(
				"Retención de IVA", "", "", "", "", "",
				Util::formatoNumerico($retencion_iva_anterior), "",
				Util::formatoNumerico($retencion_iva), "",
				Util::formatoNumerico($retencion_iva_actual), "",
				Util::formatoNumerico($retencion_iva_saldo)
			)
		);

		$descuento_contrato = $this->estimacion->empresa->getImporteAcumuladoCargos();
		$descuento_anterior = $this->estimacion->getDescuentoAnterior();
		$descuento = $this->sumaImporteDescuentoEstaEstimacion;
		$descuento_actual = $descuento_anterior + $descuento;
		$descuento_saldo = $descuento_contrato - $descuento_actual;

		$this->Row(
			array(
				"Descuentos", "", "", "",
				Util::formatoNumerico($descuento_contrato), "",
				Util::formatoNumerico($descuento_anterior), "",
				Util::formatoNumerico($descuento), "",
				Util::formatoNumerico($descuento_actual), "",
				Util::formatoNumerico($descuento_saldo)
			)
		);

		$retencion_contrato = $this->estimacion->subcontrato->getImporteAcumuladoRetenciones();
		$retencion_anterior = $this->estimacion->getRetencionAnterior();
		$retencion = $this->suma_retenciones;
		$retencion_actual = $retencion_anterior + $retencion;
		$retencion_saldo = "";

		$this->Row(
			array(
				"Retenciones", "", "", "",
				Util::formatoNumerico($retencion_contrato), "",
				Util::formatoNumerico($retencion_anterior), "",
				Util::formatoNumerico($retencion), "",
				Util::formatoNumerico($retencion_actual), "",
				Util::formatoNumerico($retencion_saldo)
			)
		);

		$this->Row(
			array(
				"Anticipo a Liberar", "", "", "",
				"", "",
				Util::formatoNumerico($this->estimacion->getAnticipoLiberarAnterior()), "",
				Util::formatoNumerico($this->estimacion->getAnticipoLiberar()), "",
				Util::formatoNumerico($this->estimacion->getAnticipoLiberar()), "",
				""
			)
		);

		$this->Row(
			array(
				"Retenciones Liberadas", "", "", "",
				"", "",
				Util::formatoNumerico($this->estimacion->getLiberadoAnterior()), "",
				Util::formatoNumerico($this->suma_liberaciones), "",
				Util::formatoNumerico(
					  $this->estimacion->getLiberadoAnterior()
					+ $this->suma_liberaciones
				), "", ""
			)
		);

		$pagar_contrato = $total_contrato - $descuento_contrato - $retencion_contrato;

		$pagar_anterior = $total_anterior - $descuento_anterior - $retencion_anterior;

		$pagar =
			  $total
			+ $this->estimacion->getAnticipoLiberar()
			+ $this->suma_liberaciones
            - $fondo_garantia
			- $retencion_iva
			- $descuento
			- $retencion;

		$pagar_actual = $pagar_anterior + $pagar;

		$pagar_saldo = $pagar_contrato - $pagar_actual;

		$this->setFills(array(true));
		$this->setBorders(array(true));

		$this->Row(
			array(
				"Total pagado y/o a pagar", $this->estimacion->moneda->getNombre(),"", "",
				Util::formatoNumerico($pagar_contrato), "",
				Util::formatoNumerico($pagar_anterior), "",
				Util::formatoNumerico($pagar), "",
				Util::formatoNumerico($pagar_actual), "",
				Util::formatoNumerico($pagar_saldo)
			)
		);

		$this->resetFills();
		$this->resetBorders();
	}

    /**
     * Imprime las firmas y numero de pagina
     */
    public function Footer()
    {
		$this->Ln(5);
		$this->resetFontStyle();
		$this->SetFontSize(4);

		$this->Cell(10, 3, "", 0, 0);
		$this->Cell(25, 3, $this->firma_contratista_titulo_label, 1, 0, "C", true);
        $this->Cell(5, 3, "", 0, 0);
        $this->Cell(25, 3, $this->firma_control_estimaciones_titulo_label, 1, 0, "C", true);
        $this->Cell(5, 3, "", 0, 0);
        $this->Cell(25, 3, $this->firma_superintendencia_titulo_label, 1, 0, "C", true);
        $this->Cell(5, 3, "", 0, 0);
        $this->Cell(25, 3, $this->firma_calidad_titulo_label, 1, 0, "C", true);
        $this->Cell(5, 3, "", 0, 0);
        $this->Cell(25, 3, $this->firma_planeacion_titulo_label, 1, 0, "C", true);
        $this->Cell(5, 3, "", 0, 0);
        $this->Cell(25, 3, $this->firma_seguimiento_titulo_label, 1, 0, "C", true);
        $this->Cell(5, 3, "", 0, 0);
        $this->Cell(25, 3, $this->firma_subcontratos_titulo_label, 1, 0, "C", true);
        $this->Cell(5, 3, "", 0, 0);
        $this->Cell(25, 3, $this->firma_director_proyecto_titulo_label, 1, 0, "C", true);
        $this->Cell(5, 3, "", 0, 0);
        $this->Cell(25, 3, $this->firma_administrador_titulo_label, 1, 0, "C", true);

        $this->Ln();

		$this->Cell(10, 3, "", 0, 0);
		$this->Cell(25, 8, "", 1, 0, "C");
		$this->Cell(5, 8, "", 0, 0);
		$this->Cell(25, 8, "", 1, 0, "C");
        $this->Cell(5, 8, "", 0, 0);
        $this->Cell(25, 8, "", 1, 0, "C");
        $this->Cell(5, 8, "", 0, 0);
        $this->Cell(25, 8, "", 1, 0, "C");
        $this->Cell(5, 8, "", 0, 0);
        $this->Cell(25, 8, "", 1, 0, "C");
        $this->Cell(5, 8, "", 0, 0);
        $this->Cell(25, 8, "", 1, 0, "C");
        $this->Cell(5, 8, "", 0, 0);
        $this->Cell(25, 8, "", 1, 0, "C");
        $this->Cell(5, 8, "", 0, 0);
        $this->Cell(25, 8, "", 1, 0, "C");
        $this->Cell(5, 8, "", 0, 0);
        $this->Cell(25, 8, "", 1, 0, "C");

		$this->Ln();

        $this->Cell(10, 3, "", 0, 0);
		$this->Cell(25, 3, $this->firma_contratista_descripcion_label, 1, 0, "C", true);
		$this->Cell(5, 3, "", 0, 0);
		$this->Cell(25, 3, $this->firma_control_estimaciones_descripcion_label, 1, 0, "C", true);
        $this->Cell(5, 3, "", 0, 0);
        $this->Cell(25, 3, $this->firma_superintendencia_descripcion_label, 1, 0, "C", true);
        $this->Cell(5, 3, "", 0, 0);
        $this->Cell(25, 3, $this->firma_calidad_descripcion_label, 1, 0, "C", true);
        $this->Cell(5, 3, "", 0, 0);
        $this->Cell(25, 3, $this->firma_planeacion_descripcion_label, 1, 0, "C", true);
        $this->Cell(5, 3, "", 0, 0);
        $this->Cell(25, 3, $this->firma_seguimiento_descripcion_label, 1, 0, "C", true);
        $this->Cell(5, 3, "", 0, 0);
        $this->Cell(25, 3, $this->firma_subcontratos_descripcion_label, 1, 0, "C", true);
        $this->Cell(5, 3, "", 0, 0);
        $this->Cell(25, 3, $this->firma_director_proyecto_descripcion_label, 1, 0, "C", true);
        $this->Cell(5, 3, "", 0, 0);
        $this->Cell(25, 3, $this->firma_administrador_descripcion_label, 1, 0, "C", true);

		$this->Ln();

        // Numero de pagina
        $this->SetFontSize(7);
		$this->SetY(-8);
		$this->AliasNbPages();
		$this->Cell(0, 3, "Página " . $this->getCurrentPage() . '/{nb}', 0, 0, self::TEXT_ALIGN_RIGHT);
	}

    /**
     * @param null $nombre
     * @param string $i
     * @return string|void
     */
    public function Output($nombre = null, $i = 'I')
    {
		$this->writeDatosGeneralesEstimacion();
		$this->writeConceptosEstimados();
		$this->writeDeductivas();
		$this->writeRetenciones();
		$this->writeLiberaciones();
		$this->writeTotales();

		parent::Output(self::FORMATO_NOMBRE_ARCHIVO, 'I');
	}

}
