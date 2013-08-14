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

$tsql = "{call [EstimacionesSubcontratos].[uspObtieneTotalesEstimacion]( ? )}";

$params = array( array( $_GET['IDEstimacion'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ) );

$stmt = sqlsrv_query($conn, $tsql, $params);

if( ! $stmt ) {
	$data['success'] = 0;
	$data['errorMessage'] = getErrorMessage();

	echo json_encode($data);
	return;
}
else
	$data['success'] = 1;

$data['Totales'] = array();

while( $dataRow = sqlsrv_fetch_object($stmt) ) {
	
	$data['Totales'][] = array(
								'SumaImportes' => number_format($dataRow->SumaImportes, 2),
								'AmortizacionAnticipo' => number_format($dataRow->AmortizacionAnticipo, 2),
								'FondoGarantia' => number_format($dataRow->FondoGarantia, 2),
								'TotalRetenido' => number_format($dataRow->TotalRetenido, 2),
								'TotalPenalizado' => number_format($dataRow->TotalPenalizado, 2),
								'Subtotal' => number_format($dataRow->Subtotal, 2),
								'IVA' => number_format($dataRow->IVA, 2),
								'TotalIVARetenido' => number_format($dataRow->TotalIVARetenido, 2),
								'Total' => number_format($dataRow->Total, 2)
							  );
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode($data);
?>