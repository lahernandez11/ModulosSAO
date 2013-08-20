function PubSub() {

	var events = {},
		subUid = -1;

	this.subscribe = function( event, callback ) {

		if( ! events[event] ) {
			events[event] = [];
		}

		var token = (++subUid).toString();
		
		events[event].push({
			token: token,
			callback: callback
		});

		return token;
	};

	// Publish or broadcast events of interest
	// with a specific topic name and arguments
	this.publish = function( event, args ) {

		if( ! events[event] ) {
			return false;
		}

		var subscribers = events[event],
			len = subscribers ? subscribers.length : 0;
		
		while( len-- ) {
			subscribers[len].callback( event, args );
		}

		return this;
	};

	return {
		subscribe: this.subscribe,
		publish: this.publish
	};
};

String.prototype.repeat = function( num ) {
	return new Array(isNaN(num)? 1 : ++num).join(this);
}

String.prototype.numFormat = function() {

	var num = this.replace(/,/g, '');
		conSigno = 0,
		posSigno = num.indexOf('-');

	if ( posSigno === 0 )
		conSigno = 1;

	num = num.replace(/-/g, '');

	var decIndex = num.indexOf('.');

	if( decIndex < 0 )
		decIndex = num.length;

	var integers = num.substring(0, decIndex);
	var intArray = new Array();
	var formatted = '';

	if( integers.length <= 3 ) {
		formatted = num.toString();
	}
	else {

		for( i = 0; i < integers.length; i++ ) {

		    intArray.push(integers[i]);
		}

		var decSeparatorCounter = 0;
		var formattedInts = '';

		while( el = intArray.pop() ) {

		    formattedInts = el + formattedInts;
		    
		    decSeparatorCounter++;

		    if( decSeparatorCounter === 3 && intArray.length >= 1 ) {

		        decSeparatorCounter = 0;
		        formattedInts = ',' + formattedInts;
		    }
		}

		formatted = formattedInts + num.substr(decIndex);
	}
	
	return ( conSigno ? '-' : '' ) + formatted;
}

var LISTA_PROYECTOS = {
	
	container: '#lista-proyectos',
	dataURL: 'inc/lib/controllers/ListaProyectosController.php',
	selectedItem: null,
	
	init: function() {
		var LP = this;

		LP.load();
		
		// Click handler para los items de la lista de proyectos
		$(LP.container).click( function(event) {
			LP.selectProyecto(event);
		});
	},
	
	load: function() {
		var LP = this;
		
		this.onLoad();
		
		$(LP.container).empty()
		
		// Carga la lista de proyectos
		$.ajax({
			type: 'GET',
			url: LP.dataURL,
			dataType: 'json',
			data: {action: 'getListaProyectos'}
		}).done( function(json) {
			try {
				
				if( ! json.success ) {
					messageConsole.displayMessage(json.message, 'error');
					return false;
				}
				
				if( json.noRows ) {
					messageConsole.displayMessage(json.message, 'info');
					return false;
				}
				
				var proyectos = '';
				
				// Agrega cada uno de los proyectos a la lista
				$.each(json.options, function() {
					proyectos += '<li><a href="#' + this.idProyecto + '">' + this.NombreProyecto + '</a></li>';
				});
				
				$(LP.container).append(proyectos);
				
				return true;
			} catch(e) {
				messageConsole.displayMessage(e.message, 'error');
				return false;
			}
		});
	},
	
	selectProyecto: function(event) {
		var LP = this;

		var $tgt = $(event.target);

		// Selecciona solo el proyecto que no este seleccionado
		if( $tgt.is('a') && !$tgt.is('.selected') ) {
			
			// Obtiene el valor correspondiente al item del atributo href
			// normalmente este valor es el id del item que viene de la BD
			LP.selectedItem = {
				  value: parseInt($tgt.attr('href').split('#')[1])
				, label: $tgt.text()
				, element: $tgt
			}
			
			// Quita la clase de seleccion de otro item que lo tenga
			$(LP.container).find('a').removeClass('selected');
			
			$tgt.addClass('selected');
			
			// Llama el callbak definido para cuando ocurre este evento
			this.onSelect(event);
			
			event.preventDefault();
		}
	},
	
	selectLast: function() {
		$(this.container + ' li').last().children().click();
	},
	
	onLoad: function(event) {},
	onSelect: function() {}
}

