<?php
require_once 'models/TransaccionSAO.class.php';
require_once 'models/Empresa.class.php';
require_once 'models/Moneda.class.php';
require_once 'models/Subcontrato.class.php';
require_once 'models/EstimacionDeductiva.class.php';

class EstimacionSubcontrato extends TransaccionSAO {
	
	const TIPO_TRANSACCION = 52;
	const CARACTER_OPERACION_REGISTRO = 'I';
	const CARACTER_OPERACION_APROBACION = 'A';
	const ESTADO_CAPTURADA = 0;
	const ESTADO_APROBADA  = 1;

	public $subcontrato;
	public $empresa;
	public $moneda;

	private $fecha_inicio;
	private $fecha_termino;
	private $numero_folio_consecutivo;
	private $pct_anticipo;
	private $pct_fondo_garantia;
	private $pct_iva;

	private $conceptos = array();

	private $suma_importes 		 	 = 0;
	private $fondo_garantia 		 = 0;
	private $amortizacion_anticipo 	 = 0;
	private $anticipo_liberar 		 = 0;
	private $suma_descuento 		 = 0;
	private $suma_retencion 		 = 0;
	private $suma_retencion_liberada = 0;
	private $subtotal 		 		 = 0;
	private $iva 		 			 = 0;
	private $retencion_iva 		 	 = 0;
	private $total_estimacion 		 = 0;
	private $total_pagar 		 	 = 0;

	public function __construct() {
		
		$params = func_get_args();

		switch ( func_num_args() ) {

			case 7:
				call_user_func_array(array($this, "instaceFromDefault"), $params);
				break;

			case 2:
				call_user_func_array(array($this, "instanceFromID"), $params);
				break;
		}
	}

	private function instaceFromDefault( 
		Obra $obra, Subcontrato $subcontrato, $fecha, $fechaInicio, $fechaTermino, $observaciones,
		Array $conceptos ) {
		
		parent::__construct( $obra, self::TIPO_TRANSACCION, $fecha, $observaciones );

		$this->subcontrato = $subcontrato;
		$this->setFechaInicio( $fechaInicio );
		$this->setFechaTermino( $fechaTermino );
		$this->setConceptos( $conceptos );
	}

	private function instanceFromID( Obra $obra, $id_transaccion ) {
		parent::__construct( $obra, $id_transaccion );

		$this->registraTransaccionAdicional();
		$this->setDatosGenerales();
	}

