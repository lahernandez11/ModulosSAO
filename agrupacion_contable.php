<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
	<html lang="es">
	<head>
		<meta charset="utf-8">
		<title>Agrupación Contable</title>
		<link rel="stylesheet" href="css/normalize.css" />
		<link rel="stylesheet" href="inc/js/jquery-ui/css/grupo-hi/jquery-ui.min.css" />
		<link rel="stylesheet" href="css/general.css" />
		<link rel="stylesheet" href="css/jquery.notify.css" />
		
		<!--[if lt IE 9]><script src="inc/js/html5shiv.js"></script><![endif]-->
	</head>
	<body>
		<div id="app-wrapper">
			<?php include("inc/app-header.php"); ?>
			<nav>
				<?php include("inc/app-menu.php"); ?>
			</nav>
			
			<div id="app-content">
				<div id="app-module">
					<div class="module-toolbar">
						<a class="button dd-list" id="bl-proyectos">
							<span class="button-text">Proyectos</span>
							<span class="icon flechita-abajo"></span>
						</a>
						<span class="col-switch">
							<input type="checkbox" name="col-switch" id="empresa">
							<label for="empresa">Empresa 1</label>
							<input type="checkbox" name="col-switch" id="empresa2">
							<label for="empresa2">Empresa 2</label>
							<input type="checkbox" name="col-switch" id="naturaleza">
							<label for="naturaleza">Naturaleza</label>
						</span>
						<h2>Agrupación Contable</h2>
					</div>
					<div id="app-module-content">
						<section id="tran">

							<section id="tran-content">
								<table id="tabla-cuentas" class="stripped tabla-arbol">
									<colgroup>
										<col class="icon-header"/>
										<col class="icon"/>
										<col class="icon"/>
										<col class="clave"/>
									</colgroup>
									<thead>
										<tr>
											<th>Afectable</th>
											<th></th>
											<th></th>
											<th>Clave</th>
											<th>Descripción</th>
											<th class="empresa">Agrupador Empresa 1</th>
											<th class="empresa2">Agrupador Empresa 2</th>
											<th class="naturaleza">Agrupador Naturaleza</th>
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


		<div id="dialog-nuevo-agrupador" class="dialog" title="Nuevo Agrupador">
			<form method="get" id="nuevo-agrupador" class="dialog-form">
				
				<label for="txtDescripcionAgruapdor">Descripcion</label>
				<input type="text" id="txtDescripcionAgruapdor" class="field" />

				<section class="buttons">
					<input type="submit" id="guardar_agrupador" name="guardar" class="button" value="Guardar" />
					<input type="button" id="cerrar_agrupador" name="cerrar" class="button" value="Cerrar" />
				</section>
			</form>
		</div>

		<div id="message-console">
			<span id="console-message"></span>
			<span id="console-toggler" class="open"></span>
		</div>
		<div id="cache"></div>

		<script type="template" id="template-cuenta">
			<tr id="c-<%- id_cuenta %>" data-nivel="<%- codigo %>" data-idsup="<%- id_cuenta_superior %>" data-afectable="<%- afectable %>" class="cuenta">
				<td class="icon-cell">
					<a class="<%= afectable ? 'icon-checkmark-circle' : 'icon-cancel-circle' %>"></a>
				</td>
				<td class="icon-cell">
					<a href="" class="handle icon-plus"></a>
				</td>
				<td class="icon-cell">
					<a href="" class="select icon-checkbox-unchecked"></a>
				</td>
				<td class="clave <%= afectable ? 'importante' : '' %>">
					<a href="" title="<%- codigo %>" class="descripcion <%= afectable ? 'importante' : '' %>">
						<%- codigo %>
					</a>
				</td>
				<td>
					<a href="" title="<%- nombre %>" style="margin-left: <%- nivel %>em" class="descripcion <%= afectable ? 'importante' : '' %>"><%- nombre %></a>
				</td>
				<td class="empresa<%= afectable ? ' importante' : '' %>
				<%= !showEmpresa ? ' hidden' : '' %>" title="<%- empresa %>">
					<%- empresa %>
				</td>
				<td class="empresa2<%= afectable ? ' importante' : '' %>
				<%= !showEmpresa2 ? ' hidden' : '' %>" title="<%- empresa2 %>">
					<%- empresa2 %>
				</td>
				<td class="naturaleza<%= afectable ? ' importante' : '' %>
				<%= !showNaturaleza ? ' hidden' : '' %>" title="<%- agrupador_naturaleza %>">
					<%- agrupador_naturaleza %>
				</td>
			</tr>
		</script>

		<script type="template" id="template-cuenta-properties">
			<div id="dialog-propiedades-cuenta" class="dialog" title="Propiedades de: <%- nombre %>">
				<form method="get" class="dialog-form form-cuenta-properties">
					
					<label for="txtAgrupadorEmpresa">Agrupador Empresa 1</label>
					<input type="text" id="txtAgrupadorEmpresa" class="field" value="<%- empresa %>" />

					<label for="txtAgrupadorEmpresa2">Agrupador Empresa 2</label>
					<input type="text" id="txtAgrupadorEmpresa2" class="field" value="<%- empresa2 %>" />

					<label for="txtAgrupadorNaturaleza">Agrupador Naturaleza</label>
					<input type="text" id="txtAgrupadorNaturaleza" class="field" value="<%- agrupador_naturaleza %>" />

					<!--<section class="buttons">
						<input type="button" id="cerrar-cuenta" name="cerrar" class="button" value="Cerrar" />
					</section> -->
				</form>
			</div>
		</script>

		<script src="inc/js/jquery-1.7.1.min.js"></script>
		<script src="inc/js/jquery-ui/js/jquery-ui.min.js"></script>
		<script src="inc/js/lib/underscore-min.js"></script>

		<script src="inc/js/general.js"></script>
		<script src="inc/js/jquery.buttonlist.js"></script>
		<script src="inc/js/jquery.uxtable.js"></script>
		<script src="inc/js/jquery.notify.js"></script>
		<script src="inc/js/agrupacion_contable.js"></script>
	</body>
</html>