// CONSOLA DE MENSAJES
var messageConsole = {
	container: '#message-console',
	typeClass: { Success: 'success'
			   , Error: 'error'
			   , Info: 'info'
			   , Warning: 'warning'
	},
	errorClass: 'error',
	successClass: 'success',
	infoClass: 'info',
	timerID: null,
	displayed: false,
	hideAfter: 5000,
	
	init: function() {
		var _mc = this;
		
		$('#console-toggler').click( function(event) {
			
			if( $(this).hasClass('close') )
				_mc.hideConsole();
			else
				_mc.showConsole();
		});
	},
	
	displayMessage: function(message, type) {
		
		var _msgClass = null;
		
		switch(type) {
			case 'error':
				_msgClass = this.typeClass.Error;
				break;
			case 'success':
				_msgClass = this.typeClass.Success;
				break;
			case 'info':
				_msgClass = this.typeClass.Info;
				break;
			case 'warning':
				_msgClass = this.typeClass.Warning;
				break;
		}
		/*
		if(type == 'error')
			_msgClass = this.typeClass.Error;
		if(type == 'success')
			_msgClass = this.typeClass.Success;
		if(type == 'info')
			_msgClass = this.typeClass.Info;
		if(type == 'warning')
			_msgClass = this.typeClass.Warning;*/
		
		$(this.container).removeClass().addClass(_msgClass);
			
		$('#console-message').html(message);
		
		//console.log(this.timerID);
		
		if( this.timerID !== null )
			clearTimeout(this.timerID);
		
		this.showConsole();
	},
	
	showConsole: function()	{
		
		var _cl = this;
		
		if( !this.displayed )
			$('#console-toggler').toggleClass('open close');
		
		$(this.container)
		.animate({
				bottom: '0'
		}, {
			duration: 'fast',
			queue: false,
			complete: function() {
				_cl.displayed = true;
				
				_cl.timerID = setTimeout(function() {
					_cl.hideConsole();
				}, _cl.hideAfter)
			}
		});
	},
	
	hideConsole: function() {
		
		var _cl = this;
		
		if( this.timerID !== null )
			clearTimeout(this.timerID);
		
		$(this.container).animate({
			bottom: '-29px'
		},{
			duration: 'normal',
			queue: false,
			complete: function() {
				
				$('#console-toggler').toggleClass('open close');
				
				_cl.displayed = false;

				// Si la consola esta en modo informativo, la limpia
				if( $(_cl.container).is('.info') )
					_cl.clearConsole();
			}
		});
	},
	
	clearConsole: function() {
		
		$('#console-message').html('');
		$(this.container).removeClass();
	}
}


/*
 * LIGHTBOX OBJECT
*/
var LIGHTBOX = {
	OLcontainer: '#lb-overlay',
	LBcontainer: '.lightbox',
	title: 'Lightbox Title Here',
	content: null,
	closeButton: true,
	closeOverlay: true,
	
	show: function() {
		
		var _lb = this;
		
		// DISABLE WINDOW SCROLLBARS
		$('body').css({'overflow': 'hidden'});
		
		// CREA UN OVERLAY
		$('<div id="lb-overlay" class="overlay"></div>')
		.hide()
		.css('opacity', 0.5)
		.insertBefore('#message-console')
		.fadeIn('fast')
		.click(function(event)
		{
			if(_lb.closeOverlay)
				_lb.hide();

			event.stopPropagation();
		});
		
		// CREA EL LIGHTBOX
		$('<div class="lightbox">'
		+ '  <div class="lightbox-title">' + this.title + '</div>'
		+ (_lb.closeButton ? '  <div class="lightbox-close"></div>' : '')
		+ '  <div class="lightbox-content"></div>'
		+ '</div>')
		.insertAfter(this.OLcontainer)
		.hide()
		.find('.lightbox-close')
		.click(function()
		{
			_lb.hide();
		})
		.end()
		.find('.lightbox-content')
		.append(this.content);
		
		this.position();
	},
	hide: function()
	{
		$(this.OLcontainer + ', ' + this.LBcontainer)
		.fadeOut('fast')
		.remove();

		$('body').css('overflow', 'auto');
	},
	position: function()
	{
		var top = ($(window).height() - $(this.LBcontainer).height()) / 2;
		var left = ($(window).width() - $(this.LBcontainer).width()) / 2;

		$(this.LBcontainer)
			.css({
				'top': (top + $(document).scrollTop()) + 'px',
				'left': left + 'px'
			})
			.fadeIn('slow');
	}
}


