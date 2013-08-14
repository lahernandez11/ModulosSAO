<?php
require_once("../../inc/DBConn.php");

$data['success'] = 0;
$data['errorMessage'] = null;
$data['noRows'] = false;
$data['noRowsMessage'] = null;

$conn = modulosSAO();

if( !$conn ) {
	$data['success'] = 0;
	$data['errorMessage'] = 'No se pudo establecer una conexion con el servidor de Base de Datos';
	
	echo json_encode($data);
	return;
}

$tsql = "{call [EstimacionesSubcontratos].[uspRegistraEstimacion]( ?, ? )}";

$IDEstimacion = null;
$NumeroFolio = null;

$params = array(
				array( $_GET['idSubcontrato'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
				array( $IDEstimacion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
		  );

$stmt = sqlsrv_query( $conn, $tsql, $params );

if( ! $stmt ) {
	$data['success'] = 0;
	$data['errorMessage'] = getErrorMessage();

	echo json_encode( $data );
	return;
} else
	$data['success'] = 1;

$data['IDEstimacion'] = $IDEstimacion;

sqlsrv_close( $conn );
echo json_encode( $data );
?>