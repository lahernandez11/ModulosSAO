<?php
session_start();
require_once 'db/SAO1814DBConn.class.php';

$data['success'] = 1;
$data['errorMessage'] = null;

try {

	$conn = new SAO1814DBConn();

	$tsql = "{call [EstimacionesObra].[uspGuardaConceptoCobranza]( ?, ?, ?, ?, ? )}";

	$data['conceptosError'] = array();

	if( ! isset($_GET['conceptos']) )
		$conceptos = array();
	else
		$conceptos = $_GET['conceptos'];

	foreach ($conceptos as $key => $concepto) {
		
		try {
			// Limpia y valida la cantidad estimada
			$concepto['Cantidad'] = str_replace(',', '', $concepto['Cantidad']);
			$concepto['Importe'] = str_replace(',', '', $concepto['Importe']);

			if(
				preg_match('/^-?\d+(\.\d+)?$/', $concepto['Cantidad']) === 0 ) {
				//||
				//preg_match('/^-?\d+(\.\d+)?$/', $concepto['Cantidad']) === 0 ) {

				throw new Exception("La cantidad o importe son incorrectos.");
			}

			$params = array(

				array( $_GET['IDCobranza'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
				array( $concepto['IDConcepto'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT ),
				array( $concepto['Cantidad'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(17, 4) ),
				array( $concepto['PrecioUnitario'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 2) ),
				array( $concepto['Importe'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(19, 2) )
			);

			$conn->executeSP( $tsql, $params );

		} catch( Exception $e ) {

			$data['conceptosError'][] = array(

				'IDConcepto' 	 => $concepto['IDConcepto'],
				'Cantidad' 		 => $concepto['Cantidad'],
				'PrecioUnitario' => $concepto['PrecioUnitario'],
				'Importe' 		 => $concepto['Importe'],
				'ErrorMessage' 	 => $e->getMessage()
			);
		}
	}

	if( count($data['conceptosError']) ) {
		$data['success'] = 0;
		$data['errorMessage'] = "Los conceptos no se pudieron guardar.";
	}

} catch( Exception $e ) {

	$data['success'] = 0;
	$data['errorMessage'] = $e->getMessage();
}

unset($conn);

echo json_encode( $data );
?>