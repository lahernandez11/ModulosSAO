<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!doctype html>
<html lang="en-ES">
<head>
	<meta charset="UTF-8">
	<title>Administración de Usuarios</title>
	<link href="css/general.css" type="text/css" rel="stylesheet" />
	<link href="css/superfish.css" type="text/css" rel="stylesheet" />
	<link href="inc/js/jquery-ui/css/south-street/jquery-ui-1.8.18.custom.css" type="text/css" media="all" rel="stylesheet" />
	<!--[if lt IE 9]><script src="inc/js/html5shiv.js"></script><![endif]-->
	<script src="inc/js/jquery-1.7.1.min.js"></script>
	<script src="inc/js/jquery-ui/js/jquery-ui-1.8.18.custom.min.js"></script>
	<script src="inc/js/jquery-ui/development-bundle/ui/i18n/jquery.ui.datepicker-es.js"></script>
	<script src="inc/js/hoverIntent.js"></script>
	<script src="inc/js/superfish.js"></script>
	<script src="inc/js/general.js"></script>
	<!--<script src="inc/js/usuarios.js"></script>-->
</head>
<body>
	<div id="app-wrapper">
		<header id="app-header">
			<?php include("inc/app-header.php"); ?>
		</header> <!-- app-header -->
		
		<nav>
			<?php include("inc/app-menu.php"); ?>
		</nav> <!-- app-nav -->
		
		<div id="app-content">
			<div id="app-module">
				<header id="app-module-header">
					<h4>-> ADMINISTRACIÓN DE USUARIOS</h4>
				</header>
				<div id="app-module-content" class="two-panel-app">
					
					<div class="panel left-panel">
						<header class="panel-header">
		    				<h3 class="title">Usuarios<a href="#dropdown-proyecto" class="dropdown-list-trigger"></a></h3>
		    			</header>
		    			<div class="panel-content">
		    				<ul id="lista-usuarios" class="tree"></ul>
		    			</div>
		    		</div>
		    		<div id="info-usuarios" class="panel right-panel">
		    			<div class="panel-content">
		    				<div class="tabs" id="info-usuario">
			    				<ul class="tab-nav">
			    					<li><a href="#general">GENERAL</a></li>
			    					<li><a href="#addendums">APLICACIONES</a></li>
			    					<li><a href="#documentos">PROYECTOS</a></li>
			    				</ul>
			    				<div class="tab-panels">
				    				<section id="general" class="tab-panel">
				    					<form id="form-datos-generales">
				    						<fieldset>
				    							<legend>INFORMACION GENERAL</legend>
				    							<div>
				    								<label for="txtNombreUsuario">Nombre:</label>
				    								<textarea id="txtNombreUsuario" name="txtNombreUsuario" disabled="disabled"></textarea>
				    							</div>
				    							<div>
				    								<label for="txt">Nombre de Usuario:</label>
				    								<textarea id="txtDescripcionSubcontrato" name="txtDescripcionSubcontrato"></textarea>
				    							</div>
				    							<div class="multi-field">
				    								<span>
					    								<label for="txtMontoSubcontrato">Monto de Subcontrato:</label>
					    								<input type="text" id="txtMontoSubcontrato" name="txtMontoSubcontrato" class="amount" />
					    							</span>
				    							</div>
				    						</fieldset>
			    						</form>
				    				</section>
				    				<section id="aplicaciones" class="tab-panel">
				    					APLICACIONES
				    				</section>
				    				<section id="proyectos" id="tab-panel">
				    					PROYECTOS
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
			<li><a href="#1">Nuevo Usuario</a></li>
			<li><a href="#2">Eliminar Usuario Seleccionado</a></li>
		</ul>
	</div>
	<div id="confirmation-dialog" class="dialog" title="Modulos SAO">
		<p class="confirmation-message"></p>
	</div>
	<div id="message-console"><span id="console-message"></span><span id="console-toggler" class="open"></span></div>
</body>
</html>