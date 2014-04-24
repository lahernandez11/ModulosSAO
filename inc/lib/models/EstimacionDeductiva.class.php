<?php
require_once 'EstimacionSubcontrato.class.php';
require_once 'Empresa.class.php';
require_once 'Material.class.php';
require_once 'EstimacionDescuento.class.php';

class EstimacionDeductiva {
	
	private $id_item;
	public  $empresa;
	public  $material;

	private $cantidad_total;
	private $unidad;
	private $precio;
	private $importe_total;

	private $conn;

	public function __construct( Empresa $empresa, $id_item=null ) {

		if ( ! is_int( (int) $id_item ) ){
			throw new Exception("Identificador de deductiva incorrecto.", 1);
		}
		
		$this->empresa = $empresa;
		$this->id_item = $id_item;
		$this->conn    = $empresa->obra->getConn();
		$this->init();
	}

	public static function getObjects( Empresa $empresa, $id_item=null ) {

		$tsql = "SELECT
					  [ItemsXContratista].[id_item]
				FROM
					[Compras].[ItemsXContratista]
				WHERE
					[ItemsXContratista].[id_empresa] = ?
						AND
					[ItemsXContratista].[id_item] = ISNULL(?, [ItemsXContratista].[id_item])";

	    $params = array(
	        array( $empresa->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_item, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $rows = $empresa->obra->getConn()->executeQuery( $tsql, $params );

	    $deductivas = array();
	    foreach ($rows as $deductiva) {
	    	$deductivas[] = new self( $empresa, $deductiva->id_item );
	    }

	    return $deductivas;
	}

	private function init() {

		$tsql = "SELECT 
					  [items].[id_material]
					, [items].[cantidad] AS [cantidad_total]
					, [items].[unidad]
					, [items].[precio_unitario]
					, [items].[importe] AS [importe_total]
				FROM
					[Compras].[ItemsXContratista]
				INNER JOIN
					[dbo].[items]
				ON
					[ItemsXContratista].[id_item] = [items].[id_item]
				WHERE
					[ItemsXContratista].[id_item] = ?";

	    $params = array(
	        array( $this->id_item, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $data = $this->conn->executeQuery( $tsql, $params );

		$this->material 		  	  = new Material( $this->empresa->obra, $data[0]->id_material );
		$this->cantidad_total 		  = $data[0]->cantidad_total;
		$this->unidad 		  		  = $data[0]->unidad;
		$this->precio 				  = $data[0]->precio_unitario;
		$this->importe_total 		  = $data[0]->importe_total;
	}

	public function getId() {
		return $this->id_item;
	}

	public function getCantidadTotal() {
		return $this->cantidad_total;
	}

	public function getUnidad() {
		return $this->unidad;
	}

	public function getPrecio() {
		return $this->precio;
	}

	public function getImporteTotal() {
		return $this->importe_total;
	}

	public function getDescuento( EstimacionSubcontrato $estimacion ) {
		return EstimacionDescuento::getInstance( $estimacion, $this );
	}

	public function __toString() {
		$data =  "id: {$this->id_item}, ";
		$data .= "empresa: { {$this->empresa} }";
		$data .= "cantidad_total: {$this->cantidad_total}, ";
		$data .= "precio: {$this->precio}, ";
		$data .= "importe_total: {$this->importe_total}, ";
		$data .= "cantidad_descontada: {$this->cantidad_descontada}, ";
		$data .= "importe_descontado: {$this->importe_descontado}, ";
		$data .= "cantidad_por_descontar: {$this->cantidad_por_descontar}, ";
		$data .= "importe_por_descontar: {$this->importe_por_descontar}, ";

		return $data;
	}
}
?>