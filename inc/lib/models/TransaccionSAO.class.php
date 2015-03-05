<?php
abstract class TransaccionSAO {

	const TIPO_TRANSACCION = 0;

	public $obra;
	protected $id_transaccion;
	protected $tipo_transaccion;
	protected $cumplimiento;
	protected $vencimiento;
	protected $id_concepto;
	protected $estado = 0;
	protected $_numeroFolio = 0;
	protected $_fecha = null;
	protected $referencia;
	protected $_observaciones = "";

	protected $conn = null;

	public function __construct()
	{
		$params = func_get_args();

		switch (func_num_args())
		{
			case 2:
				$this->instanceFromID( $params[0], $params[1] );
				break;

			case 4:
				call_user_func_array( array( $this, "init" ), $params );
				break;
		}
	}

	/**
	 * @param Obra $obra
	 * @param $tipoTransaccion
	 * @param $fecha
	 * @param $observaciones
     */
	private function init(Obra $obra, $tipoTransaccion, $fecha, $observaciones)
	{
		$this->obra = $obra;
		$this->tipo_transaccion = $tipoTransaccion;
		$this->setFecha($fecha);
		$this->setObservaciones($observaciones);
		$this->conn = $obra->getConn();
	}

	/**
	 * @param Obra $obra
	 * @param $id_transaccion
	 * @throws Exception
     */
	private function instanceFromID(Obra $obra, $id_transaccion)
	{
		if ((int) $id_transaccion <= 0)
		{
			throw new Exception( "No es un identificador de transacción válido." );
		}

		$this->obra = $obra;
		$this->conn = $obra->getConn();
		$this->setIDTransaccion( $id_transaccion );
	}

	/**
	 * @throws Exception
     */
	protected function setDatosGenerales()
	{
		$tsql = "SELECT
					  [transacciones].[id_transaccion]
					, [transacciones].[tipo_transaccion]
					, [transacciones].[estado]
					, [transacciones].[numero_folio]
					, [transacciones].[referencia]
					, [transacciones].[id_concepto]
					, CAST([transacciones].[fecha] AS DATE) AS [fecha]
					, CAST([transacciones].[vencimiento] AS DATE) AS [cumplimiento]
					, CAST([transacciones].[cumplimiento] AS DATE) AS [vencimiento]
					, [transacciones].[observaciones]
				FROM
					[dbo].[transacciones]
				WHERE
					[transacciones].[id_obra] = ?
						AND
					[transacciones].[id_transaccion] = ?";

	    $params = array
		(
	        array( $this->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
	        array( $this->id_transaccion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $rsDatosTran = $this->conn->executeQuery( $tsql, $params );

	    if (count($rsDatosTran) < 0)
		{
			throw new Exception("No se encontro la transacción.");
		}

		foreach ($rsDatosTran as $datosTran)
		{
			$this->tipo_transaccion = $datosTran->tipo_transaccion;
			$this->cumplimiento = $datosTran->cumplimiento;
			$this->vencimiento = $datosTran->vencimiento;
			$this->id_concepto = $datosTran->id_concepto;
			$this->referencia = $datosTran->referencia;
			$this->estado = $datosTran->estado;
			$this->_numeroFolio = $datosTran->numero_folio;
			$this->setFecha($datosTran->fecha);
			$this->_observaciones = $datosTran->observaciones;
		}
	}

	/**
	 * @param $id_transaccion
     */
	protected function setIDTransaccion($id_transaccion)
	{
		$this->id_transaccion = $id_transaccion;
	}

	/**
	 * @return null
     */
	public function getConn()
	{
		return $this->conn;
	}

	/**
	 * @return mixed
     */
	public function getIDTransaccion()
	{
		return $this->id_transaccion;
	}

	/**
	 * @return mixed
     */
	public function getIDObra()
	{
		return $this->obra->getId();
	}

	/**
	 * @return mixed
     */
	public function getTipoTransaccion()
	{
		return $this->tipo_transaccion;
	}

	/**
	 * @return null
     */
	public function getFecha()
	{
		return $this->_fecha;
	}

	/**
	 * @return mixed
     */
	public function getReferencia()
	{
		return $this->referencia;
	}

	/**
	 * @param $referencia
     */
	public function setReferencia( $referencia )
	{
		$this->referencia = $referencia;
	}

	/**
	 * @param $fecha
     */
	public function setFecha( $fecha )
	{
		// if ( ! $this->fechaEsValida( $fecha ) )
		// 	throw new Exception("El formato de fecha es incorrecto.");
		// else
			$this->_fecha = $fecha;
	}

	/**
	 * @param $fecha
	 * @return bool
     */
	protected function fechaEsValida( $fecha )
	{
		if (preg_match( "/^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/", $fecha ) === 1)
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $fecha
	 * @return bool
     */
	protected function esFecha( $fecha )
	{
		if (preg_match( "/^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/", $fecha ) === 1)
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $importe
	 * @return int
     */
	protected function esImporte( $importe )
	{
		return preg_match('/^-?\d+(\.\d+)?$/', $importe );
	}

	/**
	 * @return int
     */
	public function getNumeroFolio()
	{
		return $this->_numeroFolio;
	}

	/**
	 * @param $numeroFolio
     */
	protected function setNumeroFolio($numeroFolio)
	{
		$this->_numeroFolio = $numeroFolio;
	}

	/**
	 * @return string
     */
	public function getObservaciones()
	{
		return $this->_observaciones;
	}

	/**
	 * @param $Observaciones
	 * @return mixed
     */
	public function setObservaciones( $Observaciones)
	{
		return $this->_observaciones = $Observaciones;
	}

	/**
	 *
     */
	public function eliminaTransaccion()
	{
		$tsql = "{call [dbo].[sp_borra_transaccion]( ? )}";

	    $params = array
		(
	        array( $this->id_transaccion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT )
	    );

	    $this->conn->executeSP( $tsql, $params );
	}

	/**
	 * @param Obra $obra
	 * @param null $tipo_transaccion
	 * @return array
	 * @throws DBServerStatementExecutionException
     */
	public static function getListaTransacciones(Obra $obra, $tipo_transaccion=null)
	{
		if (is_null( $tipo_transaccion ))
		{
			$tipo_transaccion = self::TIPO_TRANSACCION;
		}

		$tsql = '{call [SAO].[uspListaTransacciones]( ?, ? )}';

		$params = array
		(
	        array($obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
	        array($tipo_transaccion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	    );

	    $listaTran = $obra->getConn()->executeSP($tsql, $params);

		return $listaTran;
	}

	/**
	 * @param Usuario $usuario
	 * @param $operacion
	 * @return string
     */
	public static function generaComentario(Usuario $usuario, $operacion)
	{
		$fecha = date("d/m/Y H:i");

		$comentario = "{$operacion};{$fecha};{$usuario->getUsername()}|";

		return $comentario;
	}

	/**
	 * @return string
     */
	public function __toString()
	{
		$data =  "id_transaccion: {$this->id_transaccion}, ";
		$data .= "tipo_transaccion: {$this->tipo_transaccion}, ";
		$data .= "id_obra: {$this->obra->getId()}, ";
		$data .= "estado: {$this->estado}, ";
		$data .= "fecha: {$this->_fecha}, ";
		$data .= "numero_folio: {$this->_numeroFolio}, ";
		$data .= "observaciones: {$this->_observaciones}, ";

		return $data;
	}

}