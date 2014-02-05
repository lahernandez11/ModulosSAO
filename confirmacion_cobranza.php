<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
<html lang="es-mx">
<head>
	<meta charset="utf-8" />
	<title>Confirmación de Cobranza</title>

	<link rel="stylesheet" href="css/normalize.css" />
	<link rel="stylesheet" href="css/general.css" />
	<link rel="stylesheet" href="css/cobranza.css" />
	<link rel="stylesheet" href="css/jquery.notify.css" />
	<link rel="stylesheet" href="inc/js/jquery-ui/css/south-street/jquery-ui-1.8.18.custom.css" />

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
					<a id="nuevo" class="button">
						<span class="label">Nuevo</span>
					</a>
					<a id="guardar" class="button">
						<span class="label">Guardar</span>
					</a>
					<a id="eliminar" class="button">
						<span class="label">Eliminar</span>
					</a>
					<h2>Confirmación de Cobranza</h2>
				</div>
				<div id="app-module-content">
					<section id="tran">
						<section id="tran-header">
							<section class="module-toolbar" id="tran-toolbar">
								<a class="button dd-list" id="folios-transaccion">
									<span class="button-text">Folio</span>
									<span class="icon flechita-abajo"></span>
								</a>
								<form>
									<label>Fecha</label>
									<input type="text" class="date" name="txtFecha" id="txtFecha" />
									<input type="hidden" name="txtFechaDB" id="txtFechaDB" />
								</form>
								<a id="btnResumen" class="button op"><span class="icon"></span>Resumen</a>
							</section>

							<section id="tran-info">
								<form>
									<fieldset>
										<div class="multi-field">
											<label>Referencia</label>
											<input type="text" id="txtReferencia" class="roField"></input>
										</div>
										<div>
											<label for="txtObservaciones">Observaciones</label>
											<textarea id="txtObservaciones" class="roField"></textarea>
										</div>
									</fieldset>

									<fieldset>
										<legend>Totales</legend>
										<div class="multi-field">
											<span>
												<span class="label">Subtotal</span>
												<div id="txtSubtotal" name="txtSubtotal" class="roField amount"></div>
											</span>
											<span>
												<span class="label">IVA</span>
												<div id="txtIVA" name="txtIVA" class="roField amount"></div>
											</span>
											<span>
												<span class="label">Total</span>
												<div id="txtTotal" name="txtTotal" class="roField amount"></div>
											</span>
										</div>
									</fieldset>
									<fieldset>
										<legend>Folios</legend>
										<div class="multi-field">
											<span>
												<span class="label">Factura</span>
												<input type="text" id="txtFolioFactura" class="roField amount">
											</span>
										</div>
									</fieldset>

								</form>
								<div style="clear:both"></div>
							</section> <!-- tran-info -->
						</section> <!-- tran-header -->

						<div style="clear:both"></div>
						<section id="tran-content">
							<table id="tabla-conceptos">
								<colgroup>
									<col class="icon"/>
									<col/>
									<col class="unidad"/>
									<col class="monto"/>
									<col class="monto"/>
									<col class="monto"/>
									<col class="monto"/>
									<col class="monto"/>
									<col span="2" class="monto editable"/>
									<col class="monto"/>
								</colgroup>
								<thead>
									<tr>
										<th rowspan="2"></th>
										<th rowspan="2">Concepto</th>
										<th rowspan="2">UM</th>
										<th>PRESUPUESTO</th>
										<th>ANTERIOR</th>
										<th colspan="3">AVANCE</th>
										<th colspan="3">COBRADO</th>
									</tr>
									<tr>
										<th>Volumen</th>
										<th>Acum.</th>
										<th>Volumen</th>
										<th>P.U.</th>
										<th>Importe</th>
										<th>Volumen</th>
										<th>P.U.</th>
										<th>Importe</th>
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

	<div id="dialog-estimaciones-obra" class="dialog" title="Lista de Estimaciones de Obra">
		<div class="ui-state-highlight ui-corner-all">
			<p><span class="ui-icon ui-icon-info"></span><strong>De doble click para seleccionar una estimacion</strong></p>
		</div>
		<table id="tabla-estimaciones-obra">
			<colgroup>
				<col class="folio"></col>
				<col class="fecha"></col>
			</colgroup>
			<thead>
				<tr>
					<th>Folio</th>
					<th>Fecha</th>
					<th>Referencia</th>
				</tr>
			</thead>
			<tbody>

			</tbody>
		</table>
	</div>
	<div id="dialog-resumen" class="dialog" title="Resumen">
		<table class="tabla-resumen">
			<caption>Cálculo de la Estimación</caption>
			<colgroup>
				<col/>
				<col class="pct"/>
				<col class="monto"/>
			</colgroup>
			<tbody>
				<tr>
					<td>Importe Programado</td>
					<td></td>
					<td class="numerico editable" id="txtImporteProgramado"></td>
				</tr>
				<tr>
					<td>Estimado Acumulado Anterior</td>
					<td></td>
					<td class="numerico" id="txtImporteEstimadoAcumuladoAnterior"></td>
				</tr>
				<tr>
					<td>Esta Estimacion</td>
					<td></td>
					<td class="numerico" id="txtImporteEstimacion"></td>
				</tr>
				<tr>
					<td>Obra Ejecutada Estimada</td>
					<td></td>
					<td class="numerico" id="txtImporteObraEjecutadaEstimada"></td>
				</tr>
				<tr>
					<td>Obra Acumulada no Ejecutada</td>
					<td></td>
					<td class="numerico" id="txtImporteObraAcumuladaNoEjecutada"></td>
				</tr>
				<tr>
					<td>Retención por Obra no Ejecutada</td>
					<td id="txtPctObraNoEjecutada" class="porcentaje"></td>
					<td class="numerico editable" id="txtImporteRetencionObraNoEjecutada"></td>
				</tr>
				<tr>
					<td>Devolución</td>
					<td></td>
					<td class="numerico editable" id="txtImporteDevolucion"></td>
				</tr>
				<tr>
					<td>Subtotal a Facturar</td>
					<td></td>
					<td class="numerico" id="txtSubtotalFacturar"></td>
				</tr>
				<tr>
					<td>I.V.A.</td>
					<td></td>
					<td class="numerico" id="txtIVAFacturar"></td>
				</tr>
				<tr>
					<td>Total a Facturar</td>
					<td></td>
					<th class="numerico" id="txtTotalFacturar"></th>
				</tr>
			</tbody>
		</table>
		<table class="tabla-resumen">
			<caption>DEDUCCIONES</caption>
			<colgroup>
				<col/>
				<col class="pct"/>
				<col class="monto"/>
			</colgroup>
			<tbody>
				<tr>
					<td>Amortización de Anticipo</td>
					<td></td>
					<td class="numerico editable" id="txtImporteAmortizacionAnticipo"></td>
				</tr>
				<tr>
					<td>I.V.A. sobre Anticipo</td>
					<td id="txtPctIVAAnticipo" class="porcentaje"></td>
					<td class="numerico editable" id="txtImporteIVAAnticipo"></td>
				</tr>
				<tr>
					<td>Inspección y Vigilancia</td>
					<td id="txtPctInspeccionVigilancia" class="porcentaje"></td>
					<td class="numerico editable" id="txtImporteInspeccionVigilancia"></td>
				</tr>
				<tr>
					<td>Camara Mexicana de Ingenieros Civiles</td>
					<td></td>
					<td class="numerico editable" id="txtImporteCMIC"></td>
				</tr>
				<tr>
					<th>Total Deducciones</th>
					<td></td>
					<th class="numerico" id="txtTotalDeducciones"></th>
				</tr>
			</tbody>
		</table>
		<table class="tabla-resumen">
			<caption>TOTAL</caption>
			<colgroup>
				<col/>
				<col class="monto"/>
			</colgroup>
			<tbody>
				<tr class="total">
					<th>Alcance Liquido al Contratista</th>
					<th class="numerico" id="txtImporteLiquidoContrato"></th>
				</tr>
			</tbody>
		</table>
	</div>
	<div id="message-console"><span id="console-message"></span><span id="console-toggler" class="open"></span></div>
	<div id="cache"></div>

	<script type="text/template" id="concepto-template">
		<tr <%= Estimado === 1 ? 'class="modificado"' : "" %> data-id="<%- IDConcepto %>" data-esactividad="<%- EsActividad %>">
			<td class="icon-cell">
				<a class="icon fixed"></a>
			</td>
			<<%= EsActividad === 1 ? 'td' : 'th' %> title="<%- Descripcion %>">
			 <%= '&nbsp;&nbsp;'.repeat( NumeroNivel ) + Descripcion %>
			</<%= EsActividad === 1 ? 'td' : 'th' %>>
			<td class="centrado"><%- Unidad %></td>
			<td class="numerico"><%= EsActividad ? CantidadPresupuestada : '' %></td>
			<td class="numerico"></td>
			<td class="numerico"><%= EsActividad ? CantidadEstimada : '' %></td>
			<td class="numerico"><%= EsActividad ? PrecioUnitarioEstimado : '' %></td>
			<td class="numerico"><%= EsActividad ? ImporteEstimado : '' %></td>
			<td class="editable-cell numerico"><%= EsActividad ? CantidadCobrada : '' %></td>
			<td class="editable-cell numerico"><%= EsActividad ? PrecioUnitarioCobrado : '' %></td>
			<td class="numerico"><%= EsActividad ? ImporteCobrado : '' %></td>
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
	<script src="inc/js/confirmacion_cobranza.js"></script>
</body>
</html>