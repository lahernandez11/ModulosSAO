<?php

class RetencionTipo {
	
	private $id;
	private $descripcion;

	private $conn;

	public function __construct( SAODBConn $conn, $descripcion, $id=null ) {
		$this->descripcion = $descripcion;
		$this->conn = $conn;

		if ( isset( $id ) ) {
			$this->id = $id;
		}
	}

	public static function getInstance( SAODBConn $conn, $id=null ) {

		if ( isset( $id ) && ! is_int( $id ) ) {
			throw new Exception("El identificador del tipo de retencion es incorrecto", 1);
		}

		$tsql = "SELECT
					  [id_tipo_retencion]
					, [tipo_retencion]
				FROM
					[SubcontratosEstimaciones].[retencion_tipo]
				WHERE
					[id_tipo_retencion] = ISNULL(?, [id_tipo_retencion]);";

		$params = array(
			array( $id, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		);

		$data = $conn->executeQuery( $tsql, $params );

		$tipos = array();

    	if ( is_null( $id ) ) {
			foreach ( $data as $tipo ) {
				$tipos[] = new self( $conn, $tipo->tipo_retencion, $tipo->id_tipo_retencion );
			}
    	} else {
    		$tipos = new self( $conn, $data[0]->tipo_retencion, $data[0]->id_tipo_retencion );
    	}

		return $tipos;
	}

	public function save() {

	}

	public function delete() {

	}

	public function getDescripcion() {
		return $this->descripcion;
	}

	public function getId() {
		return $this->id;
	}

}
?>