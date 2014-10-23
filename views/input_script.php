<div style="margin: 20px">
	<?php { $this->view("check_credentials", array("has_credentials" => $has_credentials)); } ?>
	<form action="index.php" method="post" class="form-inline" id="input_script_form">
		<input type="hidden" name="action" value="submit_script">
		<center>
			<div class="splash_main">
				<h6>Propagate script</h6>
			</div>
		</center>
		<br/>
		<center>
			<div class="splash_main">
				<div class="col-lg-6">
    				<div class="input-group">
    					<div class="btn-group input-group-btn">
							<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" id="select_role">
								Choose role <span class="caret"></span>
							</button>
							<ul class="dropdown-menu">
								<?php foreach ($database_roles as $role)  { ?>
									<li><a href="#" data-type="database_role" data-value="<?php echo $role['database_role_id'] ?>">
										<?php echo $role['database_role_id'] ?>
									</a></li>
								<?php } ?>
							</ul>
						</div>
						<input type="text" id="database_role_id" name="database_role_id"
							value="<?php echo $database_role_id ?>" class="form-control"
							placeholder="Database role (cannot be empty)"/>
					</div>
				</div>
			</div>
		</center>
		<br/>
		<center>
			<div class="splash_main">
				<div class="col-lg-6">
    				<div class="input-group">
    					<div class="btn-group input-group-btn">
							<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" id="select_schema">
								Choose schema <span class="caret"></span>
							</button>
							<ul class="dropdown-menu">
								<li><a href="#" data-type="known_schema" data-value="">[none]</a></li>
								<?php foreach ($known_schemas as $known_schema) { ?>
									<li><a href="#" data-type="known_schema" data-value="<?php echo $known_schema['schema_name'] ?>">
										<?php echo $known_schema['schema_name'] ?>
										<?php if ($known_schema['has_mapping']) { ?> 
											<span class="glyphicon glyphicon-random"></span>
										<?php } ?>
									</a></li>
								<?php } ?>
							</ul>
						</div>
						<input type="text" id="script_default_schema" name="script_default_schema" 
							value="<?php echo $script_default_schema ?>" class="form-control"
							placeholder="Default schema (can be left blank if all script queries are fully qualified)"/>
					</div>
				</div>
				<div>
					Schemas noted by <span class="glyphicon glyphicon-random"></span> are mapped to different names on some database instances.
					<a href="#" class="" data-toggle="popover" data-html="true" data-placement="auto" title="Mapped schemas" data-content="
							<p>Different servers may use different schema names for the same logical data.
							For example, your production server may have a schema called <code>data_statistics</code>,
							and your build server may put same data under the <code>test_data_statistics</code> schema.
							<p>To set up schema mapping please populate the <code>database_instance_schema_mapping</code> table.
						"><span class="glyphicon glyphicon-info-sign"></span> learn more</a>
				</div>
			</div>
		</center>
		<br/>
		<center>
			<div class="splash_main">
				<div>
					Enter script (one or more queries, separated by semicolons) to propagate:
				</div>
				<textarea id="script_sql_code" name="script_sql_code"><?php echo $script_sql_code ?></textarea>
			</div>
		</center>
		<br/>
		<center>
			<div class="splash_main">
				<div>
					Description:
				</div>
				<textarea id="script_description" name="script_description"><?php echo $script_description ?></textarea>
			</div>
		</center>
		<!-- 
		<br/>
		<center>
			<div class="splash_main">
				<div>
					Deployment environments: where shall the script be deployed?<br/>
				</div>
				<?php {
					$this->view('input_deployment_environments', array());
				} ?>
    		</div>
		</center>
		 -->
		<br/>
		<center>
			<div>
				<button class="btn btn-primary btn-small" type="button" id="input_script_form_submit">Propagate</button>
			</div>
		</center>
	</form>
</div>


