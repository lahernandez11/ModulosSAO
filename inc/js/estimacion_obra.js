$( function() {
	
	ESTIMACION.init();
});

var pubsub = PubSub();

var ESTIMACION = {

	requestingData: false,
	classes: {
		conceptoModificado: 'modificado'
	},
	urls: {
		tranController: 'inc/lib/controllers/EstimacionObraController.php'
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
				
				$('#folios-transaccion')
				.buttonlist('option', 'data', {IDProyecto: selectedItem.value, action: 'getFoliosTransaccion'});

				$('#folios-transaccion').buttonlist('refresh');

				$('#btnLista-transacciones').listaTransacciones('option', 'data', {IDProyecto: selectedItem.value, action: 'getListaTransacciones'});
				
				that.limpiaDatosTransaccion();
				that.deshabilitaCamposTransaccion();
			},
			didNotDataFound: function() {
				$.notify({text: 'No se pudo cargar la lista de proyectos'});
			},
			onCreateListItem: function() {
				return {
					id: this.idProyecto,
					value: this.NombreProyecto
				}
			}
		});

		$('#folios-transaccion').buttonlist({
			source: that.urls.tranController,
			beforeLoad: function() {

				if( ! that.getIDProyecto() ) {

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
					id: this.IDTransaccion,
					value: this.NumeroFolio
				}
			}
		});

		$('#btnLista-transacciones').listaTransacciones({
			source: that.urls.tranController,
			data: { action: 'getListaTransacciones'},
			beforeLoad: function() {

				if( ! that.getIDProyecto() ) {
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
			}
		});

		$('#tabla-conceptos').uxtable({
			editableColumns: {
				5: {
					'onFinishEdit': function( activeCell, value ) {

						var IDConcepto = parseInt( activeCell.parent().attr('data-id') );

						if ( parseInt(activeCell.parent().attr('data-esactividad')) == 1 )
							that.setCantidadAvance.call( this, IDConcepto, value );

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
		
		$('#nueva-transaccion').on('click', function(event) {

			//that.nuevaTransaccion();
			
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

		$('#txtFechaTransaccion, #txtFechaInicio, #txtFechaTermino')
		.datepicker({
			dateFormat: 'dd-mm-yy',
			altFormat: 'yy-mm-dd',
			altField: '#txtFechaTransaccionDB',
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

		if ( ! this.getIDProyecto() ) {
			$.notify({text: 'Seleccione un proyecto'});
			return;
		}

		this.limpiaDatosTransaccion();
		this.habilitaCamposTransaccion();
		$('#folios-transaccion').buttonlist('reset');
	},

	getIDProyecto: function() {
		return $('#bl-proyectos').buttonlist('option', 'selectedItem').value;
	},

	getIDTransaccion: function() {
		return $('#folios-transaccion').buttonlist('option', 'selectedItem').value;
	},

	deshabilitaConceptoRaiz: function() {
		$('#txtConceptoRaiz').prop('disabled', true).addClass('disabled');
	},

	deshabilitaFechaTransaccion: function() {
		$('#txtFechaTransaccion').datepicker('disable');
	},

	deshabilitaCamposTransaccion: function() {
		this.deshabilitaFechaTransaccion();
		$('#txtFechaInicio').datepicker('disable');
		$('#txtFechaTermino').datepicker('disable');
		$('#txtObservaciones').prop('disabled', true).addClass('disabled');
		this.deshabilitaConceptoRaiz();
	},

	habilitaCamposTransaccion: function() {
		$('#txtConceptoRaiz').prop('disabled', false).removeClass('disabled');
		$('#txtFechaTransaccion').datepicker('enable');
		$('#txtFechaInicio').datepicker('enable');
		$('#txtFechaTermino').datepicker('enable');
		$('#txtObservaciones').prop('disabled', false).removeClass('disabled');
	},

	limpiaDatosTransaccion: function() {
		$('#tabla-conceptos tbody').empty();
		$('#txtFechaTransaccion').datepicker( 'setDate', new Date() );
		$('#txtFechaInicio').datepicker( 'setDate', new Date() );
		$('#txtFechaTermino').datepicker( 'setDate', new Date() );
		$('#txtObservaciones').val('');
		$('#txtSubtotal, #txtIVA, #txtTotal').text('');
		$('#guardar').removeClass('alert');
	},

	getTotalesTransaccion: function() {

		var that = this,
			IDTransaccion = this.getIDTransaccion();

		var request =
			$.ajax({
				type: 'GET',
				url: that.urls.tranController,
				data: {
					IDTransaccion: IDTransaccion,
					action: 'getTotalesTransaccion'
				},
				dataType: 'json'
			}).done( function( json ) {
				try {

					if( ! json.success ) {
						messageConsole.displayMessage(json.message, 'error');
						return;
					}

					if( json.totales.length ) {
						that.setSubtotal(json.totales[0].Subtotal);
						that.setIVA(json.totales[0].IVA);
						that.setTotal(json.totales[0].Total);
					}

				} catch( e ) {
					messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
				}
			}).fail( function() {
				$.notify({text: 'Ocurrió un error al cargar los totales.'});
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

	setCantidadAvance: function( IDConcepto, cantidad ) {

		var cantidad = parseFloat(cantidad.replace(/,/g, '')) || 0;

		//if ( cantidad.length > 0 || cantidad != 0 )
			ESTIMACION.marcaConcepto( IDConcepto );
		//else
			//ESTIMACION.desmarcaConcepto( IDConcepto );

		this.uxtable('getCell', 5).text( cantidad.toFixed(4).numFormat() );
	},

	cargaTransaccion: function() {
		
		var that = this;

		that.deshabilitaCamposTransaccion();
		that.limpiaDatosTransaccion();

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
			url: that.urls.tranController,
			data: {
				IDTransaccion: $('#folios-transaccion').buttonlist('option', 'selectedItem').value,
				action: 'getDatosTransaccion'
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

				// Establece los datos generales
				$('#txtFechaTransaccion').datepicker( 'setDate', json.datos.Fecha );
				$('#txtConceptoRaiz').val( json.datos.ConceptoRaiz );
				$('#txtObservaciones').val( json.datos.Observaciones );
				$('#txtFechaInicio').datepicker( 'setDate', json.datos.FechaInicio );
				$('#txtFechaTermino').datepicker( 'setDate', json.datos.FechaTermino );

				// llena la tabla de conceptos
				that.llenaTablaConceptos( json.conceptos );

				// Establece los totales de transaccion
				if( json.totales.length ) {
					that.setSubtotal(json.totales[0].Subtotal);
					that.setIVA(json.totales[0].IVA);
					that.setTotal(json.totales[0].Total);
				}

				that.habilitaCamposTransaccion();
				that.deshabilitaFechaTransaccion();
				that.deshabilitaConceptoRaiz();

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

	cargaConceptos: function() {
		
		var IDProyecto = this.getIDProyecto(),
			that = this;

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
			url: that.urls.tranController,
			data: {
				IDProyecto: IDProyecto,
				IDConceptoRaiz: IDConceptoRaiz,
				action: 'getConceptosNuevoAvance'
			},
			dataType: 'json'
		})
		.done( function( json ) {

			if ( ! json.success ) {
				messageConsole.displayMessage( json.message, 'error' );
			}

			that.llenaTablaConceptos( json.conceptos, DATA_LOADER.hide() );
		});
	},

	llenaTablaConceptos: function( conceptos, callback ) {

		var rows = '',
			cellType = 'td',
			cantidadPresupuestada = '',
			cantidadAvanceAnterior = '',
			cantidadAvanceActual = '',
			cantidadAvance = '';

		$.each( conceptos, function() {

			if ( this.EsActividad ) {
				cellType = 'td';
				cantidadPresupuestada  = this.CantidadPresupuestada;
				cantidadAvanceAnterior = this.CantidadAvanceAnterior;
				cantidadAvanceActual   = this.CantidadAvanceActual;
				cantidadAvance 		   = this.CantidadAvance;
			}
			else {
				cellType = 'th';
				cantidadPresupuestada  = '';
				cantidadAvanceAnterior = '';
				cantidadAvanceActual   = '';
				cantidadAvance 		   = '';
			}

			rows +=
				 '<tr data-id="' + this.IDConcepto + '" data-esactividad="' + this.EsActividad + '">'
				+  '<td class="icon-cell"><a class="icon fixed"></a></td>'
				+  '<' + cellType + ' title="' + this.Descripcion + '">'
				+  '&nbsp;&nbsp;'.repeat(this.NumeroNivel) + this.Descripcion + ' </' + cellType + '>'
				+  '<td class="centrado">' + this.Unidad + '</td>'
				+  '<td class="numerico">' + cantidadPresupuestada + '</td>'
				+  '<td class="numerico">' + cantidadAvanceAnterior + '</td>'
				+  '<td class="editable-cell numerico">' + cantidadAvance + '</td>'
				+  '<td class="numerico">' + (this.EsActividad ? this.PrecioVenta : '') + '</td>'
				+  '<td class="numerico">' + (this.EsActividad ? this.MontoAvance : '') + '</td>'
				+  '<td class="numerico">' + cantidadAvanceActual + '</td>'
				+  '<td class="numerico">' + (this.EsActividad ? this.MontoAvanceActual : '') + '</td>'
				+  '<td class="icon-cell cumplido">'
				+    (this.EsActividad ? '<a class="icon action checkbox checkbox-' + ( this.Cumplido ? 'checked' : 'unchecked') + '"></a> Si' : '')
				+  '</td>'
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
				'cantidad': row.children(':eq(5)').text(),
				'cumplido': (row.find('td.cumplido a.checkbox').hasClass('checkbox-checked') ? 1: 0)
			}
		});

		return conceptos;
	},

	guardaTransaccion: function() {

		var that = this;

		var IDProyecto = that.getIDProyecto(),
			IDTransaccion = that.getIDTransaccion(),
			conceptos = that.getConceptosModificados();

		if( ! IDTransaccion ) {
			
			DATA_LOADER.show();

			$.ajax({
				type: 'POST',
				url: that.urls.tranController,
				data: {
					IDProyecto: IDProyecto,
					IDConceptoRaiz: IDConceptoRaiz,
					fecha: $('#txtFechaTransaccionDB').val(),
					fechaInicio: $('#txtFechaInicioDB').val(),
					fechaTermino: $('#txtFechaTerminoDB').val(),
					observaciones: $('#txtObservaciones').val(),
					conceptos: conceptos,
					action: 'registraTransaccion'
				},
				dataType: 'json'
			})
			.done( function( json ) {
				try {

					if( ! json.success ) {
						messageConsole.displayMessage( json.message, 'error' );
						return;
					}

					$('#folios-transaccion').buttonlist('addListItem', {id: json.IDTransaccion, text: json.numeroFolio}, 'start');
					$('#folios-transaccion').buttonlist('setSelectedItemById', json.IDTransaccion, false );
					that.deshabilitaFechaTransaccion();
					$('#tabla-conceptos tr.modificado').removeClass(that.classes.conceptoModificado);

					messageConsole.displayMessage( 'La transacción se guardó correctamente.', 'success' );
				} catch( e ) {
					messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
				}
			})
			.always( function() {
				DATA_LOADER.hide();
			});
		} else {

			if ( ! IDTransaccion )
				return;
			// Guardar Transaccion Existente
			DATA_LOADER.show();

			$.ajax({
				type: 'POST',
				url: that.urls.tranController,
				data: {
					IDTransaccion: IDTransaccion,
					fecha: $('#txtFechaTransaccionDB').val(),
					fechaInicio: $('#txtFechaInicioDB').val(),
					fechaTermino: $('#txtFechaTerminoDB').val(),
					observaciones: $('#txtObservaciones').val(),
					conceptos: conceptos,
					action: 'guardaTransaccion'
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
		}
	},

	eliminaTransaccion: function() {

		var that = this
			IDTransaccion = this.getIDTransaccion();

		if ( ! IDTransaccion ) {
			return;
		}

		if ( ! confirm('La transacción será eliminada, desea continuar?') )
			return;

		DATA_LOADER.show();

		this.requestingData = true;

		$.ajax({
			type: 'GET',
			url: that.urls.tranController,
			data: {
				IDTransaccion: IDTransaccion,
				action: 'eliminaTransaccion'
			},
			dataType: 'json'
		}).done( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage( json.message, 'error' );
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
		}).always( function() {
			that.requestingData = false;
			DATA_LOADER.hide();
		});
	},

	apruebaTransaccion: function() {

		var that = this
			IDTransaccion = this.getIDTransaccion();

		if ( ! confirm('La transacción será aprobada, desea continuar?') )
			return;

		DATA_LOADER.show();

		this.requestingData = true;

		$.ajax({
			type: 'GET',
			url: that.urls.tranController,
			data: {
				IDTransaccion: IDTransaccion,
				action: 'apruebaTransaccion'
			},
			dataType: 'json'
		}).done( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage( json.message, 'error' );
					return;
				}

				messageConsole.displayMessage( 'La transacción se aprobó correctamente.', 'success' );
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		}).always( function() {
			that.requestingData = false;
			DATA_LOADER.hide();
		});
	},

	revierteAprobacion: function() {

		var that = this
			IDTransaccion = this.getIDTransaccion();

		if ( ! confirm('La aprobación será revertida, desea continuar?') )
			return;

		DATA_LOADER.show();

		this.requestingData = true;

		$.ajax({
			type: 'GET',
			url: that.urls.tranController,
			data: {
				IDTransaccion: IDTransaccion,
				action: 'revierteAprobacion'
			},
			dataType: 'json'
		}).done( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage( json.message, 'error' );
					return;
				}

				messageConsole.displayMessage( 'La aprobación se revirtió correctamente.', 'success' );
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		}).always( function() {
			that.requestingData = false;
			DATA_LOADER.hide();
		});
	},

	marcaConceptoError: function( IDConcepto, errorMessage ) {

		$('tr[data-id=' + IDConcepto + ']')
		 .find('.icon')
		 .addClass('error')
		 .attr('title', errorMessage);
	},

	desmarcaConceptosError: function() {
		$('#tabla-conceptos')
		.find('tr.' + this.classes.conceptoModificado + ' .icon')
		.removeClass('error')
		.removeAttr('title');
	},

	desmarcaConceptoError: function( IDConcepto ) {

		$('tr[data-id=' + IDConcepto + ']')
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
	},

	showListaTransacciones: function() {
		$('#dialog-lista-transacciones').dialog('open');
	}
};

// funciones Mediators que llamaran las notificaciones
var modifiedTran = function( event, data ) {
	ESTIMACION.identificaModificacion();
};

var notifyModifiedTran = function( event, data ) {
	
	if( confirm('Existen cambios sin guardar, desea continuar?...') ) {

		//if( typeof data === 'object' )
			data.call(ESTIMACION);
	}
}