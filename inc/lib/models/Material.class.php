<?php
class Material {

	private $conn = null;
	private $id_material = null;

	public function __construct(SAODBConn $conn, $id_material) {
		$this->conn = $conn;
		$this->id_material = $id_material;
	}

	private function existeRegistroAgrupacion($id_obra) {

		$tsql = "SELECT 1
				 FROM [Agrupacion].[agrupacion_insumos]
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_material] = ?";

	    $params = array(
	        array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id_material, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $res = $this->conn->executeQuery($tsql, $params);

	    if (count($res) > 0)
	    	return true;
	    else
	    	return false;
	}

	private function creaRegistroAgrupacion($id_obra) {

		$tsql = "INSERT INTO [Agrupacion].[agrupacion_insumos]
				(
					  [id_obra]
					, [id_material]
				)
				VALUES
				( ?, ? )";

	    $params = array(
	        array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id_material, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery($tsql, $params);
	}

	public function setAgrupador($id_obra, AgrupadorInsumo $agrupador) {

		switch ($agrupador->getTipoAgrupador()) {
			case AgrupadorInsumo::TIPO_NATURALEZA:
				$this->setAgrupadorNaturaleza($id_obra, $agrupador);
				break;

			case AgrupadorInsumo::TIPO_FAMILIA:
				$this->setAgrupadorFamilia($id_obra, $agrupador);
				break;

			case AgrupadorInsumo::TIPO_GENERICO:
				$this->setAgrupadorGenerico($id_obra, $agrupador);
				break;
		}
	}

	public function setAgrupadorNaturaleza($id_obra, AgrupadorInsumo $agrupador) {

		if (! $this->existeRegistroAgrupacion($id_obra))
			$this->creaRegistroAgrupacion($id_obra);

		$tsql = "UPDATE [Agrupacion].[agrupacion_insumos]
				 SET
				 	[id_agrupador_naturaleza] = ?
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_material] = ?";

	    $params = array(
	        array( $agrupador->getIDAgrupador(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id_material, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery($tsql, $params);
	}
	
	public function setAgrupadorFamilia($id_obra, AgrupadorInsumo $agrupador) {
		
		if (! $this->existeRegistroAgrupacion($id_obra))
			$this->creaRegistroAgrupacion($id_obra);

		$tsql = "UPDATE [Agrupacion].[agrupacion_insumos]
				 SET
				 	[id_agrupador_familia] = ?
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_material] = ?";

	    $params = array(
	        array( $agrupador->getIDAgrupador(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id_material, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery($tsql, $params);
	}
	
	public function setAgrupadorGenerico($id_obra, AgrupadorInsumo $agrupador) {
		
		if (! $this->existeRegistroAgrupacion($id_obra))
			$this->creaRegistroAgrupacion($id_obra);

		$tsql = "UPDATE [Agrupacion].[agrupacion_insumos]
				 SET
				 	[id_agrupador_insumo_generico] = ?
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_material] = ?";

	    $params = array(
	        array( $agrupador->getIDAgrupador(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id_material, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery($tsql, $params);
	}

	public static function getMateriales() {

	}

	public static function getMaterialesObra( $id_obra, SAODBConn $conn ) {

		$tsql = "SELECT
				    [id_obra]
				  , [id_familia]
				  , [familia]
				  , [id_material]
				  , [material]
				  , [codigo_externo]
				  , [unidad]
				  , [EstaPresupuestado]
				  , [id_agrupador_naturaleza]
				  , [agrupador_naturaleza]
				  , [id_agrupador_familia]
				  , [agrupador_familia]
				  , [id_agrupador_insumo_generico]
				  , [agrupador_insumo_generico]
				FROM
					[Agrupacion].[vwListaInsumos]
				WHERE
					[id_obra] = ?
				ORDER BY
					[familia],
					[material]";

		$params = array(
			array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		);

		$data = $conn->executeSP($tsql, $params);

		return $data;
	}
}
?>