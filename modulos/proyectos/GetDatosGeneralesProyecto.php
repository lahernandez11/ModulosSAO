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

$tsql = "{call [Proyectos].[uspDatosGeneralesProyecto]( ? )}";

$params = array(
				  array($_GET['idProyecto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
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

while( $datos = sqlsrv_fetch_object($stmt) ) {

	$data['Nombre'] = $datos->Nombre;
	$data['Descripcion'] = $datos->Descripcion;
	$data['TipoProyecto'] = $datos->TipoProyecto;
	$data['Empresa'] = $datos->Empresa;
	$data['Estado'] = $datos->Estado;
	$data['Direccion'] = $datos->Direccion;
	$data['FechaInicio'] = $datos->FechaInicio;
	$data['FechaTermino'] = $datos->FechaTermino;
	$data['FechaInicioContrato'] = $datos->FechaInicioContrato;
	$data['FechaTerminoContrato'] = $datos->FechaTerminoContrato;
	$data['PctMetaUtilidadCorporativo'] = $datos->PctMetaUtilidadCorporativo;
	$data['PctMetaUtilidadObra'] = $datos->PctMetaUtilidadObra;
	$data['PctParticipacion'] = $datos->PctParticipacion;
	
	$data['MontoVentaContrato'] = $datos->MontoVentaContrato;
	$data['MontoActualContrato'] = $datos->MontoActualContrato;
	$data['MontoInicialPIO'] = $datos->MontoInicialPIO;
	$data['MontoActualPIO'] = $datos->MontoActualPIO;
	
	$data['EstaActivo'] = $datos->EstaActivo;
	$data['VisibleEnApps'] = $datos->VisibleEnApps;
	$data['VisibleEnReportes'] = $datos->VisibleEnReportes;
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode($data);
?>