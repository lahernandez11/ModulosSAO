<?php
session_start();
require_once("../../inc/DBConn.php");

$data['success'] = 0;
$data['errorMessage'] = null;
$data['isDataValid'] = 0;

// Limpieza de caracteres
$_POST['Monto'] = str_replace(',', '', $_POST['Monto']);
$_POST['MontoAnticipo'] = str_replace(',', '', $_POST['MontoAnticipo']);

// Validacion

// Fecha
$isValid = preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $_POST['Fecha']);

if( !$isValid ) {
	$data['errorMessage'] = 'La fecha elegida no es valida.';
	echo json_encode($data);
	return;
}

// Monto
$isValid = preg_match('/^-?\d+(\.\d+)?$/', $_POST['Monto']);

if( !$isValid ) {
	$data['errorMessage'] = 'El valor o formato del monto es incorrecto.';
	echo json_encode($data);
	return;
}

// Monto Anticipo
$isValid = preg_match('/^-?\d+(\.\d+)?$/', $_POST['MontoAnticipo']);

if( !$isValid ) {
	$data['errorMessage'] = 'El valor o formato del monto de anticipo es incorrecto.';
	echo json_encode($data);
	return;
}

// Porcentaje de Retencion de Fondo de Garantia
$isValid = preg_match('/^\d\d{0,2}?(\.\d{1,2})?$/', $_POST['PctRetFG']);

if( !$isValid ) {
	$data['errorMessage'] = 'El valor o formato del porcentaje de retencion es incorrecto.';
	echo json_encode($data);
	return;
}

$data['isDataValid'] = 1;

$conn = modulosSAO();

if( !$conn ) {
	$data['success'] = 0;
	$data['errorMessage'] = 'No se pudo establecer una conexion con el servidor de Base de Datos';
	
	echo json_encode($data);
	return;
}

$tsql = "{call [Subcontratos].[uspRegistraAddendum](?, ?, ?, ?, ?, ?)}";

$idAddendum = null;

$params = array(
				  array($_POST['idSubcontrato'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
				, array($_POST['Fecha'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE)
				, array($_POST['Monto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_MONEY)
				, array($_POST['MontoAnticipo'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_MONEY)
				, array($_POST['PctRetFG'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(5, 2))
				, array($idAddendum, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT)
			   );

$stmt = sqlsrv_query($conn, $tsql, $params);

if( !$stmt ) {
	$data['success'] = 0;
	$data['errorMessage'] = getErrorMessage();

	echo json_encode($data);
	return;
}
else {
	$data['success'] = 1;
	$data['idAddendum'] = $idAddendum;
}
sqlsrv_close($conn);

echo json_encode($data);
?>