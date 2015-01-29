<?php
require_once 'models/TransaccionSAO.class.php';

class AvanceObra extends TransaccionSAO {

	const TIPO_TRANSACCION = 98;

	private $id_concepto_raiz = 0;
	private $concepto_raiz = null;
	private $fecha_inicio = null;
	private $fecha_termino = null;
	private $conceptos;

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

	private function instaceFromDefault(Obra $obra, $fecha, $fechaInicio, $fechaTermino,
		$observaciones, $id_concepto_raiz, Array $conceptos)
	{
		parent::__construct($obra, self::TIPO_TRANSACCION, $fecha, $observaciones);

		$this->setIDConceptoRaiz($id_concepto_raiz);
		$this->setFechaInicio($fechaInicio);
		$this->setFechaTermino($fechaTermino);
		$this->setConceptos($conceptos);
	}

	private function instanceFromID(Obra $obra, $id_transaccion)
	{
		parent::__construct($obra, $id_transaccion);
		
		$this->setDatosGenerales();
	}

	// @override
	protected function setDatosGenerales()
	{
		parent::setDatosGenerales();

		$tsql = "{call [AvanceObra].[uspDatosGenerales]( ? )}";

		$params = array
		(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $datos = $this->conn->executeSP($tsql, $params);

	    $this->setIDConceptoRaiz($datos[0]->IDConceptoRaiz);
	    $this->concepto_raiz   = $datos[0]->ConceptoRaiz;
	    $this->fecha_inicio    = $datos[0]->FechaInicio;
	    $this->fecha_termino   = $datos[0]->FechaTermino;
	}

	public function guardaTransaccion(Usuario $usuario)
	{
		$errores = array();

		if ( ! empty($this->id_transaccion))
		{
			$tsql = "{call [AvanceObra].[uspGuardaDatosGenerales]( ?, ?, ?, ?, ?, ? )}";

		    $params = array
			(
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
			$tsql = "{call [AvanceObra].[uspRegistraTransaccion]( ?, ?, ?, ?, ?, ?, ?, ?, ? )}";

		    $params = array(
		        array($this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
		        array($this->getFecha(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
		       	array($this->getFechaInicio(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
		        array($this->getFechaTermino(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
		        array($this->getObservaciones(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(4096)),
		        array($this->getIDConceptoRaiz(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
		        array($usuario->getUsername(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(16)),
		        array(&$this->id_transaccion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT),
		        array(&$this->_numeroFolio, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT)
		    );

		    $this->conn->executeSP($tsql, $params);
		}

		return $errores = $this->guardaConceptosAvance();
	}

	private function guardaConceptosAvance()
	{
		$errores = array();

		$tsql = "{call [AvanceObra].[uspGuardaAvanceConcepto]( ?, ?, ?, ? )}";

		foreach ($this->conceptos as $concepto)
		{
			try
			{
				// Lipia y valida la cantidad estimada
				$concepto['cantidad'] = str_replace(',', '', $concepto['cantidad']);

				$isValid = preg_match('/^-?\d+(\.\d+)?$/', $concepto['cantidad']);

				// Si la cantidad no es valida agrega el concepto con error
				if ( ! $isValid)
				{
					throw new Exception("La cantidad ingresada no es correcta.");
				}

				$params = array
				(
					array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($concepto['IDConcepto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
					array($concepto['cantidad'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT),
					array($concepto['cumplido'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT)
				);
			
				$this->conn->executeSP($tsql, $params);
			}
			catch (Exception $e)
			{
				$errores[] = array
				(
					'IDConcepto' => $concepto['IDConcepto'],
					'cantidad' => $concepto['cantidad'],
					'message' => $e->getMessage()
				);
			}
		}

		return $errores;
	}

	public function apruebaTransaccion(Usuario $usuario)
	{
		$tsql = "{call [AvanceObra].[uspApruebaTransaccion]( ? )}";

		$params = array
		(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $this->conn->executeSP($tsql, $params);
	}

	public function revierteAprobacion(Usuario $usuario)
	{
		$tsql = "{call [AvanceObra].[uspRevierteAprobacion]( ? )}";

		$params = array
		(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $this->conn->executeSP($tsql, $params);
	}

	private function getIDConceptoRaiz()
	{
		return $this->id_concepto_raiz;
	}

	private function setIDConceptoRaiz($id_concepto_raiz)
	{
		$this->id_concepto_raiz = $id_concepto_raiz;
	}

	public function setConceptos(Array $conceptos)
	{
		$this->conceptos = $conceptos;
	}

	public function getFechaInicio()
	{
		return $this->fecha_inicio;
	}

	public function setFechaInicio($fecha)
	{
		if ( ! $this->fechaEsValida($fecha))
		{
			throw new Exception("El formato de fecha inicial es incorrecto.");
		}
		
		$this->fecha_inicio = $fecha;
	}

	public function getFechaTermino()
	{
		return $this->fecha_termino;
	}

	public function setFechaTermino( $fecha )
	{
		if ( ! $this->fechaEsValida( $fecha ))
		{
			throw new Exception("El formato de fecha termino es incorrecto.");
		}
		
		$this->fecha_termino = $fecha;
	}

	public function getConceptoRaiz()
	{
		return $this->concepto_raiz;
	}

	public function getConceptosAvance()
	{
		$tsql = "{call [AvanceObra].[uspConceptosAvance]( ?, ?, ? )}";

	    $params = array
		(
	        array($this->getIDObra(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array($this->getIDConceptoRaiz(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $conceptos = $this->conn->executeSP($tsql, $params);

	    return $conceptos;
	}

	public function getTotalesTransaccion()
	{
		$tsql = "{call [AvanceObra].[uspTotalesTransaccion]( ? )}";

		$params = array
		(
	        array($this->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $totales = $this->conn->executeSP($tsql, $params);

	    return $totales;
	}

	public static function getConceptosNuevoAvance(Obra $obra, $id_concepto_raiz)
	{
		$tsql = "{call [AvanceObra].[uspConceptosAvance]( ?, ? )}";

	    $params = array
		(
	        array($obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array($id_concepto_raiz, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $conceptos = $obra->getConn()->executeSP($tsql, $params);

	    return $conceptos;
	}

	public static function getFoliosTransaccion(Obra $obra)
	{
		$tsql = '{call [AvanceObra].[uspListaFolios]( ? )}';

		$params = array
		(
	        array($obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $foliosTran = $obra->getConn()->executeSP($tsql, $params);

		return $foliosTran;
	}

	public static function getListaTransacciones(Obra $obra, $tipo_transaccion=null)
	{
		return parent::getListaTransacciones($obra, self::TIPO_TRANSACCION);
	}

}