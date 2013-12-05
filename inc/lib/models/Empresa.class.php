<?php

class Empresa {
	
	const PROVEEDOR = 1;
	const CONTRATISTA = 2;
	const CONTRATISTA_PROVEEDOR = 3;
	const DESTAJISTA = 4;

	public static function getEmpresas( SAODBConn $conn, $descripcion, $tipos = array() ) {

		$params = array(
			$descripcion
		);

		$sql = "SELECT
				  [id_empresa]
				, [tipo_empresa]
				, [razon_social]
				, [rfc]
				, [dias_credito]
				, [cuenta_contable]
				, [tipo_cliente]
				, [porcentaje]
			FROM
				[dbo].[empresas]
			WHERE
				[razon_social] LIKE '%' + ISNULL(?, [razon_social]) + '%'";

		if (count($tipos) > 0) {
			$sql .= " AND [tipo_empresa] IN(";

			for ($i=0; $i < count($tipos); $i++) {
				$sql .= "?";

				$params[count($params)] = $tipos[$i];

				if ($i < count($tipos) - 1)
					$sql .= ",";
			}
			
			$sql .= ")";
		}
			
		$sql .= " ORDER BY [razon_social]";

		return $conn->executeQuery($sql, $params);
	}
}
?>