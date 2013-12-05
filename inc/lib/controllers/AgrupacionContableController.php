<?php 
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'db/ReportesSAOConn.class.php';
require_once 'models/CuentaContable.class.php';
require_once 'models/AgrupadorCuentaContable.class.php';
require_once 'models/Empresa.class.php';
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
	$rsao_conn = new ReportesSAOConn();
	
	switch ( $_REQUEST['action'] ) {

		case 'getCuentas':
			$id_cuenta = (int) $_GET['id_cuenta'];

			$cuenta_contable = new CuentaContable($IDProyecto, $rsao_conn);
			$data['cuentas'] = array();
			$data['cuentas'] = $cuenta_contable->getCuentas($id_cuenta);
			break;

		case 'getDatosCuenta':
			$id_cuenta = (int) $_GET['id_cuenta'];
			$cuenta_contable = new CuentaContable($IDProyecto, $rsao_conn);
			$data['cuenta'] = array();
			$data['cuenta'] = $cuenta_contable->getDatosCuenta($id_cuenta);
			break;

		case 'getAgrupadoresProveedor':
		case 'getAgrupadoresTipoCuenta':

			$descripcion = $_GET['term'];
			$data['agrupadores'] = array();
			$data['agrupadores'] = AgrupadorCuentaContable::$_GET['action']($rsao_conn, $IDProyecto, $descripcion);

			break;

		case 'getAgrupadoresEmpresa':
			$descripcion = $_GET['term'];
			$data['agrupadores'] = array();

			$empresas = Empresa::getEmpresas($conn, $descripcion,
				array(Empresa::CONTRATISTA, Empresa::DESTAJISTA, Empresa::CONTRATISTA_PROVEEDOR));

			foreach ($empresas as $empresa) {
				$data['agrupadores'][] = array(
					'id' => $empresa->id_empresa,
					'agrupador' => $empresa->razon_social
				);
			}

			break;

		case 'setAgrupadorProveedor':
		case 'setAgrupadorTipoCuenta':
		case 'setAgrupadorEmpresa':
			$cuenta_contable = new CuentaContable($IDProyecto, $rsao_conn);
			$cuentas = $_POST['cuentas'];
			$id_agrupador = $_POST['id_agrupador'];

			foreach ($cuentas as $cuenta) {
				$cuenta_contable->setAgrupador($cuenta['id_cuenta'], $id_agrupador, $_POST['action']);
			}

			break;

		case 'addAgrupadorTipoCuenta':

			$descripcion = $_POST['descripcion'];

			$data['id_agrupador'] = AgrupadorCuentaContable::$_POST['action']($rsao_conn, $IDProyecto, $descripcion);
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