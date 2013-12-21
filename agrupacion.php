<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
<html lang="es-mx">
<head>
	<meta charset="utf-8" />
	<title>Agrupación de Insumos</title>

	<link rel="stylesheet" href="css/normalize.css" />
	<link rel="stylesheet" href="css/general.css" />
	<link rel="stylesheet" href="css/agrupacion.css"/>
	<link rel="stylesheet" href="inc/js/jquery-ui/css/south-street/jquery-ui-1.8.18.custom.css" />
	
</head>

<body>
	<div id="app-wrapper">
		<?php include("inc/app-header.php"); ?>
		<nav>
			<ul id="app-nav"></ul>
		</nav>
		
		<div id="app-content">
			<div id="app-module">
				<div id="app-module-content">
					<div class="options">
						<div class="options-block">
							<h3 class="title">Proyectos</h3>
							<div class="content">
								<ul id="lista-proyectos" class="options-list"></ul>
							</div>
						</div>
						<div class="options-block" id="opciones">
							<h3 class="title">Opciones</h3>
							<div class="content">
								<form>
									<div>
										<input type="button" class="button consultar" id="cmdInsumos" name="cmdInsumos" value="Consultar Insumos" />
									</div>
									<div>
										<input type="button" class="button consultar" id="cmdSubcontratos" name="cmdSubcontratos" value="Consultar Subcontratos" />
									</div>
									<div>
										<input type="button" class="button consultar" id="cmdCuentas" name="cmdCuentas" value="Consultar Cuentas Contables" />
									</div>
									<div>
										<input type="button" class="button consultar" id="cmdFacturasVarios" name="cmdFacturasVarios" value="Fact. Gastos Varios" />
									</div>
								</form>
							</div>
						</div>
					</div>

					<div id="agrupacion">
						<ul class="menu hz-menu toolbar">
							<li id="radios-expansion">
								<input type="radio" id="rd-expand-all" name="rdExpansion"  />
								<label for="rd-expand-all">Expandir todo</label>
								<input type="radio" id="rd-collapse-all" name="rdExpansion" checked="true"  />
								<label for="rd-collapse-all">Contraer todo</label>
							</li>
							<li class="item-title">Mostrar:</li>
							<li id="radios-visibilidad">
								<input type="radio" id="rd-show-all" name="rdVisibilidad" disabled="disabled" />
								<label for="rd-show-all">Todos</label>
								
								<input type="radio" id="rd-show-sin-naturaleza" name="rdVisibilidad" disabled="disabled" />
								<label for="rd-show-sin-naturaleza">Sin Naturaleza</label>
								
								<input type="radio" id="rd-show-sin-familia" name="rdVisibilidad" disabled="disabled" />
								<label for="rd-show-sin-familia">Sin Familia</label>
								
								<input type="radio" id="rd-show-sin-insumo-generico" name="rdVisibilidad" disabled="disabled" />
								<label for="rd-show-sin-insumo-generico">Sin Insumo Genérico</label>
							</li>
						</ul>
						<div id="conceptos"></div>
					</div>

				</div> <!-- module-content -->
			</div> <!-- module -->
		</div> <!-- app-content -->

		<footer id="app-footer">
			<?php include("inc/app-footer.php"); ?>
		</footer> <!-- app-footer -->
	</div> <!-- app-wrapper -->
	
	<div id="cache"></div>
	<div id="confirmation-dialog" class="dialog" title="Modulos SAO">
		<p class="confirmation-message"></p>
	</div>
	<div id="message-console"><span id="console-message"></span><span id="console-toggler" class="open"></span></div>

	<script src="inc/js/jquery-1.7.1.min.js"></script>
	<script src="inc/js/jquery-ui/js/jquery-ui-1.8.18.custom.min.js"></script>
	<script src="inc/js/lib/underscore-min.min.js"></script>
	<script src="inc/js/general.js"></script>
	<script src="inc/js/agrupacion.js"></script>

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
				<a href="" title="<%- Nombre %>" style="margin-left: <%- Nivel %>em" class="descripcion <%= Afectable ? 'importante' : '' %>"><%- Nombre %></a>
			</td>
			<td class="proveedor<%= Afectable ? ' importante' : '' %><%= !showProveedor ? ' hidden' : '' %>" title="<%- Proveedor %>">
				<%- Proveedor %>
			</td>
			<td class="empresa<%= Afectable ? ' importante' : '' %><%= !showEmpresa ? ' hidden' : '' %>" title="<%- Empresa %>">
				<%- Empresa %>
			</td>
		</tr>
	</script>
</body>
</html>