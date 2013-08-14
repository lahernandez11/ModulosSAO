<?php
session_start();
require_once 'db/SAO1814DBConn.class.php';

$data['success'] = 1;
$data['errorMessage'] = null;
$data['noRows'] = false;
$data['noRowsMessage'] = null;

try {
    
    $conn = new SAO1814DBConn();

    $tsql = "{call [EstimacionesObra].[uspDatosGeneralesEstimacionObra]( ? )}";

    $params = array(
        array($_GET['IDEstimacionObra'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT)
    );

    $rsDatosEstimacion = $conn->executeSP($tsql, $params);

    $data['DatosEstimacion'] = array();

    foreach ( $rsDatosEstimacion as $datos ) {

    	$data['DatosEstimacion'][] = array(

    		'IDEstimacionObra' => $datos->IDEstimacionObra,
    		'NumeroFolio'      => Util::formatoNumeroFolio($datos->NumeroFolio),
    		'Fecha'            => $datos->Fecha,
    		'Referencia'       => $datos->Referencia
    	);
	}

} catch( Exception $e ) {
	
	$data['success'] = 0;
	$data['errorMessage'] = $e->getMessage();
}

$data['Estimacion'] = array();

while( $dataRow = sqlsrv_fetch_object($stmt) ) {
	
	$data['Estimacion'] = array(

		'IDSubcontratoCDC' => $dataRow->IDSubcontratoCDC,
		'IDEstimacion' => $dataRow->IDEstimacion,
		'NumeroFolio' => $dataRow->NumeroFolio,
		'NumeroFolioCDC' => $dataRow->NumeroFolioCDC,
		'Fecha' => $dataRow->Fecha,
		'FechaInicio' => $dataRow->FechaInicio,
		'FechaTermino' => $dataRow->FechaTermino,
		'Observaciones' => $dataRow->Observaciones,
		'NombreSubcontrato' => $dataRow->NombreSubcontrato,
		'NombreContratista' => $dataRow->NombreContratista,
	);
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode($data);
?>