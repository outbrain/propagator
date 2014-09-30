<div style="margin: 20px">
	<div class="pull-right">
		<a href="#" class="" data-toggle="popover" data-html="true" data-placement="auto" title="Database instance" data-content="
				<p>Show info about a specific database instance.
				<p>Instances are defined in the <code>database_instance</code> table. You will want to associate
				an instance with one or more <em>roles</em>, by populating the <code>database_instance_role</code> table. 
				<p>You can view the replication topology for this instance. Topology requires that you have <b>pt-slave-find</b>, as part of Percona Toolkit, installed and in path.
				<p>An instance may have schema mappings, as defined in the <code>database_instance_schema_mapping</code> table.
				With such mappings a particular instance may declare per schema its personal alternative schema (which has the same
				set of objects: tables, views etc.)
			"><span class="glyphicon glyphicon-info-sign"></span> learn more</a>
	</div>
	
	<h6>Database instance</h6>
	<table class="table table-striped table-bordered table-condensed">
		<thead>
			<tr>
               	<?php { $this->view("database_instance_table_header", array()); } ?>
				<th>Info</th>
			</tr>
		</thead>
		<tr>
            <?php { $this->view("database_instance_table_entry", array("database_instance" => $instance)); } ?>
			<td>
				<a href="<?php echo site_url()."?action=database_instance_topology&database_instance_id=".$instance["database_instance_id"]; ?>">Topology</a>
			</td>
			</tr>
	</table>
</div>


<?php if(!empty($roles)) { ?>
	<div style="margin: 20px">
		<h6>Roles</h6>
		Database roles associated with this instance:
		<?php { $this->view("database_role_table", array("database_roles" => $roles)); } ?>
	</div>
<?php } ?>

<?php if(!empty($schema_mappings)) { ?>
	<div style="margin: 20px">
	<h6>Schema mapping</h6>
		This particular instance will replace schema names upon deployments. 
		It will replace any schema appearing as "Map from" with name listed as "Map to". 
		<table class="table table-striped table-bordered table-condensed">
			<thead>
				<tr>
					<th>Map from</th>
					<th>Map to</th>
				</tr>
			</thead>
			<?php foreach($schema_mappings as $schema_mapping) { ?>
				<tr>
					<td><?php echo $schema_mapping["from_schema"]; ?></td>
					<td><?php echo $schema_mapping["to_schema"]; ?></td>
				</tr>
			<?php } ?>
		</table>
	</div>
<?php } ?>


<?php if(!empty($query_mappings)) { ?>
	<div style="margin: 20px">
		<h6>Query mapping</h6>
		Script queries issued on instances of this role will be transformed as follows:
		<table class="table table-striped table-bordered table-condensed">
			<thead>
				<tr>
					<th>Mapping type</th>
					<th>Mapping key</th>
					<th>Mapping value</th>
				</tr>
			</thead>
			<?php foreach($query_mappings as $query_mapping) { ?>
				<tr>
					<td><?php echo $query_mapping["mapping_type"]; ?></td>
					<td><?php echo htmlspecialchars($query_mapping["mapping_key"]); ?></td>
					<td><?php echo htmlspecialchars($query_mapping["mapping_value"]); ?></td>
				</tr>
			<?php } ?>
		</table>
	</div>
<?php } ?>

<div style="margin: 20px">
	<ul id="deployments_tabs" class="nav nav-tabs">
        <li class="active"><a href="#recent_deployments_tab" data-toggle="tab"><span class="glyphicon glyphicon-th-list"></span> History</a></li>
        <li><a href="#pending_deployments_tab" data-toggle="tab"><span class="glyphicon glyphicon-flag"></span> Pending</a></li>
    </ul>
</div>
    
<div class="tab-content">
    <div class="tab-pane active" id="recent_deployments_tab">
		<?php {
			$this->view('view_script_instance_deployments', array(
				"is_dba" => $is_dba,
				"script" => null,
				"approve_script_mode" => false,
				"propagate_script_instance_deployments" => $instance_deployments_history
				)
			);
		} ?>
	</div>
	<div class="tab-pane" id="pending_deployments_tab">
		<form action="index.php" method="GET" class="form-inline" name="approve_instance_deployments_form" id="approve_instance_deployments_form">
			<input type="hidden" name="action" value="approve_instance_deployments">
			<input type="hidden" name="deploy_action" value="approve">
			<span id="propagate_script_instance_deployments">
				<?php {
						$this->view('view_script_instance_deployments', array(
							"is_dba" => $is_dba,
							"script" => null,
							"approve_script_mode" => false,
							"deployment_actions_available" => true,
							"propagate_script_instance_deployments" => $pending_instance_deployments_history
							)
						);
					} ?>
			</span>
			<div id="approve_script_form_submit_container">
				<center>
					<button class="btn btn-primary btn-small" type="button" id="approve_script_form_approve_button"/>Approve</button>
					<input class="btn-small alert-error" type="submit" value="Disapprove" name="disapprove" id="approve_script_form_disapprove_button"/>
				</center>
			</div>
		</form>
	</div>
