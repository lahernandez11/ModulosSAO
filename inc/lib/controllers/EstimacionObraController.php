<?php
require_once 'setPath.php';
require_once 'db/SAODBConnFactory.class.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/EstimacionObra.class.php';
require_once 'models/Util.class.php';

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
		case 'nuevaTransaccion':
			$conn = SAODBConnFactory::getInstance($_GET['base_datos']);
			$obra = new Obra($conn, $_GET['id_obra']);
			$conceptos = EstimacionObra::getConceptosNuevaEstimacion($obra);
			
			$data['conceptos'] = [];

			foreach ($conceptos as $concepto)
            {
				$data['conceptos'][] = [
					'IDConcepto'  			   => $concepto->IDConcepto,
					'NumeroNivel' 			   => $concepto->NumeroNivel,
                    'clave_concepto' 		   => $concepto->clave_concepto,
					'Descripcion' 			   => $concepto->Descripcion,
					'EsActividad' 			   => $concepto->EsActividad,
					'Unidad' 	  			   => $concepto->Unidad,
					'CantidadPresupuestada'    => Util::formatoNumerico($concepto->CantidadPresupuestada),
					'CantidadEstimadaAnterior' => Util::formatoNumerico($concepto->CantidadEstimadaAnterior),
					'CantidadEstimada' 		   => Util::formatoNumerico($concepto->CantidadEstimada),
					'PrecioVenta' 		 	   => $concepto->PrecioVenta,
					'Total' 		 		   => Util::formatoNumerico($concepto->Total),
					'Cumplido' 		 		   => $concepto->Cumplido,
				];
			}
			break;

		case 'getFoliosTransaccion':
			$conn = SAODBConnFactory::getInstance($_GET['base_datos']);
			$obra = new Obra($conn, $_GET['id_obra']);
			
			$data['options'] = [];
			$folios = EstimacionObra::getFoliosTransaccion($obra);
			
			foreach ($folios as $folio)
            {
				$data['options'][] = [
					'id_transaccion' => $folio->IDTransaccion,
					'numero_folio' => Util::formatoNumeroFolio($folio->NumeroFolio),
				];
			}
			break;

		case 'getDatosTransaccion':
			$conn = SAODBConnFactory::getInstance($_GET['base_datos']);
			$obra = new Obra($conn, $_GET['id_obra']);
			$id_transaccion = (int) $_GET['id_transaccion'];

			$data['datos'] 	   = [];
			$data['conceptos'] = [];
			$data['totales']   = [];
			
			$transaccion = new EstimacionObra($obra, $id_transaccion);

			$data['datos']['Fecha'] 		= Util::formatoFecha($transaccion->getFecha());
			$data['datos']['FechaInicio']   = Util::formatoFecha($transaccion->getFechaInicio());
			$data['datos']['FechaTermino']  = Util::formatoFecha($transaccion->getFechaTermino());
			$data['datos']['Observaciones'] = $transaccion->getObservaciones();
			$data['datos']['Referencia']    = $transaccion->getReferencia();

			$conceptos = $transaccion->getConceptos();

			foreach ($conceptos as $concepto)
            {
				$data['conceptos'][] = [
					'IDConcepto'  			   => $concepto->IDConcepto,
					'NumeroNivel' 			   => $concepto->NumeroNivel,
					'clave_concepto' 		   => $concepto->clave_concepto,
					'Descripcion' 			   => $concepto->Descripcion,
					'EsActividad' 			   => $concepto->EsActividad,
					'Unidad' 	  			   => $concepto->Unidad,
					'CantidadPresupuestada'    => Util::formatoNumerico($concepto->CantidadPresupuestada),
					'CantidadEstimadaAnterior' => Util::formatoNumerico($concepto->CantidadEstimadaAnterior),
					'CantidadEstimada' 		   => Util::formatoNumerico($concepto->CantidadEstimada),
					'PrecioVenta' 		 	   => $concepto->PrecioVenta,
					'Total' 		 		   => Util::formatoNumerico($concepto->Total),
					'Cumplido' 		 		   => $concepto->Cumplido,
				];
			}

			$totales = $transaccion->getTotalesTransaccion();

			foreach ($totales as $total)
            {
				$data['totales'][] = [
					'Subtotal'  => Util::formatoNumerico($total->Subtotal),
					'IVA' 		=> Util::formatoNumerico($total->IVA),
					'Total'   	=> Util::formatoNumerico($total->Total),
				];
			}
			break;

		case 'guardaTransaccion':
			$conn = SAODBConnFactory::getInstance($_POST['base_datos']);
			$obra = new Obra($conn, $_POST['id_obra']);

			$fecha 		   = $_POST['datosGenerales']['fecha'];
			$fechaInicio   = $_POST['datosGenerales']['fechaInicio'];
			$fechaTermino  = $_POST['datosGenerales']['fechaTermino'];
			$referencia    = $_POST['datosGenerales']['referencia'];
			$observaciones = $_POST['datosGenerales']['observaciones'];
			$conceptos     = isset($_POST['conceptos']) ? $_POST['conceptos'] : [];

			$data['errores'] = [];
			$data['totales'] = [];

			if (isset($_POST['id_transaccion']))
            {
				$transaccion = new EstimacionObra($obra, (int) $_POST['id_transaccion']);
				$transaccion->setFecha($fecha);
				$transaccion->setFechaInicio($fechaInicio);
				$transaccion->setFechaTermino($fechaTermino);
				$transaccion->setReferencia($referencia);
				$transaccion->setObservaciones($observaciones);
				$transaccion->setConceptos($conceptos);
				
				$data['errores'] = $transaccion->guardaTransaccion(Sesion::getUser());
			}
            else
            {
				$transaccion = new EstimacionObra(
					$obra, $fecha, $fechaInicio, $fechaTermino,
					$observaciones, $referencia, $conceptos
				);
				
				$data['errores'] = $transaccion->guardaTransaccion(Sesion::getUser());
				$data['id_transaccion'] = $transaccion->getIDTransaccion();
				$data['numero_folio'] = Util::formatoNumeroFolio($transaccion->getNumeroFolio());
			}

			$totales = $transaccion->getTotalesTransaccion();

			foreach ($totales as $total)
            {
				$data['totales'][] = [
					'Subtotal'  => Util::formatoNumerico($total->Subtotal),
					'IVA' 		=> Util::formatoNumerico($total->IVA),
					'Total'   	=> Util::formatoNumerico($total->Total),
				];
			}

			break;

		case 'eliminaTransaccion':
			$conn = SAODBConnFactory::getInstance($_POST['base_datos']);
			$obra = new Obra($conn, $_POST['id_obra']);

			$id_transaccion = (int) $_POST['id_transaccion'];
			$transaccion = new EstimacionObra($obra, $id_transaccion);
			$transaccion->eliminaTransaccion();
			break;
			
		case 'apruebaTransaccion':
			$conn = SAODBConnFactory::getInstance($_POST['base_datos']);
			$obra = new Obra($conn, $_POST['id_obra']);
			$id_transaccion = (int) $_POST['id_transaccion'];

			$transaccion = new EstimacionObra($obra , $id_transaccion);
			$transaccion->apruebaTransaccion();
			break;

		case 'revierteAprobacion':
			$conn = SAODBConnFactory::getInstance($_POST['base_datos']);
			$obra = new Obra($conn, $_POST['id_obra']);
			$id_transaccion = (int) $_POST['id_transaccion'];

			$transaccion = new EstimacionObra($obra , $id_transaccion);
			$transaccion->revierteAprobacion();
			break;

		case 'getTotalesTransaccion':
			$conn = SAODBConnFactory::getInstance($_GET['base_datos']);
			$obra = new Obra($conn, $_GET['id_obra']);
			$id_transaccion = (int) $_GET['id_transaccion'];

			$transaccion = new EstimacionObra($obra , $id_transaccion);

			$data['totales'] = [];

			$totales = $transaccion->getTotalesTransaccion();

			foreach ($totales as $total)
            {
				$data['totales'][] = [
					'Subtotal'  => Util::formatoNumerico($total->Subtotal),
					'IVA' 		=> Util::formatoNumerico($total->IVA),
					'Total'     => Util::formatoNumerico($total->Total),
				];
			}
			break;

		case 'getListaTransacciones':
			$conn = SAODBConnFactory::getInstance($_GET['base_datos']);
			$obra = new Obra($conn, (int) $_GET['id_obra']);

			$listaTran = EstimacionObra::getListaTransacciones($obra);

			$data['options'] = [];

			foreach ($listaTran as $tran)
            {
				$data['options'][] = [
					'IDTransaccion'  => $tran->IDTransaccion,
					'NumeroFolio' 	 => Util::formatoNumeroFolio($tran->NumeroFolio),
					'Fecha'     	 => Util::formatoFecha($tran->Fecha),
					'Observaciones'  => $tran->Observaciones,
				];
			}
			break;
	}

}
catch (Exception $e)
{
	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
