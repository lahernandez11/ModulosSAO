<?php
require_once 'models/TransaccionSAO.class.php';
require_once 'models/Obra.class.php';

class Cobranza extends TransaccionSAO
{
	const TIPO_TRANSACCION = 104;

	private $id_estimacion_obra;
	private $conceptos = array();
	private $folio_factura;

    /**
     *
     */
    public function __construct()
    {
		$params = func_get_args();

		switch (func_num_args())
        {
			case 6:
				call_user_func_array(array($this, "instanceFromDefault"), $params);
				break;

			case 2:
				call_user_func_array(array($this, "instanceFromID"), $params);
				break;
		}
	}

    /**
     * @param Obra $obra
     * @param $IDEstimacionObra
     * @param $fecha
     * @param $folio_factura
     * @param $observaciones
     * @param array $conceptos
     */
    private function instanceFromDefault(
        Obra $obra,
        $IDEstimacionObra,
        $fecha,
        $folio_factura,
		$observaciones,
        Array $conceptos
    )
    {
		parent::__construct($obra, self::TIPO_TRANSACCION, $fecha, $observaciones);

		$this->id_estimacion_obra = $IDEstimacionObra;
		$this->_observaciones = $observaciones;
		$this->setConceptos($conceptos);
		$this->setFolioFactura($folio_factura);
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
		if ( ! empty($this->id_transaccion))
        {
			$tsql = "{call [Cobranza].[uspActualizaDatosGenerales]( ?, ?, ?, ?, ? )}";

		    $params = array(
		        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
		        array($this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
		        array($this->getReferencia(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(64)),
		        array($this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096)),
		        array($this->folio_factura, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(20)),
		        array($usuario->getUsername(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16)),
		    );

		    $this->conn->executeSP($tsql, $params);
		}
        else
        {
			$tsql = "{call [Cobranza].[uspRegistraTransaccion]( ?, ?, ?, ?, ?, ? )}";

		    $params = array(
		        array($this->getIDEstimacionObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
		        array($this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
		        array($this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX')),
		        array($usuario->getUsername(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16)),
		        array(&$this->id_transaccion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT),
		        array(&$this->_numeroFolio, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT),
		    );

		    $this->conn->executeSP($tsql, $params);
		}

		return $errores = $this->guardaConceptos();
	}

    /**
     * @return array
     */
    private function guardaConceptos()
    {
		$errores = array();

		$tsql = "{call [Cobranza].[uspGuardaConcepto]( ?, ?, ?, ? )}";

		foreach ($this->conceptos as $concepto)
        {
			try
            {
				// Limpia y valida la cantidad y precio
				$concepto['cantidad'] = (float) str_replace(',', '', $concepto['cantidad']);
				$concepto['precio'] = (float) str_replace(',', '', $concepto['precio']);

				// Si el importe no es valido agrega el concepto con error
				if ( ! $this->esImporte($concepto['cantidad']) || ! $this->esImporte($concepto['precio']))
                {
					throw new Exception("El numero ingresado no es correcto");
				}

				if ($concepto['cantidad'] === 0.0)
                {
					continue;
				}

				$params = array(
					array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($concepto['IDConcepto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($concepto['cantidad'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT),
					array($concepto['precio'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4)),
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
     *
     */
    public function eliminaTransaccion()
    {
		$tsql = "{call [Cobranza].[uspEliminaTransaccion]( ? )}";

		$params = array(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $totales = $this->conn->executeSP($tsql, $params);
	}

    /**
     * @return mixed
     */
    public function getConceptos()
    {
		$tsql = '{call [Cobranza].[uspConceptosCobranza]( ?, ?, ? )}';

		$params = array(
	        array($this->getIDObra() , SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array($this->getIDEstimacionObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	    );

	    $conceptos = $this->conn->executeSP($tsql, $params);

		return $conceptos;
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
    public function getTotales()
    {
		$tsql = "{call [Cobranza].[uspTotalesTransaccion]( ? )}";

		$params = array(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $totales = $this->conn->executeSP($tsql, $params);

	    return $totales;
	}

    /**
     * @param $folio
     */
    public function setFolioFactura($folio)
    {
		$this->folio_factura = $folio;
	}

    /**
     * @return mixed
     */
    public function getFolioFactura()
    {
		return $this->folio_factura;
	}

    /**
     *
     */
    public function setDatosGenerales()
    {
		$tsql = '{call [Cobranza].[uspDatosGenerales]( ? )}';

		$params = array(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $datos = $this->conn->executeSP($tsql, $params);

		$this->_numeroFolio = $datos[0]->NumeroFolio;
		$this->_observaciones = $datos[0]->Observaciones;
		$this->referencia = $datos[0]->Referencia;
		$this->id_estimacion_obra = $datos[0]->IDEstimacionObra;
		$this->folio_factura = $datos[0]->FolioFactura;
		$this->setFecha($datos[0]->Fecha);
	}

    /**
     * @return mixed
     */
    public function getIDEstimacionObra()
    {
		return $this->id_estimacion_obra;
	}

    /**
     * @param $importe
     * @throws Exception
     */
    public function setImporteProgramado($importe)
    {
		if ( ! $this->esImporte($importe))
        {
			throw new Exception("Importe Incorrecto");
		}

        $tsql = "{call [Cobranza].[uspActualizaTotales]( ?, ? )}";

        $params = array(
            array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
            array($importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4)),
        );

        $this->conn->executeSP($tsql, $params);
	}

    /**
     * @param $importe
     * @throws Exception
     */
    public function setImporteDevolucion($importe)
    {
		if ( ! $this->esImporte($importe))
        {
			throw new Exception("Importe Incorrecto");
		}

        $tsql = "{call [Cobranza].[uspActualizaTotales]( ?, ?, ? )}";

        $params = array(
            array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
            null,
            array($importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4)),
        );

        $this->conn->executeSP($tsql, $params);
	}

    /**
     * @param $importe
     * @throws Exception
     */
    public function setImporteRetencionObraNoEjecutada($importe)
    {
		if ( ! $this->esImporte($importe))
        {
			throw new Exception("Importe Incorrecto");
		}

        $tsql = "{call [Cobranza].[uspActualizaTotales]( ?, ?, ?, ? )}";

        $params = array(
            array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
            null,
            null,
            array($importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4)),
        );

        $this->conn->executeSP($tsql, $params);
	}

    /**
     * @param $importe
     * @throws Exception
     */
    public function setImporteAmortizacionAnticipo($importe)
    {
		if ( ! $this->esImporte($importe))
        {
			throw new Exception("Importe Incorrecto");
		}

        $tsql = "{call [Cobranza].[uspActualizaTotales]( ?, ?, ?, ?, ? )}";

        $params = array(
            array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
            null,
            null,
            null,
            array($importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4)),
        );

        $this->conn->executeSP($tsql, $params);
	}

    /**
     * @param $importe
     * @throws Exception
     */
    public function setImporteIVAAnticipo($importe)
    {
		if ( ! $this->esImporte($importe))
        {
			throw new Exception("Importe Incorrecto");
		}

        $tsql = "{call [Cobranza].[uspActualizaTotales]( ?, ?, ?, ?, ?, ? )}";

        $params = array(
            array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
            null,
            null,
            null,
            null,
            array($importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4)),
        );

        $this->conn->executeSP($tsql, $params);
	}

    /**
     * @param $importe
     * @throws Exception
     */
    public function setImporteInspeccionVigilancia($importe)
    {
		if ( ! $this->esImporte($importe))
        {
			throw new Exception("Importe Incorrecto");
		}

        $tsql = "{call [Cobranza].[uspActualizaTotales]( ?, ?, ?, ?, ?, ?, ? )}";

        $params = array(
            array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
            null,
            null,
            null,
            null,
            null,
            array($importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4)),
        );

        $this->conn->executeSP($tsql, $params);
	}

    /**
     * @param $importe
     * @throws Exception
     */
    public function setImporteCMIC($importe)
    {
		if ( ! $this->esImporte($importe))
        {
			throw new Exception("Importe Incorrecto");
		}

        $tsql = "{call [Cobranza].[uspActualizaTotales]( ?, ?, ?, ?, ?, ?, ?, ? )}";

        $params = array(
            array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
            null,
            null,
            null,
            null,
            null,
            null,
            array($importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4)),
        );

        $this->conn->executeSP($tsql, $params);
	}

    /**
     * @param Obra $obra
     * @return array
     * @throws DBServerStatementExecutionException
     */
    public static function getFoliosTransaccion(Obra $obra)
    {
		$tsql = '{call [Cobranza].[uspListaTransacciones]( ? )}';

		$params = array(
	        array($obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $folios = $obra->getConn()->executeSP($tsql, $params);

		return $folios;
	}

    /**
     * @param Obra $obra
     * @param $id_estimacion_obra
     * @return array
     * @throws DBServerStatementExecutionException
     */
    public static function getConceptosNuevaTransaccion(Obra $obra, $id_estimacion_obra)
    {
		$tsql = '{call [Cobranza].[uspConceptosCobranza]( ?, ? )}';

		$params = array(
	        array($obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array($id_estimacion_obra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	    );

	    $conceptos = $obra->getConn()->executeSP($tsql, $params);

		return $conceptos;
	}

}
