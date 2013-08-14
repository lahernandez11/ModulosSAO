<?php
session_start();
require_once 'db/SAO1814DBConn.class.php';

$data['success'] = 1;
$data['errorMessage'] = null;

try {
    
    $conn = new SAO1814DBConn();
    
    $tsql = "{call [EstimacionesObra].[uspActualizaDatosCobranza]( ?, ?, ?, ? )}";

    $params = array(
        array($_GET['IDCobranza'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
        array($_GET['Fecha'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
        array($_GET['Referencia'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(500)),
        array($_GET['Observaciones'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX'))
    );

    $conn->executeSP($tsql, $params);

} catch( Exception $e ) {
	
	$data['success'] = 0;
	$data['errorMessage'] = $e->getMessage();
}

unset($conn);

echo json_encode($data);
?>