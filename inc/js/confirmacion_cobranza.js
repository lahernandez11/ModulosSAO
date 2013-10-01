$( function() {
	
	COBRANZA.init();
});

var pubsub = PubSub();

var COBRANZA = {

	classes: {
		conceptoModificado: 'modificado'
	},
	urls: {
		tranController: 'inc/lib/controllers/CobranzaController.php'
	},
	IDEstimacionObra: null,

	init: function() {

		var that = this;

		// Suscripcion al evento transaccion modificada
		var modifiedTranSubscription = pubsub.subscribe('modified_tran', modifiedTran);
		// Suscripcion al evento que notifica cuando la transaccion tiene cambios por guardar
		var notifyModifiedTranSubs   = pubsub.subscribe('notify_modtran', notifyModifiedTran);

		$('#tabla-conceptos').on('keyup', 'input[type=text]', function() {

		    var oldValue = $(this).val();
		    
		    $(this).val(oldValue.numFormat());
		});

		$('#bl-proyectos').buttonlist({
			source: 'inc/lib/controllers/ListaProyectosController.php',
			data: {action: 'getListaProyectos'},
			'onSelect': function( selectedItem, listItem ) {
				
				$('#folios-transaccion').buttonlist('option', 'data', {
					IDProyecto: selectedItem.value, action: 'getTransacciones'
				});

				$('#folios-transaccion').buttonlist('refresh');

				that.limpiaDatosTransaccion();
				that.deshabilitaCamposTransaccion();
			},
			'didNotDataFound': function() {
				$.notify({text: 'No se pudo cargar la lista de proyectos'});
			},
			onCreateListItem: function() {
				return {
					id: this.IDProyecto,
					value: this.NombreProyecto
				}
			}
		});

		$('#folios-transaccion').buttonlist({
			'source': that.urls.tranController,
			'beforeLoad': function() {

				if( ! $('#bl-proyectos').buttonlist('option', 'selectedItem') ) {

					$.notify({text: 'Seleccione un proyecto para cargar los folios'});
					return false;
				} else
					return true;
			},
			'onSelect': function( selectedItem, listItemElement ) {

				that.cargaTransaccion();
			},
			'didNotDataFound': function() {
				$.notify({text: 'No se encontraron transacciones registradas en este proyecto'});
			},
			onCreateListItem: function() {
				return {
					id: this.IDCobranza,
					value: this.NumeroFolio
				}
			}
		});

		$('#tabla-conceptos').uxtable({
			editableColumns: {
				8: {
					'onFinishEdit': function( activeCell, value ) {

						var cantidad = parseFloat(value.replace(/,/g, '')),
							IDConcepto = parseInt( activeCell.parent().attr('data-id') );

						if ( parseInt(activeCell.parent().attr('data-esactividad')) == 1 ) {
							that.setCantidadCobrada.call( this, IDConcepto, value );
							that.setImporteCobrado.call(activeCell.next().next());
						}

						that.marcaConcepto( IDConcepto );
						pubsub.publish('modified_tran');
					}
				},
				9: {
					'onFinishEdit': function( activeCell, value ) {

						var pu = parseFloat(value.replace(/,/g, '')),
							IDConcepto = parseInt( activeCell.parent().attr('data-id') );

						if ( parseInt(activeCell.parent().attr('data-esactividad')) == 1 ) {
							
							that.setPrecio.call( this, IDConcepto, value );
							that.setImporteCobrado.call(activeCell.next());
						}

						pubsub.publish('modified_tran');
					}
				}
			}
		});
		

		$('#nuevo').bind('click', function() {

			if( ! $('#bl-proyectos').buttonlist('option', 'selectedItem') ) {
				$.notify({text: 'Seleccione un proyecto'});
				return;
			}

			if( that.existenCambiosSinGuardar() )
				pubsub.publish('notify_modtran', that.muestraListaEstimacionesObra);
			else
				that.muestraListaEstimacionesObra();
		});

		$('#eliminar').bind('click', function() {

			if( that.existenCambiosSinGuardar() )
				pubsub.publish('notify_modtran', that.eliminaEstimacion);
			else
				that.eliminaTransaccion();
		});

		$('#guardar').bind('click', function() {
			that.guardaTransaccion();
		});

		$('#enviar-sao').bind('click', function() {

			if( that.existenCambiosSinGuardar() )
				pubsub.publish('notify_modtran', that.enviaEstimacionCDC);
			else
				that.enviaEstimacionCDC();
		});

		$('#btnResumen').on('click', function() {
			$('#dialog-resumen').dialog('open');
		});

		$('#btnRetenciones').on('click', function() {
			$('#dialog-retenciones').dialog('open');
		});

		$('#btnPenalizaciones').on('click', function() {
			$('#dialog-penalizaciones').dialog('open');
		});

		$('#txtFecha').datepicker({
			dateFormat: 'dd-mm-yy',
			altFormat: 'yy-mm-dd',
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

		$('#txtFecha')
		 .datepicker( 'option', 'altField', '#txtFechaDB' );

		$('#txtObservaciones').on('change', function(){
			pubsub.publish('modified_tran');
		});

		$('#dialog-estimaciones-obra').dialog({
			autoOpen: false,
			modal: true,
			width: 760,
			height: 390,
			show: 'fold'
		});

		$('#dialog-resumen').dialog({
			autoOpen: false,
			modal: true,
			width: 400,
			buttons: {
				Cerrar: function() {
					$(this).dialog('close');
				}
			}
		});

		// Botones para ocultar/mostrar las columnas de la tabla
		$('#column-switchers')
		 .buttonset()
		 .buttonset({disabled: true}).on('click', 'input', function( event ) {
		 	$('#tabla-conceptos colgroup .' + this.id).toggleClass('hidden');
		 });

		// Handler para la tabla de seleccion de una estimacion de obra
		$('#tabla-estimaciones-obra tbody').on({

			click: function( event ) {

				$(this).siblings().removeClass('selected');
				$(this).addClass('selected');
			},
			dblclick: function( event ) {

				that.nuevaTransaccion.call(that, this);
			}

		}, 'tr');

		$('.tabla-resumen .editable').on('dblclick', function(event) {
		    
		    var $that = $(this),
		    	tipoTotal = this.id;

		    if ( $that.children('input').length )
		    	return;

		    var	oldValue  = $(this).text(),
		    	inputText = $('<input type="text" class="text" value="' + oldValue + '" />');
		    
		    $that.empty();
		    
		    inputText
		    .appendTo($that)
		    .on('blur', function() {
				updateValue();
			})
			.on('keydown', function(event) {

				switch( event.keyCode ) {
			    	case 13:
			        	updateValue();
		           	break;

		           	case 27:
		            	restorePrevValue();
		            	removeInput();
		           	break;
			   }

			   event.stopPropagation();
			}).select();

			function removeInput() {
				inputText.remove();
			}

			function restorePrevValue() {
				$that.text(oldValue);
			}

			function setValue() {
				$that.text(inputText.val().numFormat());
			}

			function updateValue() {

				that.actualizaTotal( tipoTotal, inputText.val(), setValue, restorePrevValue, removeInput );
			}
		});

		this.deshabilitaCamposTransaccion();
		this.limpiaDatosTransaccion();
	},

	actualizaTotal: function(tipoTotal, importe, successCallback, errorCallback, alwaysCallback) {
		var that = this;

		DATA_LOADER.show();

    	$.ajax({
    		type: 'POST',
    		url: that.urls.tranController,
    		data: {
    			IDTransaccion: that.getIDTransaccion(),
    			tipoTotal: tipoTotal,
    			importe: importe,
    			action: 'actualizaTotal'
    		},
    		dataType: 'json'
    	})
    	.done( function( json ) {

    		if ( ! json.success ) {
    			messageConsole.displayMessage(json.message, 'error');
    			errorCallback();
    		} else {
    			successCallback();
    			that.cargaTotales();
    		}
    	})
    	.always( function() { alwaysCallback(); DATA_LOADER.hide(); });
	},

	getIDProyecto: function() {
		return $('#bl-proyectos').buttonlist('option', 'selectedItem').value;
	},

	getIDTransaccion: function() {
		return $('#folios-transaccion').buttonlist('option', 'selectedItem').value;
	},

	getIDEstimacionObra: function() {
		return this.IDEstimacionObra;
	},

	setIDEstimacionObra: function( id ) {
		this.IDEstimacionObra = id;
	},

	setReferencia: function( referencia ) {
		$('#txtReferencia').val(referencia);
	},

	deshabilitaFechaTransaccion: function() {
		$('#txtFecha').datepicker('disable');
	},

	deshabilitaCamposTransaccion: function() {
		this.deshabilitaFechaTransaccion();
		$('#txtObservaciones').prop('disabled', true);
		$('#txtReferencia').prop('disabled', true);
	},

	habilitaCamposTransaccion: function() {
		$('#txtFecha').datepicker('enable');
		$('#txtObservaciones').prop('disabled', false);
		$('#txtReferencia').prop('disabled', false);
	},

	habilitaObservaciones: function() {
		$('#txtObservaciones').prop('disabled', false);
	},

	habilitaFechaTransaccion: function() {
		$('#txtFecha').datepicker('enable');
	},

	limpiaDatosTransaccion: function() {
		
		$('#txtFecha').datepicker( 'setDate', new Date() );
		$('#txtObservaciones').val('');
		$('#txtReferencia').val('');
		$('#txtSubtotal, #txtIVA, #txtTotal').text('');
		$('#txtFolioFactura').val('');
		$('#tabla-conceptos tbody').empty();

		$('#guardar').removeClass('alert');
	},

	muestraListaEstimacionesObra: function() {
		var that = this;

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
			url: 'inc/lib/controllers/EstimacionObraController.php',
			data: {
				IDProyecto: that.getIDProyecto(),
				action: 'getListaTransacciones'
			},
			dataType: 'json'
		}).done( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage( json.message, 'error' );
					return;
				}

				if( json.noRows ) {
					$.notify( {text: json.message} );
					return;
				}

				that.fillEstimacionesObra(json.options);

				$('#dialog-estimaciones-obra').dialog('open');

			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}

		}).always( function() { DATA_LOADER.hide(); });
	},

	fillEstimacionesObra: function( estimaciones ) {
		$estimacionesObraBody = $('#tabla-estimaciones-obra tbody');

		$estimacionesObraBody.empty();

		$estimacionesObraBody.html(this.listaEstimacionesObraTemplate(estimaciones));
	},

	estimacionObraItemTemplate: function( data ) {
		var html = "";

		html = '<tr data-id="' + data.IDTransaccion + '">'
			+   '<td>' + data.NumeroFolio + '</td>'
			+   '<td>' + data.Fecha + '</td>'
			+   '<td>' + data.Observaciones + '</td>'
			+ '</tr>';

		return html;
	},

	listaEstimacionesObraTemplate: function( estimaciones ) {
		var html = "";

		for (var i = 0; i < estimaciones.length; i++) {
			html += this.estimacionObraItemTemplate( estimaciones[i] );
		};

		return html;
	},

	nuevaTransaccion: function( estimacionObra ) {
		var that = this;

		this.limpiaDatosTransaccion();
		this.deshabilitaCamposTransaccion();
		$('#folios-transaccion').buttonlist('reset');
		$('#dialog-estimaciones-obra').dialog('close');

		this.setIDEstimacionObra(parseInt($(estimacionObra).attr('data-id')));

		DATA_LOADER.show();

		// cargar los datos de la estimacion de obra
		$.ajax({
			url: that.urls.tranController,
			data: {
				IDProyecto: that.getIDProyecto(),
				IDEstimacionObra: that.getIDEstimacionObra(),
				action: 'nuevaTransaccion'
			},
			dataType: 'json'
		})
		.done( function(data) {
			try {

				if( ! data.success ) {
					messageConsole.displayMessage( data.message, 'error');
				}

				that.setReferencia($(estimacionObra).children().last().text());

				that.fillConceptos( data.conceptos );

				that.habilitaObservaciones();
				that.habilitaFechaTransaccion();
			} catch( e ) {
				messageConsole.displayMessage(e.message, 'error');
			}
		})
		.always( function() { DATA_LOADER.hide(); });
	},

	fillConceptos: function( conceptos ) {

		$('#tabla-conceptos tbody').html( this.conceptosListTemplate(conceptos) );
	},

	conceptosListTemplate: function( conceptos ) {
		var html = '';

		for (var i = 0; i < conceptos.length; i++) {
			html += this.conceptoTemplate( conceptos[i] );
		}

		return html;
	},

	conceptoTemplate: function( concepto ) {

		var html = '',
			cellType = 'td',
			cantidad = '',
			precio = '',
			importe = '',
			rowClass = '';

		if ( concepto.EsActividad ) {
			cellType = 'td';
			cantidad = concepto.CantidadCobrada;
			precio   = concepto.PrecioUnitarioCobrado;
			importe  = concepto.ImporteCobrado;
		} else {
			cellType = 'th';
			cantidad = '';
			precio   = '';
			importe  = '';
		}

		if ( concepto.Estimado == 1 )
			rowClass = ' class="modificado"';

		html =
		'<tr' + rowClass + ' data-id="' + concepto.IDConcepto + '" data-esactividad="' + concepto.EsActividad + '">'
		+  '<td class="icon-cell"><a class="icon fixed"></a></td>'
		+  '<' + cellType + ' title="' + concepto.Descripcion + '">'
		+    '&nbsp;&nbsp;'.repeat(concepto.NumeroNivel) + concepto.Descripcion
		+ ' </' + cellType + '>'
		+  '<td class="centrado">' + concepto.Unidad + '</td>'
		+  '<td class="numerico">' + concepto.CantidadPresupuestada + '</td>'
		+  '<td class="numerico"></td>'
		+  '<td class="numerico">' + concepto.CantidadEstimada + '</td>'
		+  '<td class="numerico">' + concepto.PrecioUnitarioEstimado + '</td>'
		+  '<td class="numerico">' + concepto.ImporteEstimado + '</td>'
		+  '<td class="editable-cell numerico">' + cantidad + '</td>'
		+  '<td class="editable-cell numerico">' + precio + '</td>'
		+  '<td class="numerico">' + importe + '</td>'
		+ '</tr>';

		return html;
	},
	
	cargaTransaccion: function() {
		
		var that = this;
		
		this.limpiaDatosTransaccion();

		DATA_LOADER.show();

		$.ajax({
			url: that.urls.tranController,
			data: {
				IDTransaccion: that.getIDTransaccion(),
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

				that.fillDatosGenerales( json.datos );

				that.fillConceptos( json.conceptos );

				that.fillTotales( json.totales );

				that.habilitaObservaciones();
				that.deshabilitaFechaTransaccion();

			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		})
		.fail( function() { $.notify({text: 'Ocurrió un error al cargar la transaccion.'}); })
		.always( function() { DATA_LOADER.hide(); });
	},

	fillDatosGenerales: function( data ) {
		// Establece los datos generales
		$('#txtFecha').datepicker( 'setDate', data.fecha );
		$('#txtObservaciones').val( data.observaciones );
		$('#txtReferencia').val( data.referencia );
		$('#txtFolioFactura').val( data.folio_factura );
	},

	fillTotales: function( totales ) {
		// Establece los totales de transaccion
		$('#txtSubtotal').text(totales.subtotal);
		$('#txtIVA').text(totales.iva);
		$('#txtTotal').text(totales.total);

		$('#txtImporteProgramado').text(totales.importeProgramado);
		$('#txtImporteEstimadoAcumuladoAnterior').text(totales.importeEstimadoAcumuladoAnterior)
		$('#txtImporteObraEjecutadaEstimada').text(totales.importeObraEjecutadaEstimada);
		$('#txtImporteObraAcumuladaNoEjecutada').text(totales.importeObraAcumuladaNoEjecutada);
		$('#txtImporteDevolucion').text(totales.importeDevolucion);
		$('#txtImporteRetencionObraNoEjecutada').text(totales.importeRetencionObraNoEjecutada);
		$('#txtSubtotalFacturar').text(totales.subtotalFacturar);
		$('#txtIVAFacturar').text(totales.ivaFacturar);
		$('#txtTotalFacturar').text(totales.totalFacturar);
		$('#txtImporteAmortizacionAnticipo').text(totales.importeAmortizacionAnticipo);
		$('#txtImporteIVAAnticipo').text(totales.importeIVAAnticipo);

		$('#txtPctObraNoEjecutada').text(totales.pctObraNoEjecutada);
		$('#txtPctInspeccionVigilancia').text(totales.pctInspeccion);
		$('#txtPctIVAAnticipo').text(totales.pctIVAAnticipo);

		$('#txtImporteInspeccionVigilancia').text(totales.importeInspeccionVigilancia);
		$('#txtImporteCMIC').text(totales.importeCMIC);
		$('#txtImporteEstimacion').text(totales.subtotal);
		$('#txtTotalDeducciones').text(totales.totalDeducciones);
		$('#txtImporteLiquidoContrato').text(totales.alcanceLiquidoContratista);
	},

	guardaTransaccion: function() {

		var that = this;

		DATA_LOADER.show();

		that.desmarcaConceptosError();

		$.ajax({
			type: 'POST',
			url: that.urls.tranController,
			data: {
				IDProyecto: that.getIDProyecto(),
				IDTransaccion: that.getIDTransaccion(),
				fecha: $('#txtFechaDB').val(),
				IDEstimacionObra: that.getIDEstimacionObra(),
				observaciones: $('#txtObservaciones').val(),
				folio_factura: $('#txtFolioFactura').val(),
				conceptos: that.getConceptosModificados(),
				action: 'guardaTransaccion'
			},
			dataType: 'json'
		}).done( function(data) {

			if( ! data.success ) {
				messageConsole.displayMessage( data.message, 'error' );
				return;
			}

			if ( ! that.getIDTransaccion() ) {
				$('#folios-transaccion').buttonlist('addListItem', 
					{id: data.IDTransaccion, text: data.numeroFolio}, 'start');
				
				$('#folios-transaccion').buttonlist('setSelectedItemById', 
					data.IDTransaccion, false );
				
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
	 		
		}).always( function() {
			DATA_LOADER.hide();
		});
	},

	getConceptosModificados: function() {

		var conceptos = [],
			row = null;

		$('#tabla-conceptos tr.' + this.classes.conceptoModificado).each( function() {			
			row = $(this);

			conceptos[conceptos.length] = {

				'IDConcepto': row.attr('data-id'),
				'cantidad': row.children(':eq(8)').text(),
				'precio': row.children(':eq(9)').text()
				// 'cumplido': (row.find('td.cumplido a.checkbox').hasClass('checkbox-checked') ? 1: 0)
			}
		});

		return conceptos;
	},
	
	eliminaTransaccion: function() {

		var that = this;

		if( ! this.getIDTransaccion() ) {
			messageConsole.displayMessage( 'No hay una transacción cargada.', 'error' );
			return;
		}

		if ( ! confirm('La transacción será eliminada, desea continuar?') )
			return;

		DATA_LOADER.show();

		$.ajax({
			url: that.urls.tranController,
			data: {
				'IDTransaccion': that.getIDTransaccion(),
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

			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		}).always( function() {	DATA_LOADER.hide(); });
	},

	cargaTotales: function() {

		var that = this;

		DATA_LOADER.show();

		$.ajax({
			url: that.urls.tranController,
			data: {
				IDTransaccion: that.getIDTransaccion(),
				action: 'getTotalesTransaccion'
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
				
				that.fillTotales(data.totales);

			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		})
		.fail( function() {	$.notify({text: 'Ocurrió un error al cargar la transaccion.'});	})
		.always( function() { DATA_LOADER.hide(); });
	},

	identificaModificacion: function() {
		$('#guardar').addClass('alert');
	},

	existenCambiosSinGuardar: function() {
		return $('#guardar').hasClass('alert');
	},

	setCantidadCobrada: function( IDConcepto, cantidad ) {

		var cantidad = parseFloat(cantidad.replace(/,/g, '')) || 0;

		COBRANZA.marcaConcepto( IDConcepto );
		
		this.uxtable('getCell', 8).text( cantidad.toFixed(4).numFormat() );
	},

	setPrecio: function( IDConcepto, precio ) {

		var pu = parseFloat(precio.replace(/,/g, '')) || 0;

		COBRANZA.marcaConcepto( IDConcepto );

		this.uxtable('getCell', 9).text( pu.toFixed(4).numFormat() );
	},

	setImporteCobrado: function() {
		this.text((parseFloat(this.prev().text()) * parseFloat(this.prev().prev().text())).toFixed(2).toString().numFormat());
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
		.find('tr.' + this.classes.conceptoModificado)
		.removeClass('error')
		.find('.icon')
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
	}
};

// funciones Mediators que llamaran las notificaciones
var modifiedTran = function( event, data ) {
	COBRANZA.identificaModificacion();
};

var notifyModifiedTran = function( event, data ) {
	if( confirm('Desea continuar sin guardar los cambios?...') ) {
		if( typeof data === 'object')
			data.call(ESTIMACION);
	}
}