<?php
class PresupuestoObra {
	
	private $_obra = null;

	public function __construct( Obra $obra ) {

		$this->obra = $obra;
	}

	public function getConceptos() {

	}

	public static function getDescendantsOf( $IDObra, $IDConceptoRaiz, SAODBConn $conn ) {

		$tsql = "{call [SAO].[uspPresupuestoObra]( ?, ? )}";

		$params = array(
			array( $IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $IDConceptoRaiz, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		);

		$nodes = $conn->executeSP($tsql, $params);

		return $nodes;
	}
}
?>