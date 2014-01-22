<?php
require_once 'db/SAODBConn.class.php';

class AgrupacionGastosVarios {

	public static function getGastosVarios( Obra $obra ) {

		$tsql = "SELECT
					  [transacciones].[id_obra]
					, [transacciones].[id_empresa]
					, [empresas].[razon_social] AS [proveedor]
					, [transacciones].[id_transaccion] AS [id_factura]
					, 'Factura #'
					  + CASE LEN([transacciones].[numero_folio])
						  WHEN 4 THEN ''
						  WHEN 3 THEN '0'
						  WHEN 2 THEN '00'
						  WHEN 1 THEN '000'
						END
					  + CAST([transacciones].[numero_folio] AS VARCHAR(50))
					  + ' [' + CONVERT( VARCHAR(10), [transacciones].[fecha], 105)
					  + ']' AS [referencia_factura]
					, [items].[id_item]
					, ISNULL([items].[referencia], [materiales].[descripcion]) AS [referencia]
					, [agrupacion_gastos_varios].[id_agrupador_naturaleza]
					, CONCAT([Naturaleza].[codigo] + ' ', [Naturaleza].[agrupador]) AS [agrupador_naturaleza]
					, [agrupacion_gastos_varios].[id_agrupador_familia]
					, CONCAT([Familia].[codigo] + ' ', [Familia].[agrupador]) AS [agrupador_familia]
					, [agrupacion_gastos_varios].[id_agrupador_insumo_generico]
					, CONCAT([Generico].[codigo] + ' ', [Generico].[agrupador]) AS [agrupador_insumo_generico]
				FROM
					[dbo].[transacciones]
				INNER JOIN
					[dbo].[items]
					ON
						[transacciones].[id_transaccion] = [items].[id_transaccion]
				INNER JOIN
					[dbo].[empresas]
					ON
						[transacciones].[id_empresa] = [empresas].[id_empresa]
				LEFT OUTER JOIN
					[dbo].[materiales]
					ON
						[items].[id_material] = [materiales].[id_material]
				LEFT OUTER JOIN
					[Agrupacion].[agrupacion_gastos_varios]
					ON
						[transacciones].[id_transaccion] = [agrupacion_gastos_varios].[id_factura]
							AND
						[items].[id_item] = [agrupacion_gastos_varios].[id_item]
				LEFT OUTER JOIN
					[Agrupacion].[Agrupadores] AS [Naturaleza]
					ON
						[agrupacion_gastos_varios].[id_agrupador_naturaleza] = [Naturaleza].[id_agrupador]
				LEFT OUTER JOIN
					[Agrupacion].[Agrupadores] AS [Familia]
					ON
						[agrupacion_gastos_varios].[id_agrupador_familia] = [Familia].[id_agrupador]
				LEFT OUTER JOIN
					[Agrupacion].[Agrupadores] AS [Generico]
					ON
						[agrupacion_gastos_varios].[id_agrupador_insumo_generico] = [Generico].[id_agrupador]
				WHERE
					[transacciones].[id_obra] = ?
						AND
					[transacciones].[tipo_transaccion] = 65
						AND
					[items].[id_material] IS NULL
						AND
			        [transacciones].[opciones] = 1
				ORDER BY
					  [empresas].[razon_social]
					, [transacciones].[id_transaccion]";

		$params = array( array( $obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT) );
		
		$data = $obra->getConn()->executeSP( $tsql, $params );

		return $data;
	}

	private static function existeRegistroAgrupacion( $obra, $id_factura, $id_item ) {

		$tsql = "SELECT 1
				 FROM
				 	[Agrupacion].[agrupacion_gastos_varios]
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_factura] = ?
				 		AND
				 	[id_item] = ?";

	    $params = array(
	        array( $obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_factura, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_item, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $res = $obra->getConn()->executeQuery( $tsql, $params );

	    if (count($res) > 0)
	    	return true;
	    else
	    	return false;
	}

	private static function creaRegistroAgrupacion( $obra, $id_factura, $id_item ) {

		$tsql = "INSERT INTO [Agrupacion].[agrupacion_gastos_varios]
				(
					  [id_obra]
					, [id_factura]
					, [id_item]
				)
				VALUES ( ?, ?, ? )";

	    $params = array(
	        array( $obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_factura, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_item, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $obra->getConn()->executeQuery($tsql, $params);
	}

	public static function setAgrupador( Obra $obra, $id_factura, 
		$id_item, AgrupadorInsumo $agrupador ) {

		if ( !self::existeRegistroAgrupacion( $obra, $id_factura, $id_item ) )
			self::creaRegistroAgrupacion( $obra, $id_factura, $id_item);

		$field = '';

		switch ($agrupador->getTipoAgrupador()) {
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

		$tsql = "UPDATE [Agrupacion].[agrupacion_gastos_varios]
				 SET
				 	{$field} = ?
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_factura] = ?
				 		AND
				 	[id_item] = ?";

	    $params = array(
			  array( $agrupador->getIDAgrupador(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
			, array( $obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
			, array( $id_factura, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
			, array( $id_item, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
		);

	    $obra->getConn()->executeQuery($tsql, $params);
	}
}