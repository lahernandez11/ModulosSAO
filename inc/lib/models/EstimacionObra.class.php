<?php
include_once 'models/TransaccionSAO.class.php';

class EstimacionObra extends TransaccionSAO {

	const TIPO_TRANSACCION = 103;

	private $_referencia;
	private $_fechaInicio;
	private $_fechaTermino;
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

	private function instaceFromDefault( $IDObra, $fecha, $fechaInicio, 
		$fechaTermino, $observaciones, $referencia, Array $conceptos, SAODBConn $conn ) {

		parent::__construct($IDObra, self::TIPO_TRANSACCION, $fecha, $observaciones, $conn);

		$this->_referencia = $referencia;
		$this->setFechaInicio( $fechaInicio );
		$this->setFechaTermino( $fechaTermino );
		$this->setConceptos( $conceptos );
	}

	private function instanceFromID( $IDTransaccion, SAODBConn $conn ) {
		parent::__construct( $IDTransaccion, $conn );
		
		$this->setDatosGenerales();
	}

	public function getReferencia() {
		return $this->_referencia;
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
		else
			$this->_fechaInicio = $fecha;
	}

	public function getFechaTermino() {
		return $this->_fechaTermino;
	}

	public function setFechaTermino( $fecha ) {
		
		if ( ! $this->fechaEsValida( $fecha ) )
			throw new Exception("El formato de fecha término es incorrecto.");
		else
			$this->_fechaTermino = $fecha;
	}

	// @override
	protected function setDatosGenerales() {
		
		parent::setDatosGenerales();

		$tsql = "{call [EstimacionObra].[uspDatosGenerales]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $datos = $this->_SAOConn->executeSP($tsql, $params);

	    $this->_referencia = $datos[0]->Referencia;
	    $this->setFechaInicio($datos[0]->FechaInicio);
	    $this->setFechaTermino($datos[0]->FechaTermino);
	}

	public function getConceptos() {

		$tsql = "{call [EstimacionObra].[uspConceptosEstimacion]( ?, ? )}";

	    $params = array(
	        array( $this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $conceptos = $this->_SAOConn->executeSP($tsql, $params);

	    return $conceptos;
	}

	public function getTotalesTransaccion() {

		$tsql = "{call [EstimacionObra].[uspTotalesTransaccion]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $totales = $this->_SAOConn->executeSP($tsql, $params);

	    return $totales;
	}

	public static function getConceptosNuevaEstimacion( $IDObra, $IDConceptoRaiz, SAODBConn $conn ) {

		$tsql = "{call [EstimacionObra].[uspConceptosEstimacion]( ? )}";

	    $params = array(
	        array( $IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $conceptos = $conn->executeSP($tsql, $params);

	    return $conceptos;
	}

	public static function getFoliosTransaccion( $IDObra, SAODBConn $conn) {

		if ( ! is_int($IDObra) )
			throw new Exception("El identificador de la obra no es correcto.");
		else {
			$tsql = '{call [EstimacionObra].[uspListaFolios]( ? )}';

			$params = array(
		        array( $IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		    );

		    $foliosTran = $conn->executeSP($tsql, $params);

			return $foliosTran;
		}
	}

	public static function getListaTransacciones( $IDObra, SAODBConn $conn ) {

		return parent::getListaTransacciones($IDObra, self::TIPO_TRANSACCION, $conn);
	}

	public function __toString() {

		$data  = parent::__toString() . ', ';
		$data .= "FechaInicio: {$this->_fechaInicio}, ";
		$data .= "FechaTermino: {$this->_fechaTermino}, ";
		$data .= "Referencia: {$this->_referencia}, ";

		return $data;
	}
}
?>