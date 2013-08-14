<?php
set_include_path(get_include_path().";".$_SERVER["DOCUMENT_ROOT"]."\ModulosSAO\inc\lib");
require_once 'tcpdf/tcpdf.php';

abstract class FormatoPDF extends TCPDF {
	
	const PDF_PAGE_ORIENTATION = 'P';
	const PDF_UNIT 			   = 'mm';
	const PDF_PAGE_FORMAT 	   = 'A4';
	const PDF_USE_UNICODE 	   = true;
	const PDF_UNICODE_CODEPAGE = 'UTF-8';
	const PDF_DISK_CACHE 	   = false;
	const PDF_PDFA_MODE 	   = false;
	const PDF_DEFAULT_FONT	   = 'helvetica';
	const PDF_DEFAULT_FONTSIZE = 8;

	const PDF_MARGIN_HEADER    = 0;
	const PDF_MARGIN_FOOTER	   = 0;

	const PDF_MARGIN_TOP	   = 0;
	const PDF_MARGIN_BOTTOM	   = 10;
	const PDF_MARGIN_LEFT	   = 10;
	const PDF_MARGIN_RIGHT	   = 5;
	// static $_prueba = 'asd';

	public function __construct() {
		parent::__construct(
			self::PDF_PAGE_ORIENTATION, self::PDF_UNIT, self::PDF_PAGE_FORMAT,
			self::PDF_USE_UNICODE, self::PDF_UNICODE_CODEPAGE, self::PDF_PDFA_MODE
		);
	}

	protected function init() {
		$this->SetAutoPageBreak(TRUE);
		//$this->SetDefaultMonospacedFont(self::PDF_DEFAULT_FONT);
		// $this->SetFontSize(self::PDF_DEFAULT_FONTSIZE);
		$this->SetMargins(self::PDF_MARGIN_LEFT, self::PDF_MARGIN_TOP, self::PDF_MARGIN_RIGHT);
		$this->SetHeaderMargin(self::PDF_MARGIN_HEADER);
		$this->SetFooterMargin(self::PDF_MARGIN_FOOTER);
		$this->setPrintHeader(false);
		$this->setPrintFooter(false);
	}

	abstract protected function setEncabezado();
	abstract protected function setContenidoPrincipal();
	abstract protected function setPiePagina();
}

class SubcontratoEstimacionFormatoPFD extends FormatoPDF {

	const TITULO = 'ESTIMACIÓN DE OBRA EJECUTADA';
	const FORMATO_NOMBRE_ARCHIVO = 'EstimacionSubcontrato.pdf';
	const PDF_PAGE_ORIENTATION = 'L';

	private $_transaccion;

	public function __construct( /*EstimacionSubcontrato $transaccion*/ ) {
		parent::__construct();

		$this->init();

		// $this->_transaccion = $transaccion;
		$this->setContenidoPrincipal();
	}

	protected function init() {
		parent::init();
		$this->SetPageOrientation(self::PDF_PAGE_ORIENTATION);
	}

	protected function setEncabezado() {}

	protected function setContenidoPrincipal() {
		$this->AddPage();
		$this->SetFont(self::PDF_DEFAULT_FONT, '', self::PDF_DEFAULT_FONTSIZE);

		$content = 'Hola Mundo';

		$this->SetFont('helvetica', '', 8);
		$this->Write(20, $conten, '', 0, '', true, 0, false, false, 0);
		//$this->Text(0, 0, $content);

		$partidaColWidth = 12;
		$descripcionColWidth = 70;
		$importeColWidth = 25;
		$costoColWidth = 50;
		$pctColWidth = 10;
		$cuentaColWidth = 20;
		
		$this->Cell( $partidaColWidth, 8, "Partida", 1, 0, "C" );
		$this->Cell( $descripcionColWidth, 8, "Descripción", 1, 0, "C");
		$this->Cell( $importeColWidth, 8, "Importe", 1, 0, "C");
		$this->Cell( $costoColWidth, 8, "Aplicación de Costo", 1, 0, "C");
		$this->Cell( $pctColWidth, 8, "%", 1, 0, "C");
		$this->Cell( $cuentaColWidth, 8, "Cuenta", 1, 1, "C");

		$datos = array();

		for ($i=1; $i <= 20; $i++) { 
			
			$datos[] = array(
				'NoPartida' => $i,
				'Concepto' => 'Concepto de Estimación Prueba: ' . $i,
				'Importe' => 1233.00 + ($i*100),
				'AplicacionCosto' => 'Aplicado en concepto de costo: ' . ($i*10),
				'Porcentaje' => ($i/100),
				'Cuenta' => ($i*36)
			);
		}

		foreach ($datos as $dato) {

			$this->MultiCell( $partidaColWidth, 5, $dato['NoPartida'], 1, '', false, 0 );
			$this->MultiCell( $descripcionColWidth, 5, $dato['Concepto'], 1, '', false, 0);
			$this->MultiCell( $importeColWidth, 5, $dato['Importe'], 1, "R", false, 0);
			$this->MultiCell( $costoColWidth, 5, $dato['AplicacionCosto'], 1, '', false, 0);
			$this->MultiCell( $pctColWidth, 5, $dato['Porcentaje'], 1, "C", false, 0);
			$this->MultiCell( $cuentaColWidth, 5, $dato['Cuenta'], 1, '', false, 0);
			$this->Ln();
		}
	}

	protected function setPiePagina() {}

	public function Output() {
		parent::Output(self::FORMATO_NOMBRE_ARCHIVO, 'I');
	}
}

$formatoPDF = new SubcontratoEstimacionFormatoPFD();
$formatoPDF->Output();
?>