<?php
require_once 'db/SAODBConn.class.php';
require_once 'db/ModulosSAOConn.class.php';

class Obra {
	
	private $id;
	private $nombre;
	private $tipo_obra;
	private $fecha_inicial;
	private $fecha_final;
	private $id_moneda;
	private $conn;

	public function __construct( SAODBConn $conn, $id_obra ) {

		if ( (int) $id_obra <= 0 ) {
			throw new Exception("El identificador de obra es incorrecto.", 1);
		}

		$this->conn = $conn;
		$this->id = (int) $id_obra;
		$this->init();
	}

	private function init() {

		$tsql = "SELECT
					  [id_obra]
					, IIF( LEN([nombre_publico]) = 0, [nombre], [nombre_publico]) as [nombre]
					, [nombre_publico]
					, [tipo_obra]
					, [fecha_inicial]
					, [fecha_final]
					, [id_moneda]
				FROM
				    [obras]
				WHERE
					[obras].[id_obra] = ?";

		$params = array( $this->id );

		$data = $this->conn->executeQuery( $tsql, $params );

		if ( count( $data ) < 1 ) {
			throw new Exception("No se encontró la obra.");
		} else {
			$this->nombre 		 = $data[0]->nombre;
			$this->tipo_obra 	 = $data[0]->tipo_obra;
			$this->fecha_inicial = $data[0]->fecha_inicial;
			$this->fecha_final 	 = $data[0]->fecha_final;
			$this->id_moneda 	 = $data[0]->id_moneda;
		}
	}

	public function getId() {
		return $this->id;
	}

	public function getConn() {
		return $this->conn;
	}

	public function getDBName() {
		return $this->conn->dbConf->getDBName();
	}

	public function getSourceId() {
		return $this->conn->dbConf->getSourceId();
	}

	public function getSourceName() {
		return $this->conn->dbConf->getSourceName();
	}

	public function getNombre() {
		return $this->nombre;
	}

	public function getTipoObra() {
		return $this->tipo_obra;
	}

	public function getFechaInicial() {
		return $this->fecha_inicial;
	}

	public function getFechaFinal() {
		return $this->fecha_final;
	}

	public function getIdMoneda() {
		return $this->id_moneda;
	}

	public function getProyecto() {

		$conn = ModulosSAOConn::getInstance();

		$tsql = "SELECT
					[UnificacionProyectoObra].[IDProyecto]
				FROM
					[dbo].[UnificacionProyectoObra]
				INNER JOIN
					[BaseDatosObra]
					ON
						[UnificacionProyectoObra].[IDBaseDatos] = [BaseDatosObra].[IDBaseDatos]
				WHERE
					[BaseDatosObra].[BaseDatos] = ?
						AND
				    [UnificacionProyectoObra].[id_obra] = ?";

		$params = array( $this->getDBName(), $this->getId() );

		$data = $conn->executeQuery( $tsql, $params );

		if ( count( $data ) < 1 ) {
			throw new Exception( "La obra aun no esta relacionada con un proyecto." );
		} else {
			return new Proyecto( $data[0]->IDProyecto );
		}
	}

	public function __toString() {
		$obra = "id_obra:{$this->id};\n"
			  . "nombre:{$this->nombre};\n"
			  . "tipo_obra:{$this->tipo_obra}\n"
			  . "source_id:{$this->getSourceId()}\n"
			  . "source_name:{$this->getSourceName()}";

		return $obra;
	}
}

class ObraNoAsociadaProyectoException extends Exception {}
?>