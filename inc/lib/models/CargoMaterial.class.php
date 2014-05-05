<?php
require_once 'EstimacionSubcontrato.class.php';
require_once 'Empresa.class.php';
require_once 'Material.class.php';
require_once 'EstimacionDescuentoMaterial.class.php';

class CargoMaterial {
	
	public  $material;
	public  $empresa;
	// public  $descuentos = array();
	private $cantidad = 0;
	private $precio   = 0;
	private $importe  = 0;

	private $conn;

	public function __construct( Empresa $empresa, Material $material ) {
		
		$this->empresa  = $empresa;
		$this->material = $material;
		$this->conn     = $empresa->obra->getConn();
		$this->init();
	}

	public static function getObjects( Empresa $empresa, Material $material=null ) {

		$tsql = "SELECT DISTINCT
					  [items].[id_material]
				FROM
					[Compras].[ItemsXContratista]
				INNER JOIN
					[dbo].[items]
					ON
						[ItemsXContratista].[id_item] = [items].[id_item]
				WHERE
					[ItemsXContratista].[id_empresa] = ?
						AND
					[items].[id_material] = ISNULL(?, [items].[id_material])
				ORDER BY
					[items].[id_material]";

	    $params = array(
	        array( $empresa->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $material instanceof Material ? $material->getId() : null, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $rows = $empresa->obra->getConn()->executeQuery( $tsql, $params );

	    $cargos = array();

	    foreach ( $rows as $cargo ) {
	    	$cargos[] = new self( $empresa, new Material( $empresa->obra, $cargo->id_material ) );
	    }

	    return $cargos;
	}

	private function init() {

		$tsql = "SELECT 
					  SUM([items].[cantidad]) AS [cantidad]
					, SUM([items].[importe]) / SUM([items].[cantidad]) AS [precio]
					, SUM([items].[importe]) AS [importe]
				FROM
					[Compras].[ItemsXContratista]
				INNER JOIN
					[dbo].[items]
				ON
					[ItemsXContratista].[id_item] = [items].[id_item]
				WHERE
					[ItemsXContratista].[id_empresa] = ?
						AND
					[items].[id_material] = ?";

	    $params = array(
	        array( $this->empresa->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->material->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $data = $this->conn->executeQuery( $tsql, $params );

		$this->cantidad   = $data[0]->cantidad;
		$this->precio     = $data[0]->precio;
		$this->importe    = $data[0]->importe;
		// $this->descuentos = EstimacionDescuentoMaterial::getDescuentosPorEmpresa( $this->empresa );
	}

	public function getCantidad() {
		return $this->cantidad;
	}

	public function getPrecio() {
		return $this->precio;
	}

	public function getImporte() {
		return $this->importe;
	}

	public function __toString() {
		$data =  "id: {$this->id_item}, ";
		$data .= "empresa: { {$this->empresa->getNombre()} }";
		$data .= "material: { {$this->material->getDescripcion()} }";
		$data .= "cantidad: {$this->cantidad}, ";
		$data .= "precio: {$this->precio}, ";
		$data .= "importe: {$this->importe}, ";

		return $data;
	}

	/*
	 * Obtiene el descuento hecho a la deductiva en la estimacion
	 * solo se carga un descuento por deductiva en cada estimacion
	*/
	public function getDescuentos( EstimacionSubcontrato $estimacion ) {
		return EstimacionDescuentoMaterial::getInstance( $estimacion, $this );
	}
}
?>