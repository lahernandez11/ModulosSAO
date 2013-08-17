<?php
abstract class TransaccionSAO {

	protected $_IDTransaccion = 0;
	protected $_IDObra = 0;
	protected $_estado = 0;
	protected $_numeroFolio = 0;
	protected $_fecha = null;
	protected $_observaciones = "";
	protected $_SAOConn = null;
	private $_nombreObra;

	public function __construct() {

		$params = func_get_args();

		switch ( func_num_args() ) {
			
			case 2:
				$this->instanceFromID($params[0], $params[1]);
				//call_user_func_array(array($this, "instanceFromID"), $params);
				break;

			case 5:
				call_user_func_array(array($this, "init"), $params);
				break;
		}
	}

	private function init( $IDObra, $tipoTransaccion, $fecha, $observaciones, SAODBConn $conn ) {

		$this->setIDObra( $IDObra );
		$this->_tipoTransaccion = $tipoTransaccion;
		$this->setFecha( $fecha );
		$this->setObservaciones( $observaciones );
		$this->_SAOConn = $conn;
	}
	
	private function instanceFromID( $IDTransaccion, SAODBConn $conn ) {
		
		if ( ! is_int($IDTransaccion) ) {
			throw new Exception("No es un identificador de transacción válido.");
		}

		$this->_SAOConn = $conn;
		$this->setIDTransaccion( $IDTransaccion );
	}

	protected function setDatosGenerales() {

		$tsql = "SELECT
					  [transacciones].[id_transaccion]
					, [transacciones].[id_obra]
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
					[transacciones].[id_transaccion] = ?";
		
	    $params = array(
	        array( $this->_IDTransaccion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $rsDatosTran = $this->_SAOConn->executeSP($tsql, $params);

	    if ( count($rsDatosTran) > 0 ) {

			foreach ($rsDatosTran as $datosTran) {

				$this->_IDObra 		    = $datosTran->id_obra;
				$this->_nombreObra	    = $datosTran->NombreObra;
				$this->_tipoTransaccion = $datosTran->tipo_transaccion;
				$this->_estado 		    = $datosTran->estado;
				$this->_numeroFolio     = $datosTran->numero_folio;
				$this->setFecha($datosTran->fecha);
				$this->_observaciones   = $datosTran->observaciones;
			}
		} else
			throw new Exception("No se encontro la transacción.");
	}

	protected function setIDTransaccion( $IDTransaccion ) {
		$this->_IDTransaccion = $IDTransaccion;
	}

	public function getIDTransaccion() {
		return $this->_IDTransaccion;
	}

	public function getIDObra() {
		return $this->_IDObra;
	}

	protected function setIDObra( $IDObra ) {
		$this->_IDObra = $IDObra;
	}

	public function getNombreObra() {
		return $this->_nombreObra;
	}

	public function getTipoTransaccion() {
		return $this->_tipoTransaccion;
	}

	public function getFecha() {
		return $this->_fecha;
	}

	public function setFecha( $fecha ) {
		
		if ( ! $this->fechaEsValida($fecha) )
			throw new Exception("El formato de fecha es incorrecto.");
		else
			$this->_fecha = $fecha;
	}

	protected function fechaEsValida( $fecha ) {

		if ( is_string($fecha)
			 && ! preg_match("/^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/", $Fecha) )
			return true;
		else
			return false;
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
	        array( $this->_IDTransaccion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->_SAOConn->executeSP($tsql, $params);
	}

	public static abstract function getFoliosTransaccion( $IDObra, SAODBConn $conn );
	
	public static function getListaTransacciones( $IDObra, $tipoTransaccion, SAODBConn $conn ) {

		if ( ! is_int( (int) $IDObra ) )
			throw new Exception("El identificador de la obra no es correcto.");

		$tsql = '{call [SAO].[uspListaTransacciones]( ?, ? )}';

		$params = array(
	        array( $IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $tipoTransaccion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $listaTran = $conn->executeSP($tsql, $params);

		return $listaTran;
	}

	public function __toString() {

		$data =  "IDTransaccion: {$this->_IDTransaccion}, ";
		$data .= "TipoTransaccion: {$this->_tipoTransaccion}, ";
		$data .= "IDObra: {$this->_IDObra}, ";
		$data .= "Estado: {$this->_estado}, ";
		$data .= "Fecha: {$this->_fecha}, ";
		$data .= "NumeroFolio: {$this->_numeroFolio}, ";
		$data .= "Observaciones: {$this->_observaciones}";

		return $data;
	}

	// public static function getInstance( $IDTransaccion, SAODBConn $conn) {

	// 	$tsql = "SELECT
	// 				  [transacciones].[tipo_transaccion]
	// 			FROM
	// 				[dbo].[transacciones]
	// 			WHERE
	// 				[transacciones].[id_transaccion] = ?";
		
	//     $params = array(
	//         array( $IDTransaccion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	//     );

	//     $rsDatosTran = $this->_SAOConn->executeSP($tsql, $params);	    
	// }
/*
	public abstract function getTotales();

	public abstract function guardaTransaccion();

	public abstract function eliminaTransaccion();
*/
}
?>