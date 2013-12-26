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
				<div class="module-toolbar">
						<a class="button dd-list" id="bl-proyectos">
							<span class="button-text">Proyectos</span>
							<span class="icon flechita-abajo"></span>
						</a>
						<span class="actions">
							<a id="consulta-insumos" class="button">
							<span class="label">Insumos</span>
							</a>
							<a id="consulta-subcontrato" class="button">
								<span class="label">Subcontratos</span>
							</a>
							<a id="consulta-varios" class="button">
								<span class="label">Gastos Varios</span>
							</a>
						</span>
						<h2>Agrupación</h2>
				</div>
				<div id="app-module-content">
						
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
	<div id="message-console">
		<span id="console-message"></span>
		<span id="console-toggler" class="open"></span>
	</div>

	<script type="template" id="template-insumo">
		<div class="section">
			<div class="section-header">
				<span class="content-toggler">
					<a class="title">
						<%- Familia %>
						<span class="items-counter" title="Numero de insumos afectados por el filtro">
							(<span class="item-count"><%- NumInsumos %></span>)
						</span>
					</a>
				</span>
			</div>
			<div class="section-content">
				<table class="insumos">
					<colgroup>
						<col/>
						<col class="unidad"/>
						<col/>
						<col class="icon"/>
						<col/>
						<col class="icon"/>
						<col/>
						<col class="icon"/>
					</colgroup>
					<thead>
						<tr>
							<th>Insumo</th>
							<th>Unidad</th>
							<th colspan="2">Naturaleza</th>
							<th colspan="2">Familia</th>
							<th colspan="2">Insumo Genérico</th>
						</tr>
					</thead>
					<tbody>
					<% _.each(Insumos, function(insumo) { %>
						<tr class="insumo" data-id="<%- insumo.idInsumo %>">
							<td><%- insumo.Insumo %></td>
							<td class="centrado"><%- insumo.Unidad %></td>
							<td><%- insumo.AgrupadorNaturaleza %></td>
							<td class="icon-cell">
								<a href="#dropdown-naturaleza" class="dropdown-list-trigger"></a>
							</td>
							<td><%- insumo.AgrupadorFamilia %></td>
							<td class="icon-cell">
								<a href="#dropdown-familia" class="dropdown-list-trigger"></a>
							</td>
							<td><%- insumo.AgrupadorInsumoGenerico %></td>
							<td class="icon-cell">
								<a href="#dropdown-insumo-generico" class="dropdown-list-trigger"></a>
							</td>
						</tr>
					<% }); %>
					</tbody>
				</table>
			</div>
		</div>
	</script>

	<script type="template" id="template-subcontrato">
		<div class="section">
			<div class="section-header">
				<span class="content-toggler">
					<a class="title">
						<%- Contratista %>
						<span class="items-counter" title="Numero de actividades afectadas por el filtro">
							(<span class="item-count"><%- NumActividades %></span>)
						</span>
					</a>
				</span>
			</div>
			<div class="section-content">
				<% _.each(Subcontratos, function(subcontrato) { %>
				<div class="section">
					<div class="section-header">
						<span class="content-toggler">
							<a class="title">
								<%- subcontrato.Subcontrato %>
								<span class="items-counter" title="Numero de actividades afectadas por el filtro">
									(<span class="item-count"><%- subcontrato.NumActividades %></span>)
								</span>
							</a>
						</span>
					</div>
					<div class="section-content">
						<table class="subcontratos">
							<colgroup>
								<col/>
								<col class="unidad"/>
								<col/>
								<col class="icon"/>
								<col/>
								<col class="icon"/>
								<col/>
								<col class="icon"/>
							</colgroup>
							<thead>
								<tr>
									<th>Actividad</th>
									<th>Unidad</th>
									<th colspan="2">Naturaleza</th>
									<th colspan="2">Familia</th>
									<th colspan="2">Insumo Genérico</th>
								</tr>
							</thead>
							<tbody>
							<% _.each(subcontrato.Actividades, function(actividad) { %>
								<tr class="actividad"
									data-id="<%- actividad.idActividad %>"
									data-idcontratista="<%- idContratista %>"
									data-idsubcontrato="<%- subcontrato.idSubcontrato %>">
									<td><%- actividad.Actividad %></td>
									<td class="centrado"><%- actividad.Unidad %></td>
									<td><%- actividad.AgrupadorNaturaleza %></td>
									<td class="icon-cell">
										<a href="#dropdown-naturaleza" class="dropdown-list-trigger"></a>
									</td>
									<td><%- actividad.AgrupadorFamilia %></td>
									<td class="icon-cell">
										<a href="#dropdown-familia" class="dropdown-list-trigger"></a>
									</td>
									<td><%- actividad.AgrupadorInsumoGenerico %></td>
									<td class="icon-cell">
										<a href="#dropdown-insumo-generico" class="dropdown-list-trigger"></a>
									</td>
								</tr>
							<% }); %>
							</tbody>
						</table>
					</div>
				</div>
				<% }); %>
			</div>
		</div>
	</script>

	<script type="template" id="template-contable">
		<table class="insumos">
			<colgroup>
				<col class="cuenta"/>
				<col/>
				<col class="agrupador"/>
				<col class="icon"/>
			</colgroup>
			<thead>
				<tr>
					<th>Codigo</th>
					<th>Nombre</th>
					<th colspan="2">Naturaleza</th>
				</tr>
			</thead>
			<tbody>
			<% _.each( Cuentas, function(cuenta) { %>
				<tr class="cuenta" data-id="<%- cuenta.idCuenta %>">
					<td class="centrado"><%- cuenta.Codigo %></td>
					<td><%- cuenta.Nombre %></td>
					<td><%- cuenta.AgrupadorNaturaleza %></td>
					<td class="icon-cell">
						<a href="#dropdown-naturaleza" class="dropdown-list-trigger"></a>
					</td>
				</tr>
			<% }); %>

			</tbody>
		</table>
	</script>

	<script type="template" id="template-gastos">
		<div class="section">
			<div class="section-header">
				<span class="content-toggler">
					<a class="title">
						<%- proveedor %>
						<span class="items-counter" title="Numero de facturas">
							(<span class="item-count">0</span>)
						</span>
					</a>
				</span>
			</div>
			<div class="section-content">

			<% _.each(facturas, function(factura) { %>
			<div class="section">
				<div class="section-header">
					<span class="content-toggler">
						<a class="title">
							<%- factura.referencia_factura %>
							<span class="items-counter" title="Numero de subcontratos ectados por el filtro">
								(<span class="item-count">0</span>)
							</span>
						</a>
					</span>
				</div>
				<div class="section-content">
					<table class="subcontratos">
						<colgroup>
							<col/>
							<col/>
							<col class="icon"/>
							<col/>
							<col class="icon"/>
							<col/>
							<col class="icon"/>
						</colgroup>
					<thead>
						<tr>
							<th>Referencia</th>
							<th colspan="2">Naturaleza</th>
							<th colspan="2">Familia</th>
							<th colspan="2">Insumo Genérico</th>
						</tr>
					</thead>
					<tbody>

						<% _.each( factura.items, function(item) { %>
						<tr class="item-facturavarios" data-id="<%- item.id_item %>" data-idtransaccion="<%- factura.id_factura %>">
							<td><%- item.referencia %></td>
							<td><%- item.agrupador_naturaleza %></td>
							<td class="icon-cell">
								<a href="#dropdown-naturaleza" class="dropdown-list-trigger"></a>
							</td>
							<td><%- item.agrupador_familia %></td>
							<td class="icon-cell">
								<a href="#dropdown-familia" class="dropdown-list-trigger"></a>
							</td>
							<td><%- item.agrupador_insumo_generico %></td>
							<td class="icon-cell">
								<a href="#dropdown-insumo-generico" class="dropdown-list-trigger"></a>
							</td>
						</tr>
						<% }); %>
					</tbody>
				</table>
			</div>
			<% }); %> 
		</div>
	</script>

	<script src="inc/js/jquery-1.7.1.min.js"></script>
	<script src="inc/js/jquery-ui/js/jquery-ui-1.8.18.custom.min.js"></script>
	<script src="inc/js/jquery.buttonlist.js"></script>
	<script src="inc/js/lib/underscore-min.js"></script>

	<script src="inc/js/general.js"></script>
	<script src="inc/js/agrupacion.js"></script>
</body>
</html>