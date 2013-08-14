<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8" />
	<title>Modulos SAO</title>
	<link href="css/general.css" type="text/css" rel="stylesheet" />
	<link href="css/superfish.css" type="text/css" rel="stylesheet" />
	<!--[if lt IE 9]><script src="inc/js/html5shiv.js"></script><![endif]-->
	<script src="inc/js/jquery-1.7.1.min.js"></script>
	<script src="inc/js/hoverIntent.js"></script>
	<script src="inc/js/superfish.js"></script>
	<script src="inc/js/general.js"></script>
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
					<h4>-> BIENVENIDO</h4>
				</header>
				<div id="app-module-content">

				</div> <!-- module-content -->
			</div> <!-- module -->
		</div> <!-- app-content -->

		<footer id="app-footer">
			<?php include("inc/app-footer.php"); ?>
		</footer> <!-- app-footer -->
	</div> <!-- app-wrapper -->
	
	<div id="message-console"><span id="console-message"></span><span id="console-toggler" class="open"></span></div>
</body>
</html>