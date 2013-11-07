Presupuesto = {

	controller_url: 'inc/lib/controllers/PresupuestoObraController.php',

	init: function() {
		var that = this;

		$('#bl-proyectos').buttonlist({
			source: 'inc/lib/controllers/ListaProyectosController.php',
			data: {action: 'getListaProyectos'},
			onSelect: function( selectedItem, listItem ) {
				that.getConceptos();
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

		$('#tabla-conceptos').on('click', '.handle', function(event) {
			var id_concepto = parseInt($(this).parents('tr').attr('id').split('-')[1]);
			that.getConceptos(id_concepto);
			event.preventDefault();
		});

		$('#dialog-propiedades-concepto').dialog({
			autoOpen: false,
			modal: true,
			width: '550px',
			// closeOnEscape: false,
			close: function() {
				that.desmarcaConceptos();
			}
		});

		$('#tabla-conceptos').on('click', '.descripcion', function(event) {
			var id_concepto = parseInt($(this).parents('tr').attr('id').split('-')[1]);
			that.marcaConcepto(id_concepto);
			that.getDatosConcepto(id_concepto);
			event.preventDefault();
			event.stopPropagation();
		});

		// $('#tabla-conceptos').on('click', 'tr.concepto', function(event) {
		// 	var id_concepto = parseInt($(this).attr('id').split('-')[1]);
		// 	that.toggleMarcaConcepto(id_concepto);
		// 	console.log('asd');
		// });

		$("#txtAgrupadorPartida").autocomplete({
		    minLength: 1,
		    source: function(request, response) {
				request.IDProyecto = Presupuesto.getIDProyecto();
				request.action = 'getAgrupadoresPartida';
				that.requestListaAgrupadores(request, response);
			},
		    select: function( event, ui ) {
		    	that.setAgrupadorPartida(ui.item.id)
		    }
		});

		$("#txtAgrupadorSubpartida").autocomplete({
		    minLength: 1,
		    source: function(request, response) {
				request.IDProyecto = Presupuesto.getIDProyecto();
				request.action = 'getAgrupadoresSubpartida';
				that.requestListaAgrupadores(request, response);
			},
		    select: function( event, ui ) {
		    	that.setAgrupadorSubpartida(ui.item.id)
		    }
		});

		$("#txtAgrupadorActividad").autocomplete({
		    minLength: 1,
		    source: function(request, response) {
				request.IDProyecto = Presupuesto.getIDProyecto();
				request.action = 'getAgrupadoresActividad';
				that.requestListaAgrupadores(request, response);
			},
		    select: function( event, ui ) {
		    	that.setAgrupadorActividad(ui.item.id)
		    }
		});

		$('#cerrar-concepto').on('click', function(){
			$('#dialog-propiedades-concepto').dialog('close');
		});
	},

	requestListaAgrupadores: function(request, response) {
		var that = this;

		$.getJSON( that.controller_url, request, function( data, status, xhr ) {
			var agrupadores = [];
            
            for( i = 0; i < data.agrupadores.length; i++ ) {
			   agrupadores.push({
			   		id: data.agrupadores[i].id_agrupador,
			   		label: data.agrupadores[i].agrupador
			   	});
			}

			response( agrupadores );
		});
	},

	getIDProyecto: function() {
		return $('#bl-proyectos').buttonlist('option', 'selectedItem').value;
	},

	getConceptos: function(id_concepto) {
		var that = this;

		id_concepto = id_concepto || null;

		console.log(that.isNodeOpen(id_concepto));

		if ( ! that.isNodeOpen(id_concepto) ) {
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
			.done( function(data){
				that.fillConceptos(data.conceptos, id_concepto);
				that.toggleConceptoHandle(id_concepto);
			})
			.always(function(){ DATA_LOADER.hide(); })
		} else {
			that.removeChildren(id_concepto);
			that.toggleConceptoHandle(id_concepto);
		}
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
			descripcion = '<td style="padding-left: ' + data.numero_nivel + 'em"><a href="#" title="'+ data.descripcion +'" class="descripcion">' + data.descripcion + '</a></td>',
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
			'<tr id="c-' + data.id_concepto + '" data-numeronivel="' + data.numero_nivel + '" class="concepto">'
			+ 	concepto_icon
			+ 	handle
			+ 	'<td>' + data.clave_concepto + '</td>'
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

	removeChildren: function(id_concepto) {

		numero_nivel_ancestro = parseInt(this.getConceptoNode(id_concepto).attr('data-numeronivel'));

		descendientes = this.getConceptoNode(id_concepto).nextAll();
		console.log(descendientes)
		for (var i = 0; i < descendientes.length; i++) {
			descendiente = $(descendientes[i]);
			if( parseInt(descendiente.attr('data-numeronivel')) > numero_nivel_ancestro )
				descendiente.remove();
		};
	},

	toggleMarcaConcepto: function(id_concepto) {
		this.getConceptoNode(id_concepto).toggleClass('selected');
	},

	marcaConcepto: function(id_concepto) {
		this.getConceptoNode(id_concepto).addClass('selected');
	},

	desmarcaConceptos: function(id_concepto) {
		$('#tabla-conceptos').find('tr.selected').removeClass('selected');
	},

	getConceptoNode: function(id_concepto) {
		return $('#c-' + id_concepto);
	},

	getConceptoHandleNode: function(id_concepto) {
		return this.getConceptoNode(id_concepto).find('.handle');
	},

	getConceptosSeleccionadosDom: function() {
		return $('#tabla-conceptos').find('.concepto.selected');
	},

	// getConceptoDescripcion: function(id_concepto) {
	// 	return this.getConceptoNode(id_concepto).find('.descripcion').text();
	// },

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

	setAgrupadorPartida: function(id_agrupador) {

		DATA_LOADER.show();
		
		var request = {};
		
		request.action = 'setAgrupadorPartida';
		request.conceptos = this.getConceptosSeleccionados();
		request.id_agrupador = id_agrupador;

		this.requestSetAgrupador(request, DATA_LOADER.hide);
	},

	setAgrupadorSubpartida: function(id_agrupador) {
		DATA_LOADER.show();
		
		var request = {};
		
		request.action = 'setAgrupadorSubpartida';
		request.conceptos = this.getConceptosSeleccionados();
		request.id_agrupador = id_agrupador;

		this.requestSetAgrupador(request, DATA_LOADER.hide);
	},

	setAgrupadorActividad: function(id_agrupador) {
		DATA_LOADER.show();
		
		var request = {};
		
		request.action = 'setAgrupadorActividad';
		request.conceptos = this.getConceptosSeleccionados();
		request.id_agrupador = id_agrupador;

		this.requestSetAgrupador(request, DATA_LOADER.hide);
	},

	requestSetAgrupador: function(request, callback) {
		request.IDProyecto = this.getIDProyecto();

		$.ajax({
			type: 'POST',
			url: this.controller_url,
			data: request,
			dataType: 'json'
		})
		.done( function(data) {
			if ( data.success ) {
				messageConsole.displayMessage('Agrupador asignado correctamente.', 'success');
			} else {
				messageConsole.displayMessage(data.message, 'error');
			}
		})
		.always(callback);
	}
}

Presupuesto.init();