<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
<html lang="es-mx">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, inital-scale=1, maximum-scale=1"/>
	<title>Estimaciones de Subcontratos</title>

	<link rel="stylesheet" href="css/normalize.css" />
	<link rel="stylesheet" href="inc/js/jquery-ui/css/grupo-hi/jquery-ui.min.css" />
	<link rel="stylesheet" href="css/multiple-select.css" />
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
					<h2>Estimación de Subcontrato</h2>
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
		<table id="tabla-subcontratos" class="stripped">
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
	
	<div id="dialog-resumen" class="dialog" title="Resumen de Estimación"></div>
	
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
				<div style="margin-top: 1em; text-align: right;border-top: 1px solid #222;padding: 0.5em 0;">
					<input type="submit" class="button dd-list" value="Guardar" />
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
					<col>
					<col class="icon"/>
				</colgroup>
				<thead>
					<tr>
						<th>Tipo</th>
						<th>Importe</th>
						<th>Concepto</th>
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
		<form id="form-nueva-retencion" action="" method="get" accept-charset="utf-8">
			<div>
				<label for="tipo_retencion">Tipo Retención</label>
				<select id="tipo_retencion"></select>
			</div>
			<p>
				<label for="txtConceptoRetencion">Concepto</label>
				<textarea id="txtConceptoRetencion" name="txtConceptoRetencion"></textarea>
			</p>
			<label for="txtImporteRetencion">Importe</label>
			<input type="text" id="txtImporteRetencion" class="amount" placeholder="0.00" />
			<div style="margin-top: 1em; text-align: right;border-top: 1px solid #222;padding: 0.5em 0 0 0;">
				<input type="submit" class="button" value="Guardar" />
			</div>
		</form>
	</div>
	
	<div id="dialog-nueva-liberacion" class="dialog" title="Nueva Liberación">
		<form id="form-nueva-liberacion" method="post" class="dialog-form" style="
    white-space: nowrap;
">
			<div style="
    display: inline-block;
    margin: 1em;
    vertical-align: top;
">
				<div>
					<label for="txtImporteLiberacion">Importe</label>
					<input type="text" id="txtImporteLiberacion" class="input amount" placeholder="0.00" />
				</div>
				<div>
					<label for="txtObservacionesLiberacion">Concepto</label>
					<textarea id="txtObservacionesLiberacion" class="input" placeholder="Capture aqui el concepto de liberacion"></textarea>
				</div>
			</div>
			<div style="
    display: inline-block;
    margin: 1em;
    vertical-align: top;
