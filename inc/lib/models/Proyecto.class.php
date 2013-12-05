<?php
class Proyecto {
	
	protected $IDProyecto = null;
	protected $SQLConn 	= null;
	protected $IDObraCDC  = null;

	public function __construct( $IDProyecto, ModulosSAOConn $conn ) {

		if ( ! is_int($IDProyecto) ) {
			throw new Exception("El identificador de proyecto no es válido.");
		}

		$this->IDProyecto = $IDProyecto;
		$this->SQLConn 	  = $conn;

		$this->getIDObraProyecto();
	}

	private function getIDObraProyecto() {

		$tsql = 
		"SELECT
			[idProyectoUnificado]
		FROM
			[Proyectos].[vwListaProyectosUnificados]
		WHERE
			[idProyecto] = ?
				AND
			[idTipoSistemaOrigen] = 1
				AND
			[idTipoBaseDatos] = 1";

		$rsObra = $this->SQLConn->executeQuery( $tsql, array($this->IDProyecto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT) );

		$IDObra = $rsObra[0]->idProyectoUnificado;
		
		$this->IDObraCDC = $IDObra;
	}

	public function getIDObra() {

		return $this->IDObraCDC;
	}
}
?>