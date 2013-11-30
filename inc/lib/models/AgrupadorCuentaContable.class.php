<?php
require_once 'db/ReportesSAOConn.class.php';

class AgrupadorCuentaContable {
	
	public static function getAgrupadoresProveedor( ReportesSAOConn $conn, $IDProyecto, $descripcion, $IDProveedor = null) {
		
		$tsql = "SELECT
					[Proveedores].[IdProveedor] as [id],
					[Proveedores].[Nombre] AS [agrupador]
				FROM
					[Contabilidad].[Proveedores]
				WHERE
					[Proveedores].[IdProyecto] = ?
						AND
					[Proveedores].[IdProveedor] = ISNULL(?, [Proveedores].[IdProveedor])
						AND
				    [Proveedores].[Nombre] LIKE '%' + ? +'%'
				ORDER BY
					[Proveedores].[Nombre]";

		$params = array(
			array( $IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $IDProveedor, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') )
		);

		$data = $conn->executeSP($tsql, $params);

		return $data;
	}

	public static function getAgrupadoresTipoCuenta(ReportesSAOConn $conn, $IDProyecto, $descripcion, $IDAgrupadorTipoCuenta = null) {

		$tsql = "SELECT
					  [IDAgrupadorTipoCuenta] as [id]
					, [AgrupadorTipoCuenta] as [agrupador]
				FROM
					[Contabilidad].[AgrupadorTipoCuenta]
				WHERE
					[IDProyecto] = ?
						AND
					[IDAgrupadorTipoCuenta] = ISNULL(?, [IDAgrupadorTipoCuenta])
						AND
					[AgrupadorTipoCuenta] LIKE '%' + ? +'%'
				ORDER BY
					[AgrupadorTipoCuenta]";

		$params = array(
			array( $IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $IDAgrupadorTipoCuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') )
		);

		$data = $conn->executeSP($tsql, $params);

		return $data;
	}

	public static function addAgrupadorTipoCuenta(ReportesSAOConn $conn, $IDProyecto, $descripcion) {

		$tsql = "{call [Contabilidad].[uspRegistraAgrupadorTipoCuenta](?, ?, ?)}";

		$id_cuenta = null;

	    $params = array(
	        array( $IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(140) ),
	        array( $id_cuenta, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT )
	    );

	    $conn->executeSP($tsql, $params);

	    return $id_cuenta;
	}
}
?>