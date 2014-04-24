<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
<html lang="es-mx">
<head>
	<meta charset="utf-8" />
	<title>Estimaciones de Subcontratos</title>

	<link rel="stylesheet" href="css/normalize.css" />
	<link rel="stylesheet" href="inc/js/jquery-ui/css/south-street/jquery-ui-1.8.18.custom.css" />
	<link rel="stylesheet" href="css/multiple-select.css" />
	<link rel="stylesheet" href="css/opentip.css" />
	<link rel="stylesheet" href="css/general.css" />
	<link rel="stylesheet" href="css/estimaciones.css" />
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
					<a id="nuevo" class="button" data-ot="bla bla" data-ot-style="dark">
						<span class="label">Nuevo</span>
					</a>
					<a id="guardar" class="button" title="prueba tooltip">
						<span class="label">Guardar</span>
					</a>
					<a id="eliminar" class="button">
						<span class="label">Eliminar</span>
					</a>
					<h2>Estimación de Subcontrato</h2>
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
								<a id="btnDeductivas" class="button op">
									<span class="label">Deductivas</span></a>
								<a id="btnRetenciones" class="button op">
									<span class="label">Retenciones</span></a>
								<a id="btnResumen" class="button op">
									<span class="label">Resumen</span></a>
								<a id="btnFormatoPDF" class="button op" target="_blank" href="inc/lib/controllers/EstimacionSubcontratoController.php?action=generaFormato&id_obra=&base_datos=&id_transaccion=">
									<span class="label">Formato</span></a>
							</section>

							<section id="tran-info">
								<form>
									<fieldset>
										<legend>Subcontrato</legend>
										<div class="multi-field">
											<label>Objeto</label>
											<div id="txtObjetoSubcontrato" class="roField"></div>
										</div>
										<div class="multi-field">
											<label>Contratista</label>
											<div id="txtNombreContratista" class="roField"></div>
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
										<legend>Folio</legend>

										<div class="multi-field">
											<span>
												<span class="label">Consecutivo</span>
												<div class="roField folio" id="txtFolioConsecutivo"></div>
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
							</section> <!-- tran-info -->
							<div style="clear:both"></div>
						</section> <!-- tran-header -->

						<div style="clear:both"></div>
						<section id="tran-content">
							
							<select class="col-switch conceptos" multiple="multiple">
								<option value="contratado">Contratado</option>
								<option value="avance-volumen">Avance Volumen</option>
								<option value="avance-importe">Avance Importe</option>
								<option value="saldo">Saldo</option>
								<option value="destino">Destino</option>
							</select>

							<!-- <div id="column-switchers" class="checkboxgroup">
								<input type="checkbox" id="contratado" name="col-viz" />
								<label for="contratado">Contratado</label>
								<input type="checkbox" id="avance-volumen" name="col-viz" />
								<label for="avance-volumen">Avance Volumen</label>
								<input type="checkbox" id="avance-importe" name="col-viz" />
								<label for="avance-importe">Avance Importe</label>
								<input type="checkbox" id="saldo" name="col-viz" />
								<label for="saldo">Saldo</label>
								<input type="checkbox" id="destino" name="col-viz" />
								<label for="destino">Destino</label>
							</div> -->
							<table id="tabla-conceptos">
								<colgroup>
									<col class="icon" />
									<col/>
									<col class="unidad" />
									<col class="monto contratado" span="2" />
									<col class="monto avance-volumen" span="2" />
									<col class="pct avance-volumen" />
									<col class="monto avance-importe" span="2" />
									<col class="monto saldo" span="2" />
									<col class="monto editable" />
									<col class="pct editable" />
									<col class="monto" />
									<col class="monto editable" />
									<col class="destino"/>
								</colgroup>
								<thead>
									<tr>
										<th rowspan="2"></th>
										<th rowspan="2">Concepto</th>
										<th rowspan="2">UM</th>
										<th colspan="2" class="contratado">Contratado</th>
										<th colspan="3" class="avance-volumen">Avance Volumen</th>
										<th colspan="2" class="avance-importe">Avance Importe</th>
										<th colspan="2" class="saldo">Saldo</th>
										<th colspan="4">Esta Estimación</th>
										<th class="destino">Distribución</th>
									</tr>
									<tr>
										<th class="contratado">Volumen</th>
										<th class="contratado">P.U.</th>
										<th class="avance-volumen">Anterior</th>
										<th class="avance-volumen">Acumulado</th>
										<th class="avance-volumen">%</th>
										<th class="avance-importe">Anterior</th>
										<th class="avance-importe">Acumulado</th>
										<th class="saldo">Volumen</th>
										<th class="saldo">Importe</th>
										
										<th>Volumen</th>
										<th>%</th>
										<th>P.U.</th>
										<th>Importe</th>
										<th class="destino">Destino</th>
									</tr>
								</thead>
								<tbody>
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
		<table id="tabla-subcontratos">
			<colgroup>
				<col/>
				<col span="2" class="folio"></col>
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
	<div id="dialog-resumen" class="dialog" title="Resumen de Estimación">
		<table id="resumen-total" class="tabla-resumen">
			<colgroup>
				<col/>
				<col class="monto"/>
			</colgroup>
			<tbody>
				<tr>
					<th>Suma de Importes</th>
					<td class="numerico" id="txtSumaImportes"></td>
				</tr>
				<tr>
					<th>Amortización de Anticipo</th>
					<td class="numerico editable" id="txtAmortAnticipo"></td>
				</tr>
				<tr>
					<th>Fondo de Garantia</th>
					<td class="numerico editable" id="txtFondoGarantia"></td>
				</tr>
				<tr>
					<th>Subtotal</th>
					<td class="numerico" id="rsSubtotal"></td>
				</tr>
				<tr>
					<th>I.V.A.</th>
					<td class="numerico" id="rsIVA"></td>
				</tr>
				<tr class="total">
					<th>Total</th>
					<td class="numerico" id="rsTotal"></td>
				</tr>
			</tbody>
		</table>
		<table class="tabla-resumen">
			<caption>Deductivas y Retenciones</caption>
			<colgroup>
				<col/>
				<col class="monto"/>
			</colgroup>
			<tbody>
				<tr>
					<th>Deductivas</th>
					<td class="numerico" id="txtSumaDeductivas"></td>
				</tr>
				<tr>
					<th>Retenciones</th>
					<td class="numerico" id="txtSumaRetenciones"></td>
				</tr>
				<tr>
					<th>Retenciones Liberadas</th>
					<td class="numerico" id="txtRetencionesLiberadas"></td>
				</tr>
				<tr>
					<th>Retención de I.V.A.</th>
					<td class="numerico editable" id="txtRetencionIVA"></td>
				</tr>
				<tr>
					<th>Anticipo A Liberar</th>
					<td class="numerico editable" id="txtAnticipoLiberar"></td>
				</tr>
			</tbody>
		</table>
	</div>
	<div id="dialog-deductivas" class="" title="Deductivas">
		<div id="registros_deductivas" class="registros">
			<select class="col-switch descuentos" multiple="multiple">
				<option value="descuento-total">Descuento Total</option>
				<option value="descuento-acumulado">Descuento Acumulado</option>
				<option value="descuento-saldo" selected>Por Descontar</option>
			</select>
			<form id="form_descuento" method="post">
				<table class="stripped">
					<colgroup>
						<col />
						<col class="cantidad descuento-total"/>
						<col class="unidad descuento-total">
						<col class="precio descuento-total">
						<col class="monto descuento-total">
						<col class="cantidad descuento-acumulado"/>
						<col class="monto descuento-acumulado">
						<col class="cantidad descuento-saldo"/>
						<col class="monto descuento-saldo">
						<col class="cantidad"/>
						<col class="precio">
						<col class="monto">
					</colgroup>
					<thead>
						<tr>
							<th rowspan="2">Concepto</th>
							<th colspan="4">Descuento Total</th>
							<th colspan="2">Descuento Acumulado</th>
							<th colspan="2">Por Descontar</th>
							<th colspan="3">Descuento Actual</th>
						</tr>
						<tr>
							<th>Cantidad</th>
							<th>Unidad</th>
							<th>P.U.</th>
							<th>Importe</th>
							<th>Cantidad</th>
							<th>Importe</th>
							<th>Cantidad</th>
							<th>Importe</th>
							<th>Cantidad</th>
							<th>P.U.</th>
							<th>Importe</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
				<div style="margin-top: 0.5em; text-align: right;">
					<input type="submit" class="button" value="Guardar" />
				</div>
			</form>
		</div>
	</div>
	<div id="dialog-nueva-deduccion" class="dialog" title="Nueva Deductiva">
		<label for="">Concepto</label>
		<input type="text" id="txtConceptoDeductiva" />
		<label for="">Importe</label>
		<input type="text" id="txtImporteDeductiva" />
		<label for="">Observaciones</label>
		<textarea id="txtObservacionesDeductiva"></textarea>
	</div>
	<div id="dialog-retenciones" class="dialog" title="Retenciones">
		<div id="registros_retenciones" class="registros">
			<table>
				<caption>Aplicadas</caption>
				<colgroup>
					<col class="tipo"/>
					<col class="monto"/>
					<col span="2">
					<col class="icon"/>
				</colgroup>
				<thead>
					<tr>
						<th>Tipo</th>
						<th>Importe</th>
						<th>Concepto</th>
						<th>Observaciones</th>
						<th class="icon-cell">
							<a id="btnNuevaRetencion" class="button op new" title="Aplicar nueva retención">
								<span class="icon"></span>
							</a>
						</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div id="registros_liberaciones" class="registros">
			<table>
				<caption>Liberadas</caption>
				<colgroup>
					<col class="monto"/>
					<col>
					<col class="icon"/>
				</colgroup>
				<thead>
					<tr>
						<th>Importe</th>
						<th>Concepto</th>
						<th class="icon-cell">
							<a id="btnLiberaRetencion" class="button op unlock" title="Liberar retención">
								<span class="icon"></span>
							</a>
						</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>

	<div id="dialog-nueva-retencion" class="dialog" title="Nueva Retención">
		<div>
			<a class="button dd-list" id="tipos-retencion">
				<span class="button-text">Tipo Retención</span>
				<span class="icon flechita-abajo"></span>
			</a>
		</div>
		<label for="txtConceptoRetencion">Concepto</label>
		<input type="text" id="txtConceptoRetencion" />
		<label for="txtImporteRetencion">Importe</label>
		<input type="text" id="txtImporteRetencion" class="amount" value="0" />
		<label for="txtObservacionesRetencion">Observaciones</label>
		<textarea id="txtObservacionesRetencion"></textarea>
	</div>
	<div id="dialog-nueva-liberacion" class="dialog" title="Nueva Liberación">
		<label for="txtImporteLiberacion">Importe</label>
		<input type="text" id="txtImporteLiberacion" class="amount" value="0" />
		<label for="txtObservacionesLiberacion">Observaciones</label>
		<textarea id="txtObservacionesLiberacion"></textarea>
		<label><strong>Importe por liberar del contratista: <span id="txtImportePorLiberar"></span></strong></label>
	</div>
	<div id="message-console">
		<span id="console-message"></span>
		<span id="console-toggler" class="open"></span>
	</div>
	<div id="cache"></div>

	<script type="text/template" id="template-concepto">
		<tr data-id="<%- IDConceptoContrato %>" <%= EsActividad ? 'data-iddestino="' + IDConceptoDestino + '"' : '' %>>
			<td class="icon-cell">
				<a class="icon fixed"></a>
			</td>
			<<%= EsActividad ? 'td' : 'th' %> title="<%- Descripcion %>">
				<%= '&nbsp;&nbsp;'.repeat(NumeroNivel) + Descripcion %>
			</<%= EsActividad ? 'td' : 'th' %>>
			<td class="centrado"><%- Unidad %></td>
			<td class="numerico contratado"><%- CantidadSubcontratada %></td>
			<td class="numerico contratado"><%- PrecioUnitario %></td>

			<td class="numerico avance-volumen"></td>
			<td class="numerico avance-volumen"><%- CantidadEstimadaTotal %></td>
			<td class="numerico avance-volumen"><%- PctAvance %></td>
			
			<td class="numerico avance-importe"></td>
			<td class="numerico avance-importe"><%- MontoEstimadoTotal %></td>
			
			<td class="numerico saldo"><%- CantidadSaldo %></td>
			<td class="numerico saldo"><%- MontoSaldo %></td>
			
			<td class="editable-cell numerico"><%- CantidadEstimada %></td>
			<td class="editable-cell numerico"><%- PctEstimado %></td>
			<td class="numerico"><%- PrecioUnitario %></td>
			<td class="editable-cell numerico"><%- ImporteEstimado %></td>
			<td class="destino" title="<%- RutaDestino %>"><%- RutaDestino %></td>
		</tr>
	</script>
	<script type="text/template" id="template-deductiva">
		<tr data-id="<%- id_descuento %>" data-iditem="<%- id_item %>">
			<td title="<%- descripcion %>"><%- descripcion %></td>
			<td class="numerico"><%- cantidad_total %></td>
			<td class="centrado"><%- unidad %></td>
			<td class="numerico"><%= precio %></td>
			<td class="numerico"><%= importe_total %></td>
			<td class="numerico"><%- cantidad_descontada %></td>
			<td class="numerico"><%= importe_descontado %></td>
			<th class="numerico"><%- cantidad_por_descontar %></th>
			<th class="numerico"><%= importe_por_descontar %></th>
			<td class="numerico">
				<input type="text" class="text" name="cantidad_descuento" value="<%- cantidad_descuento %>"/>
			</td>
			<td class="numerico">
				<input type="hidden" name="id_item" value="<%- id_item %>">
				<input type="text" class="text" name="precio_descuento" value="<%= precio_descuento %>"/>
			</td>
			<th class="numerico"><%- importe_descuento %></th>
		</tr>;
	</script>
	<script type="text/template" id="template-retencion">
		<tr data-id="<%- IDRetencion %>">
			<td><%- TipoRetencion %></td>
			<td class="numerico"><%= importe.numFormat() %></td>
			<td title="<%- concepto %>"><%- concepto %></td>
			<td title="<%- observaciones %>"><%- observaciones %></td>
			<td class="icon-cell">
				<span class="icon action delete"></span>
			</td>
		</tr>
	</script>

	<script src="inc/js/lib/underscore-min.js"></script>
	<script src="inc/js/jquery-1.7.1.min.js"></script>
	<script src="inc/js/jquery-ui/js/jquery-ui-1.8.18.custom.min.js"></script>
	<script src="inc/js/jquery-ui/development-bundle/ui/i18n/jquery.ui.datepicker-es.js"></script>
	<script src="inc/js/lib/opentip-jquery.min.js"></script>
	<script src="inc/js/lib/jquery.multiple.select.js"></script>

	<script src="inc/js/general.js"></script>
	<script src="inc/js/jquery.buttonlist.js"></script>
	<script src="inc/js/jquery.listaTransacciones.js"></script>
	<script src="inc/js/jquery.uxtable.js"></script>
	<script src="inc/js/jquery.notify.js"></script>
	<script src="inc/js/estimaciones.js"></script>
	<script>
        Opentip.defaultStyle = "dark";
	</script>
</body>
</html>