/* =================================== */
/* ==== VENTANA MODAL PARA EL BLOQUE = */
/* ==== DE INFORMACION DEL PANEL ==== */
/* =================================== */
var INNER_DIALOG = {

	overlay: '.inner-dialog-overlay',
	container: '.inner-dialog',
	insertInsideOf: null,
	title: '',
	content: '',
	
	show: function() {
		
		var _id = this;
		var contentWidth = 0;
		
		var $overlayParent = $(_id.overlay).parent();
		
		// Oculta las barras de navegacion
		$overlayParent.css('overflow-y', 'hidden');
		
		// Posiciona el overlay
		$(_id.overlay)
			.css({
				'position': 'absolute',
				'top': $overlayParent.scrollTop(),
				'opacity': 0.5
			}).fadeIn('slow');
		
		// Establece el titulo y contenido del dialogo
		$(_id.container).find('.title').text(_id.title)
		.end()
		.find('.content').html(_id.content);
		
		// Calcula las posiciones para centrar la ventana modal
		var left = ($overlayParent.outerWidth() - $(_id.container).width()) / 2;
		var top = ($overlayParent.outerHeight() - $(_id.container).height()) / 2;
		
		// Posiciona la ventana modal
		$(_id.container)
		.css({
				'left': left + 'px',
				'top': ($overlayParent.scrollTop() + top) + 'px'
		})
		.fadeIn('slow');
		
		/*contentWidth = $(this.MWContainer).width() + 20;
		$(this.MWContainer).width(contentWidth);
		*/
	},
	
	hide: function() {
		
		var $overlayParent = $(this.overlay).parent();
		
		$(this.overlay + ', ' + this.container).fadeOut();
		
		$(this.container).find('.content').html('');
		
		// Restablece las barras de desplazamiento
		$overlayParent.css('overflow-y', 'auto');
	}
};


