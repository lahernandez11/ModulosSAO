<?php
require_once 'db/SAODBConn.class.php';

class CuentaContable {

	private $conn = null;
	private $IDProyecto = null;

	public function __construct( $IDProyecto, ReportesSAOConn $conn ) {
		
		if ( ! is_int($IDProyecto) || ! $IDProyecto > 0 ) {
			throw new Exception("El identificador de obra no es correcto.");
		} else {
			$this->conn = $conn;
			$this->IDProyecto = $IDProyecto;
		}
	}

	public function getCuentas( $id_cuenta ) {

		$tsql = "WITH CTE_CUENTAS([IdProyecto], [IdCuenta], [IdCtaSup], [Afectable], [Codigo], [Nombre], [Nivel])
				AS
				(  
					SELECT
						[Cuentas].[IdProyecto],
						[Cuentas].[IdCuenta],
						[Asociaciones].[IDCtaSup],
						[Cuentas].[Afectable],
						[Cuentas].[Codigo],
						[Cuentas].[Nombre],
						1 AS [Nivel]
					FROM
						[Contabilidad].[Cuentas]
					LEFT OUTER JOIN
					    [Contabilidad].[Asociaciones]
						ON
							[Cuentas].[IdProyecto] = [Asociaciones].[IdProyecto]
								AND
				            [Cuentas].[IdCuenta] = [Asociaciones].[IdCuenta]
					WHERE
				        [Asociaciones].[IDCtaSup] IS NULL
				        	AND
				       	[Cuentas].[IdProyecto] = ?

					UNION ALL
				  
				    SELECT
						[Cuentas].[IdProyecto],
						[Asociaciones].[IdCuenta],
						[Asociaciones].[IdCtaSup],
						[Cuentas].[Afectable],
						[Cuentas].[Codigo],
						[Cuentas].[Nombre],
						[CTE_CUENTAS].[Nivel] + 1 AS [Nivel]
					FROM
						[Contabilidad].[Asociaciones]
					INNER JOIN
						[Contabilidad].[Cuentas]
						ON
							[Asociaciones].[IdProyecto] = [Cuentas].[IdProyecto]
								AND
				            [Asociaciones].[IdCuenta] = [Cuentas].[IdCuenta]
					INNER JOIN
						[CTE_CUENTAS]
						ON
							[Asociaciones].[IdProyecto] = [CTE_CUENTAS].[IdProyecto]
								AND
				            [Asociaciones].[IdCtaSup] = [CTE_CUENTAS].[IdCuenta]
				)
				
				SELECT
					  [CTE_CUENTAS].[IdProyecto]
					, [CTE_CUENTAS].[IdCuenta]
					, [CTE_CUENTAS].[IdCtaSup]
					, [CTE_CUENTAS].[Afectable]
					, [CTE_CUENTAS].[Codigo]
					, [CTE_CUENTAS].[Nombre]
					, [CTE_CUENTAS].[Nivel]
					, [Proveedores].[Nombre] AS [Proveedor]
					, [empresas].[razon_social] AS [Empresa]
				FROM
					[CTE_CUENTAS]
				LEFT OUTER JOIN
					[Contabilidad].[AgrupacionCuentaContable]
					ON
						[CTE_CUENTAS].[IdProyecto] = [AgrupacionCuentaContable].[IDProyecto]
							AND
				        [CTE_CUENTAS].[IdCuenta] = [AgrupacionCuentaContable].[IDCuenta]
				LEFT OUTER JOIN
					[Contabilidad].[Proveedores]
					ON
						[AgrupacionCuentaContable].[IDProyecto] = [Proveedores].[IdProyecto]
							AND
				        [AgrupacionCuentaContable].[IDAgrupadorProveedor] = [Proveedores].[IdProveedor]
				LEFT OUTER JOIN
					[SAO1814Reportes].[dbo].[empresas]
					ON
						[AgrupacionCuentaContable].[IDAgrupadorEmpresaSAO] = [empresas].[id_empresa]
				WHERE
					[IDCtaSup] ";
		
		$params = array();

		if ($id_cuenta === 0) {
			$tsql .= "IS NULL";
			$params = array( $this->IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT );
		} else {
			$tsql .= " = ?";
		    $params = array(
		        array( $this->IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		    );
		}

	    $cuentas = $this->conn->executeSP($tsql, $params);

	    return $cuentas;
	}

	public function getDatosCuenta( $id_cuenta ) {
		
		$tsql = "SELECT
					  [Cuentas].[Nombre]
					, [AgrupacionCuentaContable].[IDAgrupadorNaturaleza]
					, [AgrupacionCuentaContable].[IDAgrupadorTipoCuenta]
					, [AgrupadorTipoCuenta].[AgrupadorTipoCuenta]
					, [AgrupacionCuentaContable].[IDAgrupadorProveedor]
					, [Proveedores].[Nombre] AS [Proveedor]
					, [empresas].[razon_social] AS [Empresa]
				FROM
					[Contabilidad].[Cuentas]
				LEFT OUTER JOIN
					[Contabilidad].[AgrupacionCuentaContable]
					ON
						[Cuentas].[IdProyecto] = [AgrupacionCuentaContable].[IDProyecto]
							AND
						[Cuentas].[IdCuenta] = [AgrupacionCuentaContable].[IDCuenta]
				LEFT OUTER JOIN
					[Contabilidad].[AgrupadorTipoCuenta]
					ON
						[AgrupacionCuentaContable].[IDProyecto] = [AgrupadorTipoCuenta].[IDProyecto]
							AND
				        [AgrupacionCuentaContable].[IDAgrupadorTipoCuenta] = [AgrupadorTipoCuenta].[IDAgrupadorTipoCuenta]
				LEFT OUTER JOIN
					[Contabilidad].[Proveedores]
					ON
						[AgrupacionCuentaContable].[IDProyecto] = [Proveedores].[IdProyecto]
							AND
						[AgrupacionCuentaContable].[IDAgrupadorProveedor] = [Proveedores].[IdProveedor]
				LEFT OUTER JOIN
					[SAO1814Reportes].[dbo].[empresas]
					ON
						[AgrupacionCuentaContable].[IDAgrupadorEmpresaSAO] = [empresas].[id_empresa]
				WHERE
					[Cuentas].[IdProyecto] = ?
						AND
				    [Cuentas].[IdCuenta] = ?";

		$params = array(
	        array( $this->IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

		$data = $this->conn->executeSP($tsql, $params);

		return $data[0];
	}

	public function esAfectable($id_cuenta) {

		$tsql = "SELECT
					1
				FROM
					[Contabilidad].[Cuentas]
				WHERE
					[Cuentas].[IdProyecto] = ?
						AND
			        [Cuentas].[IdCuenta] = ?
						AND
			        [Cuentas].[Afectable] = 1";

		$params = array(
	        array( $this->IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $cuenta = $this->conn->executeQuery($tsql, $params);

		if (count($cuenta) > 0)
	    	return true;
	    else
	    	return false;
	}

	public function setAgrupador($id_cuenta, $id_agrupador, $method) {

		if ($this->esAfectable($id_cuenta)) {
			$this->{$method}($id_cuenta, $id_agrupador);
		} else {
			$tsql = "WITH CTE_CUENTAS([IdProyecto], [IdCuenta], [IdCtaSup], [Afectable], [Codigo], [Nombre], [Nivel])
					AS
					(  
						SELECT
							[Cuentas].[IdProyecto],
							[Cuentas].[IdCuenta],
							[Asociaciones].[IDCtaSup],
							[Cuentas].[Afectable],
							[Cuentas].[Codigo],
							[Cuentas].[Nombre],
							1 AS [Nivel]
						FROM
							[Contabilidad].[Cuentas]
						LEFT OUTER JOIN
							[Contabilidad].[Asociaciones]
							ON
								[Cuentas].[IdProyecto] = [Asociaciones].[IdProyecto]
									AND
								[Cuentas].[IdCuenta] = [Asociaciones].[IdCuenta]
						WHERE
							[Asociaciones].[IDCtaSup] = ?
								AND
							[Cuentas].[IdProyecto] = ?

						UNION ALL
									  
						SELECT
							[Cuentas].[IdProyecto],
							[Asociaciones].[IdCuenta],
							[Asociaciones].[IdCtaSup],
							[Cuentas].[Afectable],
							[Cuentas].[Codigo],
							[Cuentas].[Nombre],
							[CTE_CUENTAS].[Nivel] + 1 AS [Nivel]
						FROM
							[Contabilidad].[Asociaciones]
						INNER JOIN
							[Contabilidad].[Cuentas]
							ON
								[Asociaciones].[IdProyecto] = [Cuentas].[IdProyecto]
									AND
								[Asociaciones].[IdCuenta] = [Cuentas].[IdCuenta]
						INNER JOIN
							[CTE_CUENTAS]
							ON
								[Asociaciones].[IdProyecto] = [CTE_CUENTAS].[IdProyecto]
									AND
								[Asociaciones].[IdCtaSup] = [CTE_CUENTAS].[IdCuenta]
					)
									
					SELECT
						  [CTE_CUENTAS].[IdProyecto]
						, [CTE_CUENTAS].[IdCuenta]
						, [CTE_CUENTAS].[IdCtaSup]
						, [CTE_CUENTAS].[Afectable]
						, [CTE_CUENTAS].[Codigo]
						, [CTE_CUENTAS].[Nombre]
						, [CTE_CUENTAS].[Nivel]
					FROM
						[CTE_CUENTAS]
					WHERE
						[CTE_CUENTAS].[IdCuenta] != ?
							AND
						[CTE_CUENTAS].[Afectable] = 1";

			$params = array(
		        array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $this->IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		    );

			$afectables = $this->conn->executeQuery($tsql, $params);

			foreach ($afectables as $cuenta) {
				$this->{$method}($cuenta->IdCuenta, $id_agrupador);
			}
		}
	}

	private function existeRegistroAgrupacion($id_cuenta) {

		$tsql = "SELECT
					1
				FROM
					[Contabilidad].[AgrupacionCuentaContable]
				WHERE
					[AgrupacionCuentaContable].[IDProyecto] = ?
						AND
					[AgrupacionCuentaContable].[IDCuenta] = ?";

	    $params = array(
	        array( $this->IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $cuenta = $this->conn->executeQuery($tsql, $params);

		return count($cuenta) > 0 ? true : false;
	}

	private function creaRegistroAgrupacion($id_cuenta) {

		$tsql = "INSERT INTO [Contabilidad].[AgrupacionCuentaContable]
				(
					  [IDProyecto]
					, [IDCuenta]
				)
				VALUES
				( ?, ? )";

	    $params = array(
	        array( $this->IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery($tsql, $params);
	}

	private function setAgrupadorProveedor( $id_cuenta, $id_agrupador ) {

		if (! $this->existeRegistroAgrupacion($id_cuenta))
			$this->creaRegistroAgrupacion($id_cuenta);

		$tsql = "UPDATE [Contabilidad].[AgrupacionCuentaContable]
				 SET
				 	[IDAgrupadorProveedor] = ?
				 WHERE
				 	[IDProyecto] = ?
				 		AND
				 	[IDCuenta] = ?";

	    $params = array(
	        array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery($tsql, $params);
	}

	private function setAgrupadorEmpresa( $id_cuenta, $id_agrupador ) {

		if (! $this->existeRegistroAgrupacion($id_cuenta))
			$this->creaRegistroAgrupacion($id_cuenta);

		$tsql = "UPDATE [Contabilidad].[AgrupacionCuentaContable]
				 SET
				 	[IDAgrupadorEmpresaSAO] = ?
				 WHERE
				 	[IDProyecto] = ?
				 		AND
				 	[IDCuenta] = ?";

	    $params = array(
	        array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery($tsql, $params);
	}

	private function setAgrupadorTipoCuenta( $id_cuenta, $id_agrupador ) {
		
		if (! $this->existeRegistroAgrupacion($id_cuenta))
			$this->creaRegistroAgrupacion($id_cuenta);

		$tsql = "UPDATE [Contabilidad].[AgrupacionCuentaContable]
				 SET
				 	[IDAgrupadorTipoCuenta] = ?
				 WHERE
				 	[IDProyecto] = ?
				 		AND
				 	[IDCuenta] = ?";

	    $params = array(
	        array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_cuenta, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery($tsql, $params);
	}

	public function __toString() {

		$data  = "FechaInicio: {}, ";
		$data .= "FechaTermino: {}, ";
		$data .= "Referencia: {}, ";

		return $data;
	}
}
?>