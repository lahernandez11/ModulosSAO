<?php 
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'db/SAO1814DBConn.class.php';
require_once 'models/AgrupacionGastosVarios.class.php';
require_once 'models/AgrupadorInsumo.class.php';
require_once 'models/Util.class.php';

$data['success'] = true;
$data['message'] = null;
$data['noRows']  = false;

try {

	Sesion::validaSesionAsincrona();

	if ( ! isset($_REQUEST['action']) ) {
		throw new Exception("No fue definida una acción");
	}

	$conn = new SAO1814DBConn();

	$IDProyecto = (int) $_REQUEST['IDProyecto'];
	$IDObra 	= Obra::getIDObraProyecto($IDProyecto);
	
	switch ( $_REQUEST['action'] ) {

		case 'getGastosVarios':
			
			$gastos = AgrupacionGastosVarios::getGastosVarios($conn, $IDObra);

			$data['gastos'] = array();

			$lastProveedor = null;
			$ixProveedor   = null;
			$lastFactura   = null;
			$ixFactura     = null;

			foreach ($gastos as $gasto) {

				if( $gasto->id_empresa !== $lastProveedor ) {
					
					$data['data']['gastos'][] = array(

						  'id_empresa'  => $gasto->id_empresa
						, 'proveedor'   => $gasto->proveedor
						, 'facturas'	=> array()
					);

					$lastProveedor = $gasto->id_empresa;
					$lastFactura   = null;
					
					$ixProveedor = count($data['data']['gastos']) - 1;
				}
				
				if( $gasto->id_factura !== $lastFactura ) {
					
					$data['data']['gastos'][$ixProveedor]['facturas'][] = array(

						  'id_factura'  	   => $gasto->id_factura
			  			, 'referencia_factura' => $gasto->referencia_factura
			  			, 'items' => array()
			  		);

					$lastFactura = $gasto->id_factura;
					
					$ixFactura = count($data['data']['gastos'][$ixProveedor]['facturas']) - 1;
				}

				$data['data']['gastos'][$ixProveedor]['facturas'][$ixFactura]['items'][] = array(
					  'id_item' 				=> $gasto->id_item
					, 'referencia' 				=> $gasto->referencia
					, 'id_agrupador_naturaleza' => $gasto->id_agrupador_naturaleza
					, 'agrupador_naturaleza' 	=> $gasto->agrupador_naturaleza
					, 'id_agrupador_familia' 	=> $gasto->id_agrupador_familia
					, 'agrupador_familia' 		=> $gasto->agrupador_familia
					, 'id_agrupador_familia'    => $gasto->id_agrupador_familia
					, 'agrupador_familia'   	=> $gasto->agrupador_familia
				);
			}

			if (count($gastos) < 1) {
				$data['noRows'] = true;
				$data['message'] = "No se encontraron datos";
			}
			break;

		case 'setAgrupador':
			
			$id_agrupador = $_POST['id_agrupador'];
			$id_factura   = $_POST['id_factura'];
			$id_item = $_POST['id'];

			$agrupador = new AgrupadorInsumo($conn, $id_agrupador);
			
			AgrupacionGastosVarios::setAgrupador($conn, $IDObra, $id_factura, $id_item, $agrupador);
			break;

		default:
			throw new Exception("Acción desconocida");
	}

} catch( Exception $e ) {

	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>