</div>

<?php if($is_dba) {?>
    <div style="margin: 20px">
        <ul id="action_tabs" class="nav nav-tabs">
            <li class="active"><a href="#duplicate_instance_tab" data-toggle="tab"><span class="glyphicon glyphicon-plus"></span> Duplicate</a></li>
            <li><a href="#rewire_instance_tab" data-toggle="tab"><span class="glyphicon glyphicon-random"></span> Rewire</a></li>
            <li><a href="#delete_instance_tab" data-toggle="tab"><span class="glyphicon glyphicon-remove-circle"></span> Delete</a></li>
		</ul>

        <div class="tab-content">
            <div class="tab-pane active" id="duplicate_instance_tab">
                <h6>New instance based on current</h6>

                <div class="pull-left">
                    Create a new instance with same set of roles, same query mappings & same schema mappings as <b><?php echo "".$instance["host"].":".$instance["port"] ?></b>.
                    Environment will be set to <b><?php echo $instance["environment"]; ?></b>; it will not be a guinea pig.
                </div>

                <div class="pull-right">
                    <form action="index.php" method="post" class="form-inline" name="duplicate_database_instance_form" id="duplicate_database_instance_form">
                        <input type="hidden" name="action" value="duplicate_database_instance">
                        <input type="hidden" name="database_instance_id" value="<?php echo htmlspecialchars($instance["database_instance_id"]); ?>">
                        <input type="text" name="new_database_instance_host" placeholder="Host name/address" class="input-medium">
                        <input type="text" name="new_database_instance_port" value="<?php echo htmlspecialchars($instance["port"]); ?>" placeholder="Port" class="input-mini">
                        <input type="text" name="new_database_instance_description" value="<?php echo htmlspecialchars($instance["description"]); ?>" placeholder="Description">
                        <input class="btn-primary btn-small" type="submit" value="Duplicate" name="submit"/>
                    </form>
                </div>
            </div>

            <div class="tab-pane" id="rewire_instance_tab">
                <h6>Rewire roles</h6>

                <div class="pull-left">
                    Assign this instance to other roles; disassociate this instance from roles.
                </div>

                <div class="pull-right">
                    <form action="index.php" method="post" class="form-inline" name="rewire_database_instance_form" id="rewire_database_instance_form">
                        <input type="hidden" name="action" value="rewire_database_instance">
                        <input type="hidden" name="database_instance_id" value="<?php echo htmlspecialchars($instance["database_instance_id"]); ?>">
                        <select class="chosen-select" multiple="true" name="assigned_role_ids[]">
                            <?php foreach($database_roles as $role) { ?>
                                <tr>
                                    <option value="<?php echo $role["database_role_id"] ?>"
                                        <?php if(in_array($role["database_role_id"], $assigned_role_ids)) {?>
                                            selected="true"
                                        <?php } ?>
                                    ><?php echo $role["database_role_id"] ?></option>
                                </tr>
                            <?php } ?>
                        </select>
                        <input class="btn-primary btn-small" type="submit" value="Rewire" name="submit"/>
                    </form>
                </div>
            </div>

            <div class="tab-pane" id="delete_instance_tab">
                <h6>Delete this instance</h6>

                <div class="pull-left">
                    Completely forget this instance. It will not be deployed to; there will be no history for this instance.
                </div>
                
                <div class="pull-right">
                    <form action="index.php" method="post" class="form-inline" name="delete_database_instance_form" id="delete_database_instance_form">
                        <input type="hidden" name="action" value="delete_database_instance">
                        <input type="hidden" name="database_instance_id" value="<?php echo htmlspecialchars($instance["database_instance_id"]); ?>">
                        <input class="btn-danger btn-small" type="submit" value="Delete" name="submit"/>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php } ?>


<script lang="JavaScript">
	$(document).ready(function() {
		$("#recent_deployments_tab :checkbox").attr("disabled", true).prop("checked", false);
        $(".chosen-select").chosen({
            placeholder_text_multiple: "Choose roles",
            width: "480px"
        });
    	
    	$("input.all_deployment_instances").live("click", function() {
    		var is_checked = this.checked;
    	    $(this).closest("form").find("input[name='instance[]']").each(function(index) {
    	    	if (!$(this).is(":disabled")) {
    		    	$(this).prop("checked", is_checked);
    	    	}
    		});
    	});            

    	$("#delete_database_instance_form").submit(function() {
    		if (confirm('Are you sure you want to delete this instance?')) {
    			return true;
    		}
    		return false;
    	});                 
    	
	});
</script>


