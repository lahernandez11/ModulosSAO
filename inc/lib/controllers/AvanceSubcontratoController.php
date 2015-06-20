<?php
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Util.class.php';
require_once 'models/Subcontrato.class.php';
require_once 'models/AvanceSubcontrato.class.php';

$data['success'] = true;
$data['message'] = null;
$data['noRows']  = false;

try
{
	Sesion::validaSesionAsincrona();

	if ( ! isset($_REQUEST['action']))
	{
		throw new Exception("No fue definida una acción");
	}
	
	switch ($_REQUEST['action'])
	{
		case 'eliminaTransaccion':
			$conn = SAODBConnFactory::getInstance($_GET['base_datos']);
			$obra = new Obra($conn, $_GET['id_obra']);

			$id_transaccion = (int) $_GET['id_transaccion'];

			$transaccion = new AvanceSubcontrato($obra, $id_transaccion);

			$transaccion->eliminaTransaccion();

			break;

		case 'nuevaTransaccion':

			$conn = SAODBConnFactory::getInstance($_GET['base_datos']);
			$obra = new Obra($conn, $_GET['id_obra']);
			$id_subcontrato = (int) $_GET['id_subcontrato'];

			$data['subcontrato'] = array();
			$data['conceptos'] = array();

			$subcontrato = new Subcontrato($obra, $id_subcontrato);

			$data['subcontrato'] = array
			(
				'descripcion' => $subcontrato->getReferencia(),
				'empresa' => $subcontrato->empresa->getNombre()
			);

			$conceptos = AvanceSubcontrato::getConceptosNuevoAvance($obra, $id_subcontrato);

			foreach ($conceptos as $concepto)
			{
				$data['conceptos'][] = array
				(
					'id_item' => null,
					'id_concepto' => $concepto->id_concepto,
					'numero_nivel' => $concepto->numero_nivel,
					'es_actividad' => $concepto->es_actividad,
					'clave' => $concepto->clave,
					'descripcion' => $concepto->descripcion,
					'unidad' => $concepto->unidad,
					'cantidad_presupuestada' => Util::formatoNumerico($concepto->cantidad_presupuestada),
					'precio_unitario' => Util::formatoNumerico($concepto->precio_unitario),
					'cantidad' => 0,
				);
			}

			break;

		case 'getFoliosTransaccion':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, $_GET['id_obra'] );

			$data['options'] = array();

			$folios = AvanceSubcontrato::getFoliosTransaccion($obra);
			
			foreach ($folios as $folio)
			{
				$data['options'][] = array
				(
					'id_transaccion' => $folio->id_transaccion,
					'numero_folio' => Util::formatoNumeroFolio($folio->numero_folio)
				);
			}
			
			break;

		case 'getTransaccion':
			$conn = SAODBConnFactory::getInstance($_GET['base_datos']);
			$obra = new Obra($conn, $_GET['id_obra']);
			$id_transaccion = (int) $_GET['id_transaccion'];

			$data['datos'] = array();
			$data['conceptos'] = array();
			$data['totales'] = array();

			$transaccion = new AvanceSubcontrato($obra, $id_transaccion);

			$data['datos']['fecha'] = Util::formatoFecha($transaccion->getFecha());
			$data['datos']['fecha_inicio'] = Util::formatoFecha($transaccion->getFechaInicio());
			$data['datos']['fecha_termino'] = Util::formatoFecha($transaccion->getFechaTermino());
			$data['datos']['fecha_ejecucion'] = Util::formatoFecha($transaccion->getFechaEjecucion());
			$data['datos']['fecha_contable'] = Util::formatoFecha($transaccion->getFechaContable());
			$data['datos']['observaciones'] = $transaccion->getObservaciones();
			$data['datos']['descripcion'] = $transaccion->subcontrato->getReferencia();
			$data['datos']['empresa'] = $transaccion->subcontrato->empresa->getNombre();
			$data['datos']['id_subcontrato'] = $transaccion->subcontrato->getIDTransaccion();

			$conceptos = $transaccion->getConceptosAvance();

			foreach ($conceptos as $concepto)
			{
				$data['conceptos'][] = array
				(
					'id_item' => null,
					'id_concepto' => $concepto->id_concepto,
					'numero_nivel' => $concepto->numero_nivel,
					'es_actividad' => $concepto->es_actividad,
                    'clave' => $concepto->clave,
					'descripcion' => $concepto->descripcion,
					'unidad' => $concepto->unidad,
					'cantidad_presupuestada' => Util::formatoNumerico($concepto->cantidad_presupuestada),
					'precio_unitario' => Util::formatoNumerico($concepto->precio_unitario),
					'cantidad' => Util::formatoNumerico($concepto->cantidad),
				);
			}

			$totales = $transaccion->getTotalesTransaccion();

			foreach ($totales as $total)
			{
				$data['totales'] = array
				(
					'subtotal' => Util::formatoNumerico($total->subtotal),
					'iva' => Util::formatoNumerico($total->impuesto),
					'total' => Util::formatoNumerico($total->monto)
				);
			}
			break;

		case 'guardaTransaccion':
			$conn = SAODBConnFactory::getInstance($_POST['base_datos']);
			$obra = new Obra($conn, (int) $_POST['id_obra']);

			$id_transaccion = (int) $_POST['id_transaccion'];

			$fecha = $_POST['fecha'];
			$fechaInicio = $_POST['fecha_inicio'];
			$fechaTermino = $_POST['fecha_termino'];
			$fechaEjecucion = $_POST['fecha_ejecucion'];
			$fechaContable = $_POST['fecha_contable'];
			$observaciones = $_POST['observaciones'];
			$conceptos = array();

			if (isset($_POST['conceptos'] ) && is_array( $_POST['conceptos']))
			{
				$conceptos = $_POST['conceptos'];
			}

			$data['errores'] = array();
			$data['totales'] = array();

			if ( ! empty($id_transaccion))
			{
				$transaccion = new AvanceSubcontrato($obra, $id_transaccion);
				$transaccion->setFecha($fecha);
				$transaccion->setFechaInicio($fechaInicio);
				$transaccion->setFechaTermino($fechaTermino);
				$transaccion->setFechaEjecucion($fechaEjecucion);
				$transaccion->setFechaContable($fechaContable);
				$transaccion->setObservaciones($observaciones);
				$transaccion->setConceptos($conceptos);
				$data['errores'] = $transaccion->guardaTransaccion(Sesion::getUser());
			}
			else
			{
				$id_subcontrato = (int) $_POST['id_subcontrato'];

				$subcontrato = new Subcontrato($obra, $id_subcontrato);

				$transaccion = new AvanceSubcontrato(
					$obra, $subcontrato, $fecha, $fechaInicio,
					$fechaTermino, $fechaEjecucion, $fechaContable, $observaciones, $conceptos
				);

                $data['errores'] = $transaccion->guardaTransaccion(Sesion::getUser());

                $transaccion = new AvanceSubcontrato($obra, $transaccion->getIDTransaccion());

                $data['id_transaccion'] = $transaccion->getIDTransaccion();
				$data['numero_folio'] = Util::formatoNumeroFolio($transaccion->getNumeroFolio());
			}

			if (count($data['errores']) == 0)
			{
				$totales = $transaccion->getTotalesTransaccion();

				foreach ($totales as $total)
				{
					$data['totales'] = array
					(
						'subtotal' => Util::formatoNumerico($total->subtotal),
						'iva' => Util::formatoNumerico($total->impuesto),
						'total' => Util::formatoNumerico($total->monto)
					);
				}
			}

			break;

		case 'getListaTransacciones':
			$conn = SAODBConnFactory::getInstance($_GET['base_datos']);
			$obra = new Obra($conn, (int) $_GET['id_obra']);

			$data['options'] = array();

			$listaTran = AvanceSubcontrato::getListaTransacciones($obra);

			foreach ($listaTran as $tran)
			{
				$data['options'][] = array
				(
					'id_transaccion'  => $tran->IDTransaccion,
					'numero_folio' 	 => Util::formatoNumeroFolio($tran->NumeroFolio),
					'fecha'     	 => Util::formatoFecha($tran->Fecha),
					'observaciones'  => $tran->Observaciones
				);
			}
			break;
	}

}
catch( Exception $e )
{
	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);