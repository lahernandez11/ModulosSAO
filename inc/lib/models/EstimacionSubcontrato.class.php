<?php
require_once 'models/TransaccionSAO.class.php';
require_once 'models/Empresa.class.php';
require_once 'models/Moneda.class.php';
require_once 'models/Subcontrato.class.php';
require_once 'models/CargoMaterial.class.php';
require_once 'models/EstimacionRetencion.class.php';
require_once 'models/EstimacionDescuentoMaterial.class.php';
require_once 'models/EstimacionRetencionLiberacion.class.php';
require_once 'models/Exceptions/TransaccionEstaCerradaExeption.php';

class EstimacionSubcontrato extends TransaccionSAO {

	const TIPO_TRANSACCION = 52;
	const CARACTER_OPERACION_REGISTRO   = 'I';
	const CARACTER_OPERACION_APROBACION = 'A';
	const ESTADO_CAPTURADA = 0;
	const ESTADO_APROBADA  = 1;
	const ESTADO_REVISADA  = 2;

	public $subcontrato;
	public $empresa;
	public $moneda;

	private $fecha_inicio;
	private $fecha_termino;
	private $numero_folio_consecutivo;
	private $pct_anticipo = 0;
	private $pct_fondo_garantia = 0;
	private $pct_iva = 0;

	private $conceptos = array();

	private $suma_importes 		 	 = 0;
	private $amortizacion_anticipo 	 = 0;
	private $fondo_garantia 		 = 0;
	private $subtotal 		 		 = 0;
	private $iva 		 			 = 0;
	private $total_estimacion 		 = 0;
	public  $descuentos 		 	 = array();
	public  $retenciones 		 	 = array();
	public  $liberaciones 		 	 = array();
	private $retencion_iva 		 	 = 0;
	private $anticipo_liberar 		 = 0;
	private $retencion_liberada 	 = 0;
	private $total_pagar 		 	 = 0;

	private $estimado_anterior       = 0;
	private $anticipo_anterior 		 = 0;
	private $fondo_garantia_anterior = 0;
	private $iva_anterior 			 = 0;
	private $retencion_iva_anterior  = 0;
	private $descuento_anterior      = 0;
	private $retencion_anterior 	 = 0;
	private $liberado_anterior		 = 0;
	private $anticipo_liberar_anterior = 0;

