<?php
class PrecioVenta {

	public static function getPreciosVenta( SAODBConn $conn, $IDObra ) {

		$tsql = "{call [Precios].[uspPreciosVenta]( ? )}";

		$params = array(
	        array( $IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $preciosConceptos = $conn->executeSP($tsql, $params);

	    return $preciosConceptos;
	}

	public static function setPreciosVenta( SAODBConn $conn, $IDObra, Array $conceptos ) {

		$conceptosError = array();

		$tsql = "{call [Precios].[uspAsignaPrecioVenta]( ?, ?, ?, ? )}";

		foreach ( $conceptos as $concepto ) {
			
			try {
				// Limpia los precios
				$concepto['precioProduccion'] = str_replace(',', '', $concepto['precioProduccion']);
				$concepto['precioEstimacion'] = str_replace(',', '', $concepto['precioEstimacion']);

				$params = array(
					array( $IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
					array( $concepto['IDConcepto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
					array( $concepto['precioProduccion'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19,4) ),
					array( $concepto['precioEstimacion'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19,4) )
				);

				$conn->executeSP($tsql, $params);
			} catch( Exception $e ) {

				$conceptosError[] = array(
					'IDConcepto' => $concepto['IDConcepto'],
					'message' => $e->getMessage()
				);
			}
		}

		return $conceptosError;
	}
}
?>