<?php
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Util.class.php';
require_once 'db/SAODBConn.class.php';
require_once 'models/PrecioVenta.class.php';

$data['success'] = true;
$data['message'] = null;
$data['noRows']  = false;

try {

	Sesion::validaSesionAsincrona();

	if ( ! isset($_REQUEST['action']) ) {
		throw new Exception("No fue definida una acción");
	}

	$conn = new SAO1814DBConn();

	$IDProyecto = (int) $_REQUEST['IDProyecto'];
	$IDObra 	= Obra::getIDObraProyecto($IDProyecto);
	
	switch ( $_REQUEST['action'] ) {

		case 'getPreciosVenta':

			$data['conceptos'] = array();

			$precios = PrecioVenta::getPreciosVenta($conn, $IDObra);

			foreach ($precios as $precio) {
				
				$data['conceptos'][] = array(

					'IDConcepto'  	   => $precio->IDConcepto,
					'NumeroNivel' 	   => $precio->NumeroNivel,
					'Descripcion' 	   => $precio->Descripcion,
					'EsActividad' 	   => $precio->EsActividad,
					'ConPrecio' 	   => $precio->ConPrecio,
					'Unidad' 	   	   => $precio->Unidad,
					'PrecioProduccion' => Util::formatoNumerico($precio->PrecioProduccion),
					'PrecioEstimacion' => Util::formatoNumerico($precio->PrecioEstimacion),
					'FechaUltimaModificacion' => Util::formatoFecha($precio->FechaUltimaModificacion),
				);
			}

			break;

		case 'setPreciosVenta':

			$conceptos = is_array($_REQUEST['conceptos']) ? $_REQUEST['conceptos'] : array();

			$data['conceptosError'] = array();

			$data['conceptosError'] = PrecioVenta::setPreciosVenta($conn, $IDObra, $conceptos);

			break;
	}

	unset($conn);
} catch( Exception $e ) {

	unset($conn);
	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>