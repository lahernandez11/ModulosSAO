<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8" />
	<title>Confirmación de Cobranza</title>
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
	<script src="inc/js/general.js"></script>
	<script src="inc/js/jquery.buttonlist.js"></script>
	<script src="inc/js/jquery.uxtable.js"></script>
	<script src="inc/js/jquery.notify.js"></script>
	<script src="inc/js/confirmacion_cobranza.js"></script>
</head>

<body>
	<div id="app-wrapper">
		<header id="app-header">
			<?php include("inc/app-header.php"); ?>
		</header> <!-- app-header -->
		
		<nav>
			<ul id="app-nav"></ul> 
		</nav> <!-- app-nav -->
		
		<div id="app-content">
			<div id="app-module">
				<header id="app-module-header">
					<h4>-> CONFIRMACIÓN DE COBRANZA</h4>
				</header>
				<div class="module-toolbar">
					<a class="toolbar-button dd-list" id="bl-proyectos">
						<span class="button-text">Proyectos</span>
						<span class="icon"></span>
					</a>
					<a id="nuevo" class="toolbar-button new"><span class="icon"></span>Nuevo registro</a>
					<a id="eliminar" class="toolbar-button delete"><span class="icon"></span>Eliminar registro</a>
					<a id="guardar" class="toolbar-button save"><span class="icon"></span>Guardar</a>
					<!--<a id="enviar-sao" class="toolbar-button">Enviar a SAO<span class="icon send"></span></a>-->
				</div>
				<div id="app-module-content">
					<section id="tran">
						<section id="tran-header">
							<section class="module-toolbar" id="tran-toolbar">
								<a class="toolbar-button dd-list" id="folios-estimacion">
									<span class="button-text">Folio</span>
									<span class="icon"></span>
								</a>
								<form>
									<label>Fecha</label>
									<input type="text" class="date" name="txtFecha" id="txtFecha" />
									<input type="hidden" name="txtFechaDB" id="txtFechaDB" />
									<input type="hidden" name="IDCobranza" id="IDCobranza" value="" />
									<input type="hidden" name="IDEstimacionObra" id="IDEstimacionObra" />
								</form>
								<a id="btnResumen" class="toolbar-button op resumen"><span class="icon"></span>Resumen</a>
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

						<div style="clear:both"></div>
						<section id="tran-content">
							<div id="column-switchers" class="checkboxgroup">
								<input type="checkbox" id="contratado" name="col-viz" checked />
								<label for="contratado">Contratado</label>
								<input type="checkbox" id="avance-volumen" name="col-viz" checked />
								<label for="avance-volumen">Avance Volumen</label>
								<input type="checkbox" id="avance-importe" name="col-viz" checked />
								<label for="avance-importe">Avance Importe</label>
								<input type="checkbox" id="saldo" name="col-viz" checked />
								<label for="saldo">Saldo</label>
								<input type="checkbox" id="destino" name="col-viz" checked />
								<label for="destino">Destino</label>
							</div>
							<table id="tabla-conceptos">
								<colgroup>
									<col class="icon"/>
									<col/>
									<col class="unidad"/>
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
										<th>AVANCE</th>
										<th colspan="3">COBRADO</th>
									</tr>
									<tr>
										<th>Volumen</th>
										<th>Acum.</th>
										<th>Volumen</th>
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
	<div id="dialog-resumen" class="dialog" title="Resumen de Estimación">
		<table id="resumen-total" class="tabla-resumen">
			<colgroup>
				<col/>
				<col class="monto"/>
			</colgroup>
			<tbody>
				<tr>
					<th>Suma de Importes</th>
					<td class="numerico" id="rsSumaImportes"></td>
				</tr>
				<tr>
					<th>Amortización de Anticipo</th>
					<td class="numerico" id="rsAnticipo"></td>
				</tr>
				<tr>
					<th>Fondo de Garantia</th>
					<td class="numerico" id="rsFondoG"></td>
				</tr>
				<tr>
					<th>Retenciones</th>
					<td class="numerico" id="rsRetenciones"></td>
				</tr>
				<tr>
					<th>Penalizaciones</th>
					<td class="numerico" id="rsPenalizaciones"></td>
				</tr>
				<tr>
					<th>Subtotal</th>
					<td class="numerico" id="rsSubtotal"></td>
				</tr>
				<tr>
					<th>I.V.A.</th>
					<td class="numerico" id="rsIVA"></td>
				</tr>
				<tr>
					<th>Retención de I.V.A.</th>
					<td class="numerico" id="rsRetencionIVA"></td>
				</tr>
				<tr class="total">
					<th>Total</th>
					<td class="numerico" id="rsTotal"></td>
				</tr>
			</tbody>
		</table>
	</div>
	<div id="dialog-retenciones" class="dialog" title="Retenciones">
		<div class="table-toolbar">
			<h4 class="table-title">Retenciones</h4>
			<ul class="options">
				<li>
					<a id="nueva-retencion" class="add-new" title="Agregar retencion"></a>
				</li>
			</ul>
		</div>
		<table id="retenciones-registradas" class="item-list">
			<colgroup>
				<col class="icon"/>
				<col class="concepto"/>
				<col class="monto"/>
				<col/>
			</colgroup>
			<thead>
				<tr>
					<th></th>
					<th>Tipo</th>
					<th>Importe</th>
					<th>Descripción</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div id="dialog-nuevaRetencion" class="dialog" title="Nueva Retención">
		<div class="validation-tips"></div>
		<form>
			<div>
				<label for="lstTipoRetencion">Tipo:</label>
				<select class="select" id="lstTipoRetencion">
					<option value="1">Prueba</option>
				</select>
			</div>
			<div>
				<label for="txtImporteRetencion">Importe:</label>
				<input type="text" id="txtImporteRetencion" name="txtImporteRetencion" class="amount" value="0" />
			</div>
			<div>
				<label for="txtDescripcionRetencion">Descripción:</label>
				<textarea id="txtDescripcionRetencion" name="txtDescripcionRetencion"></textarea>
			</div>
		</form>
	</div>
	<div id="dialog-penalizaciones" class="dialog" title="Penalizaciones">
		<div class="table-toolbar">
			<h4 class="table-title">Penalizaciones</h4>
			<ul class="options">
				<li>
					<a id="nueva-penalizacion" class="add-new" title="Agregar penalización"></a>
				</li>
			</ul>
		</div>
		<table id="penalizaciones-registradas" class="item-list">
			<colgroup>
				<col class="icon"/>
				<col class="monto"/>
				<col/>
			</colgroup>
			<thead>
				<tr>
					<th></th>
					<th>Importe</th>
					<th>Descripción</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
		
		<div class="table-toolbar">
			<h4 class="table-title">Penalizaciones por Liberar</h4>
		</div>
		<table id="penalizaciones-pendientes" class="item-list">
			<colgroup>
				<col class="icon"/>
				<col class="monto"/>
				<col/>
			</colgroup>
			<thead>
				<tr>
					<th></th>
					<th>Importe</th>
					<th>Descripción</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div id="dialog-nuevaPenalizacion" class="dialog" title="Nueva Penalización">
		<div class="validation-tips"></div>
		<form>
			<div>
				<label for="txtImportePenalizacion">Importe:</label>
				<input type="text" id="txtImportePenalizacion" name="txtImportePenalizacion" class="amount" value="0" />
			</div>
			<div>
				<label for="txtDescripcionPenalizacion">Descripción:</label>
				<textarea id="txtDescripcionPenalizacion" name="txtDescripcionPenalizacion"></textarea>
			</div>
		</form>
	</div>
	<div id="message-console"><span id="console-message"></span><span id="console-toggler" class="open"></span></div>
	<div id="cache">
		
	</div>
</body>
</html>