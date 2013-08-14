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

$tsql = "{call [Subcontratos].[uspAddendumsSubcontrato]( ? )}";

$params = array(
				  array($_GET['idSubcontrato'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
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

$data['Addendums'] = array();

$counter = 0;
while( $dataRow = sqlsrv_fetch_object($stmt) ) {
	
	$data['Addendums'][] = array( 'idAddendum' => $dataRow->idAddendum
							  , 'Fecha' => $dataRow->Fecha
							  , 'Monto' => $dataRow->Monto
							  , 'MontoAnticipo' => $dataRow->MontoAnticipo
							  , 'PctRetencionFG' => $dataRow->PctRetencionFG
							  );
	++$counter;
}

if( $counter === 0 ) {
	$data['noRows'] = true;
	$data['noRowsMessage'] = 'No se encontraron addendums registrados.';
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode($data);
?>