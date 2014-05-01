<?php
require_once 'EstimacionSubcontrato.class.php';
require_once 'EstimacionDeductiva.class.php';

class EstimacionDescuento {
	
	private $id;
	public  $obra;
	private $estimacion;    // estimacion donde se aplica el descuento
	private $deductiva;     // deductiva donde se aplica el descuento
	private $cantidad = 0;  // cantidad descontada
	private $precio   = 0;  // precio del descuento
	private $importe  = 0;  // importe del descuento
	private $cantidad_descontada_anterior = 0;  // suma de cantidad descontado en otras estimaciones
	private $importe_descontado_anterior  = 0;  // suma de importe descontado en otras estimaciones
	private $creado;

	private $conn;

	public function __construct( EstimacionSubcontrato $estimacion, EstimacionDeductiva $deductiva ) {
		$this->estimacion = $estimacion;
		$this->deductiva  = $deductiva;
		$this->conn 	  = $estimacion->obra->getConn();

		$this->init();
	}

	public static function getInstance( EstimacionSubcontrato $estimacion, EstimacionDeductiva $deductiva=null ) {

		$tsql = "SELECT
					  [id_item]
				FROM
					[SubcontratosEstimaciones].[descuento]
				WHERE
					[id_transaccion] = ?
						AND
					[id_item] = ISNULL(?, [id_item])";

		$params = array(
			array( $estimacion->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $deductiva instanceof EstimacionDeductiva ? $deductiva->getId() : null, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		);

		$data = $estimacion->obra->getConn()->executeQuery( $tsql, $params );
    	
    	$descuentos = array();

    	if ( $deductiva instanceof EstimacionDeductiva ) {
			$descuentos = new self( $estimacion, $deductiva );			
    	} else {
    		foreach ( $data as $deductiva ) {
    			$descuentos[] = new self( $estimacion, new EstimacionDeductiva( $estimacion->empresa, $deductiva->id_item ) );
    		}
    	}

		return $descuentos;
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
					[descuento].[id_transaccion] != ?
						AND
					[descuento].[id_item] = ?;";

		$params = array(
	        array( $this->estimacion->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->deductiva->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
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
			if ( $this->cantidad == 0 ) {
				$this->delete();
			} else {
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
			}
		} else {
			// ingresa registro
			if ( $this->cantidad > 0 ) {

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
	}

	public function delete() {

		if ( $this->estimacion->estaAprobada() ) {
			throw new Exception("La estimacion se encuentra aprobada.", 1);
		}

		$tsql = "DELETE
					[SubcontratosEstimaciones].[descuento]
				WHERE
					[id_transaccion] = ?
						AND
				    [id_descuento] = ?";

		$params = array(
	        array( $this->estimacion->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

    	$this->conn->executeQuery( $tsql, $params );
	}

	public function getId() {
		return $this->id;
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
		$data .= "cantidad: {$this->cantidad}, ";
		$data .= "precio: {$this->precio}, ";
		$data .= "importe: {$this->importe}, ";
		$data .= "creado: {$this->creado}";

		return $data;
	}
}
?>