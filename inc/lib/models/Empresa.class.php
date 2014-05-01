<?php
require_once 'EstimacionDeductiva.class.php';

class Empresa {
	
	const PROVEEDOR = 1;
	const CONTRATISTA = 2;
	const CONTRATISTA_PROVEEDOR = 3;
	const DESTAJISTA = 4;

	public  $obra;
	public  $deductivas = array();
	private $id;
	private $tipo;
	private $nombre;
	private $rfc;
	private $dias_credito;
	private $formato;
	private $cuenta_contable;
	private $tipo_cliente;
	private $porcentaje;
	private $no_proveedor_virtual;

	private $importe_acumulado_cargos = 0;

	private $conn;

	public function __construct( Obra $obra, $id_empresa ) {

		if ( ! is_int( $id_empresa ) ) {
			throw new Exception("El identificador de empresa no es valido");
		}

		$this->obra = $obra;
		$this->id   = $id_empresa;
		$this->conn = $obra->getConn();
		$this->init();
	}

	private function init() {

		$tsql = "SELECT    
					  [empresas].[id_empresa]
					, [empresas].[tipo_empresa]
					, [empresas].[razon_social]
					, [empresas].[rfc]
					, [empresas].[dias_credito]
					, [empresas].[formato]
					, [empresas].[cuenta_contable]
					, [empresas].[tipo_cliente]
					, [empresas].[porcentaje]
					, [empresas].[no_proveedor_virtual]
					, [cargos_contratista].[importe_cargado]
				FROM
					[dbo].[empresas]
				LEFT OUTER JOIN
				(
					SELECT
						  [id_empresa]
						, SUM([items].[importe]) AS [importe_cargado]
					FROM
					    [Compras].[ItemsXContratista]
					INNER JOIN
						[dbo].[items]
						ON
							[ItemsXContratista].[id_item] = [items].[id_item]
								AND
							[ItemsXContratista].[con_cargo] = 1
					GROUP BY
						[id_empresa]
				) AS [cargos_contratista]
				ON
					[empresas].[id_empresa] = [cargos_contratista].[id_empresa]
				WHERE
					[empresas].[id_empresa] = ?";

		$data = $this->conn->executeQuery( $tsql, array( $this->getId() ) );

		$this->tipo   	   				= $data[0]->tipo_empresa;
		$this->nombre 	   				= $data[0]->razon_social;
		$this->rfc 		   				= $data[0]->rfc;
		$this->dias_credito 			= $data[0]->dias_credito;
		$this->formato 					= $data[0]->formato;
		$this->cuenta_contable 			= $data[0]->cuenta_contable;
		$this->tipo_cliente 			= $data[0]->tipo_cliente;
		$this->porcentaje 				= $data[0]->porcentaje;
		$this->no_proveedor_virtual 	= $data[0]->no_proveedor_virtual;
		$this->importe_acumulado_cargos = $data[0]->importe_cargado;
		$this->deductivas 				= EstimacionDeductiva::getObjects( $this );
	}

	public function getId() {
		return $this->id;
	}

	public function getNombre() {
		return $this->nombre;
	}

	public function getTipo() {
		return $this->tipo;
	}

	public function getRFC() {
		return $this->rfc;
	}

	public function getImporteAcumuladoCargos() {
		return $this->importe_acumulado_cargos;
	}

	public function getImporteTotalRetenido() {

		$tsql = "SELECT
					  [transacciones].[id_empresa]
					, SUM([retencion].[importe]) AS [importe_retenido]
				FROM
					[SubcontratosEstimaciones].[retencion]
				INNER JOIN
					[dbo].[transacciones]
					ON
						[retencion].[id_transaccion] = [transacciones].[id_transaccion]
				WHERE
					[transacciones].[id_empresa] = ?
				GROUP BY
					[transacciones].[id_empresa];";

		$data = $this->conn->executeQuery( $tsql, array( $this->id) );

		if ( count( $data ) > 0 ) {
			return $data[0]->importe_retenido;
		} else {
			return 0;
		}
	}

	public function getImporteTotalRetencionLiberado() {
		
		$tsql = "SELECT
					  [transacciones].[id_empresa]
					, SUM([retencion_liberacion].[importe]) AS [importe_liberado]
				FROM
					[SubcontratosEstimaciones].[retencion_liberacion]
				INNER JOIN
					[dbo].[transacciones]
					ON
						[retencion_liberacion].[id_transaccion] = [transacciones].[id_transaccion]
				WHERE
					[transacciones].[id_empresa] = ?
				GROUP BY
					[transacciones].[id_empresa];";

		$data = $this->conn->executeQuery( $tsql, array( $this->id) );

		if ( count( $data ) > 0 ) {
			return $data[0]->importe_liberado;
		} else {
			return 0;
		}
	}

	public function getImportePorLiberar() {
		return $this->getImporteTotalRetenido() - $this->getImporteTotalRetencionLiberado();
	}

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

	public function __toString() {
		$data =  "id: {$this->id}, ";
		$data .= "tipo: {$this->tipo}, ";
		$data .= "nombre: {$this->nombre}, ";
		$data .= "rfc: {$this->rfc}, ";
		$data .= "obra: { {$this->obra} }";

		return $data;
	}
}
?>