<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8" />
	<title>Precios de Venta</title>
	<link href="css/general.css" type="text/css" rel="stylesheet" />
	<link href="css/cobranza.css" type="text/css" rel="stylesheet" />
	<link href="css/jquery.notify.css" type="text/css" rel="stylesheet" />
	<link href="inc/js/jquery-ui/css/south-street/jquery-ui-1.8.18.custom.css" type="text/css" media="all" rel="stylesheet" />
	<link href="css/superfish.css" type="text/css" rel="stylesheet" />
	<!--[if lt IE 9]><script src="inc/js/html5shiv.js"></script><![endif]-->
	<script src="inc/js/jquery-1.7.1.min.js"></script>
	<script src="inc/js/jquery-ui/js/jquery-ui-1.8.18.custom.min.js"></script>
	<script src="inc/js/jquery-ui/development-bundle/ui/i18n/jquery.ui.datepicker-es.js"></script>
	<script src="inc/js/hoverIntent.js"></script>
	<script src="inc/js/superfish.js"></script>
	<script src="inc/js/jquery.buttonlist.js"></script>
	<script src="inc/js/jquery.listaTransacciones.js"></script>
	<script src="inc/js/jquery.uxtable.js"></script>
	<script src="inc/js/jquery.notify.js"></script>
	<script src="inc/js/general.js"></script>
	<script src="inc/js/precios_venta.js"></script>
</head>

<body>
	<div id="app-wrapper">
		<header id="app-header">
			<?php include("inc/app-header.php"); ?>
		</header> <!-- app-header -->
		
		<nav>
			<ul id="app-nav" class="sf-menu"></ul> 
		</nav> <!-- app-nav -->
		
		<div id="app-content">
			<div id="app-module">
				<header id="app-module-header">
					<h4>-> PRECIOS DE VENTA</h4>
				</header>
				<div class="module-toolbar">
					<a class="toolbar-button dd-list" id="bl-proyectos">
						<span class="button-text">Proyectos</span>
						<span class="icon"></span>
					</a>
					<a id="guardar" class="toolbar-button save"><span class="icon"></span>Guardar</a>
				</div>
				<div id="app-module-content">
					<section id="tran">
						<section id="tran-header">
							<section class="module-toolbar" id="tran-toolbar">
								<form>
									<input type="hidden" name="IDListaPreciosVenta" id="IDListaPreciosVenta" value="" />
								</form>
							</section>

							<section id="tran-info">
							</section> <!-- tran-info -->
						</section> <!-- tran-header -->

						<section id="tran-content">
							<table id="tabla-conceptos">
								<colgroup>
									<col class="icon"/>
									<col/>
									<col class="unidad"/>
									<col span="2" class="monto editable"/>
									<col class="monto"/>
								</colgroup>
								<thead>
									<tr>
										<th rowspan="2"></th>
										<th rowspan="2">Concepto</th>
										<th rowspan="2">Unidad</th>
										<th colspan="2">Precio Venta</th>
										<th rowspan="2">Última Modificación</th>
									</tr>
									<tr>
										<th>PRODUCCIÓN</th>
										<th>ESTIMACIÓN</th>
									</tr>
								</thead>
								<tbody></tbody>
							</table>
						</section> <!-- tran-content -->
					</section> <!-- tran -->

				</div> <!-- module-content -->
			</div> <!-- module -->
		</div> <!-- app-content -->

		<footer id="app-footer">
			<?php include("inc/app-footer.php"); ?>
		</footer> <!-- app-footer -->
	</div> <!-- app-wrapper -->

	<div id="message-console"><span id="console-message"></span><span id="console-toggler" class="open"></span></div>
	<div id="cache"></div>
</body>
</html>