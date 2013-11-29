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
}
?>