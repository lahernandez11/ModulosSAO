<?php
require_once 'models/TransaccionSAO.class.php';
require_once 'models/Empresa.class.php';
require_once 'models/Moneda.class.php';
require_once 'models/Subcontrato.class.php';
require_once 'models/EstimacionDeductiva.class.php';

class EstimacionSubcontrato extends TransaccionSAO {
	
	const TIPO_TRANSACCION = 52;

	public $subcontrato;
	public $empresa;
	public $moneda;

	private $fecha_inicio;
	private $fecha_termino;
	private $numero_folio_consecutivo;
	private $pct_anticipo;
	private $pct_fondo_garantia;
	private $pct_iva;

	public function __construct() {
		
		$params = func_get_args();

		switch ( func_num_args() ) {

			case 7:
				call_user_func_array(array($this, "instaceFromDefault"), $params);
				break;

			case 2:
				call_user_func_array(array($this, "instanceFromID"), $params);
				break;
		}
	}

	private function instaceFromDefault( 
		Obra $obra, Subcontrato $subcontrato, $fecha, $fechaInicio, $fechaTermino, $observaciones,
		Array $conceptos ) {
		
		parent::__construct( $obra, self::TIPO_TRANSACCION, $fecha, $observaciones );

		$this->subcontrato = $subcontrato;
		$this->setFechaInicio( $fechaInicio );
		$this->setFechaTermino( $fechaTermino );
		$this->setConceptos( $conceptos );
	}

	private function instanceFromID( Obra $obra, $id_transaccion ) {
		parent::__construct( $obra, $id_transaccion );
		$this->setDatosGenerales();
	}

	// @override
	protected function setDatosGenerales() {
		
		parent::setDatosGenerales();

		$tsql = "SELECT
					  [transacciones].[id_transaccion]
					, [transacciones].[id_empresa]
					, [transacciones].[numero_folio]
					, [Estimaciones].[NumeroFolioConsecutivo]
					, [transacciones].[cumplimiento]
					, [transacciones].[vencimiento]
					, [transacciones].[observaciones]
					, [transacciones].[id_antecedente]
					, [transacciones].[id_moneda]
					, [transacciones].[anticipo]
					, [transacciones].[retencion]
					, IIF([transacciones].[monto] - [transacciones].[impuesto] = 0, 0,
					(
						[transacciones].[impuesto]
							/
						([transacciones].[monto] - [transacciones].[impuesto])
					) * 100) AS [porcentaje_iva]
				FROM
					[dbo].[transacciones]
				LEFT OUTER JOIN
					[SubcontratosEstimaciones].[Estimaciones]
					ON
						[transacciones].[id_transaccion] = [Estimaciones].[IDEstimacion]
				WHERE
					[transacciones].[tipo_transaccion] = 52
						AND
					[transacciones].[id_transaccion] = ?";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $datos = $this->conn->executeSP( $tsql, $params );

	    $this->subcontrato 			    = new Subcontrato( $this->obra, $datos[0]->id_antecedente );
	    $this->empresa 		   		    = new Empresa( $this->obra, $datos[0]->id_empresa);
	    $this->moneda 					= new Moneda( $this->obra, $datos[0]->id_moneda );

	    $this->numero_folio_consecutivo = $datos[0]->NumeroFolioConsecutivo;
	    $this->fecha_inicio 			= $datos[0]->cumplimiento;
	    $this->fecha_termino 			= $datos[0]->vencimiento;
	    $this->pct_anticipo 		    = $datos[0]->anticipo;
	    $this->pct_fondo_garantia 	    = $datos[0]->retencion;
	    $this->pct_iva 	   			    = $datos[0]->porcentaje_iva;
	}

