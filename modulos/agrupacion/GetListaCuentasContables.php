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
	$data['errorMessage'] = getErrorMessage();
	
	echo json_encode($data);
	return;
}

$tsql = "{call [Agrupadores].[uspListaCuentasContables]( ? )}";

$params = array( array($_GET['idProyecto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT) );

$stmt = sqlsrv_query($conn, $tsql, $params);

if( !$stmt ) {
	$data['success'] = 0;
	$data['errorMessage'] = getErrorMessage();

	echo json_encode($data);
	return;
} else
	$data['success'] = 1;

$data['Cuentas'] = array();

$counter = 0;
while( $dataRow = sqlsrv_fetch_object($stmt) ) {

	$data['Cuentas'][] = 
		array( 'idCuenta' => $dataRow->idCuenta
			 , 'Codigo' => $dataRow->Codigo
			 , 'Nombre' => $dataRow->Nombre
			 , 'Afectable' => $dataRow->Afectable
			 , 'idAgrupadorNaturaleza' => $dataRow->idAgrupadorNaturaleza
			 , 'AgrupadorNaturaleza' => $dataRow->AgrupadorNaturaleza
			 );

	++$counter;
}

if( $counter === 0 ) {
	$data['noRows'] = true;
	$data['noRowsMessage'] = 'No se encontraron datos.';
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode($data);
?>