<script lang="JavaScript">
	$(document).ready(function()  {

		var databaseRoles = {
				<?php foreach ($database_roles as $role)  { ?>
					"<?php echo $role['database_role_id'] ?>" : <?php echo $role['has_schema'] ?>, 
				<?php } ?>
			};
		var databaseRolesMappedSchemas = {
				<?php foreach ($database_roles_mapped_schema_names as $role_schemas)  { ?>
					"<?php echo $role_schemas['database_role_id'] ?>" : "<?php echo $role_schemas['role_mapped_schema_names'] ?>", 
				<?php } ?>
			};
		function reviewDatabaseRole() {
			if (databaseRoles[$("#database_role_id").val()] >= 0) {
				return true;
			}
            alert("Unknown database role");
			return false;
		}

		function currentRoleMayRequireSchema() {
			return (databaseRoles[$("#database_role_id").val()] == 1);
		}

		function applySchemaFiltering() {
			
			if (currentRoleMayRequireSchema()) {
				$('#script_default_schema').removeAttr('disabled');
				$('#select_schema').removeAttr('disabled');
			} else {
				$('#script_default_schema').attr('disabled','disabled');
				$('#select_schema').attr('disabled','disabled');
			} 
			var supportedSchemas = databaseRolesMappedSchemas[$("#database_role_id").val()].split(",").filter(function (schema_name) {return schema_name != '';});
			if (supportedSchemas.length > 0) {
				$('a[data-type=known_schema]').hide();
				supportedSchemas.forEach(function(schema_name) {
					$('a[data-type=known_schema][data-value='+schema_name+']').show();
				});
				if (supportedSchemas.indexOf($("#script_default_schema").val()) < 0) {
					$("#script_default_schema").val(supportedSchemas[0]);					
				}
			} else {
				$('a[data-type=known_schema]').show();
			}			
		}
		$("#database_role_id").on('input propertychange paste', function() {
			applySchemaFiltering();
		});
		
		function reviewSchemaNameSubmission() {
			if ($("#script_default_schema").val() == '' && currentRoleMayRequireSchema()) {
				bootbox.confirm("You have not submitted a schema name.<p>This is allowed, but discouraged: you may wish to strip schema name from script code and specify the schema in its dedicated box.<p>Please confirm you with to proceed anyhow", function(confirm_result) {
					if (confirm_result) {
						checkIfScriptAlreadyExists();
					}
				});
			}
			else {
				checkIfScriptAlreadyExists();
			}
			return false;
		}

		function checkIfScriptAlreadyExists() {
			$.post("index.php", {action: "check_for_existing_script", script_sql_code: $("#script_sql_code").val()}, function(check_result) {
					if(check_result.script_exists) {
						bootbox.confirm(
						    "It seems like this same script has been <a href='<?php echo site_url();?>?action=view_script&propagate_script_id="+check_result.propagate_script_id+"' target='propagator_duplicate_script'>submitted before</a>."
						    + "<p>It may not make sense to generate same script again."
						    + "<p>Are you sure you wish to continue?", function(confirm_result) {
							if (confirm_result) {
							    submitScriptForm();
							}
						}); 
					}
					else {
					    submitScriptForm();
					}
				}, "json");
			return false;
		}
		
		function submitScriptForm() {
			if (!currentRoleMayRequireSchema()) {
				$('#script_default_schema').removeAttr('disabled');
				$("#script_default_schema").val("");
			}
			$("#input_script_form").submit();
		}

		$("#script_sql_code").on('input propertychange paste', function() {
		});
		$("#input_script_form_submit").click(function() {
		    if (!reviewDatabaseRole())
		        return false;
			reviewSchemaNameSubmission();
			return false;
		});
		
		$('[data-type="database_role"]').click(function() {
			$("#database_role_id").val($(this).attr("data-value"));
			applySchemaFiltering();
		});
		$('[data-type="known_schema"]').click(function() {
			$("#script_default_schema").val($(this).attr("data-value"));
		});

		applySchemaFiltering();
});
</script>
