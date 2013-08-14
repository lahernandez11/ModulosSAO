<?php
require_once("../../inc/DBConn.php");

$data['success'] = 0;
$data['errorMessage'] = null;
$data['isDataValid'] = true;

// Limpieza de caracteres
$_GET['Importe'] = str_replace(',', '', $_GET['Importe']);

// Validacion
$isValid = preg_match('/^-?\d+(\.\d+)?$/', $_GET['Importe']);

if( ! $isValid ) {
	$data['isDataValid'] = false;
	$data['errorMessage'] = 'El importe ingresado no es correcto.';
	
	echo json_encode($data);
	return;
}

$conn = modulosSAO();

if( ! $conn ) {
	$data['success'] = 0;
	$data['errorMessage'] = 'No se pudo establecer una conexion con el servidor de Base de Datos';
	
	echo json_encode($data);
	return;
}

$tsql = "{call [EstimacionesSubcontratos].[uspRegistraPenalizacion]( ?, ?, ?, ? )}";

$IDPenalizacion = null;

$params = array(
				array( $_GET['IDEstimacion'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
				array( $_GET['Importe'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19,2) ),
				array( $_GET['Descripcion'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('MAX') ),
				array( $IDPenalizacion, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )
		  );

$stmt = sqlsrv_query( $conn, $tsql, $params );

if( ! $stmt ) {
	$data['success'] = 0;
	$data['errorMessage'] = getErrorMessage();

	echo json_encode( $data );
	return;
} else
	$data['success'] = 1;

$data['IDPenalizacion'] = $IDPenalizacion;

sqlsrv_close( $conn );
echo json_encode( $data );
?>