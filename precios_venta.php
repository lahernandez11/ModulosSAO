<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!doctype html>
<html lang="es-mx">
<head>
	<meta charset="utf-8" />
	<title>Precios de Venta</title>

	<link rel="stylesheet" href="css/normalize.css" />
	<link rel="stylesheet" href="inc/js/jquery-ui/css/south-street/jquery-ui-1.8.18.custom.css" />
	<link rel="stylesheet" href="css/general.css" />
	<link rel="stylesheet" href="css/cobranza.css" />
	<link rel="stylesheet" href="css/jquery.notify.css" />
	
	<!--[if lt IE 9]><script src="inc/js/html5shiv.js"></script><![endif]-->
</head>

<body>
	<div id="app-wrapper">
		<?php include("inc/app-header.php"); ?>
		<nav>
			<ul id="app-nav"></ul>
		</nav>
		
		<div id="app-content">
			<div id="app-module">
				<div class="module-toolbar">
					<a class="button dd-list" id="bl-proyectos">
						<span class="button-text">Proyectos</span>
						<span class="icon flechita-abajo"></span>
					</a>
					<a id="guardar" class="button">
						<span class="label">Guardar</span>
					</a>
					<h2>Precios de Venta</h2>
				</div>
				<div id="app-module-content">
					<section id="tran">

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

	<div id="message-console">
		<span id="console-message"></span>
		<span id="console-toggler" class="open"></span>
	</div>
	<div id="cache"></div>

	<script type="text/template" id="concepto-template">
		<tr data-id="<%- IDConcepto %>" data-esactividad="<%- EsActividad %>">
			<td class="icon-cell">
				<a class="icon fixed"></a>
			</td>
			<<%= EsActividad ? 'td' : 'th' %> title="<%- Descripcion %>">
				<%= '&nbsp;&nbsp;'.repeat(NumeroNivel) + Descripcion %>
			</<%= EsActividad ? 'td' : 'th' %>>
			<td class="centrado"><%- Unidad %></td>
			<td class="editable-cell numerico"><%= EsActividad ? PrecioProduccion : '' %></td>
			<td class="editable-cell numerico"><%= EsActividad ? PrecioEstimacion : '' %></td>
			<td class="centrado"><%= ConPrecio ? FechaUltimaModificacion : '' %></td>
		</tr>
	</script>

	<script src="inc/js/lib/underscore-min.js"></script>
	<script src="inc/js/jquery-1.7.1.min.js"></script>
	<script src="inc/js/jquery-ui/js/jquery-ui-1.8.18.custom.min.js"></script>
	<script src="inc/js/jquery-ui/development-bundle/ui/i18n/jquery.ui.datepicker-es.js"></script>
	<script src="inc/js/general.js"></script>
	<script src="inc/js/jquery.buttonlist.js"></script>
	<script src="inc/js/jquery.listaTransacciones.js"></script>
	<script src="inc/js/jquery.uxtable.js"></script>
	<script src="inc/js/jquery.notify.js"></script>
	<script src="inc/js/precios_venta.js"></script>
</body>
</html>