<?php
require_once 'db/ModulosSAOConn.class.php';

abstract class Obra {
	
	public static function getIDObraProyecto( $IDProyecto, $IDTipoBaseDatos = 1 ) {

		$conn = new ModulosSAOConn();

		$tsql = "SELECT
					[idProyectoUnificado]
				FROM
					[Proyectos].[vwListaProyectosUnificados]
				WHERE
					[idProyecto] = ?
						AND
					[idTipoSistemaOrigen] = 1
						AND
					[idTipoBaseDatos] = ?";

		$params = array(
			array($IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
			array($IDTipoBaseDatos, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
		);

		$rsObra = $conn->executeQuery( $tsql, $params );

		$IDObra = $rsObra[0]->idProyectoUnificado;
		
		return $IDObra;
	}

	public static function getFoliosTransaccion( $IDObra, $tipoTransaccion, SAODBConn $conn) {

		if ( ! is_int($IDObra) )
			throw new Exception("El identificador de la obra no es correcto.", 1);

		if ( ! is_int($tipoTransaccion) )
			throw new Exception("El tipo de transaccion no es correcto.", 1);

		$tsql = "";

		switch ( $tipoTransaccion ) {

			case 98:
				$tsql = '{call [AvanceObra].[uspListaFolios]( ? )}';
				break;
		}

		if ( strlen($tsql) === 0 )
			throw new Exception("El tipo de transaccion no esta definido.", 1);
		
		$params = array(
	        array( $IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $rsListaTran = $conn->executeSP($tsql, $params);

	    if ( sizeof($rsListaTran) > 0 ) {
			return $rsListaTran;
		} else {
			throw new Exception("No se encontraron transacciones registradas.", 1);
		}
	}
}
?>