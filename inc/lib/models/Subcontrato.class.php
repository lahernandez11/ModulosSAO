<?php
require_once 'TransaccionSAO.class.php';
require_once 'Empresa.class.php';
require_once 'Moneda.class.php';

class Subcontrato extends TransaccionSAO {

	const TIPO_TRANSACCION = 51;

	public $empresa;
	public $moneda;

	private $porcentaje_anticipo 	   = 0;
	private $importe_anticipo 		   = 0;
	private $porcentaje_fondo_garantia = 0;
	private $importe_fondo_garantia    = 0;
	private $subtotal 				   = 0;
	private $iva 					   = 0;
	private $porcentaje_iva 		   = 0;
	private $total 					   = 0;

	// datos adicionales
	private $tipo_contrato;
	private $descripcion;
	private $id_clasificador;
	private $clasificador;
	private $monto_subcontrato 		 = 0;
	private $monto_anticipo 		 = 0;
	private $porcentaje_retencion_fg = 0;
	private $fecha_inicio_cliente;
	private $fecha_termino_cliente;
	private $fecha_inicio_proyecto;
	private $fecha_termino_proyecto;
	private $fecha_inicio_contratista;
	private $fecha_termino_contratista;
	private $monto_venta_cliente 		= 0;
	private $monto_venta_actual_cliente = 0;
	private $monto_inicial_pio 			= 0;
	private $monto_actual_pio 			= 0;

	// Acumulados de estimaciones del subcontrato
	private $importe_acumulado_estimado = 0;
	private $importe_acumulado_anticipo = 0;
	private $importe_acumulado_fondo_garantia = 0;
	private $importe_acumulado_retencion = 0;

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

		$tsql = "SELECT
					[transacciones].[id_transaccion]
					,
					CASE [contrato].[opciones]
						WHEN 2 THEN 'Precios Unitarios'
						WHEN 1026 THEN 'Precios Unitarios'
						WHEN 4 THEN 'Suministro y/o Colocación de Materiales'
						WHEN 1028 THEN 'Suministro y/o Colocación'
						WHEN 20 THEN 'Suministro y/o Colocación de Materiales % de Indirectos'
						WHEN 1044 THEN 'Suministro y/o Colocación de Materiales % de Indirectos'
						WHEN 516 THEN 'Suministro y/o Colocación de Materiales % de Desperdicio'
						WHEN 1540 THEN 'Suministro y/o Colocación de Materiales % de Desperdicio'
						WHEN 532 THEN 'Suministro y/o Colocación de Materiales % de Desperdicio y % de Indirectos'
						WHEN 1556 THEN 'Suministro y/o Colocación de Materiales % de Desperdicio y % de Indirectos'
						WHEN 65544 THEN 'Destajo de Mano de Obra'
						WHEN 66568 THEN 'Destajo de Mano de Obra'
						ELSE 'Sin Identificar'
					END AS [tipo_contrato]
					, [transacciones].[id_empresa]
					, [transacciones].[id_moneda]
					, [transacciones].[observaciones]
					, [transacciones].[anticipo] / 100 AS [PctAnticipo]
					, [transacciones].[anticipo_monto] AS [ImporteAnticipo]
					, [transacciones].[retencion] / 100 AS [PctFondoGarantia]
					, ([transacciones].[monto] - [transacciones].[impuesto]) AS [subtotal]
					, [transacciones].[impuesto]
					, ([transacciones].[impuesto] / ([transacciones].[monto] - [transacciones].[impuesto])) as [porcentaje_iva]
					, [transacciones].[monto]
					, ([transacciones].[monto] - [transacciones].[impuesto])
						*
					  ([transacciones].[retencion] / 100) AS [ImporteFondoGarantia]
					, [Acumulado].[ImporteAcumuladoEstimado]
					, [Acumulado].[ImporteAcumuladoAnticipo]
					, [Acumulado].[ImporteAcumuladoFondoGarantia]
					, [Acumulado].[ImporteAcumuladoRetenciones]
					, [subcontrato].[descripcion]
					, [subcontrato].[id_clasificador]
					, [clasificador].[clasificador]
					, [subcontrato].[monto_subcontrato]
					, [subcontrato].[monto_anticipo]
					, [subcontrato].[porcentaje_retencion_fg]
					, [subcontrato].[fecha_inicio_cliente]
					, [subcontrato].[fecha_termino_cliente]
					, [subcontrato].[fecha_inicio_proyecto]
					, [subcontrato].[fecha_termino_proyecto]
					, [subcontrato].[fecha_inicio_contratista]
					, [subcontrato].[fecha_termino_contratista]
					, [subcontrato].[monto_venta_cliente]
					, [subcontrato].[monto_venta_actual_cliente]
					, [subcontrato].[monto_inicial_pio]
					, [subcontrato].[monto_actual_pio]
				FROM
					[dbo].[transacciones]
				INNER JOIN
					[dbo].[transacciones] AS [contrato]
					ON
					[transacciones].[id_antecedente] = [contrato].[id_transaccion]
				LEFT OUTER JOIN
					[Subcontratos].[subcontrato]
					ON
						[transacciones].[id_transaccion] = [subcontrato].[id_transaccion]
				LEFT OUTER JOIN
					[Subcontratos].[clasificador]
					ON
						[subcontrato].[id_clasificador] = [clasificador].[id_clasificador]
				LEFT OUTER JOIN
				(
					SELECT
						[estimacion].[id_antecedente],
						SUM([SumaItems].[SumaImportes]) AS [ImporteAcumuladoEstimado],
						SUM
						(
							ISNULL
							(
								[SumaItems].[SumaImportes]
									*
								(1 - [estimacion].[retencion] / 100)
								- [estimacion].[monto]
								+ [estimacion].[impuesto]
								, 0
							)
						) AS [ImporteAcumuladoAnticipo], 
						SUM
						(
							ISNULL
							(
								[SumaItems].[SumaImportes]
									*
								[estimacion].[retencion] / 100, 0
							)
						) AS [ImporteAcumuladoFondoGarantia]
						, SUM(ISNULL([SumaRetenciones].[ImporteAcumuladoRetenciones], 0)) AS [ImporteAcumuladoRetenciones]
					FROM
						[dbo].[transacciones] AS [estimacion]
					INNER JOIN
					(
						SELECT
							[id_transaccion],
							SUM([importe]) AS [SumaImportes]
						FROM
							[dbo].[items]
						GROUP BY
							[id_transaccion]
					) AS [SumaItems]
						ON
							[estimacion].[id_transaccion] = [SumaItems].[id_transaccion]
					LEFT OUTER JOIN
					(
						SELECT
							[id_transaccion],
							SUM([importe]) AS [ImporteAcumuladoRetenciones]
						FROM
							[SubcontratosEstimaciones].[retencion]
						GROUP BY
							[id_transaccion]
					) AS [SumaRetenciones]
						ON
							[estimacion].[id_transaccion] = [SumaRetenciones].[id_transaccion]
					WHERE
						[estimacion].[tipo_transaccion] = 52
							AND
						[estimacion].[estado] >= 0
					GROUP BY
						[estimacion].[id_antecedente]
				) AS [Acumulado]
					ON
						[transacciones].[id_transaccion] = [Acumulado].[id_antecedente]
				WHERE
					[contrato].[tipo_transaccion] = 49
						AND
					[transacciones].[tipo_transaccion] = 51
						AND
				    [transacciones].[id_transaccion] = ?;";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $datos = $this->conn->executeSP( $tsql, $params );

