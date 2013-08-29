$( function() {

	AGRUPACION.init();
});

var AGRUPACION = {

	container: '#agrupacion',
	dataContainer: '#conceptos',
	currentRequest: null,
	
	init: function() {
		
		var AG = this;
		
		OPCIONES.disable();
		
		$('#opciones').click( function(event) {
			
			switch( event.target.id ) {
				
				case 'cmdInsumos':
				case 'cmdSubcontratos':
				case 'cmdFacturasVarios':
				case 'cmdCuentas':
					AG.clearDataContainer();
					AG.cargaFilas(event.target.id);
				break;
			}
		});
		
		// Inicializa la lista de proyectos
		LISTA_PROYECTOS.onSelect = function(event) {

			if( AG.currentRequest )
				AG.currentRequest.abort();
			
			AG.clearDataContainer();
			AG.resetToolbar();
			OPCIONES.enable('#cmdInsumos, #cmdSubcontratos, #cmdCuentas, #cmdFacturasVarios');
		}
		
		LISTA_PROYECTOS.init();
		
		$(AG.dataContainer).click( function(event) {
			
			var $tgt = $(event.target);
			
			// Bloque que controla la expansion de las secciones
			if( $tgt.parents('.content-toggler').length || $tgt.hasClass('content-toggler') ) {
				
				var $toggler;
				
				if( $tgt.hasClass('content-toggler') )
					$toggler = $tgt;
				else
					$toggler = $tgt.parents('.content-toggler');
					
				$toggler.toggleClass('expanded');
				
			    $toggler.parent().next().slideToggle();
		    }
		    
			// Handler para control de los dropdown lists
			if( $tgt.is('a.dropdown-list-trigger') ) {

				var listContainer = $tgt.attr('href');
				var source = '';
				
				switch( listContainer ) {
					
					case '#dropdown-naturaleza':
						source = 'modulos/agrupacion/GetNaturalezas.php';
					break;
					case '#dropdown-familia':
						source = 'modulos/agrupacion/GetFamilias.php';
					break;
					case '#dropdown-insumo-generico':
						source = 'modulos/agrupacion/GetInsumosGenericos.php';
					break;
				}
				
				DROP_LIST.onSelect = AG.asignaAgrupador;

				DROP_LIST.listContainer = listContainer;
				DROP_LIST.source = source;
				DROP_LIST.show(event);
				
				event.preventDefault();
			}
		});
		
		AG.resetToolbar();
		
		// Handler para los botones del toolbar
		$('#radios-visibilidad, #radios-expansion').buttonset()
		 
		$('#radios-expansion input').click( function(event) {
		 	
		 	switch( this.id ) {
		 		
		 		case 'rd-expand-all':
		 			$(AG.dataContainer).find('.section:visible .section-content').show().prev().children().addClass('expanded');
		 		break;
		 		
		 		case 'rd-collapse-all':
		 			$(AG.dataContainer).find('.section:visible .section-content').hide().prev().children().removeClass('expanded');
		 		break;
		 	}
	 	 });
	 	 
	 	 $('#radios-visibilidad input').click( function(event) {
	 	 	
		 	var hiddenClassName = 'hidden';

		    $(AG.dataContainer + ' tr').removeClass(hiddenClassName).show();
		 	
		 	switch( this.id ) {
		 		
		 		case 'rd-show-sin-naturaleza':
		 			$(AG.dataContainer).find('tr td:nth-child(3):not(:empty)').parent().addClass('hidden').hide();
		 		break;
		 		case 'rd-show-sin-familia':
		 			$(AG.dataContainer).find('tr td:nth-child(5):not(:empty)').parent().addClass('hidden').hide();

		 		break;
		 		case 'rd-show-sin-insumo-generico':
		 			$(AG.dataContainer).find('tr td:nth-child(7):not(:empty)').parent().addClass('hidden').hide();
		 		break;
		 	}

		 	AG.cuentaFilas();
		 	
		 	$(AG.dataContainer + ' .section').removeClass(hiddenClassName).show().each( function() {
	    		var totalDocs = parseInt($(this).find('.item-count').text());
	    		
	    		if( totalDocs === 0 ) {
	    			$(this).addClass(hiddenClassName).hide();
	    		}
	    	});
	 	 });
	 	 
		AG.disableToolbar();
	},
	
	cargaFilas: function(tipo) {
		
		var AG = this;

		DATA_LOADER.show();

		AG.disableToolbar();
		
		var dataURL = null;
		
		switch( tipo ) {
			
			case 'cmdInsumos':
				dataURL = 'modulos/agrupacion/GetListaInsumos.php';
			break;
			case 'cmdSubcontratos':
				dataURL = 'modulos/agrupacion/GetListaSubcontratos.php';
			break;
			case 'cmdCuentas':
				dataURL = 'modulos/agrupacion/GetListaCuentasContables.php';
			break;
			case 'cmdFacturasVarios':
				dataURL = 'modulos/agrupacion/GetListaFacturasVarios.php';
			break;
		}
		
		AG.currentRequest = 
		$.ajax({
			type: 'GET',
			url: dataURL,
			data: {
				idProyecto: LISTA_PROYECTOS.selectedItem.value
			},
			dataType: 'json',
			cache: false,
			timeout: 120000
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
				if( json.noRows ) {
					messageConsole.displayMessage(json.noRowsMessage, 'info');
					return false;
				}
				
				var content = '';
				
				if( tipo === 'cmdInsumos' ) {
					
					$.each( json.Insumos.Familias, function() {
						
						content += '<div class="section">'
								+    '<div class="section-header">'
								+      '<span class="content-toggler">'
								+        '<a class="title">' + this.Familia + '<span class="items-counter" title="Numero de insumos afectados por el filtro">(<span class="item-count">' + this.NumInsumos + '</span>)</span></a>'
								+      '</span>'
								+    '</div>'
								+    '<div class="section-content">'
								+      '<table class="insumos">'
								+        '<colgroup>'
								+          '<col/>'
								+          '<col class="unidad"/>'
								+          '<col/>'
								+          '<col class="icon"/>'
								+          '<col/>'
								+          '<col class="icon"/>'
								+          '<col/>'
								+          '<col class="icon"/>'
								+        '</colgroup>'
								+        '<thead>'
								+          '<tr>'
								+            '<th>Insumo</th>'
								+            '<th>Unidad</th>'
								+            '<th colspan="2">Naturaleza</th>'
								+            '<th colspan="2">Familia</th>'
								+            '<th colspan="2">Insumo Genérico</th>'
								+          '</tr>'
								+        '</thead>'
								+        '<tbody>';
	
						$.each( this.Insumos, function() {
	
							content += '<tr class="insumo" data-id="' + this.idInsumo + '">'
									+    '<td>' + this.Insumo + '</td>'
									+    '<td class="centrado">' + this.Unidad + '</td>'
									+    '<td>' + this.AgrupadorNaturaleza + '</td>'
									+    '<td class="icon-cell">'
									+      '<a href="#dropdown-naturaleza" class="dropdown-list-trigger"></a>'
									+    '</td>'
									+    '<td>' + this.AgrupadorFamilia + '</td>'
									+    '<td class="icon-cell">'
									+      '<a href="#dropdown-familia" class="dropdown-list-trigger"></a>'
									+    '</td>'
									+    '<td>' + this.AgrupadorInsumoGenerico + '</td>'
									+    '<td class="icon-cell">'
									+      '<a href="#dropdown-insumo-generico" class="dropdown-list-trigger"></a>'
									+    '</td>'
									+  '</tr>';
						});
						
						content +=      '</tbody>'
								+     '</table>'
								+   '</div>'
							    + '</div>';
					});

				} else if( tipo === 'cmdSubcontratos' ) {
					
					var idContratista = null;
					var idSubcontrato = null;
					
					$.each( json.Subcontratos.Contratistas, function() {
						
						idContratista = this.idContratista;
						
						content += '<div class="section">'
								+    '<div class="section-header">'
								+      '<span class="content-toggler">'
								+        '<a class="title">' + this.Contratista + '<span class="items-counter" title="Numero de subcontratos afectados por el filtro">(<span class="item-count">0</span>)</span></a>'
								+      '</span>'
								+    '</div>'
								+    '<div class="section-content">';
								
								
						$.each( this.Subcontratos, function() {
							
							idSubcontrato = this.idSubcontrato;
							
							content += '<div class="section">'
									+    '<div class="section-header">'
									+      '<span class="content-toggler">'
									+        '<a class="title">' + this.Subcontrato + '<span class="items-counter" title="Numero de subcontratos afectados por el filtro">(<span class="item-count">0</span>)</span></a>'
									+      '</span>'
									+    '</div>'
									+    '<div class="section-content">'
									+      '<table class="subcontratos">'
									+        '<colgroup>'
									+          '<col/>'
									+          '<col class="unidad"/>'
									+          '<col/>'
									+          '<col class="icon"/>'
									+          '<col/>'
									+          '<col class="icon"/>'
									+          '<col/>'
									+          '<col class="icon"/>'
									+        '</colgroup>'
									+        '<thead>'
									+          '<tr>'
									+            '<th>Actividad</th>'
									+            '<th>Unidad</th>'
									+            '<th colspan="2">Naturaleza</th>'
									+            '<th colspan="2">Familia</th>'
									+            '<th colspan="2">Insumo Genérico</th>'
									+          '</tr>'
									+        '</thead>'
									+        '<tbody>';
		
							$.each( this.Actividades, function() {
		
								content += '<tr class="actividad" data-id="' + this.idActividad + '" data-idcontratista="' + idContratista + '" data-idsubcontrato="' + idSubcontrato + '">'
										+    '<td>' + this.Actividad + '</td>'
										+    '<td class="centrado">' + this.Unidad + '</td>'
										+    '<td>' + this.AgrupadorNaturaleza + '</td>'
										+    '<td class="icon-cell">'
										+      '<a href="#dropdown-naturaleza" class="dropdown-list-trigger"></a>'
										+    '</td>'
										+    '<td>' + this.AgrupadorFamilia + '</td>'
										+    '<td class="icon-cell">'
										+      '<a href="#dropdown-familia" class="dropdown-list-trigger"></a>'
										+    '</td>'
										+    '<td>' + this.AgrupadorInsumoGenerico + '</td>'
										+    '<td class="icon-cell">'
										+      '<a href="#dropdown-insumo-generico" class="dropdown-list-trigger"></a>'
										+    '</td>'
										+  '</tr>';
							});
							
							content +=      '</tbody>'
									+     '</table>'
									+   '</div>'
								    + '</div>';
						});
						
						content += '  </div>'
								+  '</div>';
					});

				} else if( tipo === 'cmdCuentas' ) {
					
					content += '<table class="insumos">'
							+    '<colgroup>'
							+      '<col class="cuenta"/>'
							+      '<col/>'
							+      '<col class="agrupador"/>'
							+      '<col class="icon"/>'
							+     '</colgroup>'
							+     '<thead>'
							+       '<tr>'
							+         '<th>Codigo</th>'
							+         '<th>Nombre</th>'
							+         '<th colspan="2">Naturaleza</th>'
							+       '</tr>'
							+     '</thead>'
							+     '<tbody>';
					
					$.each( json.Cuentas, function() {
	
						content += '<tr class="cuenta" data-id="' + this.idCuenta + '">'
								+    '<td class="centrado">' + this.Codigo + '</td>'
								+    '<td>' + this.Nombre+ '</td>'
								+    '<td>' + this.AgrupadorNaturaleza + '</td>'
								+    '<td class="icon-cell">'
								+      '<a href="#dropdown-naturaleza" class="dropdown-list-trigger"></a>'
								+    '</td>'
								+  '</tr>';
					});
						
					content +=      '</tbody>'
							+     '</table>';

				} else if( tipo === 'cmdFacturasVarios' ) {
					
					var IDFactura = null;
					
					$.each( json.Proveedores, function() {
						
						content += '<div class="section">'
								+    '<div class="section-header">'
								+      '<span class="content-toggler">'
								+        '<a class="title">' + this.Proveedor + '<span class="items-counter" title="Numero de facturas">(<span class="item-count">0</span>)</span></a>'
								+      '</span>'
								+    '</div>'
								+    '<div class="section-content">';
								
								
						$.each( this.FacturasVarios, function() {
							
							IDFactura = this.IDTransaccionCDC;
							
							content += '<div class="section">'
									+    '<div class="section-header">'
									+      '<span class="content-toggler">'
									+        '<a class="title">' + this.ReferenciaFactura + '<span class="items-counter" title="Numero de subcontratos afectados por el filtro">(<span class="item-count">0</span>)</span></a>'
									+      '</span>'
									+    '</div>'
									+    '<div class="section-content">'
									+      '<table class="subcontratos">'
									+        '<colgroup>'
									+          '<col/>'
									+          '<col/>'
									+          '<col class="icon"/>'
									+          '<col/>'
									+          '<col class="icon"/>'
									+          '<col/>'
									+          '<col class="icon"/>'
									+        '</colgroup>'
									+        '<thead>'
									+          '<tr>'
									+            '<th>Referencia</th>'
									+            '<th colspan="2">Naturaleza</th>'
									+            '<th colspan="2">Familia</th>'
									+            '<th colspan="2">Insumo Genérico</th>'
									+          '</tr>'
									+        '</thead>'
									+        '<tbody>';
		
							$.each( this.ItemsFactura, function() {
		
								content += '<tr class="item-facturavarios" data-idtransaccion="' + IDFactura + '" data-id="' + this.IDItem + '" >'
										+    '<td>' + this.Referencia + '</td>'
										+    '<td>' + this.AgrupadorNaturaleza + '</td>'
										+    '<td class="icon-cell">'
										+      '<a href="#dropdown-naturaleza" class="dropdown-list-trigger"></a>'
										+    '</td>'
										+    '<td>' + this.AgrupadorFamilia + '</td>'
										+    '<td class="icon-cell">'
										+      '<a href="#dropdown-familia" class="dropdown-list-trigger"></a>'
										+    '</td>'
										+    '<td>' + this.AgrupadorInsumoGenerico + '</td>'
										+    '<td class="icon-cell">'
										+      '<a href="#dropdown-insumo-generico" class="dropdown-list-trigger"></a>'
										+    '</td>'
										+  '</tr>';
							});
							
							content +=      '</tbody>'
									+     '</table>'
									+   '</div>'
								    + '</div>';
						});
						
						content += '  </div>'
								+  '</div>';
					});
				}
				
				$(AG.dataContainer).html(content);
				
				AG.cuentaFilas();
				AG.enableToolbar();

			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).complete( function() {
			DATA_LOADER.hide();
		});
	},
	
	cuentaFilas: function() {
		var AG = this;
		
		// Agrega el numero de documentos visibles por seccion
		$(AG.dataContainer + ' .section').each( function() {
			
			// Por cada titulo dentro de la seccion
		    $(this).children('.section-header').each(function() {
		    	
		    	// Se obtiene una referencia al elemento que contiene el numero de documentos
		        var $numRowsContainer = $(this).find('.item-count');
		        
				// Se cuentan los items que existen en cada contenido de la seccion que
				// esten visibles. Esto puede incluir subsecciones
		        var rowsInSection = $(this).next().find('tbody tr:not(.hidden)').length;
		        
		        // Se actualiza el numero de documentos que se contaron dentro de la seccion
		        $numRowsContainer.text(rowsInSection);
		    });
		});
	},
	
	asignaAgrupador: function(selectedItem, trigger) {
		
		var AG = this;
		
		DATA_LOADER.show();
		
		$parentRow = trigger.parents('tr');
		
		var id = parseInt(trigger.parents('tr').attr('data-id'))
			, IDTransaccionCDC;
		var source = null;
		
		if( $parentRow.hasClass('insumo') ) {
			
			source = 'modulos/agrupacion/AgrupaInsumo.php';
		} else if( $parentRow.hasClass('actividad') ) {
			
			source = 'modulos/agrupacion/AgrupaActividadSubcontrato.php';
			var idContratista = parseInt(trigger.parents('tr').attr('data-idcontratista'));
			var idSubcontrato = parseInt(trigger.parents('tr').attr('data-idsubcontrato'));
		} else if( $parentRow.hasClass('cuenta') ) {
			
			source = 'modulos/agrupacion/AgrupaCuentaContable.php';
		} else if( $parentRow.hasClass('item-facturavarios') ) {

			IDTransaccionCDC = parseInt($parentRow.attr('data-idtransaccion'));
			source = 'modulos/agrupacion/AgrupaItemFacturaVarios.php';
		}
		
		$.ajax({
			type: 'POST',
			url: source,
			data: {
				  idProyecto: LISTA_PROYECTOS.selectedItem.value
				, idContratista: idContratista
				, idSubcontrato: idSubcontrato
				, idTransaccion: IDTransaccionCDC
				, id: id
				, idAgrupador: selectedItem.value
			},
			dataType: 'json',
			cache: false,
			timeout: 60000
		}).success( function(json) {
			try {
				
				if( ! json.success ) {
					
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
				trigger.parent().prev().text(selectedItem.label);
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
			}
		}).complete( function() {
			DATA_LOADER.hide();
		});
	},
	
	enableToolbar: function() {
		$('#radios-visibilidad, #radios-expansion').buttonset('enable');
	},
	
	disableToolbar: function() {
		$('#radios-visibilidad, #radios-expansion').buttonset('disable');
	},
	
	resetToolbar: function() {
		
		$toolbarButtons = $('#radios-visibilidad, #radios-expansion');
		
		$toolbarButtons.children('input').prop('checked', false);
		
		$('#rd-collapse-all, #rd-show-all').prop('checked', true);
		 
		$toolbarButtons.buttonset('refresh');
	},
	
	clearDataContainer: function() {
		
		$(this.dataContainer).empty();
	}

}