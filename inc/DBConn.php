<?php
function modulosSAO() {

	// PRODUCCION
	$Server = "(local)\SQL2012";

	sqlsrv_configure("WarningsReturnAsErrors", 0);

	$ConnectionInfo = array( "UID" => "App_ModulosSAO",
							 "PWD" => "@msApp85%",
							 "Database" => "ModulosSao",
							 "APP" => "ModulosSAO",
							 "ReturnDatesAsStrings" => "1",
							 "CharacterSet" => "UTF-8"
	);

	$conn = sqlsrv_connect($Server, $ConnectionInfo);

	if( $conn === false )
		return false;
	else
		return $conn;
}

function getErrorMessage() {
	
	$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
	
	$errorNumber = getErrorNumber();
	
	$errorMessage = '';
	
	//if( $errorNumber >= 50000 ) {
		$errorMessage = utf8_encode(str_replace("[Microsoft][SQL Server Native Client 10.0][SQL Server]", "", $errors[0]['message']));
	//}
	//else
	//	$errorMessage = 'Ocurrió un error al realizar la petición. Intentelo nuevamente.';

	logDBError();
	
	return $errorMessage;
}

function getErrorNumber() {
	
	$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
	
	return $errors[0]['code'];
}

function logDBError() {
	
	$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
	
	$errorMessage = date('d.m.Y h:i:s').' - [SQLSTATE]=>'.$errors[0]['SQLSTATE'].'[CODE]=>'.$errors[0]['code'].'[MESSAGE]=>'.$errors[0]['message'];
	
	error_log($errorMessage);
}
?>