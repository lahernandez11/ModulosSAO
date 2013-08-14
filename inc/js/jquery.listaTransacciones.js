/*
 * jQuery listaTransacciones
 * 05.06.2013
 * Author: Uziel Bueno (UBueno@grupohi.mx)
*/

(function( $ ) {

	// nombres de clases css
	var classes = {
		itemsTable: 'lista-transacciones'
	},

	methods = {

		/*
		 * Metodo de inicializacion para crear eventos
		 * y datos para cada elemento
		*/
		init: function( options ) {
			
			if( ! options )
				return false;

			// Crea las opciones de inicializacion
			var options = $.extend( {}, $.fn.listaTransacciones.defaults, options );
			
			return this.each( function() {

				var id = new Date().getTime();

				// Identificador unico para los namespaces
				var eventNamespace = 'listaTransacciones' + id;
				
				var $that = $(this);

				var data = restoreData.call($that);

				if( ! data ) {

					options.listContainer = 'lstTran-' + id;

					saveData.call($that, options);
				}

				$that.attr('title', 'Lista de Transacciones');

				var $dialogcontainer = $('<div id="' + restoreData.call($that).listContainer + '"></div>');
				
				var dataContainer =
					 '<table class="' + classes.itemsTable + '">'
					+   '<colgroup>'
					+     '<col span="2" class="fecha"/>'
					+   '</colgroup>'
					+   '<thead>'
					+     '<tr>'
					+       '<th>Folio</th>'
					+       '<th>Fecha</th>'
					+       '<th>Observaciones</th>'
					+     '</tr>'
					+    '</thead>'
					+    '<tbody><tr data-id="1"><td>#0001</td><td>05-06-2013</td><td>PRUEBA 4</td></tr><tr data-id="2"><td>#0002</td><td>05-06-2013</td><td>PRUEBA 4</td></tr><tr data-id="3">	<td>#0003</td>	<td>05-06-2013</td>	<td>PRUEBA 4</td></tr><tr data-id="3">	<td>#0004</td>	<td>05-06-2013</td>	<td>PRUEBA 4</td></tr>		</tbody>'
					+ '</table>';

				// Crear un contenedor para utilizarlo como un jQuery Dialog
				// y mostrar la lista de transaccione, asignandole un id especifico
				$dialogcontainer.html(dataContainer);
				
				$dialogcontainer.dialog({
					autoOpen: false,
					modal: true,
					width: 550,
					height: 250,
					show: 'fold',
					title: 'Lista de Transacciones'
				});

				$that.on('click', function(event) {
					
					if ( restoreData.call($that).beforeLoad.call($that) ) {
						loadData.call($that);
						abreListaTransacciones.call($that);
					}
				});

				$dialogcontainer.find('tbody').on( 'click dblclick', 'tr', function(event) {
					
					var $this = $(this);

					$this.siblings().removeClass('selected');
					$this.addClass('selected');

					if ( event.type == 'dblclick' ) {
						selectListItem.call($that, $this );
					}
				});
			});
		},

		/*
		 * Metodo que devuelve o asigna datos del elemento
		 * como el item seleccionado de la lista
		 * para pasarlo a otras funciones
		*/
		option: function( opt, value ) {

			value = value || null;

			if ( value ) {
				restoreData.call(this)[opt] = value;
			}
			else {
				return restoreData.call(this)[opt];
			}
		}
	}

	function clearList() {
		$('#' + restoreData.call(this).listContainer + ' tbody').html('');
	}

	function abreListaTransacciones() {

		var that = this;

		$.when( restoreData.call(that).request )
		 .then( function() {
			$('#' + restoreData.call(that).listContainer).dialog('open');
		});
	}

	function cierraListaTransacciones() {
		$('#' + restoreData.call(this).listContainer).dialog('close');
	}

	function getItemsContainer() {
		return $('#' + restoreData.call(this).listContainer + ' tbody');
	}

	function selectListItem( listItem ) {

		var item = {
			value: listItem.attr('data-id')
		}

		restoreData.call(this).onSelectItem.call(this, item );

		cierraListaTransacciones.call(this);
	}

	function loadData() {
		
		var $this = this;

		var options = restoreData.call(this);

		if( options.request )
			options.request.abort();
		
		// Carga los items solo si se especifico una fuente de datos
		if ( ! options.source )
			return;

		options.request = $.ajax({
			type: 'GET',
			url: options.source,
			data: options.data,
			dataType: 'json'
		})
		.done( function( json ) {

			try {

				if ( ! json.success ) {
					$.error( json.message );
					return false;
				}

				buildList.call($this, json.options);

			} catch( e ) {
				$.error( e.message );
			}
		})
		.always( function() {
			options.onFinishLoadData.call($this);
		});
	}

	function buildList( data ) {

		var $this = this
			options = restoreData.call(this);

		var $list = getItemsContainer.call(this);
		
		$list.html('');

		var items = '';

		$.each(data, function() {

			itemData = options.onCreateListItem.call(this);

			// for(key in itemData) {
			// 	console.log(itemData[key]);
			// }

			items +=
				  '<tr data-id="' + itemData.id + '">'
				+   '<td>' + itemData.folio + '</td>'
				+   '<td>' + itemData.fecha + '</td>'
				+   '<td>' + itemData.observaciones + '</td>'
				+ '</tr>';
		});

		$list.html(items);
	}

	function restoreData() {
		return this.data('listaTransacciones');
	}
	
	function saveData( data ) {
		this.data('listaTransacciones', data);
	}

	$.fn.listaTransacciones = function( method ) {

		if ( methods[method] ) {
			return methods[method].apply(this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments )
		} else {
			$.error( 'Method' + method + ' does not exists in the jQuery.listaTransacciones' );
		}
	}

	$.fn.listaTransacciones.defaults = {
		source: null,
		data: {},
		selectedItem: {value: null},
		request: null,
		beforeLoad: function() { return true },
		onSelectItem: function( item ) { return true; },
		onFinishLoadData: function() {},
		onCreateListItem: function() {}
	}
})( jQuery );