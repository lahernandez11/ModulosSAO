$( function() {
	
	SUBCONTRATOS.init();
});

var SUBCONTRATOS = {
	
	listaSubcontratos: null,
	currentRequest: null,
	obraItemTemplate: null,
	empresaItemTemplate: null,
	subcontratoItemTemplate: null,
	addendumTemplate: null,
	url: 'inc/lib/controllers/SubcontratoController.php',
	
	init: function() {
		var that = this;
		
		this.obraItemTemplate = _.template($('#obra-item-template').html());
		this.empresaItemTemplate = _.template($('#empresa-item-template').html());
		this.subcontratoItemTemplate = _.template($('#subcontrato-item-template').html());
		this.addendumTemplate = _.template($('#addendum-template').html());

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
						that.cargaDatosGenerales();
					break;
					case '#addendums':
						that.cargaAddendums();
				}
			}
		}

		$('#lista-subcontratos').on('click', '.handle', function(event) {
			event.preventDefault();
			that.toggleNode( $(this).next('a') );
		});

		$('#lista-subcontratos').on('click', '.selectable', function(event) {
			event.preventDefault();
			$('#lista-subcontratos').find('.text.selected').removeClass('selected');
			$(this).addClass('selected');
			that.cargaDatosGenerales( $(this) );
		});

		$.ajax({
			url: 'inc/lib/controllers/ListaObrasController.php',
			data: { action: 'getListaProyectos' },
			dataType: 'json'
		}).done( function(data) {
			that.renderObras(data.options);
		});
		
		// Handler para control de los dropdown lists
		$('a.dropdown-list-trigger').click( function(event) {
			
			var $target = $(event.target);
			var listContainer = $(this).attr('href');
			var source = '';
			
			switch(listContainer) {
				
				case '#dropdown-clasificacion':
					DROP_LIST.onSelect = that.setClasificador;
					source = that.url;
				break;
			}
			
			DROP_LIST.listContainer = listContainer;
			DROP_LIST.data.base_datos = that.getBaseDatos();
			DROP_LIST.data.id_obra = that.getIDObra();
			DROP_LIST.data.action = 'getClasificadores';
			DROP_LIST.source = source;
			DROP_LIST.show(event);
			
			event.preventDefault();
		});
		
		// Handler para guardar los datos de los cuadros de texto editables
		// en los formularios
		$('#info-subcontrato input[type=text], textarea').blur( function(event) {
		    
			$inputField = $(this);
			
			if( $inputField.hasClass('changed') ) {

				that.guardaTransaccion( $inputField );
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
							that.confirmaEliminaAddendum($tgt);
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
						that.showNuevoAddendum();
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
				that.guardaTransaccion( $(this) );
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
					that.registraAddendum();
				},
				Cancelar: function() {
					$(this).dialog('close');
				}
			}
		});
	},

	getSelected: function() {
		return $('li.transaccion a.selected');
	},

	getIDTransaccion: function() {
		var selected = this.getSelected();
		return selected.attr('data-id');
	},

	getBaseDatos: function() {
		var selected = this.getSelected();
		return selected.parents('li.obra').children('a').attr('data-basedatos');
	},

	getIDObra: function() {
		var selected = this.getSelected();
		return selected.parents('li.obra').children('a').attr('data-id');
	},

	renderObras: function( obras ) {
		var html = '';

		for (var i = 0; i < obras.length; i++) {
			html += this.obraItemTemplate(obras[i]);
		}

		$('#lista-subcontratos').html(html);
	},

	renderEmpresas: function( data, $item ) {
		var html = '';

		for (var i = 0; i < data.empresas.length; i++) {
			html += this.empresaItemTemplate(data.empresas[i]);
		}

		if ( data.empresas.length ) {
			$item.after('<ul>');
			$item.next('ul').html(html);
			$item.next('ul').css('display', 'block');
		}
	},

	renderTransacciones: function( data, $item ) {
		var html = '';

		for (var i = 0; i < data.transacciones.length; i++) {
			html += this.subcontratoItemTemplate(data.transacciones[i]);
		}

		if ( data.transacciones.length ) {
			$item.after('<ul>');
			$item.next('ul').html(html);
			$item.next('ul').css('display', 'block');
		}
	},

	toggleNode: function( $item ) {
		// muestra los descendientes de un item
		if ( $item.prev('.handle').hasClass('closed') ) {
			this.loadItems( $item );
		} else {
			this.closeNode( $item );
		}
	},

	closeNode: function( $item ) {
		$item.next('ul').remove();
		$item.prev('.handle').toggleClass('closed opened');
	},

	loadItems: function( $item ) {

		var that = this,
			callback = null,
			requestData = { url: that.url };

		if ( $item.parent('li').hasClass('obra') ) {
			callback 			   = this.renderEmpresas;
			requestData.base_datos = $item.attr('data-basedatos');
			requestData.id_obra    = $item.attr('data-id');
			requestData.action     = 'getEmpresas';
		} else if ( $item.parent('li').hasClass('empresa') ) {
			callback 			   = this.renderTransacciones;
			requestData.base_datos = $item.parents('li.obra').children('a').attr('data-basedatos');
			requestData.id_obra    = $item.parents('li.obra').children('a').attr('data-id');
			requestData.id_empresa = $item.attr('data-id');
			requestData.action     = 'getListaSubcontratos';
		}

		DATA_LOADER.show();

		$.ajax({
			url: that.url,
			data: requestData,
			dataType: 'json'
		})
		.done( function( data ) {
			callback.call(that, data, $item);
			$item.prev('.handle').toggleClass('opened closed');
		})
		.always( DATA_LOADER.hide );
	},
	
	clearDatosGenerales: function() {
		$('#general').find('input[type=text], textarea').val('');
	},
	
	cargaDatosGenerales: function( $item ) {
		
		var that = this;
		
		that.clearDatosGenerales();
		that.clearAddendums();
		
		TABS.disable();
		DATA_LOADER.show();
		
		$.ajax({
			url: that.url,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIDObra(),
				id_transaccion: that.getIDTransaccion(),
				action: 'getDatosSubcontrato'
			},
			dataType: 'json'
		})
		.done( function(data) {
			try {
				
				if ( ! data.success ) {
					messageConsole.displayMessage(data.errorMessage, 'error');
					return false;
				}

				that.setDatosSubcontrato(data.subcontrato);
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		})
		.always( function() {
			TABS.enable();
			DATA_LOADER.hide();
		});
	},

	setDatosSubcontrato: function( subcontrato ) {
		$('#txtReferenciaSubcontrato').val(subcontrato.referencia);
		$('#txtDescripcionSubcontrato').val(subcontrato.descripcion);
		$('#txtTipoContrato').val(subcontrato.tipo_contrato);
		$('#txtContratista').val(subcontrato.empresa);
		$('#txtMontoSubcontrato').val(subcontrato.monto_subcontrato);
		$('#txtMontoAnticipo').val(subcontrato.monto_anticipo);
		$('#txtPctFG').val(subcontrato.porcentaje_retencion_fg);
		$('#txtClasificacion').val(subcontrato.clasificador);
		$('#txtFechaInicioCliente').val(subcontrato.fecha_inicio_cliente);
		$('#txtFechaTerminoCliente').val(subcontrato.fecha_termino_cliente);
		$('#txtFechaInicioProyecto').val(subcontrato.fecha_inicio_proyecto);
		$('#txtFechaTerminoProyecto').val(subcontrato.fecha_termino_proyecto);
		$('#txtFechaInicioContratista').val(subcontrato.fecha_inicio_contratista);
		$('#txtFechaTerminoContratista').val(subcontrato.fecha_termino_contratista);
		$('#txtMontoVentaCliente').val(subcontrato.monto_venta_cliente);
		$('#txtMontoVentaActualCliente').val(subcontrato.monto_venta_actual_cliente);
		$('#txtMontoInicialPIO').val(subcontrato.monto_inicial_pio);
		$('#txtMontoActualPIO').val(subcontrato.monto_actual_pio);
	},
	
	setClasificador: function(selectedItem, event) {
		
		var that = SUBCONTRATOS;
		
		$.ajax({
			type: 'POST',
			url: that.url,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIDObra(),
				id_transaccion: that.getIDTransaccion(),
				id_clasificador: selectedItem.value,
				action: 'setClasificador'
			},
			dataType: 'json'
		})
		.done( function(json) {
			try {
				
				if ( ! json.success ) {
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

	guardaTransaccion: function( $input ) {
		var that = this;

		DATA_LOADER.show();

		$.ajax({
			type: 'POST',
			url: that.url,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIDObra(),
				id_transaccion: that.getIDTransaccion(),
				descripcion: $('#txtDescripcionSubcontrato').val(),
				monto_subcontrato: $('#txtMontoSubcontrato').val(),
				monto_anticipo: $('#txtMontoAnticipo').val(),
				porcentaje_retencion_fg: $('#txtPctFG').val(),
				fecha_inicio_cliente: $('#txtFechaInicioClienteDB').val(),
				fecha_termino_cliente: $('#txtFechaTerminoClienteDB').val(),
				fecha_inicio_proyecto: $('#txtFechaInicioProyectoDB').val(),
				fecha_termino_proyecto: $('#txtFechaTerminoProyectoDB').val(),
				fecha_inicio_contratista: $('#txtFechaInicioContratistaDB').val(),
				fecha_termino_contratista: $('#txtFechaTerminoContratistaDB').val(),
				monto_venta_cliente: $('#txtMontoVentaCliente').val(),
				monto_venta_actual_cliente: $('#txtMontoVentaActualCliente').val(),
				monto_inicial_pio: $('#txtMontoInicialPIO').val(),
				monto_actual_pio: $('#txtMontoActualPIO').val(),
				action: 'guardaTransaccion'
			},
			dataType: 'json'
		}).done( function( data ) {
			$input.removeClass('changed');
		}).always( DATA_LOADER.hide );
	},
	
	modificaDescripcionSubcontrato: function(inputField) {
		
		var that = this;
		
		$.ajax({
			type: 'POST',
			url: that.url,
			data: {
				  idSubcontrato: that.listaSubcontratos.selectedNode.value
				, Descripcion: $(inputField).val()
			},
			dataType: 'json'
		})
		.done( function(json) {
			try {
				
				if ( ! json.success ) {
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
		
		var that = this;
		
		that.clearAddendums();
		
		TABS.disable();
		DATA_LOADER.show();
		
		$.ajax({
			url: that.url,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIDObra(),
				id_transaccion: that.getIDTransaccion(),
				action: 'getAddendums'
			},
			dataType: 'json'
		})
		.done( function(data) {
			try {
				
				if ( ! data.success ) {
					messageConsole.displayMessage(data.errorMessage, 'error');
					return false;
				}
				
				if( data.noRows ) {
					$('#addendums-registrados tbody')
					  .append('<tr class="no-rows"><td colspan="5">' + data.noRowsMessage + '</td></tr>');
					return false;
				}
				
				// Llenar la tabla de addendums
				var content = '';
				
				that.renderAddendumList(data.addendums);

			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		})
		.always( function() {
			TABS.enable();
			DATA_LOADER.hide();
		});
	},

	renderAddendumList: function( data ) {
		var html = '';

		for ( var i = 0; i < data.length; i++ ) {
			html += this.addendumTemplate(data[i]);
		}

		$('#addendums-registrados tbody').html(html);
	},
	
	showNuevoAddendum: function() {
		$('#dialog-nuevo-addendum').dialog('open').find('p.validation-tips').text('').removeClass('ui-state-highlight');
	},
	
	registraAddendum: function() {
		
		var that = this;
		
		var $dialog = $('#dialog-nuevo-addendum');
		
		var $validationTips = $dialog.find('.validation-tips');
		
		$.ajax({
			type: 'POST',
			url: that.url,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIDObra(),
				id_transaccion: that.getIDTransaccion(),
				fecha: $('#txtFechaAddendumDB').val(),
				monto: $('#txtMontoAddendum').val(),
				monto_anticipo: $('#txtAnticipoAddendum').val(),
				porcentaje_retencion_fg: $('#txtRetencionFG').val(),
				action: 'addAddendum'
			},
			dataType: 'json'
		})
		.done( function(data) {
			try {
				
				if ( ! data.success ) {
					
					if ( ! data.isDataValid ) {
						$validationTips.text(data.message).addClass('ui-state-highlight');
						return false;
					}
					
					messageConsole.displayMessage(data.errorMessage, 'error');
					return false;
				}

				$('#addendums-registrados tbody').append(that.addendumTemplate(data.addendum));
				
				$dialog.dialog('close');
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		});
	}, 
	
	confirmaEliminaAddendum: function(tgt) {
		
		var that = this;
		
		$('#confirmation-dialog').dialog({
			buttons: {
				Eliminar: function() {
					that.eliminaAddendum(tgt);
				},
				Cancelar: function() {
					$(this).dialog('close');
				}
			}
		})
		.children('p.confirmation-message')
		.text('El addendum será eliminado, desea continuar?').end().dialog('open');
	},
	
	eliminaAddendum: function(tgt) {
		
		var that = this,
			tableRow = tgt.parents('tr');

		DATA_LOADER.show();
		
		$.ajax({
			type: 'POST',
			url: that.url,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIDObra(),
				id_transaccion: that.getIDTransaccion(),
				id_addendum: tableRow.attr('data-id'),
				action: 'deleteAddendum'
			},
			dataType: 'json'
		})
		.done( function(data) {
			try {
				
				if ( ! data.success ) {
					messageConsole.displayMessage(data.message, 'error');
					return false;
				}
				
				tableRow.remove();
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		})
		.always( function() {
			$('#confirmation-dialog').dialog('close');
			DATA_LOADER.hide();
		});
	}
}