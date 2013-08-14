<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8" />
	<title>Estimación de Obra</title>
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
	<script src="inc/js/estimacion_obra.js"></script>
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
					<h4>-> ESTIMACIÓN DE OBRA</h4>
				</header>
				<div class="module-toolbar">
					<a class="toolbar-button dd-list" id="bl-proyectos">
						<span class="button-text">Proyectos</span>
						<span class="icon"></span>
					</a>
					<a id="nueva-transaccion" class="toolbar-button new"><span class="icon"></span>Nuevo</a>
					<a id="guardar" class="toolbar-button save"><span class="icon"></span>Guardar</a>
					<a id="eliminar" class="toolbar-button delete"><span class="icon"></span>Eliminar</a>
				</div>
				<div id="app-module-content">
					<section id="tran">
						<section id="tran-header">
							<section class="module-toolbar" id="tran-toolbar">
								<a class="toolbar-button op dd-list" id="folios-transaccion">
									<span class="button-text">Folio</span>
									<span class="icon"></span>
								</a>
								<a id="btnLista-transacciones" class="toolbar-button">...</a>
								<form>
									<label>Fecha</label>
									<input type="text" class="date" name="txtFechaTransaccion" id="txtFechaTransaccion" />
									<input type="hidden" name="txtFechaTransaccionDB" id="txtFechaTransaccionDB" />
									<input type="hidden" name="IDTransaccion" id="IDTransaccion" value="" />
								</form>
								<a id="aprobar" class="toolbar-button op aprobar">
									<span class="icon"></span>
									<span class="button-text">Aprobar</span>
								</a>
								<a id="revierte-aprobacion" class="toolbar-button revertir">
									<span class="icon"></span>
									<span class="button-text">Revertir Aprobación</span>
								</a>
							</section>

							<section id="tran-info">
								<form>
									<fieldset>
										<div class="multi-field">
											<label>Referencia</label>
											<input type="text" id="txtReferencia" class="roField" />
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
									<col class="icon"/>
									<col/>
									<col class="unidad"/>
									<col span="2" class="monto"/>
									<col span="4" class="monto editable"/>
									<col class="monto"/>
								</colgroup>
								<thead>
									<tr>
										<th rowspan="2"></th>
										<th rowspan="2">Concepto</th>
										<th rowspan="2">Unidad</th>
										<th colspan="4">Cantidad</th>
										<th rowspan="2">Precio</th>
										<th>Monto</th>
										<th rowspan="2">Cumplido</th>
									</tr>
									<tr>
										<th>Presupuesto</th>
										<th>Anterior</th>
										<th>Actual</th>
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

	<div id="message-console"><span id="console-message"></span><span id="console-toggler" class="open"></span></div>
	<div id="cache"></div>
</body>
</html>