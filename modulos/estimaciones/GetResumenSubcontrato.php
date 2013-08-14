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

if( $_GET['IDEstimacion'] == 0 )
	$_GET['IDEstimacion'] = null;

$tsql = "{ call [EstimacionesSubcontratos].[uspResumenSubcontrato]( ?, ? )}";

$params = array(
				array($_GET['idSubcontrato'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
				array($_GET['IDEstimacion'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
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

$data['Subcontrato']['Actividades'] = array();

while( $dataRow = sqlsrv_fetch_object($stmt) ) {
	
	$data['Subcontrato']['Actividades'][] =
		array(
			'idConceptoContrato' => $dataRow->idConceptoContrato,
			'NumeroNivel' => $dataRow->NumeroNivel,
			'Concepto' => $dataRow->Concepto,
			'EsActividad' => $dataRow->EsActividad,
			'Unidad' => $dataRow->Unidad,
			'CantidadSubcontratada' => ($dataRow->EsActividad == 1) ? number_format($dataRow->CantidadSubcontratada, 4) : '',
			'PrecioUnitario' =>($dataRow->EsActividad == 1) ? number_format($dataRow->PrecioUnitario, 2) : '',
			'CantidadEstimadaTotal' => ($dataRow->EsActividad == 1) ? number_format($dataRow->CantidadEstimadaTotal, 4) : '',
			'PctAvance' => ($dataRow->EsActividad == 1) ? $dataRow->PctAvance : '',
			'MontoEstimadoTotal' => ($dataRow->EsActividad == 1) ? number_format($dataRow->MontoEstimadoTotal, 2) : '',
			'CantidadSaldo' => ($dataRow->EsActividad == 1) ? number_format($dataRow->CantidadSaldo, 4) : '',
			'MontoSaldo' => ($dataRow->EsActividad == 1) ? number_format($dataRow->MontoSaldo, 2) : '',
			'CantidadEstimada' => ($dataRow->EsActividad == 1) ? number_format($dataRow->CantidadEstimada, 4) : '',
			'PctEstimado' => ($dataRow->EsActividad == 1) ? $dataRow->PctEstimado : '',
			'ImporteEstimado' => ($dataRow->EsActividad == 1) ? number_format($dataRow->ImporteEstimado, 2) : '',
			'IDConceptoDestino' => $dataRow->idConceptoPresupuesto,
			'Ruta' => ($dataRow->EsActividad == 1) ? $dataRow->Ruta : ''
		);
}

if( ! count($data['Subcontrato']['Actividades']) ) {
	$data['noRows'] = true;
	$data['noRowsMessage'] = 'No se encontraron conceptos subcontratados.';
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode($data);
?>