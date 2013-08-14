<?php
require_once 'models/TransaccionSAO.class.php';
require_once 'models/Subcontrato.class.php';

class EstimacionSubcontrato extends TransaccionSAO {
	
	const TIPO_TRANSACCION = 52;

	private $_IDSubcontrato;
	private $_fechaInicio;
	private $_fechaTermino;
	private $_numeroFolioConsecutivo;
	private $_IDContratista;
	private $_nombreContratista;
	private $_objetoSubcontrato;
	private $_tipoMoneda;
	private $_pctAnticipo;
	private $_pctFondoGarantia;
	private $_pctIVA;
	public $subcontrato;

	public function __construct() {
		
		$params = func_get_args();

		switch ( func_num_args() ) {

			case 8:
				call_user_func_array(array($this, "instaceFromDefault"), $params);
				break;

			case 2:
				call_user_func_array(array($this, "instanceFromID"), $params);
				break;
		}
	}

	private function instaceFromDefault( 
		$IDObra, $IDSubcontrato, $fecha, $fechaInicio, $fechaTermino, $observaciones,
		Array $conceptos, SAODBConn $conn ) {
		
		parent::__construct($IDObra, self::TIPO_TRANSACCION, $fecha, $observaciones, $conn);

		$this->_IDSubcontrato = $IDSubcontrato;
		$this->setFechaInicio( $fechaInicio );
		$this->setFechaTermino( $fechaTermino );
		$this->setConceptos( $conceptos );
	}

	private function instanceFromID( $IDTransaccion, SAODBConn $conn ) {
		parent::__construct( $IDTransaccion, $conn );
		
		$this->setDatosGenerales();
		$this->subcontrato = new Subcontrato($this->getIDSubcontrato(), $conn);
	}

	// @override
	protected function setDatosGenerales() {
		
		parent::setDatosGenerales();

		$tsql = "{call [SubcontratosEstimaciones].[uspDatosGenerales]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $datos = $this->_SAOConn->executeSP($tsql, $params);

	    $this->_IDSubcontrato 		   = $datos[0]->IDSubcontrato;
	    $this->_objetoSubcontrato 	   = $datos[0]->ObjetoSubcontrato;
	    $this->_IDContratista 		   = $datos[0]->IDContratista;
	    $this->_nombreContratista 	   = $datos[0]->NombreContratista;
	    $this->_numeroFolioConsecutivo = $datos[0]->NumeroFolioConsecutivo;
	    $this->setFechaInicio($datos[0]->FechaInicio);
	    $this->setFechaTermino($datos[0]->FechaTermino);
	    $this->_tipoMoneda 			   = $datos[0]->TipoMoneda;
	    $this->_pctAnticipo 		   = $datos[0]->PctAnticipo;
	    $this->_pctFondoGarantia 	   = $datos[0]->PctFondoGarantia;
	    $this->_pctIVA 	   			   = $datos[0]->PctIVA;
	}

	private function esImporte( $importe ) {

		return preg_match('/^-?\d+(\.\d+)?$/', $importe );
	}

