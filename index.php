<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
<html lang="es-mx">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, inital-scale=1, maximum-scale=1"/>
		<title>Modulos SAO</title>
		
		<link rel="stylesheet" href="css/normalize.css" />
		<link rel="stylesheet" href="css/general.css" />

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
					<!-- <header id="app-module-header">
						<h4>-> BIENVENIDO</h4>
					</header> -->
					<div id="app-module-content">

					</div> <!-- module-content -->
				</div> <!-- module -->
			</div> <!-- app-content -->

			<footer id="app-footer">
				<?php include("inc/app-footer.php"); ?>
			</footer> <!-- app-footer -->
		</div> <!-- app-wrapper -->
		
		<div id="message-console">
			<span id="console-message"></span>
			<span id="console-toggler" class="open"></span>
		</div>
		<script src="inc/js/jquery-1.7.1.min.js"></script>
		<script src="inc/js/general.js"></script>
		<script src="inc/js/analisis.js"></script>
	</body>
</html>