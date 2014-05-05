<?php
require_once 'EstimacionSubcontrato.class.php';
require_once 'Material.class.php';

class EstimacionDescuentoMaterial {
	
	private $id;
	public  $obra;
	public  $estimacion;    // estimacion donde se aplica el descuento
	public  $material;     // deductiva donde se aplica el descuento
	private $cantidad = 0;  // cantidad descontada
	private $precio   = 0;  // precio del descuento
	private $importe  = 0;  // importe del descuento
	private $cantidad_descontada_anterior = 0;  // suma de cantidad descontado en otras estimaciones
	private $importe_descontado_anterior  = 0;  // suma de importe descontado en otras estimaciones
	private $creado;

	private $conn;

	public function __construct( EstimacionSubcontrato $estimacion, Material $material ) {
		$this->estimacion = $estimacion;
		$this->material   = $material;
		$this->conn 	  = $estimacion->obra->getConn();

		$this->init();
	}

	public static function getInstance( EstimacionSubcontrato $estimacion, Material $material=null ) {

		$tsql = "SELECT
					  [id_material]
				FROM
					[SubcontratosEstimaciones].[descuento]
				WHERE
					[id_transaccion] = ?
						AND
					[id_material] = ISNULL(?, [id_material])";

		$params = array(
			array( $estimacion->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $material instanceof Material ? $material->getId() : null, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		);

		$data = $estimacion->obra->getConn()->executeQuery( $tsql, $params );
    	
    	$descuentos = array();

    	if ( $material instanceof Material ) {
			$descuentos = new self( $estimacion, $material );			
    	} else {
    		foreach ( $data as $material ) {
    			$descuentos[] = new self( $estimacion, new Material( $estimacion->obra, $material->id_material ) );
    		}
    	}

		return $descuentos;
	}

	// public static function getDescuentosPorEmpresa( Empresa $empresa ) {
	// 	$tsql = "SELECT
	// 				  [descuento].[id_transaccion],
	// 				  [descuento].[id_material]
	// 			FROM
	// 				[SubcontratosEstimaciones].[descuento]
	// 			INNER JOIN
	// 				[dbo].[transacciones]
	// 				ON
	// 					[descuento].[id_transaccion] = [transacciones].[id_transaccion]
	// 			WHERE
	// 				[transacciones].[id_obra] = ?
	// 					AND
	// 				[transacciones].[id_empresa] = ?
	// 			ORDER BY
	// 				[descuento].[id_material]";

	// 	$params = array(
	// 		array( $empresa->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	// 		array( $empresa->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	// 	);

	// 	$data = $empresa->obra->getConn()->executeQuery( $tsql, $params );
    	
 //    	$descuentos = array();

 //    	foreach ( $data as $descuento ) {
 //    			$descuentos[] = new self(
 //    				new EstimacionSubcontrato( $empresa->obra, $descuento->id_transaccion ), 
 //    				new Material( $estimacion->obra, $descuento->id_material )
 //    			);
 //    	}

	// 	return $descuentos;
	// }

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
					[id_material] = ?";

		$params = array(
	        array( $this->estimacion->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->material->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $row = $this->conn->executeQuery( $tsql, $params );
	    
	    if ( count( $row ) > 0 ) {

			$this->id	  	  = $row[0]->id_descuento;
			$this->cantidad	  = $row[0]->cantidad;
			$this->precio	  = $row[0]->precio;
			$this->importe	  = $row[0]->importe;
			$this->creado	  = $row[0]->creado;

		}

		$tsql = "SELECT
					  SUM([descuento].[importe]) AS [importe_descontado]
					, SUM([descuento].[cantidad]) AS [cantidad_descontada]
				FROM
					[SubcontratosEstimaciones].[descuento]
				INNER JOIN
					[dbo].[transacciones]
					ON
						[descuento].[id_transaccion] = [transacciones].[id_transaccion]
				WHERE
					[transacciones].[id_obra] = ?
						AND
					[transacciones].[id_antecedente] = ?
						AND
					[transacciones].[id_empresa] = ?
						AND
				    [transacciones].[numero_folio] < ?
						AND
				    [descuento].[id_material] = ?
				    	AND
    				[transacciones].[estado] > 0";

		$params = array(
	        array( $this->estimacion->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->estimacion->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->estimacion->empresa->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->estimacion->getNumeroFolio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->material->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $row = $this->conn->executeQuery( $tsql, $params );
	    $this->cantidad_descontada_anterior	  = $row[0]->cantidad_descontada;
		$this->importe_descontado_anterior	  = $row[0]->importe_descontado;
	}

	public function save() {

		if ( ! $this->estimacion instanceof EstimacionSubcontrato ) {
			throw new Exception("No se ha especificado la estimación.", 1);
		}

		if ( ! $this->material instanceof Material ) {
			throw new Exception("No se ha especificado el material", 1);
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
					        , [id_material]
					        , [cantidad]
					        , [precio]
				        )
						VALUES
				        ( ?, ?, ?, ? );";

				$params = array(
			        array( $this->estimacion->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			        array( $this->material->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			        array( $this->cantidad, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
			        array( $this->precio, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
			    );

			    $id_descuento = null;
		    	$id_descuento = $this->conn->executeQueryGetId( $tsql, $params );

		    	$this->id 	   = $id_descuento;
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

	public static function getCantidadDescontadaAnterior( EstimacionSubcontrato $estimacion, Material $material ) {

		$tsql = "SELECT
					SUM([descuento].[cantidad]) AS [cantidad_descontada]
				FROM
					[SubcontratosEstimaciones].[descuento]
				INNER JOIN
					[dbo].[transacciones]
					ON
						[descuento].[id_transaccion] = [transacciones].[id_transaccion]
				WHERE
					[transacciones].[id_obra] = ?
						AND
					[transacciones].[id_antecedente] = ?
						AND
					[transacciones].[id_empresa] = ?
						AND
				    [transacciones].[numero_folio] < ?
						AND
				    [descuento].[id_material] = ?
				    	AND
    				[transacciones].[estado] > 0";

    	$params = array(
	        array( $estimacion->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $estimacion->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $estimacion->empresa->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $estimacion->getNumeroFolio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $material->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $row = $estimacion->getConn()->executeQuery( $tsql, $params );

	    if ( count( $row ) > 0 )
	    	return $row[0]->cantidad_descontada;
	    else
	    	return 0;
	}

	public static function getImporteDescontadoAnterior( EstimacionSubcontrato $estimacion, Material $material ) {
		
		$tsql = "SELECT
					  SUM([descuento].[importe]) AS [importe_descontado]
					, SUM([descuento].[cantidad]) AS [cantidad_descontada]
				FROM
					[SubcontratosEstimaciones].[descuento]
				INNER JOIN
					[dbo].[transacciones]
					ON
						[descuento].[id_transaccion] = [transacciones].[id_transaccion]
				WHERE
					[transacciones].[id_obra] = ?
						AND
					[transacciones].[id_antecedente] = ?
						AND
					[transacciones].[id_empresa] = ?
						AND
				    [transacciones].[numero_folio] < ?
						AND
				    [descuento].[id_material] = ?
				    	AND
    				[transacciones].[estado] > 0";

    	$params = array(
	        array( $estimacion->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $estimacion->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $estimacion->empresa->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $estimacion->getNumeroFolio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $material->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $row = $estimacion->getConn()->executeQuery( $tsql, $params );

	    if ( count( $row ) > 0 )
	    	return $row[0]->importe_descontado;
	    else
	    	return 0;
	}

	public function __toString() {
		$data =  "id: {$this->id}, ";
		$data .= "material: {$this->material->getDescripcion()}, ";
		$data .= "cantidad: {$this->cantidad}, ";
		$data .= "precio: {$this->precio}, ";
		$data .= "importe: {$this->importe}, ";
		$data .= "creado: {$this->creado}";

		return $data;
	}
}
?>