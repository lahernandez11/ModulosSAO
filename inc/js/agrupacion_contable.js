var App = {};

App.AgrupacionContable = {

	controller_url: 'inc/lib/controllers/AgrupacionContableController.php',
	agrupadorInsumoController: 'inc/lib/controllers/AgrupadorInsumoController.php',
	$table: null,

	cuentaTemplate: null,
	cuentaPropertiesTemplate: null,

	init: function() {
		var that = this;

		this.$table = $('#tabla-cuentas');

		this.cuentaTemplate = _.template($('#template-cuenta').html());
		this.cuentaPropertiesTemplate = _.template($('#template-cuenta-properties').html());

		$('#bl-proyectos').buttonlist({
			source: 'inc/lib/controllers/ListaObrasController.php',
			data: {action: 'getListaProyectos'},
			onSelect: function( selectedItem, listItem ) {
				that.loadDescendants();
			},
			didNotDataFound: function() {
				$.notify({text: 'No se pudo cargar la lista de proyectos'});
			},
			onCreateListItem: function() {
				return {
					id: this.id,
					value: this.nombre,
					extra: {
						source: this.source_id
					}
				}
			}
		});

		this.$table.on('click', '.handle', function(event) {
			event.preventDefault();
			that.toggleNode($(this).parents('.cuenta'));
		});

		$('#dialog-nuevo-agrupador').dialog({
			autoOpen: false,
			modal: true,
			width: '350px'
		});

		this.$table.on('click', '.descripcion', function(event) {

			event.preventDefault();
			var $cuenta = $(this).parents('.cuenta');
			that.selectNode($cuenta);
			that.getDatosCuenta($cuenta);
		});

		this.$table.on('click', '.select', function(event) {
			event.preventDefault();
			that.toggleMarcaConcepto($(this).parents('.cuenta'));
		});

		$('#nuevo-agrupador').on('submit', function(event) {
			event.preventDefault();
			that.AddCuenta();
		});

		$('#cerrar_agrupador').on('click', function() {
			$('#dialog-nuevo-agrupador').dialog('close');
		});

		$('.col-switch').on('change', 'input', function(event){
			if (this.checked)
				that.$table.find('.' + this.id).removeClass('hidden');
			else
				that.$table.find('.' + this.id).addClass('hidden');
		})
		.find('input').prop('checked', true);
	},

	cleanDescripcionAgrupador: function(descripcion) {
		return descripcion.split('-')[1].trim();
	},

	getIDCuenta: function($el) {
		return parseInt($el.attr('id').split('-')[1]);
	},

	requestAgrupadoresList: function(request, response, url) {
		var that = this;
		
		request.id_obra = that.getIDProyecto();
		request.base_datos = that.getBaseDatos();
		var agrupadores = [];

		$.getJSON( url, request, function( data, status, xhr ) {
            
            if ( ! data.success ) {
            	messageConsole.displayMessage(data.message, 'error');
            } else {
	            for( i = 0; i < data.options.length; i++ ) {
				   agrupadores.push({
				   		id: data.options[i].id,
				   		label: data.options[i].label
				   	});
				}

				if (request.action === 'getAgrupadoresTipoCuenta') {
					if ( data.options.length == 0) {
						agrupadores.push({
							id: 0,
							label: 'Agregar - ' + request.term
						});
					}
				}
			}

			response( agrupadores );
		});
	},

	getIDProyecto: function() {
		return $('#bl-proyectos').buttonlist('option', 'selectedItem').value;
	},

	getBaseDatos: function() {
		return $('#bl-proyectos').buttonlist('option', 'selectedItem').extra.source
	},

	toggleNode: function($el) {
		// muestra los descendientes de un cuenta

		var isNodeOpen = this.isNodeOpen($el);

		if ( this.haveDescendants($el) ) {
			// esto se ejecuta si el cuenta tiene descendientes
			// ya cargados, dependiendo del estado del cuenta
			// se muestran u ocultan sus descendientes
			if (isNodeOpen) {
				this.hideDescendants($el);
			} else {
				this.showDescendants($el);
			}
			this.toggleHandle($el);
		} else {
			// esto se ejecuta cuando el cuenta no tiene
			// descendientes cargados, si el nodo esta cerrado
			// los carga y muestra
			if (isNodeOpen) {
				this.toggleHandle($el);
			} else {
				this.loadDescendants($el);
			}
		}	
	},

	getIDCuentaSup: function($el) {
		return parseInt($el.attr('data-idsup'));
	},

	loadDescendants: function($el) {
		var that = this;

		var id_cuenta = null;

		if ( $el )
			id_cuenta = this.getIDCuenta($el);

		DATA_LOADER.show();

		$.ajax({
			url: that.controller_url,
			data: {
				'id_obra': that.getIDProyecto(),
				'base_datos': that.getBaseDatos(),
				'action': 'getCuentas',
				id_cuenta: id_cuenta
			},
			dataType: 'json'
		})
		.done( function(data) {
			if ( ! data.success ) {
				messageConsole.displayMessage(data.message, 'error');
			} else {
				that.renderCuentas(data.cuentas, $el);
				that.toggleHandle($el);
			}
		})
		.always(function(){ DATA_LOADER.hide(); });
	},

	renderCuentas: function( cuentas, $el ) {

		var html = '';

		for (cuenta in cuentas) {
			cuentas[cuenta].showEmpresa = $('#empresa').prop('checked');
			cuentas[cuenta].showNaturaleza = $('#naturaleza').prop('checked');
			
			html += this.cuentaTemplate(cuentas[cuenta]);
		}

		if ($el)
			$el.after(html);
		else
			$('#tabla-cuentas tbody').html(html);
	},
	
	toggleHandle: function($el) {
		if ($el)
			this.getHandle($el).toggleClass('icon-minus icon-plus');
	},

	isNodeOpen: function($el) {

		if ( $el )
			return this.getHandle($el).hasClass('icon-minus');
		else
			return false;
	},

	getDescendants: function($el) {
		// obtiene los descendientes de un concepto (si ya estan cargados existen)
		var that = this;
		var IdCtaSup = this.getIDCuenta($el);
		
		return $el.nextAll().filter(function() {
			return that.getIDCuentaSup($(this)) === IdCtaSup ? true : false;
		});
	},

	haveDescendants: function($el) {
		// Determina si el concepto ya tiene descendientes cargados
		return this.getDescendants($el).length;
	},

	hideDescendants: function($el) {
		// oculta todos los descendientes de un cuenta
		// y cambia su handle a + par aindicar que esta cerrado
		var that = this;

		this.getDescendants($el).hide().each( function() {
			that.hideDescendants($(this));
		})
		.find('.handle').removeClass('icon-minus').addClass('icon-plus');
	},

	showDescendants: function($el) {
		// muestra solo los descendientes inmediatos de un cuenta
		var that = this;
		var IdCtaSup = this.getIDCuenta($el);
		
		this.getDescendants($el).show();
	},

	toggleMarcaConcepto: function($el) {
		$el.toggleClass('selected')
		.find('.select').toggleClass('icon-checkbox-unchecked icon-checkbox-checked');

		if ($el.hasClass('selected'))
			this.selectDescendants($el);
		else
			this.unselectDescendants($el);
	},

	selectDescendants: function($el) {
		var that = this;
		
		var desc = this.getDescendants($el);

		this.selectNode(desc);

		desc.each(function() {
			that.selectDescendants($(this));
		});
	},

	unselectDescendants: function($el) {
		var that = this;

		var desc = this.getDescendants($el);

		this.unselectNode(desc);

		desc.each(function() {
			that.unselectDescendants($(this));
		});
	},

	selectNode: function($el) {
		$el.addClass('selected')
		.find('.select')
		.removeClass('icon-checkbox-unchecked')
		.addClass('icon-checkbox-checked');
	},

	unselectNode: function($el) {
		$el.removeClass('selected')
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

	getHandle: function($el) {
		return $el.find('.handle');
	},

	openCuentaPropertiesDialog: function() {
		$('#dialog-propiedades-cuenta').dialog('open');
	},

	getDatosCuenta: function($el) {
		var that = this;

		DATA_LOADER.show();

		$.ajax({
			url: that.controller_url,
			data: {
				id_obra: that.getIDProyecto(),
				base_datos: that.getBaseDatos(),
				id_cuenta: that.getIDCuenta($el),
				action: 'getDatosCuenta'
			},
			dataType: 'json'
		})
		.done( function(data) {

			if (! data.success) {
				messageConsole.displayMessage(data.message, 'error');
				that.unselectNode($el);
				return;
			}

			that.renderCuentaProperties(data.cuenta);

			that.openCuentaPropertiesDialog();
		})
		.always(DATA_LOADER.hide());
	},

	renderCuentaProperties: function(data) {
		var that = this,
			html = '';

		data = data || {};
		
		if (this.getSelected().length > 1)
			data.nombre =  'Varias cuentas seleccionadas';

		var html = this.cuentaPropertiesTemplate(data);
		
		$(html).dialog({
			modal: true,
			width: '550px',
			buttons: {
				cerrar: function() {
					$(this).dialog('close');
				}
			},
			close: function() {
				that.desmarcaConceptos();
				$(this).remove();
			}
		})

		$("#txtAgrupadorEmpresa").autocomplete({
		    minLength: 1,
		    source: function(request, response) {
				request.action = 'getAgrupadoresEmpresa';
				that.requestAgrupadoresList(request, response, that.controller_url);
			},
		    select: function( event, ui ) {
		    	that.setAgrupador(ui.item, 'setAgrupadorEmpresa', this);
		    }
		});

		$("#txtAgrupadorNaturaleza").autocomplete({
		    minLength: 1,
		    source: function(request, response) {
				request.action = 'getAgrupadoresNaturaleza';
				that.requestAgrupadoresList(request, response, that.agrupadorInsumoController);
			},
		    select: function( event, ui ) {
		    	that.setAgrupador(ui.item, 'setAgrupadorNaturaleza', this);
		    }
		});
	},

	getSelected: function() {
		return this.$table.find('.selected');
	},

	esAfectable: function(ix, $el) {
		ix = ix || 0;

		if (parseInt($($el).attr('data-afectable')) === 1)
			return true;
		else
			return false;
	},

	getSelected: function() {
		return this.$table.find('.cuenta.selected');
	},

	getConceptosSeleccionados: function() {
		var conceptos = [];

		this.getSelected().map(function(index, domElement){

			conceptos.push({
				'id_cuenta': parseInt(this.id.split('-')[1])
			});
		});

		return conceptos;
	},

	getCuentasSeleccionadas: function() {
		var cuentas = [];

		this.getSelected().map(function(index, domElement){

			cuentas.push({
				'id_cuenta': parseInt(this.id.split('-')[1])
			});
		});

		return cuentas;
	},

	setAgrupador: function(item, method, input) {

		var request = {
			id_obra: this.getIDProyecto(),
			base_datos: this.getBaseDatos(),
			cuentas: this.getCuentasSeleccionadas(),
			callback: this.requestSetCuenta,
			action: method,
			$input: $(input),
			descripcion: item.label
		};
		
		if ( item.id == 0 ) {
			this.openAddCuentaDialog(request);
		} else {
			request.id_agrupador = item.id;
			request.callback = DATA_LOADER.hide;
			this.requestSetCuenta(request);
		}
	},

	requestSetCuenta: function(request) {
		DATA_LOADER.show();
		var that = this;

		$.ajax({
			type: 'POST',
			url: this.controller_url,
			data: {
				id_obra: request.id_obra,
				base_datos: request.base_datos,
				cuentas: request.cuentas,
				id_agrupador: request.id_agrupador,
				action: request.action	
			},
			dataType: 'json'
		})
		.done( function(data) {
			if ( data.success ) {
				messageConsole.displayMessage('Agrupador asignado correctamente.', 'success');
				that.updateAgrupadorColumna(request.$input);
			} else {
				messageConsole.displayMessage(data.message, 'error');
			}
		})
		.always(request.callback);
	},

	updateAgrupadorColumna: function($input) {

		switch ($input[0].id) {

			case 'txtAgrupadorEmpresa':
				this.getSelected().filter(this.esAfectable).find('td:eq(5)').text($input.val());
			break;

			case 'txtAgrupadorNaturaleza':
				this.getSelected().filter(this.esAfectable).find('td:eq(6)').text($input.val());
			break;
		}
	},

	openAddCuentaDialog: function(request) {

		$('#guardar_agrupador').data('request', request);
		$('#txtDescripcionAgruapdor').val(request.descripcion.split('-')[1].trim());
		$('#dialog-nuevo-agrupador').dialog('open');
	},

	AddCuenta: function() {
		var that = this;

		DATA_LOADER.show();

		var request = $('#guardar_agrupador').data('request');
		var action = '';

		switch(request.$input[0].id) {
			
			case 'txtAgrupadorTipoCuenta':
				action = 'addAgrupadorTipoCuenta';
			break;
		}

		var clave = $('#txtClaveAgrupador').val(),
			descripcion = $('#txtDescripcionAgruapdor').val();

		$.ajax({
			type: 'POST',
			url: that.controller_url,
			data: {
				id_obra: request.id_obra,
				base_datos: request.base_datos,
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
				that.requestSetCuenta(request);
			}
		});
	},
}

App.AgrupacionContable.init();