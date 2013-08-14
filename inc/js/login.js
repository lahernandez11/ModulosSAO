$( function() {
	
	$('#usuario').focus();
	
	$.ajax({
		
		type: 'GET',
		url: 'inc/lib/controllers/LoginController.php',
		data: {
			action: 'terminaSesion'
		}
	});
	
	LOGINFORM = {
		
		login: function() {
			
			$('#entrar').addClass('disabled').attr('disabled', 'disabled');
			
			$.ajax({
				type: 'GET',
				url: 'inc/lib/controllers/LoginController.php',
				data: {
					  usr: $('#usuario').val()
					, pwd: $('#clave').val()
					, action: 'logueaUsuario'
				},
				dataType: 'json'
			}).success( function(json) {
				try {
					if( ! json.success ) {

						$('.LoginErrorBar span').html(json.message);
						$('.LoginErrorBar span').parent().show();

						return;
					}

					window.location.replace('index.php');
					
				} catch(e) {
					$('.LoginErrorBar span').html(e.message);
					$('.LoginErrorBar span').parent().show();

					return;
				}
			}).complete( function()	{
				$('#entrar').removeClass('disabled').removeAttr('disabled');
			});
		}
	}
	
	$('#entrar').click(function(event) {
		
		LOGINFORM.login();
		
		event.preventDefault();
	});
});