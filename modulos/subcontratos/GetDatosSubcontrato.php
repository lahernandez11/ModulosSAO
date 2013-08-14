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

$tsql = "{call [Subcontratos].[uspDatosSubcontrato]( ? )}";

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

while( $dataRow = sqlsrv_fetch_object($stmt) ) {
	$data['Subcontrato'] = $dataRow->Subcontrato;
	$data['Descripcion'] = $dataRow->Descripcion;
	$data['TipoContrato'] = $dataRow->TipoContrato;
	$data['EmpresaContratista'] = $dataRow->EmpresaContratista;
	
	$data['MontoSubcontrato'] = $dataRow->MontoSubcontrato;
	$data['MontoAnticipo'] = $dataRow->MontoAnticipo;
	$data['PctRetencionFG'] = $dataRow->PctRetencionFG;
	
	$data['idClasificador'] = $dataRow->idClasificador;
	$data['Clasificador'] = $dataRow->Clasificador;
	
	$data['FechaInicioCliente'] = $dataRow->FechaInicioCliente;
	$data['FechaTerminoCliente'] = $dataRow->FechaTerminoCliente;
	$data['FechaInicioProyecto'] = $dataRow->FechaInicioProyecto;
	$data['FechaTerminoProyecto'] = $dataRow->FechaTerminoProyecto;
	$data['FechaInicioContratista'] = $dataRow->FechaInicioContratista;
	$data['FechaTerminoContratista'] = $dataRow->FechaTerminoContratista;
	
	$data['MontoVentaCliente'] = $dataRow->MontoVentaCliente;
	$data['MontoVentaActualCliente'] = $dataRow->MontoVentaActualCliente;
	$data['MontoInicialPIO'] = $dataRow->MontoInicialPIO;
	$data['MontoActualPIO'] = $dataRow->MontoActualPIO;
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode($data);
?>