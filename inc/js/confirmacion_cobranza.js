$( function() {
	
	COBRANZA.init();
});

var pubsub = PubSub();

var COBRANZA = {

	isModified: false,
	estimando: false,
	requestingData: false,

	init: function() {

		var CO = this;

		this.deshabilitaCamposEstimacion();
		this.limpiaDatosEstimacion();

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
			data: {action: 'getListaProyectos'},
			'onSelect': function( selectedItem, listItem ) {
				
				$('#folios-estimacion').buttonlist('option', 'data', {IDProyecto: selectedItem.value});
				$('#folios-estimacion').buttonlist('refresh');

				CO.limpiaDatosEstimacion();
				CO.deshabilitaCamposEstimacion();
			},
			'didNotDataFound': function() {
				$.notify({text: 'No se pudo cargar la lista de proyectos'});
			},
			onCreateListItem: function() {
				return {
					id: this.idProyecto,
					value: this.NombreProyecto
				}
			}
		});

		$('#folios-estimacion').buttonlist({
			'source': 'modulos/cobranza/GetListaFoliosCobranza.php',
			'beforeLoad': function() {

				if( ! $('#bl-proyectos').buttonlist('option', 'selectedItem') ) {

					$.notify({text: 'Seleccione un proyecto para cargar los folios'});
					return false;
				} else
					return true;
			},
			'onSelect': function( selectedItem, listItemElement ) {

				CO.limpiaDatosEstimacion();
				CO.setIDCobranza(selectedItem.value);
				CO.cargaCobranza();
			},
			'didNotDataFound': function() {
				$.notify({text: 'No se encontraron transacciones registradas en este proyecto'});
			},
			onCreateListItem: function() {
				return {
					id: this.id,
					value: this.value
				}
			}
		});

		$('#tabla-conceptos').uxtable({
			editableColumns: {
				6: {
					'onFinishEdit': function( activeCell, value ) {

						var cantidad = parseFloat(value.replace(/,/g, '')),
							IDConcepto = parseInt( activeCell.parent().attr('data-id') );

						if ( parseInt( activeCell.parent().attr('data-id') ) === 0 )
							return false;

						var importe = (cantidad * CO.getPrecioUnitario.call(this));

						CO.setImporteCobrado.call( this, importe );

						CO.marcaConcepto( IDConcepto );
						pubsub.publish('modified_tran');
					}
				},
				7: {
					'onFinishEdit': function( activeCell, value ) {

						var pu = parseFloat(value.replace(/,/g, '')),
							IDConcepto = parseInt( activeCell.parent().attr('data-id') );

						var importe = CO.getCantidadCobrada.call(this) * pu;

						CO.setImporteCobrado.call( this, importe );

						CO.marcaConcepto( IDConcepto );
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

			if( CO.existenCambiosSinGuardar() )
				pubsub.publish('notify_modtran', CO.nuevaEstimacion);
			else
				CO.nuevaEstimacion();
		});

		$('#eliminar').bind('click', function() {

			if( CO.existenCambiosSinGuardar() )
				pubsub.publish('notify_modtran', CO.eliminaEstimacion);
			else
				CO.eliminaTransaccion();
		});

		$('#guardar').bind('click', function() {
			CO.guardaTransaccion();
		});

		$('#enviar-sao').bind('click', function() {

			if( CO.existenCambiosSinGuardar() )
				pubsub.publish('notify_modtran', CO.enviaEstimacionCDC);
			else
				CO.enviaEstimacionCDC();
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

		// Handler para la tabla de seleccion de un subcontrato a estimar
		$('#tabla-estimaciones-obra tbody').on({

			click: function( event ) {

				$(this).siblings().removeClass('selected');
				$(this).addClass('selected');
			},
			dblclick: function( event ) {

				CO.creaEstimacion.call(CO, this);
				event.preventDefault();
			}

		}, 'tr');
	},

	nuevaEstimacion: function() {
		
		this.limpiaDatosEstimacion();
		this.deshabilitaCamposEstimacion();
		this.muestraListaEstimaciones();
		$('#folios-estimacion').buttonlist('reset');
	},

	muestraListaEstimaciones: function() {

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
			url: 'modulos/cobranza/GetListaTransacciones.php',
			data: {
				IDObra: $('#bl-proyectos').buttonlist('option', 'selectedItem').value
			},
			dataType: 'json'
		}).done( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage( json.errorMessage, 'error' );
					return;
				}

				if( json.noRows ) {
					$.notify( {text: json.noRowsMessage} );
					return;
				}

				var $tableBody = $('#tabla-estimaciones-obra tbody'),
					transacciones = '';

				$tableBody.empty();

				$.each( json.Transacciones, function() {

					transacciones +=
						'<tr data-id="' + this.IDEstimacionObra + '">'
					  +   '<td>' + this.NumeroFolio + '</td>'
					  +   '<td>' + this.Fecha + '</td>'
					  +   '<td>' + this.Referencia + '</td>'
					  + '</tr>';
				});

				$tableBody.html(transacciones);

				$('#dialog-estimaciones-obra').dialog('open');

			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		}).always( function() {
			DATA_LOADER.hide();
		});
	},

	deshabilitaCamposEstimacion: function() {
		$('#txtObservaciones').prop('disabled', true);
		$('#column-switchers').buttonset('disable');
	},

	habilitaCamposEstimacion: function() {
		$('#txtFecha').datepicker('enable').prop('disabled', false);
		 $('#txtObservaciones').prop('disabled', false);
		 $('#column-switchers').buttonset('enable');
	},

	limpiaDatosEstimacion: function() {
		//$('#IDCobranza').val('');
		$('#IDEstimacionObra').val('');
		$('#tabla-conceptos tbody').empty();
		$('#txtFecha').datepicker( 'setDate', new Date() );
		$('#txtObservaciones').val('');
		$('#txtSubtotal, #txtIVA, #txtTotal').text('');
		$('#guardar').removeClass('alert');

		// Limpia la tabla de totales
		$('#resumen-total td.numerico').text('');
	},

	getIDCobranza: function() {

		return $('#IDCobranza').val();
	},

	setIDCobranza: function( IDCobranza ) {
		
		$('#IDCobranza').val(IDCobranza);
	},

	identificaModificacion: function() {
		
		$('#guardar').addClass('alert');
	},

	existenCambiosSinGuardar: function() {
		
		return $('#guardar').hasClass('alert');
	},

	cargaDatosGeneralesEstimacion: function( IDEstimacion ) {
		
		var request = 
			$.ajax({
				type: 'GET',
				url: 'modulos/estimaciones/GetDatosGeneralesEstimacion.php',
				data: {
					'IDEstimacion': IDEstimacion
				},
				dataType: 'json'
			}).success( function( json ) {
				try {

					if( ! json.success ) {
						messageConsole.displayMessage(json.errorMessage, 'error');
						return;
					}
					
					$('#txtNombreSubcontrato').text( json.Estimacion.NombreSubcontrato );
					$('#txtNombreContratista').text( json.Estimacion.NombreContratista );
					$('#txtFecha').val( json.Estimacion.Fecha );
					$('#txtFechaInicio').val( json.Estimacion.FechaInicio );
					$('#txtFechaTermino').val( json.Estimacion.FechaTermino );
					$('#txtFolio').text( json.Estimacion.NumeroFolio );
					$('#txtFolioCDC').text( json.Estimacion.NumeroFolioCDC );
					$('#IDCobranza').val(IDEstimacion);
					$('#IDSubcontratoCDC').val( json.Estimacion.IDSubcontratoCDC );
					$('#txtObservaciones').val( json.Estimacion.Observaciones );

				} catch( e ) {
					messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
				}
			}).fail( function() {
				$.notify({text: 'Ocurrió un error al cargar los datos generales.'});
			});

		return request;
	},

	cargaTotalesCobranza: function() {

		var IDCobranza = this.getIDCobranza();

		var request =
			$.ajax({
				type: 'GET',
				url: 'modulos/cobranza/GetTotales.php',
				data: {
					'IDCobranza': IDCobranza
				},
				dataType: 'json'
			}).done( function( json ) {
				try {

					if( ! json.success ) {
						messageConsole.displayMessage(json.errorMessage, 'error');
						return;
					}

					if( json.Totales.length ) {

						$('#txtSubtotal, #rsSubtotal').text(json.Totales[0].Subtotal);
						$('#txtIVA, #rsIVA').text(json.Totales[0].IVA);
						$('#txtTotal, #rsTotal').text(json.Totales[0].Total);
						//$('#rsSumaImportes').text(json.Totales[0].SumaImportes);
						//$('#rsAnticipo').text(json.Totales[0].AmortizacionAnticipo);
						//$('#rsFondoG').text(json.Totales[0].FondoGarantia);
						//$('#rsRetenciones').text(json.Totales[0].TotalRetenido);
						//$('#rsPenalizaciones').text(json.Totales[0].TotalPenalizado);
						//$('#rsRetencionIVA').text(json.Totales[0].TotalIVARetenido);
					}

				} catch( e ) {
					messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
				}
			}).fail( function() {
				$.notify({text: 'Ocurrió un error al cargar los totales.'});
			});
	},

	cargaConceptosCobranza: function( IDCobranza ) {

		$.notify({text: 'Se estan cargando los datos de la transacción. Por favor espere.'});
		
		IDCobranza = IDCobranza || 0;

		//--- Caragar los conceptos de contrato subcontratados
		var request = 
		$.ajax({
			type: 'GET',
			url: 'modulos/cobranza/GetConceptosCobranza.php',
			data: {
				IDCobranza: IDCobranza
			},
			dataType: 'json',
		}).done( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage( json.errorMessage, 'error');
					return;
				}

				if( json.noRows ) {
					$.notify({text: json.noRowsMessage});
					return;
				}

				var conceptos = '';
				var cellType = 'td';
				var rowClass = '';
				var cantidad = '';
				var precio = '';
				var importe = '';

				$.each( json.Conceptos, function() {

					if( this.EsActividad ) {
						cellType = 'td';
						rowClass = '';
						cantidad = this.CantidadCobrada;
						precio = this.PrecioUnitario;
						importe = this.ImporteCobrado;
					}
					else {
						cellType = 'th';
						cantidad = '';
						precio = '';
						importe = '';
					}

					conceptos +=
						 '<tr' + rowClass + ' data-id="' + this.IDConcepto + '" data-esactividad="' + this.EsActividad + '">'
						+  '<td class="icon-cell"><a class="icon fixed"></a></td>'
						+  '<' + cellType + ' title="' + this.Concepto + '">' + '&nbsp;&nbsp;'.repeat(this.NumeroNivel) + this.Concepto + ' </' + cellType + '>'
						+  '<td class="centrado">' + this.Unidad + '</td>'
						+  '<td class="numerico">' + this.CantidadPresupuestada + '</td>'
						+  '<td class="numerico"></td>'
						+  '<td class="numerico">' + this.CantidadEstimada + '</td>'
						+  '<td class="editable-cell numerico">' + cantidad + '</td>'
						+  '<td class="editable-cell numerico">' + precio + '</td>'
						+  '<td class="numerico">' + importe + '</td>'
						+ '</tr>';
				});
				
				$('#tabla-conceptos tbody').html( conceptos );

			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		}).fail( function() {
			$.notify({text: 'Ocurrió un error al cargar los conceptos contratados.'});
		});

		return request;
	},

	cargaCobranza: function() {
		
		var that = this;

		that.deshabilitaCamposEstimacion();

		DATA_LOADER.show();

		//var rqDatosGenerales = that.cargaDatosGeneralesEstimacion( IDCobranza );

		//$.when( rqDatosGenerales )
		//.then( function() {

		 	var rqConceptos = that.cargaConceptosCobranza( that.getIDCobranza() );
		 	var rqTotales = that.cargaTotalesCobranza();
		 	$.when( rqConceptos, rqTotales ).then( function() {

		 	 	that.habilitaCamposEstimacion();
		 	 })
		 	 .always( function() { DATA_LOADER.hide(); });
		//})

	
	},

	creaEstimacion: function( estOb ) {

		var that = this;
		var $estimacionObra = $(estOb);

		this.limpiaDatosEstimacion();
		$('#dialog-estimaciones-obra').dialog('close');

		DATA_LOADER.show();

		//--- Registra una nueva estimacion del subcontrato seleccionado
		var rqRegistro = 
		$.ajax({
			type: 'GET',
			url: 'modulos/cobranza/RegistraCobranza.php',
			data: {
				IDEstimacionObra: parseInt($estimacionObra.attr('data-id'))
			},
			dataType: 'json',
			cache: false
		}).done( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage( json.errorMessage, 'error' );
					return;
				}

				that.setIDCobranza(json.IDCobranza);
				
				that.cargaCobranza();
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		})

		$.when( rqRegistro )
		 .fail( function() {
			DATA_LOADER.hide();
		});
	},

	getCantidadCobrada: function() {
		
		var cantidad = parseFloat(this.uxtable('getCell', 6).text().replace(/,/g, ''));
		
		return (isNaN(cantidad) ? 0 : cantidad);
	},

	getPrecioUnitario: function() {

		var pu = parseFloat(this.uxtable('getCell', 7).text().replace(/,/g, ''));

		return (isNaN(pu) ? 0 : pu);
	},

	getImporteCobrado: function() {

		return parseFloat(this.uxtable('getCell', 8).text().replace(/,/g, ''));
	},

	setCantidadEstimada: function( cantidad ) {

		var cantidad = cantidad || 0;

		this.uxtable('getCell', 6).text( cantidad.toFixed(4).numFormat() );
	},

	setPrecioUnitario: function( precio ) {

		precio = precio || 0;

		this.uxtable('getCell', 7).text( precio.toFixed(2).numFormat() );
	},

	setImporteCobrado: function( importe ) {

		importe = importe || 0;

		this.uxtable('getCell', 8).text( importe.toFixed(2).numFormat() );
	},

	guardaDatosGenerales: function() {

		var that = this;

		return $.ajax({
					type: 'GET',
					url: 'modulos/cobranza/GuardaDatosGenerales.php',
					data: {
						'IDCobranza': 	 that.getIDCobranza(),
						'Fecha': 	     $('#txtFechaDB').val(),
						'Referencia':    $('#txtReferencia').val(),
						'Observaciones': $('#txtObservaciones').val()
					},
					dataType: 'json'
				}).done( function( json ) {
					try {

						if( ! json.success ) {
							messageConsole.displayMessage( json.errorMessage, 'error' );
							return;
						}

					} catch( e ) {
						messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
					}
				})
				.fail( function() {
					$.notify({text: 'Ocurrió un error al guardar los datos generales de la transacción.'});
				});
	},

	getConceptos: function() {

		var estimados = [], row = null;

		$('#tabla-conceptos tr.estimado').each( function() {			
			row = $(this);

			estimados[estimados.length] = {
				'IDConcepto': row.attr('data-id'),
				'Cantidad': row.children(':eq(6)').text(),
				'PrecioUnitario': row.children(':eq(7)').text(),
				'Importe': row.children(':eq(8)').text()
			}
		});

		return estimados;
	},

	guardaConceptos: function() {

		var that = this;

		var estimados = this.getConceptos();
		
		return $.ajax({
				type: 'GET',
				url: 'modulos/cobranza/GuardaConceptos.php',
				data: {
					'IDCobranza': that.getIDCobranza(),
					'conceptos': estimados
				},
				dataType: 'json'
			})
			.done( function( json ) {
				try {
					if( ! json.success ) {

						messageConsole.displayMessage( json.errorMessage, 'error');

						if( json.conceptosError.length ) {

							for( concepto in json.conceptosError ) {
								 that.marcaConceptoError( json.conceptosError[concepto].IDConcepto, json.conceptosError[concepto].ErrorMessage );
							}
						}

						return;
					}

				} catch( e ) {
					messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
				}
			})
			.fail( function() {
				$.notify({text: 'Ocurrió un error al guardar los conceptos.'});
			});
	},

	guardaTransaccion: function() {

		var that = this;
		var IDCobranza = this.getIDCobranza();

		if( ! IDCobranza ) {
			messageConsole.displayMessage( 'No hay una transacción cargada.', 'error' );
			return;
		}

		DATA_LOADER.show();

		var rqDatosGenerales = this.guardaDatosGenerales();
		var rqConceptos = this.guardaConceptos();

		$.when( rqDatosGenerales, rqConceptos )
		 .then( function( jsonDatosGenerales, jsonEstimados ) {
		 	
		 	if( jsonDatosGenerales[0].success && jsonEstimados[0].success ) {

		 		messageConsole.displayMessage( 'La transacción se guardó correctamente.', 'success');
		 		that.cargaTotalesCobranza();
		 		that.desmarcaConceptosError();
		 		$('#guardar').removeClass('alert');
		 	}

		 })
		 .fail( function() {
		 	messageConsole.displayMessage( 'Ocurrió un error al guardar la transacción.', 'error' );
		 })
		 .always( function() {
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
		.find('tr.estimado .icon')
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
		
		$('tr[data-id=' + IDConcepto + ']').addClass('estimado');
	},

	desmarcaConceptoEstimado: function( IDConcepto ) {

		$('tr[data-id=' + IDConcepto + ']').removeClass('estimado');
		this.desmarcaConceptoError( IDConcepto );
	},

	eliminaTransaccion: function() {

		var that = this
			IDCobranza = this.getIDCobranza();

		if( ! IDCobranza ) {
			messageConsole.displayMessage( 'No hay una transacción cargada.', 'error' );
			return;
		}

		if ( this.requestingData ) {
			$.notify({text: 'La transacción se esta eliminando, espere por favor.'});
			return;
		}

		if ( ! confirm('La transacción será eliminada, desea continuar?') )
			return;

		DATA_LOADER.show();

		this.requestingData = true;

		$.ajax({
			type: 'GET',
			url: 'modulos/cobranza/EliminaTransaccion.php',
			data: {
				'IDCobranza': IDCobranza
			},
			dataType: 'json'
		}).done( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage( json.errorMessage, 'error' );
					return;
				}

				that.deshabilitaCamposEstimacion();
				that.limpiaDatosEstimacion();
				$('#folios-estimacion').buttonlist('reset');

			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		}).always( function() {
			that.requestingData = false;
			DATA_LOADER.hide();
		});
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