	    $this->empresa 				 = new Empresa( $this->obra, $datos[0]->id_empresa );
	    $this->moneda 				 = new Moneda( $this->obra, $datos[0]->id_moneda );

	    $this->porcentaje_fondo_garantia 	= $datos[0]->PctFondoGarantia;
	    $this->importe_fondo_garantia 		= $datos[0]->ImporteFondoGarantia;
	    $this->porcentaje_anticipo 		 	= $datos[0]->PctAnticipo;
	    $this->importe_anticipo 	 		= $datos[0]->ImporteAnticipo;
	    $this->subtotal 	 		 		= $datos[0]->subtotal;
	    $this->iva 	 			 			= $datos[0]->impuesto;
	    $this->porcentaje_iva 	 			= $datos[0]->porcentaje_iva;
	    $this->total 	 			 		= $datos[0]->monto;

	    $this->importe_acumulado_estimado 	= $datos[0]->ImporteAcumuladoEstimado;
	    $this->importe_acumulado_anticipo 	= $datos[0]->ImporteAcumuladoAnticipo;
	    $this->importe_acumulado_fondo_garantia = $datos[0]->ImporteAcumuladoFondoGarantia;
	    $this->importe_acumulado_retencion  = $datos[0]->ImporteAcumuladoRetenciones;

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
	
	public function getImporteAnticipo() {
		return $this->importe_anticipo;
	}

	public function getImporteEstimado() {
		return $this->importe_acumulado_estimado;
	}

	public function getImporteAcumuladoAnticipo() {
		return $this->importe_acumulado_anticipo;
	}

	public function getImporteFondoGarantia() {
		return $this->importe_fondo_garantia;
	}

	public function getImporteAcumuladoFondoGarantia() {
		return $this->importe_acumulado_fondo_garantia;
	}

	public function getImporteAcumuladoRetenciones() {
		return $this->importe_acumulado_retencion;
	}

	public function getSubtotal() {
		return $this->subtotal;
	}

	public function getIVA() {
		return $this->iva;
	}

	public function getPorcentajeIVA() {
		return $this->porcentaje_iva;
	}

	public function getTotal() {
		return $this->total;
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

	public function getPorcentajeAnticipo() {
		return $this->porcentaje_anticipo;
	}

	public function getPorcentajeRetencion() {
		return $this->porcentaje_fondo_garantia;
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

	public function getEstimaciones() {
		
		$tsql = "SELECT
					[id_transaccion]
				FROM
					[dbo].[transacciones]
				WHERE
					[id_obra] = ?
						AND
				    [tipo_transaccion] = ?
						AND
				    [id_antecedente] = ?";

		$params = array(
			array( $this->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
			array( EstimacionSubcontrato::TIPO_TRANSACCION, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
			array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
		);

		$data = $this->conn->executeQuery( $tswl, $params );

		$estimaciones = array();

		foreach ( $data as $estimacion ) {
			$estimaciones[] = new EstimacionSubcontrato( $this->obra, $estimacion->id_transaccion );
		}

		return $estimaciones;
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
	        array( $this->empresa->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
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
	        array( $this->empresa->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
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
	        array( $this->empresa->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
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