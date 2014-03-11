<div style="margin: 20px">
	<div class="pull-right">
		<a href="#" class="" data-toggle="popover" data-html="true" data-placement="auto" title="Search/list scripts" data-content="
			<p>See script history and script deployment status.
			<p>This listing also suggests how many deployments of each script were successful, how many failed, and what's
			the overall deployment status.
		"><span class="glyphicon glyphicon-info-sign"></span> learn more</a>
	</div>
	
	<h6>Matched scripts</h6>


	<ul class="pager">
		<li class="previous <?php echo ($has_previous_page ? '' : 'disabled') ?>">
			<a href="<?php echo site_url()."?action=propagate_script_history&page=".((int)$page-1)."&submitter=".$submitter."&script_fragment=".$script_fragment."&database_role_id=".$database_role_id."&default_schema=".$default_schema; ?>">
				<span class="glyphicon glyphicon-chevron-left"></span> Previous
			</a>
		</li>
		<li class="next <?php echo ($has_next_page ? '' : 'disabled') ?>">
			<a href="<?php echo site_url()."?action=propagate_script_history&page=".((int)$page+1)."&submitter=".$submitter."&script_fragment=".$script_fragment."&database_role_id=".$database_role_id."&default_schema=".$default_schema; ?>">
				Next <span class="glyphicon glyphicon-chevron-right"></span>
			</a>
		</li>
	</ul>
		
	<table class="table table-striped table-bordered table-condensed">
		<thead>
			<tr>
				<th>Script id</th>
				<th>Submitted at</th>
				<th>Submitter</th>
				<th>Role</th>
				<th>Schema</th>
				<th>SQL code</th>
				<th>Description</th>
				<th>Deployed environments</th>
				<th>Total/Passed/Failed</th>
			</tr>
		</thead>
		<?php foreach ($propagate_scripts as $script) { ?>
		<tr>
			<td><a href="<?php echo site_url()."?action=view_script&propagate_script_id=".$script["propagate_script_id"]; ?>"><?php echo $script["propagate_script_id"]; ?></a></td>
			<td><?php echo $script["submitted_at"]; ?></td>
			<td>
				<a href="<?php echo site_url()."?action=propagate_script_history&submitter=".$script["submitted_by"]; ?>"><?php echo $script["submitted_by"]; ?></a>
			</td>
			<td>
				<a href="<?php echo site_url()."?action=propagate_script_history&database_role_id=".$script["database_role_id"]; ?>"><?php echo $script["database_role_id"]; ?></a>
				<a href="<?php echo site_url()."?action=database_role&database_role_id=".$script["database_role_id"]; ?>"><span class="glyphicon glyphicon-circle-arrow-right"></span></a>
			</td>
			<td>
				<a href="<?php echo site_url()."?action=propagate_script_history&default_schema=".$script["default_schema"]; ?>"><?php echo $script["default_schema"]; ?></a>
			</td>
			<td><pre class="script_sql_code prettyprint lang-sql"><?php echo $script["sql_code"]; ?></pre></td>
			<td>
				<?php echo $script["description"]; ?>
				<br/>
				<ul><?php echo preg_replace('/\t(.*?):([^\t]*)/', '<li><img src="img/icons/comment_mark_${1}.png"/> ${2}', $script["script_comments"]); ?></ul>
			</td>
			<td><?php echo $script["deployment_environments"]; ?></td>
			<td>
				<div class="pagination-right notify-circle <?php if ($script["count_deployment_servers_passed"] == $script["count_deployment_servers"]) echo "notify-circle-success"; ?> <?php if ($script["count_deployment_servers_failed"] > 0) echo "notify-circle-error"; ?>"><?php echo $script["count_deployment_servers"]; ?></div>
				<div class="pagination-right notify-circle <?php if ($script["count_deployment_servers_passed"] > 0) echo "notify-circle-info"; ?>"><?php echo $script["count_deployment_servers_passed"]; ?></div>
				<div class="pagination-right notify-circle <?php if ($script["count_deployment_servers_failed"] > 0) echo "notify-circle-error"; ?>"><?php echo $script["count_deployment_servers_failed"]; ?></div>
			</td>
		</tr>
		<?php } ?>
	</table>

	<?php if (count($propagate_scripts) > 5) { ?>
		<ul class="pager">
			<li class="previous <?php echo ($has_previous_page ? '' : 'disabled') ?>">
				<a href="<?php echo site_url()."?action=propagate_script_history&page=".((int)$page-1)."&submitter=".$submitter."&script_fragment=".$script_fragment."&database_role_id=".$database_role_id."&default_schema=".$default_schema; ?>">
					<span class="glyphicon glyphicon-chevron-left"></span> Previous
				</a>
			</li>
			<li class="next <?php echo ($has_next_page ? '' : 'disabled') ?>">
				<a href="<?php echo site_url()."?action=propagate_script_history&page=".((int)$page+1)."&submitter=".$submitter."&script_fragment=".$script_fragment."&database_role_id=".$database_role_id."&default_schema=".$default_schema; ?>">
					Next <span class="glyphicon glyphicon-chevron-right"></span>
				</a>
			</li>
		</ul>	
	<?php } ?>
</div>

<script lang="JavaScript">
	$(document).ready(function()  {
		$(".pager .disabled a").click(function() {
			return false;
		});
	});
</script>
