<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
	<html lang="es">
	<head>
		<meta charset="utf-8">
		<title>Agrupación Contable</title>
		<link rel="stylesheet" href="css/normalize.css" />
		<link rel="stylesheet" href="inc/js/jquery-ui/css/south-street/jquery-ui-1.8.18.custom.css" />
		<link rel="stylesheet" href="css/general.css" />
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
						<span class="col-switch">
							<input type="checkbox" name="col-switch" id="proveedor" value="5">
							<label for="proveedor">Proveedor</label>
							<input type="checkbox" name="col-switch" id="empresa" value="6">
							<label for="empresa">Empresa SAO</label>
						</span>
						<h2>Agrupación Contable</h2>
					</div>
					<div id="app-module-content">
						<section id="tran">

							<section id="tran-content">
								<table id="tabla-cuentas" class="stripped tabla-arbol">
									<colgroup>
										<col class="clave"/>
										<col class="icon-header"/>
										<col class="icon"/>
										<col class="icon"/>
									</colgroup>
									<thead>
										<tr>
											<th>Clave</th>
											<th>Afectable</th>
											<th></th>
											<th></th>
											<th>Descripción</th>
											<th class="proveedor">Agrupador Proveedor</th>
											<th class="empresa">Agrupador Empresa SAO</th>
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

		<div id="dialog-propiedades-cuenta" class="dialog" title="Propiedades">
			<form method="get" class="dialog-form form-cuenta-properties">

				<label for="txtAgrupadorProveedor">Agrupador Proveedor</label>
				<!-- <a title="Eliminar agrupador" class="elimina-agrupador icon-close"></a> -->
				<input type="text" id="txtAgrupadorProveedor" class="field" />
				
				<label for="txtAgrupadorEmpresa">Agrupador Empresa SAO</label>
				<input type="text" id="txtAgrupadorEmpresa" class="field" />

				<label for="txtAgrupadorTipoCuenta">Agrupador Tipo Cuenta</label>
				<input type="text" id="txtAgrupadorTipoCuenta" class="field" />

				<label for="txtAgrupadorNaturaleza">Agrupador Naturaleza</label>
				<input type="text" id="txtAgrupadorNaturaleza" class="field" />

				<section class="buttons">
					<input type="button" id="cerrar-cuenta" name="cerrar" class="button" value="Cerrar" />
				</section>
			</form>
		</div>

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
			<tr id="c-<%- IdCuenta %>" data-nivel="<%- Codigo %>" data-idsup="<%- IdCtaSup %>" data-afectable="<%- Afectable%>" class="cuenta">
				<td class="clave <%= Afectable ? 'importante' : '' %>"><%- Codigo %></td>
				<td class="icon-cell">
					<a class="<%= Afectable ? 'icon-checkmark-circle' : 'icon-cancel-circle' %>"></a>
				</td>
				<td class="icon-cell">
					<a href="" class="handle icon-plus"></a>
				</td>
				<td class="icon-cell">
					<a href="" class="select icon-checkbox-unchecked"></a>
				</td>
				<td>
					<a href="" title="<%- Proveedor %>" style="margin-left: <%- Nivel %>em" class="descripcion <%= Afectable ? 'importante' : '' %>"><%- Nombre %></a>
				</td>
				<td class="proveedor<%= Afectable ? ' importante' : '' %><%= !showProveedor ? ' hidden' : '' %>" title="<%- Proveedor %>">
					<%- Proveedor %>
				</td>
				<td class="empresa<%= Afectable ? ' importante' : '' %><%= !showEmpresa ? ' hidden' : '' %>" title="<%- Empresa %>">
					<%- Empresa %>
				</td>
			</tr>
		</script>

		<script src="inc/js/jquery-1.7.1.min.js"></script>
		<script src="inc/js/jquery-ui/js/jquery-ui-1.8.18.custom.min.js"></script>
		<script src="inc/js/jquery-ui/development-bundle/ui/i18n/jquery.ui.datepicker-es.js"></script>
		<script src="inc/js/lib/underscore-min.js"></script>

		<script src="inc/js/general.js"></script>
		<script src="inc/js/jquery.buttonlist.js"></script>
		<script src="inc/js/jquery.uxtable.js"></script>
		<script src="inc/js/jquery.notify.js"></script>
		<script src="inc/js/agrupacion_contable.js"></script>
	</body>
</html>