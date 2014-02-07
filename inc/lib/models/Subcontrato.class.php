<?php
require_once 'TransaccionSAO.class.php';

class Subcontrato extends TransaccionSAO {

	const TIPO_TRANSACCION = 51;

	private $_id_empresa;
	private $_nombreContratista;
	private $_pctFondoGarantia;
	private $_importeFondoGarantia;
	private $_pctAnticipo;
	private $_importeAnticipo;
	private $_importeAcumuladoEstimado;
	private $_importeAcumuladoAnticipo;
	private $_importeAcumuladoFondoGarantia;
	private $_importeAcumuladoRetenciones;
	private $_importeAcumuladoDeductivas;
	private $_subtotal;
	private $_iva;
	private $_total;

	// datos adicionales
	private $tipo_contrato;
	private $descripcion;
	private $id_clasificador;
	private $clasificador;
	private $monto_subcontrato;
	private $monto_anticipo;
	private $porcentaje_retencion_fg;
	private $fecha_inicio_cliente;
	private $fecha_termino_cliente;
	private $fecha_inicio_proyecto;
	private $fecha_termino_proyecto;
	private $fecha_inicio_contratista;
	private $fecha_termino_contratista;
	private $monto_venta_cliente;
	private $monto_venta_actual_cliente;
	private $monto_inicial_pio;
	private $monto_actual_pio;

	public function __construct() {
		
		$params = func_get_args();

		switch ( func_num_args() ) {

			case 6:
				call_user_func_array( array( $this, "instaceFromDefault" ), $params );
				break;

			case 2:
				call_user_func_array( array( $this, "instanceFromID" ), $params );
				break;
		}
	}

	private function instaceFromDefault( Obra $obra, $fecha, $fechaInicio, $fechaTermino, 
		$observaciones, Array $conceptos ) {

		parent::__construct( $obra, self::TIPO_TRANSACCION, $fecha, $observaciones );
	}

	private function instanceFromID( Obra $obra, $id_transaccion ) {
		parent::__construct( $obra, $id_transaccion );
		
		$this->setDatosGenerales();

		if ( ! $this->existeRegistroSubcontrato() ) {
			$this->creaRegistroSubcontrato();
		}
	}

	// @override
	protected function setDatosGenerales() {
		
		parent::setDatosGenerales();

		$tsql = "{call [Subcontratos].[uspDatosGenerales]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $datos = $this->conn->executeSP( $tsql, $params );

	    $this->_id_empresa 	 		 = $datos[0]->id_empresa;
	    $this->_nombreContratista 	 = $datos[0]->NombreContratista;
	    $this->_pctFondoGarantia 	 = $datos[0]->PctFondoGarantia;
	    $this->_importeFondoGarantia = $datos[0]->ImporteFondoGarantia;
	    $this->_pctAnticipo 		 = $datos[0]->PctAnticipo;
	    $this->_importeAnticipo 	 = $datos[0]->ImporteAnticipo;
	    $this->_subtotal 	 		 = $datos[0]->Subtotal;
	    $this->_iva 	 			 = $datos[0]->IVA;
	    $this->_total 	 			 = $datos[0]->Total;

	    $this->_importeAcumuladoEstimado 	= $datos[0]->ImporteAcumuladoEstimado;
	    $this->_importeAcumuladoAnticipo 	= $datos[0]->ImporteAcumuladoAnticipo;
	    $this->_importeAcumuladoFondoGarantia = $datos[0]->ImporteAcumuladoFondoGarantia;
	    $this->_importeAcumuladoRetenciones = $datos[0]->ImporteAcumuladoRetenciones;
	    $this->_importeAcumuladoDeductivas 	= $datos[0]->ImporteAcumuladoDeductivas;

		$this->tipo_contrato 			   	= $datos[0]->tipo_contrato;
		$this->descripcion 			   		= $datos[0]->descripcion;
		$this->id_clasificador 		   		= $datos[0]->id_clasificador;
		$this->clasificador 			   	= $datos[0]->clasificador;
		$this->monto_subcontrato 		   	= $datos[0]->monto_subcontrato;
		$this->monto_anticipo 			   	= $datos[0]->monto_anticipo;
		$this->porcentaje_retencion_fg    	= $datos[0]->porcentaje_retencion_fg;
		$this->fecha_inicio_cliente 	   	= $datos[0]->fecha_inicio_cliente;
		$this->fecha_termino_cliente 	   	= $datos[0]->fecha_termino_cliente;
		$this->fecha_inicio_proyecto 	   	= $datos[0]->fecha_inicio_proyecto;
		$this->fecha_termino_proyecto 	   	= $datos[0]->fecha_termino_proyecto;
		$this->fecha_inicio_contratista   	= $datos[0]->fecha_inicio_contratista;
		$this->fecha_termino_contratista  	= $datos[0]->fecha_termino_contratista;
		$this->monto_venta_cliente 	   		= $datos[0]->monto_venta_cliente;
		$this->monto_venta_actual_cliente 	= $datos[0]->monto_venta_actual_cliente;
		$this->monto_inicial_pio 		   	= $datos[0]->monto_inicial_pio;
		$this->monto_actual_pio 		   	= $datos[0]->monto_actual_pio;
	}

