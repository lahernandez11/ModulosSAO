(function( $ ) {

	$.extend({
		notify: function( options ) {

			var defaults = {
				text: '',
				sticky: false,
				notifyDuration: 3000,
				effectDuration: 600,
			}, opts, notifyWrapper, notify, notificationContent, notificationClose, timer;

			var classes = {
				notifyWrapper: 'notify-wrapper',
				notification: 'notify',
				close: 'notify-close',
				icon: 'notify-icon',
				text: 'notify-text',
				content: 'notify-content'
			}

			opts = $.extend(defaults, options);

			notifyWrapper = $('.' + classes.notifyWrapper);

			if( !notifyWrapper.length ) {
				notifyWrapper = $('<div class="' + classes.notifyWrapper + '"></div>');
			}

			notifyWrapper.appendTo('body');

			notify = $('<div/>').addClass(classes.notification)
			 .animate({
			 	'opacity': 'show'
			  } , 'slow')
			 .prependTo(notifyWrapper);

			 notificationContent = $('<div/>').addClass(classes.content);

			 if( !opts.sticky ) {
			 	timer = setTimeout( function() {
			 		$(notify).animate({'opacity': '0'}, opts.effectDuration, function() {
			  		$(this).animate({'height': '0px'}, 'normal', function() {
			  			$(this).remove();
			  		});
			  	});
			 	}, opts.notifyDuration);
			 }


			 notificationClose = $('<div/>').addClass(classes.close).html('x')
			  .one('click', function() {

			  	if( timer )
			  		clearTimeout(timer);

			  	$(notify).animate({'opacity': '0'}, opts.effectDuration, function() {
			  		$(this).animate({'height': '0px'}, 'normal', function() {
			  			$(this).remove();
			  		});
			  	});
			 });

			notificationContent
			 .append($('<div/>').addClass(classes.icon))
			 .append($('<div/>').addClass(classes.text).html(opts.text))
			 .append(notificationClose)
			 .appendTo(notify);
		}
	});
})(jQuery);