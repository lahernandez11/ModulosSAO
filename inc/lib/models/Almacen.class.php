<?php
require_once 'models/Obra.class.php';

class Almacen {
	
	const ALMACEN_X 		  = 0;
	const ALMACEN_MATERIALES  = 1;
	const ALMACEN_MAQUINARIA  = 2;
	const ALMACEN_MANO_OBRA   = 3;
	const ALMACEN_SERVICIOS   = 4;
	const ALMACEN_HERRAMIENTA = 5;

	public static function getAlmacenes( Obra $obra, $tipo_almacen = null, $descripcion = null ) {

		$tsql = "{call [Almacenes].[uspListaAlmacenes]( ?, ?, ? )}";

	    $params = array(
	        array( $obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $tipo_almacen, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('255') )
	    );

	    return $obra->getConn()->executeSP($tsql, $params);
	}
}
?>