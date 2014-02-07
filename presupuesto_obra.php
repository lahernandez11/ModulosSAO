<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
	<html lang="es">
	<head>
		<meta charset="utf-8">
		<title>Presupuesto de Obra</title>
		<link rel="stylesheet" href="css/normalize.css" />
		<link rel="stylesheet" href="inc/js/jquery-ui/css/south-street/jquery-ui-1.8.18.custom.css" />
		<link rel="stylesheet" href="css/general.css" />
		<link rel="stylesheet" href="css/jquery.notify.css" />
		
		<!--[if lt IE 9]><script src="inc/js/html5shiv.js"></script><![endif]-->
	</head>
	<body>
		<!-- <section>
			<div class="app-wrapper">
				<section class="titulo">
					<h2>Precios de Venta</h2>
				</section>
				<section class="toolbar">
					<a class="button dd-list" id="bl-proyectos">
							<span class="button-text">Proyectos</span>
							<span class="icon flechita-abajo"></span>
						</a>
						<a id="guardar" class="button">
							<span class="icon save"></span>
							<span class="label">Guardar</span>
						</a>
				</section>
			</div>
		</section> -->
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
						<span class="col-switch">
							<input type="checkbox" name="col-switch" id="partida" value="5">
							<label for="partida">Partida</label>
							<input type="checkbox" name="col-switch" id="subpartida" value="6">
							<label for="subpartida">Subpartida</label>
							<input type="checkbox" name="col-switch" id="actividad" value="6">
							<label for="actividad">Actividad</label>
						</span>
						<h2>Presupuesto de Obra</h2>
					</div>
					<div id="app-module-content">
						<section id="tran">

							<section id="tran-content">
								<table id="tabla-conceptos" class="stripped tabla-arbol">
									<colgroup>
										<col class="icon"/>
										<col class="icon"/>
										<col class="icon"/>
										<col class="clave"/>
										<col />
										<col class="unidad"/>
										<col span="3" class="monto"/>
									</colgroup>
									<thead>
										<tr>
											<th></th>
											<th></th>
											<th></th>
											<th>Clave</th>
											<th>Concepto</th>
											<th>Unidad</th>
											<th>Cantidad</th>
											<th>Precio</th>
											<th>Monto</th>
											<th class="partida">Agrupador Partida</th>
											<th class="subpartida">Agrupador Subpartida</th>
											<th class="actividad">Agrupador Actividad</th>
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

		<div id="dialog-propiedades-concepto" class="dialog" title="Propiedades">
			<form method="get" class="dialog-form form-concepto-properties">
				<!-- <label for="">Concepto</label>
				<textarea id="txtDescripcion" class="field"></textarea> -->
				<label for="txtAgrupadorSubpartida">Agrupador Partida</label>
				<!-- <a title="Eliminar agrupador" class="elimina-agrupador icon-close"></a> -->
				<input type="text" id="txtAgrupadorPartida" class="field" />
				
				<label for="txtAgrupadorSubpartida">Agrupador Subpartida</label>
				<input type="text" id="txtAgrupadorSubpartida" class="field" />
				
				<label for="txtAgrupadorActividad">Agrupador Actividad</label>
				<input type="text" id="txtAgrupadorActividad" class="field" />

				<section class="buttons">
					<input type="button" id="cerrar-concepto" name="cerrar" class="button" value="Cerrar" />
				</section>
			</form>
		</div>

		<div id="dialog-nuevo-agrupador" class="dialog" title="Nuevo Agrupador">
			<form method="get" class="dialog-form form-nuevo-agrupador">
				<label for="txtClaveAgrupador">Clave</label>
				<input type="text" id="txtClaveAgrupador" class="field" />
				<label for="txtDescripcionAgruapdor">Descripcion</label>
				<input type="text" id="txtDescripcionAgruapdor" class="field" />
				<section class="buttons">
					<input type="button" id="guardar_agrupador" name="guardar" class="button" value="Guardar" />
					<input type="button" id="cerrar_agrupador" name="cerrar" class="button" value="Cerrar" />
				</section>
			</form>
		</div>

		<div id="message-console">
			<span id="console-message"></span>
			<span id="console-toggler" class="open"></span>
		</div>
		
		<div id="cache"></div>

		<script type="template" id="template-concepto">
			<tr id="c-<%- id_concepto %>" data-nivel="<%- nivel %>" data-numeronivel="<%- numero_nivel %>" data-medible="<%- concepto_medible %>"class="concepto">
				<td class="icon-cell">
					<%= concepto_medible > 0 ? '<a class="icon-file"></a>' : '' %>
					<%= tipo_material === 1 ? '<a class="icon-database"></a>' : '' %>
					<%= tipo_material === 2 ? '<a class="icon-users"></a>' : '' %>
					<%= tipo_material === 4 ? '<a class="icon-hammer"></a>' : '' %>
					<%= tipo_material === 8 ? '<a class="icon-truck"></a>' : '' %>
				</td>
				<td class="icon-cell">
					<%= id_material > 0 ? '' : '<a href="" class="handle icon-plus"></a>' %>
				</td>
				<td class="icon-cell">
					<a href="" class="select icon-checkbox-unchecked"></a>
				</td>

				<td class="clave_concepto"><%- clave_concepto %></td>
				<td style="padding-left: <%- numero_nivel %>em" class="<%= concepto_medible > 0 ? ' importante' : '' %>">
					<a href="" title="<%- descripcion %>" class="descripcion <%= concepto_medible === 3 ? 'concepto-medible' : '' %>">
						<%- descripcion %>
					</a>
				</td>

				<td class="<%= concepto_medible > 0 ? ' importante' : '' %>"><%- unidad %></td>
				<td class="numerico<%= concepto_medible > 0 ? ' importante' : '' %>"><%- cantidad_presupuestada %></td>
				<td class="numerico<%= concepto_medible > 0 ? ' importante' : '' %>"><%- precio_unitario %></td>
				<td class="numerico<%= concepto_medible > 0 ? ' importante' : '' %>"><%- monto_presupuestado %></td>
				<td class="partida<%= concepto_medible > 0 ? ' importante' : '' %><%= !showPartida ? ' hidden' : '' %>" title="<%- agrupador_partida %>"><%- agrupador_partida %></td>
				<td class="subpartida<%= concepto_medible > 0 ? ' importante' : '' %><%= !showSubpartida ? ' hidden' : '' %>" title="<%- agrupador_subpartida %>"><%- agrupador_subpartida %></td>
				<td class="actividad<%= concepto_medible > 0 ? ' importante' : '' %><%= !showActividad ? ' hidden' : '' %>" title="<%- agrupador_actividad %>"><%- agrupador_actividad %></td>
			</tr>
		</script>

		<script src="inc/js/lib/underscore-min.js"></script>
		<script src="inc/js/jquery-1.7.1.min.js"></script>
		<script src="inc/js/jquery-ui/js/jquery-ui-1.8.18.custom.min.js"></script>
		<script src="inc/js/jquery-ui/development-bundle/ui/i18n/jquery.ui.datepicker-es.js"></script>
		<script src="inc/js/general.js"></script>
		<script src="inc/js/jquery.buttonlist.js"></script>
		<script src="inc/js/jquery.uxtable.js"></script>
		<script src="inc/js/jquery.notify.js"></script>
		<script src="inc/js/presupuesto_obra.js"></script>
	</body>
</html>