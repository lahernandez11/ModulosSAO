<?php
class DBServerConnectionException extends Exception {}
class DBServerStatementExecutionException extends Exception {}

class DBNoResultsException extends Exception {
	
	public function __construct( $message ) {
		
		parent::__construct($message);
	}
}
?>