$( document ).ready(function() {
    //console.log( "ready!" );
	
	$('.form [name="template"]').change(function() {
		
		switch($(this).val()) {
			case "tmp1":
				$('.form #text1').show();
				$('.form #text2').hide();
				$('.form #text3').hide();
				$('.form #text4').hide();
			break;
			case "tmp2":
				$('.form #text1').show();
				$('.form #text2').show();
				$('.form #text3').hide();
				$('.form #text4').hide();
			break;
			case "tmp3":
				$('.form #text1').show();
				$('.form #text2').hide();
				$('.form #text3').hide();
				$('.form #text4').hide();
			break;
			case "tmp4":
				$('.form #text1').show();
				$('.form #text2').show();
				$('.form #text3').show();
				$('.form #text4').show();
			break;
		} 
	});
	
	$('.form button').click(function(){
		
		$("#preview").addClass("loader");
		$("#preview").removeClass("error");
		var action = $(this).attr('name');
		var data = $('.form').serialize() + "&action=" + action;
		$(".form button").prop("disabled",true);
		
		$.ajax({url: "index.php?ajax", data: data, method: "POST", dataType: "json"})
		
		.done(function(data) {
			if(data.okay) {
				if(data.error) {
					$("#preview").addClass("error");
					$("#preview").html(data.html);
				} else {
					$("#preview").html(data.html);
					if(data.location !== undefined) {
						window.location.href = data.location;
					}
				}
				
			} else {
				$("#preview").html(data.html).addClass("error");
			}
		})
		.fail(function( data ) {
			$("#preview").html("Server Error. Something went wrong!").addClass("error");
		})
		.always(function( data ) {
			$("#preview").removeClass("loader");
			$(".form button").prop("disabled",false);
		});
		
	});
});