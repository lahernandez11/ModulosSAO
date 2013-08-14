<?php
session_start();
require_once 'db/SAO1814DBConn.class.php';

$data['success'] = 1;
$data['errorMessage'] = null;
$data['noRows'] = false;
$data['noRowsMessage'] = null;

try {
    
    $conn = new SAO1814DBConn();

    $tsql = "{call [EstimacionesObra].[uspTotalesCobranza]( ? )}";

    $params = array(
        array($_GET['IDCobranza'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
    );

    $rsTotales = $conn->executeSP($tsql, $params);

    $data['Totales'] = array();

    foreach ( $rsTotales as $total ) {

        $data['Totales'][] = array(
								
			'Subtotal' => number_format($total->Subtotal, 2),
			'IVA' => number_format($total->IVA, 2),
			'Total' => number_format($total->Total, 2)
		);
    }

    if( sizeof($rsTotales) <= 0 ) {
        $data['noRows'] = true;
        $data['noRowsMessage'] = 'No se encontraron los totales de la transacción.';
    }

} catch( Exception $e ) {
	
	$data['success'] = 0;
	$data['errorMessage'] = $e->getMessage();
}

unset($conn);

echo json_encode($data);
?>