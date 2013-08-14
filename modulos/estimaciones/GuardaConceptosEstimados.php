<?php
require_once("../../inc/DBConn.php");

$data['success'] = 0;
$data['errorMessage'] = null;

// Si no hay conceptos estimados termina la ejecucion del script
// como satisfactoria
if( ! isset($_GET['conceptos']) ) {
	$data['success'] = 1;
	echo json_encode($data);
	return;
}

$conn = modulosSAO();

if( ! $conn ) {
	$data['success'] = 0;
	$data['errorMessage'] = 'No se pudo establecer una conexion con el servidor de Base de Datos';
	
	echo json_encode($data);
	return;
}

$tsql = "{call [EstimacionesSubcontratos].[uspEstimaConcepto]( ?, ?, ?, ? )}";

$data['conceptosError'] = array();

foreach ($_GET['conceptos'] as $key => $concepto) {

	// Lipia y valida la cantidad estimada
	$concepto['Importe'] = str_replace(',', '', $concepto['Importe']);
	$isValid = preg_match('/^-?\d+(\.\d+)?$/', $concepto['Importe']);

	// Si la cantidad no es valida agrega el concepto con error
	if( ! $isValid ) {

		$data['conceptosError'][] = array(
			'IDConcepto' => $concepto['IDConcepto'],
			'IDConceptoDestino' => $concepto['IDConceptoDestino'],
			'CantidadEstimada' => $concepto['Cantidad'],
			'Importe' => $concepto['Importe'],
			'ErrorMessage' => 'La cantidad ingresada no es correcta.'
		);

		continue;
	}

	$params = array(
		array( $_GET['IDEstimacion'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		array( $concepto['IDConcepto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		array( $concepto['IDConceptoDestino'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
		array( $concepto['Importe'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 2) )
	);

	$stmt = sqlsrv_query( $conn, $tsql, $params );

	// Si ocurre algun error al estimar el concepto en la ejecucion de la sentencia
	// sera agregado al arreglo de conceptos con error
	if( ! $stmt ) {

		$data['conceptosError'][] = array(
			'IDConcepto' => $concepto['IDConcepto'],
			'CantidadEstimada' => $concepto['Cantidad'],
			'ImporteEstimado' => $concepto['Importe'],
			'ErrorMessage' => getErrorMessage()
		);
	}
}

if( count($data['conceptosError']) ) {
	$data['success'] = 0;
	$data['errorMessage'] = "Los conceptos no se pudieron guardar.";
} else
	$data['success'] = 1;

sqlsrv_close( $conn );
echo json_encode( $data );
?>