<?php
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Util.class.php';
require_once 'db/SAODBConn.class.php';
require_once 'models/AvanceObra.class.php';

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

		case 'listaMateriales':

			$fecha 		    = $_REQUEST['fecha'];
			$fechaInicio    = $_REQUEST['fechaInicio'];
			$fechaTermino   = $_REQUEST['fechaTermino'];
			$observaciones  = $_REQUEST['observaciones'];
			$IDConceptoRaiz = (int) $_REQUEST['IDConceptoRaiz'];
			$conceptos 	 	= is_array($_REQUEST['conceptos']) ? $_REQUEST['conceptos'] : array();
			$data['conceptosError'] = array();

			$avanceObra = new AvanceObra( $IDObra, $fecha, $fechaInicio, $fechaTermino, $observaciones, $IDConceptoRaiz, $conceptos , $conn );
			
			$data['conceptosError'] = $avanceObra->registraTransaccion();
			$data['IDTransaccion']  = $avanceObra->getIDTransaccion();
			$data['numeroFolio']    = Util::formatoNumeroFolio($avanceObra->getNumeroFolio());

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