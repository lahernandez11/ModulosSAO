<?php
require_once 'models/TransaccionSAO.class.php';

class Cobranza extends TransaccionSAO {

	const TIPO_TRANSACCION = 104;

	private $_referencia;
	private $_IDEstimacionObra;
	private $_conceptos = array();
	private $_folioFactura;

	public function __construct() {
		
		$params = func_get_args();

		switch ( func_num_args() ) {

			case 7:
				call_user_func_array(array($this, "instanceFromDefault"), $params);
				break;

			case 2:
				call_user_func_array(array($this, "instanceFromID"), $params);
				break;
		}
	}

	private function instanceFromDefault( $IDObra, $IDEstimacionObra, $fecha, $folio_factura, 
		$observaciones, Array $conceptos, SAODBConn $conn ) {

		parent::__construct($IDObra, self::TIPO_TRANSACCION, $fecha, $observaciones, $conn);

		$this->_IDEstimacionObra = $IDEstimacionObra;
		$this->_observaciones = $observaciones;
		$this->setConceptos( $conceptos );
		$this->setFolioFactura($folio_factura);
	}

	private function instanceFromID( $IDTransaccion, SAODBConn $conn ) {
		parent::__construct( $IDTransaccion, $conn );

		$this->setDatosGenerales();
	}

