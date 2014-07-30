<?php if(!empty($propagate_script_instance_deployments)) { ?>
		<div style="margin: 20px">
			<h6 data-propagate-script-instance-deployments-checksum="<?php echo $propagate_script_instance_deployments_checksum; ?>">Instance deployments</h6>
			<?php if ($approve_script_mode) { ?>
				Based on the given role, the script will be deployed on these servers. If unsure, leave all checkboxes checked.
			<?php } ?>
			 
			<table class="table table-striped table-bordered table-condensed">
				<thead>
					<tr>
						<th class="minimal-width"><input type="checkbox" name="all_deployments" class="all_deployment_instances" value="1" ></th>
						<th>Env</th>
						<th>Script id</th>
						<th>Instance host</th>
						<th>Instance port</th>
						<th>Schema</th>
						<th>Deployment type</th>
						<th>Status</th>
						<th>Submitter</th>
						<th>Time</th>
						<th>Current</th>
						<?php if ($deployment_actions_available) { ?>
							<th class="script_deployment_actions">Actions</th>
						<?php } ?>
					</tr>
				</thead>
				<?php foreach($propagate_script_instance_deployments as $deployment) { ?>
					<tr>
						<td class="minimal-width">
							<input type="checkbox" name="instance[]" 
								value="<?php echo $deployment["database_instance_id"]; ?>" 
								data-instance-environment="<?php echo $deployment["environment"]; ?>"
								<?php if ($deployment["action_enabled"]) { ?>
								<?php } else { ?>
									disabled="1"
								<?php } ?>
							>
						</td>
						<td class="instance-environment instance-environment-<?php echo $deployment["environment"]; ?>"><a href="#" data-instance-environment="<?php echo $deployment["environment"]; ?>"><?php echo $deployment["environment"]; ?></a></td>
						<td>
							<a href="<?php echo site_url()."?action=view_script&propagate_script_id=".$deployment["propagate_script_id"]; ?>"><?php echo $deployment["propagate_script_id"]; ?></a>
						</td>
						<td>
							<a href="<?php echo site_url()."?action=database_instance&database_instance_id=".$deployment["database_instance_id"]; ?>"><?php echo $deployment["host"]; ?></a>
                            <?php if ($deployment["description"]) {?>
                                <div class="pull-right">
                                    <a href="#" class="" data-live-popover="true" data-toggle="popover" data-html="true" data-placement="auto"
                                        data-content="<?php echo $deployment["description"]; ?>">
                                        <span class="glyphicon glyphicon-info-sign"></span>
                                    </a>
                                </div>
							<?php } ?>
						</td>
						<td><?php echo $deployment["port"]; ?></td>
						<td><?php echo $deployment["deploy_schema"]; ?></td>
						<td>
							<?php if ($deployment["is_guinea_pig"]) {?>
								<a href="#" class="" data-live-popover="true" data-toggle="popover" data-html="true" data-placement="auto" title="Guinea pigs" data-content="
									<p>Guinea pig instances are deployed first.
									<p>At least one guinea pig deployment must pass in order for non-guinea pigs deployments to begin.
								"><img src="img/icons/guinea_pig.png" alt="Guinea pig" title="Guinea pig"></a>
							<?php } ?>
							<?php echo $deployment["deployment_type"]; ?>
						</td>
						<td>
							<img src="img/icons/<?php echo $deployment["deployment_status"]; ?>.gif" alt=""/>
							<a 
								data-deployment-status="<?php echo $deployment["deployment_status"]; ?>" 
								data-is-guinea-pig="<?php echo $deployment["is_guinea_pig"]; ?>" 
								data-propagate-script-instance-deployment-id="<?php echo $deployment["propagate_script_instance_deployment_id"]; ?>"
								data-last-message="<?php echo str_replace('"', '\\"', $deployment["last_message"]); ?>" 
								data-current-query="<?php echo $deployment["current_propagate_script_query_id"]; ?>"
								data-deployment-type="<?php echo $deployment["deployment_type"]; ?>"
								href="#">
								<?php echo str_replace('_', ' ', $deployment["deployment_status"]); ?>
							</a>
						</td>
						<td><?php echo $deployment["submitted_by"]; ?></td>
						<td>
						    <?php if (!is_null($deployment["processing_duration_seconds"])) {?>
                                <a href="#" class="" data-live-popover="true" data-toggle="popover" data-html="true" data-placement="auto"
                                    data-content="<?php echo $deployment["processing_start_time"]; ?> [start]<br/><?php echo $deployment["processing_end_time"]; ?> [end]">
                                        <?php echo $deployment["processing_duration_seconds"]; ?> seconds
                                </a>
    						<?php } ?>
						</td>
						<td>
						    <a href="#" data-toggle="popover" data-html="true" data-placement="auto" data-content="" data-propagate-script-query-id="<?php echo $deployment["current_propagate_script_query_id"]; ?>" class="current_propagate_script_query">
						        <?php echo $deployment["current_propagate_script_query_id"]; ?>
						    </a>
						    <div class="pull-right"><?php echo $deployment["query_progress_status"]; ?></div>
						</td>
						<?php if ($deployment_actions_available) { ?>
							<td class="deployment-actions">
							    <ul class="pager">
								<?php if ($is_dba || (($auth_user == $deployment["submitted_by"]) && $deployment["restartable_by_user"])) { ?>
									<li class="previous"><a href="#" data-dba-action="1" data-retry-deployment="1" data-count-executed-queries="<?php echo $deployment["count_executed_queries"]; ?>" data-propagate-script-instance-deployment-id="<?php echo $deployment["propagate_script_instance_deployment_id"]; ?>" data-deployment-host="<?php echo $deployment["host"]; ?>" data-dba-deployment-status="<?php echo $deployment["deployment_status"]; ?>" data-current-query="<?php echo $deployment["current_propagate_script_query_id"]; ?>"><span class="glyphicon glyphicon-play-circle alert-info" title="Run/retry deployment"></a></li>
								<?php } ?>
							
								<?php if ($is_dba) { ?>
									<li class="previous"><a href="#" data-dba-action="1" data-step-query-deployment="1" data-propagate-script-instance-deployment-id="<?php echo $deployment["propagate_script_instance_deployment_id"]; ?>" data-deployment-host="<?php echo $deployment["host"]; ?>" data-dba-deployment-status="<?php echo $deployment["deployment_status"]; ?>" data-current-query="<?php echo $deployment["current_propagate_script_query_id"]; ?>"><span class="glyphicon glyphicon-step-forward alert-info" title="Run next query"></span></a></li>
									<li class="previous"><a href="#" data-dba-action="1" data-skip-query-deployment="1" data-propagate-script-instance-deployment-id="<?php echo $deployment["propagate_script_instance_deployment_id"]; ?>" data-deployment-host="<?php echo $deployment["host"]; ?>" data-dba-deployment-status="<?php echo $deployment["deployment_status"]; ?>" data-current-query="<?php echo $deployment["current_propagate_script_query_id"]; ?>"><span class="glyphicon glyphicon-minus-sign alert-danger" title="Skip one query"></span></a></li>
									<li class="previous"><a href="#" data-dba-action="1" data-restart-deployment="1"        data-propagate-script-instance-deployment-id="<?php echo $deployment["propagate_script_instance_deployment_id"]; ?>" data-deployment-host="<?php echo $deployment["host"]; ?>" data-dba-deployment-status="<?php echo $deployment["deployment_status"]; ?>"><span class="glyphicon glyphicon-repeat alert-warning" title="Restart deployment"></a></li>
								<?php } ?>
								<?php if ($is_dba || ($auth_user == $deployment["submitted_by"])) { ?>
									<li class="previous"><a href="#" data-dba-action="1" data-mark-as-deployed-manually="1" data-propagate-script-instance-deployment-id="<?php echo $deployment["propagate_script_instance_deployment_id"]; ?>" data-deployment-host="<?php echo $deployment["host"]; ?>" data-dba-deployment-status="<?php echo $deployment["deployment_status"]; ?>"><span class="glyphicon glyphicon-thumbs-up alert-success" title="Mark as passed"></a></li>
								<?php } ?>
								</ul>
							</td>
						<?php } ?>
					</tr>
				<?php } ?>
			</table>
		</div>
<?php } ?>
