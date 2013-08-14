<?php
session_start();
require_once("../../inc/DBConn.php");

$data['success'] = 0;
$data['errorMessage'] = null;

if( ! isset($_POST['id']) ) {
	$data['errorMessage'] = 'No se especifico una insumo.';
	echo json_encode($data);
	return;
}

if( ! isset($_POST['idAgrupador']) ) {
	$data['errorMessage'] = 'No se especifico un agrupador para el insumo.';
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

$tsql = "{call [AgrupacionInsumos].[uspAgrupaInsumo]( ?, ?, ? )}";

$params = array(
	  array($_POST['idProyecto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	, array($_POST['id'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
	, array($_POST['idAgrupador'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
);

$stmt = sqlsrv_query($conn, $tsql, $params);

if( ! $stmt ) {
	
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