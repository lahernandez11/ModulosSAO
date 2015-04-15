var pubsub = PubSub();

var ESTIMACION = {

	requestingData: false,
	classes: {
		conceptoModificado: 'modificado'
	},
	urls: {
		tranController: 'inc/lib/controllers/EstimacionObraController.php'
	},
	conceptoTemplate: null,

	init: function() {

		var that = this;

		this.conceptoTemplate = _.template($('#concepto-template').html());

		// Suscripcion al evento transaccion modificada
		var modifiedTranSubscription = pubsub.subscribe('modified_tran', modifiedTran);
		// Suscripcion al evento que notifica cuando la transaccion tiene cambios por guardar
		var notifyModifiedTranSubs = pubsub.subscribe('notify_modtran', notifyModifiedTran);

		// Da formato al numero ingresado en un input text de la tabla de conceptos
		$('#tabla-conceptos').on('keyup', 'input[type=text]', function(event) {
		    var rowKeys = [38, 39, 40, 37],
		    	oldValue = $(this).val();

		    if ( rowKeys.indexOf(event.keyCode) < 0 )
		    	$(this).val(oldValue.numFormat());
		});

		$('#bl-proyectos').buttonlist({
			source: 'inc/lib/controllers/ListaObrasController.php',
			data: { action: 'getListaProyectos'},
			onSelect: function( selectedItem, listItem ) {
				
				$('#folios-transaccion')
				.buttonlist('option', 'data', {
					base_datos: that.getBaseDatos(),
					id_obra: selectedItem.value,
					action: 'getFoliosTransaccion'
				});

				$('#folios-transaccion').buttonlist('refresh');

				$('#btnLista-transacciones').listaTransacciones('option', 'data', {
					base_datos: that.getBaseDatos(),
					id_obra: selectedItem.value,
					action: 'getListaTransacciones'
				});
				
				that.limpiaDatosTransaccion();
				that.deshabilitaCamposTransaccion();
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

		$('#folios-transaccion').buttonlist({
			source: that.urls.tranController,
			beforeLoad: function() {

				if( ! that.getIDObra() ) {

					$.notify({text: 'Seleccione un proyecto para cargar los folios.'});
					return false;
				} else
					return true;
			},
			onSelect: function( selectedItem, listItemElement ) {
				that.cargaTransaccion();
			},
			didNotDataFound: function() {
				$.notify({text: 'No se encontraron transacciones registradas en este proyecto.'});
			},
			onCreateListItem: function() {
				return {
					id: this.id_transaccion,
					value: this.numero_folio
				}
			}
		});

		$('#btnLista-transacciones').listaTransacciones({
			source: that.urls.tranController,
			data: { action: 'getListaTransacciones'},
			onLoad: function() { DATA_LOADER.show() },
			beforeLoad: function() {

				if( ! that.getIDObra() ) {
					$.notify({text: 'Seleccione un proyecto para cargar las transacciones.'});
					return false;
				} else
					return true;
			},
			onSelectItem: function( item ) {
				$('#folios-transaccion').buttonlist('selectItemById', item.value, true );
			},
			onCreateListItem: function() {
				return {
					id: this.IDTransaccion,
					folio: this.NumeroFolio,
					fecha: this.Fecha,
					observaciones: this.Observaciones
				};
			},
			onFinishLoad: function() { DATA_LOADER.hide() }
		});

		$('#tabla-conceptos').uxtable({
			editableColumns: {
				5: {
					'onFinishEdit': function( activeCell, value ) {

						var IDConcepto = parseInt( activeCell.parent().attr('data-id') ),
							row = activeCell.parents('tr');

						if ( parseInt(activeCell.parent().attr('data-esactividad')) == 1 ) {

							that.setCantidadEstimada.call( this, IDConcepto, value );
							that.setMontoTotal(row);
						}						
						
						pubsub.publish('modified_tran');
					}
				},
				6: {
					'onFinishEdit': function( activeCell, value ) {

						var IDConcepto = parseInt( activeCell.parent().attr('data-id') ),
							row = activeCell.parents('tr');

						if ( parseInt(activeCell.parent().attr('data-esactividad')) == 1 ) {
							
							that.setPrecio.call( this, IDConcepto, value );
							that.setMontoTotal(row);
						}

						pubsub.publish('modified_tran');
					}
				}
			}
		})
		.on( 'click', '.icon.action', function(event) {

			var IDConcepto = parseInt( $(this).parents('tr').attr('data-id') );

			if( $(this).hasClass('checkbox') ) {

				$(this).toggleClass('checkbox-unchecked checkbox-checked');
				that.marcaConcepto( IDConcepto );
			}
		});
		
		$('#nuevo').on('click', function(event) {

			if( that.existenCambiosSinGuardar() )
				pubsub.publish('notify_modtran', that.nuevaTransaccion);
			else
				that.nuevaTransaccion();

			event.preventDefault();
		});

		$('#eliminar').on('click', function() {
			that.eliminaTransaccion();
		});

		$('#guardar').on('click', function() {
			that.guardaTransaccion();
		});

		$('#aprobar').on('click', function() {
			that.apruebaTransaccion();
		});

		$('#revierte-aprobacion').on('click', function() {
			that.revierteAprobacion();
		});

		$('#txtFecha, #txtFechaInicio, #txtFechaTermino')
		.datepicker({
			dateFormat: 'dd-mm-yy',
			altFormat: 'yy-mm-dd',
			altField: '#txtFechaDB',
			showOtherMonths: "true",
			selectOtherMonths: "true",
			buttonImage: "img/app/calendar_light-green_16x16.png",
			showOn: "both",
			buttonImageOnly: true,
			onSelect: function() {
				pubsub.publish('modified_tran');
			}
		})
		 .datepicker( 'setDate', new Date() )
		 .datepicker('disable');

		$('#txtFechaInicio')
		 .datepicker( 'option', 'altField', '#txtFechaInicioDB' );
		 
		$('#txtFechaTermino')
		 .datepicker( 'option', 'altField', '#txtFechaTerminoDB' )

		$('#txtObservaciones').on('change', function(){
			pubsub.publish('modified_tran');
		});

		this.deshabilitaCamposTransaccion();
		this.limpiaDatosTransaccion();
	},

	nuevaTransaccion: function() {

		var that = this;

		if ( ! this.getIDObra() ) {
			$.notify({text: 'Seleccione un proyecto'});
			return;
		}

		this.limpiaDatosTransaccion();
		this.habilitaCamposTransaccion();
		$('#folios-transaccion').buttonlist('reset');

		DATA_LOADER.show();

		$.ajax({
			url: that.urls.tranController,
			data: {
				base_datos: this.getBaseDatos(),
				id_obra: this.getIDObra(),
				action: 'nuevaTransaccion'
			},
			dataType: 'json'
		})
		.done( function( data ) {

			if ( ! data.success ) {
				messageConsole.displayMessage( data.message, 'error' );
			}

			that.renderConceptos( data.conceptos );
		})
		.always( DATA_LOADER.hide );
	},

	getBaseDatos: function() {
		return $('#bl-proyectos').buttonlist('option', 'selectedItem').extra.source
	},

	getIDObra: function() {
		return $('#bl-proyectos').buttonlist('option', 'selectedItem').value;
	},

	getIDTransaccion: function() {
		return $('#folios-transaccion').buttonlist('option', 'selectedItem').value;
	},

	deshabilitaFechaTransaccion: function() {
		$('#txtFecha').datepicker('disable');
	},

	deshabilitaCamposTransaccion: function() {
		this.deshabilitaFechaTransaccion();
		$('#txtFechaInicio').datepicker('disable');
		$('#txtFechaTermino').datepicker('disable');
		$('#txtObservaciones').prop('disabled', true).addClass('disabled');
		$('#txtReferencia').prop('disabled', true).addClass('disabled');
	},

	habilitaCamposTransaccion: function() {
		$('#txtFecha').datepicker('enable');
		$('#txtFechaInicio').datepicker('enable');
		$('#txtFechaTermino').datepicker('enable');
		$('#txtObservaciones').prop('disabled', false).removeClass('disabled');
		$('#txtReferencia').prop('disabled', false).removeClass('disabled');
	},

	limpiaDatosTransaccion: function() {
		$('#tabla-conceptos tbody').empty();
		$('#txtFecha').datepicker( 'setDate', new Date() );
		$('#txtFechaInicio').datepicker( 'setDate', new Date() );
		$('#txtFechaTermino').datepicker( 'setDate', new Date() );
		$('#txtObservaciones').val('');
		$('#txtReferencia').val('');
		$('#txtSubtotal, #txtIVA, #txtTotal').text('');
		$('#guardar').removeClass('alert');
	},

	fillDatosGenerales: function( data ) {
		// Establece los datos generales
		$('#txtFecha').datepicker( 'setDate', data.Fecha );
		$('#txtFechaInicio').datepicker( 'setDate', data.FechaInicio );
		$('#txtFechaTermino').datepicker( 'setDate', data.FechaTermino );
		$('#txtObservaciones').val( data.Observaciones );
		$('#txtReferencia').val( data.Referencia );
	},

	fillTotales: function( totales ) {
		// Establece los totales de transaccion
		if( totales.length ) {
			this.setSubtotal(totales[0].Subtotal);
			this.setIVA(totales[0].IVA);
			this.setTotal(totales[0].Total);
		}
	},
	
	getTotalesTransaccion: function() {

		var that = this;

		var request =
		$.ajax({
			url: that.urls.tranController,
			data: {
				base_datos: this.getBaseDatos(),
				id_obra: this.getIDObra(),
				id_transaccion: this.getIDTransaccion(),
				action: 'getTotalesTransaccion'
			},
			dataType: 'json'
		}).done( function( data ) {
			try {

				if( ! data.success ) {
					messageConsole.displayMessage(data.message, 'error');
					return;
				}

				that.fillTotales( data.totales );

			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		});
	},

	setSubtotal: function( $monto ) {
		$('#txtSubtotal').text($monto);
	},

	setIVA: function( $monto ) {
		$('#txtIVA').text($monto);
	},

	setTotal: function( $monto ) {
		$('#txtTotal').text($monto);
	},

	setCantidadEstimada: function( IDConcepto, cantidad ) {

		var cantidad = parseFloat(cantidad.replace(/,/g, '')) || 0;

		//if ( cantidad.length > 0 || cantidad != 0 )
			ESTIMACION.marcaConcepto( IDConcepto );
		//else
			//ESTIMACION.desmarcaConcepto( IDConcepto );

		this.uxtable('getCell', 5).text( cantidad.toFixed(4).numFormat() );
	},

	setPrecio: function( IDConcepto, precio ) {

		var pu = parseFloat(precio.replace(/,/g, '')) || 0;

		//if ( cantidad.length > 0 || cantidad != 0 )
			ESTIMACION.marcaConcepto( IDConcepto );
		//else
			//ESTIMACION.desmarcaConcepto( IDConcepto );

		this.uxtable('getCell', 6).text( pu.toFixed(4).numFormat() );
	},

	cleanCantidad: function(text) {
		return text.replace(/,/gi, "");
	},

	setMontoTotal: function($row) {
		var that = this,
			cantidad = parseFloat(that.cleanCantidad($row.find('.cantidad').text())),
			precio = parseFloat(that.cleanCantidad($row.find('.precio').text()));
console.log(cantidad);
console.log(precio);
			monto = cantidad * precio;

		$row.find('.total').text(monto.toFixed(2).toString().numFormat());
	},

	cargaTransaccion: function() {
		
		var that = this;

		that.deshabilitaCamposTransaccion();
		that.limpiaDatosTransaccion();

		DATA_LOADER.show();

		$.ajax({
			url: that.urls.tranController,
			data: {
				base_datos: this.getBaseDatos(),
				id_obra: this.getIDObra(),
				id_transaccion: that.getIDTransaccion(),
				action: 'getDatosTransaccion'
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

				that.fillDatosGenerales( data.datos );

				that.renderConceptos( data.conceptos );

				that.fillTotales( data.totales );

				that.habilitaCamposTransaccion();
				that.deshabilitaFechaTransaccion();

			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		})
		.always( function() {
			DATA_LOADER.hide();
		});
	},

	renderConceptos: function( conceptos ) {
		var html = '';

		for (var i = 0; i < conceptos.length; i++) {
			html += this.conceptoTemplate(conceptos[i]);
		};

		$('#tabla-conceptos tbody').html( html );
	},

	guardaTransaccion: function() {

		var that = this;

		DATA_LOADER.show();

		that.desmarcaConceptosError();

		var requestData = {
			base_datos: that.getBaseDatos(),
			id_obra: that.getIDObra(),
			datosGenerales: {
				'fecha': $('#txtFechaDB').val(),
				'fechaInicio': $('#txtFechaInicioDB').val(),
				'fechaTermino': $('#txtFechaTerminoDB').val(),
				'referencia': $('#txtReferencia').val(),
				'observaciones': $('#txtObservaciones').val()
			},
			conceptos: that.getConceptosModificados(),
			action: 'guardaTransaccion'
		}

		if ( that.getIDTransaccion() ) {
			requestData.id_transaccion = that.getIDTransaccion()
		}

		$.ajax({
			type: 'POST',
			url: that.urls.tranController,
			data: requestData,
			dataType: 'json'
		}).done( function( data ) {

			if( ! data.success ) {
				messageConsole.displayMessage( data.message, 'error' );
				return;
			}

			if ( ! that.getIDTransaccion() ) {
				$('#folios-transaccion').buttonlist('addListItem', 
					{id: data.id_transaccion, text: data.numero_folio}, 'start');
				
				$('#folios-transaccion').buttonlist('setSelectedItemById', 
					data.id_transaccion, false );
				
				that.deshabilitaFechaTransaccion();
			}

	 		that.fillTotales(data.totales);

	 		if ( data.errores.length > 0 ) {
	 			that.marcaConceptosError(data.errores);
	 			messageConsole.displayMessage( 'Existen errores en algunos conceptos, por favor revise y guarde otra vez.', 'error');
	 		} else {
	 			$('#guardar').removeClass('alert');
	 			messageConsole.displayMessage( 'La transacción se guardó correctamente.', 'success');
	 		}
	 		
		}).always( DATA_LOADER.hide );
	},

	getConceptosModificados: function() {

		var conceptos = [],
			row = null;

		$('#tabla-conceptos tr.' + this.classes.conceptoModificado).each( function() {			
			row = $(this);

			conceptos[conceptos.length] = {

				'IDConcepto': row.attr('data-id'),
				'cantidad': row.children(':eq(5)').text(),
				'precio': row.children(':eq(6)').text(),
				'cumplido': (row.find('td.cumplido a.checkbox').hasClass('checkbox-checked') ? 1: 0)
			}
		});

		return conceptos;
	},

	eliminaTransaccion: function() {

		var that = this;

		if ( ! this.getIDTransaccion() ) {
			return;
		}

		if ( ! confirm('La transacción será eliminada, desea continuar?') )
			return;

		DATA_LOADER.show();

		$.ajax({
			type: 'POST',
			url: that.urls.tranController,
			data: {
				base_datos: this.getBaseDatos(),
				id_obra: this.getIDObra(),
				id_transaccion: that.getIDTransaccion(),
				action: 'eliminaTransaccion'
			},
			dataType: 'json'
		}).done( function( data ) {
			try {

				if( ! data.success ) {
					messageConsole.displayMessage( data.message, 'error' );
					return;
				}

				that.deshabilitaCamposTransaccion();
				that.limpiaDatosTransaccion();
				$('#folios-transaccion').buttonlist('reset');
				$('#folios-transaccion').buttonlist('refresh');

				messageConsole.displayMessage( 'La transacción se eliminó correctamente.', 'success' );

			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		}).always( function() {	DATA_LOADER.hide();	});
	},

	apruebaTransaccion: function() {

		var that = this;

		if ( ! confirm('La transacción será aprobada, desea continuar?') )
			return;

		DATA_LOADER.show();

		$.ajax({
			type: 'POST',
			url: that.urls.tranController,
			data: {
				base_datos: this.getBaseDatos(),
				id_obra: this.getIDObra(),
				id_transaccion: that.getIDTransaccion(),
				action: 'apruebaTransaccion'
			},
			dataType: 'json'
		}).done( function( data ) {
			try {

				if( ! data.success ) {
					messageConsole.displayMessage( data.message, 'error' );
					return;
				}

				messageConsole.displayMessage( 'La transacción se aprobó correctamente.', 'success' );
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		}).always( DATA_LOADER.hide );
	},

	revierteAprobacion: function() {

		var that = this;

		if ( ! confirm('La aprobación será revertida, desea continuar?') )
			return;

		DATA_LOADER.show();

		$.ajax({
			type: 'POST',
			url: that.urls.tranController,
			data: {
				base_datos: this.getBaseDatos(),
				id_obra: this.getIDObra(),
				id_transaccion: that.getIDTransaccion(),
				action: 'revierteAprobacion'
			},
			dataType: 'json'
		}).done( function( data ) {
			try {

				if( ! data.success ) {
					messageConsole.displayMessage( data.message, 'error' );
					return;
				}

				messageConsole.displayMessage( 'La aprobación se revirtió correctamente.', 'success' );
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		}).always( DATA_LOADER.hide );
	},

	marcaConceptosError: function( errores ) {

		for( error in errores ) {

			$('tr[data-id=' + errores[error].IDConcepto + ']')
			.addClass('error')
			.find('.icon')
			.addClass('error')
			.attr('title', errores[error].message);
		}
	},

	desmarcaConceptosError: function() {
		$('#tabla-conceptos')
		.find('tr.modificado')
		.removeClass('error')
		.find('.icon')
		.removeClass('error')
		.removeAttr('title');
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
	ESTIMACION.identificaModificacion();
};

var notifyModifiedTran = function( event, data ) {
	
	if( confirm('Existen cambios sin guardar, desea continuar?...') ) {

		if( typeof data === 'function' )
			data.call(ESTIMACION);
	}
}

ESTIMACION.init();