var DROP_LIST = {
	listContainer: '#lista',
	source: null,
	cacheContainer: '#cache',
	initialized: false,
	isLoading: false,
	trigger: null,
	selectedItem: null,
	mainTriggerContainer: null,
	onSelect: function() {},

	init: function() {
		
		var DL = this;
		
		// Handler para cerrar las listas cuando se dad clic fuera de alguna
		$(document).bind('click.droplist', function(event) {
			
			$tgt = $(event.target);
			
			if( !$tgt.parents().hasClass('dropdown-list') && !$tgt.is('.dropdown-list-trigger') && !$tgt.is('.dropdown-list') ) {
				$('ul.dropdown-list').fadeOut();
			}
		});
		
		$('ul.dropdown-list').live('click', function(event) {
			
			var $tgt = $(event.target);
			
			if( $tgt.is('a') ) {
				event.preventDefault();
				
				// Guarda el valor del item seleccionado de la lista
				var selectedItem = {
					  value: $tgt.attr('href').split('#')[1]
					, label: $tgt.text()
					, 'event': event
				}
				
				DL.hide();
				
				// Ejecuta el callback asignado para la lista
				if( DL.onSelect && typeof DL.onSelect === 'function' )
					DL.onSelect.call(DL, selectedItem, DL.trigger);
			}
		});
		
		this.initialized = true;
	},
	
	load: function(event) {
		var DL = this;
		
		if( this.isLoading )
			return false;
		
		this.isLoading = true;
		
		DATA_LOADER.show();
		
		$.ajax({
			type: 'GET',
			url: DL.source,
			data: {},
			dataType: 'json',
			timeout: 60000,
			cache: false
		}).success( function(json) {
			try {
				
				if( !json.success ) {
					
					messageConsole.displayMessage(json.errorMessage, 'error');
					return false;
				}
				
				var lista = '<ul id="' + DL.listContainer.split('#')[1] + '" class="dropdown-list">';
				
				$.each( json.options, function() {
					
					lista += '<li><a href="#' + this.id + '">' + this.value + '</a></li>';
				});
				
				lista += '</ul>';
				
				$(DL.cacheContainer).append(lista);
				
				DL.show(event);
				
				return true;
			} catch(e) {
				messageConsole.displayMessage('Error: ' + e.message, 'error');
				return false;
			}
			
		}).complete( function() {
			DL.isLoading = false;
			DATA_LOADER.hide();
		});
	},
	
	show: function(event) {
		
		this.trigger = $(event.target);

		if( !this.initialized )
			this.init();

		$('ul.dropdown-list').fadeOut();

		if( ! $(this.listContainer).length ) {
			this.load(event);
			return false;
		}
		
		$(this.listContainer).appendTo('body');
		
		// Este bloque determina si el dropdown list se mostrara a la derecha
		// o izquierda del elemento donde ocurre el evento que la muestra
		var windowWidth = parseInt($(document).width());
		var listWidth = parseInt($(this.listContainer).outerWidth());
		var eventXPos = parseInt(event.pageX);
		var targetHeight = $(event.target).outerHeight();
		var targetYPos = $(event.target).offset().top;
		var targetXPos = $(event.target).offset().left;
		
		// Muestra la lista a la izquierda si sobrepasa el ancho de la ventana
		if( (eventXPos + listWidth) > windowWidth )
			eventXPos = eventXPos - listWidth - (eventXPos - targetXPos);
		else
			eventXPos = targetXPos;
			
		$(this.listContainer)
		.css({'top': ( targetYPos + targetHeight) + 'px', 'left': (eventXPos) + 'px'})
		.fadeToggle('fast');
	},
	
	hide: function() {
		$(this.listContainer).fadeOut().appendTo(this.cacheContainer);
	}
}


var TABS = {
	container: '',
	
	init: function() {
		
		var T = this;
		
		// Comportamiento estandar de tabbed navigation
		$('.tab-nav').click( function(event) {
			
			$target = $(event.target);
			
			if( $target.is('a:not(.disabled)') ) {
				
				T.onSelect(event, $target);
				
				$target.parent().siblings().children().removeClass('selected');
				
				$target.addClass('selected');
				
				$('.tab-panel', $(this).next()).hide();
				
				$($target.attr('href')).show();
			}
			
			event.preventDefault();
		});
		
		this.reset();
	},
	
	disable: function(tabIndex) {
		
		if( typeof tabIndex === 'undefined' ) {
			
			$(this.container).find('.tab-nav li a').addClass('disabled');
			$(this.container).find('.overlay').fadeIn('fast');
			
		} else {

			var $tab = $(this.container).find('.tab-nav li a').eq(tabIndex);
			$tab.addClass('disabled');
			
			var $tabPanel = $(this.container).find($tab.attr('href'));
			var $overlay = $('<div class="overlay"></div>');
			$tabPanel.append($overlay);
			$overlay.fadeIn('fast');
		}
	},
	
	enable: function(tabIndex) {
		
		if( typeof tabIndex === 'undefined' ) {
			
			$(this.container).find('.tab-nav li a').removeClass('disabled');
			$(this.container).find('.overlay').fadeOut('fast');
			
		} else {
			
			var $tab = $(this.container).find('.tab-nav li a').eq(tabIndex);
			$tab.removeClass('disabled');
			
			var $tabPanel = $(this.container).find($tab.attr('href'));
			$tabPanel.find('.overlay').fadeOut( 'fast', function() { $(this).remove(); });
		}
	},
	
	disableAllButSelected: function() {
		
		$(this.container).find('.tab-nav li a:not(.selected)').addClass('disabled');
		//$(this.container).find('.overlay').fadeIn('fast');
	},
	
	disablePanels: function() {},
	
	reset: function() {
		$(this.container).find('.tab-nav li a').removeClass('selected').first().addClass('selected');
		$(this.container).find('.tab-panel').hide().first().show();
	},
	
	isSelected: function(tab) {
		if( tab.hasClass('selected') )
			return true;
		else
			return false;
	},
	
	onSelect: function(event, tab){}
}


