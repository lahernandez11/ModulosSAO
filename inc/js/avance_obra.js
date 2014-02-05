$( function() {
	
	AVANCE.init();
});

var pubsub = PubSub();

var AVANCE = {

	classes: {
		conceptoModificado: 'modificado'
	},
	urls: {
		tranController: 'inc/lib/controllers/AvanceObraController.php'
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
			source: 'inc/lib/controllers/ListaObrasController.php',
			data: { action: 'getListaProyectos'},
			onSelect: function( selectedItem, listItem ) {
				
				$('#folios-transaccion')
				.buttonlist('option', 'data', 
					{
						base_datos: that.getBaseDatos(),
						id_obra: selectedItem.value,
						action: 'getFoliosTransaccion'
					});

				$('#folios-transaccion').buttonlist('refresh');

				$('#txtConceptoRaiz').presupuestoObra('option', 'data', 
					{
						base_datos: that.getBaseDatos(),
						id_obra: selectedItem.value,
						action: 'getDescendantsOf'
					});

				$('#btnLista-transacciones').listaTransacciones('option', 'data', 
					{
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
					id: this.IDTransaccion,
					value: this.NumeroFolio
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
		
		$('#txtConceptoRaiz').presupuestoObra({
			onAddNodes: function() {

			    return {
			        ID: this.id_concepto,
			        text: this.descripcion
			    }
			},
			onSelectNode: function( nodeElement, nodeId ) {
				that.cargaConceptos();
			},
			onLoadNodes: function() {
			    DATA_LOADER.show();
			},
			onNodesLoaded: function() {
				DATA_LOADER.hide();
			},
			dataSource: 'inc/lib/controllers/ArbolPresupuestoController.php'
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

		$('#txtFecha')
		 .datepicker( 'option', 'altField', '#txtFechaDB' );

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

		if ( ! this.getIDObra() ) {
			$.notify({text: 'Seleccione un proyecto'});
			return;
		}

		this.limpiaDatosTransaccion();
		this.habilitaCamposTransaccion();
		$('#folios-transaccion').buttonlist('reset');
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

	getIDConceptoRaiz: function() {
		return $('#txtConceptoRaiz').presupuestoObra('option', 'selectedNode').id
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
		$('#txtConceptoRaiz').presupuestoObra('clear');
		$('#guardar').removeClass('alert');
	},

	getTotalesTransaccion: function() {

		var that = this;

		var request =
			$.ajax({
				url: urls.tranController,
				data: {
					base_datos: that.getBaseDatos(),
					id_obra: that.getIDObra(),
					id_transaccion: that.getIDTransaccion(),
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

	fillTotales: function( totales ) {
		// Establece los totales de transaccion
		this.setSubtotal(totales.subtotal);
		this.setIVA(totales.iva);
		this.setTotal(totales.total);
	},

	setCantidadAvance: function( IDConcepto, cantidad ) {

		var cantidad = parseFloat(cantidad.replace(/,/g, '')) || 0;

		//if ( cantidad.length > 0 || cantidad != 0 )
			AVANCE.marcaConcepto( IDConcepto );
		//else
			//AVANCE.desmarcaConcepto( IDConcepto );

		this.uxtable('getCell', 5).text( cantidad.toFixed(4).numFormat() );
	},

	cargaTransaccion: function() {
		
		var that = this;

		that.deshabilitaCamposTransaccion();
		that.limpiaDatosTransaccion();

		DATA_LOADER.show();

		$.ajax({
			url: that.urls.tranController,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIDObra(),
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

				// Establece los datos generales
				$('#txtFechaTransaccion').datepicker( 'setDate', data.datos.Fecha );
				$('#txtConceptoRaiz').val( data.datos.ConceptoRaiz );
				$('#txtObservaciones').val( data.datos.Observaciones );
				$('#txtFechaInicio').datepicker( 'setDate', data.datos.FechaInicio );
				$('#txtFechaTermino').datepicker( 'setDate', data.datos.FechaTermino );

				// llena la tabla de conceptos
				that.llenaTablaConceptos( data.conceptos );

				// Establece los totales de transaccion
				that.fillTotales( data.totales );

				that.habilitaCamposTransaccion();
				that.deshabilitaFechaTransaccion();
				that.deshabilitaConceptoRaiz();

			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		})
		.fail( function() {	$.notify({text: 'Ocurrió un error al cargar la transaccion.'});	})
		.always( DATA_LOADER.hide );
	},

	cargaConceptos: function() {
		
		var that = this;

		DATA_LOADER.show();

		$.ajax({
			url: that.urls.tranController,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIDObra(),
				id_concepto_raiz: that.getIDConceptoRaiz(),
				action: 'getConceptosNuevoAvance'
			},
			dataType: 'json'
		})
		.done( function( data ) {

			if ( ! data.success ) {
				messageConsole.displayMessage( data.message, 'error' );
			}

			that.llenaTablaConceptos( data.conceptos );
		})
		.always( DATA_LOADER.hide );
	},

	llenaTablaConceptos: function( conceptos ) {

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

		that.desmarcaConceptosError();

		DATA_LOADER.show();

		var requestData = {
			base_datos: that.getBaseDatos(),
			id_obra: that.getIDObra(),
			id_concepto_raiz: that.getIDConceptoRaiz(),
			fecha: $('#txtFechaTransaccionDB').val(),
			fechaInicio: $('#txtFechaInicioDB').val(),
			fechaTermino: $('#txtFechaTerminoDB').val(),
			observaciones: $('#txtObservaciones').val(),
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
				console.log(data.IDTransaccion);
				$('#folios-transaccion').buttonlist('addListItem', 
					{id: data.IDTransaccion, text: data.numeroFolio}, 'start');
				
				$('#folios-transaccion').buttonlist('setSelectedItemById', 
					data.IDTransaccion, false );
				
				that.deshabilitaFechaTransaccion();
				that.deshabilitaConceptoRaiz();
			}

	 		that.fillTotales( data.totales );

	 		if ( data.errores.length > 0 ) {
	 			that.marcaConceptosError( data.errores );
	 			messageConsole.displayMessage( 'Existen errores en algunos conceptos, por favor revise y guarde otra vez.', 'error');
	 		} else {
	 			$('#guardar').removeClass('alert');
	 			messageConsole.displayMessage( 'La transacción se guardó correctamente.', 'success');
	 		}
	 		
		}).always( DATA_LOADER.hide );
	},

	eliminaTransaccion: function() {

		var that = this
			id_transaccion = this.getIDTransaccion();

		if ( ! id_transaccion ) {
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
				base_datos: that.getBaseDatos(),
				id_obra: that.getIDObra(),
				id_transaccion: id_transaccion,
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
		}).always( function() {
			that.requestingData = false;
			DATA_LOADER.hide();
		});
	},

	apruebaTransaccion: function() {

		var that = this;

		if ( ! confirm('La transacción será aprobada, desea continuar?') )
			return;

		DATA_LOADER.show();

		this.requestingData = true;

		$.ajax({
			type: 'POST',
			url: that.urls.tranController,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIDObra(),
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
		}).always( function() {
			that.requestingData = false;
			DATA_LOADER.hide();
		});
	},

	revierteAprobacion: function() {

		var that = this;

		if ( ! confirm('La aprobación será revertida, desea continuar?') )
			return;

		DATA_LOADER.show();

		this.requestingData = true;

		$.ajax({
			type: 'POST',
			url: that.urls.tranController,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIDObra(),
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
		}).always( function() {
			that.requestingData = false;
			DATA_LOADER.hide();
		});
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
	AVANCE.identificaModificacion();
};

var notifyModifiedTran = function( event, data ) {
	
	if( confirm('Existen cambios sin guardar, desea continuar?...') ) {

		if( typeof data === 'function' )
			data.call(AVANCE);
	}
}