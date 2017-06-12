<?php
require_once 'models/Obra.class.php';

class Material
{
	const TIPO_MATERIAL = 1;
	const TIPO_MANO_OBRA = 2;
	const TIPO_SERVICIOS = 3;
	const TIPO_HERRAMIENTA = 4;
	const TIPO_MAQUINARIA = 8;

	private $id;
	public  $obra;
	private $descripcion;
	private $tipo_material;
	private $nivel;
	private $unidad;
	private $unidad_compra;
	private $unidad_capacidad;
	private $equivalencia;
	private $marca;
	private $id_insumo;
	private $consumo;
	private $codigo_externo;
	private $merma;
	private $secuencia;
	private $cuenta_contable;
	private $numero_parte;
	private $id_agrupador_naturaleza;
	private $id_agrupador_familia;
	private $id_agrupador_insumo_generico;

	private $conn = null;

    /**
     * @param Obra $obra
     * @param $id_material
     * @throws Exception
     */
    public function __construct(Obra $obra, $id_material)
    {
		if ( ! is_int($id_material))
        {
			throw new Exception("El identificador de material no es valido");
		}

		$this->obra = $obra;
		$this->conn = $obra->getConn();
		$this->id   = $id_material;
		$this->init();
	}

    /**
     * @throws DBServerStatementExecutionException
     */
    private function init()
    {
		$tsql = "SELECT
					[materiales].[id_material]
				  , [materiales].[tipo_material]
				  , [materiales].[nivel]
				  , [materiales].[descripcion]
				  , [materiales].[unidad]
				  , [materiales].[unidad_compra]
				  , [materiales].[unidad_capacidad]
				  , [materiales].[equivalencia]
				  , [materiales].[marca]
				  , [materiales].[id_insumo]
				  , [materiales].[consumo]
				  , [materiales].[codigo_externo]
				  , [materiales].[merma]
				  , [materiales].[secuencia]
				  , [materiales].[cuenta_contable]
				  , [materiales].[numero_parte]
				  , [agrupacion_insumos].[id_agrupador_naturaleza]
				  , [agrupacion_insumos].[id_agrupador_familia]
				  , [agrupacion_insumos].[id_agrupador_insumo_generico]
				FROM
					[dbo].[materiales]
				LEFT OUTER JOIN
					[Agrupacion].[agrupacion_insumos]
                ON
                    [materiales].[id_material] = [agrupacion_insumos].[id_material]
                        AND
                    [agrupacion_insumos].[id_obra] = ?
				WHERE
					[materiales].[id_material] = ?";

		$params = [
	        [$this->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT],
	        [$this->id, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT],
	    ];

	    $res = $this->conn->executeQuery($tsql, $params);

		$this->descripcion 					= $res[0]->descripcion;
		$this->tipo_material 				= $res[0]->tipo_material;
		$this->nivel 						= $res[0]->nivel;
		$this->unidad 						= $res[0]->unidad;
		$this->unidad_compra 				= $res[0]->unidad_compra;
		$this->unidad_capacidad 			= $res[0]->unidad_capacidad;
		$this->equivalencia 				= $res[0]->equivalencia;
		$this->marca 						= $res[0]->marca;
		$this->id_insumo 					= $res[0]->id_insumo;
		$this->consumo 						= $res[0]->consumo;
		$this->codigo_externo 				= $res[0]->codigo_externo;
		$this->merma 						= $res[0]->merma;
		$this->secuencia 					= $res[0]->secuencia;
		$this->cuenta_contable 				= $res[0]->cuenta_contable;
		$this->numero_parte 				= $res[0]->numero_parte;
		$this->id_agrupador_naturaleza 		= $res[0]->id_agrupador_naturaleza;
		$this->id_agrupador_familia 		= $res[0]->id_agrupador_familia;
		$this->id_agrupador_insumo_generico = $res[0]->id_agrupador_insumo_generico;
	}

    /**
     * @return bool
     * @throws DBServerStatementExecutionException
     */
    private function existeRegistroAgrupacion()
    {
		$tsql = "SELECT 1
				 FROM
				    [Agrupacion].[agrupacion_insumos]
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_material] = ?";

	    $params = [
	        [$this->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT],
	        [$this->id, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT],
	    ];

	    $res = $this->conn->executeQuery( $tsql, $params );

	    if (count($res) > 0)
        {
            return true;
        }

