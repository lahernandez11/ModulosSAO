$(function() {	
	
	PROYECTOS.init();
});

var PROYECTOS = {
	
	container: '#info-proyectos',
	currentRequest: null,
	
	init: function() {
		
		var P = this;
		
		// Inicializa el panel de tabs
		TABS.container = '#info-proyecto';
		TABS.init();
		TABS.disable();
		TABS.onSelect = function(event, tab) {
			
			if( ! TABS.isSelected(tab) ) {
				
				switch(tab.attr('href') ) {
					
					case '#general':
						P.cargaDatosGenerales();
					break;
					case '#fechas':
						//console.log('Cargando fechas de proyecto');
					break;
					case '#porcentajes':
						//console.log('Cargando porcentajes de proyecto');
					break;
					case '#estatus':
						//console.log('Cargando estatus de proyecto');
					break;
					case '#empresas':
						//console.log('Cargando empresas de proyecto');
					break;
				}
			}
		}
		
		// Inicializa la lista de proyectos
		LISTA_PROYECTOS.dataURL = 'inc/lib/controllers/ListaProyectosController.php';
		LISTA_PROYECTOS.init();
		
		LISTA_PROYECTOS.onSelect = function(event) {
			TABS.enable();
			TABS.reset();
			P.cargaDatosGenerales();
		}
		
		// Handler para control de los dropdown lists
		$('a.dropdown-list-trigger').click( function(event) {
			
			var $target = $(event.target);
			var listContainer = $(this).attr('href');
			var source = '';
			
			switch(listContainer) {
				
				case '#dropdown-proyecto':
					DROP_LIST.onSelect = P.llamaOpcionesProyecto;
				break;
				case '#dropdown-tipo-proyecto':
					DROP_LIST.onSelect = P.asignaTipoProyecto;
					source = 'modulos/proyectos/GetListaTiposProyecto.php';
				break;
				case '#dropdown-empresas':
					DROP_LIST.onSelect = P.asignaEmpresa;
					source = 'modulos/proyectos/GetListaEmpresasConstructoras.php';
				break;
				case '#dropdown-estados':
					DROP_LIST.onSelect = P.asignaEstado;
					source = 'modulos/proyectos/GetListaEstados.php';
				break;
			}
			
			DROP_LIST.listContainer = listContainer;
			DROP_LIST.source = source;
			DROP_LIST.show(event);
			
			event.preventDefault();
		});
		
		// Handler para guardar los datos de los cuadros de texto editables
		// en los formularios
		$('input[type=text], textarea').blur( function(event) {
		    
			$inputField = $(this);
			
			if( $inputField.hasClass('changed') ) {

				switch( $inputField.attr('id') ) {

					case 'txtNombreProyecto':
						P.modificaNombreProyecto($inputField);
					break;
					case 'txtDescripcionProyecto':
						P.modificaDescripcionProyecto($inputField);
					break;
					case 'txtDireccionProyecto':
						P.modificaDireccionProyecto($inputField);
					break;
					case 'txtPctParticipacion':
					case 'txtPctMetaUtilidadCorp':
					case 'txtPctMetaUtilidadObra':
						P.modificaPorcentajesProyecto($inputField);
					break;
					case 'txtMontoVentaContrato':
					case 'txtMontoActualContrato':
					case 'txtMontoInicialPIO':
					case 'txtMontoActualPIO':
						P.asignaMontoProyecto($inputField);
					break;
				}
				
				$inputField.removeClass('changed');
			}
		}).change( function(event) {
			$(this).addClass('changed');
		});
		
		// Inicializacion de las ventanas de dialogo
		$('#dialog-nuevo-proyecto').dialog({
			autoOpen: false,
			modal: true,
			width: 'auto',
			resizable: false,
			buttons: {
				Aceptar: function() {
					P.registraProyecto();
				},
				
				Cancelar: function() {
					$(this).dialog('close');
				}
			}
		});
		
		$('#confirmation-dialog').dialog({
			autoOpen: false,
			modal: true,
			resizable: false,
			buttons: {
				Cancelar: function() {
					$(this).dialog('close');
				}
			}
		});
		
		// Inicializacion de datepickers
		$('#txtFechaInicio').datepicker({
			dateFormat: 'dd-mm-yy',
			altField: '#txtFechaInicioDB',
			altFormat: 'yy-mm-dd',
			showOtherMonths: "true",
			selectOtherMonths: "true",
			buttonImage: "img/app/calendar_light-green_16x16.png",
			showOn: "both",
			buttonImageOnly: true,
			onSelect: function() {
				P.modificaFechaProyecto(this);
			}
		});
		
		$('#txtFechaTermino').datepicker({
			dateFormat: 'dd-mm-yy',
			altField: '#txtFechaTerminoDB',
			altFormat: 'yy-mm-dd',
			showOtherMonths: "true",
			selectOtherMonths: "true",
			buttonImage: "img/app/calendar_light-green_16x16.png",
			showOn: "both",
			buttonImageOnly: true,
			onSelect: function() {
				P.modificaFechaProyecto(this);
			}
		});
		
		$('#txtFechaInicioContrato').datepicker({
			dateFormat: 'dd-mm-yy',
			altField: '#txtFechaInicioContratoDB',
			altFormat: 'yy-mm-dd',
			showOtherMonths: "true",
			selectOtherMonths: "true",
			buttonImage: "img/app/calendar_light-green_16x16.png",
			showOn: "both",
			buttonImageOnly: true,
			onSelect: function() {
				P.modificaFechaProyecto(this);
			}
		});
		
		$('#txtFechaTerminoContrato').datepicker({
			dateFormat: 'dd-mm-yy',
			altField: '#txtFechaTerminoContratoDB',
			altFormat: 'yy-mm-dd',
			showOtherMonths: "true",
			selectOtherMonths: "true",
			buttonImage: "img/app/calendar_light-green_16x16.png",
			showOn: "both",
			buttonImageOnly: true,
			onSelect: function() {
				P.modificaFechaProyecto(this);
			}
		});
		
		// Inicializacion de botones
		$('#chkEstaActivo, #chkVisibleReportes, #chkVisibleApps').prop('checked', false)
		.button().button('refresh').click( function() {

			P.cambiaEstatusProyecto($(this));
		});
	},
	
	llamaOpcionesProyecto: function(selectedItem) {
		var P = PROYECTOS;

		switch( parseInt(selectedItem.value) ) {
			
			case 1:
				P.showNuevoProyecto();
			break;
			case 2:
				P.showEliminaProyecto();
			break;
		}
	},
	
	showNuevoProyecto: function() {
		$('#dialog-nuevo-proyecto').dialog('open').find('[name^="txt"]').val('');;
	},
	
	registraProyecto: function() {
		
		var $dialog = $('#dialog-nuevo-proyecto');
		var $validationTips = $('p.validation-tips', $dialog);
		var highlightClass = 'ui-state-highlight';
		var errorClass = 'ui-state-error';
		
		var reNombre = /^(\w|\W){5,}$/;
		
		var $nombreProyecto = $('#txtNombreNvoProyecto');
		
		if( !reNombre.test($nombreProyecto.val()) ) {
			$validationTips.addClass(highlightClass).text('El nombre del proyecto debe incluir minimo 5 caracteres.');
			
			$nombreProyecto.addClass(errorClass).focus();
			
			return false;
		} else
			$nombreProyecto.removeClass(errorClass);
		
		$.ajax({
			type: 'POST',
			url: 'modulos/proyectos/RegistraProyecto.php',
			data: {
				nombreProyecto: $nombreProyecto.val()
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
				$(LISTA_PROYECTOS.container).append('<li><a href="#' + json.idProyecto + '">' + $nombreProyecto.val() + '</a></li>');
				LISTA_PROYECTOS.selectLast();
				
				$('#dialog-nuevo-proyecto').dialog('close');
				
				messageConsole.displayMessage('El proyecto se registro correctamente', 'success');
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		});
	},
	
	showEliminaProyecto: function() {
		var P = this;
		
		if( !LISTA_PROYECTOS.selectedItem ) {
			messageConsole.displayMessage('Debe seleccionar un proyecto para poder eliminarlo.', 'error');
			return false;
		}
		
		$('#confirmation-dialog').dialog({
			buttons: {
				Eliminar: function() {
					P.eliminaProyecto();
				},
				Cancelar: function() {
					$(this).dialog('close');
				}
			}
		})
		.children('p.confirmation-message').text('El proyecto será eliminado, desea continuar?')
		.end()
		.dialog('open');
	},
	
	eliminaProyecto: function() {
		var P = this;
		
		DATA_LOADER.show();
		
		var po = 
		$.ajax({
			type: 'POST',
			url: 'modulos/proyectos/EliminaProyecto.php',
			data: {
				idProyecto: LISTA_PROYECTOS.selectedItem.value
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
				TABS.reset();
				TABS.disable();
				P.clearDatosGenerales();
				
				LISTA_PROYECTOS.selectedItem.element.fadeOut( function() {
					$(this).remove();
				});
				
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).complete( function() {
			$('#confirmation-dialog').dialog('close');
			DATA_LOADER.hide();
		});
		
		return po;
	},
	
	clearDatosGenerales: function() {
		
		$('#form-datos-generales').find('input[type=text], textarea').val('');
	},
	
	cargaDatosGenerales: function() {
		var P = this;
		
		P.clearDatosGenerales();
		
		if( P.currentRequest )
			P.currentRequest.abort();
		
		TABS.disable(0);
		DATA_LOADER.show();
		
		P.currentRequest = 
		$.ajax({
			type: 'GET',
			url: 'modulos/proyectos/GetDatosGeneralesProyecto.php',
			data: {
				idProyecto: LISTA_PROYECTOS.selectedItem.value
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
				$('#txtNombreProyecto').val(json.Nombre);
				$('#txtDescripcionProyecto').val(json.Descripcion);
				$('#txtTipoProyecto').val(json.TipoProyecto);
				$('#txtEmpresa').val(json.Empresa);
				$('#txtEstado').val(json.Estado);
				$('#txtDireccionProyecto').val(json.Direccion);
				
				$('#chkEstaActivo').prop('checked', json.EstaActivo).button('refresh');
				$('#chkVisibleReportes').prop('checked', json.VisibleEnApps).button('refresh')
				$('#chkVisibleApps').prop('checked', json.VisibleEnReportes).button('refresh')
				
				$('#txtFechaInicio').val(json.FechaInicio);
				$('#txtFechaTermino').val(json.FechaTermino);
				$('#txtFechaInicioContrato').val(json.FechaInicioContrato);
				$('#txtFechaTerminoContrato').val(json.FechaTerminoContrato);
				
				$('#txtPctMetaUtilidadCorp').val(json.PctMetaUtilidadCorporativo);
				$('#txtPctMetaUtilidadObra').val(json.PctMetaUtilidadObra);
				$('#txtPctParticipacion').val(json.PctParticipacion);
				
				$('#txtMontoVentaContrato').val(json.MontoVentaContrato);
				$('#txtMontoActualContrato').val(json.MontoActualContrato);
				$('#txtMontoInicialPIO').val(json.MontoInicialPIO);
				$('#txtMontoActualPIO').val(json.MontoActualPIO);

			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).complete( function() {
			DATA_LOADER.hide();
			TABS.enable(0);
			P.currentRequest = null;
		});
	},
	
	modificaNombreProyecto: function(inputField) {

		DATA_LOADER.show();
		
		$.ajax({
			type: 'POST',
			url: 'modulos/proyectos/ModificaNombreProyecto.php',
			data: {
				  idProyecto: LISTA_PROYECTOS.selectedItem.value
				, NombreProyecto: inputField.val()
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
				LISTA_PROYECTOS.selectedItem.element.text(inputField.val());
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).complete( function() {
			DATA_LOADER.hide();
		});
	},
	
	modificaDescripcionProyecto: function(inputField) {

		DATA_LOADER.show();
		
		$.ajax({
			type: 'POST',
			url: 'modulos/proyectos/ModificaDescripcionProyecto.php',
			data: {
				  idProyecto: LISTA_PROYECTOS.selectedItem.value
				, Descripcion: inputField.val()
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}

			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).complete( function() {
			DATA_LOADER.hide();
		});
	},
	
	cambiaEstatusProyecto: function(inputField) {
		
		var dataURL = '';
		
		switch( inputField.attr('id') ) {
			
			case 'chkEstaActivo':
				dataURL = 'modulos/proyectos/CambiaEstatusProyecto.php';
			break;
			case 'chkVisibleReportes':
				dataURL = 'modulos/proyectos/CambiaVisibilidadReportes.php';
			break;
			case 'chkVisibleApps':
				dataURL = 'modulos/proyectos/CambiaVisibiliadApps.php'
			break;
		}
		
		var isChecked = inputField.prop('checked');
		
		$.ajax({
			type: 'POST',
			url: dataURL,
			data: {
				  idProyecto: LISTA_PROYECTOS.selectedItem.value
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).fail(function() {
			// Evita que el boton quede en el estado que se pretendia(seleccionado o no seleccionado)
			inputField.prop('checked', !isChecked);
		}).complete( function() {
			
			inputField.button('refresh');
		});
	},
	
	modificaDireccionProyecto: function(inputField) {

		DATA_LOADER.show();
		
		$.ajax({
			type: 'POST',
			url: 'modulos/proyectos/ModificaDireccionProyecto.php',
			data: {
				  idProyecto: LISTA_PROYECTOS.selectedItem.value
				, Direccion: inputField.val()
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}

			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).complete( function() {
			DATA_LOADER.hide();
		});

	},
	
	asignaTipoProyecto: function(selectedItem, trigger) {
		
		DATA_LOADER.show();
		
		$.ajax({
			type: 'POST',
			url: 'modulos/proyectos/AsignaTipoProyecto.php',
			data: {
				  idProyecto: LISTA_PROYECTOS.selectedItem.value
				, idTipoProyecto: selectedItem.value
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
				$('#txtTipoProyecto').val(selectedItem.label);
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).complete( function() {
			DATA_LOADER.hide();
		});
	},
	
	asignaEmpresa: function(selectedItem, trigger) {
		
		DATA_LOADER.show();
		
		$.ajax({
			type: 'POST',
			url: 'modulos/proyectos/AsignaEmpresa.php',
			data: {
				  idProyecto: LISTA_PROYECTOS.selectedItem.value
				, idEmpresa: selectedItem.value
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
				$('#txtEmpresa').val(selectedItem.label);
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).complete( function() {
			DATA_LOADER.hide();
		});
	},
	
	asignaEstado: function(selectedItem, trigger) {
		
		DATA_LOADER.show();
		
		$.ajax({
			type: 'POST',
			url: 'modulos/proyectos/AsignaEstado.php',
			data: {
				  idProyecto: LISTA_PROYECTOS.selectedItem.value
				, idEstado: selectedItem.value
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
				$('#txtEstado').val(selectedItem.label);
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).complete( function() {
			DATA_LOADER.hide();
		});
	},
	
	modificaFechaProyecto: function(inputField) {
		
		var dataURL = '';
		var fecha = null;
		
		switch( $(inputField).attr('id') ) {
			
			case 'txtFechaInicio':
				dataURL = 'modulos/proyectos/ModificaFechaInicioProyecto.php';
				fecha = $('#txtFechaInicioDB').val();
			break;
			case 'txtFechaTermino':
				dataURL = 'modulos/proyectos/ModificaFechaTerminoProyecto.php';
				fecha = $('#txtFechaTerminoDB').val();
			break;
			case 'txtFechaInicioContrato':
				dataURL = 'modulos/proyectos/ModificaFechaInicioContrato.php';
				fecha = $('#txtFechaInicioContratoDB').val();
			break;
			case 'txtFechaTerminoContrato':
				dataURL = 'modulos/proyectos/ModificaFechaTerminoContrato.php';
				fecha = $('#txtFechaTerminoContratoDB').val();
			break;
		}
		
		$.ajax({
			type: 'POST',
			url: dataURL,
			data: {
				  idProyecto: LISTA_PROYECTOS.selectedItem.value
				, Fecha: fecha
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		});
	},
	
	modificaPorcentajesProyecto: function(inputField) {
		
		var dataURL = '';
		var fieldId = '#' + $(inputField).attr('id');
		var pct = $(fieldId).val();
		
		switch( $(inputField).attr('id') ) {
			
			case 'txtPctParticipacion':
				dataURL = 'modulos/proyectos/ModificaPctParticipacion.php';
			break;
			case 'txtPctMetaUtilidadCorp':
				dataURL = 'modulos/proyectos/ModificaPctUtilCorp.php';
			break;
			case 'txtPctMetaUtilidadObra':
				dataURL = 'modulos/proyectos/ModificaPctUtilObra.php';
			break;
		}
		
		$.ajax({
			type: 'POST',
			url: dataURL,
			data: {
				  idProyecto: LISTA_PROYECTOS.selectedItem.value
				, Pct: pct
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		});		
	},
	
	asignaMontoProyecto: function(inputField) {
		
		var dataURL = '';
		
		switch( inputField.attr('id') ) {
			
			case 'txtMontoVentaContrato':
				dataURL = 'modulos/proyectos/AsignaMontoVentaContrato.php';
			break;
			case 'txtMontoActualContrato':
				dataURL = 'modulos/proyectos/AsignaMontoActualContrato.php';
			break;
			case 'txtMontoInicialPIO':
				dataURL = 'modulos/proyectos/AsignaMontoInicialPIO.php';
			break;
			case 'txtMontoActualPIO':
				dataURL = 'modulos/proyectos/AsignaMontoActualPIO.php';
			break;
		}
		
		$.ajax({
			type: 'POST',
			url: dataURL,
			data: {
				  idProyecto: LISTA_PROYECTOS.selectedItem.value
				, Monto: inputField.val()
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		});
	}
	
}