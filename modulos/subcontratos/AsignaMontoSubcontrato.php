<?php
session_start();
require_once("../../inc/DBConn.php");

$data['success'] = 0;
$data['errorMessage'] = null;
$data['isDataValid'] = 0;

// Limpieza de caracteres
$_POST['Monto'] = str_replace(',', '', $_POST['Monto']);

// Validacion
$isValid = preg_match('/^-?\d+(\.\d+)?$/', $_POST['Monto']);

if( !$isValid ) {
	$data['errorMessage'] = 'El valor o formato del monto es incorrecto.';
	echo json_encode($data);
	return;
}

$data['isDataValid'] = 1;

$conn = modulosSAO();

if( !$conn ) {
	$data['errorMessage'] = 'No se pudo establecer una conexion con el servidor de Base de Datos';
	
	echo json_encode($data);
	return;
}

$tsql = "{call [Subcontratos].[uspAsignaMontoSubcontrato](?, ?)}";

$params = array(
				    array($_POST['idSubcontrato'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
				  , array($_POST['Monto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_MONEY)
			   );

$stmt = sqlsrv_query($conn, $tsql, $params);

if( !$stmt ) {
	$data['success'] = 0;
	$data['errorMessage'] = getErrorMessage();

	echo json_encode($data);
	return;
}
else
	$data['success'] = 1;

sqlsrv_close($conn);

echo json_encode($data);
?>