var DATA_LOADER = {
	container: 'div.data-loader',
	position: 'center',
	overlayID: null,
	
	show: function() {
		
		overlayID = 'ol' + (new Date().getTime());

		// DISABLE WINDOW SCROLLBARS
		$('body').css({'overflow': 'hidden'});
		
		// CREA UN OVERLAY
		$('<div id="' + overlayID + '" class="overlay"></div>')
		.hide()
		.css('opacity', 0.5)
		.appendTo('body')
		.fadeIn('fast');
		
		if( ! $(this.container).length ) {
			$('body').append('<div class="data-loader"></div>');
		}

		this.position();

		$(this.container).stop(true, true).fadeIn('fast');
	},
	
	hide: function() {
		$(this.container).remove();

		//$('#' + overlayID)
		$('.overlay')
		.fadeOut('fast')
		.remove();

		$('body').css('overflow', 'auto');
	},
	
	position: function() {
		var top = ($(window).height() - $(this.container).height()) / 2;
		var left = ($(window).width() - $(this.container).width()) / 2;

		$(this.container)
			.css({
				'top': (top + $(document).scrollTop()) + 'px',
				'left': left + 'px'
			});
	}
}

function TreeViewList(container) {
	this.container = container;
	this.selectedNode = null;
	
	var tree = this;
	
	this.fill = function() {}
	
	this.selectNode = function(node) {
		
		this.selectedNode = {
			value: node.attr('data-id'),
			label: node.text(),
			element: node
		}
		
		this.onSelectNode();
	}
		
	this.onSelectNode = function() {}

	$(this.container).click( function(event) {
		
	    var $tgt = $(event.target);
		
	    if( $tgt.hasClass('text') ) {
	    	
	    	// Si ocurrio en el texto se selecciona
	        if( $tgt.hasClass('selectable') && !$tgt.hasClass('selected') ) {
        		// Quita la seleccion de el nodo que actualmente esta seleccionado
        		$(this).find('.text.selected').removeClass('selected');
        		$tgt.addClass('selected');
        		
        		tree.selectNode($tgt);
		    } else {
		    	$tgt.nextAll('ul').slideToggle();
		    	$tgt.prev('.handle').toggleClass('closed opened');
		    }
	    } else if( $tgt.hasClass('handle') ) {
        	$tgt.nextAll('ul').slideToggle();
        	
			$tgt.toggleClass('closed opened');
	    }
	});
};

TreeViewList.prototype.treeClass = '.tree';

// var uxTable = function(container, editableCellsArray) {

// 	console.log(this);
	
// 	this.tableContainer = container;
// 	this.editableColumns = editableCellsArray || [];
// 	this.isCellBeignEdited = false;
// 	this.finishEditing = null;
	
// 	var UT = this;
	
// 	// Maneja el evento clic en la tabla
// 	$(this.tableContainer).click( function(event) {

// 		//console.log('clic en tabla');
// 	    var $tgt = $(event.target);
	    
// 	    UT.blurTables();
	
// 	    // Hace la tabla activa
// 	    $(this).addClass('active');
	
// 	    if( $tgt.parents().is('tbody') ) {

// 	        if( $tgt.is('td') ) {
	        	
// 	        	if( UT.isCellBeignEdited )
// 	        		UT.finishEditCell();
	        	
// 	            UT.selectCell($tgt);
//             }
// 	    }
// 	});
	
// 	// Solo registra los manejadores globales una vez
// 	if( !uxTable.prototype.isGlobalHandlerSet ) {
		
// 		// Handler global para manejar clics sobre la tabla
// 		// Si la tabla pierde el enfoque se inhabilitaran los eventos
// 		// para no interferir con otras partes de la interfaz
// 		// la clase .active en la tabla indica que los eventos seran manejados en la tabla
// 		$(document).click( function(event) {
			
// 		    var $tgt = $(event.target);

// 		    if( !$tgt.parents(uxTable.prototype.tableClass).length ) {
// 		        UT.blurTables();
// 		        //console.log('clic global');
// 		    }
// 		});

