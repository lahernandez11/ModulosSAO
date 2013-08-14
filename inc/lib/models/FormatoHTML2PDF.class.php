<?php
set_include_path(get_include_path().";".$_SERVER["DOCUMENT_ROOT"]."\ModulosSAO\inc\lib");
require_once 'html2pdf/html2pdf.class.php';

abstract class FormatoPDF extends HTML2PDF {
	
	const PDF_PAGE_ORIENTATION = 'P';
	const PDF_LANGUAGE		   = 'es';
	const PDF_UNIT 			   = 'mm';
	const PDF_PAGE_FORMAT 	   = 'A4';
	const PDF_USE_UNICODE 	   = true;
	const PDF_UNICODE_CODEPAGE = 'UTF-8';
	// const PDF_DISK_CACHE 	   = false;
	// const PDF_PDFA_MODE 	   = false;
	// const PDF_DEFAULT_FONT	   = 'helvetica';
	// const PDF_DEFAULT_FONTSIZE = 8;

	// const PDF_MARGIN_HEADER    = 0;
	// const PDF_MARGIN_FOOTER	   = 0;

	const PDF_MARGIN_TOP	   = 5;
	const PDF_MARGIN_BOTTOM	   = 5;
	const PDF_MARGIN_LEFT	   = 5;
	const PDF_MARGIN_RIGHT	   = 5;

	public function __construct() {
		parent::__construct(
			self::PDF_PAGE_ORIENTATION, self::PDF_PAGE_FORMAT, self::PDF_LANGUAGE,
			self::PDF_USE_UNICODE, self::PDF_UNICODE_CODEPAGE,
			array(self::PDF_MARGIN_LEFT, self::PDF_MARGIN_TOP, self::PDF_MARGIN_RIGHT, self::PDF_MARGIN_BOTTOM)
		);
	}

