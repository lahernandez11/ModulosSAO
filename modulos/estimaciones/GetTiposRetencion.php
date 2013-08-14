<?php
session_start();
require_once("../../inc/DBConn.php");

$data['success'] = 0;
$data['errorMessage'] = null;

$conn = modulosSAO();

if( ! $conn ) {
	$data['success'] = 0;
	$data['errorMessage'] = 'No se pudo establecer una conexion con el servidor de Base de Datos';
	
	echo json_encode($data);
	return;
}

$tsql = "{call [EstimacionesSubcontratos].[uspListaTiposRetencion]}";

$stmt = sqlsrv_query($conn, $tsql);

if( ! $stmt ) {
	$data['success'] = 0;
	$data['errorMessage'] = getErrorMessage();

	echo json_encode($data);
	return;
}
else
	$data['success'] = 1;

$data['options'] = array();

while( $dataRow = sqlsrv_fetch_object($stmt) ) {
	
	$data['options'][] = array( 'option' => '<option value="'.$dataRow->IDTipoRetencion.'">'.$dataRow->TipoRetencion.'</option>');
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode($data);
?>