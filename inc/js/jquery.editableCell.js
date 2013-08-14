/*
 * jQuery editableCell
 * 05.06.2013
 * Author: Uziel Bueno (UBueno@grupohi.mx)
*/

(function( $ ) {

	// nombres de clases css
	var classes = {
		onEdit: '',
		editable: ''
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
			var options = $.extend( {}, $.fn.editableCell.defaults, options );
			
			return this.each( function() {

				var id = new Date().getTime();

				// Identificador unico para los namespaces
				var eventNamespace = 'editableCell' + id;
				
				var $that = $(this);

				// var data = restoreData.call($that);

				// if( ! data ) {
					saveData.call($that, options);
				// }

				var $editField = $('<input type="text" />');

				$editField.val($that.text());

				$that.on('click', function(event) {
					
					if ( restoreData.call($that).isDisabled )
						return false;

					if ( restoreData.call($that).beforeEdit.call($that) ) {
						
						editCell.call($that);
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

	function setOldValue( oldValue ) {
		saveData(restoreData.call($that).oldValue = oldValue);
	}

	function restoreOldValue() {
		this.text(restoreData().oldValue);
	}

	function getOldValue() {
		return true;
	}

	function setValue( value ) {
		this.text(value);
	}

	function getValue() {
		return this.text();
	}

	function editCell() {

		var that = this,
			data = this.restoreData();

		// activeCell.addClass(classes.editingCell);

		setOldValue(activeCell.text());

	    // Crea un textField con el texto de la celda para ser editado
	    var $inputField = $('<input type="text" class="text" />').val( data.cellValue );
	    // Borra el contenido actual de la celda
	    this.empty();
	    // Agrega el textField a la celda
	    $inputField.appendTo(this).focus( function() {

	    	$(this).select();
	    }).focus();

	    $inputField.on('blur', function() {

	    	if ( restoreData.call($that).onUpdate.call($that) )
	    	that.setValue($(this).val());
	    	this.remove();
	    });
	},

	function restoreData() {
		return this.data('editableCell');
	}
	
	function saveData( data ) {
		this.data('editableCell', data);
	}

	$.fn.editableCell = function( method ) {

		if ( methods[method] ) {
			return methods[method].apply(this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments )
		} else {
			$.error( 'Method' + method + ' does not exists in the jQuery.editableCell' );
		}
	}

	$.fn.editableCell.defaults = {
		oldValue: null,
		isDisabled: false,
		onUpdate: function() { return true },
		beforeEdit: function() { return true }
	}
})( jQuery );