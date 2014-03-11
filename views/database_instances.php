<div style="margin: 20px">
	<div class="pull-right">
		<a href="#" class="" data-toggle="popover" data-html="true" data-placement="auto" title="Database instances" data-content="
				<p>Listing of all instances
				<p>These are all of the instances known to <i>propagator</i>.
				 This list should only include replication masters.
			"><span class="glyphicon glyphicon-info-sign"></span> learn more</a>
	</div>
	<h6>Instances</h6>
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
