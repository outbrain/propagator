<div style="margin: 20px">
	<div class="pull-right">
		<a href="#" class="" data-toggle="popover" data-html="true" data-placement="auto" title="Database role" data-content="
				<p>Show info about a specific role.
				<p>A role is a logical zone where you put your datasets. 
				<p>For example, you may have two different Hive clusters
				and three different MySQL masters, each with their own, different dataset. These would make for five roles.
				<p>You will assign a role to any distinct type of dataset. A database instance will have one or more
				roles associated. It is also possible that instances share the same role, so a role should be associated with
				one or more instances.
				<p>For example, your build server should obviously have roles of your production servers. Maybe your
				build server applies to all your production servers, in which case it must be associated with all roles your production
				servers have.
				<p>Roles are defined in the <code>database_role</code> table. Instance-role associations are
				defined in <code>database_instance_role</code>. 
			"><span class="glyphicon glyphicon-info-sign"></span> learn more</a>
	</div>
	<h6>Database role</h6>
	<?php { $this->view("database_role_table", array("database_roles" => array($database_role))); } ?>
</div>


<?php if(!empty($instances)) { ?>
	<div style="margin: 20px">
		<h6>Instances</h6>
    	<div class="pull-right">
            <a href="#" class="" data-toggle="popover" data-html="true" data-placement="bottom" title="" data-content="
                    <pre><?php echo $instances_compact; ?></pre>
                "></span>Compact list</a>
		</div>
        Database instances associated with this role:
		<table class="table table-striped table-bordered table-condensed">
			<thead>
				<tr>
                	<?php { $this->view("database_instance_table_header", array()); } ?>
				</tr>
			</thead>
			<?php foreach($instances as $instance) { ?>
				<tr>
                	<?php { $this->view("database_instance_table_entry", array("database_instance" => $instance)); } ?>
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
	<h6>Scripts history</h6>
	<a href="<?php echo site_url()."?action=propagate_script_history&database_role_id=".$database_role["database_role_id"]; ?>">Find scripts</a> deployed with this role.
</div>

<?php if($is_dba) {?>
    <div style="margin: 20px">
        <ul id="action_tabs" class="nav nav-tabs">
            <li class="active"><a href="#duplicate_role_tab" data-toggle="tab"><span class="glyphicon glyphicon-plus"></span> Duplicate</a></li>
            <li><a href="#rewire_role_tab" data-toggle="tab"><span class="glyphicon glyphicon-random"></span> Rewire</a></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane active" id="duplicate_role_tab">
                <h6>New role based on current</h6>

                <div class="pull-left">
                    Create a new role with same properties, same set of instances & same query mappings as <b><?php echo $database_role["database_role_id"] ?></b>.
                </div>

                <div class="pull-right">
                    <form action="index.php" method="post" class="form-inline" name="duplicate_database_role_form" id="duplicate_database_role_form">
                        <input type="hidden" name="action" value="duplicate_database_role">
                        <input type="hidden" name="database_role_id" value="<?php echo htmlspecialchars($database_role["database_role_id"]); ?>">
                        <input type="text" name="new_database_role_id" placeholder="New database role name">
                        <input type="text" name="new_database_role_description" placeholder="Description">
                        <input class="btn-primary btn-small" type="submit" value="Duplicate" name="submit"/>
                    </form>
                </div>
            </div>

            <div class="tab-pane" id="rewire_role_tab">
            	<div class="tab-pane-section">
	                <h6>Rewire hosts</h6>
	
	                <div class="pull-left">
	                    Assign this role to other hosts; remove this role from other hosts
	                </div>
	
	                <div class="pull-right">
	                    <form action="index.php" method="post" class="form-inline" name="rewire_database_role_form" id="rewire_database_role_form">
	                        <input type="hidden" name="action" value="rewire_database_role">
	                        <input type="hidden" name="database_role_id" value="<?php echo htmlspecialchars($database_role["database_role_id"]); ?>">
	                        <select class="chosen-select chosen-select-instances" multiple="true" name="assigned_instance_ids[]">
	                            <?php foreach($all_database_instances as $instance) { ?>
	                                <tr>
	                                    <option value="<?php echo $instance["database_instance_id"] ?>"
	                                        <?php if(in_array($instance["database_instance_id"], $assigned_instance_ids)) {?>
	                                            selected="true"
	                                        <?php } ?>
	                                    ><?php echo $instance["host"] ?>:<?php echo $instance["port"] ?></option>
	                                </tr>
	                            <?php } ?>
	                        </select>
	                        <input class="btn-primary btn-small" type="submit" value="Rewire" name="submit"/>
	                    </form>
	                </div>
				</div>                
                <div class="tab-pane-section">
	                <h6>Rewire known schemas</h6>
	
	                <div class="pull-left">
	                    Associate schemas with this role
	                </div>
	
	                <div class="pull-right">
	                    <form action="index.php" method="post" class="form-inline" name="rewire_database_role_schema_form" id="rewire_database_role_schema_form">
	                        <input type="hidden" name="action" value="rewire_database_role_schemas">
	                        <input type="hidden" name="database_role_id" value="<?php echo htmlspecialchars($database_role["database_role_id"]); ?>">
	                        <select class="chosen-select chosen-select-schemas" multiple="true" name="assigned_schema_ids[]">
	                            <?php foreach($all_known_schemas as $schema) { ?>
	                                <tr>
	                                    <option value="<?php echo $schema["known_deploy_schema_id"] ?>"
	                                        <?php if(in_array($schema["known_deploy_schema_id"], $assigned_schemas_ids)) {?>
	                                            selected="true"
	                                        <?php } ?>
	                                    ><?php echo $schema["schema_name"] ?></option>
	                                </tr>
	                            <?php } ?>
	                        </select>
	                        <input class="btn-primary btn-small" type="submit" value="Rewire" name="submit"/>
	                    </form>
	                </div>
	            </div>
            </div>
        </div>
    </div>
<?php } ?>

<script lang="JavaScript">
    $(document).ready(function() {
        $(".chosen-select.chosen-select-instances").chosen({
            placeholder_text_multiple: "Choose instance",
            width: "480px"
        });
        $(".chosen-select.chosen-select-schemas").chosen({
            placeholder_text_multiple: "Choose schema",
            width: "480px"
        });
    });
</script>