	public function getConceptosEstimacion() {

		$tsql = "{call [SubcontratosEstimaciones].[uspConceptosEstimacion]( ?, ?, ?, ? )}";

	    $params = array(
	        array( $this->getIDSubcontrato(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( 0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT ),
	        array( 0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT )
	    );

	    $conceptos = $this->_SAOConn->executeSP($tsql, $params);

	    return $conceptos;
	}

	public function guardaTransaccion() {

		if ( ! empty($this->_IDTransaccion) ) {

			$tsql = "{call [SubcontratosEstimaciones].[uspActualizaDatosGenerales]( ?, ?, ?, ?, ?, ? )}";

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
		        array( $this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
		        array( $this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
		        array( $this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096) ),
		        array( Sesion::getCuentaUsuarioSesion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16) ),
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		} else {

			$tsql = "{call [SubcontratosEstimaciones].[uspRegistraTransaccion]( ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )}";

		    $params = array(
		        array( $this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $this->getIDSubcontrato(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
		       	array( $this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
		        array( $this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
		        array( $this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096) ),
		        array( Sesion::getCuentaUsuarioSesion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16) ),
		        array( &$this->_IDTransaccion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT ),
		        array( &$this->_numeroFolio, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT ),
		        array( &$this->_numeroFolioConsecutivo, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT ),
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
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

				$this->_SAOConn->executeSP($tsql, $params);
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

	    $this->_SAOConn->executeSP($tsql, $params);
	}

	public function getConceptosEstimados( $soloConceptosEstimados = 1 ) {

		$tsql = "{call [SubcontratosEstimaciones].[uspConceptosEstimacion]( ?, ?, ?, ? )}";

	    $params = array(
	        array( $this->getIDSubcontrato(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( 1, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT ),
	        array( $soloConceptosEstimados, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT )
	    );

	    $conceptos = $this->_SAOConn->executeSP($tsql, $params);

	    return $conceptos;
	}

	public function getDeductivas() {

		$tsql = "{call [SubcontratosEstimaciones].[uspDeductivas]( ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    return $this->_SAOConn->executeSP($tsql, $params);
	}

	public function agregaDeductivaMaterial( $IDMaterial, $importe, $observaciones ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {
			$tsql = "{call [SubcontratosEstimaciones].[uspRegistraDeductivaMaterial]( ?, ?, ?, ?, ? )}";

			$IDDeductiva = 0;

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $IDMaterial, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
		        array( $observaciones, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		        array( $IDDeductiva, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}

	    return $IDDeductiva;
	}

	public function agregaDeductivaMaquinaria( $IDAlmacen, $importe, $observaciones ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {

			$tsql = "{call [SubcontratosEstimaciones].[uspRegistraDeductivaMaquinaria]( ?, ?, ?, ?, ? )}";

			$IDDeductiva = 0;

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $IDAlmacen, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
		        array( $observaciones, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		        array( $IDDeductiva, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}

	    return $IDDeductiva;
	}

	public function agregaDeductivaManoObra( $IDCategoria, $importe, $observaciones ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {

			$tsql = "{call [SubcontratosEstimaciones].[uspRegistraDeductivaManoObra]( ?, ?, ?, ?, ? )}";

			$IDDeductiva = 0;

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $IDCategoria, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
		        array( $observaciones, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		        array( $IDDeductiva, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}

	    return $IDDeductiva;
	}

	public function agregaDeductivaSubcontratos( $concepto, $importe, $observaciones ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {

			$tsql = "{call [SubcontratosEstimaciones].[uspRegistraDeductivaSubcontratos]( ?, ?, ?, ?, ? )}";

			$IDDeductiva = 0;

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
		        array( $observaciones, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		        array( $IDDeductiva, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}

	    return $IDDeductiva;
	}

	public function agregaDeductivaOtros( $concepto, $importe, $observaciones ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {

			$tsql = "{call [SubcontratosEstimaciones].[uspRegistraDeductivaOtros]( ?, ?, ?, ?, ? )}";

			$IDDeductiva = 0;
			
		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
		        array( $observaciones, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		        array( $IDDeductiva, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}

	    return $IDDeductiva;
	}

	public function eliminaDeductiva( $IDDeductiva ) {

		$tsql = "{call [SubcontratosEstimaciones].[uspEliminaDeductiva]( ?, ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $IDDeductiva, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->_SAOConn->executeSP($tsql, $params);
	}

	public function getRetenciones() {

		$tsql = "{call [SubcontratosEstimaciones].[uspRetenciones]( ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    return $this->_SAOConn->executeSP($tsql, $params);
	}

	public function agregaRetencion( $IDTipoRetencion, $importe, $concepto, $observaciones ) {

		if ( ! is_int($IDTipoRetencion) || ! $IDTipoRetencion > 0 ) {
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

		    $this->_SAOConn->executeSP($tsql, $params);
		}

	    return $IDRetencion;
	}

	public function eliminaRetencion( $IDRetencion ) {

		$tsql = "{call [SubcontratosEstimaciones].[uspEliminaRetencion]( ?, ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $IDRetencion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->_SAOConn->executeSP($tsql, $params);
	}

	public function agregaLiberacion( $importe, $observaciones, $nombreCuentaUsuario ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {

			$tsql = "{call [SubcontratosEstimaciones].[uspRegistraLiberacionRetencion]( ?, ?, ?, ?, ? )}";

			$IDLiberacion = null;

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
		        array( $observaciones, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		        array( $nombreCuentaUsuario, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('20') ),
		        array( $IDLiberacion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT ),
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}

	    return $IDLiberacion;
	}

	public function eliminaLiberacion( $IDLiberacion ) {

		$tsql = "{call [SubcontratosEstimaciones].[uspEliminaLiberacionRetencion]( ?, ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $IDLiberacion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->_SAOConn->executeSP($tsql, $params);
	}

	public function getLiberaciones() {

		$tsql = "{call [SubcontratosEstimaciones].[uspRetencionesLiberadas]( ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    return $this->_SAOConn->executeSP($tsql, $params);
	}

	public function getImporteRetenidoPorLiberar() {

		$tsql = "{call [SubcontratosEstimaciones].[uspImporteRetenidoPorLiberar]( ?, ? )}";

		$importePorLiberar = null;

	    $params = array(
	        array( $this->getIDSubcontrato(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( &$importePorLiberar, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
	    );

	    $this->_SAOConn->executeSP($tsql, $params);

	    return $importePorLiberar;
	}

	public function getPctIVA() {
		return $this->_pctIVA;
	}

	public function getPctAnticipo() {
		return $this->_pctAnticipo;
	}

	public function getPctFondoGarantia() {
		return $this->_pctFondoGarantia;
	}

	public function getTipoMoneda() {
		return $this->_tipoMoneda;
	}
	
	public function getIDSubcontrato() {
		return $this->_IDSubcontrato;
	}

	public function getTotalesTransaccion() {

		$tsql = "{call [SubcontratosEstimaciones].[uspTotalesTransaccion]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $totales = $this->_SAOConn->executeSP($tsql, $params);

	    return $totales;
	}

	public function getContratista() {
		return $this->_nombreContratista;
	}

	public function getObjetoSubcontrato() {
		return $this->_objetoSubcontrato;
	}

	public function getNumeroFolioConsecutivo() {
		return $this->_numeroFolioConsecutivo;
	}

	public function getFechaInicio() {
		return $this->_fechaInicio;
	}

	public function setFechaInicio( $fecha ) {
		
		if ( ! $this->fechaEsValida( $fecha ) )
			throw new Exception("El formato de fecha inicial es incorrecto.");
		
		$this->_fechaInicio = $fecha;
	}

	public function getFechaTermino() {
		return $this->_fechaTermino;
	}

	public function setFechaTermino( $fecha ) {
		
		if ( ! $this->fechaEsValida( $fecha ) )
			throw new Exception("El formato de fecha término es incorrecto.");
		
		$this->_fechaTermino = $fecha;
	}

	public function setConceptos( Array $conceptos ) {
		$this->_conceptos = $conceptos;
	}

	public function setImporteAmortizacionAnticipo( $importe ) {
		
		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {
			$tsql = "{call [SubcontratosEstimaciones].[uspActualizaTotales]( ?, ? )}";

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}
	}

	public function setImporteFondoGarantia( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {
			$tsql = "{call [SubcontratosEstimaciones].[uspActualizaTotales]( ?, ?, ? )}";

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        null,
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}
	}

	public function setImporteRetencionIVA( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {
			$tsql = "{call [SubcontratosEstimaciones].[uspActualizaTotales]( ?, ?, ?, ? )}";

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        null,
		        null,
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}
	}

	public function setImporteAnticipoLiberar( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {
			$tsql = "{call [SubcontratosEstimaciones].[uspActualizaTotales]( ?, ?, ?, ?, ? )}";

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        null,
		        null,
		        null,
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}
	}

	public static function getConceptosNuevaEstimacion( $IDObra, $IDSubcontrato, SAODBConn $conn ) {

		$tsql = "{call [SubcontratosEstimaciones].[uspConceptosEstimacion]( ?, ?, ?, ? )}";

	    $params = array(
	        array( $IDSubcontrato, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( null, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( 0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( 0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $conceptos = $conn->executeSP($tsql, $params);

	    return $conceptos;
	}

	public static function getFoliosTransaccion( $IDObra, SAODBConn $conn) {

		if ( ! is_int($IDObra) )
			throw new Exception("El identificador de la obra no es correcto.");

		$tsql = '{call [SubcontratosEstimaciones].[uspListaFolios]( ? )}';

		$params = array(
	        array( $IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $foliosTran = $conn->executeSP($tsql, $params);

		return $foliosTran;
	}

	public static function getListaTransacciones( $IDObra, SAODBConn $conn ) {

		return parent::getListaTransacciones($IDObra, self::TIPO_TRANSACCION, $conn);
	}

	public static function getListaSubcontratos( $IDObra, SAODBConn $conn ) {

		if ( ! is_int( (int) $IDObra ) )
			throw new Exception("El identificador de la obra no es correcto.");

		$tsql = '{call [SubcontratosEstimaciones].[uspListaSubcontratos]( ? )}';

		$params = array(
	        array( $IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $lista = $conn->executeSP($tsql, $params);

		return $lista;
	}

	public static function getTiposRetencion( SAODBConn $conn ) {

		$tsql = '{call [SubcontratosEstimaciones].[uspTiposRetencion]}';

	    $lista = $conn->executeSP($tsql);

		return $lista;
	}
}
?>