	public function getConceptosEstimacion() {

		$tsql = "{call [SubcontratosEstimaciones].[uspConceptosEstimacion]( ?, ?, ?, ? )}";

	    $params = array(
	        array( $this->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( 0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT ),
	        array( 0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT )
	    );

	    $conceptos = $this->conn->executeSP( $tsql, $params );

	    return $conceptos;
	}

	public function guardaTransaccion( Usuario $usuario ) {

		if ( ! empty( $this->id_transaccion ) ) {

			$tsql = "{call [SubcontratosEstimaciones].[uspActualizaDatosGenerales]( ?, ?, ?, ?, ?, ? )}";

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
		        array( $this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
		        array( $this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
		        array( $this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096) ),
		        array( $usuario->getUsername(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16) ),
		    );

		    $this->conn->executeSP( $tsql, $params );
		} else {

			$tsql = "{call [SubcontratosEstimaciones].[uspRegistraTransaccion]( ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )}";

		    $params = array(
		        array( $this->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $this->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
		       	array( $this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
		        array( $this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
		        array( $this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096) ),
		        array( $usuario->getUsername(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16) ),
		        array( &$this->id_transaccion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT ),
		        array( &$this->_numeroFolio, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT ),
		        array( &$this->numero_folio_consecutivo, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT ),
		    );

		    $this->conn->executeSP( $tsql, $params );
		}

		$errores = array();

		$tsql = "{call [SubcontratosEstimaciones].[uspEstimaConcepto]( ?, ?, ?, ? )}";

		foreach ( $this->_conceptos as $concepto ) {
			
			try {
				// Limpia y valida el importe estimado
				$concepto['importeEstimado'] = str_replace(',', '', $concepto['importeEstimado']);

				// Si el importe no es valido agrega el concepto con error
				if( ! $this->esImporte($concepto['importeEstimado']) ) {
					throw new Exception("El numero ingresado no es correcto");
				}

				$params = array(
					array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
					array( $concepto['IDConceptoContrato'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
					array( $concepto['IDConceptoDestino'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
					array( $concepto['importeEstimado'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
				);

				$this->conn->executeSP( $tsql, $params );
			} catch( Exception $e ) {

				$errores[] = array(
					'IDConceptoContrato' => $concepto['IDConceptoContrato'],
					'importeEstimado'    => $concepto['importeEstimado'],
					'message' 		     => $e->getMessage()
				);
			}
		}

		return $errores;
	}

	public function eliminaTransaccion() {

		$tsql = "{call [SubcontratosEstimaciones].[uspEliminaTransaccion]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeSP( $tsql, $params );
	}

	public function getConceptosEstimados( $soloConceptosEstimados = 1 ) {

		$tsql = "{call [SubcontratosEstimaciones].[uspConceptosEstimacion]( ?, ?, ?, ? )}";

	    $params = array(
	        array( $this->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( 1, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT ),
	        array( $soloConceptosEstimados, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT )
	    );

	    $conceptos = $this->conn->executeSP( $tsql, $params );

	    return $conceptos;
	}

	public function getDeductivas() {

		return EstimacionDeductiva::getObjects( $this->empresa );
	}

	// public function agregaDeductivaMaterial( $id_material, $importe, $observaciones ) {

	// 	if ( ! $this->esImporte( $importe ) ) {
	// 		throw new Exception("Importe Incorrecto");
	// 	} else {
	// 		$tsql = "{call [SubcontratosEstimaciones].[uspRegistraDeductivaMaterial]( ?, ?, ?, ?, ? )}";

	// 		$IDDeductiva = 0;

	// 	    $params = array(
	// 	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	// 	        array( $id_material, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	// 	        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
	// 	        array( $observaciones, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
	// 	        array( $IDDeductiva, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
	// 	    );

	// 	    $this->conn->executeSP( $tsql, $params );
	// 	}

	//     return $IDDeductiva;
	// }

	// public function agregaDeductivaMaquinaria( $IDAlmacen, $importe, $observaciones ) {

	// 	if ( ! $this->esImporte( $importe ) ) {
	// 		throw new Exception("Importe Incorrecto");
	// 	} else {

	// 		$tsql = "{call [SubcontratosEstimaciones].[uspRegistraDeductivaMaquinaria]( ?, ?, ?, ?, ? )}";

	// 		$IDDeductiva = 0;

	// 	    $params = array(
	// 	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	// 	        array( $IDAlmacen, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	// 	        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
	// 	        array( $observaciones, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
	// 	        array( $IDDeductiva, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
	// 	    );

	// 	    $this->conn->executeSP( $tsql, $params );
	// 	}

	//     return $IDDeductiva;
	// }

	// public function agregaDeductivaManoObra( $IDCategoria, $importe, $observaciones ) {

	// 	if ( ! $this->esImporte( $importe ) ) {
	// 		throw new Exception("Importe Incorrecto");
	// 	} else {

	// 		$tsql = "{call [SubcontratosEstimaciones].[uspRegistraDeductivaManoObra]( ?, ?, ?, ?, ? )}";

	// 		$IDDeductiva = 0;

	// 	    $params = array(
	// 	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	// 	        array( $IDCategoria, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	// 	        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
	// 	        array( $observaciones, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
	// 	        array( $IDDeductiva, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
	// 	    );

	// 	    $this->conn->executeSP($tsql, $params);
	// 	}

	//     return $IDDeductiva;
	// }

	// public function agregaDeductivaSubcontratos( $concepto, $importe, $observaciones ) {

	// 	if ( ! $this->esImporte( $importe ) ) {
	// 		throw new Exception("Importe Incorrecto");
	// 	} else {

	// 		$tsql = "{call [SubcontratosEstimaciones].[uspRegistraDeductivaSubcontratos]( ?, ?, ?, ?, ? )}";

	// 		$IDDeductiva = 0;

	// 	    $params = array(
	// 	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	// 	        array( $concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
	// 	        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
	// 	        array( $observaciones, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
	// 	        array( $IDDeductiva, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
	// 	    );

	// 	    $this->conn->executeSP($tsql, $params);
	// 	}

	//     return $IDDeductiva;
	// }

	// public function agregaDeductivaOtros( $concepto, $importe, $observaciones ) {

	// 	if ( ! $this->esImporte( $importe ) ) {
	// 		throw new Exception("Importe Incorrecto");
	// 	} else {

	// 		$tsql = "{call [SubcontratosEstimaciones].[uspRegistraDeductivaOtros]( ?, ?, ?, ?, ? )}";

	// 		$IDDeductiva = 0;
			
	// 	    $params = array(
	// 	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	// 	        array( $concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
	// 	        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
	// 	        array( $observaciones, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
	// 	        array( $IDDeductiva, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
	// 	    );

	// 	    $this->conn->executeSP( $tsql, $params );
	// 	}

	//     return $IDDeductiva;
	// }

	// public function eliminaDeductiva( $IDDeductiva ) {

	// 	$tsql = "{call [SubcontratosEstimaciones].[uspEliminaDeductiva]( ?, ? )}";

	//     $params = array(
	//         array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	//         array( $IDDeductiva, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	//     );

	//     $this->conn->executeSP( $tsql, $params );
	// }

	public function getRetenciones() {

		$tsql = "{call [SubcontratosEstimaciones].[uspRetenciones]( ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    return $this->conn->executeSP( $tsql, $params );
	}

	public function agregaRetencion( $IDTipoRetencion, $importe, $concepto, $observaciones ) {

		if ( ! is_int( $IDTipoRetencion ) || ! $IDTipoRetencion > 0 ) {
			throw new Exception("El tipo de retención no es válido");
			return;
		}

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {

			$tsql = "{call [SubcontratosEstimaciones].[uspRegistraRetencion]( ?, ?, ?, ?, ?, ? )}";

			$IDRetencion = 0;
			
		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $IDTipoRetencion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
		        array( $concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		        array( $observaciones, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		        array( &$IDRetencion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
		    );

		    $this->conn->executeSP( $tsql, $params );
		}

	    return $IDRetencion;
	}

	public function eliminaRetencion( $IDRetencion ) {

		$tsql = "{call [SubcontratosEstimaciones].[uspEliminaRetencion]( ?, ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $IDRetencion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeSP( $tsql, $params );
	}

	public function agregaLiberacion( $importe, $observaciones, Usuario $usuario ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {

			$tsql = "{call [SubcontratosEstimaciones].[uspRegistraLiberacionRetencion]( ?, ?, ?, ?, ? )}";

			$IDLiberacion = null;

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
		        array( $observaciones, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		        array( $usuario->getUsername(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('20') ),
		        array( $IDLiberacion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT ),
		    );

		    $this->conn->executeSP( $tsql, $params );
		}

	    return $IDLiberacion;
	}

	public function eliminaLiberacion( $IDLiberacion ) {

		$tsql = "{call [SubcontratosEstimaciones].[uspEliminaLiberacionRetencion]( ?, ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $IDLiberacion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeSP( $tsql, $params );
	}

	public function getLiberaciones() {

		$tsql = "{call [SubcontratosEstimaciones].[uspRetencionesLiberadas]( ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    return $this->conn->executeSP( $tsql, $params );
	}

	public function getImporteRetenidoPorLiberar() {

		$tsql = "{call [SubcontratosEstimaciones].[uspImporteRetenidoPorLiberar]( ?, ? )}";

		$importePorLiberar = null;

	    $params = array(
	        array( $this->getIDSubcontrato(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( &$importePorLiberar, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
	    );

	    $this->conn->executeSP( $tsql, $params );

	    return $importePorLiberar;
	}

	public function getPctIVA() {
		return $this->pct_iva;
	}

	public function getPctAnticipo() {
		return $this->pct_anticipo;
	}

	public function getPctFondoGarantia() {
		return $this->pct_fondo_garantia;
	}
	
	public function getTotalesTransaccion() {

		$tsql = "{call [SubcontratosEstimaciones].[uspTotalesTransaccion]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $totales = $this->conn->executeSP( $tsql, $params );

	    return $totales;
	}

	public function getNumeroFolioConsecutivo() {
		return $this->numero_folio_consecutivo;
	}

	public function getFechaInicio() {
		return $this->fecha_inicio;
	}

	public function setFechaInicio( $fecha ) {

		if ( $this->fechaEsValida( $fecha ) )
			$this->fecha_inicio = $fecha;
		else
			throw new Exception("El formato de fecha inicial es incorrecto.");
	}

	public function getFechaTermino() {
		return $this->fecha_termino;
	}

	public function setFechaTermino( $fecha ) {
		
		if ( $this->fechaEsValida( $fecha ) )
			$this->fecha_termino = $fecha;
		else
			throw new Exception("El formato de fecha término es incorrecto.");
		
	}

	public function setConceptos( Array $conceptos ) {
		$this->_conceptos = $conceptos;
	}

	public function setImporteAmortizacionAnticipo( $importe ) {
		
		if ( ! $this->esImporte( $importe ) ) {
			$importe = 0;
		}

		$tsql = "{call [SubcontratosEstimaciones].[uspActualizaTotales]( ?, ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
	    );

	    $this->conn->executeSP( $tsql, $params );
	}

	public function setImporteFondoGarantia( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			$importe = 0;
		}

		$tsql = "{call [SubcontratosEstimaciones].[uspActualizaTotales]( ?, ?, ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        null,
	        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
	    );

	    $this->conn->executeSP( $tsql, $params );
	}

	public function setImporteRetencionIVA( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			$importe = 0;
		}

		$tsql = "{call [SubcontratosEstimaciones].[uspActualizaTotales]( ?, ?, ?, ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        null,
	        null,
	        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
	    );

	    $this->conn->executeSP( $tsql, $params );
	}

	public function setImporteAnticipoLiberar( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			$importe = 0;
		}

		$tsql = "{call [SubcontratosEstimaciones].[uspActualizaTotales]( ?, ?, ?, ?, ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        null,
	        null,
	        null,
	        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
	    );

	    $this->conn->executeSP( $tsql, $params );
	}

	public static function getConceptosNuevaEstimacion( Subcontrato $subcontrato ) {

		$tsql = "{call [SubcontratosEstimaciones].[uspConceptosEstimacion]( ?, ?, ?, ? )}";

	    $params = array(
	        array( $subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( null, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( 0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( 0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $conceptos = $subcontrato->obra->getConn()->executeSP( $tsql, $params );

	    return $conceptos;
	}

	public static function getFoliosTransaccion( Obra $obra ) {

		$tsql = '{call [SubcontratosEstimaciones].[uspListaFolios]( ? )}';

		$params = array(
	        array( $obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $foliosTran = $obra->getConn()->executeSP( $tsql, $params );

		return $foliosTran;
	}

	public static function getListaTransacciones( Obra $obra, $tipo_transaccion=null ) {

		return parent::getListaTransacciones( $obra, self::TIPO_TRANSACCION );
	}

	public static function getTiposRetencion( SAODBConn $conn ) {

		$tsql = '{call [SubcontratosEstimaciones].[uspTiposRetencion]}';

	    $lista = $conn->executeSP( $tsql );

		return $lista;
	}
}
?>