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
	$data['errorMessage'] = getErrorMessage();
	
	echo json_encode($data);
	return;
}

$tsql = "{call [AgrupacionFactVarios].[uspListaFacturaVarios]( ? )}";

$params = array( array($_GET['idProyecto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT) );

$stmt = sqlsrv_query($conn, $tsql, $params);

if( ! $stmt ) {
	$data['success'] = 0;
	$data['errorMessage'] = getErrorMessage();

	echo json_encode($data);
	return;
} else
	$data['success'] = 1;

$data['Proveedores'] = array();

$ultimoProveedor = null;
$indexProveedor  = null;
$ultimaFactura   = null;
$indexFactura    = null;

$counter = 0;

while( $dataRow = sqlsrv_fetch_object($stmt) ) {

	if( $dataRow->IDProveedor !== $ultimoProveedor ) {
		
		$data['Proveedores'][] = array(

			  'IDProveedor' => $dataRow->IDProveedor
			, 'Proveedor'   => $dataRow->Proveedor
		);

		$ultimoProveedor = $dataRow->IDProveedor;
		$ultimaFactura   = null;
		
		$indexProveedor = count($data['Proveedores']) - 1;
	}
	
	if( $dataRow->IDTransaccionCDC !== $ultimaFactura ) {
		
		$data['Proveedores'][$indexProveedor]['FacturasVarios'][] = array(

			  'IDTransaccionCDC'  => $dataRow->IDTransaccionCDC
  			, 'ReferenciaFactura' => $dataRow->ReferenciaFactura
  			, 'FechaFactura'      => $dataRow->FechaFactura
  		);

		$ultimaFactura = $dataRow->IDTransaccionCDC;
		
		$indexFactura = count($data['Proveedores'][$indexProveedor]['FacturasVarios']) - 1;
	}

	$data['Proveedores'][$indexProveedor]['FacturasVarios'][$indexFactura]['ItemsFactura'][] = array(

		  'IDItem' 					  => $dataRow->IDItem
		, 'Referencia' 				  => $dataRow->Referencia
		, 'IDAgrupadorNaturaleza' 	  => $dataRow->IDAgrupadorNaturaleza
		, 'AgrupadorNaturaleza' 	  => $dataRow->AgrupadorNaturaleza
		, 'IDAgrupadorFamilia' 		  => $dataRow->IDAgrupadorFamilia
		, 'AgrupadorFamilia' 		  => $dataRow->AgrupadorFamilia
		, 'IDAgrupadorInsumoGenerico' => $dataRow->IDAgrupadorInsumoGenerico
		, 'AgrupadorInsumoGenerico'   => $dataRow->AgrupadorInsumoGenerico
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