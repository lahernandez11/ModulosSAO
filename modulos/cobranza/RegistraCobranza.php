<?php
session_start();
require_once 'db/SAO1814DBConn.class.php';

$data['success'] = 1;
$data['errorMessage'] = null;
$data['noRows'] = false;
$data['noRowsMessage'] = null;

try {
    
    $conn = new SAO1814DBConn();

    $tsql = "{call [EstimacionesObra].[uspRegistraCobranza]( ?, ? )}";

    $IDCobranza = null;

    $params = array(
        array( $_GET['IDEstimacionObra'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
        array( $IDCobranza, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT )
    );

    $rsTransacciones = $conn->executeSP($tsql, $params);

    $data['IDCobranza'] = $IDCobranza;

} catch( Exception $e ) {
	
	$data['success'] = 0;
	$data['errorMessage'] = $e->getMessage();
}

echo json_encode($data);
?>