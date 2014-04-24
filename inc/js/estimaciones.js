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
	colClasses: {
		contratado: 'contratado',
		avance_volumen: 'avance-volumen',
		avance_importe: 'avance-importe',
		saldo: 'saldo',
		destino: 'destino'
	},
	urls: {
		tranController: 'inc/lib/controllers/EstimacionSubcontratoController.php'
	},
	templateConcepto: null,

	init: function() {

		var that = this;

		Retenciones.init();
		Deductivas.init();

		this.deshabilitaCamposTransaccion();
		this.limpiaDatosTransaccion();
		this.templateConcepto = _.template($('#template-concepto').html());

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
			data: {action: 'getListaProyectos'},
			onSelect: function( selectedItem, listItem ) {

				$('#btnLista-transacciones').listaTransacciones('option', 'data', 
					{
						base_datos: that.getBaseDatos(),
						id_obra: selectedItem.value,
						action: 'getListaTransacciones'
					}
				);

				$('#folios-transaccion')
				.buttonlist('option', 'data', 
					{
						base_datos: that.getBaseDatos(),
						id_obra: selectedItem.value,
						action: 'getFoliosTransaccion'
					}
				);

				$('#folios-transaccion').buttonlist('refresh');

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
			onLoad: function() { DATA_LOADER.show() },
			beforeLoad: function() {

				if( ! that.getIdObra() ) {
					$.notify({text: 'Seleccione un proyecto para cargar las transacciones.'});
					return false;
				} else {
					return true;
				}
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

				if ( ! that.getIdObra() ) {
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
		//  .buttonset()
		//  .buttonset({disabled: true})
		 .on('click', 'input', function( event ) {
		 	$('#tabla-conceptos').css('width', 0);
		 	$('#tabla-conceptos .' + this.id).toggleClass('hidden');
		 	$('#tabla-conceptos').css('width', '100%');
		 });

		that.setConceptosVisibility();

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

		$('#txtAmortAnticipo, #txtFondoGarantia, #txtRetencionIVA, #txtAnticipoLiberar')
		.on('click', function(event) {
		    
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
		    		type: 'POST',
		    		url: that.urls.tranController,
		    		data: {
		    			base_datos: that.getBaseDatos(),
		    			id_obra: that.getIdObra(),
		    			id_transaccion: that.getIDTransaccion(),
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

		$('.col-switch.conceptos').multipleSelect({
	    	selectAll: false,
	    	onClick: function(option) {

	      		if (option.checked)
					$('#tabla-conceptos').find('.' + option.value).removeAttr('style');
				else
					$('#tabla-conceptos').find('.' + option.value).css('width', '0px');	    	
	    	}
		});

		that.ocultaColumnasOpcionales();
	},

	ocultaColumnasOpcionales: function() {
		var that = this;

	    $('.col-switch option').each(function(){
	    	$('#tabla-conceptos').find('.' + this.value).css('width', '0px');
	    });
	},

	muestraColumnasMarcadas: function() {
		// muestra las columnas de agrupadores
		var that = this;
		this.ocultaColumnasOpcionales();

	    $('.col-switch option').each(function() {
	    	if ( this.selected )
	    		$('#tabla-conceptos').find('.' + this.value).removeAttr('style');
	    });
	},

	nuevaTransaccion: function() {

		var that = this;

		DATA_LOADER.show();

		$.ajax({
			url: that.urls.tranController,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIdObra(),
				id_subcontrato: that.getIDSubcontrato(),
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
				
				that.renderConceptos(json.conceptos);
				that.habilitaCamposTransaccion();
				that.setConceptosVisibility();
				pubsub.publish('modified_tran');
				
				that.setURLFormatoPDF();
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		}).always( DATA_LOADER.hide );
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

	getIdObra: function() {
		return $('#bl-proyectos').buttonlist('option', 'selectedItem').value;
	},

	getBaseDatos: function() {
		return $('#bl-proyectos').buttonlist('option', 'selectedItem').extra.source
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
				base_datos: that.getBaseDatos(),
				id_obra: that.getIdObra(),
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
				id_obra: that.getIdObra(),
				base_datos: that.getBaseDatos(),
				id_transaccion: that.getIDTransaccion(),
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
				that.renderConceptos( json.conceptos );

				// Establece los totales de transaccion
				that.setTotalesTransaccion(json.totales);

				that.habilitaCamposTransaccion();
				that.deshabilitaFechaTransaccion();
				that.setURLFormatoPDF();
				that.setConceptosVisibility()

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

	setConceptosVisibility: function() {
		$('#column-switchers input').not(':checked').each(function() {
		     $('#tabla-conceptos .' + this.id).addClass('hidden');
		});
	},

	setURLFormatoPDF: function() {
		// Establece los datos de la url para la descarga del formato
		// de estimacion en pdf. Cuando se consulte una transaccion
		// esta url debe cambiar
		var url = $('#btnFormatoPDF').attr('href');

		url = url.replace(/(id_obra=)[0-9]*/ig, '$1' + this.getIdObra());
		url = url.replace(/(base_datos=)\w*/ig, '$1' + this.getBaseDatos());
		url = url.replace(/(id_transaccion=)[0-9]*/ig, '$1' + this.getIDTransaccion());

		$('#btnFormatoPDF').attr('href', url);
	},

	cargaTotales: function() {

		var that = this;

		DATA_LOADER.show();

		$.ajax({
			url: that.urls.tranController,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIdObra(),
				id_transaccion: that.getIDTransaccion(),
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

	renderConceptos: function( conceptos ) {
		var html = '';

		for (var i = 0; i < conceptos.length; i++) {
			html += this.templateConcepto(conceptos[i]);
		}

		$('#tabla-conceptos tbody').html( html );

		this.muestraColumnasMarcadas();
	},

	guardaTransaccion: function() {

		var that = this;

		DATA_LOADER.show();

		that.desmarcaConceptosError();

		$.ajax({
			type: 'POST',
			url: that.urls.tranController,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIdObra(),
				id_transaccion: that.getIDTransaccion(),
				id_subcontrato: that.getIDSubcontrato(),
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
	 		
		}).always( DATA_LOADER.hide );
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
			type: 'POST',
			url: that.urls.tranController,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIdObra(),
				id_transaccion: that.getIDTransaccion(),
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
		}).always( DATA_LOADER.hide );
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

	urls: {
		insumoController: 'inc/lib/controllers/InsumoController.php',
		almacenController: 'inc/lib/controllers/AlmacenController.php'
	},
	template: null,
	col_switch: null,

	init: function() {

		var that = this;

		this.template = _.template($('#template-deductiva').html());

		$('#dialog-deductivas').dialog({
			autoOpen: false,
			modal: true,
			width: 750
		});

		$('#form_descuento').on('submit', function(event) {
			event.preventDefault();
			that.guardaDescuento();
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
				that.id_material = 0;
				that.selectedTipoDeductiva = 0;
			}
		});

		$("#txtConceptoDeductiva").autocomplete({
		    minLength: 3,
		    select: function( event, ui ) {
		    	that.id_material = ui.item.id
		    }
		});

		this.col_switch = $('.col-switch.descuentos');
		this.col_switch.multipleSelect({
	    	selectAll: false,
	    	onClick: function(option) {
	    		var $target = $('#registros_deductivas');

	      		if (option.checked)
					$target.find('.' + option.value).removeAttr('style');
				else
					$target.find('.' + option.value).css('width', '0px');	    	
	    	}
		});

		this.ocultaColumnas();
		this.muestraColumnasMarcadas();

		// $('#registros_deductivas table').on('click', '.action.delete', that.eliminaDeductiva);
	},

	ocultaColumnas: function() {
	    this.col_switch.find('option').each(function(){
	    	$('#registros_deductivas').find('.' + this.value).css('width', '0px');
	    });
	},

	muestraColumnasMarcadas: function() {
		this.ocultaColumnas();

	    this.col_switch.find('option').each(function() {
	    	if ( this.selected )
	    		$('#registros_deductivas').find('.' + this.value).removeAttr('style');
	    });
	},

	showNuevaDeduccion: function() {
		$("#txtConceptoDeductiva, #txtObservacionesDeductiva").val('');
		$("#txtImporteDeductiva").val(0);
		$('#dialog-nueva-deduccion').dialog('open');
	},

	guardaDescuento: function() {
		var that = this;

		var descuentos = [];

		$('#registros_deductivas tbody tr').each( function() {
			// console.log(this)
			var $row = $(this);

			descuentos[descuentos.length] = {
				id_item: $row.find('input[name="id_item"]').val(),
				cantidad_descuento: $row.find('input[name="cantidad_descuento"]').val(),
				precio_descuento: $row.find('input[name="precio_descuento"]').val()
			}
		});

		DATA_LOADER.show();

		$.ajax({
			type: 'POST',
			url: ESTIMACION.urls.tranController,
			data: {
				base_datos: ESTIMACION.getBaseDatos(),
				id_obra: ESTIMACION.getIdObra(),
				id_transaccion: ESTIMACION.getIDTransaccion(),
				descuentos: descuentos,
				action: 'guardaDescuento'
			},
			dataType: 'json'
		}).done( function(data) {

			if( ! data.success ) {
				messageConsole.displayMessage( data.message, 'error' );
				return;
			}

			$('#dialog-deductivas').dialog('close');
			messageConsole.displayMessage( "Los descuentos se guardaron correctamente", 'success' );
		}).always( DATA_LOADER.hide );
	},

	guardaDeductiva: function() {

		var that = this;

		DATA_LOADER.show();

		$.ajax({
			type: 'POST',
			url: ESTIMACION.urls.tranController,
			data: {
				base_datos: ESTIMACION.getBaseDatos(),
				id_obra: ESTIMACION.getIdObra(),
				id_transaccion: ESTIMACION.getIDTransaccion(),
				id_material: that.id_material,
				IDTipoDeductiva: that.selectedTipoDeductiva,
				concepto: $('#txtConceptoDeductiva').val(),
				importe: $('#txtImporteDeductiva').val(),
				observaciones: $('#txtObservacionesDeductiva').val(),
				action: 'guardaDeductiva'
			},
			dataType: 'json'
		}).done( function(data) {

			if( ! data.success ) {
				messageConsole.displayMessage( data.message, 'error' );
				return;
			}
			
			var deductiva = {
				id: data.IDDeductiva,
				tipo: that.selectedTipoDeductiva,
				concepto: $('#txtConceptoDeductiva').val(),
				importe: $('#txtImporteDeductiva').val(),
				observaciones: $('#txtObservacionesDeductiva').val()
			}
			// Ingresar la deduccion a la lista
			that.renderDeductiva( deductiva );

			ESTIMACION.cargaTotales();
			
			$('#dialog-nueva-deduccion').dialog('close');
		}).always( DATA_LOADER.hide );
	},

	renderDeductiva: function( deductiva ) {
		$('#registros_deductivas table tbody').append(this.template( deductiva ));
	},

	cargaDeductivas: function() {

		var that = this;

		$('#registros_deductivas table tbody').empty();

		DATA_LOADER.show();

		$.ajax({
			url: ESTIMACION.urls.tranController,
			data: {
				base_datos: ESTIMACION.getBaseDatos(),
				id_obra: ESTIMACION.getIdObra(),
				id_transaccion: ESTIMACION.getIDTransaccion(),
				action: 'getDeductivas'
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

				$.each( data.deductivas, function() {
					that.renderDeductiva({
						id_descuento: this.id_descuento,
						id_item: this.id_item,
						descripcion: this.descripcion,
						cantidad_total: this.cantidad_total,
						unidad: this.unidad,
						precio: this.precio,
						importe_total: this.importe_total,
						cantidad_descontada: this.cantidad_descontada,
						importe_descontado: this.importe_descontado,
						cantidad_por_descontar: this.cantidad_por_descontar,
						importe_por_descontar: this.importe_por_descontar,
						cantidad_descuento: this.cantidad_descuento,
						precio_descuento: this.precio_descuento,
						importe_descuento: this.importe_descuento,
					});
				});

				$('#dialog-deductivas').dialog('open');
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		})
		.fail( function() {
			$.notify({text: 'Ocurrió un error al cargar los datos.'});
		})
		.always( DATA_LOADER.hide );
	},

	eliminaDeductiva: function( event ) {

		var that = this;
		var IDDeductiva = parseInt($(that).parents('tr').attr('data-id'));

		if ( ! confirm('La deductiva sera eliminada, continuar?') ) {
			return;
		}

		DATA_LOADER.show();

		$.ajax({
			type: 'POST',
			url: ESTIMACION.urls.tranController,
			data: {
				base_datos: ESTIMACION.getBaseDatos(),
				id_obra: ESTIMACION.getIdObra(),
				id_transaccion: ESTIMACION.getIDTransaccion(),
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
		.always( DATA_LOADER.hide );
	}
}

var Retenciones = {
	template: _.template($('#template-retencion').html()),
	
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

		$('#tipos-retencion').buttonlist('option', 'data', {base_datos: ESTIMACION.getBaseDatos(), action: 'getTiposRetencion'});

	},

	showNuevaLiberacion: function() {
		$("#txtObservacionesLiberacion").val('');
		$('#txtImporteLiberacion').val(0);

		DATA_LOADER.show();

		$.ajax({
			url: ESTIMACION.urls.tranController,
			data: {
				base_datos: ESTIMACION.getBaseDatos(),
				id_obra: ESTIMACION.getIdObra(),
				id_transaccion: ESTIMACION.getIDTransaccion(),
				action: 'getImportePorLiberar'
			},
			dataType: 'json'
		}).done( function(json) {

			$('#txtImportePorLiberar').text(json.importePorLiberar);
			$('#dialog-nueva-liberacion').dialog('open');
		}).always( DATA_LOADER.hide );
	},

	renderRetencion: function( retencion ) {
		var html = this.template(retencion);

		$('#registros_retenciones tbody').append(html);
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
			type: 'POST',
			url: ESTIMACION.urls.tranController,
			data: {
				base_datos: ESTIMACION.getBaseDatos(),
				id_obra: ESTIMACION.getIdObra(),
				id_transaccion: ESTIMACION.getIDTransaccion(),
				IDTipoRetencion: parseInt($('#tipos-retencion').buttonlist('option', 'selectedItem').value),
				importe: $('#txtImporteRetencion').val(),
				concepto: $('#txtConceptoRetencion').val(),
				observaciones: $('#txtObservacionesRetencion').val(),
				action: 'guardaRetencion'
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

				that.renderRetencion( retObj );
				
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
			type: 'POST',
			url: ESTIMACION.urls.tranController,
			data: {
				base_datos: ESTIMACION.getBaseDatos(),
				id_obra: ESTIMACION.getIdObra(),
				id_transaccion: ESTIMACION.getIDTransaccion(),
				importe: $('#txtImporteLiberacion').val(),
				observaciones: $('#txtObservacionesLiberacion').val(),
				action: 'guardaLiberacion'
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
			type: 'POST',
			url: ESTIMACION.urls.tranController,
			data: {
				base_datos: ESTIMACION.getBaseDatos(),
				id_obra: ESTIMACION.getIdObra(),
				id_transaccion: ESTIMACION.getIDTransaccion(),
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
		.always( DATA_LOADER.hide );
	},

	eliminaLiberacion: function() {

		var that = this;

		var IDLiberacion = parseInt($(that).parents('tr').attr('data-id'));

		if ( ! confirm('La liberación sera eliminada, continuar?') ) {
			return;
		}

		DATA_LOADER.show();

		$.ajax({
			type: 'POST',
			url: ESTIMACION.urls.tranController,
			data: {
				base_datos: ESTIMACION.getBaseDatos(),
				id_transaccion: ESTIMACION.getIDTransaccion(),
				id_obra: ESTIMACION.getIdObra(),
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
			url: ESTIMACION.urls.tranController,
			data: {
				base_datos: ESTIMACION.getBaseDatos(),
				id_obra: ESTIMACION.getIdObra(),
				id_transaccion: ESTIMACION.getIDTransaccion(),
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
					that.renderRetencion( this );
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
		}).always( DATA_LOADER.hide );
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