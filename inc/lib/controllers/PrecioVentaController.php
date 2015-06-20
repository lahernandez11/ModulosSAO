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

try
{
	Sesion::validaSesionAsincrona();

	if ( ! isset($_REQUEST['action']))
    {
		throw new Exception("No fue definida una acción");
	}

	switch ($_REQUEST['action'])
    {
		case 'getPreciosVenta':
			$conn = SAODBConnFactory::getInstance($_GET['base_datos']);
			$obra = new Obra($conn, $_GET['id_obra']);
			$precios = PrecioVenta::getPreciosVenta($obra);

			$data['conceptos'] = [];

			foreach ($precios as $precio)
            {
				$data['conceptos'][] = [
					'id_concepto'  	   => $precio->id_concepto,
					'numero_nivel' 	   => $precio->numero_nivel,
					'clave_concepto'   => $precio->clave_concepto,
					'descripcion' 	   => $precio->descripcion,
					'es_actividad' 	   => $precio->es_actividad,
					'con_precio' 	   => $precio->con_precio,
					'unidad' 	   	   => $precio->unidad,
					'precio_produccion' => $precio->precio_produccion,
					'precio_estimacion' => $precio->precio_estimacion,
					'updated_at' => Util::formatoFecha($precio->updated_at),
				];
			}
			break;

		case 'setPreciosVenta':
			$conn = SAODBConnFactory::getInstance($_POST['base_datos']);
			$obra = new Obra($conn, $_POST['id_obra']);

			$conceptos = is_array($_POST['conceptos']) ? $_POST['conceptos'] : [];

			$data['conceptosError'] = [];
			$data['conceptosError'] = PrecioVenta::setPreciosVenta($obra, $conceptos);
			break;
	}

}
catch (Exception $e)
{
	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