    /**
     *
     */
    public function __construct()
    {
		$params = func_get_args();

		switch ( func_num_args() )
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
     * @param Subcontrato $subcontrato
     * @param $fecha
     * @param $fechaInicio
     * @param $fechaTermino
     * @param $observaciones
     * @param array $conceptos
     * @throws Exception
     */
    private function instaceFromDefault(
		Obra $obra,
        Subcontrato $subcontrato,
        $fecha,
        $fechaInicio,
        $fechaTermino,
        $observaciones,
		Array $conceptos
    )
    {
		parent::__construct($obra, self::TIPO_TRANSACCION, $fecha, $observaciones);

		$this->subcontrato = $subcontrato;
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

		$this->registraTransaccionAdicional();
		$this->setDatosGenerales();
	}

    /**
     * Crea el registro adicional de la estimacion en la tabla
     * Estimaciones para una transaccion que fue registrada en el SAO
     */
    private function registraTransaccionAdicional()
    {
		if ( ! $this->existeRegistroEstimacion())
        {
			$tsql = "INSERT INTO [SubcontratosEstimaciones].[Estimaciones]
					(
                        [IDEstimacion],
                        [NumeroFolioConsecutivo]
					)
					VALUES ( ?, ? );";

		    $params = array(
		        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
		        array($this->getNumeroFolio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
		    );

		   	$this->conn->executeQuery($tsql, $params);
		}
	}

    /**
     * Verifica si la transaccion tiene un registro asociado en la tabla adicional Estimaciones
     *
     * @return bool
     */
    private function existeRegistroEstimacion()
    {
		$tsql = "SELECT 1
				 FROM
				 	[SubcontratosEstimaciones].[Estimaciones]
				 WHERE
				 	[IDEstimacion] = ?";

	    $params = array(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $res = $this->conn->executeQuery($tsql, $params);

	    if (count($res) > 0)
        {
            return true;
        }

        return false;
	}

    /**
     * Establece los datos generales de la transaccion
     *
     * @throws Exception
     */
    protected function setDatosGenerales()
    {
		parent::setDatosGenerales();

		$tsql = "SELECT
					  [transacciones].[id_empresa]
					, [transacciones].[numero_folio]
					, [Estimaciones].[NumeroFolioConsecutivo]
					, [transacciones].[cumplimiento]
					, [transacciones].[vencimiento]
					, [transacciones].[observaciones]
					, [transacciones].[id_antecedente]
					, [transacciones].[id_moneda]
					, [transacciones].[anticipo] / 100 AS [anticipo]
					, [Estimaciones].[PorcentajeFondoGarantia] / 100 AS [PorcentajeFondoGarantia]
					, 
					IIF(
						[transacciones].[monto] - [transacciones].[impuesto] = 0, 0,
						(
							[transacciones].[impuesto]
								/
							([transacciones].[monto] - [transacciones].[impuesto])
						)
					) AS [porcentaje_iva]
				FROM
					[dbo].[transacciones]
				LEFT OUTER JOIN
					[SubcontratosEstimaciones].[Estimaciones]
					ON
						[transacciones].[id_transaccion] = [Estimaciones].[IDEstimacion]
				WHERE
					[transacciones].[tipo_transaccion] = 52
						AND
					[transacciones].[id_transaccion] = ?";

		$params = array(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $datos = $this->conn->executeSP($tsql, $params);

	    $this->subcontrato = new Subcontrato($this->obra, $datos[0]->id_antecedente);
	    $this->empresa = $this->subcontrato->empresa;
	    $this->moneda = new Moneda($this->obra, $datos[0]->id_moneda);

	    $this->numero_folio_consecutivo = $datos[0]->NumeroFolioConsecutivo;
	    $this->fecha_inicio = $datos[0]->cumplimiento;
	    $this->fecha_termino = $datos[0]->vencimiento;
	    $this->pct_anticipo = $datos[0]->anticipo;
	    $this->pct_fondo_garantia = $datos[0]->PorcentajeFondoGarantia;
	    $this->pct_iva = $datos[0]->porcentaje_iva;

	    $this->descuentos = EstimacionDescuentoMaterial::getInstance($this);
	    $this->retenciones = EstimacionRetencion::getInstance($this);
	    $this->liberaciones = EstimacionRetencionLiberacion::getInstance($this);

	    $this->getTotalesTransaccion();
	}

    /**
     * @return mixed
     */
    public function getConceptosEstimacion()
    {
		$tsql = "{call [SubcontratosEstimaciones].[uspConceptosEstimacion]( ?, ?, ?, ? )}";

	    $params = array(
	        array($this->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array(0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT),
	        array(0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT)
	    );

	    $conceptos = $this->conn->executeSP($tsql, $params);

	    return $conceptos;
	}

    /**
     * Guarda los cambios o crea una transaccion
     *
     * @param Usuario $usuario
     * @return array
     * @throws Exception
     */
    public function guardaTransaccion(Usuario $usuario)
    {
		if ($this->estaCerrada())
        {
            throw new TransaccionEstaCerradaExeption();
		}

        try
        {
            $this->conn->beginTransaction();

            if ( ! empty($this->id_transaccion))
            {
                $tsql = "{call [SubcontratosEstimaciones].[uspActualizaDatosGenerales]( ?, ?, ?, ?, ?, ? )}";

                $params = array(
                    array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
                    array($this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
                    array($this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
                    array($this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
                    array($this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096)),
                    array($usuario->getUsername(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16)),
                );

                $this->conn->executeSP($tsql, $params);
            }
            else
            {
                $tsql = "{call [SubcontratosEstimaciones].[uspRegistraTransaccion]( ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )}";

                $params = array(
                    array($this->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
                    array($this->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
                    array($this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
                    array($this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
                    array($this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
                    array($this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096)),
                    array($usuario->getUsername(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16)),
                    array(&$this->id_transaccion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT),
                    array(&$this->_numeroFolio, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT),
                    array(&$this->numero_folio_consecutivo, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT),
                );

                $this->conn->executeSP($tsql, $params);

                $this->pct_anticipo = $this->subcontrato->getPorcentajeAnticipo();
                $this->pct_fondo_garantia = $this->subcontrato->getPorcentajeRetencion();
                $this->empresa = $this->subcontrato->empresa;
            }

            $errores = $this->guardaConceptos();

            if (count($errores))
            {
                throw new Exception('Ocurrio un error al guardar los conceptos');
            }

            $this->calculaImportes();

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
     * Guarda los conceptos que se estiman en esta transaccion
     *
     * @return array
     */
    private function guardaConceptos()
    {
		$errores = array();

		$tsql = "{call [SubcontratosEstimaciones].[uspEstimaConcepto]( ?, ?, ?, ? )}";

		$this->suma_importes = 0;

		foreach ($this->conceptos as $concepto)
        {
			try
            {
				// Limpia y valida el importe estimado
				$concepto['importeEstimado'] = str_replace(',', '', $concepto['importeEstimado']);

				// Si el importe no es valido agrega el concepto con error
				if ( ! $this->esImporte($concepto['importeEstimado']))
                {
					throw new Exception("El numero ingresado no es correcto");
				}

				$params = array(
					array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($concepto['IDConceptoContrato'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($concepto['IDConceptoDestino'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($concepto['importeEstimado'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4))
				);

				$this->conn->executeSP($tsql, $params);
				
				$this->suma_importes += $concepto['importeEstimado'];
			}
            catch(Exception $e)
            {
				$errores[] = array(
					'IDConceptoContrato' => $concepto['IDConceptoContrato'],
					'importeEstimado' => $concepto['importeEstimado'],
					'message' => $e->getMessage()
				);
			}
		}

		return $errores;
	}

    /**
     * Actualiza los importes de la transaccion.
     * Calcula el subtotal, iva y total con los descuentos de amortizacion y fondo de garantia
     *
     */
    private function calculaImportes()
    {
        // Calculo del importe de amortizacion de anticipo
		$this->amortizacion_anticipo = $this->pct_anticipo * $this->suma_importes;

        // Calculo del importe de fondo de garantia
		$this->fondo_garantia = $this->pct_fondo_garantia * $this->suma_importes;

        // Calculo del subtotal
		$this->subtotal = $this->suma_importes;

        // Descuento de amortizacion de anticipo antes de iva
		$this->subtotal -= $this->amortizacion_anticipo;

        // Se calcula el iva y total
        $this->iva = $this->subtotal * $this->subcontrato->getPorcentajeIVA();
        $this->total = $this->subtotal + $this->iva;

        // Se descuenta el fondo de garantia despues de iva
//        $this->total -= $this->fondo_garantia;

        $tsql = "UPDATE
					[dbo].[transacciones]
				SET
					[impuesto]    = ?,
					[monto]       = ?,
					[retencion]   = ?,
					[IVARetenido] = ?,
					[anticipo]    = ?
				WHERE
					[id_transaccion] = ?;";

        $params = array(
	        array($this->iva, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4)),
	        array($this->total, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4)),
            array(0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_REAL),
	        array($this->retencion_iva, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4)),
            array($this->pct_anticipo * 100, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_REAL),
            array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

        $this->conn->executeQuery($tsql, $params);

		$tsql = "UPDATE
					[SubcontratosEstimaciones].[Estimaciones]
				SET
				    [PorcentajeFondoGarantia] = ?,
				    [ImporteFondoGarantia] = ?,
					[ImporteAnticipoLiberar] = ?
				WHERE
					[IDEstimacion] = ?;";

	    $params = array(
            array($this->pct_fondo_garantia * 100, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4)),
            array($this->fondo_garantia, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4)),
	        array($this->anticipo_liberar, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4)),
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $this->conn->executeQuery($tsql, $params);
	}

    /**
     * Aprueba la transaccion
     *
     * @param Usuario $usuario
     * @throws Exception
     */
    public function setAprobada(Usuario $usuario)
    {
		if ($this->suma_importes == 0)
        {
			throw new Exception("La estimacion no tiene importe", 1);
		}

		if ($this->estado > self::ESTADO_CAPTURADA)
        {
			throw new Exception("La estimacion se encuentra aprobada.", 1);
		}

		try
        {
			$this->conn->beginTransaction();

			$tsql = "UPDATE [dbo].[transacciones]
					SET
						[comentario] = [comentario] + ?,
						[impreso] = 1,
						[saldo] = ?
					WHERE
						[id_transaccion] = ?;";

			$params = array(
		        array(self::generaComentario($usuario, self::CARACTER_OPERACION_APROBACION ), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(1024)),
		        array($this->total_estimacion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT),
		        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
		    );

		    $this->conn->executeQuery($tsql, $params);

			$tsql = "{call [dbo].[sp_aprobar_transaccion]( ? )}";

			$params = array(
		    	array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
		    );

			$this->conn->executeSP($tsql, $params);

			$tsql = "SELECT
						[estado]
					FROM
						[dbo].[transacciones]
					WHERE
						[id_transaccion] = ?;";

			$params = array(
		    	array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
		    );

			$data = $this->conn->executeQuery($tsql, $params);

			$this->estado = $data[0]->estado;

			$this->conn->commitTransaction();
		}
        catch(Exception $e)
        {
			$this->conn->rollbackTransaction();
			throw $e;
		}
	}

    /**
     * Revierte la aprobacion de esta transaccion
     *
     * @throws Exception
     */
    public function revierteAprobacion()
    {
        if ($this->estaRevisada())
        {
            throw new TransaccionEstaCerradaExeption();
        }

        $tsql = "{call [dbo].[sp_revertir_transaccion]( ? )}";

        $params = array(
            array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
        );

        $this->conn->executeSP($tsql, $params);

        $tsql = "SELECT
                    [estado]
                FROM
                    [dbo].[transacciones]
                WHERE
                    [id_transaccion] = ?;";

        $params = array(
            array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
        );

        $data = $this->conn->executeQuery($tsql, $params);

        $this->estado = $data[0]->estado;
	}

    /**
     * Elimina la transaccion
     *
     * @throws Exception
     */
    public function eliminaTransaccion()
    {
		if ($this->estaCerrada())
        {
            throw new TransaccionEstaCerradaExeption();
		}

		$tsql = "{call [SubcontratosEstimaciones].[uspEliminaTransaccion](?)}";

		$params = array(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $this->conn->executeSP($tsql, $params);
	}

    /**
     * @return bool
     */
    public function estaCerrada()
    {
        if ($this->estaRevisada() || $this->estaAprobada())
        {
            return true;
        }
        return false;
    }

    /**
     * Identifica si la transaccion esta aprobada
     *
     * @return bool
     */
    public function estaAprobada()
    {
		if ($this->estado >= self::ESTADO_APROBADA)
        {
            return true;
        }
        return false;
	}

    /**
     * Identifica si la transaccion ya esta revisada contra factura
     *
     * @return bool
     */
    public function estaRevisada()
    {
        if ($this->estado == self::ESTADO_REVISADA)
        {
            return true;
        }
        return false;
    }

    /**
     * Obtiene los conceptos a estimar para esta transaccion de acuerdo a un subcontrato
     *
     * @param int $soloConceptosEstimados
     * @return mixed
     */
    public function getConceptosEstimados($soloConceptosEstimados = 1)
    {
		$tsql = "{call [SubcontratosEstimaciones].[uspConceptosEstimacion]( ?, ?, ?, ? )}";

	    $params = array(
	        array($this->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array(1, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT),
	        array($soloConceptosEstimados, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT)
	    );

	    $conceptos = $this->conn->executeSP($tsql, $params);

	    return $conceptos;
	}

    /**
     * Agrega un descuento por prestamo de materiales en esta transaccion
     *
     * @param Material $material
     * @param $cantidad
     * @param $precio
     * @return EstimacionDescuentoMaterial
     * @throws Exception
     */
    public function addDescuento(Material $material, $cantidad, $precio)
    {
		if ($this->estaCerrada())
        {
            throw new TransaccionEstaCerradaExeption();
		}

		$descuento = new EstimacionDescuentoMaterial($this, $material);
		$descuento->setCantidad($cantidad);
		$descuento->setPrecio($precio);
		$descuento->save();
		$this->descuentos[] = $descuento;

		return $descuento;
	}

    /**
     * Agrega una retencion en esta transaccion
     *
     * @param RetencionTipo $tipo_retencion
     * @param $importe
     * @param $concepto
     * @return EstimacionRetencion
     * @throws Exception
     */
    public function addRetencion(RetencionTipo $tipo_retencion, $importe, $concepto)
    {
		$retencion = new EstimacionRetencion($this, $tipo_retencion, $importe, $concepto);
		$retencion->save();
		$this->retenciones[] = $retencion;

	    return $retencion;
	}

    /**
     * Libera importe retenido en esta transaccion
     *
     * @param $importe
     * @param $concepto
     * @param Usuario $usuario
     * @return EstimacionRetencionLiberacion
     * @throws Exception
     */
    public function addLiberacion($importe, $concepto, Usuario $usuario)
    {
		if ( ! $this->esImporte($importe))
        {
			throw new Exception("Importe Incorrecto");
		}

		$liberacion = new EstimacionRetencionLiberacion( $this, $importe, $concepto );
		$liberacion->save($usuario);
		$this->liberaciones[] = $liberacion;

		return $liberacion;
	}


    /**
     * Elimina una liberacion generada en estra transaccion
     *
     * @param $IDLiberacion
     * @return $this
     */
    public function eliminaLiberacion($IDLiberacion)
    {
		$tsql = "{call [SubcontratosEstimaciones].[uspEliminaLiberacionRetencion]( ?, ? )}";

	    $params = array(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array($IDLiberacion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $this->conn->executeSP($tsql, $params);

        return $this;
	}

    /**
     * Obtiene el importe retenido por liberar de la empresa de esta transaccion
     *
     * @return null
     */
    public function getImporteRetenidoPorLiberar()
    {
		$tsql = "{call [SubcontratosEstimaciones].[uspImporteRetenidoPorLiberar]( ?, ? )}";

		$importePorLiberar = null;

	    $params = array(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array(&$importePorLiberar, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_DECIMAL(19, 4))
	    );

	    $this->conn->executeSP($tsql, $params);

	    return $importePorLiberar;
	}

    /**
     * Obtiene los totales de esta transaccion
     *
     * @return array
     */
    public function getTotalesTransaccion()
    {
		$tsql = "{call [SubcontratosEstimaciones].[uspTotalesTransaccion]( ? )}";

		$params = array(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $totales = $this->conn->executeSP($tsql, $params, SQLSrvDBConn::FETCH_MODE_ARRAY);

	    if (count($totales) > 0)
        {
		    $this->suma_importes = $totales[0]['suma_importes'];
			$this->amortizacion_anticipo = $totales[0]['amortizacion_anticipo'];
			$this->fondo_garantia = $totales[0]['fondo_garantia'];
			$this->subtotal = $totales[0]['subtotal'];
			$this->iva = $totales[0]['iva'];
			$this->total_estimacion = $totales[0]['total'];
			$this->retencion_iva = $totales[0]['retencion_iva'];
			$this->anticipo_liberar = $totales[0]['anticipo_a_liberar'];
			$this->retencion_liberada = $totales[0]['retencion_liberada'];
			$this->total_pagar = $totales[0]['monto_a_pagar'];

			$this->estimado_anterior = $totales[0]['estimado_acumulado_anterior'];
			$this->anticipo_anterior = $totales[0]['amortizacion_anticipo_acumulado_anterior'];
			$this->fondo_garantia_anterior = $totales[0]['fondo_garantia_acumulado_anterior'];
			$this->iva_anterior = $totales[0]['iva_acumulado_anterior'];
			$this->retencion_iva_anterior = $totales[0]['iva_retenido_acumulado_anterior'];
			$this->descuento_anterior = $totales[0]['descuento_acumulado_anterior'];
			$this->retencion_anterior = $totales[0]['retencion_acumulada_anterior'];
			$this->anticipo_liberar_anterior = $totales[0]['anticipo_liberar_anterior'];
			$this->liberado_anterior = $totales[0]['liberacion_acumulada_anterior'];
	    }

        $data = array();

		$data = array(
			'suma_importes' => $this->suma_importes,
			'porcentaje_anticipo' => $this->pct_anticipo * 100,
			'amortizacion_anticipo' => $this->amortizacion_anticipo,
			'porcentaje_fondo_garantia'	=> $this->pct_fondo_garantia * 100,
			'fondo_garantia' => $this->fondo_garantia,
			'descuentos' => 0,
			'retenciones' => 0,
			'subtotal' => $this->subtotal,
			'iva' => $this->iva ,
			'total_estimacion' => $this->total_estimacion,
			'retencion_iva' => $this->retencion_iva,
			'anticipo_a_liberar' => $this->anticipo_liberar,
			'retencion_liberada' => $this->retencion_liberada,
			'total_pagar' => $this->total_pagar,

			'estimado_acumulado_anterior' => $this->estimado_anterior,
			'amortizacion_anticipo_acumulado_anterior' => $this->anticipo_anterior,
			'fondo_garantia_acumulado_anterior' => $this->fondo_garantia_anterior,
			'iva_acumulado_anterior' => $this->iva_anterior,
			'iva_retenido_acumulado_anterior' => $this->retencion_iva_anterior,
			'descuento_acumulado_anterior' => $this->descuento_anterior,
			'retencion_acumulada_anterior' => $this->retencion_anterior,
			'liberado_anterior'	=> $this->liberado_anterior,
			// 'acumulado_retenido' 					=> $this->empresa->getImporteTotalRetenido(),
			// 'acumulado_liberado' 					=> $this->empresa->getImporteTotalRetencionLiberado(),
			// 'acumulado_por_liberar'					=> $this->empresa->getImportePorLiberar()
		);

		foreach ($this->descuentos as $descuento)
        {
			$data['descuentos'] += $descuento->getImporte();
		}

		foreach ($this->retenciones as $retencion)
        {
			$data['retenciones'] += $retencion->getImporte();
		}

	    return $data;
	}

    /**
     * @return mixed
     */
    public function getNumeroFolioConsecutivo()
    {
		return $this->numero_folio_consecutivo;
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

    /**
     * @return int
     */
    public function getSumaImportes()
    {
		return $this->suma_importes;
	}

    /**
     * @return int
     */
    public function getPctIVA()
    {
		return $this->pct_iva;
	}

    /**
     * @return int
     */
    public function getPctAnticipo()
    {
		return $this->pct_anticipo;
	}

    /**
     * @return int
     */
    public function getPctFondoGarantia()
    {
		return $this->pct_fondo_garantia;
	}

    /**
     * @return int
     */
    public function getIVA()
    {
		return $this->iva;
	}

    /**
     * @return int
     */
    public function getAmortizacionAnticipo()
    {
		return $this->amortizacion_anticipo;
	}

    /**
     * @return int
     */
    public function getFondoGarantia()
    {
		return $this->fondo_garantia;
	}

    /**
     * @return int
     */
    public function getAnticipoLiberar()
    {
		return $this->anticipo_liberar;
	}

    /**
     * @return int
     */
    public function getRetencionIVA()
    {
		return $this->retencion_iva;
	}

    /**
     * @return int
     */
    public function getDescuentoAnterior()
    {
		return $this->descuento_anterior;
	}

    /**
     * @return int
     */
    public function getRetencionAnterior()
    {
		return $this->retencion_anterior;
	}

    /**
     * @return int
     */
    public function getLiberadoAnterior()
    {
		return $this->liberado_anterior;
	}

    /**
     * @return int
     */
    public function getEstimadoAnterior()
    {
		return $this->estimado_anterior;
	}

    /**
     * @return int
     */
    public function getAnticipoAnterior()
    {
		return $this->anticipo_anterior;
	}

    /**
     * @return int
     */
    public function getFondoGarantiaAnterior()
    {
		return $this->fondo_garantia_anterior;
	}

    /**
     * @return int
     */
    public function getIVAAnterior()
    {
		return $this->iva_anterior;
	}

    /**
     * @return int
     */
    public function getAnticipoLiberarAnterior()
    {
		return $this->anticipo_liberar_anterior;
	}

    /**
     * @return int
     */
    public function getRetencionIVAAnterior()
    {
		return $this->retencion_iva_anterior;
	}

    /**
     * @param array $conceptos
     */
    public function setConceptos(Array $conceptos)
    {
		$this->conceptos = $conceptos;
	}

    /**
     * @param $importe
     */
    public function setImporteAmortizacionAnticipo($importe)
    {
		if ( ! $this->esImporte($importe))
        {
			$importe = 0;
		}

        if ($this->suma_importes != 0)
        {
            $this->pct_anticipo = ($importe / $this->suma_importes);
		}
	}

    /**
     * @param $importe
     */
    public function setImporteFondoGarantia($importe)
    {
		if ( ! $this->esImporte($importe))
        {
			$importe = 0;
		}

		if ($this->suma_importes != 0)
        {
			$this->pct_fondo_garantia = ($importe / $this->suma_importes);
			$this->fondo_garantia = $importe;
		}
	}

    /**
     * @param $importe
     */
    public function setImporteRetencionIVA($importe)
    {
		if ( ! $this->esImporte($importe))
        {
			$importe = 0;
		}

		$this->retencion_iva = $importe;
	}

    /**
     * @param $importe
     */
    public function setImporteAnticipoLiberar($importe)
    {
		if ( ! $this->esImporte($importe))
        {
			$importe = 0;
		}

		$this->anticipo_liberar = $importe;
	}

    /**
     * Obtiene los conceptos a estimar para esta transaccion de acuerdo al subcontrato
     *
     * @param Subcontrato $subcontrato
     * @return mixed
     */
    public static function getConceptosNuevaEstimacion(Subcontrato $subcontrato)
    {
		$tsql = "{call [SubcontratosEstimaciones].[uspConceptosEstimacion]( ?, ?, ?, ? )}";

	    $params = array(
	        array($subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array(null, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array(0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array(0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $conceptos = $subcontrato->obra->getConn()->executeSP($tsql, $params);

	    return $conceptos;
	}

    /**
     * Obtiene todos los folios de este tipo de transaccion
     *
     * @param Obra $obra
     * @return array
     * @throws DBServerStatementExecutionException
     */
    public static function getFoliosTransaccion(Obra $obra)
    {
		$tsql = '{call [SubcontratosEstimaciones].[uspListaFolios]( ? )}';

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
		return parent::getListaTransacciones($obra, self::TIPO_TRANSACCION);
	}

    /**
     * Obtiene los tipos de retencion
     *
     * @param SAODBConn $conn
     * @return array
     * @throws DBServerStatementExecutionException
     */
    public static function getTiposRetencion(SAODBConn $conn)
    {
		$tsql = '{call [SubcontratosEstimaciones].[uspTiposRetencion]}';

	    $lista = $conn->executeSP($tsql);

		return $lista;
	}

    /**
     * @return string
     */
    public function __toString()
    {
		$data = parent::__toString();
		$data .= "fecha_inicio: {$this->fecha_inicio}, ";
		$data .= "fecha_termino: {$this->fecha_termino}, ";
		$data .= "numero_folio_consecutivo: {$this->numero_folio_consecutivo}, ";
		$data .= "pct_anticipo: {$this->pct_anticipo}, ";
		$data .= "pct_fondo_garantia: {$this->pct_fondo_garantia}, ";
		$data .= "pct_iva: {$this->pct_iva}, ";
		$data .= "suma_importes: {$this->suma_importes}, ";
		$data .= "fondo_garantia: {$this->fondo_garantia}, ";
		$data .= "amortizacion_anticipo: {$this->amortizacion_anticipo}, ";
		$data .= "subtotal: {$this->subtotal}, ";
		$data .= "iva: {$this->iva}, ";
		$data .= "total_estimacion: {$this->total_estimacion}, ";
		$data .= "retencion_iva: {$this->retencion_iva}, ";
		$data .= "anticipo_liberar: {$this->anticipo_liberar}, ";
		$data .= "retencion_liberada: {$this->retencion_liberada}, ";
		$data .= "total_pagar: {$this->total_pagar}, ";

		return $data;
	}

}
