<?php

class Empresa {
	
	const PROVEEDOR = 1;
	const CONTRATISTA = 2;
	const CONTRATISTA_PROVEEDOR = 3;
	const DESTAJISTA = 4;

	public $obra;
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
					  [id_empresa]
					, [tipo_empresa]
					, [razon_social]
					, [rfc]
					, [dias_credito]
					, [formato]
					, [cuenta_contable]
					, [tipo_cliente]
					, [porcentaje]
					, [no_proveedor_virtual]
				FROM
					[dbo].[empresas]
				WHERE
					[id_empresa] = ?";

		$data = $this->conn->executeQuery( $tsql, array( $this->getId() ) );

		$this->tipo   	   			= $data[0]->tipo_empresa;
		$this->nombre 	   			= $data[0]->razon_social;
		$this->rfc 		   			= $data[0]->rfc;
		$this->dias_credito 		= $data[0]->dias_credito;
		$this->formato 				= $data[0]->formato;
		$this->cuenta_contable 		= $data[0]->cuenta_contable;
		$this->tipo_cliente 		= $data[0]->tipo_cliente;
		$this->porcentaje 			= $data[0]->porcentaje;
		$this->no_proveedor_virtual = $data[0]->no_proveedor_virtual;
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