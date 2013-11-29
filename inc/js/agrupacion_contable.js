var App = {};

App.AgrupacionContable = {

	controller_url: 'inc/lib/controllers/AgrupacionContableController.php',
	$table: null,

	cuentaTemplate: null,

	init: function() {
		var that = this;

		this.$table = $('#tabla-cuentas');

		this.cuentaTemplate = _.template($('#template-cuenta').html());

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
			that.toggleNode($(this).parents('.cuenta'));
		});

		$('#dialog-propiedades-cuenta').dialog({
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

			event.preventDefault();
			// event.stopPropagation();
			var $cuenta = $(this).parents('.cuenta');
			that.selectNode($cuenta);
			that.getDatosCuenta($cuenta);
		});

		this.$table.on('click', '.select', function(event) {
			event.preventDefault();
			that.toggleMarcaConcepto($(this).parents('.cuenta'));
		});

		$("#txtAgrupadorProveedor").autocomplete({
		    minLength: 1,
		    source: function(request, response) {
				request.action = 'getAgrupadoresProveedor';
				that.requestAgrupadoresList(request, response);
			},
		    select: function( event, ui ) {
		    	that.setAgrupador(ui.item, 'setAgrupadorProveedor', this);
		    }
		});

		$("#txtAgrupadorTipoCuenta").autocomplete({
		    minLength: 1,
		    source: function(request, response) {
				request.action = 'getAgrupadoresSubpartida';
				that.requestAgrupadoresList(request, response);
			},
		    select: function( event, ui ) {
		    	that.setAgrupador(ui.item, 'setAgrupadorTipoCuenta', this);
		    }
		});

		$('#guardar_agrupador').on('click', function() {
			that.addAgrupador();
		});

		$('#cerrar_agrupador').on('click', function() {
			$('#dialog-nuevo-agrupador').dialog('close');
		});

		$('#cerrar-cuenta').on('click', function() {
			$('#dialog-propiedades-cuenta').dialog('close');
		});
	},

	cleanDescripcionAgrupador: function(descripcion) {
		return descripcion.split('-')[1].trim();
	},

	getIDCuenta: function($el) {
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
				   		id: data.agrupadores[i].id,
				   		label: data.agrupadores[i].agrupador
				   	});
				}

				// if ( data.agrupadores.length == 0) {
				// 	agrupadores.push({
				// 		id: 0,
				// 		label: 'Agregar - ' + request.term
				// 	});
				// }
			}

			response( agrupadores );
		});
	},

	getIDProyecto: function() {
		return $('#bl-proyectos').buttonlist('option', 'selectedItem').value;
	},

	toggleNode: function($cuenta) {
		// muestra los descendientes de un cuenta

		var isNodeOpen = this.isNodeOpen($cuenta);

		if ( this.haveDescendants($cuenta) ) {
			// esto se ejecuta si el cuenta tiene descendientes
			// ya cargados, dependiendo del estado del cuenta
			// se muestran u ocultan sus descendientes
			if (isNodeOpen) {
				this.hideDescendants($cuenta);
			} else {
				this.showDescendants($cuenta);
			}
			this.toggleHandle($cuenta);
		} else {
			// esto se ejecuta cuando el cuenta no tiene
			// descendientes cargados, si el nodo esta cerrado
			// los carga y muestra
			if (isNodeOpen) {
				this.toggleHandle($cuenta);
			} else {
				this.loadDescendants($cuenta);
			}
		}	
	},

	getIDCuentaSup: function($cuenta) {
		return parseInt($cuenta.attr('data-idsup'));
	},

	loadDescendants: function($cuenta) {
		var that = this;

		var id_cuenta = null;

		if ( $cuenta )
			id_cuenta = this.getIDCuenta($cuenta);

		DATA_LOADER.show();

		$.ajax({
			url: that.controller_url,
			data: {
				'IDProyecto': that.getIDProyecto(),
				'action': 'getCuentas',
				id_cuenta: id_cuenta
			},
			dataType: 'json'
		})
		.done( function(data) {
			if ( ! data.success ) {
				messageConsole.displayMessage(data.message, 'error');
			} else {
				that.renderCuentas(data.cuentas, $cuenta);
				that.toggleHandle($cuenta);
			}
		})
		.always(function(){ DATA_LOADER.hide(); });
	},

	renderCuentas: function( cuentas, $cuenta ) {

		var html = '';

		for (cuenta in cuentas) {
			html += this.cuentaTemplate(cuentas[cuenta]);
		}

		if ($cuenta)
			$cuenta.after(html);
		else
			$('#tabla-cuentas tbody').html(html);
	},
	
	toggleHandle: function($cuenta) {
		if ($cuenta)
			this.getHandle($cuenta).toggleClass('icon-minus icon-plus');
	},

	isNodeOpen: function($cuenta) {

		if ( $cuenta )
			return this.getHandle($cuenta).hasClass('icon-minus');
		else
			return false;
	},

	getDescendants: function($cuenta) {
		// obtiene los descendientes de un concepto (si ya estan cargados existen)
		var that = this;
		var IdCtaSup = this.getIDCuenta($cuenta);
		
		return $cuenta.nextAll().filter(function() {
			return that.getIDCuentaSup($(this)) === IdCtaSup ? true : false;
		});
	},

	haveDescendants: function($cuenta) {
		// Determina si el concepto ya tiene descendientes cargados
		return this.getDescendants($cuenta).length;
	},

	hideDescendants: function($cuenta) {
		// oculta todos los descendientes de un cuenta
		// y cambia su handle a + par aindicar que esta cerrado
		var that = this;

		this.getDescendants($cuenta).hide().each( function() {
			that.hideDescendants($(this));
		})
		.find('.handle').removeClass('icon-minus').addClass('icon-plus');
	},

	showDescendants: function($cuenta) {
		// muestra solo los descendientes inmediatos de un cuenta
		var that = this;
		var IdCtaSup = this.getIDCuenta($cuenta);
		
		this.getDescendants($cuenta).show();
	},

	toggleMarcaConcepto: function($concepto) {
		$concepto.toggleClass('selected')
		.find('.select').toggleClass('icon-checkbox-unchecked icon-checkbox-checked');

		if ($concepto.hasClass('selected'))
			this.selectDescendants($concepto);
		else
			this.unselectDescendants($concepto);
	},

	selectDescendants: function($cuenta) {
		var that = this;
		
		var desc = this.getDescendants($cuenta);

		this.selectNode(desc);

		desc.each(function() {
			that.selectDescendants($(this));
		});
	},

	unselectDescendants: function($cuenta) {
		var that = this;

		var desc = this.getDescendants($cuenta);

		this.unselectNode(desc);

		desc.each(function() {
			that.unselectDescendants($(this));
		});
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

	getHandle: function($concepto) {
		return $concepto.find('.handle');
	},

	openCuentaPropertiesDialog: function() {
		$('#dialog-propiedades-cuenta').dialog('open');
	},

	getDatosCuenta: function($cuenta) {
		var that = this;

		DATA_LOADER.show();

		$.ajax({
			url: that.controller_url,
			data: {
				IDProyecto: that.getIDProyecto(),
				id_cuenta: that.getIDCuenta($cuenta),
				action: 'getDatosCuenta'
			},
			dataType: 'json'
		})
		.done( function(data) {

			if (! data.success) {
				messageConsole.displayMessage(data.message, 'error');
				that.unselectNode($cuenta);
				return;
			}

			that.fillCuentaProperties(data.cuenta);

			that.openCuentaPropertiesDialog();
		})
		.always(DATA_LOADER.hide());
	},

	fillCuentaProperties: function(data) {
		data = data || {};

		var title = data.Nombre;

		if (this.getSelected().length)
			title = 'Varias cuentas seleccionadas';

		$('#dialog-propiedades-cuenta')
		.dialog({ title: 'Propiedades de: ' + title });

		$('#txtDescripcion').val(data.Nombre);
		$('#txtAgrupadorProveedor').val(data.Proveedor);
	},

	getSelected: function() {
		return this.$table.find('.selected');
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
			IDProyecto: this.getIDProyecto(),
			cuentas: this.getCuentasSeleccionadas(),
			callback: this.requestSetAgrupador,
			action: method,
			$input: $(input),
			descripcion: item.label
		};
		
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
				cuentas: request.cuentas,
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

App.AgrupacionContable.init();