<?php
session_start();
require_once("../../inc/DBConn.php");

$data['success'] = 0;
$data['errorMessage'] = null;

// Validacion
$isValid = preg_match('/^(\w|\W){5,}$/', $_POST['NombreProyecto']);

if( !$isValid ) {
	$data['success'] = 0;
	$data['errorMessage'] = 'El nombre del proyecto debe contener al menos 5 caracteres.';
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

$tsql = "{call [Proyectos].[uspModificaProyecto]( ?, ? )}";

$params = array(
				    
				    array($_POST['idProyecto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
				  , array($_POST['NombreProyecto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_NVARCHAR(100))
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