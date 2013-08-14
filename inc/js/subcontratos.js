$( function() {
	
	SUBCONTRATOS.init();
});

var SUBCONTRATOS = {
	
	listaSubcontratos: null,
	currentRequest: null,
	
	init: function() {
		var S = this;
		
		// Inicializacion de TABS
		TABS.container = '#info-subcontrato';
		TABS.init();
		TABS.disable();
		TABS.onSelect = function(event, tab) {
			
			// Previene que se cargue la informacion el numero de veces
			// que se le de a un tab seleccionado
			// solo al cambiar de tab se cargara la informacion
			if( ! TABS.isSelected(tab) ) {
				switch(tab.attr('href')) {
					
					case '#general':
						S.cargaDatosGenerales();
					break;
					case '#addendums':
						S.cargaAddendums();
				}
			}
		}
		
		// inicializacion del arbol de subcontratos
		S.listaSubcontratos = new TreeViewList('#lista-subcontratos');
		
		S.listaSubcontratos.fill = function() {
			var T = this;
			
			// Carga la lista de arbol de los subcontratos
			$.ajax({
				type: 'GET',
				url: 'modulos/subcontratos/GetListaSubcontratos.php',
				data: {},
				dataType: 'json',
				cache: false,
				timeout: 60000
			}).success( function(json) {
				try {
					
					if( !json.success ) {
						messageConsole.displayMessage(json.errorMessage, 'error');
						return false;
					}
					
					if( json.noRows ) {
						messageConsole.displayMessage(json.noRowsMessage, 'error');
						return false;
					}
					
					var content = '';
					$.each(json.Subcontratos.Proyectos, function() {
						
						content += '<li><span class="handle closed"></span><a class="text">' + this.Proyecto + '</a>';
						
						if( this.Contratistas.length )
							content += '<ul>';
						
						$.each(this.Contratistas, function() {
							
							content += '<li><span class="handle closed"></span><a class="text">' + this.EmpresaContratista + '</a>';
							
							if( this.Subcontratos.length )
								content += '<ul>';
									
							$.each( this.Subcontratos, function() {
								
								content += '<li><span class="icon"></span><a class="text selectable" data-id="' + this.idSubcontrato + '">' + this.NombreSubcontrato + '</a>';
							});
							
							if( this.Subcontratos.length )
								content += '</ul>';

							content += '</li>';
						});
						
						if( this.Contratistas.length )
							content += '</ul>';

						content += '</li>';
					});
					
					$(T.container).empty().html(content);
					
				} catch(e) {
					messageConsole.displayMessage('Error: ' + e.message, 'error');
				}
			});
		}
		
		S.listaSubcontratos.onSelectNode = function() {

			TABS.reset();
			S.cargaDatosGenerales();
		}
		
		S.listaSubcontratos.fill();

		
		// Handler para control de los dropdown lists
		$('a.dropdown-list-trigger').click( function(event) {
			
			var $target = $(event.target);
			var listContainer = $(this).attr('href');
			var source = '';
			
			switch(listContainer) {
				
				case '#dropdown-clasificacion':
					DROP_LIST.onSelect = S.asignaClasificacionSubcontrato;
					source = 'modulos/subcontratos/GetClasificadores.php';
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
					
					case 'txtDescripcionSubcontrato':
						S.modificaDescripcionSubcontrato($inputField);
					break;
					case 'txtMontoVentaCliente':
					case 'txtMontoVentaActualCliente':
					case 'txtMontoInicialPIO':
					case 'txtMontoActualPIO':
					case 'txtMontoSubcontrato':
					case 'txtMontoAnticipo':
					case 'txtPctFG':
						S.asignaMontoSubcontrato($inputField);
					break;
				}
				
				$inputField.removeClass('changed');
			}
		}).change( function(event) {
			$(this).addClass('changed');
		});
		
		// Event handler para clics en los iconos de accion de las tablas
		$('table').click( function(event) {
			
			$tgt = $(event.target);
			
			if( $tgt.hasClass('icon') ) {
				
				if( $tgt.hasClass('delete') ) {
					
					// Determina que accion se tomara dependiendo la tabla
					// en la que se dio clic
					switch( this.id ) {
						
						case 'addendums-registrados':
							S.confirmaEliminaAddendum($tgt);
						break;
					}
				}
			}
		});
		
		// Event handler para clicks en las opciones de los table-toolbars
		$('div.table-toolbar .options').click( function(event) {
			
			var $tgt = $(event.target);
			
			if( $tgt.is('a') ) {

				switch( $tgt[0].id ) {
					
					case 'nuevo-addendum':
						S.showNuevoAddendum();
					break;
				}
			}
		});
		
		// Inicializacion de datepickers
		$('#txtFechaInicioCliente,'
		+ '#txtFechaTerminoCliente,'
		+ '#txtFechaInicioProyecto,'
		+ '#txtFechaTerminoProyecto,'
		+ '#txtFechaInicioContratista,'
		+ '#txtFechaTerminoContratista')
		.datepicker({
			dateFormat: 'dd-mm-yy',
			altFormat: 'yy-mm-dd',
			showOtherMonths: "true",
			selectOtherMonths: "true",
			buttonImage: "img/app/calendar_light-green_16x16.png",
			showOn: "both",
			buttonImageOnly: true,
			onSelect: function() {
				S.modificaFechaSubcontrato(this);
			}
		});
		
		$('#txtFechaInicioCliente')
		 .datepicker('option', 'altField', '#txtFechaInicioClienteDB');
		 
		$('#txtFechaTerminoCliente')
		 .datepicker('option', 'altField', '#txtFechaTerminoClienteDB');
		
		$('#txtFechaInicioProyecto')
		 .datepicker('option', 'altField', '#txtFechaInicioProyectoDB');
		 
		$('#txtFechaTerminoProyecto')
		 .datepicker('option', 'altField', '#txtFechaTerminoProyectoDB');

		$('#txtFechaInicioContratista')
		 .datepicker('option', 'altField', '#txtFechaInicioContratistaDB');

		$('#txtFechaTerminoContratista')
		 .datepicker('option', 'altField', '#txtFechaTerminoContratistaDB');

		$('#txtFechaAddendum').datepicker({
			dateFormat: 'dd-mm-yy',
			altField: '#txtFechaAddendumDB',
			altFormat: 'yy-mm-dd',
			showOtherMonths: "true",
			selectOtherMonths: "true",
			buttonImage: "img/app/calendar_light-green_16x16.png",
			showOn: "both",
			buttonImageOnly: true
		});

		$('#confirmation-dialog').dialog({
			autoOpen: false,
			modal: true,
			resizable: false
		});

		$('#dialog-nuevo-addendum').dialog({
			autoOpen: false,
			modal: true,
			resizable: false,
			width: 'auto',
			closeOnEscape: false,
			buttons: {
				Aceptar: function() {
					S.registraAddendum();
				},
				Cancelar: function() {
					$(this).dialog('close');
				}
			}
		});
	},
	
	clearDatosGenerales: function() {
		$('#general').find('input[type=text], textarea').val('');
	},
	
	cargaDatosGenerales: function() {
		
		var S = this;
		
		S.clearDatosGenerales();
		
		if( S.currentRequest )
			S.currentRequest.abort();
		
		TABS.disable();
		DATA_LOADER.show();
		
		S.currentRequest = 
		$.ajax({
			type: 'GET',
			url: 'modulos/subcontratos/GetDatosSubcontrato.php',
			data: {
				idSubcontrato: S.listaSubcontratos.selectedNode.value
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
				
				$('#txtNombreSubcontrato').val(json.Subcontrato);
				$('#txtDescripcionSubcontrato').val(json.Descripcion);
				$('#txtTipoContrato').val(json.TipoContrato);
				$('#txtContratista').val(json.EmpresaContratista);
				
				$('#txtMontoSubcontrato').val(json.MontoSubcontrato);
				$('#txtMontoAnticipo').val(json.MontoAnticipo);
				$('#txtPctFG').val(json.PctRetencionFG);
				
				$('#txtClasificacion').val(json.Clasificador);
				
				$('#txtFechaInicioCliente').val(json.FechaInicioCliente);
				$('#txtFechaTerminoCliente').val(json.FechaTerminoCliente);
				$('#txtFechaInicioProyecto').val(json.FechaInicioProyecto);
				$('#txtFechaTerminoProyecto').val(json.FechaTerminoProyecto);
				$('#txtFechaInicioContratista').val(json.FechaInicioContratista);
				$('#txtFechaTerminoContratista').val(json.FechaTerminoContratista);
				
				$('#txtMontoVentaCliente').val(json.MontoVentaCliente);
				$('#txtMontoVentaActualCliente').val(json.MontoVentaActualCliente);
				$('#txtMontoInicialPIO').val(json.MontoInicialPIO);
				$('#txtMontoActualPIO').val(json.MontoActualPIO);

			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).complete( function() {
			TABS.enable();
			DATA_LOADER.hide();
			S.currentRequest = null;
		});
	},
	
	asignaClasificacionSubcontrato: function(selectedItem, event) {
		
		var S = SUBCONTRATOS;
		
		$.ajax({
			type: 'POST',
			url: 'modulos/subcontratos/AsignaClasificacionSubcontrato.php',
			data: {
				  idSubcontrato: S.listaSubcontratos.selectedNode.value
				, idClasificador: selectedItem.value
			},
			dataType: 'json',
			cache: false,
			timeout: false
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					messageConsole.displayMessage(json.errorMessage, 'error');
					input.removeAttr('checked').button('refresh');
					return false;
				}
				
				$('#txtClasificacion').val(selectedItem.label)
				
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		});
	},
	
	modificaDescripcionSubcontrato: function(inputField) {
		
		var S = this;
		
		$.ajax({
			type: 'POST',
			url: 'modulos/subcontratos/ModificaDescripcionSubcontrato.php',
			data: {
				  idSubcontrato: S.listaSubcontratos.selectedNode.value
				, Descripcion: $(inputField).val()
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
	
	modificaFechaSubcontrato: function(inputField) {
		
		var S = this;
		var dataURL = '';
		var fecha = null;
		
		switch( $(inputField).attr('id') ) {
			
			case 'txtFechaInicioCliente':
				dataURL = 'modulos/subcontratos/AsignaFechaInicioCliente.php';
				fecha = $('#txtFechaInicioClienteDB').val();
			break;
			case 'txtFechaTerminoCliente':
				dataURL = 'modulos/subcontratos/AsignaFechaTerminoCliente.php';
				fecha = $('#txtFechaTerminoClienteDB').val();
			break;
			case 'txtFechaInicioProyecto':
				dataURL = 'modulos/subcontratos/AsignaFechaInicioProyecto.php';
				fecha = $('#txtFechaInicioProyectoDB').val();
			break;
			case 'txtFechaTerminoProyecto':
				dataURL = 'modulos/subcontratos/AsignaFechaTerminoProyecto.php';
				fecha = $('#txtFechaTerminoProyectoDB').val();
			break;
			case 'txtFechaInicioContratista':
				dataURL = 'modulos/subcontratos/AsignaFechaInicioContratista.php';
				fecha = $('#txtFechaInicioContratistaDB').val();
			break;
			case 'txtFechaTerminoContratista':
				dataURL = 'modulos/subcontratos/AsignaFechaTerminoContratista.php';
				fecha = $('#txtFechaTerminoContratistaDB').val();
			break;
		}
		
		$.ajax({
			type: 'POST',
			url: dataURL,
			data: {
				  idSubcontrato: S.listaSubcontratos.selectedNode.value
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
	
	asignaMontoSubcontrato: function(inputField) {

		var S = this;
		var dataURL = '';
		
		switch( inputField.attr('id') ) {
			
			case 'txtMontoVentaCliente':
				dataURL = 'modulos/subcontratos/AsignaMontoVentaCliente.php';
			break;
			case 'txtMontoVentaActualCliente':
				dataURL = 'modulos/subcontratos/AsignaMontoVentaActualCliente.php';
			break;
			case 'txtMontoInicialPIO':
				dataURL = 'modulos/subcontratos/AsignaMontoInicialPIO.php';
			break;
			case 'txtMontoActualPIO':
				dataURL = 'modulos/subcontratos/AsignaMontoActualPIO.php';
			break;
			case 'txtMontoSubcontrato':
				dataURL = 'modulos/subcontratos/AsignaMontoSubcontrato.php';
			break;
			case 'txtMontoAnticipo':
				dataURL = 'modulos/subcontratos/AsignaMontoAnticipo.php';
			break;
			case 'txtPctFG':
				dataURL = 'modulos/subcontratos/AsignaPctRetencionFG.php';
			break;
		}
		
		$.ajax({
			type: 'POST',
			url: dataURL,
			data: {
				  idSubcontrato: S.listaSubcontratos.selectedNode.value
				, Monto: inputField.val()
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					
					if( !json.isDataValid ) {
						messageConsole.displayMessage(json.errorMessage, 'error');
						inputField.focus();
						return false;
					}
					
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		});
	},
	
	clearAddendums: function() {
		$('#addendums-registrados tbody').empty();
	},
	
	cargaAddendums: function() {
		
		var S = this;
		
		S.clearAddendums();
		
		if( S.currentRequest )
			S.currentRequest.abort();
		
		TABS.disable();
		DATA_LOADER.show();
		
		$.ajax({
			type: 'GET',
			url: 'modulos/subcontratos/GetAddendums.php',
			data: {
				idSubcontrato: S.listaSubcontratos.selectedNode.value
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
				
				if( json.noRows ) {
					$('#addendums-registrados tbody')
					  .append('<tr class="no-rows"><td colspan="5">' + json.noRowsMessage + '</td></tr>');
					return false;
				}
				
				// Llenar la tabla de addendums
				var content = '';
				
				$.each(json.Addendums, function() {
					
					content += '<tr data-id="' + this.idAddendum + '">'
							+    '<td class="icon-cell">'
							+      '<span class="icon action delete"></span>'
							+    '</td>'
							+    '<td class="centrado">' + this.Fecha + '</td>'
							+    '<td class="numerico">' + this.Monto + '</td>'
							+    '<td class="numerico">' + this.MontoAnticipo + '</td>'
							+    '<td class="centrado">' + this.PctRetencionFG + '</td>'
							+  '</tr>';
				});
				
				$('#addendums-registrados tbody').append(content);
				
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).fail( function() {
			
		}).complete( function() {
			TABS.enable();
			DATA_LOADER.hide();
			S.currentRequest = null;
		});
	},
	
	showNuevoAddendum: function() {
		$('#dialog-nuevo-addendum').dialog('open').find('p.validation-tips').text('').removeClass('ui-state-highlight');
	},
	
	registraAddendum: function() {
		
		var S = this;
		
		var $dialog = $('#dialog-nuevo-addendum');
		
		var $validationTips = $dialog.find('.validation-tips');
		
		$.ajax({
			type: 'POST',
			url: 'modulos/subcontratos/RegistraAddendum.php',
			data: {
				  idSubcontrato: S.listaSubcontratos.selectedNode.value
				, Fecha: $('#txtFechaAddendumDB').val()
				, Monto: $('#txtMontoAddendum').val()
				, MontoAnticipo: $('#txtAnticipoAddendum').val()
				, PctRetFG: $('#txtRetencionFG').val()
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					
					if( !json.isDataValid ) {
						$validationTips.text(json.errorMessage).addClass('ui-state-highlight');
						return false;
					}
					
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
				var  row = '<tr data-id="' + json.idAddendum + '">'
						 +   '<td class="icon-cell">'
						 +     '<span class="icon action delete"></span>'
						 +   '</td>'
						 +   '<td class="centrado">' + $('#txtFechaAddendum').val() + '</td>'
						 +   '<td class="numerico">' + $('#txtMontoAddendum').val() + '</td>'
						 +   '<td class="numerico">' + $('#txtAnticipoAddendum').val() + '</td>'
						 +   '<td class="centrado">' + $('#txtRetencionFG').val() + '</td>'
						 + '</tr>';
				
				$('#addendums-registrados tbody').append(row);
				
				$dialog.dialog('close');
				
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		});
	}, 
	
	confirmaEliminaAddendum: function(tgt) {
		
		var S = this;
		
		$('#confirmation-dialog').dialog({
			buttons: {
				Eliminar: function() {
					S.eliminaAddendum(tgt);
				},
				Cancelar: function() {
					$(this).dialog('close');
				}
			}
		})
		.children('p.confirmation-message').text('El addendum será eliminado, desea continuar?')
		.end()
		.dialog('open');
	},
	
	eliminaAddendum: function(tgt) {
		
		DATA_LOADER.show();
		
		var tableRow = tgt.parents('tr');
		
		$.ajax({
			type: 'POST',
			url: 'modulos/subcontratos/EliminaAddendum.php',
			data: {
				idAddendum: tableRow.attr('data-id')
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
				
				tableRow.remove();
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).complete( function() {
			$('#confirmation-dialog').dialog('close');
			DATA_LOADER.hide();
		});
	}
}