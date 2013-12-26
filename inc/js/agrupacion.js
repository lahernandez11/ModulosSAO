var AGRUPACION = {

	insumoController: 'inc/lib/controllers/AgrupacionInsumoController.php',
	subcontratoController: 'inc/lib/controllers/AgrupacionSubcontratoController.php',
	gastosVariosController: 'inc/lib/controllers/AgrupacionGastosVariosController.php',
	agrupadorController: 'inc/lib/controllers/AgrupadorInsumoController.php',
	container: '#agrupacion',
	dataContainer: '#conceptos',
	currentRequest: null,
	insumosTemplate: null,
	subcontratosTemplate: null,
	gastosTemplate: null,
	subcontratosTemplate: null,
	requestType: {
		INSUMO: 'consulta-insumos',
		SUBCONTRATO: 'consulta-subcontrato',
		GASTOS: 'consulta-varios'
	},
	
	init: function() {
		
		var that = this;
		
		this.insumosTemplate = _.template($('#template-insumo').html());
		this.subcontratosTemplate = _.template($('#template-subcontrato').html());
		this.gastosTemplate = _.template($('#template-gastos').html());

		$('.actions').on('click', '.button', function(event) {
			event.preventDefault();

			if ( !that.getIDProyecto() ){
				messageConsole.displayMessage('Debe seleccionar un proyecto.', 'info');
			} else {
				that.clearDataContainer();
				that.loadData(this.id);
			}
		});
		
		// Inicializa la lista de proyectos
		$('#bl-proyectos').buttonlist({
			source: 'inc/lib/controllers/ListaProyectosController.php',
			data: {action: 'getListaProyectos'},
			onSelect: function( selectedItem, listItem ) {
				that.clearDataContainer();
			},
			didNotDataFound: function() {
				$.notify({text: 'No se pudo cargar la lista de proyectos'});
			},
			onCreateListItem: function() {
				return {
					id: this.IDProyecto,
					value: this.NombreProyecto
				}
			}
		});
		
		$(that.dataContainer).click( function(event) {
			event.preventDefault();

			var $tgt = $(event.target);
			
			// Bloque que controla la expansion de las secciones
			if( $tgt.parents('.content-toggler').length || $tgt.hasClass('content-toggler') ) {
				
				var $toggler;
				
				if( $tgt.hasClass('content-toggler') )
					$toggler = $tgt;
				else
					$toggler = $tgt.parents('.content-toggler');
					
				$toggler.toggleClass('expanded');
				
			    $toggler.parent().next().slideToggle();
		    }
		    
			// Handler para control de los dropdown lists
			if( $tgt.is('a.dropdown-list-trigger') ) {

				var listContainer = $tgt.attr('href');
				var source = '';
				
				switch( listContainer ) {
					
					case '#dropdown-naturaleza':
						source = that.agrupadorController;
						action = 'getAgrupadoresNaturaleza';
					break;
					case '#dropdown-familia':
						source = that.agrupadorController;
						action = 'getAgrupadoresFamilia';
					break;
					case '#dropdown-insumo-generico':
						source = that.agrupadorController;
						action = 'getAgrupadoresGenerico';
					break;
				}
				
				DROP_LIST.onSelect = that.asignaAgrupador;
				DROP_LIST.data = {action: action};
				DROP_LIST.listContainer = listContainer;
				DROP_LIST.source = source;
				DROP_LIST.show(event);
			}
		});
		
		that.resetToolbar();
		
		// Handler para los botones del toolbar
		$('#radios-visibilidad, #radios-expansion').buttonset()
		 
		$('#radios-expansion input').click( function(event) {
		 	
		 	switch( this.id ) {
		 		
		 		case 'rd-expand-all':
		 			$(that.dataContainer).find('.section:visible .section-content').show().prev().children().addClass('expanded');
		 		break;
		 		
		 		case 'rd-collapse-all':
		 			$(that.dataContainer).find('.section:visible .section-content').hide().prev().children().removeClass('expanded');
		 		break;
		 	}
	 	 });
	 	 
	 	 $('#radios-visibilidad input').click( function(event) {
	 	 	
		 	var hiddenClassName = 'hidden';

		    $(that.dataContainer + ' tr').removeClass(hiddenClassName).show();
		 	
		 	switch( this.id ) {
		 		case 'rd-show-sin-naturaleza':
		 			$(that.dataContainer).find('tr td:nth-child(3)').not(':empty').parent().addClass('hidden').hide();
		 		break;
		 		case 'rd-show-sin-familia':
		 			$(that.dataContainer).find('tr td:nth-child(5):not(:empty)').parent().addClass('hidden').hide();
		 		break;
		 		case 'rd-show-sin-insumo-generico':
		 			$(that.dataContainer).find('tr td:nth-child(7):not(:empty)').parent().addClass('hidden').hide();
		 		break;
		 	}

		 	that.cuentaFilas();
		 	
		 	// $(that.dataContainer + ' .section').removeClass(hiddenClassName).show().each( function() {
	   //  		var totalDocs = parseInt($(this).find('.item-count').text());
	    		
	   //  		if( totalDocs === 0 ) {
	   //  			$(this).addClass(hiddenClassName).hide();
	   //  		}
	   //  	});
	 	 });
	 	 
		that.disableToolbar();
	},

	getIDProyecto: function() {
		return $('#bl-proyectos').buttonlist('option', 'selectedItem').value;
	},
	
	loadData: function(tipo) {
		
		var that = this;

		DATA_LOADER.show();
		
		var dataURL = null,
			action = '',
			callback = null;
		
		switch( tipo ) {
			
			case that.requestType.INSUMO:
				dataURL = that.insumoController;
				action = 'getMateriales';
				callback = that.renderInsumos;
			break;
			case that.requestType.SUBCONTRATO:
				dataURL = that.subcontratoController;
				action = 'getSubcontratos';
				callback = that.renderSubcontratos;
			break;
			case that.requestType.GASTOS:
				dataURL = that.gastosVariosController;
				action = 'getGastosVarios';
				callback = that.renderGastos;
			break;
		}
		
		that.currentRequest = 
		$.ajax({
			url: dataURL,
			data: {
				IDProyecto: that.getIDProyecto(),
				action: action
			},
			dataType: 'json'
		}).success( function(json) {
			try {
				
				if( ! json.success ) {
					messageConsole.displayMessage(json.message, 'error');
					return false;
				}
				
				if( json.noRows ) {
					messageConsole.displayMessage(json.message, 'info');
					return false;
				}
				
				callback.call(that, json.data);

				that.cuentaFilas();
				that.enableToolbar();
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).complete( function() {
			DATA_LOADER.hide();
		});
	},
	
	renderInsumos: function( data ) {

		var that = this,
			html = '';

		for (var i = 0; i < data.Familias.length; i++) {
			html += that.insumosTemplate(data.Familias[i]);
		}

		$(that.dataContainer).html(html);
	},

	renderSubcontratos: function(data) {
		var that = this,
			html = '';

		for (var i = 0; i < data.Contratistas.length; i++) {
			html += that.subcontratosTemplate(data.Contratistas[i]);
		}

		$(that.dataContainer).html(html);
	},

	renderGastos: function(data) {
		var that = this,
			html = '';

		for (var i = 0; i < data.gastos.length; i++) {
			html += that.gastosTemplate(data.gastos[i]);
		}

		$(that.dataContainer).html(html);
	},

	cuentaFilas: function() {
		var AG = this;
		
		// Agrega el numero de documentos visibles por seccion
		$(AG.dataContainer + ' .section').each( function() {
			
			// Por cada titulo dentro de la seccion
		    $(this).children('.section-header').each(function() {
		    	
		    	// Se obtiene una referencia al elemento que contiene el numero de documentos
		        var $numRowsContainer = $(this).find('.item-count');
		        
				// Se cuentan los items que existen en cada contenido de la seccion que
				// esten visibles. Esto puede incluir subsecciones
		        var rowsInSection = $(this).next().find('tbody tr:not(.hidden)').length;
		        
		        // Se actualiza el numero de documentos que se contaron dentro de la seccion
		        $numRowsContainer.text(rowsInSection);
		    });
		});
	},
	
	asignaAgrupador: function(selectedItem, trigger) {
		
		var that = AGRUPACION;
		
		DATA_LOADER.show();
		
		$parentRow = trigger.parents('tr');
		
		var id = parseInt(trigger.parents('tr').attr('data-id'))
			, IDTransaccionCDC;
		
		if( $parentRow.hasClass('insumo') ) {
			
			source = AGRUPACION.insumoController;
		} else if( $parentRow.hasClass('actividad') ) {
			
			source = AGRUPACION.subcontratoController;
			var idContratista = parseInt(trigger.parents('tr').attr('data-idcontratista'));
			var idSubcontrato = parseInt(trigger.parents('tr').attr('data-idsubcontrato'));
		} else if( $parentRow.hasClass('item-facturavarios') ) {

			id_factura = parseInt($parentRow.attr('data-idtransaccion'));
			source = AGRUPACION.gastosVariosController;
		}
		
		$.ajax({
			type: 'POST',
			url: source,
			data: {
				  IDProyecto: that.getIDProyecto()
				, id_empresa: idContratista
				, id_subcontrato: idSubcontrato
				, id_factura: id_factura
				, id: id
				, id_agrupador: selectedItem.value
				, action: 'setAgrupador'
			},
			dataType: 'json'
		}).success( function(json) {
			try {
				if (!json.success) {
					
					messageConsole.displayMessage(json.message, 'error');
					return false;
				}
				
				trigger.parent().prev().text(selectedItem.label);
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).complete( function() {
			DATA_LOADER.hide();
		});
	},
	
	enableToolbar: function() {
		$('#radios-visibilidad, #radios-expansion').buttonset('enable');
	},
	
	disableToolbar: function() {
		$('#radios-visibilidad, #radios-expansion').buttonset('disable');
	},
	
	resetToolbar: function() {
		
		$toolbarButtons = $('#radios-visibilidad, #radios-expansion');
		
		$toolbarButtons.children('input').prop('checked', false);
		
		$('#rd-collapse-all, #rd-show-all').prop('checked', true);
		 
		$toolbarButtons.buttonset('refresh');
	},
	
	clearDataContainer: function() {
		
		$(this.dataContainer).empty();
	}
}

AGRUPACION.init();