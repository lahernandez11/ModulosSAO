<?php
abstract class TransaccionSAO {
	const TIPO_TRANSACCION = 0;

	protected $obra;
	protected $id_transaccion;
	protected $tipo_transaccion;
	protected $conn = null;
	protected $_estado = 0;
	protected $_numeroFolio = 0;
	protected $_fecha = null;
	protected $_observaciones = "";
	private $_nombreObra;

	public function __construct() {

		$params = func_get_args();

		switch ( func_num_args() ) {
			
			case 2:
				$this->instanceFromID( $params[0], $params[1] );
				//call_user_func_array(array($this, "instanceFromID"), $params);
				break;

			case 4:
				call_user_func_array( array( $this, "init" ), $params );
				break;
		}
	}

	private function init( Obra $obra, $tipoTransaccion, $fecha, $observaciones ) {
		
		$this->obra = $obra;
		$this->tipo_transaccion = $tipoTransaccion;
		$this->setFecha( $fecha );
		$this->setObservaciones( $observaciones );
		$this->conn = $obra->getConn();
	}
	
	private function instanceFromID( Obra $obra, $id_transaccion ) {
		
		if ( ! is_int( $id_transaccion ) ) {
			throw new Exception( "No es un identificador de transacción válido." );
		}

		$this->obra = $obra;
		$this->conn = $obra->getConn();
		$this->setIDTransaccion( $id_transaccion );
	}

	protected function setDatosGenerales() {

		$tsql = "SELECT
					  [transacciones].[id_transaccion]
					, [obras].[nombre] AS [NombreObra]
					, [transacciones].[tipo_transaccion]
					, [transacciones].[estado]
					, [transacciones].[numero_folio]
					, CAST([transacciones].[fecha] AS DATE) AS [fecha]
					, [transacciones].[observaciones]
				FROM
					[dbo].[transacciones]
				INNER JOIN
					[dbo].[obras]
					ON
						[transacciones].[id_obra] = [obras].[id_obra]
				WHERE
					[transacciones].[id_obra] = ?
						AND
					[transacciones].[id_transaccion] = ?";
		
	    $params = array(
	        array( $this->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id_transaccion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $rsDatosTran = $this->conn->executeQuery( $tsql, $params );

	    if ( count( $rsDatosTran ) > 0 ) {

			foreach ( $rsDatosTran as $datosTran ) {

				$this->_nombreObra	    = $datosTran->NombreObra;
				$this->tipo_transaccion = $datosTran->tipo_transaccion;
				$this->_estado 		    = $datosTran->estado;
				$this->_numeroFolio     = $datosTran->numero_folio;
				$this->setFecha( $datosTran->fecha );
				$this->_observaciones   = $datosTran->observaciones;
			}
		} else
			throw new Exception("No se encontro la transacción.");
	}

	protected function setIDTransaccion( $id_transaccion ) {
		$this->id_transaccion = $id_transaccion;
	}

	public function getIDTransaccion() {
		return $this->id_transaccion;
	}

	public function getIDObra() {
		return $this->obra->getId();
	}

	public function getNombreObra() {
		return $this->_nombreObra;
	}

	public function getTipoTransaccion() {
		return $this->tipo_transaccion;
	}

	public function getFecha() {
		return $this->_fecha;
	}

	public function setFecha( $fecha ) {
		
		// if ( ! $this->fechaEsValida( $fecha ) )
		// 	throw new Exception("El formato de fecha es incorrecto.");
		// else
			$this->_fecha = $fecha;
	}

	protected function fechaEsValida( $fecha ) {
		
		if ( preg_match( "/^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/", $fecha ) === 1) {
			return true;
		}
		else {
			return false;
		}
	}

	protected function esImporte( $importe ) {

		return preg_match('/^-?\d+(\.\d+)?$/', $importe );
	}

	public function getNumeroFolio() {
		return $this->_numeroFolio;
	}

	protected function setNumeroFolio( $numeroFolio ) {
		$this->_numeroFolio = $numeroFolio;
	}

	public function getObservaciones() {
		return $this->_observaciones;
	}

	public function setObservaciones( $Observaciones) {
		return $this->_observaciones = $Observaciones;
	}

	public function eliminaTransaccion() {

		$tsql = "{call [dbo].[sp_borra_transaccion]( ? )}";

	    $params = array(
	        array( $this->id_transaccion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeSP( $tsql, $params );
	}

	// public static abstract function getFoliosTransaccion( $IDObra, SAODBConn $conn );
	
	public static function getListaTransacciones( Obra $obra, $tipo_transaccion=null ) {

		if ( is_null( $tipo_transaccion ) ) {
			$tipo_transaccion = self::TIPO_TRANSACCION;
		}

		$tsql = '{call [SAO].[uspListaTransacciones]( ?, ? )}';

		$params = array(
	        array( $obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $tipo_transaccion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $listaTran = $obra->getConn()->executeSP( $tsql, $params );

		return $listaTran;
	}

	public function __toString() {

		$data =  "IDTransaccion: {$this->id_transaccion}, ";
		$data .= "TipoTransaccion: {$this->tipo_transaccion}, ";
		$data .= "IDObra: {$this->obra->getId()}, ";
		$data .= "Estado: {$this->_estado}, ";
		$data .= "Fecha: {$this->_fecha}, ";
		$data .= "NumeroFolio: {$this->_numeroFolio}, ";
		$data .= "Observaciones: {$this->_observaciones}";

		return $data;
	}

/*
	public abstract function getTotales();

	public abstract function guardaTransaccion();

	public abstract function eliminaTransaccion();
*/
}
?>