<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!doctype html>
<html lang="es-mx">
<head>
	<meta charset="utf-8" />
	<title>Avance de Obra</title>

	<link rel="stylesheet" href="css/normalize.css" />
	<link rel="stylesheet" href="css/general.css" />
	<link rel="stylesheet" href="css/cobranza.css" />
	<link rel="stylesheet" href="css/jquery.notify.css" />
	<link rel="stylesheet" href="inc/js/jquery-ui/css/south-street/jquery-ui-1.8.18.custom.css" />

	<!--[if lt IE 9]><script src="inc/js/html5shiv.js"></script><![endif]-->
	<script src="inc/js/jquery-1.7.1.min.js"></script>
	<script src="inc/js/jquery-ui/js/jquery-ui-1.8.18.custom.min.js"></script>
	<script src="inc/js/jquery-ui/development-bundle/ui/i18n/jquery.ui.datepicker-es.js"></script>
	
	<script src="inc/js/general.js"></script>
	<script src="inc/js/jquery.buttonlist.js"></script>
	<script src="inc/js/jquery.listaTransacciones.js"></script>
	<script src="inc/js/jquery.uxtable.js"></script>
	<script src="inc/js/jquery.presupuestoObra.js"></script>
	<script src="inc/js/jquery.notify.js"></script>	
	<script src="inc/js/avance_obra.js"></script>
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
					<a class="toolbar-button dd-list" id="bl-proyectos">
						<span class="button-text">Proyectos</span>
						<span class="icon flechita-abajo"></span>
					</a>
					<a id="nueva-transaccion" class="toolbar-button">
						<span class="icon new"></span>
						<span class="label">Nuevo</span>
					</a>
					<a id="guardar" class="toolbar-button">
						<span class="icon save"></span>
						<span class="label">Guardar</span>
					</a>
					<a id="eliminar" class="toolbar-button">
						<span class="icon delete"></span>
						<span class="label">Eliminar</span>
					</a>
					<h2>Producción de Obra</h2>
				</div>
				<div id="app-module-content">
					<section id="tran">
						<section id="tran-header">
							<section class="module-toolbar" id="tran-toolbar">
								<a class="toolbar-button op dd-list" id="folios-transaccion">
									<span class="button-text">Folio</span>
									<span class="icon flechita-abajo"></span>
								</a>
								<a id="btnLista-transacciones" class="toolbar-button">...</a>
								<form>
									<label>Fecha</label>
									<input type="text" class="date" name="txtFechaTransaccion" id="txtFechaTransaccion" />
									<input type="hidden" name="txtFechaTransaccionDB" id="txtFechaTransaccionDB" />
									<input type="hidden" name="IDTransaccion" id="IDTransaccion" value="" />
								</form>
								<a id="aprobar" class="toolbar-button op">
									<span class="icon"></span>
									<span class="button-text">Aprobar</span>
								</a>
								<a id="revierte-aprobacion" class="toolbar-button">
									<span class="icon"></span>
									<span class="button-text">Revertir Aprobación</span>
								</a>
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
										<div>
											<a class="toolbar-button dd-list" id="empresa">
												<span class="button-text">Empresa</span>
												<span class="icon flechita-abajo"></span>
											</a>
											<!-- <select class="roField">
												<option>123</option>
												<option>345</option>
												<option>567</option>
											</select> -->
											<!-- <input type="text" id="txtEmpresa" class="roField" /> -->
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
									<col class="monto editable"/>
									<col span="5" class="monto"/>
								</colgroup>
								<thead>
									<tr>
										<th rowspan="2"></th>
										<th rowspan="2">Concepto</th>
										<th rowspan="2">Unidad</th>
										<th colspan="3">Cantidad</th>
										<th rowspan="2">Precio Venta</th>
										<th>Monto</th>
										<th>Cantidad</th>
										<th>Monto</th>
										<th rowspan="2">Cumplido</th>
									</tr>
									<tr>
										<th>Presupuesto</th>
										<th>Anterior</th>
										<th>Avance</th>
										<th>Avance</th>
										<th>Actual</th>
										<th>Actual</th>
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