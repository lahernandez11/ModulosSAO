<?php
require_once 'setPath.php';
require_once 'db/SAODBConnFactory.class.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/PrecioVenta.class.php';
require_once 'models/Util.class.php';

$data['success'] = true;
$data['message'] = null;
$data['noRows']  = false;

try {

	Sesion::validaSesionAsincrona();

	if ( ! isset($_REQUEST['action']) ) {
		throw new Exception("No fue definida una acción");
	}

	switch ( $_REQUEST['action'] ) {

		case 'getPreciosVenta':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, $_GET['id_obra'] );
			$precios = PrecioVenta::getPreciosVenta( $obra);

			$data['conceptos'] = array();

			foreach ( $precios as $precio ) {
				
				$data['conceptos'][] = array(

					'IDConcepto'  	   => $precio->IDConcepto,
					'NumeroNivel' 	   => $precio->NumeroNivel,
					'Descripcion' 	   => $precio->Descripcion,
					'EsActividad' 	   => $precio->EsActividad,
					'ConPrecio' 	   => $precio->ConPrecio,
					'Unidad' 	   	   => $precio->Unidad,
					'PrecioProduccion' => Util::formatoNumerico( $precio->PrecioProduccion ),
					'PrecioEstimacion' => Util::formatoNumerico( $precio->PrecioEstimacion ),
					'FechaUltimaModificacion' => Util::formatoFecha( $precio->FechaUltimaModificacion ),
				);
			}

			break;

		case 'setPreciosVenta':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, $_POST['id_obra'] );

			$conceptos = is_array( $_POST['conceptos'] ) ? $_POST['conceptos'] : array();

			$data['conceptosError'] = array();

			$data['conceptosError'] = PrecioVenta::setPreciosVenta( $obra, $conceptos );

			break;
	}

} catch( Exception $e ) {
	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode( $data );
?>