	public function guardaTransaccion() {

		$tsql = "UPDATE [Subcontratos].[subcontrato]
				SET
					[descripcion] = ?,
					[monto_subcontrato] = ?,
					[monto_anticipo] = ?,
					[porcentaje_retencion_fg] = ?,
					[fecha_inicio_cliente] = ?,
					[fecha_termino_cliente] = ?,
					[fecha_inicio_proyecto] = ?,
					[fecha_termino_proyecto] = ?,
					[fecha_inicio_contratista] = ?,
					[fecha_termino_contratista] = ?,
					[monto_venta_cliente] = ?,
					[monto_venta_actual_cliente] = ?,
					[monto_inicial_pio] = ?,
					[monto_actual_pio] = ?
				WHERE
					[id_transaccion] = ?";
		$params = array(
			$this->descripcion,
			$this->monto_subcontrato,
			$this->monto_anticipo,
			$this->porcentaje_retencion_fg,
			$this->fecha_inicio_cliente,
			$this->fecha_termino_cliente,
			$this->fecha_inicio_proyecto,
			$this->fecha_termino_proyecto,
			$this->fecha_inicio_contratista,
			$this->fecha_termino_contratista,
			$this->monto_venta_cliente,
			$this->monto_venta_actual_cliente,
			$this->monto_inicial_pio,
			$this->monto_actual_pio,
			$this->getIDTransaccion()
		);

		$this->conn->executeQuery( $tsql, $params );
	}

	public function getReferencia() {
		return $this->referencia;
	}

	public function getNombreContratista() {
		return $this->_nombreContratista;
	}

	public function getImporteAnticipo() {
		return $this->_importeAnticipo;
	}

	public function getImporteEstimado() {
		return $this->_importeAcumuladoEstimado;
	}

	public function getImporteAcumuladoAnticipo() {
		return $this->_importeAcumuladoAnticipo;
	}

	public function getImporteFondoGarantia() {
		return $this->_importeFondoGarantia;
	}

	public function getImporteAcumuladoFondoGarantia() {
		return $this->_importeAcumuladoFondoGarantia;
	}

	public function getImporteAcumuladoRetenciones() {
		return $this->_importeAcumuladoRetenciones;
	}

	public function getImporteAcumuladoDeductivas() {
		return $this->_importeAcumuladoDeductivas;
	}

	public function getSubtotal() {
		return $this->_subtotal;
	}

	public function getIVA() {
		return $this->_iva;
	}

	public function getTotal() {
		return $this->_total;
	}

	public function getTipoContrato() {
		return $this->tipo_contrato;
	}

	public function getDescripcion() {
		return $this->descripcion;
	}

	public function getIdClasificador() {
		return $this->id_clasificador;
	}

	public function getClasificador() {
		return $this->clasificador;
	}

	public function getMontoSubcontrato() {
		return $this->monto_subcontrato;
	}

	public function getMontoAnticipo() {
		return $this->monto_anticipo;
	}

	public function getPorcentajeRetencionFG() {
		return $this->porcentaje_retencion_fg;
	}

	public function getFechaInicioCliente() {
		return $this->fecha_inicio_cliente;
	}

	public function getFechaTerminoCliente() {
		return $this->fecha_termino_cliente;
	}

	public function getFechaInicioProyecto() {
		return $this->fecha_inicio_proyecto;
	}

	public function getFechaTerminoProyecto() {
		return $this->fecha_termino_proyecto;
	}

	public function getFechaInicioContratista() {
		return $this->fecha_inicio_contratista;
	}

	public function getFechaTerminoContratista() {
		return $this->fecha_termino_contratista;
	}

	public function getMontoVentaCliente() {
		return $this->monto_venta_cliente;
	}

