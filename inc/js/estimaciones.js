$( function() {
	
	ESTIMACION.init();
});

var pubsub = PubSub();

var ESTIMACION = {

	isModified: false,
	estimando: false,
	requestingData: false,
	IDSubcontrato: null,

	classes: {
		conceptoModificado: 'modificado'
	},
	urls: {
		tranController: 'inc/lib/controllers/EstimacionSubcontratoController.php'
	},

	init: function() {

		var that = this;

		Retenciones.init();
		Deductivas.init();

		this.deshabilitaCamposTransaccion();
		this.limpiaDatosTransaccion();

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
			onSelect: function( selectedItem, listItem ) {

				$('#btnLista-transacciones').listaTransacciones('option', 'data', 
					{IDProyecto: selectedItem.value, action: 'getListaTransacciones'});

				$('#folios-transaccion')
				.buttonlist('option', 'data', 
					{IDProyecto: selectedItem.value, action: 'getFoliosTransaccion'});

				$('#folios-transaccion').buttonlist('refresh');

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

				if( ! $('#bl-proyectos').buttonlist('option', 'selectedItem') ) {

					$.notify({text: 'Seleccione un proyecto para cargar los folios'});
					return false;
				} else
					return true;
			},
			'onSelect': function( selectedItem, listItem ) {
				that.cargaTransaccion();
			},
			'didNotDataFound': function() {
				$.notify({text: 'No se encontraron estimaciones registradas en este proyecto'});
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
				12: {
					'onFinishEdit': function( activeCell, value ) {

						var cantidadEstimada = parseFloat(value.replace(/,/g, '')),
							IDConcepto = parseInt( activeCell.parent().attr('data-id') );

						var pctEstimado = ((cantidadEstimada / that.getVolumenSubcontratado.call(this)) * 100);
						var importeEstimado = (cantidadEstimada * that.getPrecioUnitarioConcepto.call(this));

						that.setPorcentajeEstimado.call( this, pctEstimado );
						that.setImporteEstimado.call( this, importeEstimado );

						//if( cantidadEstimada === 0 )
						//	that.desmarcaConceptoEstimado( IDConcepto );
						//else {
							that.marcaConceptoEstimado( IDConcepto );
							pubsub.publish('modified_tran');
						//}
					}
				},
				13: {
					'onFinishEdit': function( activeCell, value ) {

						var porcentajeEstimado = parseFloat(value.replace(/,/g, '')),
							IDConcepto = parseInt( activeCell.parent().attr('data-id') );

						var cantidadEstimada = ((that.getVolumenSubcontratado.call(this) * porcentajeEstimado) / 100);
						var importeEstimado = (cantidadEstimada * that.getPrecioUnitarioConcepto.call(this));

						that.setCantidadEstimada.call( this, cantidadEstimada );
						that.setImporteEstimado.call( this, importeEstimado );

						//if( porcentajeEstimado === 0 )
							//that.desmarcaConceptoEstimado( IDConcepto );
						//else {
							that.marcaConceptoEstimado( IDConcepto );
							pubsub.publish('modified_tran');
						//}
					}
				},
				15: {
					'onFinishEdit': function( activeCell, value ) {

						var importeEstimado = parseFloat( value.replace(/,/g, '') ),
							IDConcepto = parseInt( activeCell.parent().attr('data-id') );

						var cantidadEstimada = (importeEstimado / that.getPrecioUnitarioConcepto.call(this));

						var pctEstimado = ((cantidadEstimada / that.getVolumenSubcontratado.call(this)) * 100);

						that.setCantidadEstimada.call( this, cantidadEstimada );
						that.setPorcentajeEstimado.call( this, pctEstimado );
						activeCell.text( importeEstimado.toString().numFormat() );
						//if( importeEstimado === 0 )
						//	that.desmarcaConceptoEstimado( IDConcepto );
						//else {
							that.marcaConceptoEstimado( IDConcepto );
							pubsub.publish('modified_tran');
						//}
					}
				}
			}
		});

		$('#nuevo').on('click', function() {

			// if( that.existenCambiosSinGuardar() )
			// 	pubsub.publish('notify_modtran', that.nuevaTransaccion);
			// else {

				if ( ! that.getIDProyecto() ) {
					$.notify({text: 'Seleccione un proyecto.'});
					return;
				}

				that.limpiaDatosTransaccion();
				that.deshabilitaCamposTransaccion();
				that.muestraListaSubcontratos();
				$('#folios-transaccion').buttonlist('reset');
			// }
		});

		$('#eliminar').on('click', function() {

			if( that.existenCambiosSinGuardar() )
				pubsub.publish('notify_modtran', that.eliminaTransaccion);
			else
				that.eliminaTransaccion();
		});

		$('#guardar').on('click', function() {
			that.guardaTransaccion();
		});

		$('#enviar-sao').on('click', function() {

			if( that.existenCambiosSinGuardar() )
				pubsub.publish('notify_modtran', that.enviaEstimacionCDC);
			else
				that.enviaEstimacionCDC();
		});

		$('#btnResumen').on('click', function() {
			
			if( ! that.getIDTransaccion() ) {
				$.notify({text: 'Debe cargar una estimación.'});
			} else
				$('#dialog-resumen').dialog('open');
		});

		$('#btnDeductivas').on('click', function() {
			
			if( ! that.getIDTransaccion() ) {
				$.notify({text: 'Debe cargar una estimación.'});
			} else
				Deductivas.cargaDeductivas();
		});

		$('#btnRetenciones').on('click', function() {

			if( ! that.getIDTransaccion() ) {
				$.notify({text: 'Debe cargar una estimación.'});
			} else
				Retenciones.cargaRetenciones();
		});

		$('#btnFormatoPDF').on('click', function(event) {

			if( ! that.getIDTransaccion() ) {
				$.notify({text: 'Debe cargar una estimación.'});
				event.preventDefault();
			}
		});

		$('#txtFecha, #txtFechaInicio, #txtFechaTermino').datepicker({
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

		$('#dialog-subcontratos').dialog({
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
		 .buttonset({disabled: true})
		 .on('click', 'input', function( event ) {

		 	$('#tabla-conceptos colgroup .' + this.id).toggleClass('hidden');
		 });

		// Handler para la tabla de seleccion de un subcontrato a estimar
		$('#tabla-subcontratos tbody').on({
			click: function( event ) {

				$(this).siblings().removeClass('selected');
				$(this).addClass('selected');
			},
			dblclick: function( event ) {
				$('#dialog-subcontratos').dialog('close');
				that.IDSubcontrato = parseInt($(this).attr('data-id'));
				that.nuevaTransaccion();
				event.preventDefault();
			}
		}, 'tr');

		$('#txtAmortAnticipo, #txtFondoGarantia, #txtRetencionIVA, #txtAnticipoLiberar').on('click', function(event) {
		    
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

				DATA_LOADER.show();

		    	$.ajax({
		    		type: 'GET',
		    		url: that.urls.tranController,
		    		data: {
		    			IDTransaccion: that.getIDTransaccion(),
		    			tipoTotal: tipoTotal,
		    			importe: inputText.val(),
		    			action: 'actualizaImporte'
		    		},
		    		dataType: 'json'
		    	})
		    	.done( function( json ) {

		    		if ( ! json.success ) {
		    			messageConsole.displayMessage(json.message, 'error');
		    			restorePrevValue();
		    		} else {
		    			setValue();
		    			that.cargaTotales();
		    		}
		    	})
		    	.always( function() {
		    		removeInput();
		    		DATA_LOADER.hide();
		    	});
			}
		});
	},

	nuevaTransaccion: function() {

		var that = this;

		DATA_LOADER.show();

		$.ajax({
			url: that.urls.tranController,
			data: {
				IDSubcontrato: that.getIDSubcontrato(),
				action: 'nuevaTransaccion'
			},
			dataType: 'json'
		}).done( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage( json.message, 'error' );
					return;
				}

				// Datos del Subcontrato
				$('#txtObjetoSubcontrato').text(json.datosSubcontrato.ObjetoSubcontrato);
				$('#txtNombreContratista').text(json.datosSubcontrato.NombreContratista);
				// Conceptos del subcontrato para estimacion
				
				that.fillConceptosList(json.conceptos);
				
				that.habilitaCamposTransaccion();
				
				pubsub.publish('modified_tran');
				
				that.setURLFormatoPDF();
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		}).always( function() {
			DATA_LOADER.hide();
		});
	},

	deshabilitaCamposTransaccion: function() {
		$('#txtFecha, #txtFechaInicio, #txtFechaTermino').datepicker('disable');
		$('#txtObservaciones').prop('disabled', true);
		$('#column-switchers').buttonset('disable');
	},

	deshabilitaFechaTransaccion: function() {
		$('#txtFecha').datepicker('disable');
	},

	habilitaCamposTransaccion: function() {
		$('#txtFecha, #txtFechaInicio, #txtFechaTermino').datepicker('enable');
		$('#txtObservaciones').prop('disabled', false);
		$('#column-switchers').buttonset('enable');
	},

	limpiaDatosTransaccion: function() {
		this.IDSubcontrato = null;
		$('#IDSubcontratoCDC').val('');
		$('#txtObjetoSubcontrato').text('');
		$('#txtNombreContratista').text('');
		$('#txtFecha, #txtFechaInicio, #txtFechaTermino').datepicker( 'setDate', new Date() );
		$('#txtFolioConsecutivo').text('');
		$('#txtObservaciones').val('');
		$('#txtSubtotal, #txtIVA, #txtTotal').text('');
		$('#tabla-conceptos tbody').empty();
		$('#guardar').removeClass('alert');
		
		// Limpia la tabla de totales
		$('#resumen-total td.numerico').text('');
	},

	getIDProyecto: function() {
		return $('#bl-proyectos').buttonlist('option', 'selectedItem').value;
	},

	getIDSubcontrato: function() {
		return this.IDSubcontrato;
	},

	getIDTransaccion: function() {
		return $('#folios-transaccion').buttonlist('option', 'selectedItem').value;
	},

	muestraListaSubcontratos: function() {

		var that = this;

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
			url: that.urls.tranController,
			data: {
				IDProyecto: $('#bl-proyectos').buttonlist('option', 'selectedItem').value,
				action: 'getListaSubcontratos'
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

				// Crear tabla de subcontratos
				var $bodySubcontratos = $('#tabla-subcontratos tbody'),
					subcontratos = '';

				$bodySubcontratos.empty();

				$.each( json.subcontratos, function() {

					subcontratos +=
						'<tr data-id="' + this.IDSubcontrato + '">'
					  +   '<td>' + this.Contratista + '</td>'
					  +   '<td>' + this.NumeroFolio + '</td>'
					  +   '<td>' + this.Fecha + '</td>'
					  +   '<td>' + this.Referencia + '</td>'
					  + '</tr>';
				});

				$bodySubcontratos.html(subcontratos);

				$('#dialog-subcontratos').dialog('open');

			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		}).always( function() {
			DATA_LOADER.hide();
		});
	},

	cargaTransaccion: function() {
		
		var that = this;

		this.limpiaDatosTransaccion();
		this.deshabilitaCamposTransaccion();

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
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

				// Establece los datos generales
				$('#txtFolioConsecutivo').text(json.datos.NumeroFolioConsecutivo);
				$('#txtFecha').datepicker( 'setDate', json.datos.Fecha );
				$('#txtObjetoSubcontrato').text( json.datos.ObjetoSubcontrato );
				$('#txtNombreContratista').text( json.datos.NombreContratista );
				$('#txtObservaciones').val( json.datos.Observaciones );
				$('#txtFechaInicio').datepicker( 'setDate', json.datos.FechaInicio );
				$('#txtFechaTermino').datepicker( 'setDate', json.datos.FechaTermino );

				// llena la tabla de conceptos
				that.fillConceptosList( json.conceptos );

				// Establece los totales de transaccion
				that.setTotalesTransaccion(json.totales);

				that.habilitaCamposTransaccion();
				that.deshabilitaFechaTransaccion();
				that.setURLFormatoPDF();

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

	setURLFormatoPDF: function() {

		var url = $('#btnFormatoPDF').attr('href');

		if ( url.match(/=(\d+\d$|null)/ig) ) {
			url = url.replace(/(=)(\d+\d$|null)/ig, '$1' + this.getIDTransaccion());
		} else
			url += (this.getIDTransaccion() != null ? this.getIDTransaccion() : "");

		$('#btnFormatoPDF').attr('href', url);
	},

	cargaTotales: function() {

		var that = this;

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
			url: that.urls.tranController,
			data: {
				IDTransaccion: that.getIDTransaccion(),
				action: 'getTotalesTransaccion'
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
				// Establece los totales de transaccion
				that.setTotalesTransaccion(json.totales);

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

	conceptosListTemplate: function( conceptos ) {

		var html = '',
			cellType = 'td';

		for (var i = 0; i < conceptos.length; i++) {
			
			var concepto = conceptos[i];

			if ( concepto.EsActividad )
				cellType = 'td';
			else
				cellType = 'th';

			html +=
				  '<tr data-id="' + concepto.IDConceptoContrato + '" data-iddestino="' + concepto.IDConceptoDestino + '">'
				+    '<td class="icon-cell"><a class="icon fixed"></a></td>'
				+    '<' + cellType + ' title="' + concepto.Descripcion + '">' + '&nbsp;&nbsp;'.repeat(concepto.NumeroNivel) + concepto.Descripcion + ' </' + cellType + '>'
				+    '<td class="centrado">' + concepto.Unidad + '</td>'
				+    '<td class="numerico contratado">' + concepto.CantidadSubcontratada + '</td>'
				+    '<td class="numerico contratado">' + concepto.PrecioUnitario + '</td>'
				+    '<td></td>'
				+    '<td class="numerico">' + concepto.CantidadEstimadaTotal + '</td>'
				+    '<td class="numerico">' + concepto.PctAvance + '</td>'
				+    '<td></td>'
				+    '<td class="numerico">' + concepto.MontoEstimadoTotal + '</td>'
				+    '<td class="numerico">' + concepto.CantidadSaldo + '</td>'
				+    '<td class="numerico">' + concepto.MontoSaldo + '</td>'
				+    '<td class="editable-cell numerico">' + concepto.CantidadEstimada + '</td>'
				+    '<td class="editable-cell numerico">' + concepto.PctEstimado + '</td>'
				+    '<td class="numerico">' + concepto.PrecioUnitario + '</td>'
				+    '<td class="editable-cell numerico">' + concepto.ImporteEstimado + '</td>'
				+    '<td title="' + concepto.RutaDestino + '">' + concepto.RutaDestino + '</td>'
			    +  '</tr>';
		};
		
		return html;
	},

	fillConceptosList: function( data ) {

		var html = this.conceptosListTemplate( data );

		$('#tabla-conceptos tbody').html( html );
	},

	guardaTransaccion: function() {

		var that = this;

		DATA_LOADER.show();

		that.desmarcaConceptosError();

		$.ajax({
			type: 'GET',
			url: that.urls.tranController,
			data: {
				IDProyecto: that.getIDProyecto(),
				IDTransaccion: that.getIDTransaccion(),
				IDSubcontrato: that.getIDSubcontrato(),
				datosGenerales: {
					'Fecha': $('#txtFechaDB').val(),
					'FechaInicio': $('#txtFechaInicioDB').val(),
					'FechaTermino': $('#txtFechaTerminoDB').val(),
					'Observaciones': $('#txtObservaciones').val()
				},
				conceptos: that.getConceptosEstimados(),
				action: 'guardaTransaccion'
			},
			dataType: 'json'
		}).done( function(json) {

			if( ! json.success ) {
				messageConsole.displayMessage( json.message, 'error' );
				return;
			}

			if ( ! that.getIDTransaccion() ) {
				$('#folios-transaccion').buttonlist('addListItem', 
					{id: json.IDTransaccion, text: json.NumeroFolio}, 'start');
				
				$('#folios-transaccion').buttonlist('setSelectedItemById', 
					json.IDTransaccion, false );
				
				$('#txtFolioConsecutivo').text(json.NumeroFolioConsecutivo);
				
				that.deshabilitaFechaTransaccion();
				that.setURLFormatoPDF();
			}

	 		that.setTotalesTransaccion(json.totales);

	 		if ( json.errores.length > 0 ) {
	 			that.marcaConceptoError(json.errores);
	 			messageConsole.displayMessage( 'Existen errores en algunos conceptos, por favor revise y guarde otra vez.', 'error');
	 		} else {

	 			$('#guardar').removeClass('alert');
	 			messageConsole.displayMessage( 'La transacción se guardó correctamente.', 'success');
	 		}
	 		
		}).always( function() {
			DATA_LOADER.hide();
		});
	},

	eliminaTransaccion: function() {

		var that = this;

		if( ! that.getIDTransaccion() ) {
			messageConsole.displayMessage( 'No hay una estimación cargada.', 'error' );
			return;
		}

		if ( ! confirm('La estimación será eliminada, desea continuar?') )
			return;

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
			url: that.urls.tranController,
			data: {
				IDTransaccion: that.getIDTransaccion(),
				action: 'eliminaTransaccion'
			},
			dataType: 'json'
		}).done( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage( json.message, 'error' );
					return;
				}

				that.limpiaDatosTransaccion();
				that.deshabilitaCamposTransaccion();
				$('#folios-transaccion').buttonlist('refresh');
				that.setURLFormatoPDF();

			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		}).always( function() {
			DATA_LOADER.hide();
		});
	},

	setTotalesTransaccion: function( totales ) {
		// Establece los totales de transaccion
		if( totales.length ) {
			$('#txtSumaImportes').text(totales[0].SumaImportes);
			$('#txtAmortAnticipo').text(totales[0].ImporteAmortizacionAnticipo);
			$('#txtAnticipoLiberar').text(totales[0].ImporteAnticipoLiberar);
			$('#txtFondoGarantia').text(totales[0].ImporteFondoGarantia);
			$('#txtSumaDeductivas').text(totales[0].SumaDeductivas);
			$('#txtSumaRetenciones').text(totales[0].SumaRetenciones);
			$('#txtRetencionIVA').text(totales[0].ImporteRetencionIVA);
			$('#txtRetencionesLiberadas').text(totales[0].SumaRetencionesLiberadas);
			$('#rsSubtotal').text(totales[0].Subtotal);

			this.setSubtotal(totales[0].Subtotal);
			this.setIVA(totales[0].IVA);
			$('#rsIVA').text(totales[0].IVA);
			this.setTotal(totales[0].Total);
			$('#rsTotal').text(totales[0].Total);
		}
	},

	setSubtotal: function( monto ) {
		$('#txtSubtotal').text(monto);
	},

	setIVA: function( monto ) {
		$('#txtIVA').text(monto);
	},

	setTotal: function( monto ) {
		$('#txtTotal').text(monto);
	},

	getVolumenSubcontratado: function() {
		
		return parseFloat(this.uxtable('getCell', 3).text().replace(/,/g, ''));
	},

	getPrecioUnitarioConcepto: function() {

		return parseFloat(this.uxtable('getCell', 14).text().replace(/,/g, ''));
	},

	getVolumenSaldo: function() {

		return parseFloat(this.uxtable('getCell', 10).text().replace(/,/g, ''));
	},

	setCantidadEstimada: function( cantidadEstimada ) {

		this.uxtable('getCell', 12).text( cantidadEstimada.toFixed(4).numFormat() );
	},

	setPorcentajeEstimado: function( pctEstimado ) {

		this.uxtable('getCell', 13).text( pctEstimado.toFixed(2) );
	},

	setImporteEstimado: function( importeEstimado ) {

		this.uxtable('getCell', 15).text( importeEstimado.toFixed(2).numFormat() );
	},

	getConceptosEstimados: function() {

		var estimados = [], row = null;

		$('#tabla-conceptos tr.estimado').each( function() {			
			row = $(this);

			estimados[estimados.length] = {
				'IDConceptoContrato': row.attr('data-id'),
				'IDConceptoDestino': row.attr('data-iddestino'),
				'importeEstimado': row.children(':eq(15)').text()
			}
		});

		return estimados;
	},

	marcaConceptoError: function( errores ) {

		for( error in errores ) {

			$('tr[data-id=' + errores[error].IDConceptoContrato + ']')
			.addClass('error')
			.find('.icon')
			.addClass('error')
			.attr('title', errores[error].message);
		}
	},

	desmarcaConceptosError: function() {
		$('#tabla-conceptos')
		.find('tr.estimado')
		.removeClass('error')
		.find('.icon')
		.removeClass('error')
		.removeAttr('title');
	},

	desmarcaConceptoError: function( IDConcepto ) {

		$('tr[data-id=' + IDConcepto + ']')
		.removeClass('error')
		.find('.icon')
		.removeClass('error')
		.removeAttr('title');
	},

	marcaConceptoEstimado: function( IDConcepto ) {
		
		$('tr[data-id=' + IDConcepto + ']').addClass('estimado');
	},

	desmarcaConceptoEstimado: function( IDConcepto ) {

		$('tr[data-id=' + IDConcepto + ']').removeClass('estimado');
		this.desmarcaConceptoError( IDConcepto );
	},

	identificaModificacion: function() {
		
		$('#guardar').addClass('alert');
	},

	existenCambiosSinGuardar: function() {
		
		return $('#guardar').hasClass('alert');
	},


};

