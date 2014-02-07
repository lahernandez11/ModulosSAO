<?php
require_once 'setPath.php';
require_once 'db/SAODBConnFactory.class.php';
require_once 'models/Sesion.class.php';
require_once 'models/Obra.class.php';
require_once 'models/Subcontrato.class.php';
require_once 'models/Addendum.class.php';
require_once 'models/ClasificadorSubcontrato.class.php';
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
		
		case 'getEmpresas':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, (int) $_GET['id_obra'] );

			$data['empresas'] = Subcontrato::getContratistas( $obra );
			break;

		case 'getListaSubcontratos':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, $_GET['id_obra'] );
			$id_empresa = (int) $_GET['id_empresa'];

			foreach ( Subcontrato::getTransaccionesContratista( $obra, $id_empresa ) as $transaccion ) {
				$data['transacciones'][] = array(
					'id_transaccion' => $transaccion->id_transaccion,
					'fecha' 		 => Util::formatoFecha( $transaccion->fecha ),
					'numero_folio' 	 => Util::formatoNumeroFolio( $transaccion->numero_folio ),
					'referencia' 	 => $transaccion->referencia
				);
			}
			break;

		case 'getDatosSubcontrato':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, $_GET['id_obra'] );
			$id_transaccion = (int) $_GET['id_transaccion'];

			$transaccion = new Subcontrato( $obra, $id_transaccion );
			$data['subcontrato'] = array(
				'tipo_contrato' 			 => $transaccion->getTipoContrato(),
				'referencia' 			 	 => $transaccion->getReferencia(),
				'empresa' 					 => $transaccion->getNombreContratista(),
				'observaciones' 			 => $transaccion->getObservaciones(),
				'descripcion' 				 => $transaccion->getDescripcion(),
				'id_clasificador' 			 => $transaccion->getIdClasificador(),
				'clasificador' 				 => $transaccion->getClasificador(),
				'monto_subcontrato' 		 => $transaccion->getMontoSubcontrato(),
				'monto_anticipo' 			 => $transaccion->getMontoAnticipo(),
				'porcentaje_retencion_fg' 	 => $transaccion->getPorcentajeRetencionFG(),
				'fecha_inicio_cliente' 	  	 => Util::formatoFecha( $transaccion->getFechaInicioCliente() ),
				'fecha_termino_cliente'   	 => Util::formatoFecha( $transaccion->getFechaTerminoCliente() ),
				'fecha_inicio_proyecto'   	 => Util::formatoFecha( $transaccion->getFechaInicioProyecto() ),
				'fecha_termino_proyecto'  	 => Util::formatoFecha( $transaccion->getFechaTerminoProyecto() ),
				'fecha_inicio_contratista'   => Util::formatoFecha( $transaccion->getFechaInicioContratista() ),
				'fecha_termino_contratista'  => Util::formatoFecha( $transaccion->getFechaTerminoContratista() ),
				'monto_venta_cliente' 		 => $transaccion->getMontoVentaCliente(),
				'monto_venta_actual_cliente' => $transaccion->getMontoVentaActualCliente(),
				'monto_inicial_pio' 		 => $transaccion->getMontoInicialPio(),
				'monto_actual_pio' 			 => $transaccion->getMontoActualPio()
			);
			break;

		case 'getClasificadores':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, $_GET['id_obra'] );

			$data['options'] = array();

			foreach ( ClasificadorSubcontrato::getClasificadores( $obra ) as $clasificador ) {
				$data['options'][] = array(
					'id'    => $clasificador->id_clasificador,
					'label' => $clasificador->clasificador
				);
			}
			break;

		case 'setClasificador':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, $_POST['id_obra'] );
			$id_transaccion = $_POST['id_transaccion'];
			$id_clasificador = $_POST['id_clasificador'];

			$data['options'] = array();

			$transaccion = new Subcontrato( $obra, $id_transaccion );
			$transaccion->setClasificador( $id_clasificador );
			break;

		case 'guardaTransaccion':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, $_POST['id_obra'] );
			$id_transaccion = $_POST['id_transaccion'];

			$transaccion = new Subcontrato( $obra, $id_transaccion );

			$transaccion->setDescripcion( $_POST['descripcion'] );

			$transaccion->setMontoSubcontrato( $_POST['monto_subcontrato'] );
			$transaccion->setMontoAnticipo( $_POST['monto_anticipo'] );
			$transaccion->setPorcentajeRetencionFG( $_POST['porcentaje_retencion_fg'] );
			$transaccion->setFechaInicioCliente( $_POST['fecha_inicio_cliente'] );
			$transaccion->setFechaTerminoCliente( $_POST['fecha_termino_cliente'] );
			$transaccion->setFechaInicioProyecto( $_POST['fecha_inicio_proyecto'] );
			$transaccion->setFechaTerminoProyecto( $_POST['fecha_termino_proyecto'] );
			$transaccion->setFechaInicioContratista( $_POST['fecha_inicio_contratista'] );
			$transaccion->setFechaTerminoContratista( $_POST['fecha_termino_contratista'] );
			$transaccion->setMontoVentaCliente( $_POST['monto_venta_cliente'] );
			$transaccion->setMontoVentaActualCliente( $_POST['monto_venta_actual_cliente'] );
			$transaccion->setMontoInicialPio( $_POST['monto_inicial_pio'] );
			$transaccion->setMontoActualPio( $_POST['monto_actual_pio'] );

			$transaccion->guardaTransaccion();
			break;

		case 'getAddendums':
			$conn = SAODBConnFactory::getInstance( $_GET['base_datos'] );
			$obra = new Obra( $conn, $_GET['id_obra'] );
			$id_transaccion = $_GET['id_transaccion'];

			$transaccion = new Subcontrato( $obra, $id_transaccion );
			$data['addendums'] = array();

			foreach ( $transaccion->getAddendums() as $addendum ) {
				$data['addendums'][] = array(
					'id_addendum' => $addendum->id_addendum,
					'fecha' => Util::formatoFecha( $addendum->fecha ),
					'monto' => $addendum->monto,
					'monto_anticipo' => $addendum->monto_anticipo,
					'porcentaje_retencion_fg' => $addendum->porcentaje_retencion_fg
				);
			}
			break;

		case 'addAddendum':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, $_POST['id_obra'] );
			$id_transaccion = $_POST['id_transaccion'];
			$fecha          = $_POST['fecha'];
			$monto          = $_POST['monto'];
			$monto_anticipo = $_POST['monto_anticipo'];
			$porcentaje_retencion_fg = $_POST['porcentaje_retencion_fg'];

			$addendum = new Addendum( $fecha, $monto, $monto_anticipo, $porcentaje_retencion_fg );
			$transaccion = new Subcontrato( $obra, $id_transaccion );
			$transaccion->addAddendum( $addendum );

			$data['addendum']['id_addendum'] = $addendum->getId();
			$data['addendum']['fecha'] = Util::formatoFecha( $addendum->getFecha() );
			$data['addendum']['monto'] = $addendum->getMonto();
			$data['addendum']['monto_anticipo'] = $addendum->getMontoAnticipo();
			$data['addendum']['porcentaje_retencion_fg'] = $addendum->getPorcentajeRetencionFG();
			break;

		case 'deleteAddendum':
			$conn = SAODBConnFactory::getInstance( $_POST['base_datos'] );
			$obra = new Obra( $conn, $_POST['id_obra'] );
			$id_transaccion = $_POST['id_transaccion'];
			$id_addendum    = $_POST['id_addendum'];

			$transaccion = new Subcontrato( $obra, $id_transaccion );

			$addendum = Addendum::getInstance( $transaccion, $id_addendum );
			$addendum->delete();
			break;
	}

} catch( Exception $e ) {
	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode( $data );
?>