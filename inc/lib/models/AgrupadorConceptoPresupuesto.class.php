<?php
require_once 'db/SAODBConn.class.php';

class AgrupadorConceptoPresupuesto {

	const C_TIPO_CONTRATO 	= "contrato";
	const C_TIPO_ETAPA    	= "etapa";
	const C_TIPO_COSTO        = "costo";
	const C_TIPO_ESPECIALIDAD = "especialidad";
	const C_TIPO_PARTIDA      = "partida";
	const C_TIPO_SUBPARTIDA   = "subpartida";
	const C_TIPO_CONCEPTO     = "concepto";
	const C_TIPO_FRENTE     	= "frente";
	const C_TIPO_CONTRATISTA	= "contratista";

	private static function getAgrupadores( Obra $obra, $field, $descripcion, $id_agrupador=null ) {
		
		$tsql = "SELECT
					  [id_agrupador_{$field}] AS [id_agrupador]
					, [agrupador_{$field}] AS [agrupador]
				FROM
					[PresupuestoObra].[agrupador_{$field}]
				WHERE
					[id_obra] = ?
						AND
			        [id_agrupador_{$field}] = ISNULL(?, [id_agrupador_{$field}])
						AND
			        [agrupador_{$field}] LIKE '%' + ? + '%';";

		$params = array(
	        array( $obra->getId(), SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT ),
	        array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(100) ),
	    );

		$data = $obra->getConn()->executeSP( $tsql, $params );

		return $data;
	}

	public static function getAgrupadoresContrato( Obra $obra, $descripcion, $id_agrupador=null ) {
		return self::getAgrupadores( $obra, self::C_TIPO_CONTRATO, $descripcion, $id_agrupador);
	}

	public static function getAgrupadoresEtapa( Obra $obra, $descripcion, $id_agrupador=null ) {
		return self::getAgrupadores( $obra, self::C_TIPO_ETAPA, $descripcion, $id_agrupador);
	}

	public static function getAgrupadoresCosto( Obra $obra, $descripcion, $id_agrupador=null ) {
		return self::getAgrupadores( $obra, self::C_TIPO_COSTO, $descripcion, $id_agrupador);
	}

	public static function getAgrupadoresEspecialidad( Obra $obra, $descripcion, $id_agrupador=null ) {
		return self::getAgrupadores( $obra, self::C_TIPO_ESPECIALIDAD, $descripcion, $id_agrupador);
	}

	public static function getAgrupadoresPartida( Obra $obra, $descripcion, $id_agrupador=null ) {
		return self::getAgrupadores( $obra, self::C_TIPO_PARTIDA, $descripcion, $id_agrupador);
	}

	public static function getAgrupadoresSubpartida( Obra $obra, $descripcion, $id_agrupador=null ) {
		return self::getAgrupadores( $obra, self::C_TIPO_SUBPARTIDA, $descripcion, $id_agrupador);
	}

	public static function getAgrupadoresConcepto( Obra $obra, $descripcion, $id_agrupador=null ) {
		return self::getAgrupadores( $obra, self::C_TIPO_CONCEPTO, $descripcion, $id_agrupador);
	}

	public static function getAgrupadoresFrente( Obra $obra, $descripcion, $id_agrupador=null ) {
		return self::getAgrupadores( $obra, self::C_TIPO_FRENTE, $descripcion, $id_agrupador);
	}

	public static function getAgrupadoresContratista( Obra $obra, $descripcion, $id_agrupador=null ) {
		return self::getAgrupadores( $obra, self::C_TIPO_CONTRATISTA, $descripcion, $id_agrupador);
	}

	private static function addAgrupador( Obra $obra, $field, $descripcion ) {

		$tsql = "INSERT INTO [PresupuestoObra].[agrupador_{$field}]
		        (
			          [id_obra]
			        , [agrupador_{$field}]
		        )
				VALUES
		        (
			          ?
			        ,
			          ?
		        );";

	    $params = array(
	        array( $obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(140) ),
	    );

	    $id_agrupador = null;
	    $id_agrupador = $obra->getConn()->executeQueryGetId( $tsql, $params );

	    return $id_agrupador;
	}

	public static function addAgrupadorContrato( Obra $obra, $descripcion ) {
	    return self::addAgrupador($obra, self::C_TIPO_CONTRATO, $descripcion);
	}
	public static function addAgrupadorEtapa( Obra $obra, $descripcion ) {
	    return self::addAgrupador($obra, self::C_TIPO_ETAPA, $descripcion);
	}
	public static function addAgrupadorCosto( Obra $obra, $descripcion ) {
	    return self::addAgrupador($obra, self::C_TIPO_COSTO, $descripcion);
	}
	public static function addAgrupadorEspecialidad( Obra $obra, $descripcion ) {
	    return self::addAgrupador($obra, self::C_TIPO_ESPECIALIDAD, $descripcion);
	}
	public static function addAgrupadorPartida( Obra $obra, $descripcion ) {
	    return self::addAgrupador($obra, self::C_TIPO_PARTIDA, $descripcion);
	}
	public static function addAgrupadorSubpartida( Obra $obra, $descripcion ) {
	    return self::addAgrupador($obra, self::C_TIPO_SUBPARTIDA, $descripcion);
	}
	public static function addAgrupadorConcepto( Obra $obra, $descripcion ) {
	    return self::addAgrupador($obra, self::C_TIPO_CONCEPTO, $descripcion);
	}
	public static function addAgrupadorFrente( Obra $obra, $descripcion ) {
	    return self::addAgrupador($obra, self::C_TIPO_FRENTE, $descripcion);
	}
	public static function addAgrupadorContratista( Obra $obra, $descripcion ) {
	    return self::addAgrupador($obra, self::C_TIPO_CONTRATISTA, $descripcion);
	}

}