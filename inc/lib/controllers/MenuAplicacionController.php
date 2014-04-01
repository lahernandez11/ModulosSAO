<?php
require_once 'setPath.php';
require_once 'models/App.class.php';

$data['success'] = true;
$data['message'] = null;

try {

	Sesion::validaSesionAsincrona();

	if ( ! isset($_GET['action']) ) {
		throw new Exception("No fue definida una acción");
	}

	switch ( $_GET['action'] ) {

		case 'getMenu':

			$data['menu'] = null;

			$menu = App::getMenu();

			$nivelAnterior = null;

			foreach ($menu as $nodo) {

				if( $nodo->NodoNivel > $nivelAnterior && $nodo->NodoNivel !== 1 ) {
					$data['menu'] .= '<ul>';
				}

				if( $nodo->NodoNivel < $nivelAnterior ) {
					$data['menu'] .= '</li></ul>';
				}

				if( $nodo->NodoNivel == $nivelAnterior )
					$data['menu'] .= '</li>';

				$data['menu'] .= '<li>';

				$direccion = $nodo->Direccion;
				// $icono = 'img/app/nav-icons/'.$nodo->NombreIcono;
				
				if( $nodo->EsSubmenu == 0 || strlen( $direccion ) > 0 )
					$direccion = ' href="'.$direccion.'"';
				else
					$direccion = '';
					
				$data['menu'] .= '<a'.$direccion.'>'.$nodo->Nombre.'</a>';
				
				$nivelAnterior = $nodo->NodoNivel;
			}

			$data['menu'] .= '</li>';

			if( $nivelAnterior !== 1 )
				$data['menu'] .= '</ul>';

			break;
	}
} catch( Exception $e ) {

	$data['success'] = false;
	$data['message'] = $e->getMessage();
}

echo json_encode($data);
?>