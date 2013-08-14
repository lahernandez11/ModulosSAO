<?php
require_once 'models/TransaccionSAO.class.php';

class AvanceSubcontrato extends TransaccionSAO {

	const TIPO_TRANSACCION = 98;

	private $_IDConceptoRaiz = 0;
	private $_conceptoRaiz = null;
	private $_fechaInicio = null;
	private $_fechaTermino = null;
	private $_conceptos;

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

	private function instaceFromDefault( $IDObra, $fecha, $fechaInicio, $fechaTermino, $observaciones, $IDConceptoRaiz, Array $conceptos, SAODBConn $conn ) {
		parent::__construct($IDObra, self::TIPO_TRANSACCION, $fecha, $observaciones, $conn);

		$this->setIDConceptoRaiz( $IDConceptoRaiz );
		$this->setFechaInicio($fechaInicio);
		$this->setFechaTermino($fechaTermino);
		$this->setConceptos( $conceptos );
	}

	private function instanceFromID( $IDTransaccion, SAODBConn $conn ) {
		parent::__construct( $IDTransaccion, $conn );
		
		$this->setDatosGenerales();
	}

	// @override
	protected function setDatosGenerales() {
		
		parent::setDatosGenerales();

		$tsql = "{call [AvanceObra].[uspDatosGenerales]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $datos = $this->_SAOConn->executeSP($tsql, $params);

	    $this->setIDConceptoRaiz( $datos[0]->IDConceptoRaiz );
	    $this->_conceptoRaiz   = $datos[0]->ConceptoRaiz;
	    $this->_fechaInicio = $datos[0]->FechaInicio;
	    $this->_fechaTermino = $datos[0]->FechaTermino;
	}

	private function guardaConceptosAvance() {

		$conceptosError = array();

		$tsql = "{call [AvanceObra].[uspGuardaAvanceConcepto]( ?, ?, ?, ? )}";

		foreach ( $this->_conceptos as $concepto ) {
			
			try {
				// Lipia y valida la cantidad estimada
				$concepto['cantidad'] = str_replace(',', '', $concepto['cantidad']);

				$isValid = preg_match('/^-?\d+(\.\d+)?$/', $concepto['cantidad']);

				// Si la cantidad no es valida agrega el concepto con error
				if( ! $isValid ) {
					throw new Exception("La cantidad ingresada no es correcta");
				}

				$params = array(
					array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
					array( $concepto['IDConcepto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
					array( $concepto['cantidad'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT ),
					array( $concepto['cumplido'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT )
				);

			
				$this->_SAOConn->executeSP($tsql, $params);
			} catch( Exception $e ) {

				$conceptosError[] = array(
					'IDConcepto' => $concepto['IDConcepto'],
					'cantidad' => $concepto['cantidad'],
					'message' => $e->getMessage()
				);
			}
		}

		return $conceptosError;
	}

	private function guardaDatosGenerales() {

		$tsql = "{call [AvanceObra].[uspGuardaDatosGenerales]( ?, ?, ?, ?, ?, ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
	        array( $this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
	        array( $this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
	        array( $this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096) ),
	        array( Sesion::getCuentaUsuarioSesion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16) ),
	    );

	    $rsConceptos = $this->_SAOConn->executeSP($tsql, $params);
	}

	private function getIDConceptoRaiz() {
		return $this->_IDConceptoRaiz;
	}

	private function setIDConceptoRaiz( $IDConceptoRaiz ) {
		$this->_IDConceptoRaiz = $IDConceptoRaiz;
	}

	public function registraTransaccion() {

		$tsql = "{call [AvanceObra].[uspRegistraTransaccion]( ?, ?, ?, ?, ?, ?, ?, ?, ? )}";

	    $params = array(
	        array( $this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
	       	array( $this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
	        array( $this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
	        array( $this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096) ),
	        array( $this->getIDConceptoRaiz(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( Sesion::getCuentaUsuarioSesion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16) ),
	        array( &$this->_IDTransaccion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT ),
	        array( &$this->_numeroFolio, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->_SAOConn->executeSP($tsql, $params);
	    $feedback = $this->guardaConceptosAvance();

	    return $feedback;
	}

	public function guardaTransaccion() {

		$this->guardaDatosGenerales();

		$this->guardaConceptosAvance();
	}

	public function apruebaTransaccion() {

		$tsql = "{call [AvanceObra].[uspApruebaTransaccion]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->_SAOConn->executeSP($tsql, $params);
	}

	public function revierteAprobacion() {

		$tsql = "{call [AvanceObra].[uspRevierteAprobacion]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->_SAOConn->executeSP($tsql, $params);
	}

	public function setConceptos( Array $conceptos ) {

		$this->_conceptos = $conceptos;
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
			throw new Exception("El formato de fecha termino es incorrecto.");
		
		$this->_fechaTermino = $fecha;
	}

	public function getConceptoRaiz() {
		
		return $this->_conceptoRaiz;
	}

	public function getConceptosAvance() {

		$tsql = "{call [AvanceObra].[uspConceptosAvance]( ?, ?, ? )}";

	    $params = array(
	        array( $this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDConceptoRaiz(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $conceptos = $this->_SAOConn->executeSP($tsql, $params);

	    return $conceptos;
	}

	public function getTotalesTransaccion() {

		$tsql = "{call [AvanceObra].[uspTotalesTransaccion]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $totales = $this->_SAOConn->executeSP($tsql, $params);

	    return $totales;
	}

	public static function getConceptosNuevoAvance( $IDObra, $IDConceptoRaiz, SAODBConn $conn ) {

		$tsql = "{call [AvanceObra].[uspConceptosAvance]( ?, ? )}";

	    $params = array(
	        array( $IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $IDConceptoRaiz, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $conceptos = $conn->executeSP($tsql, $params);

	    return $conceptos;
	}

	public static function getFoliosTransaccion( $IDObra, SAODBConn $conn) {

		if ( ! is_int($IDObra) )
			throw new Exception("El identificador de la obra no es correcto.");

		$tsql = '{call [AvanceObra].[uspListaFolios]( ? )}';

		$params = array(
	        array( $IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $foliosTran = $conn->executeSP($tsql, $params);

		return $foliosTran;
	}

	public static function getListaTransacciones( $IDObra, SAODBConn $conn ) {

		return parent::getListaTransacciones($IDObra, self::TIPO_TRANSACCION, $conn);
	}
}
?>