	public function getMontoVentaActualCliente() {
		return $this->monto_venta_actual_cliente;
	}

	public function getMontoInicialPio() {
		return $this->monto_inicial_pio;
	}

	public function getMontoActualPio() {
		return $this->monto_actual_pio;
	}

	public function setDescripcion( $valor ) {
		$this->descripcion = $valor;
	}

	public function setMontoSubcontrato( $valor ) {
		if ( ! $this->esImporte( $valor ) ) {
			$valor = 0.0;
		}
		
		$this->monto_subcontrato = $valor;
	}

	public function setMontoAnticipo( $valor ) {
		if ( ! $this->esImporte( $valor ) ) {
			$valor = 0.0;
		}
		
		$this->monto_anticipo = $valor;
	}

	public function setPorcentajeRetencionFG( $valor ) {
		if ( ! $this->esImporte( $valor ) ) {
			$valor = 0.0;
		}
		
		$this->porcentaje_retencion_fg = $valor;
	}

	public function setFechaInicioCliente( $valor ) {
		if ( ! $this->esFecha( $valor ) ) {
			$valor = null;
		}
		
		$this->fecha_inicio_cliente = $valor;
	}

	public function setFechaTerminoCliente( $valor ) {
		if ( ! $this->esFecha( $valor ) ) {
			$valor = null;
		}
		
		$this->fecha_termino_cliente = $valor;
	}

	public function setFechaInicioProyecto( $valor ) {
		if ( ! $this->esFecha( $valor ) ) {
			$valor = null;
		}
		
		$this->fecha_inicio_proyecto = $valor;
	}

	public function setFechaTerminoProyecto( $valor ) {
		if ( ! $this->esFecha( $valor ) ) {
			$valor = null;
		}
		
		$this->fecha_termino_proyecto = $valor;
	}

	public function setFechaInicioContratista( $valor ) {
		if ( ! $this->esFecha( $valor ) ) {
			$valor = null;
		}
		
		$this->fecha_inicio_contratista = $valor;
	}

	public function setFechaTerminoContratista( $valor ) {
		if ( ! $this->esFecha( $valor ) ) {
			$valor = null;
		}
		
		$this->fecha_termino_contratista = $valor;
	}

	public function setMontoVentaCliente( $valor ) {
		if ( ! $this->esImporte( $valor ) ) {
			$valor = 0.0;
		}
		
		$this->monto_venta_cliente = $valor;
	}

	public function setMontoVentaActualCliente( $valor ) {
		if ( ! $this->esImporte( $valor ) ) {
			$valor = 0.0;
		}
		
		$this->monto_venta_actual_cliente = $valor;
	}

	public function setMontoInicialPio( $valor ) {
		if ( ! $this->esImporte( $valor ) ) {
			$valor = 0.0;
		}

		$this->monto_inicial_pio = $valor;
	}

	public function setMontoActualPio( $valor ) {
		if ( ! $this->esImporte( $valor ) ) {
			$valor = 0.0;
		}
		
		$this->monto_actual_pio = $valor;
	}

	public static function getFoliosTransaccion( Obra $obra ) {
		return null;
	}
	
	public static function getListaTransacciones( Obra $obra, $tipo_transaccion=null ) {

		return parent::getListaTransacciones( $obra, self::TIPO_TRANSACCION );
	}

