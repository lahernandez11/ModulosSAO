<?php
require_once 'models/Util.class.php';

class Addendum {
	
	public $subcontrato;
	private $id;
	private $fecha;
	private $monto;
	private $monto_anticipo;
	private $porcentaje_retencion_fg;

	/**
	 * @param $fecha
	 * @param $monto
	 * @param $monto_anticipo
	 * @param $porcentaje_retencion_fg
	 * @throws Exception
     */
	public function __construct( $fecha, $monto, $monto_anticipo, $porcentaje_retencion_fg ) {

		$this->monto 		  = Util::limpiaImporte( $monto );
		$this->monto_anticipo = Util::limpiaImporte( $monto_anticipo );
		$this->fecha 		  = $fecha;
		$this->porcentaje_retencion_fg = $porcentaje_retencion_fg;

		if ( ! Util::esFecha( $this->fecha ) ) {
			throw new Exception("La fecha es incorrecta.", 1);
		}

		if( ! Util::esImporte( $this->monto ) ) {
			throw new Exception("El monto es incorrecto.", 1);
		}

		if( ! Util::esImporte( $this->monto_anticipo ) ) {
			throw new Exception("El monto de anticipo es incorrecto", 1);
		}

		// Porcentaje de Retencion de Fondo de Garantia
		if ( ! preg_match('/^\d\d{0,2}?(\.\d{1,2})?$/', $this->porcentaje_retencion_fg) ) {
			throw new Exception("El porcentaje de retencion es incorrecto.", 1);
		}
	}

	/**
	 * @param Subcontrato $subcontrato
	 * @param $id
	 * @return Addendum
	 * @throws Exception
     */
	public static function getInstance( Subcontrato $subcontrato, $id ) {

		$tsql = "SELECT
				    [addendum].[id_addendum]
				  , [addendum].[id_transaccion]
				  , [addendum].[fecha]
				  , [addendum].[monto]
				  , [addendum].[monto_anticipo]
				  , [addendum].[porcentaje_retencion_fg]
				  , [addendum].[creado]
				FROM
				    [Subcontratos].[addendum]
				WHERE
					[id_transaccion] = ?
						AND
				    [id_addendum] = ?";

		$params = array(
			$subcontrato->getIDTransaccion(),
			$id
		);

		$data = $subcontrato->getConn()->executeQuery( $tsql, $params );

		if ( count( $data ) > 0 ) {
			$addendum = new self( $data[0]->fecha, $data[0]->monto, 
				$data[0]->monto_anticipo, $data[0]->porcentaje_retencion_fg);
			$addendum->setId( $id );
			$addendum->subcontrato = $subcontrato;
			return $addendum;
		} else
			throw new Exception("Addendum no encontrado", 1);
	}

	/**
	 * @param Subcontrato $subcontrato
     */
	public function save( Subcontrato $subcontrato ) {
		
		$this->subcontrato = $subcontrato;

		$tsql = "INSERT INTO [Subcontratos].[addendum] (
					[id_transaccion],
					[fecha],
					[monto],
					[monto_anticipo],
					[porcentaje_retencion_fg]
				)
				VALUES( ?, ?, ?, ?, ? )";

		$params = array(
			array( $this->subcontrato->getIDTransaccion(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
			array( $this->fecha, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
			array( $this->monto, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_MONEY ),
			array( $this->monto_anticipo, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_MONEY ),
			array( $this->porcentaje_retencion_fg, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(5, 2) ),
			array( $this->id, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
		);

		$this->subcontrato->getConn()->executeQuery( $tsql, $params );
	}

	/**
	 *
     */
	public function delete() {

		$tsql = "DELETE [Subcontratos].[addendum]
				WHERE
					[id_transaccion] = ?
						AND
					[id_addendum] = ?";

		$params = array(
			$this->subcontrato->getIDTransaccion(),
			$this->id
		);

		$this->subcontrato->getConn()->executeQuery( $tsql, $params );
	}

	/**
	 * @return mixed
     */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return mixed
     */
	public function getFecha() {
		return $this->fecha;
	}

	/**
	 * @return mixed
     */
	public function getMonto() {
		return $this->monto;
	}

	/**
	 * @return mixed
     */
	public function getMontoAnticipo() {
		return $this->monto_anticipo;
	}

	/**
	 * @return mixed
     */
	public function getPorcentajeRetencionFG() {
		return $this->porcentaje_retencion_fg;
	}

	/**
	 * @param $id
     */
	public function setId( $id ) {
		$this->id = $id;
	}
}