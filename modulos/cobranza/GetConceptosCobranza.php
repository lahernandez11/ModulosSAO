<?php
session_start();
require_once 'db/SAO1814DBConn.class.php';

$data['success'] = 1;
$data['errorMessage'] = null;
$data['noRows'] = false;
$data['noRowsMessage'] = null;

try {
    
    $conn = new SAO1814DBConn();

    $tsql = "{call [EstimacionesObra].[uspConceptosCobranza]( ? )}";

    $params = array(
        
        array($_GET['IDCobranza'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
    );

    $rsConceptos = $conn->executeSP($tsql, $params);

    $data['Conceptos'] = array();

    foreach ( $rsConceptos as $concepto ) {

        $data['Conceptos'][] = array(

            'IDConcepto'            => $concepto->IDConcepto,
            'NumeroNivel'           => $concepto->NumeroNivel,
            'ConceptoMedible'       => $concepto->ConceptoMedible,
            'EsActividad'           => $concepto->EsActividad,
            'Concepto'              => $concepto->Concepto,
            'Unidad'                => $concepto->Unidad,
            'CantidadPresupuestada' => number_format($concepto->CantidadPresupuestada, 4),
            'CantidadEstimada'      => number_format($concepto->CantidadEstimada, 4),
            'CantidadCobrada'       => number_format($concepto->CantidadCobrada, 4),
            'PrecioUnitario'        => number_format($concepto->PrecioUnitario, 2),
            'ImporteCobrado'        => number_format($concepto->ImporteCobrado, 2)
        );
    }

    if( sizeof($rsConceptos) <= 0 ) {
        $data['noRows'] = true;
        $data['noRowsMessage'] = 'No se encontraron datos en esta transacción.';
    }

} catch( Exception $e ) {
	
	$data['success'] = 0;
	$data['errorMessage'] = $e->getMessage();
}

unset($conn);

echo json_encode($data);
?>