">
				<section class="importe-grande" style="padding: 1em; background-color: #333; color: white;box-shadow: 0 0 5px #333; width: 160px;">
					<div class="title" style="text-align: right;">POR LIBERAR</div>
					<div id="txtImportePorLiberar" class="content" style="margin: 0.5em 0.2em 0.5em 0.2em; font-size: 2em;text-align: center;">
					</div>
				</section>
			</div>
			<div style="margin-top: 1em; text-align: right;border-top: 1px solid #222;padding: 0.5em 0 0 0;">
				<input type="submit" class="button dd-list" value="Guardar" />
			</div>
		</form>
	</div>

	<div id="message-console">
		<span id="console-message"></span>
		<span id="console-toggler" class="open"></span>
	</div>
	<div id="cache"></div>

	<script type="text/template" id="template-tipo-retencion">
		<option value="<%- id %>"><%- descripcion %></option>
	</script>

	<script type="text/template" id="template-concepto">
		<tr data-id="<%- IDConceptoContrato %>" <%= EsActividad ? 'data-iddestino="' + IDConceptoDestino + '"' : '' %> <%= estimado ? 'class="estimado"' : '' %>>
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
		<tr data-id="<%- id_descuento %>" data-idmaterial="<%- id_material %>">
			<td title="<%- descripcion %>"><%- descripcion %></td>
			<td class="numerico"><%- cantidad %></td>
			<td class="centrado"><%- unidad %></td>
			<td class="numerico"><%= precio %></td>
			<td class="numerico"><%= importe %></td>
			<td class="numerico"><%- cantidad_descontada_anterior %></td>
			<td class="numerico"><%= importe_descontado_anterior %></td>
			<th class="numerico"><%- cantidad_por_descontar %></th>
			<th class="numerico"><%= importe_por_descontar %></th>
			<td class="numerico">
				<input type="text" class="text" name="cantidad_descuento" placeholder="0.00" value="<%= cantidad_descuento == 0 ? "" : cantidad_descuento %>" />
			</td>
			<td class="numerico">
				<input type="hidden" name="id_material" value="<%- id_material %>">
				<input type="text" class="text" name="precio_descuento" value="<%= precio_descuento %>"/>
			</td>
			<th class="numerico"><%- importe_descuento %></th>
		</tr>;
	</script>
	
	<script type="text/template" id="template-retencion">
		<tr data-id="<%- id %>">
			<td><%- tipo_retencion %></td>
			<td class="numerico"><%= importe.numFormat() %></td>
			<td title="<%- concepto %>"><%- concepto %></td>
			<td class="icon-cell">
				<span class="icon action delete"></span>
			</td>
		</tr>
	</script>

	<script type="text/template" id="template-liberacion">
		<tr data-id="<%- id %>">
			<td class="numerico"><%= importe.numFormat() %></td>
			<td title="<%- concepto %>"><%- concepto %></td>
			<td class="icon-cell">
				<span class="icon action delete"></span>
			</td>
		</tr>
	</script>

	<script type="text/template" id="template-resumen">
		<table id="resumen-total" class="tabla-resumen">
			<colgroup>
				<col/>
				<col class="pct"/>
				<col class="monto"/>
			</colgroup>
			<tbody>
				<tr>
					<th colspan="2">Suma de Importes</th>
					<td class="numerico"><%- totales.suma_importes %></td>
				</tr>
				<tr>
					<th>Amortización de Anticipo</th>
					<td class="porcentaje"><%- totales.porcentaje_anticipo %></td>
					<td class="numerico editable" id="txtAmortAnticipo"><%- totales.amortizacion_anticipo %></td>
				</tr>
				<tr>
					<th colspan="2">Subtotal</th>
					<td class="numerico"><%- totales.subtotal %></td>
				</tr>
                <tr>
					<th colspan="2">I.V.A.</th>
					<td class="numerico"><%- totales.iva %></td>
				</tr>
                <tr>
					<th colspan="2">Total</th>
					<th class="numerico"><%- totales.total_estimacion %></th>
				</tr>
                <tr>
                    <th>Fondo de Garantia</th>
                    <td class="porcentaje"><%- totales.porcentaje_fondo_garantia %></td>
                    <td class="numerico editable" id="txtFondoGarantia"><%- totales.fondo_garantia %></td>
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
					<td class="numerico"><%- totales.descuentos %></td>
				</tr>
				<tr>
					<th>Retenciones</th>
					<td class="numerico"><%- totales.retenciones %></td>
				</tr>
				<tr>
					<th>Retención de I.V.A.</th>
					<td class="numerico editable" id="txtRetencionIVA"><%- totales.retencion_iva %></td>
				</tr>
				<tr>
					<th>Retenciones Liberadas</th>
					<td class="numerico"><%- totales.retencion_liberada %></td>
				</tr>
				<tr>
					<th>Anticipo A Liberar</th>
					<td class="numerico editable" id="txtAnticipoLiberar"><%- totales.anticipo_a_liberar %></td>
				</tr>
				<tr class="total">
					<th>Monto a Pagar</th>
					<th class="numerico"><%- totales.total_pagar %></th>
				</tr>
			</tbody>
		</table>
		<div style="margin-top: 1em; text-align: right;border-top: 1px solid #222;padding: 0.5em 0;">
			<%= aprobada ? '<input type="button" class="button alert" id="btn-revertir-aprobar" value="Revertir Aprobación" />' :
			'<input type="button" class="button dd-list" id="btn-aprobar" value="Aprobar" autofocus />' %>
		</div>
	</script>

	<script src="inc/js/lib/underscore-min.js"></script>
	<script src="inc/js/jquery-1.7.1.min.js"></script>
	<script src="inc/js/jquery-ui/js/jquery-ui.min.js"></script>
	<script src="inc/js/jquery-ui/js/i18n/jquery.ui.datepicker-es.min.js"></script>
	<script src="inc/js/lib/jquery.multiple.select.js"></script>

	<script src="inc/js/general.js"></script>
	<script src="inc/js/jquery.buttonlist.js"></script>
	<script src="inc/js/jquery.listaTransacciones.js"></script>
	<script src="inc/js/jquery.uxtable.js"></script>
	<script src="inc/js/jquery.notify.js"></script>
	<script src="inc/js/estimaciones.js"></script>
</body>
</html>