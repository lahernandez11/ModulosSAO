<?php
class Almacen {
	
	const ALMACEN_X 		  = 0;
	const ALMACEN_MATERIALES  = 1;
	const ALMACEN_MAQUINARIA  = 2;
	const ALMACEN_MANO_OBRA   = 3;
	const ALMACEN_SERVICIOS   = 4;
	const ALMACEN_HERRAMIENTA = 5;

	public static function getAlmacenes( $IDObra, $tipoAlmacen = null, $descripcion = null, SAODBConn $conn ) {

		$tsql = "{call [Almacenes].[uspListaAlmacenes]( ?, ?, ? )}";

	    $params = array(
	        array( $IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $tipoAlmacen, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('255') )
	    );

	    return $conn->executeSP($tsql, $params);
	}
}
?>