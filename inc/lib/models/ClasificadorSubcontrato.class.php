<?php
class ClasificadorSubcontrato {
	
	private $id_clasificador = null;
	private $conn = null;

	public function __construct( Obra $obra, $id_clasificador ) {
		$this->conn = $$obra->getConn();
		$this->id_clasificador = $id_clasificador;
	}

	public function getIDClasificador() {
		return $this->id_clasificador;
	}

	public static function getClasificadores( Obra $obra, $descripcion=null ) {

		$tsql = "SELECT
					[id_clasificador]
				  , [clasificador]
				FROM
					[Subcontratos].[clasificador]
				WHERE
					[clasificador] LIKE '%' + ISNULL(?, [clasificador]) +'%'";

		$params = array(
			array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('50') )
		);

		$data = $obra->getConn()->executeQuery( $tsql, $params );

		return $data;
	}
}
?>