<?php
require_once 'setPath.php';
require_once 'db/SAODBConn.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Sesion.class.php';
require_once 'models/PresupuestoObra.class.php';

$data['success'] = true;
$data['message'] = null;
$data['noRows'] = false;

try {

	Sesion::validaSesionAsincrona();

	if ( ! isset($_GET['action']) ) {
		throw new Exception("No fue definida una acción");
	}

	$conn = SAODBConn::getInstance( $_GET['base'] );

	$IDProyecto = (int) $_GET['IDProyecto'];
	$IDObra 	= Obra::getIDObraProyecto($IDProyecto);

	switch ( $_GET['action'] ) {

		case 'getDescendantsOf':
			
			$IDConceptoRaiz = (isset($_GET['parentID']) ? (int) $_GET['parentID'] : null);
			$data['IDRAIZ'] = $IDConceptoRaiz;
			$data['IDOBRA'] = $IDObra;
			$data['nodes'] = array();

			$data['nodes'] = PresupuestoObra::$_GET['action']( $IDObra, $IDConceptoRaiz, $conn );

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