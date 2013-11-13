<?php
require_once 'db/SAODBConn.class.php';

class AgrupadorConceptoPresupuesto {

	public static function getAgrupadoresPartida(SAODBConn $conn, $id_obra, $descripcion, $id_agrupador = null ) {

		$tsql = "{call [PresupuestoObra].[uspAgrupadorPartida]( ?, ?, ? )}";

		$params = array(
			array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('300') )
		);

		$data = $conn->executeSP($tsql, $params);

		return $data;
	}

	public static function getAgrupadoresSubpartida(SAODBConn $conn, $id_obra, $descripcion, $id_agrupador = null ) {

		$tsql = "{call [PresupuestoObra].[uspAgrupadorSubpartida]( ?, ?, ? )}";

		$params = array(
			array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('300') )
		);

		$data = $conn->executeSP($tsql, $params);

		return $data;
	}

	public static function getAgrupadoresActividad(SAODBConn $conn, $id_obra, $descripcion, $id_agrupador = null ) {

		$tsql = "{call [PresupuestoObra].[uspAgrupadorActividad]( ?, ?, ? )}";

		$params = array(
			array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('300') )
		);

		$data = $conn->executeSP($tsql, $params);

		return $data;
	}

	public static function getAgrupadoresTramo(SAODBConn $conn, $id_obra, $descripcion, $id_agrupador = null ) {

		$tsql = "{call [PresupuestoObra].[uspAgrupadorTramo]( ?, ?, ? )}";

		$params = array(
			array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('300') )
		);

		$data = $conn->executeSP($tsql, $params);

		return $data;
	}

	public static function getAgrupadoresSubtramo(SAODBConn $conn, $id_obra, $descripcion, $id_agrupador = null ) {

		$tsql = "{call [PresupuestoObra].[uspAgrupadorSubtramo]( ?, ?, ? )}";

		$params = array(
			array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('300') )
		);

		$data = $conn->executeSP($tsql, $params);

		return $data;
	}

	public static function addAgrupadorPartida(SAODBConn $conn, $id_obra, $clave, $descripcion) {

		$tsql = "{call [PresupuestoObra].[uspRegistraAgrupadorPartida](?, ?, ?, ?)}";

		$id_agrupador = null;

	    $params = array(
	        array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $clave, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(140) ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(140) ),
	        array( $id_agrupador, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT ),
	    );

	    $conn->executeSP($tsql, $params);

	    return $id_agrupador;
	}

	public static function addAgrupadorSubpartida(SAODBConn $conn, $id_obra, $clave, $descripcion) {

		$tsql = "{call [PresupuestoObra].[uspRegistraAgrupadorSubpartida](?, ?, ?, ?)}";

		$id_agrupador = null;

	    $params = array(
	        array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $clave, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(140) ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(140) ),
	        array( $id_agrupador, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT ),
	    );

	    $conn->executeSP($tsql, $params);

	    return $id_agrupador;
	}

	public static function addAgrupadorActividad(SAODBConn $conn, $id_obra, $clave, $descripcion) {

		$tsql = "{call [PresupuestoObra].[uspRegistraAgrupadorActividad](?, ?, ?, ?)}";

		$id_agrupador = null;

	    $params = array(
	        array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $clave, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(140) ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(140) ),
	        array( $id_agrupador, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT ),
	    );

	    $conn->executeSP($tsql, $params);

	    return $id_agrupador;
	}

	public static function addAgrupadorTramo(SAODBConn $conn, $id_obra, $clave, $descripcion) {

		$tsql = "{call [PresupuestoObra].[uspRegistraAgrupadorTramo](?, ?, ?, ?)}";

		$id_agrupador = null;

	    $params = array(
	        array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $clave, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(140) ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(140) ),
	        array( $id_agrupador, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT ),
	    );

	    $conn->executeSP($tsql, $params);

	    return $id_agrupador;
	}

	public static function addAgrupadorSubtramo(SAODBConn $conn, $id_obra, $clave, $descripcion) {

		$tsql = "{call [PresupuestoObra].[uspRegistraAgrupadorSubtramo](?, ?, ?, ?)}";

		$id_agrupador = null;

	    $params = array(
	        array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $clave, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(140) ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(140) ),
	        array( $id_agrupador, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT ),
	    );

	    $conn->executeSP($tsql, $params);

	    return $id_agrupador;
	}
}