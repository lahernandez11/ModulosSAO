<?php 
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'db/SAO1814DBConn.class.php';
require_once 'models/Material.class.php';
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

		case 'getMateriales':
			$id_cuenta = (int) $_GET['id_cuenta'];

			
			$data['materiales'] = array();
			$materiales = Material::getMaterialesObra($IDObra, $conn);

			$ultimaFamilia = null;
			$indexFamilia = null;

			$counter = 0;
			foreach( $materiales as $material ) {

				if( $material->id_familia !== $ultimaFamilia ) {
					
					$data['materiales']['Familias'][] =
						array(
							  'idFamilia' => $material->id_familia
							, 'Familia'   => $material->familia
							, 'NumInsumosFamilia' => 0
							, 'Insumos'   => array()
						);

					$ultimaFamilia = $material->id_familia;
					
					$indexFamilia = count($data['materiales']['Familias']) - 1;
				}

				$data['materiales']['Familias'][$indexFamilia]['Insumos'][] = 
					array(
						  'idInsumo' 			  => $material->id_material
						, 'Insumo' 				  => $material->material
						, 'Unidad' 				  => $material->unidad
						, 'CodigoExterno' 		  => $material->codigo_externo
						, 'idAgrupadorNaturaleza' => $material->id_agrupador_naturaleza
						, 'AgrupadorNaturaleza'   => $material->agrupador_naturaleza
						, 'idAgrupadorFamilia' 	  => $material->id_agrupador_familia
						, 'AgrupadorFamilia' 	  => $material->agrupador_familia
						, 'idAgrupadorInsumoGenerico' => $material->id_agrupador_insumo_generico
						, 'AgrupadorInsumoGenerico'   => $material->agrupador_insumo_generico
					);

				++$data['materiales']['Familias'][$indexFamilia]['NumInsumos'];
				++$counter;
			}
			break;

		// case 'getDatosCuenta':
		// 	$id_cuenta = (int) $_GET['id_cuenta'];
		// 	$cuenta_contable = new CuentaContable($IDProyecto, $rsao_conn);
		// 	$data['cuenta'] = array();
		// 	$data['cuenta'] = $cuenta_contable->getDatosCuenta($id_cuenta);
		// 	break;

		case 'getAgrupadoresNaturaleza':
		case 'getAgrupadoresFamilia':
		case 'getAgrupadoresGenerico':

			// $descripcion = $_GET['term'];
			$data['options'] = array();
			$tipo = null;

			switch ($_GET['action']) {
				case 'getAgrupadoresNaturaleza':
					$tipo = AgrupadorInsumo::TIPO_NATURALEZA;
					break;
				
				case 'getAgrupadoresFamilia':
					$tipo = AgrupadorInsumo::TIPO_FAMILIA;
					break;

				case 'getAgrupadoresGenerico':
					$tipo = AgrupadorInsumo::TIPO_GENERICO;
					break;
			}

			$agrupadores = AgrupadorInsumo::getAgrupadoresInsumo(
				$conn, null, $tipo);

			foreach ($agrupadores as $agrupador) {
				$data['options'][] = array(
					'id' => $agrupador->id_agrupador,
					'value' => $agrupador->agrupador,
				);
			}

			break;

		case 'setAgrupador':
			
			$id_agrupador = $_POST['id_agrupador'];
			$id_material = $_POST['id_material'];

			$material = new Material($conn, $id_material);
			$agrupador = new AgrupadorInsumo($conn, $id_agrupador);
			
			$material->setAgrupador($IDObra, $agrupador);

			break;

		// case 'getAgrupadoresEmpresa':
		// 	$descripcion = $_GET['term'];
		// 	$data['agrupadores'] = array();

		// 	$empresas = Empresa::getEmpresas($conn, $descripcion);

		// 	foreach ($empresas as $empresa) {
		// 		$data['agrupadores'][] = array(
		// 			'id' => $empresa->id_empresa,
		// 			'agrupador' => $empresa->razon_social
		// 		);
		// 	}

		// 	break;

		// case 'setAgrupadorProveedor':
		// case 'setAgrupadorTipoCuenta':
		// case 'setAgrupadorEmpresa':
		// 	$cuenta_contable = new CuentaContable($IDProyecto, $rsao_conn);
		// 	$cuentas = $_POST['cuentas'];
		// 	$id_agrupador = $_POST['id_agrupador'];

		// 	foreach ($cuentas as $cuenta) {
		// 		$cuenta_contable->setAgrupador($cuenta['id_cuenta'], $id_agrupador, $_POST['action']);
		// 	}

		// 	break;

		// case 'addAgrupadorTipoCuenta':

		// 	$descripcion = $_POST['descripcion'];

		// 	$data['id_agrupador'] = AgrupadorCuentaContable::$_POST['action']($rsao_conn, $IDProyecto, $descripcion);
		// 	break;

		default:
			throw new Exception("Acción desconocida");
	}

} catch( Exception $e ) {

	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>