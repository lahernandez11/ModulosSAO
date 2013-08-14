(function( $ ) {
	
	var classes = {
		treeClass: 'tree',
		selectedNode: 'selected',
		handleOpen: 'opened',
		handleClose: 'closed',
		nodeCaption: 'text'
	},

	methods = {
		
		init: function( options ) {
			
			var options = $.extend( {}, $.fn.presupuestoObra.defaults, options );

			return this.each( function() {

				var id = (new Date().getTime());

				var $this = $(this),
					eventNamespace = 'presupuestoObra' + id;

				var config = getConfig.call($this);
				
				if ( ! config.length ) {

					$this.data('presupuestoObra', {

						  id: id,
						  options: options,
						  containerDialogID: 'presupuesto-' + id,
					});
				}

				// Crear un contenedor para utilizarlo como un jQuery Dialog
				// y mostrar el presupuesto en el, asignandole un id especifico
				var $containerDialog = $('<ul id="' + $this.data('presupuestoObra').containerDialogID + '"></ul>');
				
				$containerDialog.addClass(classes.treeClass);

				$containerDialog.dialog({
					autoOpen: false,
					modal: true,
					width: 760,
					height: 390,
					show: 'fold',
					title: 'Presupuesto de Obra'
				});

				$this.on('click', function() {

					clearNodeSelection.call($this);
					clearTree.call($this);
					abreDialogoArbol.call($this);
					showDescendants.call($this);
				});

				// Ocurre al dar doble clic/dblclick en un elemento texto del arbol para utilizarlo
				$containerDialog.on('dblclick.presupuestoObra, click.presupuestoObra', ('.' + classes.nodeCaption), function(event) {

					if ( event.type == 'click' ) {
						
						selectNode.call($this, this);
					} else if ( event.type == 'dblclick' ) {
						
						$selectedNode = $(this).parent();

						if ( $this.is('input') ) {
							$this.val($selectedNode.children('.' + classes.nodeCaption).text());
						} else {
							$this.text($selectedNode.children('.' + classes.nodeCaption).text());
						}

						getConfig.call($this).options.selectedNode = {

							id: $selectedNode.attr('data-id'),
							text: $selectedNode.children('.' + classes.nodeCaption).text()
						};

						getConfig.call($this).options.onSelectNode.call($selectedNode, getConfig.call($this).options.selectedNode);

						cierraDialogArbol.call($this);
					}

					event.stopPropagation();
				});

				// Ocurre al dar clic en el "handle" de un nivel del presupuesto
				$containerDialog.on('click.presupuestoObra', '.handle', function(event) {

					var $handle = $(event.target);
					var $parentElement = $handle.parent('li');

					showDescendants.call($this, $parentElement);
					
					event.stopPropagation();
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

			if( value ) {
				getConfig.call(this).options[opt] = value;
			}
			else {
				return getConfig.call(this).options[opt];
			}
		},

		clear: function() {
			
			getConfig.call(this).options.selectedNode = {id: null, text: null};

			if ( this.is('input') ) {
				this.val('');
			} else {
				this.text('');
			}
		}
	},

	selectNode = function( nodeElement ) {

		clearNodeSelection.call(this);
		$(nodeElement).addClass(classes.selectedNode);
	},

	unselectNode = function( elements ) {
		elements.removeClass(classes.selectedNode);
	},

	clearNodeSelection = function() {

		unselectNode($('#' + this.data('presupuestoObra').containerDialogID)
		.find(('.' + classes.nodeCaption) + '.' + classes.selectedNode).removeClass(classes.selectedNode));
	},

	clearTree = function() {
		$('#' + this.data('presupuestoObra').containerDialogID).empty();
	}

	abreDialogoArbol = function() {
		$('#' + this.data('presupuestoObra').containerDialogID).dialog('open');
	},

	cierraDialogArbol = function() {
		$('#' + this.data('presupuestoObra').containerDialogID).dialog('close');
	},

	showDescendants = function( parentElement ) {

		var parentElement = parentElement || $('#' + this.data('presupuestoObra').containerDialogID);

		if( ! isDescentantsLoaded.call(this, parentElement) ) {
			loadNodes.call( this, parentElement );
		} else {

			if ( parentElement.children('.handle').hasClass(classes.handleClose) ) {
				expandNode.call( this, parentElement );
			} else {
				contractNode.call( this, parentElement );
			}
		}
	},

	loadNodes = function( parentElement ) {

		var that = this,
			config = getConfig.call(this);
		
		//console.log("Loading descendants of nodeID= " + parentElement.attr('data-id'))

		config.options.data.parentID = parentElement.attr('data-id');

		config.options.onLoadNodes.call(this);

		$.ajax({
			type: 'GET',
			url: config.options.dataSource,
			dataType: 'json',
			data: config.options.data
		})
		.done( function( json ) {

			$.each(json.nodes, function() {
				addNodo.call(that, config.options.onAddNodes.call(this), parentElement);
			});

			expandNode.call( this, parentElement );
		})
		.always( function() {
			config.options.onNodesLoaded();
		});
	},

	isDescentantsLoaded = function( parentElement ) {
		if ( parentElement.children('ul').length ) {
			return true;
		} else {
			return false;
		}
	},

	addNodo = function( nodeData, parentElement ) {
		var $node;

		createDescendantsContainer.call(this, parentElement);			

		$node = 
		$(
		  '<li data-id="' + nodeData.ID + '">'
		+   '<span class="handle ' + classes.handleClose + '"></span>'
		+   '<span class="' + classes.nodeCaption + '">' + nodeData.text + '</span>'
		+ '</li>');

		getDescendantsContainer.call(this, parentElement).append($node);
	},

	createDescendantsContainer = function( parentElement ) {
		
		if ( ! parentElement.is('ul') && parentElement.children('ul').length == 0 ) {
			$('<ul></ul>').appendTo(parentElement);
		}
	},

	getDescendantsContainer = function( parentElement ) {
		
		var $container;

		if ( parentElement.is('ul') ) {
			$container = $('#' + this.data('presupuestoObra').containerDialogID);
		} else {
			$container = parentElement.children('ul');
		}

		return $container;
	},

	expandNode = function( nodeElement ) {
		nodeElement.children('ul').slideDown()
		.end().children('.handle').toggleClass(classes.handleClose + ' ' + classes.handleOpen);
	},

	contractNode = function( nodeElement ) {
		nodeElement.children('ul').slideUp()
		.end().children('.handle').toggleClass(classes.handleClose + ' ' + classes.handleOpen);
	}

	getConfig = function() {

		var data = this.data('presupuestoObra');

		return data || {};
	},

	$.fn.presupuestoObra = function( method ) {

		if( methods[method] ) {
			
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1) )
		} else if( typeof method === 'object' || ! method ) {
			
			return methods.init.apply(this, arguments);
		} else {

			$.error( 'Method' + method + ' does not exists in the jQuery.presupuestoObra' );
		}
	};

	$.fn.presupuestoObra.defaults = {
		dataSource: 'controllers/ArbolPresupuestoController.php',
		data: {},
		selectedNode: {id: null, text: null},
		onAddNodes: function( nodeData ){},
		onLoadNodes: function(){},
		onSelectNode: function(){},
		onNodesLoaded: function(){},
	};

})( jQuery );