	    return false;
	}

    /**
     * @throws DBServerStatementExecutionException
     */
    private function creaRegistroAgrupacion()
    {
		$tsql = "INSERT INTO [Agrupacion].[agrupacion_insumos]
				(
					  [id_obra]
					, [id_material]
				)
				VALUES
				( ?, ? )";

	    $params = [
	        [$this->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT],
	        [$this->id, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT],
	    ];

	    $this->conn->executeQuery($tsql, $params);
	}

    /**
     * @param AgrupadorInsumo $agrupador
     * @throws DBServerStatementExecutionException
     */
    public function setAgrupador(AgrupadorInsumo $agrupador)
    {
		if ( ! $this->existeRegistroAgrupacion())
        {
            $this->creaRegistroAgrupacion();
        }

		$field = '';

		switch ($agrupador->getTipoAgrupador())
        {
			case AgrupadorInsumo::TIPO_NATURALEZA:
				$field = AgrupadorInsumo::FIELD_NATURALEZA;
				break;

			case AgrupadorInsumo::TIPO_FAMILIA:
				$field = AgrupadorInsumo::FIELD_FAMILIA;
				break;

			case AgrupadorInsumo::TIPO_GENERICO:
				$field = AgrupadorInsumo::FIELD_GENERICO;
				break;
		}

		$tsql = "UPDATE [Agrupacion].[agrupacion_insumos]
				 SET
				 	{$field} = ?
				 WHERE
				 	[id_obra] = ?
				 		AND
				 	[id_material] = ?";

	    $params = [
	        [$agrupador->getIDAgrupador(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT],
	        [$this->obra->getId(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT],
	        [$this->id, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT],
	    ];

	    $this->conn->executeQuery($tsql, $params);
	}

    /**
     * @param Obra $obra
     * @return array
     * @throws DBServerStatementExecutionException
     */
    public static function getMaterialesObra(Obra $obra)
    {
		$tsql = "SELECT
				    [id_obra]
				  , [id_familia]
				  , [familia]
				  , [id_material]
				  , [material]
				  , [codigo_externo]
				  , [unidad]
				  , [id_agrupador_naturaleza]
				  , [agrupador_naturaleza]
				  , [id_agrupador_familia]
				  , [agrupador_familia]
				  , [id_agrupador_insumo_generico]
				  , [agrupador_insumo_generico]
				FROM
					[Agrupacion].[vwListaInsumos]
				WHERE
					[id_obra] = ?
				ORDER BY
					[familia],
					[material]";

		$params = [
			[$obra->getid(), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT],
		];

		$data = $obra->getConn()->executeSP($tsql, $params);

		return $data;
	}

    /**
     * @param SAODBConn $conn
     * @param null $descripcion
     * @param null $tipo
     * @return array
     * @throws DBServerStatementExecutionException
     */
    public static function getMateriales(SAODBConn $conn, $descripcion = null, $tipo = null)
    {
		switch ($tipo)
        {
			case self::TIPO_MATERIAL:
			case self::TIPO_MANO_OBRA:
			case self::TIPO_SERVICIOS:
			case self::TIPO_HERRAMIENTA:
			case self::TIPO_MAQUINARIA:
				break;
			
			default:
				$tipo = null;
				break;
		}

		$tsql = "{call [Insumos].[uspListaInsumos]( ?, ? )}";

	    $params = [
	        [$tipo, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT],
	        [$descripcion, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('255')],
	    ];

	    return $conn->executeSP($tsql, $params);
	}

    /**
     * @return int
     */
    public function getId()
    {
		return $this->id;
	}

    /**
     * @return mixed
     */
    public function getDescripcion()
    {
		return $this->descripcion;
	}

    /**
     * @return mixed
     */
    public function getTipoMaterial()
    {
		return $this->tipo_material;
	}

    /**
     * @return mixed
     */
    public function getNivel()
    {
		return $this->nivel;
	}

    /**
     * @return mixed
     */
    public function getUnidad()
    {
		return $this->unidad;
	}

    /**
     * @return mixed
     */
    public function getUnidadCompra()
    {
		return $this->unidad_compra;
	}

    /**
     * @return mixed
     */
    public function getUnidadCapacidad()
    {
		return $this->unidad_capacidad;
	}

    /**
     * @return mixed
     */
    public function getEquivalecia()
    {
		return $this->equivalecia;
	}

    /**
     * @return mixed
     */
    public function getMarca()
    {
		return $this->marca;
	}

    /**
     * @return mixed
     */
    public function getIdInsumo()
    {
		return $this->id_insumo;
	}

    /**
     * @return mixed
     */
    public function getConsumo()
    {
		return $this->consumo;
	}

    /**
     * @return mixed
     */
    public function getCodigoExterno()
    {
		return $this->codigo_externo;
	}

    /**
     * @return mixed
     */
    public function getMerma()
    {
		return $this->merma;
	}

    /**
     * @return mixed
     */
    public function getSecuencia()
    {
		return $this->secuencia;
	}

    /**
     * @return mixed
     */
    public function getCuentaContable()
    {
		return $this->cuenta_contable;
	}

    /**
     * @return mixed
     */
    public function getNumeroParte()
    {
		return $this->numero_parte;
	}

}