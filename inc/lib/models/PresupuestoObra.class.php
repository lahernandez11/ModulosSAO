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

	public function setClaveConcepto($id_concepto, $clave) {

		$tsql = "UPDATE [dbo].[conceptos]
				 SET
				 	[clave_concepto] = ?
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_concepto] = ?";

	    $params = array(
	        array( $clave, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(140) ),
	        array( $this->id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery($tsql, $params);
	}

	private function esMedibleFacturable($id_concepto) {

		$tsql = "SELECT 1
				 FROM [dbo].[conceptos]
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_concepto] = ?
				 		AND
				 	[concepto_medible] > 0";

	    $params = array(
	        array( $this->id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $res = $this->conn->executeQuery($tsql, $params);

	    if (count($res) > 0)
	    	return true;
	    else
	    	return false;
	}

	public function setAgrupador($id_concepto, $id_agrupador, $method) {

		if( $this->esMedibleFacturable($id_concepto) ) {
			$this->{$method}($id_concepto, $id_agrupador);
		} else {

			$tsql = "SELECT
						[conceptos].[id_concepto]
					FROM
						[dbo].[conceptos]
					WHERE
						[id_obra] = ?
							AND
					    EXISTS
						(
							SELECT
								1
							FROM
								[conceptos] AS [raiz]
							WHERE
								[raiz].[id_concepto] = ?
									AND
					            LEFT([conceptos].[nivel], LEN([raiz].[nivel])) = [raiz].[nivel]
						)
							AND
						[concepto_medible] > 0";

			$params = array(
		        array( $this->id_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $id_concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		    );

			$medibles = $this->conn->executeQuery($tsql, $params);

			foreach ($medibles as $concepto) {
				$this->{$method}($concepto->id_concepto, $id_agrupador);
			}
		}
	}

	private function setAgrupadorPartida( $id_concepto, $id_agrupador ) {

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

	private function setAgrupadorSubpartida( $id_concepto, $id_agrupador ) {
		
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
	
	private function setAgrupadorActividad( $id_concepto, $id_agrupador ) {
		
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

	private function setAgrupadorTramo( $id_concepto, $id_agrupador ) {
		
		$tsql = "UPDATE [dbo].[conceptos]
				 SET
				 	[id_agrupador_tramo] = ?
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

	private function setAgrupadorSubtramo( $id_concepto, $id_agrupador ) {
		
		$tsql = "UPDATE [dbo].[conceptos]
				 SET
				 	[id_agrupador_subtramo] = ?
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

	public function __toString() {

		$data  = "FechaInicio: {}, ";
		$data .= "FechaTermino: {}, ";
		$data .= "Referencia: {}, ";

		return $data;
	}
}
?>