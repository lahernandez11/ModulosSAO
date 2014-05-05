<?php 
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'db/SAODBConnFactory.class.php';
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

	switch ( $_REQUEST['action'] ) {

		case 'getMateriales':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );

			$obra = new Obra( $conn,  (int) $_GET['id_obra'] );
			
			$data['data'] = array();

			$materiales = Material::getMaterialesObra( $obra );

			$ultimaFamilia = null;
			$indexFamilia = null;

			$counter = 0;
			foreach ( $materiales as $material ) {

				if ( $material->familia !== $ultimaFamilia ) {
					
					$data['data']['Familias'][] =
						array(
							  'idFamilia' => $material->id_familia
							, 'Familia'   => $material->familia
							, 'NumInsumos' => 0
							, 'Insumos'   => array()
						);

					$ultimaFamilia = $material->familia;
					
					$indexFamilia = count( $data['data']['Familias'] ) - 1;
				}

				$data['data']['Familias'][$indexFamilia]['Insumos'][] = 
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

				++$data['data']['Familias'][$indexFamilia]['NumInsumos'];
				++$counter;
			}

			break;

		case 'setAgrupador':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, (int) $_POST['id_obra'] );

			$id_agrupador = (int) $_POST['id_agrupador'];
			$id_material  = (int) $_POST['id'];

			$material  = new Material( $obra, $id_material );
			$agrupador = new AgrupadorInsumo( $conn, $id_agrupador );
			
			$material->setAgrupador( $agrupador );

			break;

		default:
			throw new Exception("Acción desconocida");
	}

} catch( Exception $e ) {
	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode( $data );
?>