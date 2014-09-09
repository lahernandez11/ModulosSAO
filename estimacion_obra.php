<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
<html lang="es-mx">
<head>
	<meta charset="utf-8" />
	<title>Estimación de Obra</title>

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
					<a class="button dd-list" id="bl-proyectos">
						<span class="button-text">Proyectos</span>
						<span class="icon flechita-abajo"></span>
					</a>
					<a id="nuevo" class="button">
						<span class="label">Nuevo</span>
					</a>
					<a id="guardar" class="button">
						<span class="label">Guardar</span>
					</a>
					<a id="eliminar" class="button">
						<span class="label">Eliminar</span>
					</a>
					<h2>Estimación de Obra</h2>
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
									<input type="text" class="date" name="txtFecha" id="txtFecha" />
									<input type="hidden" name="txtFechaDB" id="txtFechaDB" />
								</form>
								<!-- <a id="aprobar" class="button op aprobar">
									<span class="icon"></span>
									<span class="button-text">Aprobar</span>
								</a>
								<a id="revierte-aprobacion" class="button revertir">
									<span class="icon"></span>
									<span class="button-text">Revertir Aprobación</span>
								</a> -->
							</section>

							<section id="tran-info">
								<form>
									<fieldset>
										<div class="multi-field">
											<label>Referencia</label>
											<input type="text" id="txtReferencia" class="roField" maxlength="64" required />
										</div>
										<div>
											<label for="txtObservaciones">Observaciones</label>
											<textarea id="txtObservaciones" class="roField"></textarea>
										</div>
									</fieldset>
									<fieldset>
										<legend>Periodo de Estimación</legend>

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
									<col class="icon" />
									<col/>
									<col class="unidad" />
									<col span="2" class="monto" />
									<col class="monto editable" />
									<col class="monto editable" />
									<col class="monto" />
									<col class="cumplido" />
								</colgroup>
								<thead>
									<tr>
										<th rowspan="2"></th>
										<th rowspan="2">Concepto</th>
										<th rowspan="2">Unidad</th>
										<th colspan="3">Cantidad</th>
										<th rowspan="2">Precio</th>
										<th>Monto</th>
										<th rowspan="2">Cumplido</th>
									</tr>
									<tr>
										<th>Presupuesto</th>
										<th>Anterior</th>
										<th>Avance</th>
										<th>Total</th>
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
			<<%= EsActividad ? 'td': 'th' %> title="<%- Descripcion %>">
				<%= '&nbsp;&nbsp;'.repeat(NumeroNivel) + Descripcion %>
			</<%= EsActividad ? 'td': 'th' %>>
			<td class="centrado"><%- Unidad %></td>
			<td class="numerico"><%= EsActividad ? CantidadPresupuestada : '' %></td>
			<td class="numerico"><%= EsActividad ? CantidadEstimadaAnterior : '' %></td>
			<td class="editable-cell numerico cantidad"><%= EsActividad ? CantidadEstimada : '' %></td>
			<td class="editable-cell numerico precio"><%= EsActividad ? PrecioVenta : '' %></td>
			<td class="numerico total"><%= EsActividad ? Total : '' %></td>
			<td class="icon-cell cumplido">
				<%= EsActividad ? '<a class="icon action checkbox checkbox-' + (Cumplido ? 'checked' : 'unchecked') + '"></a> Si' : '' %>
			</td>
		</tr>
	</script>

	<script src="inc/js/lib/underscore-min.js"></script>
	<script src="inc/js/jquery-1.7.1.min.js"></script>
	<script src="inc/js/jquery-ui/js/jquery-ui.min.js"></script>
	<script src="inc/js/jquery-ui/js/i18n/jquery.ui.datepicker-es.min.js"></script>
	<script src="inc/js/general.js"></script>
	<script src="inc/js/jquery.buttonlist.js"></script>
	<script src="inc/js/jquery.listaTransacciones.js"></script>
	<script src="inc/js/jquery.uxtable.js"></script>
	<script src="inc/js/jquery.notify.js"></script>
	<script src="inc/js/estimacion_obra.js"></script>
</body>
</html>