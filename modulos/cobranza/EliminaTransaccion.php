<?php
session_start();
require_once 'db/SAO1814DBConn.class.php';

$data['success'] = 1;
$data['errorMessage'] = null;

try {
    
    $conn = new SAO1814DBConn();
    
    $tsql = "{call [EstimacionesObra].[uspEliminaCobranza]( ? )}";

    $params = array(
        array($_GET['IDCobranza'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
    );

    $conn->executeSP($tsql, $params);

} catch( Exception $e ) {
	
	$data['success'] = 0;
	$data['errorMessage'] = $e->getMessage();
}

unset($conn);

echo json_encode($data);
?>