var Deductivas = {

	tiposMaterial: {
		materiales: 1,
		manoobra: 2,
		servicios: 3,
		herramienta: 4,
		maquinaria: 8
	},
	tiposAlmacen: {
		maquinaria: 2
	},
	urls: {
		insumoController: 'inc/lib/controllers/InsumoController.php',
		almacenController: 'inc/lib/controllers/AlmacenController.php'
	},
	selectedIDInsumo: 0,
	selectedTipoDeductiva: 0,

	init: function() {

		var that = this;

		$('#tipo_deductiva').buttonset();

		$('#dialog-deductivas').dialog({
			autoOpen: false,
			modal: true,
			width: 650
		});

		$('#dialog-nueva-deduccion').dialog({
			autoOpen: false,
			modal: true,
			resizable: false,
			buttons: {
				Aceptar: function() {
					that.guardaDeductiva();
				}
			},
			close: function() {
				$('#tipo_deductiva input').prop('checked', false);
				$('#tipo_deductiva').buttonset('refresh');
				that.selectedIDInsumo = 0;
				that.selectedTipoDeductiva = 0;
			}
		});

		$("#txtConceptoDeductiva").autocomplete({
		    minLength: 3,
		    select: function( event, ui ) {
		    	that.selectedIDInsumo = ui.item.id
		    }
		});

		$('#tipo_deductiva').on('click', 'input', function(event) {

			$("#txtConceptoDeductiva").autocomplete( "option", "disabled", false );
			
			that.selectedTipoDeductiva = parseInt($(this).val());

			switch ( this.id ) {

				case 'materiales':
					$("#txtConceptoDeductiva").autocomplete('option', 'source',
						that.configuraSourceAutocomplete(that.tiposMaterial.materiales));
					that.showNuevaDeduccion();
					break;

				case 'mano_obra':
					$("#txtConceptoDeductiva").autocomplete('option', 'source',
						that.configuraSourceAutocomplete(that.tiposMaterial.manoobra));
					that.showNuevaDeduccion();
					break;

				case 'maquinaria':
					$("#txtConceptoDeductiva").autocomplete('option', 'source',
						that.configuraSourceAutocomplete(that.tiposMaterial.maquinaria));
					that.showNuevaDeduccion();
					break;

				default:
					$("#txtConceptoDeductiva").autocomplete( "option", "disabled", true );
					that.showNuevaDeduccion();
					break;
			}
		});

		$('#registros_deductivas table').on('click', '.action.delete', that.eliminaDeductiva);
	},

	showNuevaDeduccion: function() {
		$("#txtConceptoDeductiva, #txtObservacionesDeductiva").val('');
		$("#txtImporteDeductiva").val(0);
		$('#dialog-nueva-deduccion').dialog('open');
	},

	configuraSourceAutocomplete: function( tipoMaterial ) {
		var that = this;

		return function( request, response ) {
		        
		        var src;

		        switch ( that.selectedTipoDeductiva ) {
		        	case 3:
		        		src = that.urls.almacenController;
		        		request.IDTipoAlmacen = that.tiposAlmacen.maquinaria;
		        		request.IDProyecto    = ESTIMACION.getIDProyecto();
		        		request.action = 'listaAlmacenes';
		        		$.getJSON( src, request, function( data, status, xhr ) {
							var almacenes = [];
				            
				            for( i = 0; i < data.almacenes.length; i++ ){
							   almacenes.push({
							   		id: data.almacenes[i].IDAlmacen,
							   		label: data.almacenes[i].Descripcion
							   	});
							}
							
							response( almacenes );
						});
		        		break;
		        	default:
		        		src = that.urls.insumoController;
		        		request.IDTipoInsumo = tipoMaterial;
		        		request.action = 'listaInsumos';
		        		$.getJSON( src, request, function( data, status, xhr ) {
							var insumos = [];
				            	
				            for( i = 0; i < data.insumos.length; i++ ){
							   insumos.push({
							   		id: data.insumos[i].IDInsumo,
							   		label: data.insumos[i].Descripcion
							   	});
							}
							
							response( insumos );
						});
		        		break;
		        }
			}
	},

	guardaDeductiva: function() {

		var that = this;

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
			url: ESTIMACION.urls.tranController,
			data: {
				IDTransaccion: ESTIMACION.getIDTransaccion(),
				IDInsumo: that.selectedIDInsumo,
				IDTipoDeductiva: that.selectedTipoDeductiva,
				concepto: $('#txtConceptoDeductiva').val(),
				importe: $('#txtImporteDeductiva').val(),
				observaciones: $('#txtObservacionesDeductiva').val(),
				action: 'guardaDeductiva'
			},
			dataType: 'json'
		}).done( function(json) {

			if( ! json.success ) {
				messageConsole.displayMessage( json.message, 'error' );
				return;
			}
			
			// Ingresar la deduccion a la lista
			that.agregaDeductiva(json.IDDeductiva, that.selectedTipoDeductiva, $('#txtConceptoDeductiva').val(), $('#txtImporteDeductiva').val(), $('#txtObservacionesDeductiva').val());
			ESTIMACION.cargaTotales();
			$('#dialog-nueva-deduccion').dialog('close');
		}).always( function() {
			DATA_LOADER.hide();
		});
	},

	agregaDeductiva: function( IDDeductiva, tipo, concepto, importe, observaciones ) {

		var deductiva =
			  '<tr data-id="' + IDDeductiva + '">'
			+   '<td>' + tipo + '</td>'
			+   '<td>' + concepto + '</td>'
			+   '<td class="numerico">' + importe.numFormat() + '</td>'
			+   '<td>' + observaciones + '</td>'
			+   '<td class="icon-cell"><a class="icon action delete"></a></td>'
			+ '</tr>';

		$('#registros_deductivas table tbody').append(deductiva);
	},

	cargaDeductivas: function() {

		var that = this;

		$('#registros_deductivas table tbody').empty();

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
			url: ESTIMACION.urls.tranController,
			data: {
				IDTransaccion: ESTIMACION.getIDTransaccion(),
				action: 'getDeductivas'
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

				$.each(json.deductivas, function() {
					that.agregaDeductiva( this.IDDeductiva, this.TipoDeductiva, this.Concepto, this.Importe, this.Observaciones );
				});

				$('#dialog-deductivas').dialog('open');
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		})
		.fail( function() {
			$.notify({text: 'Ocurrió un error al cargar los datos.'});
		})
		.always( function() {
			DATA_LOADER.hide();
		});
	},

	eliminaDeductiva: function( event ) {

		var that = this;
		var IDDeductiva = parseInt($(that).parents('tr').attr('data-id'));

		if ( ! confirm('La deductiva sera eliminada, continuar?') ) {
			return;
		}

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
			url: ESTIMACION.urls.tranController,
			data: {
				IDTransaccion: ESTIMACION.getIDTransaccion(),
				IDDeductiva: IDDeductiva,
				action: 'eliminaDeductiva'
			},
			dataType: 'json'
		}).done( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage(json.message, 'error');
					return;
				}

				$(that).parents('tr').remove();
				ESTIMACION.cargaTotales();
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		})
		.fail( function() {
			$.notify({text: 'Ocurrió un error al cargar los datos.'});
		})
		.always( function() {
			DATA_LOADER.hide();
		});
	}
}

