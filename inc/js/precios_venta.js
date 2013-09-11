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

	init: function() {

		var that = this;

		// Suscripcion al evento transaccion modificada
		var modifiedTranSubscription = pubsub.subscribe('modified_tran', modifiedTran);
		// Suscripcion al evento que notifica cuando la transaccion tiene cambios por guardar
		var notifyModifiedTranSubs = pubsub.subscribe('notify_modtran', notifyModifiedTran);

		$('#tabla-conceptos').on('keyup', 'input[type=text]', function() {

		    var oldValue = $(this).val();
		    
		    $(this).val(oldValue.numFormat());
		});

		$('#bl-proyectos').buttonlist({
			source: 'inc/lib/controllers/ListaProyectosController.php',
			data: { action: 'getListaProyectos'},
			onSelect: function( selectedItem, listItem ) {
				
				that.cargaTransaccion();
			},
			didNotDataFound: function() {
				$.notify({text: 'No se pudo cargar la lista de proyectos.'});
			},
			onCreateListItem: function() {
				return {
					id: this.IDProyecto,
					value: this.NombreProyecto
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

	getIDProyecto: function() {
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
			type: 'GET',
			url: that.urls.tranController,
			data: {
				IDProyecto: that.getIDProyecto(),
				action: 'getPreciosVenta'
			},
			dataType: 'json'
		}).done( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage(json.message, 'error');
					return;
				}

				if( json.noRows ) {
					$.notify({text: json.message});
					return;
				}

				// llena la tabla de conceptos
				that.llenaTablaConceptos( json.conceptos );

			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		})
		.fail( function() {
			$.notify({text: 'Ocurrió un error al cargar la transaccion.'});
		})
		.always( function() {
			DATA_LOADER.hide();
		});
	},

	llenaTablaConceptos: function( conceptos ) {

		var rows = '',
			cellType = 'td',
			precioProduccion = '',
			precioEstimacion = '',
			fechaModificacion = '';

		$.each( conceptos, function() {

			if ( this.EsActividad ) {
				cellType = 'td';
				precioProduccion  = this.PrecioProduccion;
				precioEstimacion = this.PrecioEstimacion;

				if ( this.ConPrecio ) {
					fechaModificacion = this.FechaUltimaModificacion;
				} else
					fechaModificacion = '';
			}
			else {
				cellType = 'th';
				precioProduccion  = '';
				precioEstimacion = '';
				fechaModificacion = '';
			}

			rows +=
				 '<tr data-id="' + this.IDConcepto + '" data-esactividad="' + this.EsActividad + '">'
				+  '<td class="icon-cell"><a class="icon fixed"></a></td>'
				+  '<' + cellType + ' title="' + this.Descripcion + '">'
				+  '&nbsp;&nbsp;'.repeat(this.NumeroNivel) + this.Descripcion + ' </' + cellType + '>'
				+  '<td class="centrado">' + this.Unidad + '</td>'
				+  '<td class="editable-cell numerico">' + precioProduccion + '</td>'
				+  '<td class="editable-cell numerico">' + precioEstimacion + '</td>'
				+  '<td class="centrado">' + fechaModificacion + '</td>'
				+ '</tr>';
		});
		
		$('#tabla-conceptos tbody').html( rows );
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

		var IDProyecto = that.getIDProyecto(),
			conceptos = that.getConceptosModificados();

		DATA_LOADER.show();

		$.ajax({
			type: 'POST',
			url: that.urls.tranController,
			data: {
				IDProyecto: IDProyecto,
				conceptos: conceptos,
				action: 'setPreciosVenta'
			},
			dataType: 'json'
		})
		.done( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage( json.message, 'error' );
					return;
				}

				$('#guardar').removeClass('alert');

				messageConsole.displayMessage( 'La transacción se guardó correctamente.', 'success' );
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		})
		.always( function() {
			DATA_LOADER.hide();
		});
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

		//if( typeof data === 'object' )
			data.call(PRECIOS_VENTA);
	}
}