	// protected function init() {
	// 	$this->SetAutoPageBreak(TRUE);
	// 	//$this->SetDefaultMonospacedFont(self::PDF_DEFAULT_FONT);
	// 	// $this->SetFontSize(self::PDF_DEFAULT_FONTSIZE);
	// 	// $this->SetMargins(self::PDF_MARGIN_LEFT, self::PDF_MARGIN_TOP, self::PDF_MARGIN_RIGHT);
	// 	// $this->SetHeaderMargin(self::PDF_MARGIN_HEADER);
	// 	// $this->SetFooterMargin(self::PDF_MARGIN_FOOTER);
	// 	// $this->setPrintHeader(false);
	// 	// $this->setPrintFooter(false);
	// }

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
		$this->pdf->SetDisplayMode('fullpage');
		// parent::init();
		// $this->SetPageOrientation(self::PDF_PAGE_ORIENTATION);
	}

	protected function setEncabezado() {}

	protected function setContenidoPrincipal() {

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

		$html = '
		<style>
		.formato-header {
			font-size: 7pt;
			font-family: helvetica;
			padding: 5pt 0;
		}
		.formato-header table {
			border-collapse: collapse;
			width: 100%;
		}
		.formato-header table td,
		.formato-header table th {
			border: 1px solid black;
			padding: 1pt 3pt;
		}
		.formato-header table td {
			width: 30%;
		}
		.formato-header .formato-titulo {
			width: 435px;
			background-color: #CCC;
			font-size: 12pt;
			text-align: center;
			vertical-align: middle;
		}

		.formato-content {
			font-size: 6pt;
			padding: 5pt 0;
		}
		.formato-content table {
			border-collapse: collapse;
			border: 1px solid black;
			width: 100%;
		}
		.formato-content table thead {
			text-align: center;
		}
		.formato-content table .cantidad,
		.formato-content table .importe {
			text-align: right;
		}
		.formato-content table .cantidad {
			width: 40px;
		}
		.formato-content table .importe {
			width: 50px;
		}
		.formato-content table th,
		.formato-content table td {
			border: 0.5pt solid black;
			padding: 1px 5px;
			vertical-align: middle;
		}
		
		</style>
		<page orientation="L">
		<div class="formato-header">
			<table>
				<tbody>
					<tr>
						<th style="width: 280px;">SUBCONTRATO REF</th>
						<th></th>
						<th rowspan="4" class="formato-titulo">ESTIMACIÓN DE OBRA EJECUTADA</th>
					</tr>
					<tr>
						<th>SUBCONTRATISTA</th>
						<td>HKS - ARQUITECTOS S. DE RL DE CV</td>
					</tr>
					<tr>
						<th>ESTIMACIÓN NUMERO</th>
						<td>5</td>
					</tr>
					<tr>
						<th>PERIODO</th>
						<td>Hasta el 14 de Junio 2013</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="formato-content">
			<table>
				<col style="width: 280px">
				<col style="width: 10px">
				<thead>
					<tr>
						<th rowspan="2">Concepto</th>
						<th rowspan="2">UM</th>
						<th rowspan="2">Precio Unitario</th>
						<th colspan="2">Contrato y Aditamentos</th>
						<th colspan="2">Acum. Estimación Anterior</th>
						<th colspan="2">Esta Estimación</th>
						<th colspan="2">Acum. a esta Estimación</th>
						<th colspan="2">Saldo por Estimar</th>
					</tr>
					<tr>
						<th>Cantidad</th>
						<th>Importe</th>
						<th>Cantidad</th>
						<th>Importe</th>
						<th>Cantidad</th>
						<th>Importe</th>
						<th>Cantidad</th>
						<th>Importe</th>
						<th>Cantidad</th>
						<th>Importe</th>
					</tr>
					<tr><td colspan="13"></td></tr>
					<tr>
						<th colspan="2">OBRA EJECUTADA</th>
						<td>USD</td>
						<td></td>
						<td>USD</td>
						<td></td>
						<td>USD</td>
						<td></td>
						<td>USD</td>
						<td></td>
						<td>USD</td>
						<td></td>
						<td>USD</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>test de texte assez long pour engendrer des retours à la ligne automatique...</td>
						<td>Lote</td>
						<td class="importe">$ 128,000.00</td>
						<td class="cantidad">1.00</td>
						<td class="importe">$ 128,000.00</td>
						<td class="cantidad">1.00</td>
						<td class="importe">$ 128,000.00</td>
						<td class="cantidad">-</td>
						<td class="importe">$-</td>
						<td class="cantidad">1.00</td>
						<td class="importe">$ 128,000.00</td>
						<td class="cantidad">-</td>
						<td class="importe">$ -</td>
					</tr>
					<tr>
						<td>Diseño Esquematico</td>
						<td>Lote</td>
						<td>$237,000.00</td>
						<td>1.00</td>
						<td>$237,000.00</td>
						<td>0.78</td>
						<td>$184,782.85</td>
						<td>0.17</td>
						<td>$40,367.15</td>
						<td>0.95</td>
						<td>$225,150.00</td>
						<td>0.05</td>
						<td>$11,850,098.00</td>
					</tr>
				</tbody>
			</table>
		</div>
		</page>';

		// foreach ($datos as $dato) {

		// 	$this->MultiCell( $partidaColWidth, 5, $dato['NoPartida'], 1, '', false, 0 );
		// 	$this->MultiCell( $descripcionColWidth, 5, $dato['Concepto'], 1, '', false, 0);
		// 	$this->MultiCell( $importeColWidth, 5, $dato['Importe'], 1, "R", false, 0);
		// 	$this->MultiCell( $costoColWidth, 5, $dato['AplicacionCosto'], 1, '', false, 0);
		// 	$this->MultiCell( $pctColWidth, 5, $dato['Porcentaje'], 1, "C", false, 0);
		// 	$this->MultiCell( $cuentaColWidth, 5, $dato['Cuenta'], 1, '', false, 0);
		// 	$this->Ln();
		// }

		$this->writeHTML($html);
	}

	protected function setPiePagina() {}

	public function Output() {
		parent::Output(self::FORMATO_NOMBRE_ARCHIVO, 'I');
	}
}

$formatoPDF = new SubcontratoEstimacionFormatoPFD();
$formatoPDF->Output();
?>