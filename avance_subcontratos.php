<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
<html lang="es-mx">
<head>
	<meta charset="utf-8" />
	<title>Avance de Subcontratos</title>
	
	<link rel="stylesheet" href="css/normalize.css" />
	<link rel="stylesheet" href="css/general.css" />
	<link rel="stylesheet" href="css/cobranza.css" />
	<link rel="stylesheet" href="css/jquery.notify.css" />
	<link rel="stylesheet" href="inc/js/jquery-ui/css/grupo-hi/jquery-ui.min.css" />
	
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
					<h2>Avance de Subcontratos</h2>
					<a class="button dd-list" id="bl-proyectos">
						<span class="button-text">Proyectos</span>
						<span class="icon flechita-abajo"></span>
					</a>
					<a id="nueva-transaccion" class="button">
						<span class="label">Nuevo</span>
					</a>
					<a id="guardar" class="button">
						<span class="label">Guardar</span>
					</a>
					<a id="eliminar" class="button">
						<span class="label">Eliminar</span>
					</a>
				</div>
				<div id="app-module-content">
					<section id="tran">
						<section id="tran-header">
							<section class="module-toolbar" id="tran-toolbar">
								<a class="button dd-list" id="folios-transaccion">
									<span class="button-text">Folio</span>
									<span class="icon flechita-abajo"></span>
								</a>
								<a id="btnLista-transacciones" class="button">...</a>
								<form>
									<label>Fecha</label>
									<input type="text" class="date" name="txtFechaTransaccion" id="txtFechaTransaccion" />
									<input type="hidden" name="txtFechaTransaccionDB" id="txtFechaTransaccionDB" />
									<input type="hidden" name="IDTransaccion" id="IDTransaccion" value="" />
								</form>
							</section>

							<section id="tran-info">
								<form>
									<fieldset>
										<div class="multi-field">
											<label for="txtEmpresa">Empresa</label>
											<input type="text" id="txtEmpresa" class="roField" disabled />
										</div>
										<div>
											<label for="txtSubcontrato">Subcontrato</label>
											<input type="text" id="txtSubcontrato" class="roField" disabled />
										</div>
										<div>
											<label for="txtObservaciones">Observaciones</label>
											<textarea id="txtObservaciones" class="roField"></textarea>
										</div>
									</fieldset>
									<fieldset>
										<legend>Periodo de Avance</legend>

										<div class="multi-field">
											<span>
												<span class="label">Inicio</span>
												<input type="text" class="date" name="txtFechaInicio" id="txtFechaInicio" />
												<input type="hidden" name="txtFechaInicioDB" id="txtFechaInicioDB" />
											</span>
											<span>
												<span class="label">Término</span>
												<input type="text" class="date" name="txtFechaTermino" id="txtFechaTermino" />
												<input type="hidden" name="txtFechaTerminoDB" id="txtFechaTerminoDB" />
											</span>
										</div>
									</fieldset>
									<fieldset>
										<legend>Fechas de Reconocimiento de Avance</legend>
										<div class="multi-field">
											<span>
												<span class="label">Ejecución</span>
												<input type="text" class="date" name="txtFechaEjecucion" id="txtFechaEjecucion" />
												<input type="hidden" name="txtFechaEjecucionDB" id="txtFechaEjecucionDB" />
											</span>
											<span>
												<span class="label">Contable</span>
												<input type="text" class="date" name="txtFechaContable" id="txtFechaContable" />
												<input type="hidden" name="txtFechaContableDB" id="txtFechaContableDB" />
											</span>
										</div>
									</fieldset>
									<fieldset>
										<legend>Totales</legend>
										<div class="multi-field">
											<span>
												<span class="label">Subtotal</span>
												<div id="txtSubtotal" name="txtSubtotal" class="roField amount">0</div>
											</span>
											<span>
												<span class="label">IVA</span>
												<div id="txtIVA" name="txtIVA" class="roField amount">0</div>
											</span>
											<span>
												<span class="label">Total</span>
												<div id="txtTotal" name="txtTotal" class="roField amount">0</div>
											</span>
										</div>
									</fieldset>

								</form>
								<div style="clear:both"></div>
							</section> <!-- tran-info -->
						</section> <!-- tran-header -->

						<section id="tran-content">
							<table id="tabla-conceptos">
								<colgroup>
									<col class="icon"/>
                                    <col class="clave"/>
                                    <col/>
									<col class="unidad"/>
									<col class="monto"/>
									<col class="monto"/>
									<col class="monto"/>
								</colgroup>
								<thead>
									<tr>
										<th></th>
										<th>Clave</th>
										<th>Concepto</th>
										<th>Unidad</th>
										<th>Cantidad Contratada</th>
										<th>Precio Unitario</th>
										<th>Cantidad</th>
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

	<div id="dialog-subcontratos" class="dialog" title="Subcontratos a Estimar">
		<div class="ui-state-highlight ui-corner-all">
			<p><span class="ui-icon ui-icon-info"></span><strong>De doble click para seleccionar un subcontrato</strong></p>
		</div>
		<table id="tabla-subcontratos" class="stripped">
			<colgroup>
				<col/>
				<col span="2" class="folio"/>
			</colgroup>
			<thead>
			<tr>
				<th>Contratista</th>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Referencia</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>

	<script type="text/template" id="template-concepto">
		<tr data-id="<%- id_concepto %>" data-iditem="<%- id_item %>" data-esactividad="<%- es_actividad %>">
			<td class="icon-cell">
				<a class="icon fixed"></a>
			</td>
            <td title="<%- clave %>"><%- clave %></td>
			<% if (es_actividad){ %>
				<td title="<%- descripcion %>"><%= '&nbsp;&nbsp;'.repeat(numero_nivel) + descripcion %></td>
				<td class="centrado"><%- unidad %></td>
				<td class="numerico contratado"><%- cantidad_presupuestada %></td>
				<td class="numerico"><%- precio_unitario %></td>
				<td class="numerico editable editable-cell"><%- cantidad %></td>
			<% } else { %>
				<th><%= '&nbsp;&nbsp;'.repeat(numero_nivel) + descripcion %></th>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			<% } %>
		</tr>
	</script>

	<div id="message-console"><span id="console-message"></span><span id="console-toggler" class="open"></span></div>
	<div id="cache"></div>

	<script src="inc/js/lib/underscore-min.js"></script>
	<script src="inc/js/jquery-1.7.1.min.js"></script>
	<script src="inc/js/jquery-ui/js/jquery-ui.min.js"></script>
	<script src="inc/js/jquery-ui/js/i18n/jquery.ui.datepicker-es.min.js"></script>
	
	<script src="inc/js/general.js"></script>
	<script src="inc/js/jquery.buttonlist.js"></script>
	<script src="inc/js/jquery.listaTransacciones.js"></script>
	<script src="inc/js/jquery.uxtable.js"></script>
	<script src="inc/js/jquery.notify.js"></script>
	<script src="inc/js/avance_subcontratos.js"></script>
</body>
</html>