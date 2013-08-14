<?php
class Overloading {

	public function __construct() {

		$params = func_get_args();

		switch ( func_num_args() ) {
			case 2:
				return call_user_method_array("instanceFromID", $this, $params);
				break;
			
			default:
				
				break;
		}
		print_r( func_get_args() );
		print_r( func_num_args() );
	}

	private function instanceFromID( $IDTransaccion, $asd ) {
		echo($IDTransaccion);
		echo($asd);
		return $this;
	}
}

//$var = new Overloading( 1, 2, 3, 4);
$var2 = new Overloading( "uno", "dos");

//$var3 = new Overloading( 1, 4, 5);
?>