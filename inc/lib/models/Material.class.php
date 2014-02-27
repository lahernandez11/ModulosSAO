<?php
require_once 'models/Obra.class.php';

class Material {

	const TIPO_MATERIAL = 1;
	const TIPO_MANO_OBRA = 2;
	const TIPO_SERVICIOS = 3;
	const TIPO_HERRAMIENTA = 4;
	const TIPO_MAQUINARIA = 8;

	private $conn = null;
	private $id = null;

	public function __construct( SAODBConn $conn, $id_material ) {
		$this->conn = $conn;
		$this->id = $id_material;
	}

	private function existeRegistroAgrupacion( $id_obra ) {

		$tsql = "SELECT 1
				 FROM [Agrupacion].[agrupacion_insumos]
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_material] = ?";

	    $params = array(
	        array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $res = $this->conn->executeQuery( $tsql, $params );

	    if ( count( $res ) > 0 )
	    	return true;
	    else
	    	return false;
	}

	private function creaRegistroAgrupacion( $id_obra ) {

		$tsql = "INSERT INTO [Agrupacion].[agrupacion_insumos]
				(
					  [id_obra]
					, [id_material]
				)
				VALUES
				( ?, ? )";

	    $params = array(
	        array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery($tsql, $params);
	}

	public function setAgrupador( Obra $obra, AgrupadorInsumo $agrupador ) {

		if ( ! $this->existeRegistroAgrupacion( $obra->getId() ) )
			$this->creaRegistroAgrupacion( $obra->getId() );

		$field = '';

		switch ( $agrupador->getTipoAgrupador() ) {
			case AgrupadorInsumo::TIPO_NATURALEZA:
				$field = AgrupadorInsumo::FIELD_NATURALEZA;
				break;

			case AgrupadorInsumo::TIPO_FAMILIA:
				$field = AgrupadorInsumo::FIELD_FAMILIA;
				break;

			case AgrupadorInsumo::TIPO_GENERICO:
				$field = AgrupadorInsumo::FIELD_GENERICO;
				break;
		}

		$tsql = "UPDATE [Agrupacion].[agrupacion_insumos]
				 SET
				 	{$field} = ?
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_material] = ?";

	    $params = array(
	        array( $agrupador->getIDAgrupador(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery( $tsql, $params );
	}

	public static function getMaterialesObra( Obra $obra ) {

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
			array( $obra->getid(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		);

		$data = $obra->getConn()->executeSP( $tsql, $params );

		return $data;
	}

	public static function getMateriales( SAODBConn $conn, $descripcion = null, $tipo = null ) {

		switch ( $tipo ) {
			case self::TIPO_MATERIAL:
			case self::TIPO_MANO_OBRA:
			case self::TIPO_SERVICIOS:
			case self::TIPO_HERRAMIENTA:
			case self::TIPO_MAQUINARIA:
				break;
			
			default:
				$tipo = null;
				break;
		}

		$tsql = "{call [Insumos].[uspListaInsumos]( ?, ? )}";

	    $params = array(
	        array( $tipo, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('255') )
	    );

	    return $conn->executeSP($tsql, $params);
	}
}
?>