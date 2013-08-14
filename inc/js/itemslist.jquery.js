;
( function($, window, undefined) {

	var methods = {

		init: function( options ) {

			options = $.extend( {}, $.fn.itemslist.defaults, options );
			
			var $this = $(this);

			return this.each( function() {
				console.log(this);
				loadData.call( $this, options );
			});
		}
	}

	function loadData( options ) {

		console.log( options );

		var $this = this;

		if( options.source ) {

			$.ajax({
				type: 'GET',
				url: options.source,
				data: options.data,
				dataType: 'json'
			}).done( function( json ) {

				if( !json.success ) {
					$.error( json.errorMessage );
					return;
				}

				biuldList.call( $this, options, json.options );

			} )
		}
	}

	function biuldList( options, data ) {

		if( !data || data.length === 0 )
			return;

		var items = '';

		$.each( data, function() {
			items += '<li><a href="#' + this.value + '">' + this.label + '</a></li>';
		});

		this.bind( 'click.itemslist', function( event ) {

			var $tgt = $(event.target);

			if( $tgt.is('a') ) {

				var item = {
					'label': $tgt.text(),
					'value': $tgt.attr('href').split('#').pop(),
				}

				options.selectedItem = item;

				$tgt.addClass('selected').parent().siblings().children().removeClass('selected');
			}
		});

		console.log(this)

		this.append( items );
	}

	$.fn.itemslist = function( method ) {

		if( methods[method] ) {
			return methods[method].apply(this, Array.prototype.slice.call( arguments, 1 ));
		} else if( typeof method === 'object' || !method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' + method + ' does not exist in the jQuery.itemslist' )
		}
	}

	$.fn.itemslist.defaults = {
		source: null,
		data: null,
		selectedItem: null,
		onSelect: function( selectedItem ) {}
	}

})(jQuery, window, undefined);