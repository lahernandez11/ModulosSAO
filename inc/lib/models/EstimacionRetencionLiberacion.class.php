<?php

class EstimacionRetencionLiberacion {
	
	private $id;
	private $importe  = 0;
	private $concepto = "";
	private $conn;

	public $estimacion;

	public function __construct( EstimacionSubcontrato $estimacion, $importe=0, $concepto="", $id=null ) {
		$this->estimacion = $estimacion;
		$this->importe    = $importe;
		$this->concepto   = $concepto;
		$this->conn 	  = $estimacion->obra->getConn();

		if ( ! is_null( $id ) && is_int( $id ) ) {
			$this->id = $id;
		}
	}

	public static function getInstance( EstimacionSubcontrato $estimacion, $id=null ) {

		$tsql = "SELECT
					  [id_liberacion],
					  [importe],
					  [concepto]
				FROM
					[SubcontratosEstimaciones].[retencion_liberacion]
				WHERE
					[id_transaccion] = ?
						AND
				    [id_liberacion] = ISNULL(?, [id_liberacion]);";

		$params = array(
			array( $estimacion->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $id, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		);

		$data = $estimacion->obra->getConn()->executeQuery( $tsql, $params );
    	
    	$liberaciones = array();

    	if ( is_null( $id ) ) {
			foreach ( $data as $liberacion ) {
				$liberaciones[] = new self( $estimacion, $liberacion->importe, $liberacion->concepto, $liberacion->id_liberacion );
			}
    	} else {
    		
    		if ( count( $data ) > 0 ) {
	    		$liberaciones = new self( $estimacion, $data[0]->importe, $data[0]->concepto, $id );
	    	}
    	}

		return $liberaciones;
	}

	public function save( Usuario $usuario ) {

		if ( $this->estimacion->estaAprobada() ) {
			throw new Exception("La estimacion esta aprobada", 1);
		}

		if ( $this->importe == 0 ) {
			throw new Exception("El importe debe ser mayor a 0.", 1);
		}

		$tsql = "INSERT INTO [SubcontratosEstimaciones].[retencion_liberacion]
				(
					  [id_transaccion]
					, [importe]
					, [concepto]
					, [usuario]
				)
				VALUES ( ?, ?, ?, ? );";

		$params = array(
			array( $this->estimacion->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
	        array( $this->concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
	        array( $usuario->getUsername(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(20) )
		);

		$this->id = $this->conn->executeQueryGetId( $tsql, $params );
	}

	public function delete() {

		if ( $this->estimacion->estaAprobada() ) {
			throw new Exception("La estimacion se encuentra aprobada.", 1);
		}

		$tsql = "DELETE
					[SubcontratosEstimaciones].[retencion_liberacion]
				WHERE
					[id_transaccion] = ?
						AND
				    [id_liberacion] = ?";

		$params = array(
	        array( $this->estimacion->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

    	$this->conn->executeQuery( $tsql, $params );
	}

	public function getId() {
		return $this->id;
	}

	public function getImporte() {
		return $this->importe;
	}

	public function getConcepto() {
		return $this->concepto;
	}

	public function setImporte( $importe ) {
		$this->importe = $importe;
	}

	public function setConcepto( $concepto ) {
		$this->concepto = $concepto;
	}
}
?>