var App = {};

App.Presupuesto = {

	controller_url: 'inc/lib/controllers/PresupuestoObraController.php',
	$table: null,

	init: function() {
		var that = this;

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
			var id_concepto = that.getIDConcepto($(this));
			that.toggleNode(id_concepto);
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
			var id_concepto = that.getIDConcepto($(this));
			that.marcaConcepto(id_concepto);
			that.getDatosConcepto(id_concepto);
			event.preventDefault();
			event.stopPropagation();
		});

		this.$table.on('click', '.check', function(event) {
			event.preventDefault();
			that.toggleMarcaConcepto($(this));
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

			that.setClaveConcepto(that.getIDConcepto($(this)), input_value);

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
		return parseInt($el.parents('tr.concepto').attr('id').split('-')[1]);
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

	toggleNode: function(id_concepto) {
		// muestra los descendientes de un concepto

		var isNodeOpen = this.isNodeOpen(id_concepto);

		if ( this.haveDescendants(id_concepto) ) {
			// esto se ejecuta si el concepto tiene descendientes
			// ya cargados, dependiendo del estado del concepto
			// se muestran u ocultan sus descendientes
			if (isNodeOpen) {
				this.hideDescendants(id_concepto);
			} else {
				this.showDescendants(id_concepto);
			}
			
			this.toggleConceptoHandle(id_concepto);
		} else {
			// esto se ejecuta cuando el concepto no tiene
			// descendientes cargados, si el nodo esta cerrado
			// los carga y muestra
			if (isNodeOpen) {
				this.toggleConceptoHandle(id_concepto);
			} else {
				this.loadDescendants(id_concepto);
			}
		}	
	},

	getNivelConcepto: function(id_concepto) {
		return this.getConceptoNode(id_concepto).attr('data-nivel');
	},

	loadDescendants: function(id_concepto) {
		var that = this;

		id_concepto = id_concepto || null;

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
				that.fillConceptos(data.conceptos, id_concepto);
				that.toggleConceptoHandle(id_concepto);
			}
		})
		.always(function(){ DATA_LOADER.hide(); })
	},

	fillConceptos: function( conceptos, id_concepto ) {

		id_concepto = id_concepto || null;

		if ( id_concepto == null )
			$('#tabla-conceptos tbody').html( this.conceptosListTemplate(conceptos) );
		else
			this.getConceptoNode(id_concepto).after(this.conceptosListTemplate(conceptos));
	},

	conceptoTemplate: function(data) {

		var html = '',
			descripcion = '<td style="padding-left: ' + data.numero_nivel + 'em">'
				+ '<a href="#" title="'+ data.descripcion +'" class="descripcion">' + data.descripcion + '</a></td>',
			concepto_icon = '<td class="icon-cell"></td>',
			handle = '<td class="icon-cell"><a href="" class="handle icon-plus"></a></td>';

		if ( data.id_material > 0) {

			handle = '<td class="icon-cell"></td>';
			icon = 'icon-database';
			title ='Materiales';

			switch( data.tipo_material ) {

				case 1:
					icon = 'icon-database';
					break;
				case 2:
					icon = 'icon-users';
					title ='Mano de obra';
					break;
				case 4:
					icon = 'icon-hammer';
					title ='Herramienta';
					break;
				case 8:
					icon = 'icon-truck';
					title ='Maquinaria';
					break;
			}

			concepto_icon = '<td class="icon-cell"><a class="'+ icon +'" title="'+ title +'"></a></td>';
		};

		if ( data.concepto_medible == 3 ) {
			descripcion = '<td style="padding-left: ' + data.numero_nivel + 'em"><a href="#" title="'+ data.descripcion +'" class="descripcion concepto-medible">' + data.descripcion + '</a></td>',
			concepto_icon = '<td class="icon-cell"><a class="icon-file" title="Concepto medible"></a></td>';
		};

		html =
			'<tr id="c-' + data.id_concepto + '" data-nivel="' + data.nivel + '" data-numeronivel="' + data.numero_nivel + '" class="concepto">'
			+ 	concepto_icon
			+ 	handle
			+   '<td class="icon-cell"><a href="#" class="check icon-checkbox-unchecked"></a></td>'
			+ 	'<td class="clave_concepto">' + data.clave_concepto + '</td>'
			+ 	descripcion
			+ 	'<td>' + data.unidad + '</td>'
			+ 	'<td class="numerico">' + data.cantidad_presupuestada + '</td>'
			+ 	'<td class="numerico">' + data.precio_unitario + '</td>'
			+ 	'<td class="numerico">' + data.monto_presupuestado + '</td>'
			+ '</tr>';

		return html;
	},

	conceptosListTemplate: function( conceptos ) {
		var html = '';

		for (var i = 0; i < conceptos.length; i++) {
			html += this.conceptoTemplate( conceptos[i] );
		}

		return html;
	},
	
	toggleConceptoHandle: function(id_concepto) {
		this.getConceptoHandleNode(id_concepto).toggleClass('icon-minus icon-plus');
	},

	isNodeOpen: function(id_concepto) {

		if ( id_concepto != null )
			return this.getConceptoHandleNode(id_concepto).hasClass('icon-minus');
		else
			return false;
	},

	getDescendants: function(id_concepto) {
		// obtiene los descendientes de un concepto (si ya estan cargados existen)
		var nivel_ancestro = this.getNivelConcepto(id_concepto);

		return this.getConceptoNode(id_concepto).nextAll().filter(function(){
			return $(this).attr('data-nivel').indexOf(nivel_ancestro) === 0 ? true : false;
		});
	},

	hideDescendants: function(id_concepto) {
		// oculta todos los descendientes de un concepto
		// y cambia su handle a + par aindicar que esta cerrado
		this.getDescendants(id_concepto).hide()
		.find('.handle').removeClass('icon-minus').addClass('icon-plus');
	},

	showDescendants: function(id_concepto) {
		// muestra solo los descendientes inmediatos de un concepto
		var nivel_ancestro = this.getNivelConcepto(id_concepto);
		
		this.getDescendants(id_concepto).filter(function() {
			return $(this).attr('data-nivel').indexOf(nivel_ancestro) === 0 &&
				$(this).attr('data-nivel').length === nivel_ancestro.length + 4 ?
				true : false
		}).show();
	},

	haveDescendants: function(id_concepto) {
		// Determina si el concepto ya tiene descendientes cargados
		return this.getDescendants(id_concepto).length;
	},

	toggleMarcaConcepto: function($element) {
		$element.toggleClass('icon-checkbox-unchecked icon-checkbox-checked');
		$element.parents('tr').toggleClass('selected');
	},

	marcaConcepto: function(id_concepto) {
		this.getConceptoNode(id_concepto).addClass('selected');
	},

	desmarcaConceptos: function() {
		this.$table.find('tr.selected').removeClass('selected');
		this.$table
		.find('.check.icon-checkbox-checked')
		.toggleClass('icon-checkbox-checked icon-checkbox-unchecked');
	},

	getConceptoNode: function(id_concepto) {
		return $('#c-' + id_concepto);
	},

	getConceptoHandleNode: function(id_concepto) {
		return this.getConceptoNode(id_concepto).find('.handle');
	},

	getConceptosSeleccionadosDom: function() {
		return this.$table.find('.concepto.selected');
	},

	openConceptoPropertiesDialog: function() {
		$('#dialog-propiedades-concepto').dialog('open');
	},

	getDatosConcepto: function(id_concepto) {
		var that = this;

		DATA_LOADER.show();

		$.ajax({
			url: that.controller_url,
			data: {
				IDProyecto: that.getIDProyecto(),
				id_concepto: id_concepto,
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

	fillConceptoProperties: function(data, id_concepto) {
		$('#dialog-propiedades-concepto')
		.dialog({ title: 'Propiedades de: ' + data.descripcion })

		$('#txtDescripcion').val(data.descripcion);
		$('#txtAgrupadorPartida').val(data.agrupador_partida);
		$('#txtAgrupadorSubpartida').val(data.agrupador_subpartida);
		$('#txtAgrupadorActividad').val(data.agrupador_actividad);
		$('#txtAgrupadorTramo').val(data.agrupador_tramo);
		$('#txtAgrupadorSubtramo').val(data.agrupador_subtramo);
	},

	getConceptosSeleccionados: function() {
		var conceptos = [];

		this.getConceptosSeleccionadosDom().map(function(index, domElement){

			conceptos.push({
				'id_concepto': parseInt(this.id.split('-')[1])
			});
		});

		return conceptos;
	},

	setClaveConcepto: function(id_concepto, clave) {
		var that = this;

		DATA_LOADER.show();

		$.ajax({
			type: 'POST',
			url: that.controller_url,
			data: {
				IDProyecto: that.getIDProyecto(),
				id_concepto: id_concepto,
				clave: clave,
				action: 'setClaveConcepto'
			},
			dataType: 'json'
		})
		.done( function(data) {
			if( !data.success ) {
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