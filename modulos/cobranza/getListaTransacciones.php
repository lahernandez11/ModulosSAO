<?php
session_start();
require_once 'db/SAO1814DBConn.class.php';
require_once 'Obra.class.php';
require_once 'Util.class.php';

$data['success'] = 1;
$data['errorMessage'] = null;
$data['noRows'] = false;
$data['noRowsMessage'] = null;

try {
    
    $_GET['IDObra'] = Obra::getIDObraProyecto($_GET['IDObra']);

    $conn = new SAO1814DBConn();

    $tsql = "{call [EstimacionesObra].[uspListaEstimacionesObra]( ? )}";

    $params = array(
        array($_GET['IDObra'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
    );

    $rsTransacciones = $conn->executeSP($tsql, $params);

    $data['Transacciones'] = array();

    foreach ( $rsTransacciones as $transaccion ) {

    	$data['Transacciones'][] = array(

    		'IDEstimacionObra' => $transaccion->IDEstimacionObra,
    		'NumeroFolio'      => Util::formatoNumeroFolio($transaccion->NumeroFolio),
    		'Fecha'            => $transaccion->Fecha,
    		'Referencia'       => $transaccion->Referencia
    	);
	}

} catch( Exception $e ) {
	
	$data['success'] = 0;
	$data['errorMessage'] = $e->getMessage();
}

echo json_encode($data);
?>