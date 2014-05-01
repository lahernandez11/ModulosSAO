<?php
abstract class Util {
	
	public static function formatoNumeroFolio( $numeroFolio ) {

		/*
		if( ! is_int( $numeroFolio ) )
			throw new Exception("El numero de folio no esta es un numero");
		*/
		if ( is_null($numeroFolio) )
			return "";
		else
			return "#".str_repeat( '0', 6 - strlen($numeroFolio) ) . $numeroFolio;
	}

	public static function formatoMoneda( $cantidad ) {

		return number_format($cantidad, 2);
	}

	public static function formatoNumerico( $numero ) {
		
		return number_format($numero, 2);
	}

	public static function limpiaFormatoNumerico( $cantidad ) {
		return str_replace(',', '', $cantidad);
	}

	public static function formatoFecha( $fecha ) {
		if ( $fecha === null )
			return "";
		else
			return date( "d-m-Y",  strtotime( $fecha ) );
	}

	public static function esImporte( $importe ) {

		return preg_match('/^-?\d+(\.\d+)?$/', $importe );
	}

	public static function limpiaImporte( $importe ) {

		return str_replace(',', '', $importe);
	}

	public static function aPorcentaje( $numero ) {
		return round(($numero * 100), 2);
	}

	public static function formatoPorcentaje( $numero ) {
		return round( ($numero * 100), 2 );
	}

	public static function esFecha( $fecha ) {
		
		if ( preg_match( "/^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/", $fecha ) === 1) {
			return true;
		}
		else {
			return false;
		}
	}

	public static function formatoNumericoTotales( array $totales ) {

		$totales_formato = array();

		foreach ( $totales as $key => $total ) {
			$totales_formato[$key] = self::formatoNumerico($total);
		}

		return $totales_formato;
	}
}
?>