	public static function getSubcontratosPorContratista( Obra $obra ) {

		$tsql = "{call [Agrupacion].[uspListaSubcontratos]( ? )}";

		$params = array(
			array( $obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		);

		$datos = $obra->getConn()->executeSP( $tsql, $params );

		return $datos;
	}

	private function existeRegistroAgrupacion( $id_actividad ) {

		$tsql = "SELECT 1
				 FROM [Agrupacion].[agrupacion_subcontratos]
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_empresa] = ?
				 		AND
				 	[id_subcontrato] = ?
				 		AND
				 	[id_actividad] = ?";

	    $params = array(
	        array( $this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->_id_empresa, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_actividad, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $res = $this->conn->executeQuery( $tsql, $params );

	    if (count($res) > 0)
	    	return true;
	    else
	    	return false;
	}

	private function creaRegistroAgrupacion( $id_actividad ) {

		$tsql = "INSERT INTO [Agrupacion].[agrupacion_subcontratos]
				(
					  [id_obra]
					, [id_empresa]
					, [id_subcontrato]
					, [id_actividad]
				)
				VALUES
				( ?, ?, ?, ? )";

	    $params = array(
	        array( $this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->_id_empresa, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_actividad, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery( $tsql, $params );
	}

	public function setAgrupadorPartida( $id_actividad, AgrupadorInsumo $agrupador ) {

		if ( ! $this->existeRegistroAgrupacion( $id_actividad ) )
			$this->creaRegistroAgrupacion( $id_actividad );

		$field = '';

		switch ( $agrupador->getTipoAgrupador() ) {
			case AgrupadorInsumo::TIPO_NATURALEZA:
				$field = AgrupadorInsumo::FIELD_NATURALEZA;
				break;

			case AgrupadorInsumo::TIPO_FAMILIA:
				$field = AgrupadorInsumo::FIELD_FAMILIA;
				break;

			case AgrupadorInsumo::TIPO_GENERICO:
				$field = AgrupadorInsumo::FIELD_GENERICO;
				break;
		}

		$tsql = "UPDATE [Agrupacion].[agrupacion_subcontratos]
				 SET
				 	{$field} = ?
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_empresa] = ?
				 		AND
				 	[id_subcontrato] = ?
				 		AND
				 	[id_actividad] = ?";

	    $params = array(
	    	array( $agrupador->getIDAgrupador(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->_id_empresa, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_actividad, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery( $tsql, $params );
	}

	private function existeRegistroSubcontrato() {

		$tsql = "SELECT 1
				 FROM [Subcontratos].[subcontrato]
				 WHERE
				 	[id_transaccion] = ?";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $res = $this->conn->executeQuery( $tsql, $params );

	    if ( count( $res ) > 0)
	    	return true;
	    else
	    	return false;
	}

	private function creaRegistroSubcontrato() {

		$tsql = "INSERT INTO [Subcontratos].[subcontrato]
				(
					  [id_transaccion]
				)
				VALUES
				( ? )";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery( $tsql, $params );
	}

	public function setClasificador( $id_clasificador ) {

		$tsql = "UPDATE [Subcontratos].[subcontrato]
				 SET
				 	[id_clasificador] = ?
				 WHERE
				 	[id_transaccion] = ?";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $id_clasificador, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery( $tsql, $params );
	}

	public function getAddendums() {

		$tsql = "SELECT
					  [addendum].[id_addendum]
					, [addendum].[fecha]
					, [addendum].[monto]
					, [addendum].[monto_anticipo]
					, [addendum].[porcentaje_retencion_fg]
				FROM
					[Subcontratos].[addendum]
				WHERE
					[addendum].[id_transaccion] = ?
				ORDER BY
					[addendum].[fecha] DESC";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	   return $this->conn->executeQuery( $tsql, $params );
	}

	public function addAddendum( Addendum $addendum ) {

		$addendum->save( $this );
	}

	public static function getListaSubcontratos( Obra $obra ) {

		$tsql = '{call [SubcontratosEstimaciones].[uspListaSubcontratos]( ? )}';

		$params = array(
	        array( $obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $lista = $obra->getConn()->executeSP( $tsql, $params );

		return $lista;
	}

	public static function getContratistas( Obra $obra ) {
		
		$tsql = 'SELECT DISTINCT
					  [obras].[id_obra]
					, [transacciones].[id_empresa]
					, [empresas].[razon_social] AS [empresa]
				FROM
					[dbo].[transacciones]
				INNER JOIN
					[dbo].[obras]
					ON
					[transacciones].[id_obra] = [obras].[id_obra]
				INNER JOIN
					[dbo].[empresas]
					ON
					[transacciones].[id_empresa] = [empresas].[id_empresa]
				WHERE
					[transacciones].[tipo_transaccion] = ?
						AND
				    [transacciones].[id_obra] = ?
				ORDER BY
					[empresas].[razon_social];';

		$params = array( self::TIPO_TRANSACCION, $obra->getId() );

	    $data = $obra->getConn()->executeQuery( $tsql, $params );

		return $data;
	}

	public static function getTransaccionesContratista( Obra $obra, $id_empresa ) {

		$tsql = 'SELECT 
					  [id_obra]
					, [id_transaccion]
					, [fecha]
					, [numero_folio]
					, [referencia]
				FROM
					[dbo].[transacciones]
				WHERE
					[tipo_transaccion] = ?
						AND
				    [id_obra] = ?
				    	AND
				    [id_empresa] = ?
				ORDER BY
					[numero_folio]';

		$params = array( self::TIPO_TRANSACCION, $obra->getId(), $id_empresa );

	    $data = $obra->getConn()->executeQuery( $tsql, $params );

		return $data;
	}
}
?>