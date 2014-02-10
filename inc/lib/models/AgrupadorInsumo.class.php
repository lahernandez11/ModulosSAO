<?php
class AgrupadorInsumo {
	
	const TIPO_NATURALEZA = 1;
	const TIPO_FAMILIA = 2;
	const TIPO_GENERICO = 3;
	const FIELD_NATURALEZA = 'id_agrupador_naturaleza';
	const FIELD_FAMILIA = 'id_agrupador_familia';
	const FIELD_GENERICO = 'id_agrupador_insumo_generico';

	private $id_agrupador = null;
	private $conn = null;

	public function __construct( SAODBConn $conn, $id_agrupador ) {
		$this->conn = $conn;
		$this->id_agrupador = $id_agrupador;
	}

	public function getTipoAgrupador() {

		$tsql = "SELECT
				    [agrupador].[id_tipo_agrupador]
				FROM
					[Agrupacion].[agrupador]
				WHERE
					[id_agrupador] = ?";

		$params = array(
			array( $this->id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		);

		$data = $this->conn->executeSP($tsql, $params);

		return $data[0]->id_tipo_agrupador;
	}

	public function getIDAgrupador() {
		return $this->id_agrupador;
	}

	public static function getAgrupadoresInsumo( SAODBConn $conn, 
		$descripcion = null, $tipo_agrupador = null ) {

		$tsql = "SELECT
				    [agrupador].[id_agrupador]
				  , CONCAT([agrupador].[codigo], ' ', [agrupador].[agrupador]) AS [agrupador]
				FROM
					[Agrupacion].[agrupador]
				WHERE
					[id_tipo_agrupador] = ISNULL(?, [id_tipo_agrupador])
						AND
				    CONCAT([agrupador].[codigo], ' ', [agrupador].[agrupador])
				    LIKE '%' + ISNULL(?, CONCAT([agrupador].[codigo], ' ', [agrupador].[agrupador])) +'%'
				ORDER BY
					[agrupador]";

		$params = array(
			array( $tipo_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('140') )
		);

		$data = $conn->executeSP($tsql, $params);

		return $data;
	}
}
?>