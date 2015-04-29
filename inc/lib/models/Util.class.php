<?php

abstract class Util {

    /**
     * @param $numeroFolio
     * @return string
     */
    public static function formatoNumeroFolio($numeroFolio)
    {
		/*
		if( ! is_int( $numeroFolio ) )
			throw new Exception("El numero de folio no esta es un numero");
		*/
		if (is_null($numeroFolio))
        {
            return "";
        }

        return "#".str_repeat('0', 6 - strlen($numeroFolio)) . $numeroFolio;
	}

    /**
     * @param $cantidad
     * @return string
     */
    public static function formatoMoneda($cantidad)
    {
		return number_format($cantidad, 2);
	}

    /**
     * @param $numero
     * @return string
     */
    public static function formatoNumerico( $numero )
    {
		if (empty($numero))
        {
			if (is_string($numero))
            {
				return "";
			}

			$numero = 0;
		}

		return number_format(floor($numero * 100) / 100, 2);
	}

    /**
     * @param $cantidad
     * @return mixed
     */
    public static function limpiaFormatoNumerico($cantidad)
    {
		return str_replace(',', '', $cantidad);
	}

    /**
     * @param $fecha
     * @return bool|string
     */
    public static function formatoFecha($fecha)
    {
		if ($fecha === null)
        {
            return "";
        }

        return date("d-m-Y",  strtotime($fecha));
	}

    /**
     * @param $importe
     * @return int
     */
    public static function esImporte($importe)
    {
		return preg_match('/^-?\d+(\.\d+)?$/', $importe );
	}

    /**
     * @param $importe
     * @return mixed
     */
    public static function limpiaImporte( $importe )
    {
		return str_replace(',', '', $importe);
	}

    /**
     * @param $numero
     * @return float
     */
    public static function aPorcentaje($numero)
    {
		return round(($numero * 100), 2);
	}

    /**
     * @param $numero
     * @return float
     */
    public static function formatoPorcentaje($numero)
    {
		return round(($numero * 100), 4);
	}

    /**
     * @param $fecha
     * @return bool
     */
    public static function esFecha($fecha)
    {
		if ( preg_match( "/^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/", $fecha ) === 1)
        {
			return true;
		}
		return false;
	}

    /**
     * @param array $totales
     * @return array
     */
    public static function formatoNumericoTotales(array $totales)
    {
		$totales_formato = array();

		foreach ($totales as $key => $total)
        {
			$totales_formato[$key] = self::formatoNumerico($total);
		}

		return $totales_formato;
	}
}
