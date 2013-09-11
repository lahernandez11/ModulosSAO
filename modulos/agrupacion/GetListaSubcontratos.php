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

$tsql = "{call [AgrupacionSubcontratos].[uspListaSubcontratos]( ? )}";

$params = array( array($_GET['idProyecto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT) );

$stmt = sqlsrv_query($conn, $tsql, $params);

if( ! $stmt ) {
	$data['success'] = 0;
	$data['errorMessage'] = getErrorMessage();

	echo json_encode($data);
	return;
} else
	$data['success'] = 1;

$data['Subcontratos'] = array();

$ultimoContratista = null;
$indexContratista = null;
$ultimoSubcontrato = null;
$indexSubcontrato = null;

$counter = 0;
while( $dataRow = sqlsrv_fetch_object($stmt) ) {

	if( $dataRow->IDEmpresa !== $ultimoContratista ) {
		
		$data['Subcontratos']['Contratistas'][] =
			array(
				  'idContratista'   => $dataRow->IDEmpresa
				, 'Contratista'     => $dataRow->Empresa
				, 'NumSubcontratos' => 0
				, 'Subcontratos'    => array()
			);

		$ultimoContratista = $dataRow->IDEmpresa;
		$ultimoSubcontrato = null;
		
		$indexContratista = count($data['Subcontratos']['Contratistas']) - 1;
	}
	
	if( $dataRow->IDSubcontrato !== $ultimoSubcontrato ) {
		
		$data['Subcontratos']['Contratistas'][$indexContratista]['Subcontratos'][] = 
			array(
				  'idSubcontrato'  => $dataRow->IDSubcontrato
				, 'Subcontrato'    => $dataRow->Subcontrato
				, 'NumActividades' => 0
				, 'Actividades'    => array()
			);

		$ultimoSubcontrato = $dataRow->IDSubcontrato;
		
		$indexSubcontrato = count($data['Subcontratos']['Contratistas'][$indexContratista]['Subcontratos']) - 1;
	}

	$data['Subcontratos']['Contratistas'][$indexContratista]['Subcontratos'][$indexSubcontrato]['Actividades'][] = 
		array(
			  'idActividad' 		  => $dataRow->IDActividad
			, 'Actividad' 		      => $dataRow->Actividad
			, 'Unidad' 				  => $dataRow->Unidad
			, 'idAgrupadorNaturaleza' => $dataRow->IDAgrupadorNaturaleza
			, 'AgrupadorNaturaleza'   => $dataRow->AgrupadorNaturaleza
			, 'idAgrupadorFamilia'    => $dataRow->IDAgrupadorFamilia
			, 'AgrupadorFamilia'      => $dataRow->AgrupadorFamilia
			, 'idAgrupadorInsumoGenerico' => $dataRow->IDAgrupadorInsumoGenerico
			, 'AgrupadorInsumoGenerico'   => $dataRow->AgrupadorInsumoGenerico
		);

	++$data['Subcontratos']['Contratistas'][$indexContratista]['Subcontratos'][$indexSubcontrato]['NumActividades'];
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