<?php require_once 'setPath.php'; require_once 'models/Sesion.class.php'; Sesion::validaSesion(); ?>
<!DOCTYPE html>
	<html>
	<head>
		<meta charset="utf-8">
		<title>Presupuesto de Obra</title>
		<link rel="stylesheet" href="css/normalize.css" />
		<link rel="stylesheet" href="inc/js/jquery-ui/css/south-street/jquery-ui-1.8.18.custom.css" />
		<link rel="stylesheet" href="css/general.css" />
		<link rel="stylesheet" href="css/jquery.notify.css" />
		
		<!--[if lt IE 9]><script src="inc/js/html5shiv.js"></script><![endif]-->
	</head>
	<body>
		<!-- <section>
			<div class="app-wrapper">
				<section class="titulo">
					<h2>Precios de Venta</h2>
				</section>
				<section class="toolbar">
					<a class="toolbar-button dd-list" id="bl-proyectos">
							<span class="button-text">Proyectos</span>
							<span class="icon flechita-abajo"></span>
						</a>
						<a id="guardar" class="toolbar-button">
							<span class="icon save"></span>
							<span class="label">Guardar</span>
						</a>
				</section>
			</div>
		</section> -->
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
							<span class="icon flechita-abajo"></span>
						</a>
						<h2>Presupuesto de Obra</h2>
					</div>
					<div id="app-module-content">
						<section id="tran">

							<section id="tran-content">
								<table id="tabla-conceptos" class="stripped">
									<colgroup>
										<col class="icon"/>
										<col class="icon"/>
										<col class="icon"/>
										<col class="clave"/>
										<col />
										<col class="unidad"/>
										<col span="3" class="monto"/>
									</colgroup>
									<thead>
										<tr>
											<th></th>
											<th></th>
											<th></th>
											<th>Clave</th>
											<th>Concepto</th>
											<th>Unidad</th>
											<th>Cantidad</th>
											<th>Precio</th>
											<th>Monto</th>
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

		<div id="dialog-propiedades-concepto" class="dialog" title="Propiedades">
			<form method="get" class="dialog-form form-concepto-properties">
				<!-- <label for="">Concepto</label>
				<textarea id="txtDescripcion" class="field"></textarea> -->
				<label for="txtAgrupadorPartida">Agrupador Partida</label>
				<!-- <a title="Eliminar agrupador" class="elimina-agrupador icon-close"></a> -->
				<input type="text" id="txtAgrupadorPartida" class="field" />
				<label for="txtAgrupadorSubpartida">Agrupador Subpartida</label>
				<!-- <a title="Eliminar agrupador" class="elimina-agrupador icon-close"></a> -->
				<input type="text" id="txtAgrupadorSubpartida" class="field" />
				<label for="txtAgrupadorActividad">Agrupador Actividad</label>
				<!-- <a title="Eliminar agrupador" class="elimina-agrupador icon-close"></a> -->
				<input type="text" id="txtAgrupadorActividad" class="field" />
				<label for="txtAgrupadorActividad">Agrupador Tramo</label>
				<!-- <a title="Eliminar agrupador" class="elimina-agrupador icon-close"></a> -->
				<input type="text" id="txtAgrupadorTramo" class="field" />
				<label for="txtAgrupadorActividad">Agrupador Subtramo</label>
				<!-- <a title="Eliminar agrupador" class="elimina-agrupador icon-close"></a> -->
				<input type="text" id="txtAgrupadorSubtramo" class="field" />
				<section class="buttons">
					<input type="button" id="cerrar-concepto" name="cerrar" class="button" value="Cerrar" />
				</section>
			</form>
		</div>

		<div id="dialog-nuevo-agrupador" class="dialog" title="Nuevo Agrupador">
			<form method="get" class="dialog-form form-nuevo-agrupador">
				<label for="txtClaveAgrupador">Clave</label>
				<input type="text" id="txtClaveAgrupador" class="field" />
				<label for="txtDescripcionAgruapdor">Descripcion</label>
				<input type="text" id="txtDescripcionAgruapdor" class="field" />
				<section class="buttons">
					<input type="button" id="guardar_agrupador" name="guardar" class="button" value="Guardar" />
					<input type="button" id="cerrar_agrupador" name="cerrar" class="button" value="Cerrar" />
				</section>
			</form>
		</div>

		<div id="message-console"><span id="console-message"></span><span id="console-toggler" class="open"></span></div>
		<div id="cache"></div>

		<script src="inc/js/jquery-1.7.1.min.js"></script>
		<script src="inc/js/jquery-ui/js/jquery-ui-1.8.18.custom.min.js"></script>
		<script src="inc/js/jquery-ui/development-bundle/ui/i18n/jquery.ui.datepicker-es.js"></script>

		<script src="inc/js/general.js"></script>
		<script src="inc/js/jquery.buttonlist.js"></script>
		<script src="inc/js/jquery.uxtable.js"></script>
		<script src="inc/js/jquery.notify.js"></script>
		<script src="inc/js/presupuesto_obra.js"></script>
	</body>
</html>