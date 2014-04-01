<ul id="app-nav">
<?php
	require_once 'setPath.php';
	require_once 'models/App.class.php';

	$dataMenu = App::getMenu();

	$nivelAnterior = null;
	$menu = '';

	foreach ($dataMenu as $nodo) {

		if( $nodo->NodoNivel > $nivelAnterior && $nodo->NodoNivel !== 1 ) {
			$menu .= '<ul>';
		}

		if( $nodo->NodoNivel < $nivelAnterior ) {
			$menu .= '</li></ul>';
		}

		if( $nodo->NodoNivel == $nivelAnterior )
			$menu .= '</li>';

		$menu .= '<li>';

		$direccion = $nodo->Direccion;
		// $icono = 'img/app/nav-icons/'.$nodo->NombreIcono;
		
		if( $nodo->EsSubmenu == 0 || strlen( $direccion ) > 0 )
			$direccion = ' href="'.$direccion.'"';
		else
			$direccion = '';
			
		$menu .= '<a'.$direccion.'>'.$nodo->Nombre.'</a>';
		
		$nivelAnterior = $nodo->NodoNivel;
	}

	$menu .= '</li>';

	if( $nivelAnterior !== 1 )
		$menu .= '</ul>';

	echo $menu;
?>
</ul>