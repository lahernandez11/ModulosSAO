<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
<html lang="es-mx">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<title>Subcontratos</title>

	<link rel="stylesheet" href="css/normalize.css" />
	<link rel="stylesheet" href="inc/js/jquery-ui/css/grupo-hi/jquery-ui.min.css" />
	<link rel="stylesheet" href="css/general.css" />
	<link rel="stylesheet" href="css/subcontratos.css"/>
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
				<div id="app-module-content" class="two-panel-app">
					<div class="panel left-panel">
						<div class="panel-header">
		    				<h3 class="title">Subcontratos<a href="#dropdown-proyecto" class="dropdown-list-trigger"></a></h3>
		    			</div>
		    			<div class="panel-content">
		    				<ul id="lista-subcontratos" class="tree"></ul>
		    			</div>
		    		</div>
		    		<div id="info-subcontratos" class="panel right-panel">
		    			<div class="panel-content">
		    				<div class="tabs" id="info-subcontrato">
			    				<ul class="tab-nav">
			    					<li><a href="#general">GENERAL</a></li>
			    					<li><a href="#addendums">ADDENDUMS</a></li>
			    					<li><a href="#documentos">DOCUMENTOS</a></li>
			    				</ul>
			    				<div class="tab-panels">
				    				<section id="general" class="tab-panel">
				    				<!-- <form id="form-datos-generales">
				    					<fieldset>
				    						<legend>INFORMACION GENERAL</legend>
					    					<div class="formux">
						    					<div>
				    								<label for="txtNombreSubcontrato">Nombre:</label>
				    								<textarea class="field" id="txtNombreSubcontrato" name="txtNombreSubcontrato" disabled="disabled"></textarea>
				    							</div>
				    							<div>
				    								<label for="txtDescripcionSubcontrato">Descripción:</label>
				    								<textarea class="field" id="txtDescripcionSubcontrato" name="txtDescripcionSubcontrato"></textarea>
				    							</div>
				    							<div>
				    								<label for="txtTipoContrato">Tipo de Contrato:</label>
				    								<input type="text" class="field" id="txtTipoContrato" name="txtTipoContrato" disabled="disabled" />
				    							</div>
				    							<div>
				    								<label for="txtContratista">Contratista:</label>
				    								<textarea class="field" id="txtContratista" name="txtContratista" disabled="disabled"></textarea>
				    							</div>
				    							<div>
					    								<label for="txtMontoSubcontrato">Monto de Subcontrato:</label>
					    								<input type="text" class="field" id="txtMontoSubcontrato" name="txtMontoSubcontrato" class="amount" />
					    						</div>
					    						<div>
					    								<label for="txtMontoAnticipo">Monto de Anticipo:</label>
					    								<input type="text" class="field" id="txtMontoAnticipo" name="txtMontoAnticipo" class="amount" />

					    								<label for="txtPctFG">% Ret. FG.:</label>
					    								<input type="text" class="field" id="txtPctFG" name="txtPctFG" class="amount" />
				    							</div>
					    					</div>
					    				</fieldset>
					    			</form> -->
				    					<form id="form-datos-generales">
				    						<fieldset>
				    							<legend>INFORMACION GENERAL</legend>
				    							<div>
				    								<label for="txtReferenciaSubcontrato">Referencia:</label>
				    								<textarea id="txtReferenciaSubcontrato" name="txtReferenciaSubcontrato" disabled="disabled"></textarea>
				    							</div>
				    							<div>
				    								<label for="txtDescripcionSubcontrato">Descripción:</label>
				    								<textarea id="txtDescripcionSubcontrato" name="txtDescripcionSubcontrato"></textarea>
				    							</div>
				    							<div>
				    								<label for="txtTipoContrato">Tipo de Contrato:</label>
				    								<input type="text" id="txtTipoContrato" name="txtTipoContrato" disabled="disabled" />
				    							</div>
				    							<div>
				    								<label for="txtContratista">Contratista:</label>
				    								<textarea id="txtContratista" name="txtContratista" disabled="disabled"></textarea>
				    							</div>
				    							<div class="multi-field">
				    								<span>
					    								<label for="txtMontoSubcontrato">Monto de Subcontrato:</label>
					    								<input type="text" id="txtMontoSubcontrato" name="txtMontoSubcontrato" class="amount" />
					    							</span>
					    							<span>
					    								<label for="txtMontoAnticipo">Monto de Anticipo:</label>
					    								<input type="text" id="txtMontoAnticipo" name="txtMontoAnticipo" class="amount" />
					    							</span>
					    							<span>
					    								<label for="txtPctFG">% Ret. FG.:</label>
					    								<input type="text" id="txtPctFG" name="txtPctFG" class="amount" />
					    							</span>
				    							</div>
				    						</fieldset>
				    						<fieldset>
				    							<legend>CLASIFICACION</legend>
				    							<div class="select">
				    								<label for="txtTipoProyecto">Clasificador:</label>
													<a class="dropdown-list-trigger" href="#dropdown-clasificacion"></a>
													<input type="text" id="txtClasificacion" name="txtClasificacion" disabled="disabled" />
				    							</div>
				    						</fieldset>
				    						<fieldset>
				    							<legend>FECHAS DE CONTRATO</legend>
				    							<div class="multi-field">
				    								<span>
					    								<label for="txtFechaInicioCliente">Inicio Cliente:</label>
					    								<input type="text" id="txtFechaInicioCliente" name="txtFechaInicioCliente" class="date" />
					    								<input type="hidden" id="txtFechaInicioClienteDB" name="txtFechaInicioClienteDB" />
				    								</span>
				    								<span>
					    								<label for="txtFechaTerminoCliente">Termino Cliente:</label>
					    								<input type="text" id="txtFechaTerminoCliente" name="txtFechaTerminoCliente" class="date" />
					    								<input type="hidden" id="txtFechaTerminoClienteDB" name="txtFechaTerminoClienteDB" />
				    								</span>
				    								<span>
					    								<label for="txtFechaInicioProyecto">Inicio Proyecto:</label>
					    								<input type="text" id="txtFechaInicioProyecto" name="txtFechaInicioProyecto"  class="date"/>
					    								<input type="hidden" id="txtFechaInicioProyectoDB" name="txtFechaInicioProyectoDB" />
				    								</span>
				    								<span>
					    								<label for="txtFechaTerminoProyecto">Termino Proyecto:</label>
					    								<input type="text" id="txtFechaTerminoProyecto" name="txtFechaTerminoProyecto" class="date"/>
					    								<input type="hidden" id="txtFechaTerminoProyectoDB" name="txtFechaTerminoProyectoDB" />
				    								</span>
				    								<span>
				    									<label for="txtFechaInicioContratista">Inicio Contratista:</label>
				    									<input type="text" id="txtFechaInicioContratista" name="txtFechaInicioContratista"  class="date"/>
				    								<input type="hidden" id="txtFechaInicioContratistaDB" name="txtFechaInicioContratistaDB" />
				    								</span>
				    								<span>
					    								<label for="txtFechaTerminoContratista">Termino Contratista:</label>
					    								<input type="text" id="txtFechaTerminoContratista" name="txtFechaTerminoContratista" class="date"/>
					    								<input type="hidden" id="txtFechaTerminoContratistaDB" name="txtFechaTerminoContratistaDB" />
					    							</span>
				    							</div>
				    						</fieldset>
				    						<fieldset>
				    							<legend>MONTOS DE CONTRATO</legend>
				    							<div class="multi-field">
				    								<span>
					    								<label for="txtMontoVentaCliente">Venta Cliente:</label>
					    								<input type="text" id="txtMontoVentaCliente" name="txtMontoVentaCliente" class="amount" />
				    								</span>
				    								<span>
					    								<label for="txtMontoVentaActualCliente">Actual Cliente:</label>
					    								<input type="text" id="txtMontoVentaActualCliente" name="txtMontoVentaActualCliente" class="amount" />
				    								</span>
				    								<span>
					    								<label for="txtMontoInicialPIO">P.I.O. Inicial:</label>
					    								<input type="text" id="txtMontoInicialPIO" name="txtMontoInicialPIO" class="amount" />
				    								</span>
				    								<span>
					    								<label for="txtMontoActualPIO">P.I.O. Actual:</label>
					    								<input type="text" id="txtMontoActualPIO" name="txtMontoActualPIO" class="amount" />
				    								</span>
				    							</div>
				    						</fieldset>
			    						</form>
				    				</section>
				    				<section id="addendums" class="tab-panel">
				    					<div class="table-toolbar">
				    						<h4 class="table-title">Addendums Registrados</h4>
				    						<ul class="options">
				    							<li><a id="nuevo-addendum" class="add-new" title="Agregar addendum"></a></li>
				    						</ul>
				    					</div>
				    					<table id="addendums-registrados">
				    						
				    						<colgroup>
				    							<col class="icon">
				    							<col class="fecha">
				    							<col class="monto">
				    							<col class="monto">
				    							<col class="monto">
				    						</colgroup>
				    						<thead>
				    							<tr>
				    								<th>&nbsp;</th>
				    								<th>Fecha</th>
				    								<th>Monto</th>
				    								<th>Anticipo</th>
				    								<th>% Ret. FG.</th>
				    							</tr>
				    						</thead>
				    						<tbody></tbody>
				    					</table>
				    				</section>
				    				<section id="documentos" id="tab-panel">
				    					CONTROL DOCUMENTAL
				    				</section>
									<div class="overlay"></div>
								</div> <!-- tab-panels -->
							</div>
		    				<div style="clear: both"></div>
		    			</div>
		    		</div>						
					<div style="clear: both"></div>

				</div> <!-- module-content -->
			</div> <!-- module -->
		</div> <!-- app-content -->
		
		<footer id="app-footer">
			<?php include("inc/app-footer.php"); ?>
		</footer> <!-- app-footer -->
	</div> <!-- app-wrapper -->
	
	<div id="cache">
		<ul id="dropdown-proyecto"class="dropdown-list">
			<li><a href="#1">Nuevo Subcontrato</a></li>
			<li><a href="#2">Eliminar Subcontrato Seleccionado</a></li>
		</ul>
	</div>
	<div id="dialog-nuevo-addendum" class="dialog" title="Nuevo Addendum">
		<form>
			<div>
				<label for="txtMontoAddendum">Monto:</label>
				<input type="text" id="txtMontoAddendum" name="txtMontoAddendum" class="amount" value="0" />
			</div>
			<div>
				<label for="txtAnticipoAddendum">Monto Anticipo:</label>
				<input type="text" id="txtAnticipoAddendum" name="txtAnticipoAddendum" class="amount" value="0" />
			</div>
			<div>
				<label for="txtRetencionFG">% Ret. FG.:</label>
				<input type="text" id="txtRetencionFG" name="txtRetencionFG" class="amount" value="0" />
			</div>
			<div>
				<label>Fecha:</label>
				<input type="text" id="txtFechaAddendum" name="txtFechaAddendum" class="date" />
				<input type="hidden" id="txtFechaAddendumDB" name="txtFechaAddendumDB" />
			</div>
		</form>
		<p class="validation-tips"></p>
	</div>
	<div id="confirmation-dialog" class="dialog" title="Modulos SAO">
		<p class="confirmation-message"></p>
	</div>
	<div id="message-console">
		<span id="console-message"></span>
		<span id="console-toggler" class="open"></span>
	</div>

	<script type="text/template" id="obra-item-template">
		<li class="obra">
			<span class="handle closed"></span>
			<a class="text" data-basedatos="<%- source_id %>" data-id="<%- id %>"><%- nombre %></a>
		</li>
	</script>

	<script type="text/template" id="empresa-item-template">
		<li class="empresa">
			<span class="handle closed"></span>
			<a class="text" data-id="<%- id_empresa %>"><%- empresa %></a>
		</li>
	</script>

	<script type="text/template" id="subcontrato-item-template">
		<li class="transaccion">
			<span class="icon"></span>
			<a class="text selectable" data-id="<%- id_transaccion %>"><%= numero_folio + ' (' + fecha + ') - ' + referencia %></a>
		</li>
	</script>

	<script type="text/template" id="addendum-template">
		<tr data-id="<%- id_addendum %>">
			<td class="icon-cell">
				<span class="icon action delete"></span>
			</td>
			<td class="centrado"><%- fecha %></td>
			<td class="numerico"><%- monto %></td>
			<td class="numerico"><%- monto_anticipo %></td>
			<td class="centrado"><%- porcentaje_retencion_fg %></td>
		</tr>
	</script>

	<script src="inc/js/lib/underscore-min.js"></script>
	<script src="inc/js/jquery-1.7.1.min.js"></script>
	<script src="inc/js/jquery-ui/js/jquery-ui.min.js"></script>
	<script src="inc/js/jquery-ui/js/i18n/jquery.ui.datepicker-es.min.js"></script>
	<script src="inc/js/general.js"></script>
	<script src="inc/js/subcontratos.js"></script>
</body>
</html>