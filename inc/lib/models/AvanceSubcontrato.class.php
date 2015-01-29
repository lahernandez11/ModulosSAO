<?php
require_once 'models/TransaccionSAO.class.php';

class AvanceSubcontrato extends TransaccionSAO {

	const TIPO_TRANSACCION = 105;

	private $fecha;
	private $fechaInicio;
	private $fechaTermino;
	private $fechaEjecucion;
	private $fechaContable;
	public $subcontrato;
	private $conceptos;
	private $observaciones;

	public function __construct()
	{
		$params = func_get_args();

		switch ( func_num_args())
		{
			case 9:
				call_user_func_array(array($this, "instaceFromDefault"), $params);
				break;

			case 2:
				call_user_func_array(array($this, "instanceFromID"), $params);
				break;
		}
	}

	/**
	 * @param Obra $obra
	 * @param Subcontrato $subcontrato
	 * @param $fecha
	 * @param $fechaInicio
	 * @param $fechaTermino
	 * @param $fechaEjecucion
	 * @param $fechaContable
	 * @param $observaciones
	 * @param array $conceptos
     * @throws Exception
     */
	private function instaceFromDefault(
		Obra $obra, Subcontrato $subcontrato, $fecha, $fechaInicio, $fechaTermino,
		$fechaEjecucion, $fechaContable, $observaciones, Array $conceptos)
	{
		parent::__construct($obra, self::TIPO_TRANSACCION, $fecha, $observaciones);

		$this->subcontrato = $subcontrato;
		$this->setFechaInicio($fechaInicio);
		$this->setFechaTermino($fechaTermino);
		$this->setFechaEjecucion($fechaEjecucion);
		$this->setFechaContable($fechaContable);
		$this->setConceptos($conceptos);
	}

	/**
	 * @param Obra $obra
	 * @param $id_transaccion
	 */
	private function instanceFromID(Obra $obra, $id_transaccion)
	{
		parent::__construct($obra, $id_transaccion);
		
		$this->setDatosGenerales();
	}

