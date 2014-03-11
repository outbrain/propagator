<div style="margin: 20px">
	<h6>Database instance</h6>
	<table class="table table-striped table-bordered table-condensed">
		<thead>
			<tr>
               	<?php { $this->view("database_instance_table_header", array()); } ?>
			</tr>
		</thead>
		<tr>
            <?php { $this->view("database_instance_table_entry", array("database_instance" => $instance)); } ?>
		</tr>
	</table>
</div>

<div style="margin: 20px">
	<?php if ($has_credentials ) { ?>
		<h6>Topology</h6>
		<pre class="database_instance_topology"><?php echo $topology; ?></pre>
	<?php } else { ?>
		<div class="alert alert-danger">
			This operation cannot procees without database credentials.
		    <a data-link-type="input_credentials" href="<?php echo site_url().'?action=input_credentials' ?>">Submit your database credentials here</a>.
		</div>
	<?php } ?>
</div>		