	public function guardaTransaccion() {

		if ( ! empty($this->_IDTransaccion) ) {

			$tsql = "{call [Cobranza].[uspActualizaDatosGenerales]( ?, ?, ?, ?, ? )}";

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
		        array( $this->getReferencia(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(64) ),
		        array( $this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096) ),
		        array( $this->_folioFactura, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(20)),
		        array( Sesion::getCuentaUsuarioSesion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16) ),
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		} else {

			$tsql = "{call [Cobranza].[uspRegistraTransaccion]( ?, ?, ?, ?, ?, ? )}";

		    $params = array(
		        array( $this->getIDEstimacionObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
		        array( $this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		        array( Sesion::getCuentaUsuarioSesion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16) ),
		        array( &$this->_IDTransaccion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT ),
		        array( &$this->_numeroFolio, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}

		return $errores = $this->guardaConceptos();
	}

	private function guardaConceptos() {
		$errores = array();

		$tsql = "{call [Cobranza].[uspGuardaConcepto]( ?, ?, ?, ? )}";

		foreach ( $this->_conceptos as $concepto ) {
			
			try {
				// Limpia y valida la cantidad y precio
				$concepto['cantidad'] = str_replace(',', '', $concepto['cantidad']);
				$concepto['precio'] = str_replace(',', '', $concepto['precio']);

				// Si el importe no es valido agrega el concepto con error
				if( ! $this->esImporte($concepto['cantidad']) || ! $this->esImporte($concepto['precio'])) {
					throw new Exception("El numero ingresado no es correcto");
				}

				$params = array(
					array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
					array( $concepto['IDConcepto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
					array( $concepto['cantidad'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) ),
					array( $concepto['precio'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
					// array( $concepto['cumplido'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT )
				);

				$this->_SAOConn->executeSP($tsql, $params);
			} catch( Exception $e ) {

				$errores[] = array(
					'IDConcepto' => $concepto['IDConcepto'],
					'cantidad'   => $concepto['cantidad'],
					'message' 	 => $e->getMessage()
				);
			}
		}

		return $errores;
	}

	public function eliminaTransaccion() {

		$tsql = "{call [Cobranza].[uspEliminaTransaccion]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $totales = $this->_SAOConn->executeSP($tsql, $params);
	}

	public function getConceptos() {

		$tsql = '{call [Cobranza].[uspConceptosCobranza]( ?, ?, ? )}';

		$params = array(
	        array( $this->getIDObra() , SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDEstimacionObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $conceptos = $this->_SAOConn->executeSP($tsql, $params);

		return $conceptos;
	}

	public function setConceptos( Array $conceptos ) {
		$this->_conceptos = $conceptos;
	}

	public function getTotales() {

		$tsql = "{call [Cobranza].[uspTotalesTransaccion]( ? )}";

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $totales = $this->_SAOConn->executeSP($tsql, $params);

	    return $totales;
	}

	public function setFolioFactura( $folio ) {
		$this->_folioFactura = $folio;
	}

	public function getFolioFactura() {
		return $this->_folioFactura;
	}

	public function setDatosGenerales() {

		$tsql = '{call [Cobranza].[uspDatosGenerales]( ? )}';

		$params = array(
	        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $datos = $this->_SAOConn->executeSP($tsql, $params);

		$this->_IDObra 		     = $datos[0]->IDObra;
		$this->_nombreObra	     = $datos[0]->NombreObra;
		$this->_numeroFolio      = $datos[0]->NumeroFolio;
		$this->setFecha($datos[0]->Fecha);
		$this->_observaciones    = $datos[0]->Observaciones;
		$this->_referencia   	 = $datos[0]->Referencia;
		$this->_IDEstimacionObra = $datos[0]->IDEstimacionObra;
		$this->_folioFactura 	 = $datos[0]->FolioFactura;
	}

	public function setReferencia( $referencia ) {
		$this->_referencia = $referencia;
	}

	public function getReferencia() {
		return $this->_referencia;
	}

	public function getIDEstimacionObra() {
		return $this->_IDEstimacionObra;
	}

	public function setImporteProgramado( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {
			$tsql = "{call [Cobranza].[uspActualizaTotales]( ?, ? )}";

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}
	}

	public function setImporteDevolucion( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {
			$tsql = "{call [Cobranza].[uspActualizaTotales]( ?, ?, ? )}";

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        null,
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}
	}

	public function setImporteRetencionObraNoEjecutada( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {
			$tsql = "{call [Cobranza].[uspActualizaTotales]( ?, ?, ?, ? )}";

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        null,
		        null,
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}
	}

	public function setImporteAmortizacionAnticipo( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {
			$tsql = "{call [Cobranza].[uspActualizaTotales]( ?, ?, ?, ?, ? )}";

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        null,
		        null,
		        null,
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}
	}

	public function setImporteIVAAnticipo( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {
			$tsql = "{call [Cobranza].[uspActualizaTotales]( ?, ?, ?, ?, ?, ? )}";

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        null,
		        null,
		        null,
		        null,
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}
	}

	public function setImporteInspeccionVigilancia( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {
			$tsql = "{call [Cobranza].[uspActualizaTotales]( ?, ?, ?, ?, ?, ?, ? )}";

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        null,
		        null,
		        null,
		        null,
		        null,
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}
	}

	public function setImporteCMIC( $importe ) {

		if ( ! $this->esImporte( $importe ) ) {
			throw new Exception("Importe Incorrecto");
		} else {
			$tsql = "{call [Cobranza].[uspActualizaTotales]( ?, ?, ?, ?, ?, ?, ?, ? )}";

		    $params = array(
		        array( $this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		        null,
		        null,
		        null,
		        null,
		        null,
		        null,
		        array( $importe, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 4) )
		    );

		    $this->_SAOConn->executeSP($tsql, $params);
		}
	}

	public static function getTransacciones( $IDObra, SAODBConn $conn) {

		if ( ! is_int($IDObra) )
			throw new Exception("El identificador de la obra no es correcto.");
		else {
			$tsql = '{call [Cobranza].[uspListaTransacciones]( ? )}';

			$params = array(
		        array( $IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
		    );

		    $transacciones = $conn->executeSP($tsql, $params);

			return $transacciones;
		}
	}
	
	public static function getConceptosNuevaTransaccion( $IDObra, $IDEstimacionObra, SAODBConn $conn ) {

		$tsql = '{call [Cobranza].[uspConceptosCobranza]( ?, ? )}';

		$params = array(
	        array( $IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $IDEstimacionObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $conceptos = $conn->executeSP($tsql, $params);

		return $conceptos;
	}

	public static function getFoliosTransaccion( $IDObra, SAODBConn $conn ){}
}
?>