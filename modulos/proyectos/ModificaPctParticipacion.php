<?php
session_start();
require_once("../../inc/DBConn.php");

$data['success'] = 0;
$data['errorMessage'] = null;

// Validacion
$isValid = preg_match('/^[0-9]{1,3}(\.\d+)?$/', $_POST['Pct']);

if( !$isValid ) {
	$data['success'] = 0;
	$data['errorMessage'] = 'El formato o rango de porcentaje indicado no es valido. Debe ser entre 1 y 100.';
	echo json_encode($data);
	return;
}

$conn = modulosSAO();

if( !$conn ) {
	$data['success'] = 0;
	$data['errorMessage'] = 'No se pudo establecer una conexion con el servidor de Base de Datos';
	
	echo json_encode($data);
	return;
}

$tsql = "{call [Proyectos].[uspModificaPctParticipacion](?, ?)}";

$params = array(
				    array($_POST['idProyecto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
				  , array($_POST['Pct'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(5,2))
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