<?php
session_start();
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

$tsql = "{call [EstimacionesSubcontratos].[uspDatosGeneralesEstimacion]( ? )}";

$params = array( array( $_GET['IDEstimacion'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT) );

$stmt = sqlsrv_query($conn, $tsql, $params);

if( !$stmt ) {
	$data['success'] = 0;
	$data['errorMessage'] = getErrorMessage();

	echo json_encode($data);
	return;
} else
	$data['success'] = 1;

$data['Estimacion'] = array();

while( $dataRow = sqlsrv_fetch_object($stmt) ) {
	
	$data['Estimacion'] = array(

		'IDSubcontratoCDC' => $dataRow->IDSubcontratoCDC,
		'IDEstimacion' => $dataRow->IDEstimacion,
		'NumeroFolio' => $dataRow->NumeroFolio,
		'NumeroFolioCDC' => $dataRow->NumeroFolioCDC,
		'Fecha' => $dataRow->Fecha,
		'FechaInicio' => $dataRow->FechaInicio,
		'FechaTermino' => $dataRow->FechaTermino,
		'Observaciones' => $dataRow->Observaciones,
		'NombreSubcontrato' => $dataRow->NombreSubcontrato,
		'NombreContratista' => $dataRow->NombreContratista,
	);
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode($data);
?>