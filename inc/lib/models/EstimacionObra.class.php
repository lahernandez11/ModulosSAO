<?php
require_once 'models/TransaccionSAO.class.php';

class EstimacionObra extends TransaccionSAO {

	const TIPO_TRANSACCION = 103;

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
				call_user_func_array(array($this, "instanceFromDefault"), $params);
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
     * @param $referencia
     * @param array $conceptos
     * @throws Exception
     */
    private function instanceFromDefault(
        Obra $obra,
        $fecha,
        $fechaInicio,
		$fechaTermino,
        $observaciones,
        $referencia,
        Array $conceptos
    )
    {
		parent::__construct($obra, self::TIPO_TRANSACCION, $fecha, $observaciones);

		$this->referencia = $referencia;
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

    /**
     * @param Usuario $usuario
     * @return array
     */
    public function guardaTransaccion(Usuario $usuario)
    {
		if ( ! empty( $this->id_transaccion))
        {
			$tsql = "{call [EstimacionObra].[uspActualizaDatosGenerales]( ?, ?, ?, ?, ?, ?, ? )}";

		    $params = array(
		        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
		        array($this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
		        array($this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
		        array($this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
		        array($this->getReferencia(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(64)),
		        array($this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096)),
		        array($usuario->getUsername(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16)),
		    );

		    $this->conn->executeSP($tsql, $params);
		}
        else
        {
			$tsql = "{call [EstimacionObra].[uspRegistraTransaccion]( ?, ?, ?, ?, ?, ?, ?, ?, ? )}";

		    $params = array(
		        array($this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
		        array($this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
		       	array($this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
		        array($this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
		        array($this->getReferencia(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(64)),
		        array($this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096)),
		        array($usuario->getUsername(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16)),
		        array(&$this->id_transaccion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT),
		        array(&$this->_numeroFolio, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT),
		    );

		    $this->conn->executeSP($tsql, $params);
		}

		$errores = array();

		$tsql = "{call [EstimacionObra].[uspEstimaConcepto]( ?, ?, ?, ?, ? )}";

		foreach ($this->conceptos as $concepto)
        {
			try
            {
				// Limpia y valida la cantidad y precio
				$concepto['cantidad'] = str_replace(',', '', $concepto['cantidad']);
				$concepto['precio'] = str_replace(',', '', $concepto['precio']);

				// Si el importe no es valido agrega el concepto con error
				if ( ! $this->esImporte($concepto['cantidad']) || ! $this->esImporte($concepto['precio']))
                {
					throw new Exception("El numero ingresado no es correcto");
				}

				$params = array(
					array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($concepto['IDConcepto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($concepto['cantidad'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT),
					array($concepto['precio'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4)),
					array($concepto['cumplido'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT),
				);

				$this->conn->executeSP($tsql, $params);
			}
            catch (Exception $e)
            {
				$errores[] = array(
					'IDConcepto' => $concepto['IDConcepto'],
					'cantidad'   => $concepto['cantidad'],
					'message' 	 => $e->getMessage()
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
    public function setFechaTermino($fecha)
    {
		if ( ! $this->fechaEsValida($fecha))
        {
            throw new Exception("El formato de fecha término es incorrecto.");
        }

		$this->fecha_termino = $fecha;
	}

	// @override
    /**
     * @throws Exception
     */
    protected function setDatosGenerales()
    {
		parent::setDatosGenerales();

		$tsql = "{call [EstimacionObra].[uspDatosGenerales]( ? )}";

		$params = array(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $datos = $this->conn->executeSP($tsql, $params);
	    $this->setFechaInicio($datos[0]->FechaInicio);
	    $this->setFechaTermino($datos[0]->FechaTermino);
	}

    /**
     * @return mixed
     */
    public function getConceptos()
    {
		$tsql = "{call [EstimacionObra].[uspConceptosEstimacion]( ?, ? )}";

	    $params = array(
	        array($this->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $conceptos = $this->conn->executeSP($tsql, $params);

	    return $conceptos;
	}

    /**
     * @return mixed
     */
    public function getTotalesTransaccion()
    {
		$tsql = "{call [EstimacionObra].[uspTotalesTransaccion]( ? )}";

		$params = array(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $totales = $this->conn->executeSP($tsql, $params);

	    return $totales;
	}

    /**
     * @param Obra $obra
     * @return array
     * @throws DBServerStatementExecutionException
     */
    public static function getConceptosNuevaEstimacion(Obra $obra)
    {
		$tsql = "{call [EstimacionObra].[uspConceptosEstimacion]( ? )}";

	    $params = array(
	        array($obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
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
		$tsql = '{call [EstimacionObra].[uspListaFolios]( ? )}';

		$params = array(
	        array($obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $foliosTran = $obra->getConn()->executeSP($tsql, $params);

		return $foliosTran;
	}

    /**
     * @param Obra $obra
     * @param null $tipo_transaccion
     * @return array
     */
    public static function getListaTransacciones(Obra $obra, $tipo_transaccion = null)
    {
		return parent::getListaTransacciones( $obra, self::TIPO_TRANSACCION );
	}

    /**
     * @return string
     */
    public function __toString()
    {
		$data  = parent::__toString() . ', ';
		$data .= "FechaInicio: {$this->fecha_inicio}, ";
		$data .= "FechaTermino: {$this->fecha_termino}, ";
		$data .= "Referencia: {$this->referencia}, ";

		return $data;
	}

}
