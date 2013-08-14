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

$tsql = "{call [AgrupacionInsumos].[uspListaInsumosProyecto]( ? )}";

$params = array( array($_GET['idProyecto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT) );

$stmt = sqlsrv_query($conn, $tsql, $params);

if( !$stmt ) {
	$data['success'] = 0;
	$data['errorMessage'] = getErrorMessage();

	echo json_encode($data);
	return;
} else
	$data['success'] = 1;

$data['Insumos'] = array();

$ultimaFamilia = null;
$indexFamilia = null;

$counter = 0;
while( $dataRow = sqlsrv_fetch_object($stmt) ) {

	if( $dataRow->idFamilia !== $ultimaFamilia ) {
		
		$data['Insumos']['Familias'][] = array( 'idFamilia' => $dataRow->idFamilia
								  			  , 'Familia' => $dataRow->Familia
								  			  , 'NumInsumosFamilia' => 0
								  			  , 'Insumos' => array()
								  			  );

		$ultimaFamilia = $dataRow->idFamilia;
		
		$indexFamilia = count($data['Insumos']['Familias']) - 1;
	}

	$data['Insumos']['Familias'][$indexFamilia]['Insumos'][] = 
		array( 'idInsumo' => $dataRow->idInsumo
			 , 'Insumo' => $dataRow->Insumo
			 , 'Unidad' => $dataRow->Unidad
			 , 'CodigoExterno' => $dataRow->CodigoExterno
			 , 'idAgrupadorNaturaleza' => $dataRow->idAgrupadorNaturaleza
			 , 'AgrupadorNaturaleza' => $dataRow->AgrupadorNaturaleza
			 , 'idAgrupadorFamilia' => $dataRow->idAgrupadorFamilia
			 , 'AgrupadorFamilia' => $dataRow->AgrupadorFamilia
			 , 'idAgrupadorInsumoGenerico' => $dataRow->idAgrupadorInsumoGenerico
			 , 'AgrupadorInsumoGenerico' => $dataRow->AgrupadorInsumoGenerico
			 );

	++$data['Insumos']['Familias'][$indexFamilia]['NumInsumos'];
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