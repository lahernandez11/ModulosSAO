<?php
require_once("../../inc/DBConn.php");

$data['success'] = 0;
$data['errorMessage'] = null;

$conn = modulosSAO();

if( !$conn ) {
	$data['success'] = 0;
	$data['errorMessage'] = 'No se pudo establecer una conexion con el servidor de Base de Datos';
	
	echo json_encode($data);
	return;
}

$tsql = "{call [EstimacionesSubcontratos].[uspActualizaDatosEstimacion]( ?, ?, ?, ?, ? )}";

$params = array(
				array( $_GET['IDEstimacion'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
				array( $_GET['Fecha'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
				array( $_GET['FechaInicio'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
				array( $_GET['FechaTermino'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE ),
				array( $_GET['Observaciones'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
		  );

$stmt = sqlsrv_query( $conn, $tsql, $params );

if( ! $stmt ) {
	$data['success'] = 0;
	$data['errorMessage'] = getErrorMessage();

	echo json_encode( $data );
	return;
} else
	$data['success'] = 1;

sqlsrv_close( $conn );
echo json_encode( $data );
?>