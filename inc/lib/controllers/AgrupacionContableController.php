<?php 
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'db/ReportesSAOConn.class.php';
require_once 'models/CuentaContable.class.php';
require_once 'models/Empresa.class.php';
require_once 'models/Fondo.php';
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

		case 'getCuentas':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra']);
			$id_cuenta = (int) $_GET['id_cuenta'];

			$data['cuentas'] = array();
			$data['cuentas'] = CuentaContable::getCuentas( $obra, $id_cuenta );
			break;

		case 'getDatosCuenta':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra']);
			$id_cuenta = (int) $_GET['id_cuenta'];

			$cuenta = new CuentaContable( $obra, $id_cuenta );
			$data['cuenta'] = array();
			$data['cuenta'] = $cuenta->getDatosCuenta();
			break;

		case 'getAgrupadoresEmpresa':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$descripcion = $_GET['term'];
			$data['options'] = array();

			$fondos = Empresa::getEmpresas( $conn, $descripcion );

			foreach ( $fondos as $fondo ) {
				$data['options'][] = array(
					'id' 	    => $fondo->id_empresa,
					'label' => $fondo->razon_social
				);
			}
			break;

		case 'getAgrupadoresEmpresa2':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra']);
			$descripcion = $_GET['term'];
			$data['options'] = array();

			$fondos = Fondo::findAllFondoObra($conn, $obra, $descripcion);

			foreach ( $fondos as $fondo )
			{
				$data['options'][] = array(
					'id' 	=> $fondo->id_fondo,
					'label' => $fondo->descripcion
				);
			}
			break;

		case 'setAgrupadorNaturaleza':
		case 'setAgrupadorEmpresa':
		case 'setAgrupadorEmpresa2':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra']);
			$cuentas = $_POST['cuentas'];
			$id_agrupador = $_POST['id_agrupador'];

			foreach ($cuentas as $cuenta)
			{
				$cuenta_contable = new CuentaContable( $obra, $cuenta['id_cuenta'] );
				$cuenta_contable->setAgrupador( $id_agrupador, $_POST['action'] );
			}
			break;

		default:
			throw new Exception("Acción desconocida");
	}

} catch( Exception $e ) {

	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);