<?php
class Insumo {
	
	const TIPO_MATERIAL = 1;
	const TIPO_MANO_OBRA = 2;
	const TIPO_SERVICIOS = 3;
	const TIPO_HERRAMIENTA = 4;
	const TIPO_MAQUINARIA = 8;

	public function __construct() {

	}

	public static function getInsumos( $descripcion = null, SAODBConn $conn ) {

		$tsql = "{call [Insumos].[uspListaInsumos]( ?, ? )}";

	    $params = array(
	        array( null, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('255') )
	    );

	    return $conn->executeSP($tsql, $params);
	}

	public static function getInsumosMateriales( $descripcion = null, SAODBConn $conn ) {

		$tsql = "{call [Insumos].[uspListaInsumos]( ?, ? )}";

	    $params = array(
	        array( self::TIPO_MATERIAL, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('255') )
	    );

	    return $conn->executeSP($tsql, $params);
	}

	public static function getInsumosManoObra( $descripcion = null, SAODBConn $conn ) {

		$tsql = "{call [Insumos].[uspListaInsumos]( ?, ? )}";

	    $params = array(
	        array( self::TIPO_MANO_OBRA, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('255') )
	    );

	    return $conn->executeSP($tsql, $params);
	}

	public static function getInsumosMaquinaria( $descripcion = null, SAODBConn $conn ) {

		$tsql = "{call [Insumos].[uspListaInsumos]( ?, ? )}";

	    $params = array(
	        array( self::TIPO_MAQUINARIA, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('255') )
	    );

	    return $conn->executeSP($tsql, $params);
	}

	public static function getInsumosServicios( $descripcion = null, SAODBConn $conn ) {

		$tsql = "{call [Insumos].[uspListaInsumos]( ?, ? )}";

	    $params = array(
	        array( self::TIPO_SERVICIOS, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('255') )
	    );

	    return $conn->executeSP($tsql, $params);
	}
}
?>