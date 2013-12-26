<?php
require_once 'db/SAODBConn.class.php';

class CuentaContable {

	private $conn = null;
	private $id_obra = null;
	private $id_cuenta = null;

	public function __construct( SAODBConn $conn, $id_obra, $id_cuenta ) {
		
		if ( ! is_int($id_obra) || ! $id_obra > 0 ) {
			throw new Exception("El identificador de obra no es correcto.");
		} else {
			$this->conn = $conn;
			$this->id_obra = $id_obra;
			$this->id_cuenta = $id_cuenta;
		}
	}

	public static function getCuentas( SAODBConn $conn, $id_obra, $id_cuenta ) {

		$tsql = "WITH CTE_CUENTAS([id_obra], [id_cuenta], [id_cuenta_superior], [afectable], [codigo], [nombre], [nivel])
				AS
				(  
					SELECT
						[cuenta_contable].[id_obra],
						[cuenta_contable].[id_cuenta],
						[cuenta_contable_asociacion].[id_cuenta_superior],
						[cuenta_contable].[afectable],
						[cuenta_contable].[codigo],
						[cuenta_contable].[nombre],
						1 AS [nivel]
					FROM
						[Contabilidad].[cuenta_contable]
					LEFT OUTER JOIN
						[Contabilidad].[cuenta_contable_asociacion]
						ON
							[cuenta_contable].[id_obra] = [cuenta_contable_asociacion].[id_obra]
								AND
							[cuenta_contable].[id_cuenta] = [cuenta_contable_asociacion].[id_cuenta]
					WHERE
						[cuenta_contable_asociacion].[id_cuenta_superior] IS NULL
							AND
						[cuenta_contable].[id_obra] = ?

					UNION ALL
								  
					SELECT
						[cuenta_contable].[id_obra],
						[cuenta_contable_asociacion].[id_cuenta],
						[cuenta_contable_asociacion].[id_cuenta_superior],
						[cuenta_contable].[afectable],
						[cuenta_contable].[codigo],
						[cuenta_contable].[nombre],
						[CTE_CUENTAS].[nivel] + 1 AS [nivel]
					FROM
						[Contabilidad].[cuenta_contable_asociacion]
					INNER JOIN
						[Contabilidad].[cuenta_contable]
						ON
							[cuenta_contable_asociacion].[id_obra] = [cuenta_contable].[id_obra]
								AND
							[cuenta_contable_asociacion].[id_cuenta] = [cuenta_contable].[id_cuenta]
					INNER JOIN
						[CTE_CUENTAS]
						ON
							[cuenta_contable_asociacion].[id_obra] = [CTE_CUENTAS].[id_obra]
								AND
							[cuenta_contable_asociacion].[id_cuenta_superior] = [CTE_CUENTAS].[id_cuenta]
				)
								
				SELECT
					  [CTE_CUENTAS].[id_obra]
					, [CTE_CUENTAS].[id_cuenta]
					, [CTE_CUENTAS].[id_cuenta_superior]
					, [CTE_CUENTAS].[afectable]
					, [CTE_CUENTAS].[codigo]
					, [CTE_CUENTAS].[nombre]
					, [CTE_CUENTAS].[nivel]
					, [empresas].[razon_social] AS [empresa]
					, [naturaleza].[agrupador] AS [agrupador_naturaleza]
				FROM
					[CTE_CUENTAS]
				LEFT OUTER JOIN
					[Agrupacion].[agrupacion_cuenta_contable]
					ON
						[CTE_CUENTAS].[id_obra] = [agrupacion_cuenta_contable].[id_obra]
							AND
						[CTE_CUENTAS].[id_cuenta] = [agrupacion_cuenta_contable].[id_cuenta]
				LEFT OUTER JOIN
					[Agrupacion].[agrupadores] AS [naturaleza]
					ON
						[agrupacion_cuenta_contable].[id_agrupador_naturaleza] = [naturaleza].[id_agrupador]
				LEFT OUTER JOIN
					[dbo].[empresas]
					ON
						[agrupacion_cuenta_contable].[id_agrupador_empresa_sao] = [empresas].[id_empresa]
				WHERE
					[CTE_CUENTAS].[id_cuenta_superior]";
		
		$params = array();

		if ($id_cuenta === 0) {
			$tsql .= " IS NULL";
			$params = array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT );
		} else {
			$tsql .= " = ?";
		    $params = array(
		        array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		    );
		}

	    $cuentas = $conn->executeQuery($tsql, $params);

