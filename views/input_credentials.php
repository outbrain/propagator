<center>
<div style="margin: 20px" class="splash_main">
	<h6>Submit credentials</h6>
	<p>
		<i>Propagator</i> does not store credentials for your database servers. 
		You must supply your credentials before submitting queries for propagation.
		Your credentials will be stored in your session, and will therefore expire
		when you leave.
	</p>
	<p>
		You will typically enter here your MySQL user & password. Make sure that
		these credentials have the privileges for DDL statements such as 
		<code>CREATE</code>, <code>ALTER</code>, <code>DROP</code>.
	</p>
</div>

<div>
	<form action="index.php" method="GET" class="form-inline" id="submit_credentials_form">
		<input type="hidden" name="action" value="set_credentials">
		<div class="splash_row">
			Username<br/>
			<input type="text" class="input-medium" name="username" placeholder="Username" id="input_username"/>
		</div>
		<div class="splash_row">
			Password<br/>
			<input type="password" class="input-medium" name="password" placeholder="Password" id="input_password"/>
		</div>
		<button type="button" class="btn btn-primary btn-small has-spinner" id="submit_credentials"
			data-loading-text="<span class='spinner glyphicon glyphicon-refresh'></span> Checking">
			Submit
		</button>
	</form>
</div>
</center>

<script lang="JavaScript">
	$(document).ready(function()  {
		$(function(){
		    $('.has-spinner').click(function() {
		        $(this).toggleClass('active');
		    });
		});		
		$("#submit_credentials").click(function() {
			$("#submit_credentials").button('loading');
	        var icon = $( this ).find("span");
	        icon.addClass("icon-refresh-animate");

			$.post("index.php", {action: "verify_mysql_credentials", username: $("#input_username").val(), password: $("#input_password").val()}, function(verification_result) {
					$("#submit_credentials").button('reset');
					if(verification_result.success) {
						$("#submit_credentials_form").submit();
					}
					else {
						bootbox.confirm("Verification failed<p>Click 'OK' to continue anyhow (not recommended), or 'Cancel' to resubmit your credentials.", function(confirm_result) {
							if(confirm_result) {
								$("#submit_credentials_form").submit();
							}
						}); 
					}
				}, "json");
			return false;
		});
		
	});
</script>
