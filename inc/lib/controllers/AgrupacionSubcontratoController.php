<?php 
require_once 'setPath.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'db/SAODBConnFactory.class.php';
require_once 'models/Subcontrato.class.php';
require_once 'models/AgrupadorInsumo.class.php';
require_once 'models/Util.class.php';

$data['success'] = true;
$data['message'] = null;
$data['noRows']  = false;

try {

	Sesion::validaSesionAsincrona();

	if ( ! isset( $_REQUEST['action'] ) ) {
		throw new Exception("No fue definida una acción");
	}
	
	switch ( $_REQUEST['action'] ) {

		case 'getSubcontratos':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn,  (int) $_GET['id_obra'] );

			$data['data'] = array();
			$datos = Subcontrato::getSubcontratosPorContratista( $obra );

			$lastContratista = null;
			$ixContratista = null;
			$lastSubcontrato = null;
			$ixSubcontrato = null;

			foreach ( $datos as $dataRow ) {

				if( $dataRow->id_empresa !== $lastContratista ) {
					
					$data['data']['Contratistas'][] =
						array(
							  'idContratista'   => $dataRow->id_empresa
							, 'Contratista'     => $dataRow->contratista
							, 'NumActividades' => 0
							, 'Subcontratos'    => array()
						);

					$lastContratista = $dataRow->id_empresa;
					$lastSubcontrato = null;
					
					$ixContratista = count($data['data']['Contratistas']) - 1;
				}
				
				if( $dataRow->id_subcontrato !== $lastSubcontrato ) {
					
					$data['data']['Contratistas'][$ixContratista]['Subcontratos'][] = 
						array(
							  'idSubcontrato'  => $dataRow->id_subcontrato
							, 'Subcontrato'    => $dataRow->referencia_subcontrato
							, 'NumActividades' => 0
							, 'Actividades'    => array()
						);

					$lastSubcontrato = $dataRow->id_subcontrato;
					
					$ixSubcontrato = count($data['data']['Contratistas'][$ixContratista]['Subcontratos']) - 1;
				}

				$data['data']['Contratistas'][$ixContratista]['Subcontratos'][$ixSubcontrato]['Actividades'][] = 
					array(
						  'idActividad' 		  => $dataRow->id_actividad
						, 'Actividad' 		      => $dataRow->actividad
						, 'Unidad' 				  => $dataRow->unidad
						, 'idAgrupadorNaturaleza' => $dataRow->id_agrupador_naturaleza
						, 'AgrupadorNaturaleza'   => $dataRow->agrupador_naturaleza
						, 'idAgrupadorFamilia'    => $dataRow->id_agrupador_familia
						, 'AgrupadorFamilia'      => $dataRow->agrupador_familia
						, 'idAgrupadorInsumoGenerico' => $dataRow->id_agrupador_insumo_generico
						, 'AgrupadorInsumoGenerico'   => $dataRow->agrupador_insumo_generico
					);

				++$data['data']['Contratistas'][$ixContratista]['Subcontratos'][$ixSubcontrato]['NumActividades'];
				++$data['data']['Contratistas'][$ixContratista]['NumActividades'];
			}
			
			break;

		case 'setAgrupador':

			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn,  $_POST['id_obra'] );
			
			$id_actividad   = (int) $_POST['id'];
			$id_agrupador   = (int) $_POST['id_agrupador'];
			$id_subcontrato = (int) $_POST['id_subcontrato'];
			$id_empresa     = (int) $_POST['id_empresa'];

			$agrupador = new AgrupadorInsumo( $conn, $id_agrupador );

			$subcontrato = new Subcontrato( $obra, $id_subcontrato );
			$subcontrato->setAgrupadorPartida( $id_actividad, $agrupador );
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