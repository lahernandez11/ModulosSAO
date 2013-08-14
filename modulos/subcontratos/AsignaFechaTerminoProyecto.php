<?php
session_start();
require_once("../../inc/DBConn.php");

$data['success'] = 0;
$data['errorMessage'] = null;

// Validacion
$isValid = preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $_POST['Fecha']);

if( !$isValid ) {
	$data['success'] = 0;
	$data['errorMessage'] = 'La fecha elegida no es valida.';
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

$tsql = "{call [Subcontratos].[uspAsignaFechaTerminoProyecto](?, ?)}";

$params = array(
				    array($_POST['idSubcontrato'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
				  , array($_POST['Fecha'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE)
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