// 		// Handler global para manejar el evento keydown en la tabla
// 		// Solo maneja los eventos cuando una tabla esta activa (tiene el enfoque)
// 		$(document.documentElement).bind('keydown.uxTable', function(event) {
// 			//console.log('key global')
// 			//console.log(UT.tableContainer);

// 		    var $activeTable = $(this).find(uxTable.prototype.tableClass + '.active');

// 		    // Si hay una tabla activa, manejara los eventos
// 		    if( $activeTable.length ) {

// 		        var activeCellIX = UT.activeCell.index();
// 		        var keyCode = event.keyCode;
// 		        var direction = '';
// 		        var $cellToMove = false;
		        
// 		        // Bloque que permite editar una celda cuando la tecla Enter es presionada
// 		        if( keyCode === 13 ) {
		            
// 		            if( UT.isCellBeignEdited )
// 						UT.finishEditCell();
// 		            else
// 			            UT.editCell();
			            
// 		        } else {
		            
// 		        	// Si una celda esta siendo editada no se puede mover a otra hasta que termine la edicion
// 		        	// de la celda actual
// 		            if( UT.isCellBeignEdited )
// 		                return;
		            
// 		            var direction = '';
		                
// 		            switch( keyCode ) {
		            	
// 		                case 38:
// 		                    direction = 'up';
// 		                    $cellToMove = UT.activeCell.parent().prev().children().eq(activeCellIX);
// 		                break;
// 		                case 39:
// 		                    direction = 'next';
// 		                    $cellToMove = UT.activeCell.next();
// 		                break;
// 		                case 40:
// 		                    direction = 'down';
// 		                    $cellToMove = UT.activeCell.parent().next().children().eq(activeCellIX);
// 		                break;
// 		                case 37:
// 		                    direction = 'prev';
// 		                    $cellToMove = UT.activeCell.prev();
// 		                break;
// 		            }
					
// 		            // Si se va a mover hacia otra celda
// 		            if( direction ) {
			            
// 			            if( $cellToMove.length ) {
// 			            	UT.selectCell($cellToMove)
// 			            }
// 		            }
// 		        }
// 		    }
// 		});
		
// 		uxTable.prototype.isGlobalHandlerSet = true;
// 	}
	
// 	return this;
// }

// uxTable.prototype.isGlobalHandlerSet = false;
// uxTable.prototype.activeCell = null;
// uxTable.prototype.tableClass = '.ux-table';

// uxTable.prototype.editCell = function() {
	
// 	// Solo permitira editar la celda cuando este definida en el arreglo de celdas editables
// 	// o cuando no se especificaron celdas editables, todas seran editables
// 	if( this.editableColumns.indexOf(this.activeCell.index()) >= 0 || !this.editableColumns.length ) {

// 		this.isCellBeignEdited = true;
// 		// Agregar la clase in-edit a la celda para saber que se esta editando
// 	    this.activeCell.addClass('in-edit');
// 	    // Crea un textField con el texto de la celda para ser editado
// 	    var $inputBox = $('<input type="text" class="text" />').val(this.activeCell.text());
// 	    // Borra el contenido actual de la celda
// 	    this.activeCell.empty();
// 	    // Agrega el textField a la celda
// 	    $inputBox.appendTo(this.activeCell).focus( function(){ 
// 	    	$(this).select();
// 	    }).focus();
//     }
// }

// uxTable.prototype.finishEditCell = function() {
	
// 	var $inputBox = this.activeCell.children('input');
//     this.activeCell.text($inputBox.val());
//     $inputBox.remove();
//     this.activeCell.removeClass('in-edit');
    
//     this.isCellBeignEdited = false;
    
//     console.log(this.activeCell.index());
    
//     if( $.isFunction(this.finishEditing) )
//     	this.finishEditing();
// }

// uxTable.prototype.selectCell = function(cell) {
	
// 	if( uxTable.prototype.activeCell )
// 		uxTable.prototype.activeCell.removeClass('active-cell');
	
// 	uxTable.prototype.activeCell = cell;
// 	cell.addClass('active-cell');
// }

// uxTable.prototype.blurTables = function() {
// 	$(uxTable.prototype.tableClass).removeClass('active').find('td').removeClass('active-cell');
// }


