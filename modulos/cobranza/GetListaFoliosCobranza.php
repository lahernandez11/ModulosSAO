<?php
session_start();
require_once 'db/SAO1814DBConn.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Util.class.php';

$data['success'] = 1;
$data['errorMessage'] = null;
$data['noRows'] = false;
$data['noRowsMessage'] = null;

try {
    
    $IDObra = Obra::getIDObraProyecto($_GET['IDProyecto']);

    $conn = new SAO1814DBConn();
    
    $tsql = "{call [EstimacionesObra].[uspListaFoliosCobranza]( ? )}";

    $params = array(
        array($IDObra, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
    );

    $rsTransacciones = $conn->executeSP($tsql, $params);

    $data['options'] = array();

    foreach ( $rsTransacciones as $transaccion ) {

    	$data['options'][] = array(

    		'id'  => $transaccion->IDCobranza,
    		'value' => Util::formatoNumeroFolio($transaccion->NumeroFolio)
    	);
	}

	if( sizeof($rsTransacciones) <= 0 ) {
		$data['noRows'] = true;
		$data['noRowsMessage'] = 'No se encontraron transacciones registradas.';
	}

} catch( Exception $e ) {
	
	$data['success'] = 0;
	$data['errorMessage'] = $e->getMessage();
}

unset($conn);

echo json_encode($data);
?>