	/**
	 * @throws Exception
     */
	protected function setDatosGenerales()
	{
		parent::setDatosGenerales();

		$tsql = "SELECT
					  [id_antecedente]
					, [tipo_transaccion]
					, [fecha]
					, [id_obra]
					, [id_empresa]
					, [id_moneda]
					, [cumplimiento]
					, [vencimiento]
					, [fecha_ejecucion]
					, [fecha_contable]
					, [monto]
					, [impuesto]
					, [referencia]
					, [comentario]
					, [observaciones]
				FROM
					[dbo].[transacciones]
				WHERE
					[id_obra] = ?
						AND
					[id_transaccion] = ?";

		$params = array
		(
			array($this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
			array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	    );

	    $datos = $this->conn->executeQuery($tsql, $params);

	    $this->fechaInicio = $datos[0]->cumplimiento;
	    $this->fechaTermino = $datos[0]->vencimiento;
	    $this->fechaEjecucion = $datos[0]->fecha_ejecucion;
	    $this->fechaContable = $datos[0]->fecha_contable;
	    $this->subcontrato = new Subcontrato($this->obra, $datos[0]->id_antecedente);
	}

	/**
	 * @param Usuario $usuario
	 * @return array
	 * @throws Exception
     */
	public function guardaTransaccion( Usuario $usuario )
	{
		try
		{
			$this->conn->beginTransaction();

			if ( ! empty($this->id_transaccion))
			{
				$tsql = "UPDATE [dbo].[transacciones]
						 SET
						 	  [fecha] = ?
							, [cumplimiento] = ?
							, [vencimiento] = ?
							, [fecha_ejecucion] = ?
							, [fecha_contable] = ?
							, [referencia] = ?
							, [comentario] = [comentario] + ?
							, [observaciones] = ?
						 WHERE
						 	[id_transaccion] = ?";

				$params = array(
					array($this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
					array($this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
					array($this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
					array($this->getFechaEjecucion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
					array($this->getFechaContable(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
					array($this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(64)),
					array($this->generaComentario($usuario, 'M'), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(1024)),
					array($this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096)),
					array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
				);

				$this->conn->executeQuery($tsql, $params);
			}
			else
			{
				$tsql = "INSERT INTO [dbo].[transacciones]
						(
							  [id_antecedente]
							, [tipo_transaccion]
							, [fecha]
							, [id_obra]
							, [id_empresa]
							, [id_moneda]
							, [cumplimiento]
							, [vencimiento]
							, [fecha_ejecucion]
							, [fecha_contable]
							, [monto]
							, [impuesto]
							, [referencia]
							, [comentario]
							, [observaciones]
						)
						VALUES
						(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

				$params = array
				(
					array($this->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array(self::TIPO_TRANSACCION, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
					array($this->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($this->subcontrato->empresa->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($this->subcontrato->moneda->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
					array($this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
					array($this->getFechaEjecucion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
					array($this->getFechaContable(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
					array(0.0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT),
					array(0.0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT),
					array($this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(64)),
					array($this->generaComentario($usuario, 'I'), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(1024)),
					array($this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096)),
				);

				$id_transaccion = $this->conn->executeQueryGetId($tsql, $params);

				$this->setIDTransaccion($id_transaccion);
			}

			$errores = $this->guardaConceptos();

			$this->conn->commitTransaction();

			return $errores;
		}
		catch (Exception $e)
		{
			$this->conn->rollbackTransaction();
			throw $e;
		}

	}

	/**
	 * @return array
     */
	private function guardaConceptos()
	{
		$errores = array();

		$tsql = "{call [AvanceSubcontratos].[uspAvanceConcepto]( ?, ?, ?, ? )}";

		$suma_importes = 0;

		foreach ($this->conceptos as $concepto)
		{
			try
			{
				// Limpia y valida el importe estimado
				$concepto['cantidad'] = str_replace(',', '', $concepto['cantidad']);

				// Si el importe no es valido agrega el concepto con error
				if ( ! $this->esImporte($concepto['cantidad']))
				{
					throw new Exception("El numero ingresado no es correcto");
				}

				$params = array
				(
					array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($this->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($concepto['id_concepto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($concepto['cantidad'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT),
				);

				$this->conn->executeQuery($tsql, $params);

				$suma_importes += $concepto['cantidad'];
			}
			catch (Exception $e)
			{
				$errores[] = array
				(
					'id_concepto' => $concepto['id_concepto'],
					'cantidad' => $concepto['cantidad'],
					'message' => $e->getMessage(),
				);
			}
		}

		return $errores;
	}

	/**
	 * @param array $conceptos
     */
	public function setConceptos(Array $conceptos)
	{
		$this->conceptos = $conceptos;
	}

	/**
	 * @return null
     */
	public function getFechaInicio()
	{
		return $this->fechaInicio;
	}

	/**
	 * @param $fecha
	 * @throws Exception
     */
	public function setFechaInicio($fecha)
	{
		if ( ! $this->fechaEsValida($fecha))
		{
			throw new Exception("El formato de fecha inicial es incorrecto.");
		}
		
		$this->fechaInicio = $fecha;
	}

	/**
	 * @return null
     */
	public function getFechaTermino()
	{
		return $this->fechaTermino;
	}

	/**
	 * @param $fecha
	 * @throws Exception
     */
	public function setFechaTermino($fecha)
	{
		if ( ! $this->fechaEsValida($fecha))
		{
			throw new Exception("El formato de fecha termino es incorrecto.");
		}
		
		$this->fechaTermino = $fecha;
	}

	/**
	 * @param $fecha
	 * @throws Exception
     */
	public function setFechaEjecucion($fecha)
	{
		if ( ! $this->fechaEsValida($fecha))
		{
			throw new Exception("El formato de fecha termino es incorrecto.");
		}

		$this->fechaEjecucion = $fecha;
	}

	/**
	 * @return mixed
     */
	public function getFechaEjecucion()
	{
		return $this->fechaEjecucion;
	}

	/**
	 * @param $fecha
	 * @throws Exception
     */
	public function setFechaContable($fecha)
	{
		if ( ! $this->fechaEsValida($fecha))
		{
			throw new Exception("El formato de fecha termino es incorrecto.");
		}

		$this->fechaContable = $fecha;
	}

	/**
	 * @return mixed
     */
	public function getFechaContable()
	{
		return $this->fechaContable;
	}

	/**
	 * @return mixed
     */
	public function getConceptosAvance()
	{
		$tsql = "SELECT
					  [transacciones].[id_transaccion]
					, [items].[id_concepto]
					, [contratos].[nivel]
					, (LEN([contratos].[nivel]) / 4) - 1 AS [numero_nivel]
					,
					CASE
						WHEN [items].[id_concepto] > 0 THEN 1
						ELSE 0
					END AS [es_actividad]
					, [contratos].[descripcion]
					, [contratos].[unidad]
					, [contratos].[cantidad_presupuestada]
					, [items].[precio_unitario]
					, [contratos].[estado]
					, [contratos].[clave]
					, [items_avance].[cantidad]
				FROM
					[dbo].[contratos]
				INNER JOIN
					[dbo].[transacciones]
					ON
						[contratos].[id_transaccion] = [transacciones].[id_antecedente]
				LEFT OUTER JOIN
					[dbo].[items]
					ON
						[transacciones].[id_transaccion] = [items].[id_transaccion]
							AND
						[contratos].[id_concepto] = [items].[id_concepto]
				LEFT OUTER JOIN
				(
					SELECT
						  [items].[id_antecedente]
						, [items].[id_concepto]
						, items.[cantidad]
					FROM
						[dbo].[transacciones]
					INNER JOIN
						[dbo].[items]
					ON
						[transacciones].[id_transaccion] = [items].[id_transaccion]
					WHERE
						[transacciones].[tipo_transaccion] = 105
							AND
						[items].[id_transaccion] = ?
				) AS [items_avance]
				ON
					[transacciones].[id_transaccion] = [items_avance].[id_antecedente]
						AND
					[items].[id_concepto] = [items_avance].[id_concepto]
				WHERE
					[transacciones].[tipo_transaccion] = 51
						AND
					[transacciones].[id_obra] = ?
						AND
					[transacciones].[id_transaccion] = ?
						AND
					EXISTS
					(
						SELECT 1
						FROM
							[dbo].[items] AS [items_contrato]
						INNER JOIN
							[dbo].[contratos] AS [conceptos_contrato]
							ON
								[items_contrato].[id_concepto] = [conceptos_contrato].[id_concepto]
						WHERE
							[items_contrato].[id_transaccion] = [transacciones].[id_transaccion]
								AND
							[conceptos_contrato].[nivel] LIKE [contratos].[nivel] + '%'
					)
				ORDER BY
					[contratos].[nivel];;";

	    $params = array
		(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
			array($this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
			array($this->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	    );

	    $conceptos = $this->conn->executeQuery($tsql, $params);

	    return $conceptos;
	}

	/**
	 * @return mixed
     */
	public function getTotalesTransaccion()
	{
		$tsql = "SELECT
					  [transacciones].[id_transaccion]
					, [transacciones].[monto] - [transacciones].[impuesto] AS [subtotal]
					, [transacciones].[monto]
					, [transacciones].[impuesto]
				FROM
					[transacciones]
				WHERE
					[transacciones].[id_transaccion] = ?";

		$params = array
		(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $totales = $this->conn->executeQuery($tsql, $params);

	    return $totales;
	}

	/**
	 * @param Obra $obra
	 * @param $id_subcontrato
	 * @return array
	 * @throws DBServerStatementExecutionException
     */
	public static function getConceptosNuevoAvance(Obra $obra, $id_subcontrato)
	{
		$tsql = "SELECT
					  [transacciones].[id_transaccion]
					, [items].[id_concepto]
					, [contratos].[nivel]
					, (LEN([contratos].[nivel]) / 4) - 1 AS [numero_nivel]
					,
					CASE
						WHEN [items].[id_concepto] > 0 THEN 1
						ELSE 0
					END AS [es_actividad]
					, [contratos].[descripcion]
					, [contratos].[unidad]
					, [contratos].[cantidad_presupuestada]
					, [items].[precio_unitario]
					, [contratos].[estado]
					, [contratos].[clave]
				FROM
					[dbo].[contratos]
				INNER JOIN
					[dbo].[transacciones]
					ON
						[contratos].[id_transaccion] = [transacciones].[id_antecedente]
				LEFT OUTER JOIN
					[dbo].[items]
					ON
						[transacciones].[id_transaccion] = [items].[id_transaccion]
							AND
						[contratos].[id_concepto] = [items].[id_concepto]
				WHERE
					[transacciones].[tipo_transaccion] = 51
						AND
					[transacciones].[id_obra] = ?
						AND
					[transacciones].[id_transaccion] = ?
						AND
					EXISTS
					(
						SELECT 1
						FROM
							[dbo].[items] AS [items_contrato]
						INNER JOIN
							[dbo].[contratos] AS [conceptos_contrato]
							ON
								[items_contrato].[id_concepto] = [conceptos_contrato].[id_concepto]
						WHERE
							[items_contrato].[id_transaccion] = [transacciones].[id_transaccion]
								AND
							[conceptos_contrato].[nivel] LIKE [contratos].[nivel] + '%'
					)
				ORDER BY
					[contratos].[nivel];";

		$params = array
		(
			array($obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
			array($id_subcontrato, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
		);

		$conceptos = $obra->getConn()->executeSP($tsql, $params);

		return $conceptos;
	}

	/**
	 * @param Obra $obra
	 * @return array
	 * @throws DBServerStatementExecutionException
     */
	public static function getFoliosTransaccion(Obra $obra)
	{
		$tsql = "SELECT
					[id_transaccion],
					[numero_folio]
				 FROM
				 	[dbo].[transacciones]
				 WHERE
				 	[id_obra] = ?
						AND
					[tipo_transaccion] = ?
				 ORDER BY
				 	[numero_folio] DESC";

		$params = array
		(
			array($obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
			array(self::TIPO_TRANSACCION, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
		);

		$foliosTran = $obra->getConn()->executeSP($tsql, $params);

		return $foliosTran;
	}

	/**
	 * @param Obra $obra
	 * @param null $tipo_transaccion
	 * @return array
     */
	public static function getListaTransacciones(Obra $obra, $tipo_transaccion=null)
	{
		return parent::getListaTransacciones($obra, self::TIPO_TRANSACCION);
	}

}