/*
 * OPCIONES
 */
var OPCIONES = {
	
	container: '#opciones',
	
	disable: function() {
		$(this.container).find('input').prop('disabled', true)
		.filter(':button').addClass('disabled').end();
		//.filter(':checkbox').attr('checked', false);
	},
	
	enable: function(elements) {
		this.disable();
		
		if( elements.length === 0 )
			elements = 'input';
		
		$(this.container).find(elements).prop('disabled', false).removeClass('disabled');
	}
};


/* AJAX GENERAL ERROR HANDLER */
$(function() {
	$(document).ajaxError(
		function(event, jqXHR, ajaxSettings, thrownError) {

			var msg = 'Ocurrio un error. Por favor intente otra vez';
			/*
			console.log(thrownError);
			console.log(jqXHR.status)
			console.log(jqXHR.statusText);
			*/
			if( jqXHR.statusText === 'abort' )
				return;

			if( jqXHR.statusText === 'timeout' )
				msg = 'La petición no pudo completarse en el tiempo esperado, Intente otra vez'

			if( jqXHR.status === 404 ) {
				msg = 'El recurso solicitado no se pudo localizar';
			}

			messageConsole.displayMessage(msg, 'error');
			//console.log(JSON.stringify(jqXHR));
			//console.log(JSON.stringify(ajaxSettings));
			
			// Bloque try para atrapar la excepcion lanzada cuando se genera un timeout en la peticion
			/*
			try {
				var errorMessage = 'Estatus ' + jqXHR.status + ': ';
				var displayError = true;
				
				if( jqXHR.readyState != 0 ) {
					
					switch(jqXHR.status) {
						case 404:
							errorMessage += 'La pagina ' + ajaxSettings.url + ' no existe.';//$(xhr.responseText)[5].innerHTML
							break;
						case 500:
							errorMessage += 'Error del servidor de internet.';
							break;
						default:
							errorMessage += jqXHR.statusText;//'Error desconcido.';
							displayError = false;
							break;
					}
					
				} else {
					
					switch(jqXHR.statusText) {
						
						case 'timeout':
							errorMessage += 'Se termino el tiempo de espera asignado para realizar la petición.';
							break;
							
						// Don´t show error when a request is aborted by custom code
						case 'abort':
							errorMessage += 'Peticion abortada.';
							displayError = false;
							break;
						
						//case 'error':
						//	errorMessage += '';
						//	break;
						//case 'notmodified':
						//	errorMessage += '';
						//	break;
						case 'parsererror':
							errorMessage += '';
							break;
						
						default:
							errorMessage += jqXHR.statusText;
							displayError = false;
							break;
					}
				}
				
				if( displayError )
					messageConsole.displayMessage(errorMessage, 'error');
				
			} catch(e) {
				messageConsole.displayMessage(e.message, 'error');
			}
			*/
		}
	);

	messageConsole.init();
	
	LIGHTBOX.title = 'Modulos SAO';
	LIGHTBOX.content = '<div style="text-align:center;"><img src="img/loaders/rounded-blocks_50x50.gif" /><h5>Cargando Menu ... Espere por favor</h5></div>';
	LIGHTBOX.closeButton = false;
	LIGHTBOX.closeOverlay = false;
	LIGHTBOX.show();
	
	// Carga el menu de la aplicacion
	$.ajax({
		type: 'GET',
		url: 'inc/lib/controllers/MenuAplicacionController.php',
		data: {
			action: 'getMenu'
		},
		dataType: 'json'
	}).success( function(json) {
		
		try {
			
			if( ! json.success ) {
				messageConsole.displayMessage(json.message, 'error');
				return false;
			}
			
			if( json.noRows ) {
				messageConsole.displayMessage(json.message, 'info');
				return false;
			}
			
			$('#app-nav').empty().html(json.menu);
		} catch(e) {
			messageConsole.displayMessage('Error: ' + e.message, 'error');
		}
		
	}).fail( function(jqXHR, textStatus, errorMessage) {
		if( textStatus != 'timeout' )
			messageConsole.displayMessage('Error: ' + errorMessage, 'error');
	}).always( function() {
		LIGHTBOX.hide();
	});
});