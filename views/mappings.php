<div style="margin: 20px">
	<h6>All mappings</h6>
</div>


<?php if(!empty($general_query_mappings)) { ?>
	<div style="margin: 20px">
		<h6>General query mapping</h6>
		All script queries will be transformed as follows:
		<table class="table table-striped table-bordered table-condensed">
			<thead>
				<tr>
					<th>Mapping type</th>
					<th>Mapping key</th>
					<th>Mapping value</th>
				</tr>
			</thead>
			<?php foreach($general_query_mappings as $query_mapping) { ?>
				<tr>
					<td><?php echo $query_mapping["mapping_type"]; ?></td>
					<td><?php echo htmlspecialchars($query_mapping["mapping_key"]); ?></td>
					<td><?php echo htmlspecialchars($query_mapping["mapping_value"]); ?></td>
				</tr>
			<?php } ?>
		</table>
	</div>
<?php } ?>



<?php if(!empty($database_roles_query_mappings)) { ?>
	<div style="margin: 20px">
		<h6>Role query mapping</h6>
		Script queries transformed by role associated with:
		<table class="table table-striped table-bordered table-condensed">
			<thead>
				<tr>
					<th>Database role</th>
					<th>Mapping type</th>
					<th>Mapping key</th>
					<th>Mapping value</th>
				</tr>
			</thead>
			<?php foreach($database_roles_query_mappings as $query_mapping) { ?>
				<tr>
   					<td><a href="<?php echo site_url()."?action=database_role&database_role_id=".$query_mapping["database_role_id"]; ?>"><?php echo $query_mapping["database_role_id"]; ?></a></td>
					<td><?php echo $query_mapping["mapping_type"]; ?></td>
					<td><?php echo htmlspecialchars($query_mapping["mapping_key"]); ?></td>
					<td><?php echo htmlspecialchars($query_mapping["mapping_value"]); ?></td>
				</tr>
			<?php } ?>
		</table>
	</div>
<?php } ?>


<?php if(!empty($database_instances_query_mappings)) { ?>
	<div style="margin: 20px">
		<h6>Instance query mapping</h6>
		Script queries transformed by specific instances they are executed against:
		<table class="table table-striped table-bordered table-condensed">
			<thead>
				<tr>
                	<?php { $this->view("database_instance_table_header", array()); } ?>
					<th>Mapping type</th>
					<th>Mapping key</th>
					<th>Mapping value</th>
				</tr>
			</thead>
			<?php foreach($database_instances_query_mappings as $query_mapping) { ?>
				<tr>
                	<?php { $this->view("database_instance_table_entry", array("database_instance" => $query_mapping)); } ?>
					<td><?php echo $query_mapping["mapping_type"]; ?></td>
					<td><?php echo htmlspecialchars($query_mapping["mapping_key"]); ?></td>
					<td><?php echo htmlspecialchars($query_mapping["mapping_value"]); ?></td>
				</tr>
			<?php } ?>
		</table>
	</div>
<?php } ?>


<?php if(!empty($database_instance_schema_mapping)) { ?>
	<div style="margin: 20px">
		<h6>Schema mapping</h6>
		Schemas are transformed per specific instances:
		<table class="table table-striped table-bordered table-condensed">
			<thead>
				<tr>
                	<?php { $this->view("database_instance_table_header", array()); } ?>
					<th>From schema</th>
					<th>To schema</th>
				</tr>
			</thead>
			<?php foreach($database_instance_schema_mapping as $query_mapping) { ?>
				<tr>
                	<?php { $this->view("database_instance_table_entry", array("database_instance" => $query_mapping)); } ?>
					<td><?php echo htmlspecialchars($query_mapping["from_schema"]); ?></td>
					<td><?php echo htmlspecialchars($query_mapping["to_schema"]); ?></td>
				</tr>
			<?php } ?>
		</table>
	</div>
<?php } ?>
