<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
<html lang="es-mx">
<head>
	<meta charset="utf-8" />
	<title>Catalogo de Proyectos</title>

	<link rel="stylesheet" href="css/normalize.css" />
	<link rel="stylesheet" href="css/general.css" />
	<link rel="stylesheet" href="css/proyectos.css"/>
	<link rel="stylesheet" href="inc/js/jquery-ui/css/grupo-hi/jquery-ui.min.css" />
	
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
		    				<h3 class="title">Proyectos<a href="#dropdown-proyecto" class="dropdown-list-trigger"></a></h3>
		    			</div>
		    			<div class="panel-content">
		    				<ul id="lista-proyectos" class="options-list"></ul>
		    			</div>
		    		</div>
		    		<div id="info-proyectos" class="panel right-panel">
		    			<div class="panel-content">
		    				<div class="tabs" id="info-proyecto">
			    				<ul class="tab-nav">
			    					<li><a href="#general">GENERAL</a></li>
			    					<li><a href="#porcentajes">UNIFICACION</a></li>
			    					<li><a href="#empresas">EMPRESAS</a></li>
			    				</ul>
			    				<div class="tab-panels">
				    				<section id="general" class="tab-panel">
				    					<form id="form-datos-generales">
				    						<fieldset>
				    							<legend>INFORMACION GENERAL</legend>
				    							<div>
				    								<label for="txtNombreProyecto">Nombre:</label>
				    								<input type="text" id="txtNombreProyecto" name="txtNombreProyecto" />
				    							</div>
				    							<div>
				    								<label for="txtDescripcionProyecto">Descripción:</label>
				    								<textarea id="txtDescripcionProyecto" name="txtDescripcionProyecto"></textarea>
				    							</div>
				    							<div class="select">
				    								<label for="txtTipoProyecto">Tipo de Proyecto:</label>
				    								<a class="dropdown-list-trigger" href="#dropdown-tipo-proyecto"></a>
				    								<input type="text" id="txtTipoProyecto" name="txtTipoProyecto" disabled="disabled" />
				    							</div>
				    							<div class="select">
				    								<label for="txtEmpresa">Empresa:</label>
				    								<a class="dropdown-list-trigger" href="#dropdown-empresas"></a>
				    								<input type="text" id="txtEmpresa" name="txtEmpresa" disabled="disabled" />
				    							</div>
				    						</fieldset>
				    						<fieldset>
				    							<legend>ESTATUS</legend>
				    							<div>
				    								<div id="estatus-proyecto">
					    								<input type="checkbox" id="chkEstaActivo" name="chkEstaActivo" value="0" />
					    								<label for="chkEstaActivo">Activo</label>
					    								<input type="checkbox" id="chkVisibleReportes" name="chkVisibleReportes" value="0" />
					    								<label for="chkVisibleReportes">Visible en Reportes</label>
					    								<input type="checkbox" id="chkVisibleApps" name="chkVisibleApps" value="0" />
					    								<label for="chkVisibleApps">Visible en Apps</label>
					    							</div>
				    							</div>
				    						</fieldset>
				    						<fieldset>
				    							<legend>DIRECCION</legend>
				    							<div class="select">
				    								<label for="txtEstado">Estado:</label>
				    								<a class="dropdown-list-trigger" href="#dropdown-estados"></a>
				    								<input type="text" id="txtEstado" name="txtEstado" disabled="disabled" />
				    							</div>
				    							<div>
				    								<label for="txtDireccionProyecto">Dirección:</label>
				    								<textarea id="txtDireccionProyecto" name="txtDireccionProyecto"></textarea>
				    							</div>
				    						</fieldset>
				    						<fieldset>
				    							<legend>FECHAS DEL PROYECTO</legend>
				    							<div class="multi-field">
				    								<span>
					    								<label for="txtFechaInicio">Inicio:</label>
					    								<input type="text" id="txtFechaInicio" name="txtFechaInicio" class="date" />
					    								<input type="hidden" id="txtFechaInicioDB" name="txtFechaInicioDB" >
				    								</span>
				    								<span>
					    								<label for="txtFechaTermino">Termino:</label>
					    								<input type="text" id="txtFechaTermino" name="txtFechaTermino" class="date" />
					    								<input type="hidden" id="txtFechaTerminoDB" name="txtFechaTerminoDB" />
				    								</span>
				    								<span>
					    								<label for="txtFechaInicioContrato">Inicio de Contrato:</label>
					    								<input type="text" id="txtFechaInicioContrato" name="txtFechaInicioContrato"  class="date"/>
					    								<input type="hidden" id="txtFechaInicioContratoDB" name="txtFechaInicioContratoDB" />
				    								</span>
				    								<span>
					    								<label for="txtFechaTerminoContrato">Termino de Contrato:</label>
					    								<input type="text" id="txtFechaTerminoContrato" name="txtFechaTerminoContrato" class="date"/>
					    								<input type="hidden" id="txtFechaTerminoContratoDB" name="txtFechaTerminoContratoDB" />
					    							</span>
				    							</div>
				    						</fieldset>
				    						<fieldset>
				    							<legend>PORCENTAJES</legend>
				    							<div class="multi-field">
				    								<span>
					    								<label for="txtPctParticipacion">% Participacion:</label>
					    								<input type="text" id="txtPctParticipacion" name="txtPctParticipacion" class="amount" />
				    								</span>
				    								<span>
					    								<label for="txtPctMetaUtilidadCorp">% Meta Utilidad Corporativo:</label>
					    								<input type="text" id="txtPctMetaUtilidadCorp" name="txtPctMetaUtilidadCorp" class="amount" />
				    								</span>
				    								<span>
					    								<label for="txtPctMetaUtilidadObra">% Meta Utilidad Obra:</label>
					    								<input type="text" id="txtPctMetaUtilidadObra" name="txtPctMetaUtilidadObra" class="amount" />
				    								</span>
				    							</div>
				    						</fieldset>
				    						<fieldset>
				    							<legend>MONTOS DE CONTRATO</legend>
				    							<div class="multi-field">
				    								<span>
					    								<label for="txtMontoVentaContrato">Monto de Venta:</label>
					    								<input type="text" id="txtMontoVentaContrato" name="txtMontoVentaContrato" class="amount" />
				    								</span>
				    								<span>
					    								<label for="txtMontoActualContrato">Monto Actual:</label>
					    								<input type="text" id="txtMontoActualContrato" name="txtMontoActualContrato" class="amount" />
				    								</span>
				    								<span>
					    								<label for="txtMontoInicialPIO">Monto Inicial P.I.O.:</label>
					    								<input type="text" id="txtMontoInicialPIO" name="txtMontoInicialPIO" class="amount" />
				    								</span>
				    								<span>
					    								<label for="txtMontoActualPIO">Monto Actual P.I.O.:</label>
					    								<input type="text" id="txtMontoActualPIO" name="txtMontoActualPIO" class="amount" />
					    							</span>
				    							</div>
				    						</fieldset>
			    						</form>
				    				</section>
				    				<!--<section id="fechas" class="tab-panel">FECHAS</section>-->
				    				<section id="unificacion" class="tab-panel">UNIFICACION</section>
				    				<section id="empresas" class="tab-panel">EMPRESAS</section>
				    				
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
			<li><a href="#1">Nuevo Proyecto</a></li>
			<li><a href="#2">Eliminar Proyecto Seleccionado</a></li>
		</ul>
	</div>
	<div id="dialog-nuevo-proyecto" class="dialog" title="Nuevo Proyecto">
		<form>
			<div>
				<label for="txtNombreNvoProyecto">Nombre:</label>
				<input type="text" id="txtNombreNvoProyecto" name="txtNombreNvoProyecto" />
			</div>
		</form>
		<p class="validation-tips"></p>
	</div>
	<div id="confirmation-dialog" class="dialog" title="Modulos SAO">
		<p class="confirmation-message"></p>
	</div>
	<div id="message-console"><span id="console-message"></span><span id="console-toggler" class="open"></span></div>

	<script src="inc/js/jquery-1.7.1.min.js"></script>
	<script src="inc/js/jquery-ui/js/jquery-ui.min.js"></script>
	<script src="inc/js/jquery-ui/js/i18n/jquery.ui.datepicker-es.min.js"></script>
	<script src="inc/js/general.js"></script>
	<script src="inc/js/proyectos.js"></script>
</body>
</html>