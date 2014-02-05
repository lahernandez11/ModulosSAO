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

	if ( ! isset($_REQUEST['action']) ) {
		throw new Exception("No fue definida una acción");
	}

	switch ( $_REQUEST['action'] ) {

		case 'getDescendantsOf':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, $_GET['id_obra'] );
			
			$id_concepto_raiz = isset( $_GET['parentID'] ) ? (int) $_GET['parentID'] : null;
			// $data['IDRAIZ'] = $id_concepto_raiz;
			// $data['IDOBRA'] = $obra->getId();
			$data['nodes'] = array();

			$data['nodes'] = PresupuestoObra::getDescendantsOf( $obra, $id_concepto_raiz );

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