	    return $cuentas;
	}

	public function getCuentasAgrupacionInsumo() {

		$tsql = "SELECT
					  [Cuentas].[IdCuenta] AS [IDCuenta]
					, LEFT([Cuentas].[Codigo], 4) + '-'
					+ SUBSTRING([Cuentas].[Codigo], 5, 2) + '-'
					+ SUBSTRING([Cuentas].[Codigo], 7, 2) + '-'
					+ SUBSTRING([Cuentas].[Codigo], 9, 3) AS [Codigo]
					, [Cuentas].[Nombre]
					, [Cuentas].[Afectable]
					, [AgrupacionCuentasContpaq].[id_grupador] AS [id_AgrupadorNaturaleza]
					, CONCAT([Agrupadores].[Codigo] + ' ', [Agrupadores].[Agrupador]) AS [AgrupadorNaturaleza]
				FROM
					[ReportesSAO].[Contabilidad].[Cuentas]
				LEFT OUTER JOIN
					[Agrupadores].[AgrupacionCuentasContpaq]
				  ON
					[Cuentas].[IDProyecto] = [AgrupacionCuentasContpaq].[IDProyecto]
						AND
					[Cuentas].[Codigo] = [AgrupacionCuentasContpaq].[CodigoCuenta]
				LEFT OUTER JOIN
					[Agrupadores].[Agrupadores]
				  ON
					[AgrupacionCuentasContpaq].[IDAgrupador] = [Agrupadores].[IDAgrupador]
				WHERE
					[Cuentas].[IDProyecto] = ?
				ORDER BY
					[Codigo]";

		$params = array(
	        array( $this->IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

		$data = $this->conn->executeQuery($tsql, $params);

		return $data;
	}

	public function getDatosCuenta() {
		
		$tsql = "SELECT
					  [cuenta_contable].[nombre]
					, [agrupacion_cuenta_contable].[id_agrupador_naturaleza]
					, [naturaleza].[agrupador] AS [agrupador_naturaleza]
					, [agrupacion_cuenta_contable].[id_agrupador_empresa_sao]
					, [empresas].[razon_social] AS [empresa]
				FROM
					[Contabilidad].[cuenta_contable]
				LEFT OUTER JOIN
					[Agrupacion].[agrupacion_cuenta_contable]
					ON
						[cuenta_contable].[id_obra] = [agrupacion_cuenta_contable].[id_obra]
							AND
						[cuenta_contable].[id_cuenta] = [agrupacion_cuenta_contable].[id_cuenta]
				LEFT OUTER JOIN
					[Agrupacion].[agrupadores] AS [naturaleza]
					ON
						[agrupacion_cuenta_contable].[id_agrupador_naturaleza] = [naturaleza].[id_agrupador]
				LEFT OUTER JOIN
					[dbo].[empresas]
					ON
						[Agrupacion].[agrupacion_cuenta_contable].[id_agrupador_empresa_sao] = [empresas].[id_empresa]
				WHERE
					[cuenta_contable].[id_obra] = ?
						AND
					[cuenta_contable].[id_cuenta] = ?";

		$params = array(
	        array( $this->id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

		$data = $this->conn->executeQuery($tsql, $params);

		return $data[0];
	}

	private static function getCodigo(SAODBConn $conn, $id_obra, $id_cuenta) {

		$tsql = "SELECT
					[codigo]
				FROM
					[Contabilidad].[cuenta_contable]
				WHERE
					[id_obra] = ?
						AND
			        [id_cuenta] = ?";

		$params = array(
	        array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $cuenta = $conn->executeQuery($tsql, $params);

	    return $cuenta[0]->codigo;
	}

	public function esAfectable() {

		$tsql = "SELECT
					1
				FROM
					[Contabilidad].[cuenta_contable]
				WHERE
					[id_obra] = ?
						AND
			        [id_cuenta] = ?
						AND
			        [afectable] = 1";

		$params = array(
	        array( $this->id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $cuenta = $this->conn->executeQuery($tsql, $params);

		if (count($cuenta) > 0)
	    	return true;
	    else
	    	return false;
	}

	public function getCuentasDescendientesAfectables() {

		$tsql = "WITH CTE_CUENTAS([id_obra], [id_cuenta], [id_cuenta_superior], 
				[afectable], [codigo], [nombre], [nivel])
				AS
				(  
					SELECT
						[cuenta_contable].[id_obra],
						[cuenta_contable].[id_cuenta],
						[cuenta_contable_asociacion].[id_cuenta_superior],
						[cuenta_contable].[afectable],
						[cuenta_contable].[codigo],
						[cuenta_contable].[nombre],
						1 AS [Nivel]
					FROM
						[Contabilidad].[cuenta_contable]
					LEFT OUTER JOIN
						[Contabilidad].[cuenta_contable_asociacion]
						ON
							[cuenta_contable].[id_obra] = [cuenta_contable_asociacion].[id_obra]
								AND
							[cuenta_contable].[id_cuenta] = [cuenta_contable_asociacion].[id_cuenta]
					WHERE
						[cuenta_contable_asociacion].[id_cuenta_superior] = ?
							AND
						[cuenta_contable].[id_obra] = ?

					UNION ALL
													  
					SELECT
						[cuenta_contable].[id_obra],
						[cuenta_contable_asociacion].[id_cuenta],
						[cuenta_contable_asociacion].[id_cuenta_superior],
						[cuenta_contable].[Afectable],
						[cuenta_contable].[Codigo],
						[cuenta_contable].[Nombre],
						[CTE_CUENTAS].[Nivel] + 1 AS [Nivel]
					FROM
						[Contabilidad].[cuenta_contable_asociacion]
					INNER JOIN
						[Contabilidad].[cuenta_contable]
						ON
							[cuenta_contable_asociacion].[id_obra] = [cuenta_contable].[id_obra]
								AND
							[cuenta_contable_asociacion].[id_cuenta] = [cuenta_contable].[id_cuenta]
					INNER JOIN
						[CTE_CUENTAS]
						ON
							[cuenta_contable_asociacion].[id_obra] = [CTE_CUENTAS].[id_obra]
								AND
							[cuenta_contable_asociacion].[id_cuenta_superior] = [CTE_CUENTAS].[id_cuenta]
				)
													
				SELECT
					  [CTE_CUENTAS].[id_obra]
					, [CTE_CUENTAS].[id_cuenta]
					, [CTE_CUENTAS].[id_cuenta_superior]
					, [CTE_CUENTAS].[afectable]
					, [CTE_CUENTAS].[codigo]
					, [CTE_CUENTAS].[nombre]
					, [CTE_CUENTAS].[nivel]
				FROM
					[CTE_CUENTAS]
				WHERE
					[CTE_CUENTAS].[id_cuenta] != ?
						AND
					[CTE_CUENTAS].[afectable] = 1";

			$params = array(
		        array( $this->id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $this->id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $this->id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		    );

		    return $this->conn->executeQuery($tsql, $params);
	}

	public function setAgrupador($id_agrupador, $method) {

		if ($this->esAfectable()) {
			$this->{$method}($this->id_cuenta, $id_agrupador);
		} else {

			$afectables = $this->getCuentasDescendientesAfectables();

			foreach ($afectables as $cuenta) {
				$this->{$method}($cuenta->id_cuenta, $id_agrupador);
			}
		}
	}

	private function setAgrupadorEmpresa($id_cuenta, $id_agrupador) {

		if (!$this->existeRegistroAgrupacion($id_cuenta))
			$this->creaRegistroAgrupacion($id_cuenta);

		$tsql = "UPDATE [Agrupacion].[agrupacion_cuenta_contable]
				 SET
				 	[id_agrupador_empresa_sao] = ?
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_cuenta] = ?";

	    $params = array(
	        array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery($tsql, $params);
	}

	private function setAgrupadorNaturaleza($id_cuenta, $id_agrupador) {

		if (!$this->existeRegistroAgrupacion($id_cuenta))
			$this->creaRegistroAgrupacion($id_cuenta);

		$tsql = "UPDATE [Agrupacion].[agrupacion_cuenta_contable]
				 SET
				 	[id_agrupador_naturaleza] = ?
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_cuenta] = ?";

	    $params = array(
	        array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery($tsql, $params);
	}

	private function existeRegistroAgrupacion($id_cuenta) {

		$tsql = "SELECT
					1
				FROM
					[Agrupacion].[agrupacion_cuenta_contable]
				WHERE
					[id_obra] = ?
						AND
					[id_cuenta] = ?";

	    $params = array(
	        array( $this->id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $cuenta = $this->conn->executeQuery($tsql, $params);

		return count($cuenta) > 0 ? true : false;
	}

	private function creaRegistroAgrupacion($id_cuenta) {

		$codigo = self::getCodigo($this->conn, $this->id_obra, $id_cuenta);

		$tsql = "INSERT INTO [Agrupacion].[agrupacion_cuenta_contable]
				(
					  [id_obra]
					, [id_cuenta]
					, [codigo]
				)
				VALUES
				( ?, ?, ? )";

	    $params = array($this->id_obra, $id_cuenta, $codigo);

	    $this->conn->executeQuery($tsql, $params);
	}

	// private function setAgrupadorTipoCuenta($id_cuenta, $id_agrupador) {
		
	// 	if (! $this->existeRegistroAgrupacion($id_cuenta))
	// 		$this->creaRegistroAgrupacion($id_cuenta);

	// 	$tsql = "UPDATE [Contabilidad].[AgrupacionCuentaContable]
	// 			 SET
	// 			 	[IDAgrupadorTipoCuenta] = ?
	// 			 WHERE
	// 			 	[IDProyecto] = ?
	// 			 		AND
	// 			 	[IDCuenta] = ?";

	//     $params = array(
	//         array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	//         array( $this->IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	//         array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	//     );

	//     $this->conn->executeQuery($tsql, $params);
	// }

	public function __toString() {

		$data  = "FechaInicio: {}, ";
		$data .= "FechaTermino: {}, ";
		$data .= "Referencia: {}, ";

		return $data;
	}
}
?>