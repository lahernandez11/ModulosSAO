<?php

require_once 'models/Obra.class.php';

class PrecioVenta {

    /**
     * @param Obra $obra
     * @return array
     * @throws DBServerStatementExecutionException
     */
    public static function getPreciosVenta(Obra $obra)
    {
        $tsql = "SELECT
                      [conceptos].[id_concepto]
                    , (LEN([conceptos].[nivel]) / 4) AS [numero_nivel]
                    , [conceptos].[descripcion]
                    , IIF( [conceptos].[concepto_medible] > 0, 1, 0) AS [es_actividad]
                    , IIF( [precios_venta].[id_concepto] IS NULL, 0, 1) AS [con_precio]
                    , ISNULL([conceptos].[unidad], '') AS [unidad]
                    , ISNULL([precios_venta].[precio_produccion], 0) AS [precio_produccion]
                    , ISNULL([precios_venta].[precio_estimacion], 0) AS [precio_estimacion]
                    , [precios_venta].[updated_at]
                FROM
                    [dbo].[conceptos]
                LEFT OUTER JOIN
                    [PresupuestoObra].[precios_venta]
                ON
                    [conceptos].[id_concepto] = [precios_venta].[id_concepto]
                WHERE
                    EXISTS
                    (
                        SELECT 1
                        FROM
                            [dbo].[conceptos] AS [conceptos_1]
                        WHERE
                            [conceptos_1].[nivel] LIKE ([conceptos].[nivel] + '%')
                                AND
                            [conceptos_1].[id_obra] = [conceptos].[id_obra]
                                AND
                            [conceptos_1].[concepto_medible] > 0
                    )
                        AND
                    [conceptos].[id_obra] = ?
                ORDER BY
                    [conceptos].[nivel];";

		$params = array(
	        array($obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );
	    $preciosConceptos = $obra->getConn()->executeQuery($tsql, $params);

	    return $preciosConceptos;
	}

    /**
     * @param Obra $obra
     * @param array $conceptos
     * @return array
     */
    public static function setPreciosVenta(Obra $obra, Array $conceptos)
    {
		$conceptosError = array();

		$tsql = "{call [PresupuestoObra].[uspAsignaPrecioVenta](?, ?, ? )}";

		foreach ($conceptos as $concepto)
        {
			try
            {
				// Limpia los precios
				$concepto['precio_produccion'] = str_replace(',', '', $concepto['precio_produccion']);
				$concepto['precio_estimacion'] = str_replace(',', '', $concepto['precio_estimacion']);

				$params = array(
					array($concepto['id_concepto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($concepto['precio_produccion'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19,6)),
					array($concepto['precio_estimacion'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19,6)),
				);

				$obra->getConn()->executeSP($tsql, $params);
			}
            catch (Exception $e)
            {
				$conceptosError[] = array(
					'id_concepto' => $concepto['id_concepto'],
					'message' => $e->getMessage()
				);
			}
		}

		return $conceptosError;
	}
}
