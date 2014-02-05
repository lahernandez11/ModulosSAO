$( function() {	
	PRECIOS_VENTA.init();
});

var pubsub = PubSub();

var PRECIOS_VENTA = {

	classes: {
		conceptoModificado: 'modificado'
	},
	urls: {
		tranController: 'inc/lib/controllers/PrecioVentaController.php'
	},
	conceptoTemplate: null,

	init: function() {

		var that = this;
		
		this.conceptoTemplate = _.template($('#concepto-template').html());

		// Suscripcion al evento transaccion modificada
		var modifiedTranSubscription = pubsub.subscribe('modified_tran', modifiedTran);
		// Suscripcion al evento que notifica cuando la transaccion tiene cambios por guardar
		var notifyModifiedTranSubs = pubsub.subscribe('notify_modtran', notifyModifiedTran);

		$('#tabla-conceptos').on('keyup', 'input[type=text]', function() {

		    var oldValue = $(this).val();
		    
		    $(this).val(oldValue.numFormat());
		});

		$('#bl-proyectos').buttonlist({
			source: 'inc/lib/controllers/ListaObrasController.php',
			data: { action: 'getListaProyectos'},
			onSelect: function( selectedItem, listItem ) {
				
				that.cargaTransaccion();
			},
			didNotDataFound: function() {
				$.notify({text: 'No se pudo cargar la lista de proyectos.'});
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

		$('#tabla-conceptos').uxtable({
			editableColumns: {
				3: {
					'onFinishEdit': function( activeCell, value ) {

						var IDConcepto = parseInt( activeCell.parent().attr('data-id') );

						if ( parseInt(activeCell.parent().attr('data-esactividad')) == 1 ) {
							that.setPrecioProduccion.call( this, IDConcepto, value );

							pubsub.publish('modified_tran');
						}
					}
				},
				4: {
					'onFinishEdit': function( activeCell, value ) {

						var IDConcepto = parseInt( activeCell.parent().attr('data-id') );

						if ( parseInt(activeCell.parent().attr('data-esactividad')) == 1 ) {
							that.setPrecioEstimacion.call( this, IDConcepto, value );

							pubsub.publish('modified_tran');
						}
					}
				}
			}
		});
		

		$('#guardar').on('click', function() {
			that.guardaTransaccion();
		});

		this.limpiaDatosTransaccion();
	},

	getBaseDatos: function() {
		return $('#bl-proyectos').buttonlist('option', 'selectedItem').extra.source
	},

	getIDObra: function() {
		return $('#bl-proyectos').buttonlist('option', 'selectedItem').value;
	},

	getIDTransaccion: function() {
		return $('#IDListaPreciosVenta').val();
	},

	limpiaDatosTransaccion: function() {
		$('#tabla-conceptos tbody').empty();
		$('#guardar').removeClass('alert');
	},

	setPrecioProduccion: function( IDConcepto, precio ) {
		PRECIOS_VENTA.marcaConcepto( IDConcepto );
		this.uxtable('getCell', 3).text( PRECIOS_VENTA.validaPrecio(precio).toFixed(4).numFormat() );
	},

	setPrecioEstimacion: function( IDConcepto, precio ) {
		PRECIOS_VENTA.marcaConcepto( IDConcepto );
		this.uxtable('getCell', 4).text( PRECIOS_VENTA.validaPrecio(precio).toFixed(4).numFormat() );
	},

	validaPrecio: function( precio ) {

		return parseFloat(precio.replace(/,/g, '')) || 0;
	},

	cargaTransaccion: function() {
		
		var that = this;

		that.limpiaDatosTransaccion();

		DATA_LOADER.show();

		$.ajax({
			url: that.urls.tranController,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIDObra(),
				action: 'getPreciosVenta'
			},
			dataType: 'json'
		}).done( function( data ) {
			try {

				if( ! data.success ) {
					messageConsole.displayMessage(data.message, 'error');
					return;
				}

				if( data.noRows ) {
					$.notify({text: data.message});
					return;
				}

				// llena la tabla de conceptos
				that.renderConceptos( data.conceptos );

			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		})
		.always( DATA_LOADER.hide );
	},

	renderConceptos: function( conceptos ) {
		var html = '';

		for( var i = 0; i < conceptos.length; i++ ) {
			console.log(conceptos[i]);
			html += this.conceptoTemplate( conceptos[i] );
		}

		$('#tabla-conceptos tbody').html( html );
	},

	getConceptosModificados: function() {

		var conceptos = [],
			row = null;

		$('#tabla-conceptos tr.' + this.classes.conceptoModificado).each( function() {			
			row = $(this);

			conceptos[conceptos.length] = {

				'IDConcepto': row.attr('data-id'),
				'precioProduccion': row.children(':eq(3)').text(),
				'precioEstimacion': row.children(':eq(4)').text(),
			}
		});

		return conceptos;
	},

	guardaTransaccion: function() {

		var that = this;

		DATA_LOADER.show();

		$.ajax({
			type: 'POST',
			url: that.urls.tranController,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIDObra(),
				conceptos: that.getConceptosModificados(),
				action: 'setPreciosVenta'
			},
			dataType: 'json'
		})
		.done( function( data ) {
			try {

				if( ! data.success ) {
					messageConsole.displayMessage( data.message, 'error' );
					return;
				}

				$('#guardar').removeClass('alert');

				messageConsole.displayMessage( 'La transacción se guardó correctamente.', 'success' );
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		})
		.always( DATA_LOADER.hide );
	},

	marcaConcepto: function( IDConcepto ) {
		
		$('tr[data-id=' + IDConcepto + ']').addClass(this.classes.conceptoModificado);
	},

	desmarcaConcepto: function( IDConcepto ) {

		$('tr[data-id=' + IDConcepto + ']').removeClass(this.classes.conceptoModificado);
	},

	identificaModificacion: function() {
		$('#guardar').addClass('alert');
	},

	existenCambiosSinGuardar: function() {
		
		return $('#guardar').hasClass('alert');
	}
};

// funciones Mediators que llamaran las notificaciones
var modifiedTran = function( event, data ) {
	PRECIOS_VENTA.identificaModificacion();
};

var notifyModifiedTran = function( event, data ) {
	
	if( confirm('Existen cambios sin guardar, desea continuar?...') ) {

		if( typeof data === 'function' )
			data.call( PRECIOS_VENTA );
	}
}