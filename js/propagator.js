
$(document).ready(function() {
	prettyPrint();
	
	$("a[data-toggle=popover]").popover();
    $('a[data-toggle=popover]').live("click", function(e) {e.preventDefault(); return true;});
	
	$("*[data-deployment-status='failed']").live("click", function() {
		var dialog_title = "";
		if ($(this).attr("data-failed-query"))
			dialog_title = "script failed on query #"+$(this).attr("data-failed-query");
		else
			dialog_title = "script failed before issuing queries";
		return show_popover(this, dialog_title, $(this).attr("data-last-message"));
	});

	$("*[data-deployment-status][data-deployment-status!='failed']").live("click", function() {
		return show_popover(this, $(this).attr("data-deployment-status"), $(this).attr("data-last-message"));
	});
	
	$("*[data-deployment-status]").live("click", function() {
		return false;
	});
	
	// All things input credentials
	$(function(){
	    $('.has-spinner').click(function() {
	        $(this).toggleClass('active');
	    });
	});		
	$('a[data-link-type="input_credentials"]').click(function() {
		return prompt_for_credentials();
	});
	$("#submit_credentials").click(function() {
		$("#input_credentials_modal_verification_error").hide();
		$("#force_submit_credentials").hide();
		$("#submit_credentials").button('loading');
        var spin_icon = $(this).find("span");
        spin_icon.addClass("icon-refresh-animate");

		$.post("index.php", {action: "verify_mysql_credentials", username: $("#input_username").val(), password: $("#input_password").val()}, function(verification_result) {
				$("#submit_credentials").button('reset');
				if(verification_result.success) {
					return submit_credentials();
				}
				else {
					$("#input_credentials_modal_verification_error").show();
					$("#force_submit_credentials").show();
				}
			}, "json");
		return false;
	});
	$("#force_submit_credentials").click(function() {
		return submit_credentials();
	});
	function submit_credentials() {
		$.post("index.php", {action: "set_credentials", propagator_mysql_user: $("#input_username").val(), propagator_mysql_password: $("#input_password").val(), ajax: 1}, function(post_result) {
				$('#input_credentials_modal').modal('hide');		
				$('[data-warn-missing-credentials]').hide();	
			}, "json");
		return false;
	}
	//
});

function show_modal(title, content) {
	$("#main_modal .modal-title").html(title);
	$("#main_modal .modal-body").html(content);
	$('#main_modal').modal({});
	return false;
}

function show_popover(element, popover_title, popover_content) {
    $(element).popover({title: popover_title, content: popover_content, placement: "auto", html: true});
    if ($(element).attr("data-popover-initialized")) {
    }
    else {
        $(element).attr("data-popover-initialized", "true");
        $(element).popover('show');
    }
	return false;
}

function prompt_for_credentials(callback_function) {
	$('#input_credentials_modal').modal({});
	$("#force_submit_credentials").hide();
	$("#input_credentials_modal_verification_error").hide();
	if(typeof(callback_function)!=='undefined') {
		$('#input_credentials_modal').on('hidden.bs.modal', callback_function);
	}

	return false;
}

function has_mysql_credentials() {
	var has_credentials = false;
	$.ajax({
		type: 'get',
		url: "index.php",
		data: {action: "has_mysql_credentials_request"},
		async: false,
		success: function(request_result) {
			has_credentials = request_result.has_credentials;
		},
		dataType: "json"
	});
	return has_credentials;
}
