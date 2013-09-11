<?php
session_start();
require_once("../../inc/DBConn.php");

$data['success'] = 0;
$data['errorMessage'] = null;
$data['noRows'] = false;
$data['noRowsMessage'] = null;

$conn = modulosSAO();

if( ! $conn ) {
	$data['success'] = 0;
	$data['errorMessage'] = getErrorMessage();
	
	echo json_encode($data);
	return;
}

$tsql = "SELECT
			   [IDAgrupador]
			 , [Codigo] + ' ' + [Agrupador] AS [Agrupador]
		 FROM
		 	[Agrupadores].[vwAgrupadoresFamilia]
		 ORDER BY
		 	[Agrupador]";

$stmt = sqlsrv_query($conn, $tsql);

if( ! $stmt ) {
	$data['success'] = 0;
	$data['errorMessage'] = getErrorMessage();

	echo json_encode($data);
	return;
} else
	$data['success'] = 1;


$data['options'] = array();

$counter = 0;
while( $dataRow = sqlsrv_fetch_object($stmt) ) {
	
	$data['options'][] =
		array(
			  'id' => $dataRow->IDAgrupador
			, 'value' => $dataRow->Agrupador
	);

	++$counter;
}

if( $counter === 0 ) {
	$data['noRows'] = true;
	$data['noRowsMessage'] = 'No se encontraron opciones.';
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode($data);
?>