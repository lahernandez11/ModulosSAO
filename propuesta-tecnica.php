<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!doctype html>
<html lang="es-mx">
<head>
	<meta charset="utf-8" />
	<title>Gestión de Trabajos Extraordinarios</title>

	<link rel="stylesheet" href="css/normalize.css" />
	<link rel="stylesheet" href="css/general.css" />
	<link rel="stylesheet" href="css/cobranza.css" />
	<link rel="stylesheet" href="css/jquery.notify.css" />
	<link rel="stylesheet" href="inc/js/jquery-ui/css/grupo-hi/jquery-ui.min.css" />

	<!--[if lt IE 9]>
		<script src="inc/js/html5shiv.js"></script>
	<![endif]-->
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
					<h2>Propuesta Técnica de Trabajos Extraordinarios</h2>
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
											<label>Conceptos Dependientes de</label>
											<input type="text" id="txtConceptoRaiz" class="roField" />
										</div>
										<div>
											<label for="txtObservaciones">Observaciones</label>
											<textarea id="txtObservaciones" class="roField"></textarea>
										</div>
									</fieldset>
									<fieldset>
										<legend>Periodo</legend>

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
									<col span="3" class="monto"/>
									<col class="monto editable"/>
								</colgroup>
								<thead>
									<tr>
										<th rowspan="2"></th>
										<th rowspan="2">Clave</th>
										<th rowspan="2">Concepto</th>
										<th rowspan="2">Unidad</th>
										<th colspan="3">Presupuestado</th>
										<th rowspan="2">Cantidad</th>
									</tr>
									<tr>
										<th>Cantidad</th>
										<th>Precio</th>
										<th>Monto</th>
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

	<script type="text/template" id="template-concepto">
		<tr data-id="<%- id_concepto %>" data-esactividad="<%- es_actividad %>">
		<td class="icon-cell">
			<a class="icon fixed"></a>
		</td>
        <td title="<%- clave_concepto %>"><%- clave_concepto %></td>
		<<%= es_actividad ? 'td' : 'th' %> title="<%- descripcion %>">
		<%= '&nbsp;&nbsp;'.repeat(numero_nivel) + descripcion %>
		</<%= es_actividad ? 'td' : 'th' %>>
		<td class="centrado"><%- unidad %></td>
		<td class="numerico"><%- cantidad_presupuestada %></td>
		<td class="numerico"><%- precio_unitario %></td>
		<td class="numerico"><%- monto_presupuestado %></td>

		<td class="editable-cell numerico"><%- cantidad %></td>
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
	<script src="inc/js/jquery.presupuestoObra.js"></script>
	<script src="inc/js/jquery.notify.js"></script>	
	<script src="inc/js/propuesta-tecnica.js"></script>
</body>
</html>