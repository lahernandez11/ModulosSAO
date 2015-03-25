<?php
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Util.class.php';
require_once 'db/SAODBConn.class.php';
require_once 'models/PropuestaTecnica.class.php';

$data['success'] = true;
$data['message'] = null;
$data['noRows']  = false;

try
{
	Sesion::validaSesionAsincrona();

	if ( ! isset($_REQUEST['action']) )
	{
		throw new Exception("No fue definida una acción");
	}

	switch ( $_REQUEST['action'] )
	{
		case 'eliminaTransaccion':
			$conn = SAODBConnFactory::getInstance($_POST['base_datos']);
			$obra = new Obra($conn, $_POST['id_obra']);
			$id_transaccion = (int) $_POST['id_transaccion'];

			$transaccion = new PropuestaTecnica($obra, $id_transaccion);
			$transaccion->eliminaTransaccion();

			break;

		case 'getConceptosNuevoAvance':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra($conn, $_GET['id_obra']);
			$id_concepto_raiz = (int) $_GET['id_concepto_raiz'];
			
			$data['conceptos'] = array();

			$conceptos = PropuestaTecnica::getConceptosNuevoAvance($obra, $id_concepto_raiz);

			foreach ($conceptos as $concepto)
			{
				$data['conceptos'][] = array(
					'id_concepto'  => $concepto->id_concepto,
					'numero_nivel' => $concepto->numero_nivel,
					'descripcion' => $concepto->descripcion,
					'unidad' 	  => $concepto->unidad,
					'es_actividad' => $concepto->es_actividad,
					'cantidad_presupuestada' => Util::formatoNumerico($concepto->cantidad_presupuestada),
					'precio_unitario' => Util::formatoNumerico($concepto->precio_unitario),
					'monto_presupuestado' => Util::formatoNumerico($concepto->monto_presupuestado),
					'cantidad' => 0,
				);
			}

			break;

		case 'getDatosTransaccion':
			$conn = SAODBConnFactory::getInstance($_GET['base_datos']);
			$obra = new Obra($conn, $_GET['id_obra']);
			$id_transaccion = (int) $_GET['id_transaccion'];

			$data['datos'] = array();
			$data['conceptos'] = array();
			$data['totales'] = array();
			
			$transaccion = new PropuestaTecnica($obra, $id_transaccion);

			$data['datos']['concepto_raiz'] = $transaccion->getConceptoRaiz();
			$data['datos']['observaciones'] = $transaccion->getObservaciones();
			$data['datos']['fecha'] = Util::formatoFecha($transaccion->getFecha());
			$data['datos']['fecha_inicio'] = Util::formatoFecha($transaccion->getFechaInicio());
			$data['datos']['fecha_termino'] = Util::formatoFecha($transaccion->getFechaTermino());

			$conceptos = $transaccion->getConceptos();

			foreach ($conceptos as $concepto)
			{
				$data['conceptos'][] = array(
					'id_concepto' => $concepto->id_concepto,
					'numero_nivel' => $concepto->numero_nivel,
					'descripcion' => $concepto->descripcion,
					'unidad' => $concepto->unidad,
					'es_actividad' => $concepto->es_actividad,
					'cantidad_presupuestada' => Util::formatoNumerico($concepto->cantidad_presupuestada),
					'precio_unitario' => Util::formatoNumerico($concepto->precio_unitario),
					'monto_presupuestado' => Util::formatoNumerico($concepto->monto_presupuestado),
					'cantidad' => Util::formatoNumerico($concepto->cantidad)
				);
			}

			break;

		case 'guardaTransaccion':
			$conn = SAODBConnFactory::getInstance($_POST['base_datos']);
			$obra = new Obra($conn, $_POST['id_obra']);
			
			$id_concepto_raiz = (int) $_POST['id_concepto_raiz'];
			$fecha = $_POST['fecha'];
			$fechaInicio = $_POST['fechaInicio'];
			$fechaTermino = $_POST['fechaTermino'];
			$observaciones = $_POST['observaciones'];
			$conceptos = isset($_POST['conceptos']) ? $_POST['conceptos'] : array();

			$data['errores'] = array();

			if (isset($_POST['id_transaccion']))
			{
				$transaccion = new PropuestaTecnica($obra, (int) $_POST['id_transaccion']);
				$transaccion->setFecha($fecha);
				$transaccion->setFechaInicio($fechaInicio);
				$transaccion->setFechaTermino($fechaTermino);
				$transaccion->setObservaciones($observaciones);
				$transaccion->setConceptos($conceptos);

				$data['errores'] = $transaccion->guardaTransaccion(Sesion::getUser());
			}
			else
			{
				$transaccion = new PropuestaTecnica(
						$obra,
						$fecha,
						$fechaInicio,
						$fechaTermino,
						$observaciones,
						$id_concepto_raiz,
						$conceptos
				);
			
				$data['errores'] = $transaccion->guardaTransaccion(Sesion::getUser());
				$data['id_transaccion'] = $transaccion->getIDTransaccion();
				$data['numero_folio'] = Util::formatoNumeroFolio($transaccion->getNumeroFolio());
			}

			break;

		case 'getFoliosTransaccion':
			$conn = SAODBConnFactory::getInstance($_GET['base_datos']);
			$obra = new Obra($conn, $_GET['id_obra']);

			$data['options'] = array();

			$folios = PropuestaTecnica::getFoliosTransaccion($obra);

			foreach ($folios as $folio)
			{
				$data['options'][] = array(
					'id_transaccion' => $folio->IDTransaccion,
					'numero_folio'   => Util::formatoNumeroFolio($folio->NumeroFolio)
				);
			}
			break;

		case 'getListaTransacciones':
			$conn = SAODBConnFactory::getInstance($_GET['base_datos']);
			$obra = new Obra($conn, $_GET['id_obra']);
			
			$data['options'] = array();

			$listaTran = PropuestaTecnica::getListaTransacciones($obra);

			foreach ( $listaTran as $tran )
			{
				$data['options'][] = array(
					'id_transaccion' => $tran->IDTransaccion,
					'numero_folio' => Util::formatoNumeroFolio($tran->NumeroFolio),
					'fecha' => Util::formatoFecha($tran->Fecha),
					'observaciones' => $tran->Observaciones,
				);
			}
			break;
	}

}
catch( Exception $e )
{
	$data['success'] = false;
	$data['message'] = $e->getMessage();
	$data['errores'] = $e->errors;
}

echo json_encode($data);