	/*
	 * Crea el registro adicional de la estimacion en la tabla
	 * Estimaciones para una transaccion que fue registrada
	 * en el SAO
	*/
	private function registraTransaccionAdicional() {

		if ( ! $this->existeRegistroEstimacion() ) {
			$tsql = "INSERT INTO [SubcontratosEstimaciones].[Estimaciones]
					(
					      [IDEstimacion],
					      [NumeroFolioConsecutivo]
					)
					VALUES ( ?, ? );";

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $this->getNumeroFolio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		    );

		   	$this->conn->executeQuery( $tsql, $params );
		}
	}

	/*
	 * Verifica si la transaccion tiene un registro asociado
	 * en la tabla adicional Estimaciones
	*/
	private function existeRegistroEstimacion() {

		$tsql = "SELECT 1
				 FROM
				 	[SubcontratosEstimaciones].[Estimaciones]
				 WHERE
				 	[IDEstimacion] = ?";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $res = $this->conn->executeQuery( $tsql, $params );

	    if (count($res) > 0)
	    	return true;
	    else
	    	return false;
	}

	// @override
	protected function setDatosGenerales() {
		
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
					, [transacciones].[retencion] / 100 AS [retencion]
					, IIF([transacciones].[monto] - [transacciones].[impuesto] = 0, 0,
					(
						[transacciones].[impuesto]
							/
						([transacciones].[monto] - [transacciones].[impuesto])
					) * 100) AS [porcentaje_iva]
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
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $datos = $this->conn->executeSP( $tsql, $params );

	    $this->subcontrato 			    = new Subcontrato( $this->obra, $datos[0]->id_antecedente );
	    $this->empresa 		   		    = new Empresa( $this->obra, $datos[0]->id_empresa);
	    $this->moneda 					= new Moneda( $this->obra, $datos[0]->id_moneda );

	    $this->numero_folio_consecutivo = $datos[0]->NumeroFolioConsecutivo;
	    $this->fecha_inicio 			= $datos[0]->cumplimiento;
	    $this->fecha_termino 			= $datos[0]->vencimiento;
	    $this->pct_anticipo 		    = $datos[0]->anticipo;
	    $this->pct_fondo_garantia 	    = $datos[0]->retencion;
	    $this->pct_iva 	   			    = $datos[0]->porcentaje_iva;

	    $this->getTotalesTransaccion();
	}

	public function getConceptosEstimacion() {

		$tsql = "{call [SubcontratosEstimaciones].[uspConceptosEstimacion]( ?, ?, ?, ? )}";

	    $params = array(
	        array( $this->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( 0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT ),
	        array( 0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT )
	    );

	    $conceptos = $this->conn->executeSP( $tsql, $params );

	    return $conceptos;
	}

	public function guardaTransaccion( Usuario $usuario ) {

		if ( $this->estado > self::ESTADO_CAPTURADA ) {
			throw new Exception("No es posible modificar la estimacion. Ya esta aprobada.", 1);
		} else {
			
			try {
				$this->conn->beginTransaction();

				if ( ! empty( $this->id_transaccion ) ) {

					$tsql = "{call [SubcontratosEstimaciones].[uspActualizaDatosGenerales]( ?, ?, ?, ?, ?, ? )}";

				    $params = array(
				        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
				        array( $this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
				        array( $this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
				        array( $this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
				        array( $this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096) ),
				        array( $usuario->getUsername(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16) ),
				    );

				    $this->conn->executeSP( $tsql, $params );
				} else {

					$tsql = "{call [SubcontratosEstimaciones].[uspRegistraTransaccion]( ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )}";

				    $params = array(
				        array( $this->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
				        array( $this->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
				        array( $this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
				       	array( $this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
				        array( $this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
				        array( $this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096) ),
				        array( $usuario->getUsername(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16) ),
				        array( &$this->id_transaccion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT ),
				        array( &$this->_numeroFolio, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT ),
				        array( &$this->numero_folio_consecutivo, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT ),
				    );

				    $this->conn->executeSP( $tsql, $params );
				}

				$errores = $this->guardaConceptos();

				$this->calculaImportes();

				$this->getTotalesTransaccion();

				$this->conn->commitTransaction();

				return $errores;
			} catch( Exception $e ) {
				$this->conn->rollbackTransaction();
				throw $e;
			}
		}
	}

	private function guardaConceptos() {
		$errores = array();

		$tsql = "{call [SubcontratosEstimaciones].[uspEstimaConcepto]( ?, ?, ?, ? )}";

		$this->suma_importes = 0;

		foreach ( $this->conceptos as $concepto ) {
			
			try {
				// Limpia y valida el importe estimado
				$concepto['importeEstimado'] = str_replace(',', '', $concepto['importeEstimado']);

				// Si el importe no es valido agrega el concepto con error
				if( ! $this->esImporte($concepto['importeEstimado']) ) {
					throw new Exception("El numero ingresado no es correcto");
				}

				$params = array(
					array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
					array( $concepto['IDConceptoContrato'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
					array( $concepto['IDConceptoDestino'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
					array( $concepto['importeEstimado'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
				);

				$this->conn->executeSP( $tsql, $params );
				
				$this->suma_importes += $concepto['importeEstimado'];

			} catch( Exception $e ) {

				$errores[] = array(
					'IDConceptoContrato' => $concepto['IDConceptoContrato'],
					'importeEstimado'    => $concepto['importeEstimado'],
					'message' 		     => $e->getMessage()
				);
			}
		}

		return $errores;
	}

	private function calculaImportes() {

		$this->subtotal  = $this->suma_importes;
		$this->subtotal -= $this->amortizacion_anticipo;
		$this->subtotal -= $this->fondo_garantia;

		if ( $this->suma_importes != 0 ) {
			$this->pct_anticipo 	  = ($this->amortizacion_anticipo / $this->suma_importes) * 100;
			$this->pct_fondo_garantia = ($this->fondo_garantia / $this->suma_importes) * 100;
		}
	
		$this->iva 	 = $this->subtotal * $this->subcontrato->getPorcentajeIVA();
		$this->total = $this->subtotal + $this->iva;

	    $tsql = "UPDATE
					[dbo].[transacciones]
				SET
					[impuesto]  = ?,
					[monto]     = ?,
					[retencion] = ?,
					[anticipo]  = ?
				WHERE
					[id_transaccion] = ?;";

	    $params = array(
	        array( $this->iva, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
	        array( $this->total, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
	        array( $this->pct_fondo_garantia, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
	        array( $this->pct_anticipo, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery( $tsql, $params );

		$tsql = "UPDATE
					[SubcontratosEstimaciones].[Estimaciones]
				SET
					[ImporteRetencionIVA]    = ?,
					[ImporteAnticipoLiberar] = ?
				WHERE
					[IDEstimacion] = ?;";

	    $params = array(
	        array( $this->retencion_iva, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
	        array( $this->anticipo_liberar, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeQuery( $tsql, $params );
	}

	public function setAprobada( Usuario $usuario ) {

		if ( $this->suma_importes == 0 ) {
			throw new Exception("La estimacion no tiene importe", 1);
		}
		if ( $this->estado > self::ESTADO_CAPTURADA ) {
			throw new Exception("La estimacion se encuentra aprobada.", 1);
		}

		try {

			$this->conn->beginTransaction();

			$tsql = "UPDATE [dbo].[transacciones]
					SET
						[comentario] = [comentario] + ?,
						[impreso] = 1,
						[saldo] = ?
					WHERE
						[id_transaccion] = ?;";

			$params = array(
		        array( self::generaComentario( $usuario, self::CARACTER_OPERACION_APROBACION ), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(1024) ),
		        array( $this->total_estimacion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT ),
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		    );

		    $this->conn->executeQuery( $tsql, $params );

			$tsql = "{call [dbo].[sp_aprobar_transaccion]( ? )}";

			$params = array(
		    	array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		    );

			$this->conn->executeSP( $tsql, $params );

			$tsql = "SELECT
						[estado]
					FROM
						[dbo].[transacciones]
					WHERE
						[id_transaccion] = ?;";

			$params = array(
		    	array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		    );

			$data = $this->conn->executeQuery( $tsql, $params );

			$this->estado = $data[0]->estado;

			$this->conn->commitTransaction();
		} catch( Exception $e ) {
			$this->conn->rollbackTransaction();
			throw $e;
		}
	}

	public function revierteAprobacion() {

		if ( $this->estado === self::ESTADO_CAPTURADA ) {
			throw new Exception("La estimacion no esta aprobada.", 1);
		} else {

			$tsql = "{call [dbo].[sp_revertir_transaccion]( ? )}";

			$params = array(
		    	array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		    );

			$this->conn->executeSP( $tsql, $params );

			$tsql = "SELECT
						[estado]
					FROM
						[dbo].[transacciones]
					WHERE
						[id_transaccion] = ?;";

			$params = array(
		    	array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		    );

			$data = $this->conn->executeQuery( $tsql, $params );

			$this->estado = $data[0]->estado;
		}
	}

	public function eliminaTransaccion() {

		if ( $this->estado > self::ESTADO_CAPTURADA ) {
			throw new Exception("La estimacion se encuentra aprobada.", 1);
		}

		$tsql = "{call [SubcontratosEstimaciones].[uspEliminaTransaccion]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeSP( $tsql, $params );
	}

	public function getConceptosEstimados( $soloConceptosEstimados = 1 ) {

		$tsql = "{call [SubcontratosEstimaciones].[uspConceptosEstimacion]( ?, ?, ?, ? )}";

	    $params = array(
	        array( $this->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( 1, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT ),
	        array( $soloConceptosEstimados, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT )
	    );

	    $conceptos = $this->conn->executeSP( $tsql, $params );

	    return $conceptos;
	}

	public function getDeductivas() {

		return EstimacionDeductiva::getObjects( $this->empresa );
	}

	public function addDescuento( EstimacionDeductiva $deductiva, $cantidad, $precio ) {

		$descuento = new EstimacionDescuento( $this, $deductiva );
		$descuento->setCantidad( $cantidad );
		$descuento->setPrecio( $precio );
		$descuento->save();

		return $descuento;
	}

	public function getRetenciones() {

		$tsql = "{call [SubcontratosEstimaciones].[uspRetenciones]( ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    return $this->conn->executeSP( $tsql, $params );
	}

	public function agregaRetencion( $IDTipoRetencion, $importe, $concepto, $observaciones ) {

		if ( ! is_int( $IDTipoRetencion ) || ! $IDTipoRetencion > 0 ) {
			throw new Exception("El tipo de retención no es válido");
			return;
		}

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {

			$tsql = "{call [SubcontratosEstimaciones].[uspRegistraRetencion]( ?, ?, ?, ?, ?, ? )}";

			$IDRetencion = 0;
			
		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $IDTipoRetencion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
		        array( $concepto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		        array( $observaciones, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		        array( &$IDRetencion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
		    );

		    $this->conn->executeSP( $tsql, $params );
		}

	    return $IDRetencion;
	}

	public function eliminaRetencion( $IDRetencion ) {

		$tsql = "{call [SubcontratosEstimaciones].[uspEliminaRetencion]( ?, ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $IDRetencion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeSP( $tsql, $params );
	}

	public function agregaLiberacion( $importe, $observaciones, Usuario $usuario ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {

			$tsql = "{call [SubcontratosEstimaciones].[uspRegistraLiberacionRetencion]( ?, ?, ?, ?, ? )}";

			$IDLiberacion = null;

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
		        array( $observaciones, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		        array( $usuario->getUsername(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('20') ),
		        array( $IDLiberacion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT ),
		    );

		    $this->conn->executeSP( $tsql, $params );
		}

	    return $IDLiberacion;
	}

	public function eliminaLiberacion( $IDLiberacion ) {

		$tsql = "{call [SubcontratosEstimaciones].[uspEliminaLiberacionRetencion]( ?, ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $IDLiberacion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeSP( $tsql, $params );
	}

	public function getLiberaciones() {

		$tsql = "{call [SubcontratosEstimaciones].[uspRetencionesLiberadas]( ? )}";

	    $params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    return $this->conn->executeSP( $tsql, $params );
	}

	public function getImporteRetenidoPorLiberar() {

		$tsql = "{call [SubcontratosEstimaciones].[uspImporteRetenidoPorLiberar]( ?, ? )}";

		$importePorLiberar = null;

	    $params = array(
	        array( $this->getIDSubcontrato(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( &$importePorLiberar, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
	    );

	    $this->conn->executeSP( $tsql, $params );

	    return $importePorLiberar;
	}

	public function getPctIVA() {
		return $this->pct_iva;
	}

	public function getPctAnticipo() {
		return $this->pct_anticipo;
	}

	public function getPctFondoGarantia() {
		return $this->pct_fondo_garantia;
	}
	
	public function getTotalesTransaccion() {

		$tsql = "{call [SubcontratosEstimaciones].[uspTotalesTransaccion]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $totales = $this->conn->executeSP( $tsql, $params, SQLSrvDBConn::FETCH_MODE_ARRAY );
	    $data = array();

		$data = array(
			'suma_importes'  			  			=> $totales[0]['SumaImportes'],
			'fondo_garantia'  	  					=> $totales[0]['ImporteFondoGarantia'],
			'amortizacion_anticipo' 				=> $totales[0]['ImporteAmortizacionAnticipo'],
			'anticipo_liberar'  	  				=> $totales[0]['ImporteAnticipoLiberar'],
			'suma_descuento'  			  			=> $totales[0]['SumaDeductivas'],
			'suma_retencion'  			  			=> $totales[0]['SumaRetenciones'],
			'suma_retencion_liberada'    			=> $totales[0]['SumaRetencionesLiberadas'],
			'subtotal' 					  			=> $totales[0]['Subtotal'],
			'iva' 						  			=> $totales[0]['IVA'],
			'retencion_iva'  		  				=> $totales[0]['ImporteRetencionIVA'],
			'total_estimacion'     				  	=> $totales[0]['Total'],
			'ImporteAcumuladoEstimacionAnterior' 	=> $totales[0]['ImporteAcumuladoEstimacionAnterior'],
			'ImporteAcumuladoDeductivaAnterior' 	=> $totales[0]['ImporteAcumuladoDeductivaAnterior'],
			'ImporteAcumuladoRetencionAnterior' 	=> $totales[0]['ImporteAcumuladoRetencionAnterior'],
			'ImporteAcumuladoAnticipoAnterior' 		=> $totales[0]['ImporteAcumuladoAnticipoAnterior'],
			'ImporteAcumuladoFondoGarantiaAnterior' => $totales[0]['ImporteAcumuladoFondoGarantiaAnterior'],
			'IVAAcumuladoAnterior' 					=> $totales[0]['IVAAcumuladoAnterior'],
			'total_pagar' 							=> $totales[0]['monto_a_pagar']
		);

		$this->suma_importes 		 	= $totales[0]['SumaImportes'];
		$this->fondo_garantia 		 	= $totales[0]['ImporteFondoGarantia'];
		$this->amortizacion_anticipo 	= $totales[0]['ImporteAmortizacionAnticipo'];
		$this->anticipo_liberar 		= $totales[0]['ImporteAnticipoLiberar'];
		$this->suma_descuento 		 	= $totales[0]['SumaDeductivas'];
		$this->suma_retencion 		 	= $totales[0]['SumaRetenciones'];
		$this->suma_retencion_liberada  = $totales[0]['SumaRetencionesLiberadas'];
		$this->subtotal 		 		= $totales[0]['Subtotal'];
		$this->iva 		 			 	= $totales[0]['IVA'];
		$this->retencion_iva 		 	= $totales[0]['ImporteRetencionIVA'];
		$this->total_estimacion 		= $totales[0]['Total'];
		$this->total_pagar 		 	 	= $totales[0]['monto_a_pagar'];

	    return $data;
	}

	public function getNumeroFolioConsecutivo() {
		return $this->numero_folio_consecutivo;
	}

	public function getFechaInicio() {
		return $this->fecha_inicio;
	}

	public function setFechaInicio( $fecha ) {

		if ( $this->fechaEsValida( $fecha ) )
			$this->fecha_inicio = $fecha;
		else
			throw new Exception("El formato de fecha inicial es incorrecto.");
	}

	public function getFechaTermino() {
		return $this->fecha_termino;
	}

	public function setFechaTermino( $fecha ) {
		
		if ( $this->fechaEsValida( $fecha ) )
			$this->fecha_termino = $fecha;
		else
			throw new Exception("El formato de fecha término es incorrecto.");
	}

	public function getAnticipoLiberar() {
		return $this->anticipo_liberar;
	}

	public function setConceptos( Array $conceptos ) {
		$this->conceptos = $conceptos;
	}

	public function setImporteAmortizacionAnticipo( $importe ) {
		
		if ( ! $this->esImporte( $importe ) ) {
			$importe = 0;
		}

		$this->amortizacion_anticipo = $importe;
	}

	public function setImporteFondoGarantia( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			$importe = 0;
		}

		$this->fondo_garantia = $importe;
	}

	public function setImporteRetencionIVA( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			$importe = 0;
		}

		$this->retencion_iva = $importe;
	}

	public function setImporteAnticipoLiberar( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			$importe = 0;
		}

		$this->anticipo_liberar = $importe;
	}

	public static function getConceptosNuevaEstimacion( Subcontrato $subcontrato ) {

		$tsql = "{call [SubcontratosEstimaciones].[uspConceptosEstimacion]( ?, ?, ?, ? )}";

	    $params = array(
	        array( $subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( null, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( 0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( 0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $conceptos = $subcontrato->obra->getConn()->executeSP( $tsql, $params );

	    return $conceptos;
	}

	public static function getFoliosTransaccion( Obra $obra ) {

		$tsql = '{call [SubcontratosEstimaciones].[uspListaFolios]( ? )}';

		$params = array(
	        array( $obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $foliosTran = $obra->getConn()->executeSP( $tsql, $params );

		return $foliosTran;
	}

	public static function getListaTransacciones( Obra $obra, $tipo_transaccion=null ) {

		return parent::getListaTransacciones( $obra, self::TIPO_TRANSACCION );
	}

	public static function getTiposRetencion( SAODBConn $conn ) {

		$tsql = '{call [SubcontratosEstimaciones].[uspTiposRetencion]}';

	    $lista = $conn->executeSP( $tsql );

		return $lista;
	}

	public function __toString() {
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
		$data .= "suma_descuento: {$this->suma_descuento}, ";
		$data .= "suma_retencion: {$this->suma_retencion}, ";
		$data .= "retencion_iva: {$this->retencion_iva}, ";
		$data .= "anticipo_liberar: {$this->anticipo_liberar}, ";
		$data .= "suma_retencion_liberada: {$this->suma_retencion_liberada}, ";
		$data .= "total_pagar: {$this->total_pagar}, ";

		return $data;
	}
}
?>