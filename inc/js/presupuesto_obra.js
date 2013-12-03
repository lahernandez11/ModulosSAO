var App = {};

App.Presupuesto = {

	controller_url: 'inc/lib/controllers/PresupuestoObraController.php',
	$table: null,
	conceptoTemplate: null,

	init: function() {
		var that = this;

		this.conceptoTemplate = _.template($('#template-concepto').html());
		
		this.$table = $('#tabla-conceptos');

		$('#bl-proyectos').buttonlist({
			source: 'inc/lib/controllers/ListaProyectosController.php',
			data: {action: 'getListaProyectos'},
			onSelect: function( selectedItem, listItem ) {
				that.loadDescendants();
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

		this.$table.on('click', '.handle', function(event) {
			event.preventDefault();
			that.toggleNode($(this).parents('.concepto'));
		});

		$('#dialog-propiedades-concepto').dialog({
			autoOpen: false,
			modal: true,
			width: '550px',
			close: function() {
				that.desmarcaConceptos();
			}
		});

		$('#dialog-nuevo-agrupador').dialog({
			autoOpen: false,
			modal: true,
			width: '350px'
		});

		this.$table.on('click', '.descripcion', function(event) {

			var $concepto = $(this).parents('.concepto');
			that.selectNode($concepto);
			that.getDatosConcepto($concepto);
			event.preventDefault();
			event.stopPropagation();
		});

		this.$table.on('click', '.select', function(event) {
			event.preventDefault();
			that.toggleMarcaConcepto($(this).parents('.concepto'));
		});

		this.$table.on('dblclick', '.clave_concepto', function(event) {
			event.stopPropagation();

			var initial_value = $(this).text();
			
			$(this).data('initial_value', initial_value);

			var input = $('<input type="text" class="clave" value="' + initial_value + '"/>');

			$(this).html(input);
			input.focus();
		});

		this.$table.on('blur', 'input.clave', function() {
			var initial_value = $(this).data('initial_value');
			var input_value = $(this).val();

			that.setClaveConcepto($(this).parents('.concepto'), input_value);

			$(this).parent().text(input_value);
			$(this).remove();
		});

		$("#txtAgrupadorPartida").autocomplete({
		    minLength: 1,
		    source: function(request, response) {
				request.action = 'getAgrupadoresPartida';
				that.requestAgrupadoresList(request, response);
			},
		    select: function( event, ui ) {
		    	that.setAgrupador(ui.item, 1, this);
		    }
		});

		$("#txtAgrupadorSubpartida").autocomplete({
		    minLength: 1,
		    source: function(request, response) {
				request.action = 'getAgrupadoresSubpartida';
				that.requestAgrupadoresList(request, response);
			},
		    select: function( event, ui ) {
		    	that.setAgrupador(ui.item, 2, this);
		    }
		});

		$("#txtAgrupadorActividad").autocomplete({
		    minLength: 1,
		    source: function(request, response) {
				request.action = 'getAgrupadoresActividad';
				that.requestAgrupadoresList(request, response);
			},
		    select: function( event, ui ) {
		    	that.setAgrupador(ui.item, 3, this);
		    }
		});

		$("#txtAgrupadorTramo").autocomplete({
		    minLength: 1,
		    source: function(request, response) {
				request.action = 'getAgrupadoresTramo';
				that.requestAgrupadoresList(request, response);
			},
		    select: function( event, ui ) {
		    	that.setAgrupador(ui.item, 4, this);
		    }
		});

		$("#txtAgrupadorSubtramo").autocomplete({
		    minLength: 1,
		    source: function(request, response) {
				request.action = 'getAgrupadoresSubtramo';
				that.requestAgrupadoresList(request, response);
			},
		    select: function( event, ui ) {
		    	that.setAgrupador(ui.item, 5, this);
		    }
		});

		$('#guardar_agrupador').on('click', function() {
			that.addAgrupador();
		});

		$('#cerrar_agrupador').on('click', function() {
			$('#dialog-nuevo-agrupador').dialog('close');
		});

		$('#cerrar-concepto').on('click', function() {
			$('#dialog-propiedades-concepto').dialog('close');
		});
	},

	cleanDescripcionAgrupador: function(descripcion) {
		return descripcion.split('-')[1].trim();
	},

	getIDConcepto: function($el) {
		return parseInt($el.attr('id').split('-')[1]);
	},

	requestAgrupadoresList: function(request, response) {
		var that = this;
		
		request.IDProyecto = that.getIDProyecto();
		var agrupadores = [];

		$.getJSON( that.controller_url, request, function( data, status, xhr ) {
            
            if ( ! data.success ) {
            	messageConsole.displayMessage(data.message, 'error');
            } else {
	            for( i = 0; i < data.agrupadores.length; i++ ) {
				   agrupadores.push({
				   		id: data.agrupadores[i].id_agrupador,
				   		label: data.agrupadores[i].agrupador
				   	});
				}

				if ( data.agrupadores.length == 0) {
					agrupadores.push({
						id: 0,
						label: 'Agregar - ' + request.term
					});
				}
			}

			response( agrupadores );
		});
	},

	getIDProyecto: function() {
		return $('#bl-proyectos').buttonlist('option', 'selectedItem').value;
	},

	toggleNode: function($concepto) {
		// muestra los descendientes de un concepto

		var isNodeOpen = this.isNodeOpen($concepto);

		if ( this.haveDescendants($concepto) ) {
			// esto se ejecuta si el concepto tiene descendientes
			// ya cargados, dependiendo del estado del concepto
			// se muestran u ocultan sus descendientes
			if (isNodeOpen) {
				this.hideDescendants($concepto);
			} else {
				this.showDescendants($concepto);
			}
			
			this.toggleConceptoHandle($concepto);
		} else {
			// esto se ejecuta cuando el concepto no tiene
			// descendientes cargados, si el nodo esta cerrado
			// los carga y muestra
			if (isNodeOpen) {
				this.toggleConceptoHandle($concepto);
			} else {
				this.loadDescendants($concepto);
			}
		}	
	},

	getNivelConcepto: function($concepto) {
		return $concepto.attr('data-nivel');
	},

	loadDescendants: function($concepto) {
		var that = this;

		var id_concepto = null;

		if ( $concepto )
			id_concepto = this.getIDConcepto($concepto);

		DATA_LOADER.show();

		$.ajax({
			url: that.controller_url,
			data: {
				'IDProyecto': that.getIDProyecto(),
				'action': 'getConceptos',
				id_concepto: id_concepto
			},
			dataType: 'json'
		})
		.done( function(data) {
			if ( ! data.success ) {
				messageConsole.displayMessage(data.message, 'error');
			} else {
				that.fillConceptos(data.conceptos, $concepto);
				that.toggleConceptoHandle($concepto);
			}
		})
		.always(function(){ DATA_LOADER.hide(); })
	},

	fillConceptos: function( conceptos, $concepto ) {

		if ( $concepto )
			$concepto.after(this.conceptosListTemplate(conceptos));
		else
			$('#tabla-conceptos tbody').html( this.conceptosListTemplate(conceptos) );
	},

	conceptosListTemplate: function( conceptos ) {
		var html = '';

		for (var i = 0; i < conceptos.length; i++) {
			html += this.conceptoTemplate( conceptos[i] );
		}

		return html;
	},
	
	toggleConceptoHandle: function($concepto) {
		if ($concepto)
			this.getConceptoHandleNode($concepto).toggleClass('icon-minus icon-plus');
	},

	isNodeOpen: function($concepto) {

		if ( $concepto )
			return this.getConceptoHandleNode($concepto).hasClass('icon-minus');
		else
			return false;
	},

	getDescendants: function($concepto) {
		// obtiene los descendientes de un concepto (si ya estan cargados existen)
		var that = this;
		var nivel_ancestro = this.getNivelConcepto($concepto);

		return $concepto.nextAll().filter(function(){
			return that.getNivelConcepto($(this)).indexOf(nivel_ancestro) === 0 ? true : false;
		});
	},

	haveDescendants: function($concepto) {
		// Determina si el concepto ya tiene descendientes cargados
		return this.getDescendants($concepto).length;
	},

	selectDescendants: function($concepto) {
		var that = this;
		this.getDescendants($concepto).map(function() {
			that.selectNode($(this));
		});
	},

	unselectDescendants: function($concepto) {
		var that = this;
		this.getDescendants($concepto).map(function() {
			that.unselectNode($(this));
		});
	},

	hideDescendants: function($concepto) {
		// oculta todos los descendientes de un concepto
		// y cambia su handle a + par aindicar que esta cerrado
		this.getDescendants($concepto).hide()
		.find('.handle').removeClass('icon-minus').addClass('icon-plus');
	},

	showDescendants: function($concepto) {
		// muestra solo los descendientes inmediatos de un concepto
		var that = this;
		var nivel_ancestro = this.getNivelConcepto($concepto);
		
		this.getDescendants($concepto).filter(function() {
			return that.getNivelConcepto($(this)).indexOf(nivel_ancestro) === 0 &&
				that.getNivelConcepto($(this)).length === nivel_ancestro.length + 4 ?
				true : false
		}).show();
	},

	toggleMarcaConcepto: function($concepto) {
		$concepto.toggleClass('selected')
		.find('.select').toggleClass('icon-checkbox-unchecked icon-checkbox-checked');

		if ($concepto.hasClass('selected'))
			this.selectDescendants($concepto);
		else
			this.unselectDescendants($concepto);
	},

	selectNode: function($concepto) {
		$concepto.addClass('selected')
		.find('.select')
		.removeClass('icon-checkbox-unchecked')
		.addClass('icon-checkbox-checked');
	},

	unselectNode: function($concepto) {
		$concepto.removeClass('selected')
		.find('.select')
		.removeClass('icon-checkbox-checked')
		.addClass('icon-checkbox-unchecked');
	},

	desmarcaConceptos: function() {
		this.$table.find('tr.selected').removeClass('selected');
		this.$table
		.find('.select.icon-checkbox-checked')
		.toggleClass('icon-checkbox-checked icon-checkbox-unchecked');
	},

	getConceptoHandleNode: function($concepto) {
		return $concepto.find('.handle');
	},

	openConceptoPropertiesDialog: function() {
		$('#dialog-propiedades-concepto').dialog('open');
	},

	getDatosConcepto: function($concepto) {
		var that = this;

		DATA_LOADER.show();

		$.ajax({
			url: that.controller_url,
			data: {
				IDProyecto: that.getIDProyecto(),
				id_concepto: that.getIDConcepto($concepto),
				action: 'getDatosConcepto'
			},
			dataType: 'json'
		})
		.done( function(data) {

			if (! data.success) {
				messageConsole.displayMessage(data.message, 'error');
				return;
			}

			that.fillConceptoProperties(data.concepto);
			that.openConceptoPropertiesDialog();
		})
		.always( function() {
			DATA_LOADER.hide();
		});
	},

	fillConceptoProperties: function(data) {
		var title = data.descripcion;

		if (this.getSelected().length)
			title = 'Varios conceptos seleccionados';

		$('#dialog-propiedades-concepto')
		.dialog({ title: 'Propiedades de: ' + title });

		$('#txtDescripcion').val(data.descripcion);
		$('#txtAgrupadorPartida').val(data.agrupador_partida);
		$('#txtAgrupadorSubpartida').val(data.agrupador_subpartida);
		$('#txtAgrupadorActividad').val(data.agrupador_actividad);
		$('#txtAgrupadorTramo').val(data.agrupador_tramo);
		$('#txtAgrupadorSubtramo').val(data.agrupador_subtramo);
	},

	getSelected: function() {
		return this.$table.find('.concepto.selected');
	},

	getConceptosSeleccionados: function() {
		var conceptos = [];

		this.getSelected().map(function(index, domElement){

			conceptos.push({
				'id_concepto': parseInt(this.id.split('-')[1])
			});
		});

		return conceptos;
	},

	setClaveConcepto: function($concepto, clave) {
		var that = this;

		DATA_LOADER.show();

		$.ajax({
			type: 'POST',
			url: that.controller_url,
			data: {
				IDProyecto: that.getIDProyecto(),
				id_concepto: that.getIDConcepto($concepto),
				clave: clave,
				action: 'setClaveConcepto'
			},
			dataType: 'json'
		})
		.done( function(data) {
			if( ! data.success ) {
				messageConsole.displayMessage(data.message, 'error');
			}
		})
		.always( DATA_LOADER.hide );
	},

	setAgrupador: function(item, type, input) {

		var request = {
			IDProyecto: this.getIDProyecto(),
			conceptos: this.getConceptosSeleccionados(),
			callback: this.requestSetAgrupador,
			type: type,
			$input: $(input),
			descripcion: item.label
		};
		
		switch( type ) {

			case 1:
				request.action = 'setAgrupadorPartida';
				break;
			case 2:
				request.action = 'setAgrupadorSubpartida';
				break;
			case 3:
				request.action = 'setAgrupadorActividad';
				break;
			case 4:
				request.action = 'setAgrupadorTramo';
				break;
			case 5:
				request.action = 'setAgrupadorSubtramo';
				break;
		}

		if ( item.id == 0 ) {
			this.openAddAgrupadorDialog(request);
		} else {
			request.id_agrupador = item.id;
			request.callback = DATA_LOADER.hide;
			this.requestSetAgrupador(request);
		}
	},

	requestSetAgrupador: function(request) {
		DATA_LOADER.show();

		$.ajax({
			type: 'POST',
			url: this.controller_url,
			data: {
				IDProyecto: request.IDProyecto,
				conceptos: request.conceptos,
				id_agrupador: request.id_agrupador,
				action: request.action	
			},
			dataType: 'json'
		})
		.done( function(data) {
			if ( data.success ) {
				messageConsole.displayMessage('Agrupador asignado correctamente.', 'success');
			} else {
				messageConsole.displayMessage(data.message, 'error');
			}
		})
		.always(request.callback);
	},

	openAddAgrupadorDialog: function(request) {

		$('#guardar_agrupador').data('request', request);
		$('#txtDescripcionAgruapdor').val(request.descripcion.split('-')[1].trim());
		$('#txtClaveAgrupador').val('');
		$('#dialog-nuevo-agrupador').dialog('open');
	},

	addAgrupador: function() {
		var that = this;

		DATA_LOADER.show();

		var request = $('#guardar_agrupador').data('request');

		var action   = '';

		switch(request.type) {
			case 1: action = 'addAgrupadorPartida'; break;
			case 2: action = 'addAgrupadorSubpartida'; break;
			case 3: action = 'addAgrupadorActividad'; break;
			case 4: action = 'addAgrupadorTramo'; break;
			case 5: action = 'addAgrupadorSubtramo'; break;
		}

		var clave = $('#txtClaveAgrupador').val(),
			descripcion = $('#txtDescripcionAgruapdor').val();

		$.ajax({
			type: 'POST',
			url: that.controller_url,
			data: {
				IDProyecto: request.IDProyecto,
				clave: clave,
				descripcion: descripcion,
				action: action
			},
			dataType: 'json'
		})
		.done( function(data) {
			if ( ! data.success ) {
				messageConsole.displayMessage(data.message, 'error');
				DATA_LOADER.hide();
			} else {
				$('#dialog-nuevo-agrupador').dialog('close');
				request.id_agrupador = data.id_agrupador;
				request.callback = DATA_LOADER.hide;
				request.$input.val(descripcion);
				that.requestSetAgrupador(request);
			}
		});
	},
}

App.Presupuesto.init();