var Retenciones = {

	init: function() {

		var that = this;

		$('#dialog-retenciones').dialog({
			autoOpen: false,
			modal: true,
			width: 550,
			buttons: {
				Cerrar: function() {
					$(this).dialog('close');
				}
			}
		});

		$('#dialog-nueva-retencion').dialog({
			autoOpen: false,
			modal: true,
			resizable: false,
			buttons: {
				Aceptar: function() {
					that.guardaRetencion();
				}
			}
		});

		$('#dialog-nueva-liberacion').dialog({
			autoOpen: false,
			modal: true,
			resizable: false,
			buttons: {
				Aceptar: function() {
					that.guardaLiberacion();
				}
			}
		});

		$('#tipos-retencion').buttonlist({
			source: ESTIMACION.urls.tranController,
			data: {action: 'getTiposRetencion'},
			onSelect: function( selectedItem, listItem ) {
			},
			didNotDataFound: function() {
				$.notify({text: 'No se pudo cargar la lista.'});
			},
			onCreateListItem: function() {
				return {
					id: this.IDTipoRetencion,
					value: this.TipoRetencion
				}
			}
		});

		$('#btnNuevaRetencion').on( 'click', function() {
			that.showNuevaRetencion();
		});
		$('#btnLiberaRetencion').on( 'click', function() {
			that.showNuevaLiberacion();
		});

		$('#registros_retenciones table').on('click', '.action.delete', that.eliminaRetencion);
		$('#registros_liberaciones table').on('click', '.action.delete', that.eliminaLiberacion);
	},

	showNuevaRetencion: function() {
		$("#txtConceptoRetencion, #txtObservacionesRetencion").val('');
		$('#txtImporteRetencion').val(0);
		$('#tipos-retencion').buttonlist('refresh');
		$('#dialog-nueva-retencion').dialog('open');
	},

	showNuevaLiberacion: function() {
		$("#txtObservacionesLiberacion").val('');
		$('#txtImporteLiberacion').val(0);

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
			url: ESTIMACION.urls.tranController,
			data: {
				IDTransaccion: ESTIMACION.getIDTransaccion(),
				action: 'getImportePorLiberar'
			},
			dataType: 'json'
		}).done( function(json) {

			$('#txtImportePorLiberar').text(json.importePorLiberar);
			$('#dialog-nueva-liberacion').dialog('open');
		}).always(function() {
			DATA_LOADER.hide();
		});
	},

	agregaRetencion: function( retObj ) {

		row = '<tr data-id="' + retObj.IDRetencion + '">'
			 +   '<td>' + retObj.TipoRetencion + '</td>'
			 +   '<td class="numerico">' + retObj.importe + '</td>'
			 +   '<td>' + retObj.concepto + '</td>'
			 +   '<td>' + retObj.observaciones + '</td>'
			 +   '<td class="icon-cell">'
			 +     '<span class="icon action delete"></span>'
			 +   '</td>'
			 + '</tr>';
				
		$('#registros_retenciones tbody').append(row);
	},

	agregaLiberacion: function( libObj ) {

		row = '<tr data-id="' + libObj.IDLiberacion + '">'
			 +   '<td class="numerico">' + libObj.importe + '</td>'
			 +   '<td>' + libObj.observaciones + '</td>'
			 +   '<td class="icon-cell">'
			 +     '<span class="icon action delete"></span>'
			 +   '</td>'
			 + '</tr>';
				
		$('#registros_liberaciones tbody').append(row);
	},

	guardaRetencion: function() {
		
		var that = this;

		$.ajax({
			type: 'GET',
			url: ESTIMACION.urls.tranController,
			data: {
				  IDTransaccion: ESTIMACION.getIDTransaccion()
				, IDTipoRetencion: parseInt($('#tipos-retencion').buttonlist('option', 'selectedItem').value)
				, importe: $('#txtImporteRetencion').val()
				, concepto: $('#txtConceptoRetencion').val()
				, observaciones: $('#txtObservacionesRetencion').val()
				, action: 'guardaRetencion'
			},
			dataType: 'json'
		}).success( function(json) {
			try {
				
				if( ! json.success ) { messageConsole.displayMessage(json.message, 'error'); return false; }
				
				var retObj = {
					'IDRetencion': json.IDRetencion,
					'TipoRetencion': $('#tipos-retencion').buttonlist('option', 'selectedItem').label,
					'importe': $('#txtImporteRetencion').val().numFormat(),
					'concepto': $('#txtConceptoRetencion').val(),
					'observaciones': $('#txtObservacionesRetencion').val()
				};

				that.agregaRetencion( retObj );
				
				$('#dialog-nueva-retencion').dialog('close');

				ESTIMACION.cargaTotales();
				
			} catch(e) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		});
	},

	guardaLiberacion: function() {
		
		var that = this;

		$.ajax({
			type: 'GET',
			url: ESTIMACION.urls.tranController,
			data: {
				  IDTransaccion: ESTIMACION.getIDTransaccion()
				, importe: $('#txtImporteLiberacion').val()
				, observaciones: $('#txtObservacionesLiberacion').val()
				, action: 'guardaLiberacion'
			},
			dataType: 'json'
		}).success( function(json) {
			try {
				
				if( ! json.success ) { messageConsole.displayMessage(json.message, 'error'); return false; }
				
				var libObj = {
					'IDLiberacion': json.IDLiberacion,
					'importe': $('#txtImporteLiberacion').val().numFormat(),
					'observaciones': $('#txtObservacionesLiberacion').val()
				};

				that.agregaLiberacion( libObj );
				
				$('#dialog-nueva-liberacion').dialog('close');

				ESTIMACION.cargaTotales();
				
			} catch(e) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		});
	},

	eliminaRetencion: function() {

		var that = this;

		var IDRetencion = parseInt($(that).parents('tr').attr('data-id'));

		if ( ! confirm('La retención sera eliminada, continuar?') ) {
			return;
		}

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
			url: ESTIMACION.urls.tranController,
			data: {
				IDTransaccion: ESTIMACION.getIDTransaccion(),
				IDRetencion: IDRetencion,
				action: 'eliminaRetencion'
			},
			dataType: 'json'
		})
		.done( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage( json.message, 'error' );
					return;
				}
				
				$(that).parents('tr').remove();

				ESTIMACION.cargaTotales();
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		})
		.always( function() {
			DATA_LOADER.hide();
		});
	},

	eliminaLiberacion: function() {

		var that = this;

		var IDLiberacion = parseInt($(that).parents('tr').attr('data-id'));

		if ( ! confirm('La liberación sera eliminada, continuar?') ) {
			return;
		}

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
			url: ESTIMACION.urls.tranController,
			data: {
				IDTransaccion: ESTIMACION.getIDTransaccion(),
				IDLiberacion: IDLiberacion,
				action: 'eliminaLiberacion'
			},
			dataType: 'json'
		})
		.done( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage( json.message, 'error' );
					return;
				}
				
				$(that).parents('tr').remove();

				ESTIMACION.cargaTotales();
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		})
		.always( function() {
			DATA_LOADER.hide();
		});
	},

	cargaRetenciones: function() {

		var that = this;

		$('#registros_retenciones tbody').empty();
		$('#registros_liberaciones tbody').empty();

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
			url: ESTIMACION.urls.tranController,
			data: {
				IDTransaccion: ESTIMACION.getIDTransaccion(),
				action: 'getRetenciones'
			},
			dataType: 'json'
		}).success( function( json ) {
			try {

				if( ! json.success ) {
					messageConsole.displayMessage(json.message, 'error');
					return;
				}
				
				$.each( json.retenciones, function() {

					that.agregaRetencion( this );
				});

				$.each( json.liberaciones, function() {
					that.agregaLiberacion( this );
				});

				$('#dialog-retenciones').dialog('open');
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		}).fail( function() {
			$.notify({text: 'Ocurrió un error al cargar las retenciones.'});
		}).always( function() {
			DATA_LOADER.hide();
		});;
	}
};

// funciones Mediators que llamaran las notificaciones
var modifiedTran = function( event, data ) {
	ESTIMACION.identificaModificacion();
};

var notifyModifiedTran = function( event, data ) {
	if( confirm('Desea continuar sin guardar los cambios?...') ) {
		//if( typeof data === 'object')
			data.call(ESTIMACION);
	}
}