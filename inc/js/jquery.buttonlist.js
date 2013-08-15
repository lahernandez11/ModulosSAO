/*
 * jQuery ButtonList
 * 11.06.2012
 * Author: Uziel Bueno (uziel.bueno@hermesconstruccion.com.mx)
*/

(function( $ ) {

	// nombres de clases css
	var classes = {
		list: 'dropdown-list',
		button: 'button-list',
		text: 'button-text',
		listLoader: 'loader'
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
			var opts = $.extend( {}, $.fn.buttonlist.defaults, options );
			
			return this.each( function() {

				var id = new Date().getMilliseconds() + parseInt(Math.random().toString().split('.')[1]);

				// Identificador unico para los namespaces
				var eventNamespace = 'buttonlist' + id;//+ (new Date().getTime());

				var $this = $(this);

				var data = restoreData.call($this);

				if( ! data ) {

					opts.listContainerID = 'btnList-' + id;
					opts.buttonLabel = $('.' + classes.text, this).text();

					$this.data('buttonlist', opts);
				}

				$('<ul id="' + opts.listContainerID + '"></ul>')
				.addClass(classes.list).appendTo('body')
				.on('click.buttonlist', 'a', function(event) {

					var $listItem = $(this);
					
					selectListItem.call( $this, $listItem, true );

					event.stopPropagation();
					event.preventDefault();
				});

				// Clic Handler para el documento. Este se encargara de cerrar la lista cuando
				// se de clic en cualquier lugar del documento, incluso en los items de la lista
				$(document.documentElement).on('click.' + eventNamespace, function(event){

					// Quita el Handler del evento clic en el documento al ocultar la lista
					//$(document.documentElement).off('click.' + eventNamespace);
					// Oculta la lista
					hide.call($this);
				});

				// Click Handler para el boton que mostrara la lista
				$this.on('click.' + eventNamespace, function(event) {
					
					var list = getListContainer.call($this);

					if( list.is(':visible') )
						hide.call($this);
					else {

						// Antes de mostrar la lista oculta cualquier otra que este visible
						$('.' + classes.list).hide();

						// Muestra la lista
						if ( ! restoreData.call($this).isLoaded ) {

							loadData.call( $this );

							$.when( restoreData.call($this).request )
							 .then(	function() {
							 	methods.show.call($this);
							 });
						} else
							methods.show.call($this);
					}

					// Evita que el evento se propague en forma de burbuja hasta el documento
					// solo ocurre y termina en el boton que abre la lista
					event.preventDefault();
					event.stopPropagation();
				});
			});
		},

		/*
		 * Este metodo se encarga de mostrar la lista desplegable
		 * si ya esta presente o de lo contrario la crea y carga
		 * sus datos desde la fuente indicada
		*/
		show: function() {
			
			var $this = this;

			// Recuperar los datos del elemento
			var data = restoreData.call(this),
				list = getListContainer.call(this);

			var windowWidth = parseInt($(document).width());
			var listWidth = parseInt(list.outerWidth());
			var buttonHeight = this.outerHeight();
			var buttonYPos = this.offset().top;
			var buttonXPos = this.offset().left;

			// Si el handler del evento beforeShow devuelve false
			// se detiene la ejecucion
			if( ! data.beforeShow.call($this) )
				return;

			// Asigna la posicion de la lista con referencia al boton que la abre
			list
			.css({
				'top': (buttonYPos + buttonHeight) + 'px',
				'left': (buttonXPos) + 'px',
			}).slideDown('fast', function() {
				
				// if ( data.selectedItem ) {
					
				// 	var selecctedItemPos = list.find('a.selected').position().top;
				// 	//var listScrollHeight = $(data.listContainerID)[0].scrollHeight;
				// 	list.scrollTop( selecctedItemPos );
				// }
			});
		},

		refresh: function() {

			methods.clear.call(this);
			loadData.call(this);
		},

		reset: function() {

			// Recuperar los datos del elemento
			var data = restoreData.call(this);

			// Reinicia la lista al estado por default
			data.selectedItem = null;

			// Devuelve el texto original del boton
			$('.' + classes.text, this).text(data.buttonLabel);

			// Reasigna los datos al elemento
			saveData.call(this, data);
		},

		/*
		 * Metodo que limpia la lista de opciones
		 * quita las opciones de la lista y el item
		 * seleccionado
		*/
		clear: function() {

			var $list = getListContainer.call(this);

			methods.reset.call(this);

			$list.html('');
		},

		destroy: function() {
			
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
				if ( opt === 'selectedItem' && restoreData.call(this)[opt] == null )
					return {value: null}
				else
					return restoreData.call(this)[opt];
			}
		},

		/**
		 * Metodo para agregar un item a la lista
		 * itemData: un objeto con 2 propiedades: id y text
		 *           que representa el item a agregar
		 * position: la posicion de insercion del item
		             puede ser 'start'(inicio de la lista) o 
		             'end'(final de la lista)[Default]
		 * $('#lista').buttonlist('addListItem', {id: 12345, text: 'prueba'}, 'start')
		*/
		addListItem: function( itemData, position ) {
			
			var data = restoreData.call(this);

			position = position || data.insertItemPosition;

			var $list = getListContainer.call(this);

			if ( position == data.insertItemPosition )
				$list.append('<li><a href="#' + itemData.id + '">' + itemData.text + '</a></li>');
			else
				$list.prepend('<li><a href="#' + itemData.id + '">' + itemData.text + '</a></li>');
		},

		/**
		 * Metodo que selecciona un item de la lista
		 * como si se le ubiece dado clic, por medio
		 * de su identificador guardado en su atributo href
		 * $('#lista').buttonlist('selectItemById', 806040)
		*/
		selectItemById: function( itemId ) {

			var listItem = null;

			listItem = getListItemById.call( this, itemId );
			
			if ( listItem.length > 0 ) {
				selectListItem.call(this, listItem, true);
			}
		},

		/**
		 * Metodo que establece el item indicado de la lista
		 * por medio de su identificador guardado en su atributo href
		 * no llama el callback establecido en la seleccion del item
		 * $('#lista').buttonlist('selectItemById', 806040)
		*/
		setSelectedItemById: function( itemId ) {

			var listItem = null;

			listItem = getListItemById.call( this, itemId );
			
			if ( listItem.length > 0 ) {
				selectListItem.call(this, listItem, false);
			}
		}
	}

	/**
	 * Metodo para obtener una referencia a un item
	 * por medio del identificador guardado en su
	 * atributo href
	*/
	function getListItemById( itemId ) {

		var listItem = getListContainer.call(this).find('a[href=#' + itemId + ']');

		return listItem;
	}

	/**
	 * Quita la seleccion de el item o items
	 * seleccionados en la lista
	*/
	function clearSelectedListItem() {

		getListContainer.call(this).find('a').removeClass('selected');
	}

	/**
	 * Selecciona un item de la lista llamando
	 * a su callback en caso de haber uno asignado
	*/
	function selectListItem( listItem, callCallback ) {

		var data = restoreData.call(this);

		clearSelectedListItem.call(this);

		// Se crea un objeto literal para almacenar los datos del item
		var item = {
			label: listItem.text(),
			value: listItem.attr('href').split('#')[1]
		}

		data.selectedItem = item;

		$('.' + classes.text, this).text(item.label);

		listItem.addClass('selected');

		saveData.call( this, data );
		
		if ( callCallback )
			data.onSelect.call(this, data.selectedItem, listItem.parent());

		hide.call(this);
	}

	function hide() {
		getListContainer.call(this).hide('fast');
	}

	/*
     * Metodo encargado de realizar una peticion asincrona
     * al source de datos para poder consumirlos y agregarlos
     * como items de la lista
	*/
	function loadData() {
		
		// Guarda una referencia del contexto actual que es el boton
		var $this = this;

		var opts = restoreData.call(this);

		if( ! opts.beforeLoad.call(this) )
			return;

		// Carga los items solo si se especifico una fuente de datos
		if ( opts.source ) {

			$this.addClass(classes.listLoader);

			opts.request = $.ajax({
				url: opts.source,
				data: opts.data,
				dataType: 'json'
			})
			.done( function( json ) {

				try {

					if ( ! json.success ) {
						$.error( json.message );
						return false;
					}

					buildList.call($this, json.options);
					opts.isLoaded = true;
				} catch( e ) {

					opts.isLoaded = false;
					$this.removeClass(classes.listLoader);
					$.error( e.message );
				}
			})
			.fail( function() {
				opts.isLoaded = false;
			})
			.always( function() {
				$this.removeClass(classes.listLoader);
			});
		} else {
			$.error( 'No se especifico ninguna fuente de datos para esta lista' );
		}
	}

	function getListContainer() {
		return $('#' + restoreData.call(this).listContainerID);
	}

	function buildList( data ) {
	
		var $this = this,
			options = restoreData.call(this),
			$list = getListContainer.call(this),
			items = '';
		
		$list.empty();

		$.each(data, function() {
			items += itemTemplate( options.onCreateListItem.call(this) );
		});

		$list.html(items);
	}

	function itemTemplate( itemData ) {
		var html = "";

		html = '<li><a href="#' + itemData.id + '">' + itemData.value + '</a></li>';

		return html;
	}

	/*
     * Devuelve las opciones y estado actual de la lista
     * almacenados con el metodo data de jQuery sobre el
     * elemento
	*/
	function restoreData() {
		return this.data('buttonlist');
	}
	
	/*
     * Guarda las opciones y estado actual de la lista
     * en caso de haber realizado una modificacion a estos
	*/
	function saveData( data ) {
		this.data('buttonlist', data);
	}

	$.fn.buttonlist = function( method ) {

		if ( methods[method] ) {

			return methods[method].apply(this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {

			return methods.init.apply( this, arguments )
		} else {

			$.error( 'Method' + method + ' does not exists in the jQuery.buttonlist' );
		}
	}

	$.fn.buttonlist.defaults = {
		isLoaded: false,
		source: null,
		data: {},
		selectedItem: null,
		request: null,
		insertItemPosition: 'end',
		beforeLoad: function() { return true },
		beforeShow: function() { return true; },
		onSelect: function( selectedItem, listItem ) { return true; },
		onCreateListItem: function(){}
	}

})( jQuery );