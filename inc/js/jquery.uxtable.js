(function( $ ) {
	
	var classes = {
		table: 'ux-table',
		activeTable: 'active',
		activeCell: 'active-cell',
		editingCell: 'in-edit'
	},

	keys = {
		up: 38,
		next: 39,
		down: 40,
		prev: 37,
		enter: 13,
		backspace: 8,
		supr: 46,
		escape: 27
	},

	editionKeys = [
		8, 13, 32,
		46, 48, 49,
		50, 51, 52, 53, 54, 55, 56, 57, 59,
		60, 65, 66, 67, 68, 69,
		70, 71, 72, 73, 74, 75, 76, 77, 78, 79,
		80, 81, 82, 83, 84, 85, 86, 87, 88, 89,
		90, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105,
		107, 109, 110,
		173, 174, 175, 188, 190,
		220, 221
	],

	methods = {
		
		init: function( options ) {

			if( ! options )
				return false;

			var options = $.extend( {}, $.fn.uxtable.defaults, options );

			return this.each( function() {
				
				var $this = $(this),
				eventNamespace = 'uxtable' + (new Date().getTime());

				var data = restoreData.call($this);

				if ( ! data.length ) {

					$this.data('uxtable', {

						  editableColumns: options.editableColumns,
						  didKeydownBinded: options.didKeydownBinded,
						  activeCell: null,
						  options: options
					});
				}

				$this.addClass(classes.table);
				
				$this.on('click.uxtable', function(event) {

					var $tgt = $(event.target);
					
					$this.addClass(classes.activeTable);

					// Si el clic no es en una celda de datos la celda activa sera
					// la primer celda de datos de la primera fila de la tabla

					selectCell.call( $this, event );

					$(document.documentElement).on('click.' + eventNamespace, function() {

						$(document.documentElement).off('click.' + eventNamespace);
						$(document.documentElement).off('keydown.' + eventNamespace);
						disable.call($this, options);
					});

					if( ! $this.data('uxtable').didKeydownBinded ) {

						$this.data('uxtable').didKeydownBinded = true;
						
						$(document.documentElement).on('keydown.' + eventNamespace, function(event) {
							
							var activeCell = $this.data('uxtable').activeCell,
								isCellBeignEdited = false;
					        
					        isCellBeignEdited = activeCell.hasClass(classes.editingCell);

					        if ( activeCell ) {

						        // Bloque que permite editar una celda cuando la tecla Enter es presionada
						        if ( event.keyCode === keys.up || event.keyCode === keys.down
						        	|| event.keyCode === keys.prev || event.keyCode === keys.next
						        ) {

						        	selectCell.call( $this, event );
						        } else {

						        		if ( event.keyCode === keys.enter ) {

						        			if ( isCellBeignEdited )
						        				finishEditActiveCell.call( $this, options );
						        			else
						        				editActiveCell.call( $this );
						        		} else if ( event.keyCode === keys.escape )
						        			finishEditActiveCell.call( $this, options );
						        		else if( ! isCellBeignEdited ) {

						        			if ( editionKeys.indexOf(event.keyCode) === -1 )
												return;

						        			editActiveCell.call( $this );
						        		}
						        }
						    }
						});
					}

					event.stopPropagation();
				}).on( 'dblclick.uxtable', function(event) {
					event.stopPropagation();
					editActiveCell.call( $this );
				});
			});
		},

		getCell: function( cellIndex ) {

			return restoreData.call(this).activeCell.parent().children(':eq(' + cellIndex + ')')
		},

		revertEdition: function() {

			var activeCell = this.data('uxtable').activeCell,
			oldValue;

			if( ! activeCell )
				return;

			oldValue = activeCell.data('oldValue');

			activeCell.text(oldValue);
		}

	},

	disable = function( options ) {
		this.removeClass(classes.activeTable);

		finishEditActiveCell.call(this, options);
		//this.data('uxtable').activeCell = null;
		this.data('uxtable').didKeydownBinded = false;
	},

	selectCell = function( event ) {

		var data = restoreData.call(this);
		var $eventTarget = $(event.target);
		var activeCellIX = null;
        var direction = '';
        var $cellToMove = false;

		if ( data.activeCell ) {
			
			activeCellIX = data.activeCell.index();

			// Si la celda activa esta siendo editada
			if ( data.activeCell.hasClass(classes.editingCell) ) {

				var cell = $eventTarget.is('td') ? $eventTarget : $eventTarget.parent();

				if( event.type === 'keydown' && (event.keyCode === keys.next || event.keyCode === keys.prev) )
					return;
				else if ( event.type === 'click' ) {
					
					if ( ! cell.hasClass(classes.activeCell) )
						finishEditActiveCell.call(this, data.options);
					else
						return;

				} else
					finishEditActiveCell.call(this, data.options);

			} else
				event.preventDefault();
			
	        switch( event.keyCode ) {
	        	
	            case keys.up:
	                $cellToMove = data.activeCell.parent().prev().children().eq(activeCellIX);
	            break;

	            case keys.next:
	                $cellToMove = data.activeCell.next();
	            break;
	            
	            case keys.down:
	                $cellToMove = data.activeCell.parent().next().children().eq(activeCellIX);
	            break;
	            
	            case keys.prev:
	                $cellToMove = data.activeCell.prev();
	            break;
	        }
		}

		
		if ( event.type == 'click' )
			$cellToMove = $eventTarget;
		/*
        if( $eventTarget.parents('tbody').length ) {
			$cellToMove = $eventTarget;
			console.log(event.type);
		}
		*/

		if ( ! $cellToMove.length )
			return;

		if ( ! $cellToMove.is('td') && ! $cellToMove.is('th') && $cellToMove.parents('tbody').length == 0 )
			return;
/**/
		if( data.activeCell ) {
			data.activeCell.removeClass(classes.activeCell);
		}

		data.activeCell = $cellToMove;

		$cellToMove.addClass(classes.activeCell);

		//$cellToMove.css('backgroundColor', 'blue');

		updateData.call(this, data);

		//console.log(JSON.stringify(data));

		var windowHeight = $(window).height() - data.activeCell.height(),
			cellTop = $cellToMove.position().top,
			
			cellTopOffset = cellTop - $(window).scrollTop();

		var scrollTo = 0;

		if ( cellTopOffset > windowHeight ) {
			scrollTo = $(window).scrollTop() + (cellTopOffset - windowHeight) + 5;
			
			$(window).scrollTop(scrollTo);
		} else if( cellTopOffset < 0 ) {
			scrollTo = $(window).scrollTop() + (cellTopOffset) - 5;
			$(window).scrollTop(scrollTo);
		}

	},

	editActiveCell = function() {

		var data = restoreData.call( this );

		var activeCell = data.activeCell;

		if ( ! data.activeCell )
			return;

		if( ! data.editableColumns[activeCell.index()] )
			return;

		activeCell.addClass(classes.editingCell);

		var cellValue = activeCell.text();
		// Retiene el dato anterior para devolverlo si al finalizar la edicion
		// no es valida
		activeCell.data( 'oldValue', cellValue );
	    // Crea un textField con el texto de la celda para ser editado
	    var $inputField = $('<input type="text" class="text" />').val( cellValue );
	    // Borra el contenido actual de la celda
	    activeCell.empty();
	    // Agrega el textField a la celda
	    $inputField.appendTo(activeCell).focus( function(){ 
	    	$(this).select();
	    }).focus();
	},

	finishEditActiveCell = function( options ) {

		var activeCell = this.data('uxtable').activeCell;

		if( ! activeCell )
			return;

		if( ! activeCell.hasClass(classes.editingCell) )
			return;

		var cellValue = null;
		var $inputField = activeCell.children('input');
		
		cellValue = $inputField.val();

	    $inputField.remove();
	    activeCell.removeClass('in-edit');
		

		if ( options.editableColumns[activeCell.index()].onFinishEdit ) {
			options.editableColumns[activeCell.index()].onFinishEdit.call(this, activeCell, cellValue)
			//if( ! options.editableColumns[activeCell.index()].onFinishEdit.call(this, activeCell, cellValue) )
				//cellValue = activeCell.data('oldValue');
		}

		activeCell.text( cellValue );
	},

	restoreData = function() {

		var data = this.data('uxtable');

		return data || {};
	},

	updateData = function( data ) {

		this.data('uxtable', data);
	}

	$.fn.uxtable = function( method ) {

		if( methods[method] ) {

			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1) )
		} else if( typeof method === 'object' || !method ) {

			return methods.init.apply(this, arguments);
		} else {

			$.error( 'Method' + method + ' does not exists in the jQuery.buttonlist' );
		}
	};

	$.fn.uxtable.defaults = {
		editableColumns: {},
		didKeydownBinded: false,
	};

})( jQuery );