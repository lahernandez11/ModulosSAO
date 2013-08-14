<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
<html lang="es-mx">
<head>
	<meta charset="utf-8" />
	<title>Estimaciones de Subcontratos</title>

	<link rel="stylesheet" href="css/normalize.css" />
	<link rel="stylesheet" href="css/general.css" />
	<link rel="stylesheet" href="css/estimaciones.css" />
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
	<script src="inc/js/jquery.notify.js"></script>
	<script src="inc/js/estimaciones.js"></script>
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
						<span class="icon"></span>
					</a>
					<a id="nuevo" class="toolbar-button new">
						<span class="icon"></span>
						<span class="label">Nuevo</span>
					</a>
					<a id="eliminar" class="toolbar-button delete">
						<span class="icon"></span>
						<span class="label">Eliminar</span>
					</a>
					<a id="guardar" class="toolbar-button save">
						<span class="icon"></span>
						<span class="label">Guardar</span>
					</a>
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
									<input type="text" class="date" name="txtFecha" id="txtFecha" />
									<input type="hidden" name="txtFechaDB" id="txtFechaDB" />
								</form>
								<a id="btnDeductivas" class="toolbar-button op deductiva"><span class="icon"></span><span class="label">Deductivas</span></a>
								<a id="btnRetenciones" class="toolbar-button op retencion"><span class="icon"></span><span class="label">Retenciones</span></a>
								<a id="btnResumen" class="toolbar-button op resumen"><span class="icon"></span><span class="label">Resumen</span></a>
								<a id="btnFormatoPDF" class="toolbar-button op formato" target="_blank" href="inc/lib/controllers/EstimacionSubcontratoController.php?action=generaFormato&IDTransaccion="><span class="icon"></span><span class="label">Formato</span></a>
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
									<col span="2" class="monto contratado"/>
									<col span="2" class="monto avance-volumen"/>
									<col class="pct avance-volumen"/>
									<col span="2" class="monto avance-importe"/>
									<col span="2" class="monto saldo"/>
									<col class="monto editable"/>
									<col class="pct editable"/>
									<col class="monto"/>
									<col class="monto editable"/>
									<col class="destino"/>
								</colgroup>
								<thead>
									<tr>
										<th rowspan="2"></th>
										<th rowspan="2">Concepto</th>
										<th rowspan="2">UM</th>
										<th colspan="2">SUBCONTRATADO</th>
										<th colspan="3">AVANCE VOLUMEN</th>
										<th colspan="2">AVANCE IMPORTE</th>
										<th colspan="2">SALDO</th>
										<th colspan="4">ESTIMACIÓN</th>
										<th>DISTRIBUCIÓN</th>
									</tr>
									<tr>
										<th>Volumen</th>
										<th>P.U.</th>
										<th>Anterior</th>
										<th>Acum.</th>
										<th>% Acum.</th>
										<th>Anterior</th>
										<th>Acum.</th>
										<th>Volumen</th>
										<th>Importe</th>
										
										<th>Volumen</th>
										<th>%</th>
										<th>P.U.</th>
										<th>Importe</th>
										
										<th>Destino</th>
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
	<div id="dialog-deductivas" class="dialog" title="Deductivas">
		<div id="tipo_deductiva">
			<input type="radio" name="tipo_deductiva" id="materiales" value="1"><label for="materiales">Materiales</label>
			<input type="radio" name="tipo_deductiva" id="mano_obra"  value="2"><label for="mano_obra">Mano de Obra</label>
			<input type="radio" name="tipo_deductiva" id="maquinaria"  value="3"><label for="maquinaria">Maquinaria</label>
			<input type="radio" name="tipo_deductiva" id="subcontratos" value="4"><label for="subcontratos">Subcontratos</label>
			<input type="radio" name="tipo_deductiva" id="otros" value="5"><label for="otros">Otros</label>
			<input type="hidden" id="IDMaterial" value="0">
		</div>
		<div id="registros_deductivas" class="registros">
			<table>
				<colgroup>
					<col class="tipo"/>
					<col>
					<col class="monto"/>
					<col>
					<col class="icon"/>
				</colgroup>
				<thead>
					<tr>
						<th>Tipo</th>
						<th>Concepto</th>
						<th>Importe</th>
						<th>Observaciones</th>
						<th></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
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
							<a id="btnNuevaRetencion" class="toolbar-button op new" title="Aplicar nueva retención">
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
							<a id="btnLiberaRetencion" class="toolbar-button op unlock" title="Liberar retención">
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
			<a class="toolbar-button op dd-list" id="tipos-retencion">
				<span class="button-text">Tipo Retención</span>
				<span class="icon"></span>
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
		<label>Importe por liberar: <span id="txtImportePorLiberar"></span></label>
	</div>
	<div id="message-console"><span id="console-message"></span><span id="console-toggler" class="open"></span></div>
	<div id="cache"></div>
</body>
</html>