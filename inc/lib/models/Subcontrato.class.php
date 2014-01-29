<?php
require_once 'TransaccionSAO.class.php';

class Subcontrato extends TransaccionSAO {

	const TIPO_TRANSACCION = 51;

	private $_id_empresa;
	private $_nombreContratista;
	private $_objetoSubcontrato;
	private $_pctFondoGarantia;
	private $_importeFondoGarantia;
	private $_pctAnticipo;
	private $_importeAnticipo;
	private $_importeAcumuladoEstimado;
	private $_importeAcumuladoAnticipo;
	private $_importeAcumuladoFondoGarantia;
	private $_importeAcumuladoRetenciones;
	private $_importeAcumuladoDeductivas;
	private $_subtotal;
	private $_iva;
	private $_total;

	public function __construct() {
		
		$params = func_get_args();

		switch ( func_num_args() ) {

			case 6:
				call_user_func_array( array( $this, "instaceFromDefault" ), $params );
				break;

			case 2:
				call_user_func_array( array( $this, "instanceFromID" ), $params );
				break;
		}
	}

	private function instaceFromDefault( Obra $obra, $fecha, $fechaInicio, $fechaTermino, 
		$observaciones, Array $conceptos ) {

		parent::__construct( $obra, self::TIPO_TRANSACCION, $fecha, $observaciones );
	}

	private function instanceFromID( Obra $obra, $IDTransaccion ) {
		parent::__construct( $obra, $IDTransaccion );
		
		$this->setDatosGenerales();
	}

	// @override
	protected function setDatosGenerales() {
		
		parent::setDatosGenerales();

		$tsql = "{call [Subcontratos].[uspDatosGenerales]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $datos = $this->conn->executeSP( $tsql, $params );

	    $this->_id_empresa 	 		 = $datos[0]->id_empresa;
	    $this->_objetoSubcontrato 	 = $datos[0]->ObjetoSubcontrato;
	    $this->_nombreContratista 	 = $datos[0]->NombreContratista;
	    $this->_pctFondoGarantia 	 = $datos[0]->PctFondoGarantia;
	    $this->_importeFondoGarantia = $datos[0]->ImporteFondoGarantia;
	    $this->_pctAnticipo 		 = $datos[0]->PctAnticipo;
	    $this->_importeAnticipo 	 = $datos[0]->ImporteAnticipo;
	    $this->_subtotal 	 		 = $datos[0]->Subtotal;
	    $this->_iva 	 			 = $datos[0]->IVA;
	    $this->_total 	 			 = $datos[0]->Total;

	    $this->_importeAcumuladoEstimado 	= $datos[0]->ImporteAcumuladoEstimado;
	    $this->_importeAcumuladoAnticipo 	= $datos[0]->ImporteAcumuladoAnticipo;
	    $this->_importeAcumuladoFondoGarantia = $datos[0]->ImporteAcumuladoFondoGarantia;
	    $this->_importeAcumuladoRetenciones = $datos[0]->ImporteAcumuladoRetenciones;
	    $this->_importeAcumuladoDeductivas 	= $datos[0]->ImporteAcumuladoDeductivas;
	}

	public function getObjetoSubcontrato() {
		return $this->_objetoSubcontrato;
	}

	public function getNombreContratista() {
		return $this->_nombreContratista;
	}

	public function getImporteAnticipo() {
		return $this->_importeAnticipo;
	}

	public function getImporteEstimado() {
		return $this->_importeAcumuladoEstimado;
	}

	public function getImporteAcumuladoAnticipo() {
		return $this->_importeAcumuladoAnticipo;
	}

	public function getImporteFondoGarantia() {
		return $this->_importeFondoGarantia;
	}

	public function getImporteAcumuladoFondoGarantia() {
		return $this->_importeAcumuladoFondoGarantia;
	}

	public function getImporteAcumuladoRetenciones() {
		return $this->_importeAcumuladoRetenciones;
	}

	public function getImporteAcumuladoDeductivas() {
		return $this->_importeAcumuladoDeductivas;
	}

	public function getSubtotal() {
		return $this->_subtotal;
	}

	public function getIVA() {
		return $this->_iva;
	}

	public function getTotal() {
		return $this->_total;
	}

	public static function getFoliosTransaccion( Obra $obra ) {
		return null;
	}
	
	public static function getListaTransacciones( Obra $obra, $tipo_transaccion=null ) {

		return parent::getListaTransacciones( $obra, self::TIPO_TRANSACCION );
	}

	public static function getSubcontratosPorContratista( Obra $obra ) {

		$tsql = "{call [Agrupacion].[uspListaSubcontratos]( ? )}";

		$params = array(
			array( $obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		);

		$datos = $obra->getConn()->executeSP( $tsql, $params );

		return $datos;
	}

	private function existeRegistroAgrupacion( $id_actividad ) {

		$tsql = "SELECT 1
				 FROM [Agrupacion].[agrupacion_subcontratos]
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_empresa] = ?
				 		AND
				 	[id_subcontrato] = ?
				 		AND
				 	[id_actividad] = ?";

	    $params = array(
	        array( $this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->_id_empresa, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_actividad, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $res = $this->conn->executeQuery( $tsql, $params );

	    if (count($res) > 0)
	    	return true;
	    else
	    	return false;
	}

	private function creaRegistroAgrupacion( $id_actividad ) {

		$tsql = "INSERT INTO [Agrupacion].[agrupacion_subcontratos]
				(
					  [id_obra]
					, [id_empresa]
					, [id_subcontrato]
					, [id_actividad]
				)
				VALUES
				( ?, ?, ?, ? )";

	    $params = array(
	        array( $this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->_id_empresa, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_actividad, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery( $tsql, $params );
	}

	public function setAgrupadorPartida( $id_actividad, AgrupadorInsumo $agrupador ) {

		if ( ! $this->existeRegistroAgrupacion( $id_actividad ) )
			$this->creaRegistroAgrupacion( $id_actividad );

		$field = '';

		switch ( $agrupador->getTipoAgrupador() ) {
			case AgrupadorInsumo::TIPO_NATURALEZA:
				$field = AgrupadorInsumo::FIELD_NATURALEZA;
				break;

			case AgrupadorInsumo::TIPO_FAMILIA:
				$field = AgrupadorInsumo::FIELD_FAMILIA;
				break;

			case AgrupadorInsumo::TIPO_GENERICO:
				$field = AgrupadorInsumo::FIELD_GENERICO;
				break;
		}

		$tsql = "UPDATE [Agrupacion].[agrupacion_subcontratos]
				 SET
				 	{$field} = ?
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_empresa] = ?
				 		AND
				 	[id_subcontrato] = ?
				 		AND
				 	[id_actividad] = ?";

	    $params = array(
	    	array( $agrupador->getIDAgrupador(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->_id_empresa, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_actividad, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery( $tsql, $params );
	}

	public static function getListaSubcontratos( Obra $obra ) {

		$tsql = '{call [SubcontratosEstimaciones].[uspListaSubcontratos]( ? )}';

		$params = array(
	        array( $obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $lista = $obra->getConn()->executeSP( $tsql, $params );

		return $lista;
	}
}
?>