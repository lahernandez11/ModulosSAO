<?php
class Moneda {
	const MONEDA_LOCAL = 1;

	private $id;
	private $nombre;
	private $tipo = self::MONEDA_LOCAL;
	private $abreviatura;
	private $conn;

	public function __construct( Obra $obra, $id_moneda ) {

		if ( ! is_int( $id_moneda ) ) {
			throw new Exception("El identificador de moneda no es valido");
		}

		$this->id   = $id_moneda;
		$this->conn = $obra->getConn();
		$this->init();
	}

	private function init() {

		$tsql = "SELECT
				    [nombre]
				  , [tipo]
				  , [abreviatura]
				FROM
					[dbo].[monedas]
				WHERE
					[id_moneda] = ?";

		$data = $this->conn->executeQuery( $tsql, array( $this->getId() ) );

		$this->nombre 	   = $data[0]->nombre;
		$this->tipo   	   = $data[0]->tipo;
		$this->abreviatura = $data[0]->abreviatura;
	}

	public function getId() {
		return $this->id;
	}

	public function getNombre() {
		return $this->nombre;
	}

	public function getTipo() {
		return $this->tipo;
	}

	public function getAbreviatura() {
		return $this->abreviatura;
	}

	/**
	* Obtiene el ultimo tipo de cambio de la moneda
	* si no se especifica el argumento fecha.
	*/
	public function getTipoCambio( $fecha=null ) {

		if ( $this->getTipo() !== self::MONEDA_LOCAL ) {
			
			$tsql = "SELECT TOP 1
						[cambio] AS [tipo_cambio]
					FROM
						[dbo].[cambios]
					WHERE
						[id_moneda] = ?
							AND
						[fecha] = ISNULL(?, [fecha])
					ORDER BY
						[fecha] DESC";

			$params = array(
				$this->getId(),
				array($fecha, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATETIME)
			);

			$data = $this->conn->executeQuery( $tsql, $params );

			$tipo_cambio = $data[0]->tipo_cambio;
		} else {
			$tipo_cambio = self::MONEDA_LOCAL;
		}

		return $tipo_cambio;
	}

	public static function getMonedas( Obra $obra ) {

		$tsql = "SELECT
				    [id_moneda]
				  , [nombre]
				  , [tipo]
				  , [abreviatura]
				FROM
					[monedas]
				ORDER BY
					[tipo] DESC";

		$data = $obra->getConn->executeQuery( $tsql );

		return $data;
	}

	public function __toString() {
		return $this->getNombre();
	}
}
?>