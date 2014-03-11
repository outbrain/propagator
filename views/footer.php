
		<div class="modal fade" id="main_modal" tabindex="-1" role="dialog" aria-labelledby="mainModalLabel" aria-hidden="true">
		  <div class="modal-dialog">
		    <div class="modal-content">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		        <h4 class="modal-title" id="main_modal_title"></h4>
		      </div>
		      <div class="modal-body"></div>
		      <div class="modal-footer">
		        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
		      </div>
		    </div><!-- /.modal-content -->
		  </div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
		
	</div>
	
	
<div class="modal fade" id="input_credentials_modal" >
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Submit credentials</h4>
      </div>
      <div class="modal-body">
      	<div>
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
			</form>
		</div>
		<div class="alert alert-error" id="input_credentials_modal_verification_error">
			Verification failed
		</div>
		
		
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-small btn-default" data-dismiss="modal">Cancel</button>
        <button 
        	type="button" class="btn btn-primary btn-small has-spinner" id="submit_credentials"
			data-loading-text="<span class='spinner glyphicon glyphicon-refresh'></span> Checking">
			Submit
		</button>
		<button type="button" class="btn btn-small alert-error" id="force_submit_credentials">Submit anyway</button>
	  </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
	
	
	
</body>
  
  
</html>