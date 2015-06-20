<?php
require_once 'models/TransaccionSAO.class.php';

class PropuestaEconomica extends TransaccionSAO {

	const TIPO_TRANSACCION = 107;

	private $id_concepto_raiz = 0;
	private $concepto_raiz;
	private $fecha_inicio;
	private $fecha_termino;
	private $conceptos;

	/**
	 *
     */
	public function __construct()
	{
		$params = func_get_args();

		switch (func_num_args())
		{
			case 7:
				call_user_func_array(array($this, "instaceFromDefault"), $params);
				break;

			case 2:
				call_user_func_array(array($this, "instanceFromID"), $params);
				break;
		}
	}

	/**
	 * @param Obra $obra
	 * @param $fecha
	 * @param $fechaInicio
	 * @param $fechaTermino
	 * @param $observaciones
	 * @param $id_concepto_raiz
	 * @param array $conceptos
	 * @throws Exception
     */
	private function instaceFromDefault(Obra $obra, $fecha, $fechaInicio, $fechaTermino,
		$observaciones, $id_concepto_raiz, Array $conceptos)
	{
		parent::__construct($obra, self::TIPO_TRANSACCION, $fecha, $observaciones);

		$this->setIDConceptoRaiz($id_concepto_raiz);
		$this->setFechaInicio($fechaInicio);
		$this->setFechaTermino($fechaTermino);
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

	// @override
	/**
	 * @throws Exception
     */
	protected function setDatosGenerales()
	{
		parent::setDatosGenerales();

		$tsql = "SELECT
					  [conceptos].[descripcion] AS [concepto_raiz]
					, CAST(ISNULL([transacciones].[cumplimiento], GETDATE()) AS DATE) AS [fecha_inicio]
					, CAST(ISNULL([transacciones].[vencimiento], GETDATE()) AS DATE) AS [fecha_termino]
				FROM
					[dbo].[transacciones]
				INNER JOIN
					[dbo].[conceptos]
					ON
						[transacciones].[id_concepto] = [conceptos].[id_concepto]
				WHERE
					[transacciones].[tipo_transaccion] = ?
						AND
					[transacciones].[id_transaccion] = ?;";

		$params = array
		(
			array(self::TIPO_TRANSACCION, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	    );

	    $datos = $this->conn->executeSP($tsql, $params);

	    $this->concepto_raiz   = $datos[0]->concepto_raiz;
	    $this->fecha_inicio    = $datos[0]->fecha_inicio;
	    $this->fecha_termino   = $datos[0]->fecha_termino;
	}

	/**
	 * @param Usuario $usuario
	 * @return array
	 * @throws Exception
	 */
	public function guardaTransaccion(Usuario $usuario)
	{
		$errores = array();

		try
		{
			$this->conn->beginTransaction();

			if ( ! empty($this->id_transaccion))
			{
				$tsql = "UPDATE
							[dbo].[transacciones]
						SET
							[cumplimiento] = ?,
							[vencimiento] = ?,
							[observaciones] = ?,
							[comentario] = [comentario] + ?
						WHERE
							[transacciones].[id_transaccion] = ?;";

				$params = array
				(
					array($this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
					array($this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
					array($this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096)),
					array($this->generaComentario($usuario, 'M'), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(1024)),
					array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
				);

				$this->conn->executeQuery($tsql, $params);
			}
			else
			{
				$tsql = "INSERT INTO [dbo].[transacciones]
					(
						  [tipo_transaccion]
						, [fecha]
						, [cumplimiento]
						, [vencimiento]
						, [id_obra]
						, [id_concepto]
						, [comentario]
						, [observaciones]
					)
					VALUES
					(
						?,
						?,
						?,
						?,
						?,
						?,
						?,
						?
					);";

				$params = array(
					array(self::TIPO_TRANSACCION, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
					array($this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
					array($this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
					array($this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($this->getIDConceptoRaiz(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($this->generaComentario($usuario, 'I'), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(1024)),
					array($this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096)),
				);

				$this->id_transaccion = $this->conn->executeQueryGetId($tsql, $params);

				$this->setNumeroFolio($this->getNumeroFolioFromDb());
			}

			$errores = $this->guardaConceptos();

			if (count($errores))
			{
				throw new Exception('Ocurrio un error al guardar los conceptos');
			}

            $this->actualizaTotales();

			$this->conn->commitTransaction();

			return $errores;
		}
		catch(Exception $e)
		{
			$this->conn->rollbackTransaction();
			$e->errors = $errores;
			throw $e;
		}

	}

	/**
	 * Obtiene el numero de folio de la transaccion desde la base de datos
	 * con una nueva consulta
	 *
	 */
	private function getNumeroFolioFromDb()
	{
		$sql = "SELECT [numero_folio] FROM [dbo].[transacciones] WHERE [id_transaccion] = ?";

		$params = array(
			array($this->id_transaccion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
		);

		$folio = $this->conn->executeQuery($sql, $params);

		return $folio[0]->numero_folio;
	}

	/**
     * Guarda los conceptos definidos para la transaccion
     *
	 * @return array
     */
	private function guardaConceptos()
	{
		$errores = array();

		foreach ($this->conceptos as $concepto)
		{
			try
			{
				$id_concepto = $concepto['id_concepto'];

                // Limpia y valida la cantidad propuesta
                $precio = str_replace(',', '', $concepto['precio']);

                $cantidad = str_replace(',', '', $concepto['cantidad']);

                $es_valido = preg_match('/^-?\d+(\.\d+)?$/', $precio);

                // Si el precio no es valido
                if ( ! $es_valido)
                {
                    throw new Exception("El precio ingresado no es correcto.");
                }

                $es_valido = preg_match('/^-?\d+(\.\d+)?$/', $cantidad);

                // Si la cantidad no es valida
                if ( ! $es_valido)
                {
                    throw new Exception("La cantidad ingresada no es correcta.");
                }

                $importe = $cantidad * $precio;
                $importe = floor($importe * 100) / 100;

                // Identifica si el item ya existe en la transaccion
				$sql = "SELECT 1 AS [existe]
						FROM
							[dbo].[items]
						WHERE
							[items].[id_transaccion] = ?
								AND
							[items].[id_concepto] = ?";

				$params = array(
					array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($id_concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
				);

				$item = $this->conn->executeQuery($sql, $params);

				$existe = false;

				if (count($item) > 0)
				{
					$existe = true;
				}

				// Si el item no existe se registra
				if ( ! $existe)
				{
					if ($precio == 0 || $cantidad == 0)
					{
						continue;
					}

					$sql = "INSERT INTO [dbo].[items]
							(
								[id_transaccion],
								[id_concepto],
								[cantidad],
								[precio_unitario],
								[importe]
							)
							VALUES (?, ?, ?, ?, ?);";

					$params = array(
						array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
						array($id_concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
						array($cantidad, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT),
						array($precio, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT),
						array($importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT),
					);

					$this->conn->executeQuery($sql, $params);

					continue;
				}

				// Si el item existe se actualiza
				if ($precio != 0 && $cantidad != 0)
				{
					$sql = "UPDATE
								[dbo].[items]
							SET
                                  [cantidad] = ?,
                                  [precio_unitario] = ?,
                                  [importe] = ?
							WHERE
								[id_transaccion] = ?
									AND
								[id_concepto] = ?;";

					$params = array(
						array($cantidad, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT),
						array($precio, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT),
                        array($importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT),
						array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
						array($id_concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					);

					$this->conn->executeQuery($sql, $params);

					continue;
				}

				// Si el item existe y su cantidad es 0 se elimina
				$sql = "DELETE
							[dbo].[items]
						WHERE
							[id_transaccion] = ?
								AND
							[id_concepto] = ?;";

				$params = array(
					array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($id_concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
				);

				$this->conn->executeQuery($sql, $params);
			}
			catch (Exception $e)
			{
				$errores[] = array
				(
					'id_concepto' => $id_concepto,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
					'message' => $e->getMessage()
				);
			}
		}

		return $errores;
	}

    /**
     * Actualiza los totales de la transaccion
     * de acuerdo a los items que tenga
     */
    private function actualizaTotales()
    {
        $sql = "UPDATE [dbo].[transacciones]
                SET
                      [transacciones].[monto] = ISNULL([suma_importes].[subtotal], 0) + ROUND(ISNULL([suma_importes].[subtotal], 0) * ([obras].[iva] / 100), 2, 1)
                    , [transacciones].[impuesto] = ROUND(ISNULL([suma_importes].[subtotal], 0) * ([obras].[iva] / 100), 2, 1)
                FROM
                    [dbo].[transacciones]
                INNER JOIN
                    [dbo].[obras]
                ON
                    [transacciones].[id_obra] = [obras].[id_obra]
                LEFT OUTER JOIN
                (
                    SELECT
                          [items].[id_transaccion]
                        , SUM([items].[importe]) AS [subtotal]
                    FROM
                        [dbo].[items]
                    GROUP BY
                        [items].[id_transaccion]
                ) AS [suma_importes]
                ON
                    [transacciones].[id_transaccion] = [suma_importes].[id_transaccion]
                WHERE
                    [transacciones].[id_transaccion] = ?";

        $params = array(
            array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
        );

        $this->conn->executeQuery($sql, $params);
    }

    /**
     * Obtiene los totales de la transaccion
     *
     * @return mixed
     */
    public function getTotales() {

        $tsql = "SELECT
                      [transacciones].[id_transaccion]
                    , [suma_importes].[subtotal]
                    , [transacciones].[impuesto]
                    , [transacciones].[monto]
                FROM
                    [dbo].[transacciones]
                LEFT OUTER JOIN
                (
                    SELECT
                          [items].[id_transaccion]
                        , SUM([items].[importe]) AS [subtotal]
                    FROM
                        [items]
                    GROUP BY
                        [items].[id_transaccion]
                ) AS [suma_importes]
                ON
                    [transacciones].[id_transaccion] = [suma_importes].[id_transaccion]
                WHERE
                    [transacciones].[id_transaccion] = ?";

        $params = array(
            array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
        );

        $totales = $this->conn->executeQuery($tsql, $params);

        return $totales;
    }

	/**
	 * @return int
     */
	private function getIDConceptoRaiz()
	{
		return $this->id_concepto;
	}

	/**
	 * @param $id_concepto
     */
	private function setIDConceptoRaiz($id_concepto)
	{
		$this->id_concepto = $id_concepto;
	}

	/**
	 * @param array $conceptos
     */
	public function setConceptos(Array $conceptos)
	{
		$this->conceptos = $conceptos;
	}

    /**
     * @return mixed
     */
    public function getFechaInicio()
	{
		return $this->fecha_inicio;
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
		
		$this->fecha_inicio = $fecha;
	}

	/**
	 * @return mixed
     */
	public function getFechaTermino()
	{
		return $this->fecha_termino;
	}

	/**
	 * @param $fecha
	 * @throws Exception
     */
	public function setFechaTermino( $fecha )
	{
		if ( ! $this->fechaEsValida( $fecha ))
		{
			throw new Exception("El formato de fecha termino es incorrecto.");
		}
		
		$this->fecha_termino = $fecha;
	}

	/**
	 * @return mixed
     */
	public function getConceptoRaiz()
	{
		return $this->concepto_raiz;
	}

	/**
	 * @return mixed
     */
	public function getConceptos()
	{
		$tsql = "DECLARE
				@NivelRaiz VARCHAR(300) = '';

				SELECT
					@NivelRaiz = [nivel]
				FROM
					[dbo].[conceptos]
				WHERE
					[id_obra] = ?
						AND
					[id_concepto] = ?;

				SELECT
					  [conceptos].[id_concepto]
					, [conceptos].[nivel]
					, (LEN([conceptos].[nivel]) / 4) AS [numero_nivel]
					, [conceptos].[clave_concepto]
					, [conceptos].[descripcion] AS [descripcion]
					, ISNULL([conceptos].[unidad], '') AS [unidad]
					, IIF([conceptos].[concepto_medible] > 0, 1, 0) AS [es_actividad]
					, [conceptos].[cantidad_presupuestada]
					, [conceptos].[precio_unitario]
					, [conceptos].[monto_presupuestado]
					, ISNULL([items].[cantidad], 0) AS [cantidad]
					, ISNULL([items].[precio_unitario], 0) AS [precio]
					, ISNULL([items].[importe], 0) AS [importe]
				FROM
					[dbo].[conceptos]
				LEFT OUTER JOIN
					[dbo].[items]
				ON
					[conceptos].[id_concepto] = [items].[id_concepto]
						AND
					[items].[id_transaccion] = ?
				WHERE
					EXISTS
					(
						SELECT 1
						FROM
							[dbo].[conceptos] AS [conceptos_1]
						WHERE
							[conceptos_1].[nivel] LIKE ([conceptos].[nivel] + '%')
								AND
							[conceptos_1].[id_obra] = [conceptos].[id_obra]
								AND
							[conceptos_1].[concepto_medible] > 0
					)
						AND
					[conceptos].[id_obra] = ?
						AND
					LEFT([conceptos].[nivel], LEN(@NivelRaiz)) = @NivelRaiz
				ORDER BY
					[conceptos].[nivel];";

	    $params = array
		(
	        array($this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array($this->getIDConceptoRaiz(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
			array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
			array($this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	    );

	    $conceptos = $this->conn->executeQuery($tsql, $params);

	    return $conceptos;
	}

	/**
	 * @param Obra $obra
	 * @param $id_concepto_raiz
	 * @return array
	 * @throws DBServerStatementExecutionException
     */
	public static function getConceptosNuevoAvance(Obra $obra, $id_concepto_raiz)
	{
		$tsql = "DECLARE
					@NivelRaiz VARCHAR(300) = '';

				SELECT
					@NivelRaiz = [nivel]
				FROM
					[dbo].[conceptos]
				WHERE
					[id_obra] = ?
						AND
					[id_concepto] = ?;

				SELECT
					  [conceptos].[id_concepto]
					, [conceptos].[nivel]
					, (LEN([conceptos].[nivel]) / 4) AS [numero_nivel]
					, [conceptos].[clave_concepto]
					, [conceptos].[descripcion] AS [descripcion]
					, ISNULL([conceptos].[unidad], '') AS [unidad]
					, IIF( [conceptos].[concepto_medible] > 0, 1, 0) AS [es_actividad]
					, [conceptos].[cantidad_presupuestada]
					, [conceptos].[precio_unitario]
					, [conceptos].[monto_presupuestado]
				FROM
					[dbo].[conceptos]
				WHERE
					EXISTS
					(
						SELECT 1
						FROM
							[dbo].[conceptos] AS [conceptos_1]
						WHERE
							[conceptos_1].[nivel] LIKE ([conceptos].[nivel] + '%')
								AND
							[conceptos_1].[id_obra] = [conceptos].[id_obra]
								AND
							[conceptos_1].[concepto_medible] > 0
					)
						AND
					[conceptos].[id_obra] = ?
						AND
					LEFT([conceptos].[nivel], LEN(@NivelRaiz)) = @NivelRaiz
				ORDER BY
					[conceptos].[nivel];";

	    $params = array
		(
	        array($obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array($id_concepto_raiz, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
			array($obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	    );

	    $conceptos = $obra->getConn()->executeQuery($tsql, $params);

	    return $conceptos;
	}

	/**
	 * @param Obra $obra
	 * @return array
	 * @throws DBServerStatementExecutionException
     */
	public static function getFoliosTransaccion(Obra $obra)
	{
		$tsql = '{call [SAO].[uspListaTransacciones](?, ?)}';

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