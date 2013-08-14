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
	$data['errorMessage'] = 'No se pudo establecer una conexion con el servidor de Base de Datos';
	
	echo json_encode($data);
	return;
}

$tsql = "{call [EstimacionesSubcontratos].[uspListaSubcontratos]( ? )}";

$params = array( array($_GET['idProyecto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT) );

$stmt = sqlsrv_query($conn, $tsql, $params);

if( ! $stmt ) {
	$data['success'] = 0;
	$data['errorMessage'] = getErrorMessage();

	echo json_encode($data);
	return;
}
else
	$data['success'] = 1;

$data['Subcontratos'] = array();

while( $dataRow = sqlsrv_fetch_object($stmt) ) {
	
	$data['Subcontratos'][] = 
		array(
			'idSubcontrato' => $dataRow->idSubcontrato,
			'Folio' => "#" . str_repeat( '0', 6 - strlen($dataRow->Folio) ) . $dataRow->Folio,
			'Referencia' => $dataRow->Referencia,
			'Empresa' => $dataRow->Empresa
		);
}

if( ! count( $data['Subcontratos'] ) ) {
	$data['noRows'] = true;
	$data['noRowsMessage']	= 'No se encontraron subcontratos para estimar';
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode($data);
?>