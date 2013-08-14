<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8" />
	<title>Subcontratos</title>
	<link href="css/general.css" type="text/css" rel="stylesheet" />
	<link href="css/subcontratos.css" type="text/css" rel="stylesheet"/>
	<link href="inc/js/jquery-ui/css/south-street/jquery-ui-1.8.18.custom.css" type="text/css" media="all" rel="stylesheet" />
	<link href="css/superfish.css" type="text/css" rel="stylesheet" />
	<!--[if lt IE 9]><script src="inc/js/html5shiv.js"></script><![endif]-->
	<script src="inc/js/jquery-1.7.1.min.js"></script>
	<script src="inc/js/jquery-ui/js/jquery-ui-1.8.18.custom.min.js"></script>
	<script src="inc/js/jquery-ui/development-bundle/ui/i18n/jquery.ui.datepicker-es.js"></script>
	<script src="inc/js/hoverIntent.js"></script>
	<script src="inc/js/superfish.js"></script>
	<script src="inc/js/general.js"></script>
	<script src="inc/js/subcontratos.js"></script>
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
					<h4>-> SUBCONTRATOS</h4>
				</header>
				<div id="app-module-content" class="two-panel-app">
					
					
					
					<div class="panel left-panel">
						<header class="panel-header">
		    				<h3 class="title">Subcontratos<a href="#dropdown-proyecto" class="dropdown-list-trigger"></a></h3>
		    			</header>
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
				    					<form id="form-datos-generales">
				    						<fieldset>
				    							<legend>INFORMACION GENERAL</legend>
				    							<div>
				    								<label for="txtNombreSubcontrato">Nombre:</label>
				    								<textarea id="txtNombreSubcontrato" name="txtNombreSubcontrato" disabled="disabled"></textarea>
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
				<label>Fecha:</label>
				<input type="text" id="txtFechaAddendum" name="txtFechaAddendum" class="date" />
				<input type="hidden" id="txtFechaAddendumDB" name="txtFechaAddendumDB" />
			</div>
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
		</form>
		<p class="validation-tips"></p>
	</div>
	<div id="confirmation-dialog" class="dialog" title="Modulos SAO">
		<p class="confirmation-message"></p>
	</div>
	<div id="message-console"><span id="console-message"></span><span id="console-toggler" class="open"></span></div>
</body>

</html>