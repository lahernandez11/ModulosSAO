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

$tsql = "{call [Subcontratos].[uspListaSubcontratos]( ? )}";

$params = array(
				array( 1/*$_SESSION['uid']*/, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
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

$indexProyecto = null;
$ultimoProyecto = null;

$indexContratista = null;
$ultimoContratista = null;

$counter = 0;

$data['Subcontratos'] = array(
								'Proyectos' => array()
							 );

while( $dataRow = sqlsrv_fetch_object($stmt) ) {
	
	if( $dataRow->idProyecto != $ultimoProyecto ) {
		
		$data['Subcontratos']['Proyectos'][] = 
			array( 'idProyecto' => $dataRow->idProyecto
			     , 'Proyecto' => $dataRow->Proyecto
			     , 'Contratistas' => array()
			     );
			   
		$ultimoProyecto = $dataRow->idProyecto;
		$ultimoContratista = null;
		
		$indexProyecto = count($data['Subcontratos']['Proyectos']) - 1;
	}
	
	if( $dataRow->idEmpresaContratista != $ultimoContratista ) {
		
		$data['Subcontratos']['Proyectos'][$indexProyecto]['Contratistas'][] = 
			array( 'idEmpresaContratista' => $dataRow->idEmpresaContratista
				 , 'EmpresaContratista' => $dataRow->EmpresaContratista
				 , 'Subcontratos' => array()
				 );
				 
		$ultimoContratista = $dataRow->idEmpresaContratista;
		$indexContratista = count($data['Subcontratos']['Proyectos'][$indexProyecto]['Contratistas']) - 1;
	}
	
	$data['Subcontratos']['Proyectos'][$indexProyecto]['Contratistas'][$indexContratista]['Subcontratos'][] =
		array( 'idSubcontrato' => $dataRow->idSubcontrato
			 , 'NombreSubcontrato' => $dataRow->NombreSubcontrato
			 );
	
	++$counter;
}

if( $counter === 0 ) {
	$data['noRows'] = 1;
	$data['noRowsMessage'] = 'No se encontraron subcontratos registrados.';
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode($data);
?>