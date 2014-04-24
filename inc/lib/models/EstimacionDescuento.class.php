<?php
require_once 'EstimacionSubcontrato.class.php';
require_once 'EstimacionDeductiva.class.php';

class EstimacionDescuento {
	
	private $id;
	public  $obra;
	private $estimacion;
	private $deductiva;
	private $cantidad = 0;
	private $precio   = 0;
	private $importe  = 0;
	private $cantidad_descontada_anterior = 0;
	private $importe_descontado_anterior = 0;
	private $creado;

	private $conn;


	public function __construct( EstimacionSubcontrato $estimacion, EstimacionDeductiva $deductiva ) {
		$this->estimacion = $estimacion;
		$this->deductiva  = $deductiva;
		$this->conn 	  = $estimacion->obra->getConn();

		$this->init();
	}

	public static function getInstance( EstimacionSubcontrato $estimacion, EstimacionDeductiva $deductiva ) {

		return new self( $estimacion, $deductiva );
	}

	private function init() {

		$tsql = "SELECT
					  [id_descuento]
					, [cantidad]
					, [precio]
					, [importe]
					, [creado]
				FROM
					[SubcontratosEstimaciones].[descuento]
				WHERE
					[id_transaccion] = ?
						AND
					[id_item] = ?";

		$params = array(
	        array( $this->estimacion->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->deductiva->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $row = $this->conn->executeQuery( $tsql, $params );
	    
	    if ( count($row) > 0 ) {

			$this->id	  	  = $row[0]->id_descuento;
			$this->cantidad	  = $row[0]->cantidad;
			$this->precio	  = $row[0]->precio;
			$this->importe	  = $row[0]->importe;
			$this->creado	  = $row[0]->creado;

		}

		$tsql = "SELECT
					  SUM([cantidad]) AS [cantidad_descontada]
					, SUM([importe]) AS [importe_descontado]
				FROM
					[SubcontratosEstimaciones].[descuento]
				INNER JOIN
					[dbo].[transacciones]
					ON
						[descuento].[id_transaccion] = [transacciones].[id_transaccion]
				WHERE
					--[descuento].[id_transaccion] != ?
						--AND
					[descuento].[id_item] = ?
					--	AND
					--DATEDIFF(DAY, ?, [transacciones].[fecha]) <= 0;";

		$params = array(
	        // array( $this->estimacion->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->deductiva->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->estimacion->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_SMALLDATETIME ),
	    );

	    $row = $this->conn->executeQuery( $tsql, $params );
	    $this->cantidad_descontada_anterior	  = $row[0]->cantidad_descontada;
		$this->importe_descontado_anterior	  = $row[0]->importe_descontado;
	}

	public function save() {

		if ( ! $this->estimacion instanceof EstimacionSubcontrato ) {
			throw new Exception("No se ha especificado la estimación.", 1);
		}

		if ( ! $this->deductiva instanceof EstimacionDeductiva ) {
			throw new Exception("No se ha especificado la deductiva", 1);
		}

		if ( isset( $this->id ) ) {
			// actualiza registro
			$tsql = "UPDATE
						[SubcontratosEstimaciones].[descuento]
					SET
						[cantidad] = ?,
						[precio]   = ?
					WHERE
						[descuento].[id_descuento] = ?";

			$params = array(
		        array( $this->cantidad, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
		        array( $this->precio, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
		        array( $this->id, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		    );

			$this->conn->executeQuery( $tsql, $params );

		} else {
			// ingresa registro
			$tsql = "INSERT INTO [SubcontratosEstimaciones].[descuento]
			        (
				          [id_transaccion]
				        , [id_item]
				        , [cantidad]
				        , [precio]
			        )
					VALUES
			        ( ?, ?, ?, ? );";

			$params = array(
		        array( $this->estimacion->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $this->deductiva->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $this->cantidad, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
		        array( $this->precio, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
		    );

		    $id_descuento = null;
	    	$id_descuento = $this->conn->executeQueryGetId( $tsql, $params );

	    	$this->id = $id_descuento;
	    	$this->importe = $this->cantidad * $this->precio;
		}
	}

	public function getId() {
		return $this->id;
	}

	public function setEstimacion( EstimacionSubcontrato $estimacion ) {
		$this->estimacion = $estimacion;
	}

	public function setDeductiva( EstimacionDeductiva $deductiva ) {
		$this->deductiva = $deductiva;
	}

	public function setCantidad( $cantidad ) {
		$this->cantidad = $cantidad;
	}

	public function setPrecio( $precio ) {
		$this->precio = $precio;
	}

	public function getCantidad() {
		return $this->cantidad;
	}

	public function getPrecio() {
		return $this->precio  ;
	}

	public function getImporte() {
		return $this->importe ;
	}

	public function getCantidadDescontada() {
		return $this->cantidad_descontada_anterior;
	}

	public function getImporteDescontado() {
		return $this->importe_descontado_anterior;
	}

	public function __toString() {
		$data =  "id: {$this->id}, ";
		$data .= "estimacion:{ {$this->estimacion} }, ";
		$data .= "deductiva:{ {$this->deductiva} }, ";
		$data .= "cantidad: {$this->cantidad}, ";
		$data .= "precio: {$this->precio}, ";
		$data .= "importe: {$this->importe}, ";
		$data .= "creado: {$this->creado}";

		return $data;
	}

}
?>