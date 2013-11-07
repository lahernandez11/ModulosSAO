<?php
require_once 'db/SAODBConn.class.php';

class PresupuestoObra {

	/*const TIPO_TRANSACCION = 100000000;*/
	private $conn = null;
	private $id_obra = null;

	public function __construct( $id_obra, SAODBConn $conn ) {
		
		if ( ! is_int($id_obra) || ! $id_obra > 0 ) {
			throw new Exception("El identificador de obra no es correcto.");
		} else {
			$this->conn = $conn;
			$this->id_obra = $id_obra;
		}
	}

	public function getConceptos( $id_concepto ) {

		if ( $id_concepto == 0 )
			$id_concepto = null;

		$tsql = "{call [PresupuestoObra].[uspConceptosPresupuesto]( ?, ? )}";

	    $params = array(
	        array( $this->id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $conceptos = $this->conn->executeSP($tsql, $params);

	    return $conceptos;
	}

	public function getDatosConcepto( $id_concepto ) {
		
		$tsql = "{call [PresupuestoObra].[uspDatosConcepto](?, ?)}";

		$params = array(
	        array( $this->id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

		$data = $this->conn->executeSP($tsql, $params);

		return $data[0];
	}

	public function setAgrupadorPartida( $id_concepto, $id_agrupador ) {

		$tsql = "UPDATE [dbo].[conceptos]
				 SET
				 	[id_agrupador_partida] = ?
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_concepto] = ?";

	    $params = array(
	        array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery($tsql, $params);
	}

	public function setAgrupadorSubpartida( $id_concepto, $id_agrupador ) {
		
		$tsql = "UPDATE [dbo].[conceptos]
				 SET
				 	[id_agrupador_subpartida] = ?
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_concepto] = ?";

	    $params = array(
	        array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery($tsql, $params);
	}

	public function setAgrupadorActividad( $id_concepto, $id_agrupador ) {
		
		$tsql = "UPDATE [dbo].[conceptos]
				 SET
				 	[id_agrupador_actividad] = ?
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_concepto] = ?";

	    $params = array(
	        array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery($tsql, $params);
	}

	public static function getDescendantsOf( $IDObra, $IDConceptoRaiz, SAODBConn $conn ) {

		$tsql = "{call [SAO].[uspPresupuestoObra]( ?, ? )}";

		$params = array(
			array( $IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $IDConceptoRaiz, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		);

		$nodes = $conn->executeSP($tsql, $params);

		return $nodes;
	}

	public static function getAgrupadoresPartida(SAODBConn $conn, $id_obra, $descripcion, $id_agrupador = null ) {

		$tsql = "{call [PresupuestoObra].[uspAgrupadorPartida]( ?, ?, ? )}";

		$params = array(
			array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('300') )
		);

		$data = $conn->executeSP($tsql, $params);

		return $data;
	}

	public static function getAgrupadoresSubpartida(SAODBConn $conn, $id_obra, $descripcion, $id_agrupador = null ) {

		$tsql = "{call [PresupuestoObra].[uspAgrupadorSubpartida]( ?, ?, ? )}";

		$params = array(
			array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('300') )
		);

		$data = $conn->executeSP($tsql, $params);

		return $data;
	}

	public static function getAgrupadoresActividad(SAODBConn $conn, $id_obra, $descripcion, $id_agrupador = null ) {

		$tsql = "{call [PresupuestoObra].[uspAgrupadorActividad]( ?, ?, ? )}";

		$params = array(
			array( $id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $id_agrupador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('300') )
		);

		$data = $conn->executeSP($tsql, $params);

		return $data;
	}

	public function __toString() {

		$data  = "FechaInicio: {}, ";
		$data .= "FechaTermino: {}, ";
		$data .= "Referencia: {}, ";

		return $data;
	}
}
?>