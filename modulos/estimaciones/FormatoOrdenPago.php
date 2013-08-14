<?php
set_include_path(get_include_path().";".$_SERVER["DOCUMENT_ROOT"]."\\ModulosSAO\\inc");

include("fpdf/fpdf.php");
include("DBConn.php");

class FormatoOrdenPagoPDF extends FPDF {

	public $proyecto       = '';
	private $contratista   = '';
	private $fecha         = '';
	private $fechaInicio   = '';
	private $fechaTermino  = '';
	private $montoContrato = 0.0;

	public function Header() {

		// Incluir el logotipo de la obra
		$this->Image("logo_zap.png", 10, 10);

		$this->SetFont( "Arial", "", 11 );
		$this->SetX(80);
		$this->Cell( 0, 10, "PRESA EL ZAPOTILLO", 0, 1, "C" );
		$this->Ln(20);
	}

	public function Footer() {

	}

	public function renderDatosGenerales( $conn ) {

		$tsql = "{call [EstimacionesSubcontratos].[uspFormatoOrdenPagoDatosGenerales]( ?, ?, ?, ?, ?, ?, ? )}";

		$params = array(
			array($_GET[IDEstimacion], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
			array($this->proyecto, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING("UTF-8"), SQLSRV_SQLTYPE_VARCHAR(100)),
			array($this->contratista, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING("UTF-8"), SQLSRV_SQLTYPE_VARCHAR(100)),
			array($this->fecha, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING("UTF-8"), SQLSRV_SQLTYPE_VARCHAR(10)),
			array($this->fechaInicio, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING("UTF-8"), SQLSRV_SQLTYPE_VARCHAR(10)),
			array($this->fechaTermino, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING("UTF-8"), SQLSRV_SQLTYPE_VARCHAR(10)),
			array($this->montoContrato, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_FLOAT, SQLSRV_SQLTYPE_DECIMAL(19,2))
		);

		$stmt = sqlsrv_query( $conn, $tsql, $params );

		if ( ! $stmt )
			$this->Error(getErrorMessage());

		$this->SetFont( "Arial", "", 8);
		$this->AddPage();
		
		$headerLabelWidth = 50;
		$headerFieldWidth = 100;
		$headerFieldHeight = 5;

		$this->setFont( "Arial", "", 12 );
		$this->Cell( $headerLabelWidth, $headerFieldHeight, "Subcontrato No. :", 0, 0, "R" );
		$this->Line($this->GEtX(), $this->GetY() + $headerFieldHeight, $this->GEtX() + $headerFieldWidth, $this->GetY() + $headerFieldHeight);
		$this->Ln(10);
		
		$this->Cell( $headerLabelWidth, $headerFieldHeight, "Objeto del Contrato :", 0, 0, "R" );
		$this->Cell( $headerFieldWidth, 12, "", 1 );
		$this->Ln(13);

		$this->setFont( "Arial", "", 8 );

		$this->Cell( $headerLabelWidth, $headerFieldHeight, "Fecha :", 0, 0, "R" );
		$this->Line($this->GEtX(), $this->GetY() + $headerFieldHeight, $this->GEtX() + $headerFieldWidth, $this->GetY() + $headerFieldHeight);
		$this->Cell( $headerFieldWidth, $headerFieldHeight, $this->fecha, 0, 0, "R" );
		$this->Ln(6);

		$this->Cell( $headerLabelWidth, $headerFieldHeight, "Monto del Contrato :", 0, 0, "R" );
		$this->Line($this->GEtX(), $this->GetY() + $headerFieldHeight, $this->GEtX() + $headerFieldWidth, $this->GetY() + $headerFieldHeight);
		$this->Cell( $headerFieldWidth, $headerFieldHeight, "$ ".number_format($this->montoContrato), 0, 0, "R" );
		$this->Ln(6);

		$this->Cell( $headerLabelWidth, $headerFieldHeight, "Contratista :", 0, 0, "R" );
		$this->Line($this->GEtX(), $this->GetY() + $headerFieldHeight, $this->GEtX() + $headerFieldWidth, $this->GetY() + $headerFieldHeight);
		$this->Cell( $headerFieldWidth, $headerFieldHeight, $this->contratista );
		$this->Ln(6);

		$this->Cell( $headerLabelWidth, $headerFieldHeight, utf8_decode("Estimación :"), 0, 0, "R" );
		$this->Line($this->GEtX(), $this->GetY() + $headerFieldHeight, $this->GEtX() + $headerFieldWidth, $this->GetY() + $headerFieldHeight);
		$this->Cell( $headerFieldWidth, $headerFieldHeight );
		$this->Ln(6);

		$this->Cell( $headerLabelWidth, $headerFieldHeight, "Periodo :", 0, 0, "R" );
		$this->Line($this->GEtX(), $this->GetY() + $headerFieldHeight, $this->GEtX() + $headerFieldWidth, $this->GetY() + $headerFieldHeight);
		$this->Cell( $headerFieldWidth, $headerFieldHeight, "Del:    ".$this->fechaInicio."    Al:    ".$this->fechaTermino, 0, 0, "C" );
		$this->Ln(10);
	}

	public function renderConceptosEstimados( $conn ) {

		$tsql = "{call [EstimacionesSubcontratos].[uspFormatoOrdenPagoConceptos]( ? )}";

		$params = array(
			array($_GET[IDEstimacion], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
		);

		$stmt = sqlsrv_query( $conn, $tsql, $params );

		if ( ! $stmt )
			$this->Error(getErrorMessage());

		// Table header
		$this->SetFont( "Arial", "", 9 );
		$this->SetFillColor( 228, 228, 228);
		$partidaColWidth = 12;
		$descripcionColWidth = 70;
		$importeColWidth = 25;
		$costoColWidth = 50;
		$pctColWidth = 10;
		$cuentaColWidth = 20;

		$this->Cell( $partidaColWidth, 8, "Partida", 1, 0, "C", true );
		$this->Cell( $descripcionColWidth, 8, utf8_decode("Descripción"), 1, 0, "C", true );
		$this->Cell( $importeColWidth, 8, "Importe", 1, 0, "C", true );
		$this->Cell( $costoColWidth, 8, utf8_decode("Aplicación de Costo"), 1, 0, "C", true );
		$this->Cell( $pctColWidth, 8, "%", 1, 0, "C", true );
		$this->Cell( $cuentaColWidth, 8, "Cuenta", 1, 1, "C", true );

		while( $dataRow = sqlsrv_fetch_object($stmt) ) {

			$this->Cell( $partidaColWidth, 5, "", 1 );
			$this->Cell( $descripcionColWidth, 5, $dataRow->Concepto, 1, 0, "L", 1);
			$this->Cell( $importeColWidth, 5, number_format($dataRow->ImporteEstimado), 1, 0, "R");
			$this->Ln();
		}

		sqlsrv_free_stmt($stmt);
	}

	private function getContentHeader() {

	}
}

$conn = modulosSAO();

if( ! $conn ) {
	echo 'No se pudo establecer una conexión con el servidor de Base de Datos';
	return;
}

$formato = new FormatoOrdenPagoPDF();
$formato->renderDatosGenerales( $conn );
$formato->renderConceptosEstimados( $conn );
$formato->Output();
?>