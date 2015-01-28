$( function() {
	AVANCE.init();
});

var pubsub = PubSub();

var AVANCE = {

	classes: {
		conceptoModificado: 'modificado'
	},
	urls: {
		tranController: 'inc/lib/controllers/AvanceSubcontratoController.php'
	},
	templateConcepto: null,

	init: function() {

		var that = this;

		this.templateConcepto = _.template($('#template-concepto').html());

		// Suscripcion al evento transaccion modificada
		var modifiedTranSubscription = pubsub.subscribe('modified_tran', modifiedTran);
		// Suscripcion al evento que notifica cuando la transaccion tiene cambios por guardar
		var notifyModifiedTranSubs = pubsub.subscribe('notify_modtran', notifyModifiedTran);

		$('#tabla-conceptos').on('keyup', 'input[type=text]', function () {

			var oldValue = $(this).val();

			$(this).val(oldValue.numFormat());
		});

		$('#bl-proyectos').buttonlist({
			source: 'inc/lib/controllers/ListaObrasController.php',
			data: {action: 'getListaProyectos'},
			onSelect: function (selectedItem, listItem) {

				$('#folios-transaccion')
					.buttonlist('option', 'data',
					{
						base_datos: that.getBaseDatos(),
						id_obra: selectedItem.value,
						action: 'getFoliosTransaccion'
					});

				$('#folios-transaccion').buttonlist('refresh');

				$('#btnLista-transacciones').listaTransacciones('option', 'data',
					{
						base_datos: that.getBaseDatos(),
						id_obra: selectedItem.value,
						action: 'getListaTransacciones'
					}
				);

				that.limpiaDatosTransaccion();
				that.deshabilitaCamposTransaccion();
			},
			didNotDataFound: function () {
				$.notify({text: 'No se pudo cargar la lista de proyectos'});
			},
			onCreateListItem: function () {
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
			beforeLoad: function () {

				if (!that.getIDObra()) {

					$.notify({text: 'Seleccione una obra para cargar los folios.'});
					return false;
				} else
					return true;
			},
			onSelect: function (selectedItem, listItemElement) {
				that.cargaTransaccion();
			},
			didNotDataFound: function () {
				$.notify({text: 'No se encontraron transacciones registradas en esta obra.'});
			},
			onCreateListItem: function () {
				return {
					id: this.id_transaccion,
					value: this.numero_folio
				}
			}
		});

		$('#btnLista-transacciones').listaTransacciones({
			source: that.urls.tranController,
			data: {action: 'getListaTransacciones'},
			beforeLoad: function () {

				if ( ! that.getIDObra()) {
					$.notify({text: 'Seleccione una obra para cargar las transacciones.'});
					return false;
				} else
					return true;
			},
			onSelectItem: function (item) {
				$('#folios-transaccion').buttonlist('selectItemById', item.value, true);
			},
			onCreateListItem: function () {
				return {
					id: this.id_transaccion,
					folio: this.numero_folio,
					fecha: this.fecha,
					observaciones: this.observaciones
				};
			}
		});

		$('#tabla-conceptos').uxtable({
			editableColumns: {
				5: {
					'onFinishEdit': function (activeCell, value) {

						var id_concepto = parseInt(activeCell.parent().attr('data-id'));

						if (parseInt(activeCell.parent().attr('data-esactividad')) == 1) {
							that.setCantidadAvance.call(this, id_concepto, value);
						}

						pubsub.publish('modified_tran');
					}
				}
			}
		})
			.on('click', '.icon.action', function (event) {

				var IDConcepto = parseInt($(this).parents('tr').attr('data-id'));

				if ($(this).hasClass('checkbox')) {

					$(this).toggleClass('checkbox-unchecked checkbox-checked');
					that.marcaConcepto(IDConcepto);
				}
			});

		$('#nueva-transaccion').on('click', function (event) {

			if (that.existenCambiosSinGuardar()) {
				pubsub.publish('notify_modtran', that.nuevaTransaccion);
			}
			else {
				if ( ! that.getIDObra() ) {
					$.notify({text: 'Seleccione una obra'});
					return;
				}

				$('#folios-transaccion').buttonlist('reset');
				that.limpiaDatosTransaccion();
				that.habilitaCamposTransaccion();
				that.muestraListaSubcontratos();
			}

			event.preventDefault();
		});

		$('#eliminar').on('click', function () {
			that.eliminaTransaccion();
		});

		$('#guardar').on('click', function () {
			that.guardaTransaccion();
		});

		$('#aprobar').on('click', function () {
			that.apruebaTransaccion();
		});

		$('#revierte-aprobacion').on('click', function () {
			that.revierteAprobacion();
		});

		$('#txtFechaTransaccion, #txtFechaInicio, #txtFechaTermino, #txtFechaEjecucion, #txtFechaContable')
			.datepicker({
				dateFormat: 'dd-mm-yy',
				altFormat: 'yy-mm-dd',
				showOtherMonths: "true",
				selectOtherMonths: "true",
				buttonImage: "img/app/calendar_light-green_16x16.png",
				showOn: "both",
				buttonImageOnly: true,
				onSelect: function () {
					pubsub.publish('modified_tran');
				}
			})
			.datepicker('setDate', new Date())
			.datepicker('disable');

		$('#txtFechaTransaccion').datepicker('option', 'altField', '#txtFechaTransaccionDB');

		$('#txtFechaInicio').datepicker('option', 'altField', '#txtFechaInicioDB');

		$('#txtFechaTermino').datepicker('option', 'altField', '#txtFechaTerminoDB');

		$('#txtFechaEjecucion').datepicker('option', 'altField', '#txtFechaEjecucionDB');

		$('#txtFechaContable').datepicker('option', 'altField', '#txtFechaContableDB');

		$('#txtObservaciones').on('change', function () {
			pubsub.publish('modified_tran');
		});

		$('#dialog-subcontratos').dialog({
			autoOpen: false,
			modal: true,
			width: 760,
			height: 390,
			show: 'fold'
		});

		// Handler para la tabla de seleccion de un subcontrato a estimar
		$('#tabla-subcontratos tbody').on({
			click: function( event ) {

				$(this).siblings().removeClass('selected');
				$(this).addClass('selected');
			},
			dblclick: function( event ) {
				$('#dialog-subcontratos').dialog('close');
				that.id_subcontrato = parseInt($(this).attr('data-id'));
				that.nuevaTransaccion();
				event.preventDefault();
			}
		}, 'tr');

		this.deshabilitaCamposTransaccion();
		this.limpiaDatosTransaccion();
	},

	nuevaTransaccion: function() {
		var that = this;

		DATA_LOADER.show();

		$.ajax({
			url: that.urls.tranController,
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIDObra(),
				id_subcontrato: that.getIDSubcontrato(),
				action: 'nuevaTransaccion'
			},
			dataType: 'json'
		}).done( function( json ) {
			try {
				if( ! json.success) {
					messageConsole.displayMessage( json.message, 'error' );
					return;
				}

				// Datos del Subcontrato
				$('#txtSubcontrato').val(json.subcontrato.descripcion);
				$('#txtEmpresa').val(json.subcontrato.empresa);
				// Conceptos del subcontrato para estimacion

				that.renderConceptos(json.conceptos);
				that.habilitaCamposTransaccion();
				pubsub.publish('modified_tran');
			} catch( e ) {
				messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
			}
		}).always(DATA_LOADER.hide);
	},

	renderConceptos: function(conceptos) {
		var html = '';

		for (var i = 0; i < conceptos.length; i++) {
			html += this.templateConcepto(conceptos[i]);
		}

		$('#tabla-conceptos tbody').html( html );
	},

	renderTotales: function(totales) {
		this.setSubtotal(totales.subtotal);
		this.setIVA(totales.iva);
		this.setTotal(totales.total);
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

	getIDSubcontrato: function() {
		return this.id_subcontrato;
	},

	muestraListaSubcontratos: function() {
		var that = this;

		DATA_LOADER.show();

		$.ajax({
			type: 'GET',
			url: 'inc/lib/controllers/EstimacionSubcontratoController.php',
			data: {
				base_datos: that.getBaseDatos(),
				id_obra: that.getIDObra(),
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

	deshabilitaFechaTransaccion: function() {
		$('#txtFechaTransaccion').datepicker('disable');
	},

	deshabilitaCamposTransaccion: function() {
		this.deshabilitaFechaTransaccion();
		$('#txtFechaInicio').datepicker('disable');
		$('#txtFechaTermino').datepicker('disable');
		$('#txtObservaciones').prop('disabled', true).addClass('disabled');
	},

	habilitaCamposTransaccion: function() {
		$('#txtConceptoRaiz').prop('disabled', false).removeClass('disabled');
		$('#txtFechaTransaccion').datepicker('enable');
		$('#txtFechaInicio').datepicker('enable');
		$('#txtFechaTermino').datepicker('enable');
		$('#txtFechaEjecucion').datepicker('enable');
		$('#txtFechaContable').datepicker('enable');
		$('#txtObservaciones').prop('disabled', false).removeClass('disabled');
	},

	limpiaDatosTransaccion: function() {
		$('#tabla-conceptos tbody').empty();
		$('#txtFechaTransaccion').datepicker( 'setDate', new Date() );
		$('#txtFechaInicio').datepicker('setDate', new Date());
		$('#txtFechaTermino').datepicker('setDate', new Date());
		$('#txtFechaEjecucion').datepicker('setDate', new Date());
		$('#txtFechaContable').datepicker('setDate', new Date());
		$('#txtObservaciones').val('');
		$('#txtSubtotal, #txtIVA, #txtTotal').text('');
		$('#guardar').removeClass('alert');
	},

	getTotalesTransaccion: function() {

		var that = this,
			IDTransaccion = this.getIDTransaccion();

		$.ajax({
			type: 'GET',
			url: urls.tranController,
			data: {
				IDTransaccion: IDTransaccion,
				action: 'getTotalesTransaccion'
			},
			dataType: 'json'
		}).done( function(json) {
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
		}).fail(function() {
			$.notify({text: 'Ocurrió un error al cargar los totales.'});
		});
	},

	setSubtotal: function($monto) {
		$('#txtSubtotal').text($monto);
	},

	setIVA: function($monto) {
		$('#txtIVA').text($monto);
	},

	setTotal: function($monto) {
		$('#txtTotal').text($monto);
	},

	setCantidadAvance: function(id_concepto, cantidad) {
		var cantidad = parseFloat(cantidad.replace(/,/g, '')) || 0;

		//if ( cantidad.length > 0 || cantidad != 0 )
			AVANCE.marcaConcepto(id_concepto);
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
			type: 'GET',
			url: that.urls.tranController,
			data: {
				id_obra: that.getIDObra(),
				base_datos: that.getBaseDatos(),
				id_transaccion: $('#folios-transaccion').buttonlist('option', 'selectedItem').value,
				action: 'getTransaccion'
			},
			dataType: 'json'
		}).done(function(json) {
			try {
				if ( ! json.success) {
					messageConsole.displayMessage(json.message, 'error');
					return;
				}

				if (json.noRows) {
					$.notify({text: json.message});
					return;
				}

				// Establece los datos generales
				$('#txtSubcontrato').val(json.datos.descripcion);
				$('#txtEmpresa').val(json.datos.empresa);
				$('#txtFechaTransaccion').datepicker('setDate', json.datos.fecha);
				$('#txtFechaInicio').datepicker('setDate', json.datos.fecha_inicio);
				$('#txtFechaTermino').datepicker('setDate', json.datos.fecha_termino);
				$('#txtFechaEjecucion').datepicker('setDate', json.datos.fecha_ejecucion);
				$('#txtFechaContable').datepicker('setDate', json.datos.fecha_contable );
				$('#txtObservaciones').val(json.datos.observaciones);
				that.id_subcontrato = json.datos.id_subcontrato;

				// llena la tabla de conceptos
				that.renderConceptos(json.conceptos);
				that.renderTotales(json.totales);

				that.habilitaCamposTransaccion();
				that.deshabilitaFechaTransaccion();
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

	getConceptosModificados: function() {

		var conceptos = [],
			row = null;

		$('#tabla-conceptos tr.' + this.classes.conceptoModificado).each( function() {			
			row = $(this);

			conceptos[conceptos.length] = {
				'id_item': row.attr('data-iditem'),
				'id_concepto': row.attr('data-id'),
				'cantidad': row.children(':eq(5)').text(),
			}
		});

		return conceptos;
	},

	guardaTransaccion: function() {

		var that = this;

		var id_transaccion = that.getIDTransaccion(),
			conceptos = that.getConceptosModificados();

			DATA_LOADER.show();

			$.ajax({
				type: 'POST',
				url: that.urls.tranController,
				data: {
					id_obra: that.getIDObra(),
					base_datos: that.getBaseDatos(),
					id_transaccion: id_transaccion,
					id_subcontrato: that.getIDSubcontrato(),
					fecha: $('#txtFechaTransaccionDB').val(),
					fecha_inicio: $('#txtFechaInicioDB').val(),
					fecha_termino: $('#txtFechaTerminoDB').val(),
					fecha_ejecucion: $('#txtFechaEjecucionDB').val(),
					fecha_contable: $('#txtFechaContableDB').val(),
					observaciones: $('#txtObservaciones').val(),
					conceptos: conceptos,
					action: 'guardaTransaccion'
				},
				dataType: 'json'
			}).done( function( json ) {
				try {

					if ( ! json.success) {
						messageConsole.displayMessage( json.message, 'error' );
						return;
					}

					if ( ! that.getIDTransaccion()) {
						$('#folios-transaccion').buttonlist('addListItem',
							{id: json.id_transaccion, text: json.numero_folio}, 'start');

						$('#folios-transaccion').buttonlist('setSelectedItemById',
							json.id_transaccion, false );

						that.deshabilitaFechaTransaccion();
					}

					//$('#tabla-conceptos tr.modificado').removeClass(that.classes.conceptoModificado);

					if (json.errores.length > 0) {
						that.marcaConceptosError(json.errores);
						messageConsole.displayMessage('Existen errores en algunos conceptos, por favor revise y guarde otra vez.', 'error');
					} else {
						that.renderTotales(json.totales);
						$('#guardar').removeClass('alert');
						messageConsole.displayMessage('La transacción se guardó correctamente.', 'success');
					}

				} catch( e ) {
					messageConsole.displayMessage( 'Error: ' + e.message, 'error' );
				}
			}).always( function() {
				DATA_LOADER.hide();
			});
	},

	eliminaTransaccion: function() {

		var that = this
			id_transaccion = this.getIDTransaccion();

		if ( ! id_transaccion) {
			return;
		}

		if ( ! confirm('La transacción será eliminada, desea continuar?') )
			return;

		DATA_LOADER.show();

		$.ajax({
			url: that.urls.tranController,
			data: {
				id_obra: that.getIDObra(),
				base_datos: that.getBaseDatos(),
				id_transaccion: id_transaccion,
				action: 'eliminaTransaccion'
			},
			dataType: 'json'
		}).done( function(json) {
			try {

				if( ! json.success) {
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
			DATA_LOADER.hide();
		});
	},

	desmarcaConceptosError: function() {
		$('#tabla-conceptos')
		.find('tr.' + this.classes.conceptoModificado + ' .icon')
		.removeClass('error')
		.removeAttr('title');
	},

	marcaConcepto: function(id_concepto) {
		
		$('tr[data-id=' + id_concepto + ']').addClass(this.classes.conceptoModificado);
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
};

// funciones Mediators que llamaran las notificaciones
var modifiedTran = function( event, data ) {
	AVANCE.identificaModificacion();
};

var notifyModifiedTran = function( event, data ) {
	
	if( confirm('Existen cambios sin guardar, desea continuar?...') ) {

		//if( typeof data === 'object' )
			data.call(AVANCE);
	}
}