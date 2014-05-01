<?php
// require_once '../controllers/SetPath.php';
require_once 'models/EstimacionSubcontrato.class.php';
require_once 'models/RetencionTipo.class.php';

class EstimacionRetencion {
	
	private $id;
	public  $tipo_retencion;
	private $importe 	 = 0;
	private $concepto = "";
	private $creado;
	private $conn;

	public  $estimacion;


	public function __construct( EstimacionSubcontrato $estimacion, RetencionTipo $tipo_retencion, $importe, $concepto, $id=null ) {
		$this->estimacion 	  = $estimacion;
		$this->tipo_retencion = $tipo_retencion;
		$this->importe 		  = $importe;
		$this->concepto 	  = $concepto;
		$this->conn 		  = $estimacion->obra->getConn();

		if ( isset($id) ) {
			$this->id = $id;
		}
	}

	public static function getInstance( EstimacionSubcontrato $estimacion, $id=null ) {

		$tsql = "SELECT
					  [id_retencion]
					, [id_transaccion]
					, [id_tipo_retencion]
					, [importe]
					, [concepto]
				FROM
					[SubcontratosEstimaciones].[retencion]
				WHERE
					[id_transaccion] = ?
						AND
				    [id_retencion] = ISNULL(?, [id_retencion])";

		$params = array(
			array( $estimacion->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $id, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		);

		$data = $estimacion->obra->getConn()->executeQuery( $tsql, $params );
    	
    	$retenciones = array();

    	if ( is_null( $id ) ) {
			foreach ( $data as $retencion ) {
				$retenciones[] = new self(
					$estimacion, 
					RetencionTipo::getInstance( $estimacion->obra->getConn(), $retencion->id_tipo_retencion ), 
					$retencion->importe, 
					$retencion->concepto,
					$retencion->id_retencion
				);
			}
    	} else {
    		if ( count( $data ) > 0 ) {
	    		$retenciones = new self(
						$estimacion, 
						RetencionTipo::getInstance( $estimacion->obra->getConn(), $data[0]->id_tipo_retencion ), 
						$data[0]->importe, 
						$data[0]->concepto,
						$data[0]->id_retencion
				);
	    	}
    	}

		return $retenciones;
	}

	public function save() {

		if ( ! $this->estimacion instanceof EstimacionSubcontrato ) {
			throw new Exception("No se ha especificado la estimación.", 1);
		}

		if ( $this->importe == 0 ) {
			throw new Exception("El importe debe ser mayor a 0.", 1);
		}

		// ingresa registro
		$tsql = "INSERT INTO [SubcontratosEstimaciones].[retencion]
		        (
			          [id_transaccion]
			        , [id_tipo_retencion]
			        , [importe]
			        , [concepto]
		        )
				VALUES
		        ( ?, ?, ?, ? );";

		$params = array(
	        array( $this->estimacion->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->tipo_retencion->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
	        array( $this->concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') )
	    );

	    $id_retencion = null;
    	$id_retencion = $this->conn->executeQueryGetId( $tsql, $params );

    	$this->id = $id_retencion;
	}

	public function delete() {

		if ( $this->estimacion->estaAprobada() ) {
			throw new Exception("La estimacion se encuentra aprobada.", 1);
		}

		$tsql = "DELETE
					[SubcontratosEstimaciones].[retencion]
				WHERE
					[id_transaccion] = ?
						AND
				    [id_retencion] = ?";

		$params = array(
	        array( $this->estimacion->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

    	$this->conn->executeQuery( $tsql, $params );
	}

	public function getImporte() {
		return $this->importe;
	}

	public function getConcepto() {
		return $this->concepto;
	}

	public function getId() {
		return $this->id;
	}

	public function setImporte( $importe ) {
		$this->importe = $importe;
	}

	public function setConcepto( $concepto ) {
		$this->concepto = $concepto;
	}

	public function __toString() {
		$data  = "id: {$this->id};";
		$data .= "tipo_retencion: {$this->tipo_retencion->getDDescripcion()};";
		$data .= "importe: {$this->importe};";
		$data .= "concepto: {$this->concepto};";

		return $data;
	}
}

// $conn = SAODBConnFactory::getInstance( "SAO1814" );
// $obra = new Obra( $conn, 41 );
// $estimacion = new EstimacionSubcontrato( $obra, 1885230);

// // $ret = new EstimacionRetencion( $estimacion, 1, 100, "prueba");
// $ret = EstimacionRetencion::getInstance( $estimacion);

// echo $ret[0];

// $ret->save();

// echo $ret;
// $ret->setImporte(500);
// $ret->setConcepto("la diez");
// $ret->delete();
?>