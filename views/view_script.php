<!-- Button trigger modal -->
<div style="margin: 20px">
	<?php { $this->view("check_credentials", array("has_credentials" => $has_credentials)); } ?>

	<div class="pull-right">
		<a href="#" class="" data-toggle="popover" data-html="true" data-placement="auto" title="Script view and propagation" data-content="
			<p>Show script code and propagation status.
			<p>See when and by whom this script was submitted, as well as a breakage to queries.
			<p>You may add comments to this script, possibly adding common marks or hints.
			<p>The script cannot be changed at this point. If you find that the script is wrong in any way,
			disapprove it on all instances (see following) and mark is as 'Cancelled'.
			<p>Control your script deployments:
				<ul>
					<li>
						No deployment begins until you approve it per database instance. Mark appropriate checkboxes
						and click <b>Approve</b> button.
					</li>
					<li>
						To make sure the script is <em>not</em> deployed on an instance, check it and click <b>Disapprove</b>.
					</li>
					<li>
						Guinea pig instances go first. At least one guinea pig deployment must pass before non guinea pig
						instances are allowed to deploy.
					</li>
					<li>
						Production servers do not deploy automatically. It takes a human intervention to deploy them.
						This is configurable in <code>conf/config.inc.php</code>
					</li>
					<li>
						DBAs can retry a deployment, re-run it or mark it as manaully deployed.
					</li>
					<li>
						You can redeploy the script: this only affects servers which were not found before.
					</li>
				</ul>

		"><span class="glyphicon glyphicon-info-sign"></span> learn more</a>
	</div>

	<h6>Propagate script</h6>
	This script is queued for approval and deployment. Please review the below and approve.

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
			</tr>
		</thead>
		<tr>
			<td><a href="<?php echo site_url()."?action=view_script&propagate_script_id=".$script["propagate_script_id"]; ?>"><?php echo $script["propagate_script_id"]; ?></a></td>
			<td><?php echo $script["submitted_at"]; ?></td>
			<td><a href="<?php echo site_url()."?action=propagate_script_history&submitter=".$script["submitted_by"]; ?>"><?php echo $script["submitted_by"]; ?></a></td>
			<td><a href="<?php echo site_url()."?action=database_role&database_role_id=".$script["database_role_id"]; ?>"><?php echo $script["database_role_id"]; ?></a></td>
			<td><?php echo $script["default_schema"]; ?></td>
			<td><pre class="script_sql_code prettyprint lang-sql"><?php echo $script["sql_code"]; ?></pre></td>
			<td><?php echo $script["description"]; ?></td>
		</tr>
	</table>
</div>


<?php if(!empty($propagate_script_comments)) { ?>
	<div style="margin: 20px">
		<h6>Comments</h6>
		<table class="table table-striped table-bordered table-condensed">
			<thead>
				<tr>
					<th>Comment</th>
					<th>Submitter</th>
					<th>Submitted at</th>
				</tr>
			</thead>
			<?php foreach($propagate_script_comments as $comment) { ?>
				<tr>
					<td>
						<?php if ($comment["comment_mark"]) { ?>
							<img src="img/icons/comment_mark_<?php echo $comment["comment_mark"]; ?>.png" alt="<?php echo $comment["comment_mark"]; ?>"/>
						<?php } ?>
						<?php echo $comment["comment"]; ?>
					</td>
					<td><?php echo $comment["submitted_by"]; ?></td>
					<td><?php echo $comment["submitted_at"]; ?>	</td>
				</tr>
			<?php } ?>
		</table>
	</div>
<?php } ?>

<form action="index.php" method="GET" class="form-inline" name="comment_script_form" id="comment_script_form">
	<input type="hidden" name="action" value="comment_script">
	<input type="hidden" name="propagate_script_id" value="<?php echo $script["propagate_script_id"]; ?>">
	<input type="hidden" name="mark_comment" value="" id="mark_comment">
	<div style="margin: 20px">
	    <div class="input-group">
			<div class="btn-group input-group-btn">
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" id="mark_comment_button">
					Mark as <span class="caret"></span>
				</button>
				<ul class="dropdown-menu">
					<li><a href="#" data-type="mark_comment" data-value="">[none]</a></li>
					<li><a href="#" data-type="mark_comment" data-value="ok"><img class="mark_comment_icon" src="img/icons/comment_mark_ok.png" alt=""/> OK</a></li>
					<li><a href="#" data-type="mark_comment" data-value="fixed"><img class="mark_comment_icon" src="img/icons/comment_mark_fixed.png" alt=""/> Fixed</a></li>
					<li><a href="#" data-type="mark_comment" data-value="todo"><img class="mark_comment_icon" src="img/icons/comment_mark_todo.png" alt=""/> TODO</a></li>
					<li><a href="#" data-type="mark_comment" data-value="cancelled"><img class="mark_comment_icon" src="img/icons/comment_mark_cancelled.png" alt=""/> Cancelled</a></li>
				</ul>
			</div>
		    <input type="text" class="form-control" name="script_comment" placeholder="Add comment"/>
	      	<span class="input-group-btn">
				<input type="submit" class="btn btn-primary btn-small" value="Comment"/>
			</span>
		</div>
	</div>
</form>

<?php if(!empty($propagate_script_queries)) { ?>
	<div style="margin: 20px">
		<h6>Script queries</h6>
		Query breakdown of the script.
		<div class="pull-right"><a href="#" id="toggle_propagate_script_queries"><span class="glyphicon glyphicon-eye-open"></span>  Show/hide</a></div>
		<div id="propagate_script_queries">
            <table class="table table-striped table-bordered table-condensed">
                <thead>
                    <tr>
                        <th>Query id</th>
                        <th>SQL code</th>
                    </tr>
                </thead>
                <?php foreach($propagate_script_queries as $query) { ?>
                    <a name="query-<?php echo $query["propagate_script_query_id"]; ?>"></a>
                    <tr>
                        <td><?php echo $query["propagate_script_query_id"]; ?></td>
                        <td><pre data-propagate-script-query="<?php echo $query["propagate_script_query_id"]; ?>" data-script-query="<?php echo htmlspecialchars($query["sql_code"]); ?>" class="script_sql_code prettyprint lang-sql"><?php echo $query["sql_code"]; ?></pre></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
	</div>
<?php } ?>

<a name="instance_deployments"></a>
<form action="index.php" method="GET" class="form-inline" name="approve_script_form" id="approve_script_form">
	<input type="hidden" name="action" value="submit_approve_script">
	<input type="hidden" name="propagate_script_id" value="<?php echo $script["propagate_script_id"]; ?>">
	<input type="hidden" name="deploy_action" value="approve">
	<span id="propagate_script_instance_deployments">
		<?php {
			$this->view('view_script_instance_deployments', array(
				"deployment_actions_available" => $deployment_actions_available,
				"auth_user" => $auth_user,
				"is_dba" => $is_dba,
				"script" => $script,
				"approve_script_mode" => $approve_script_mode,
				"propagate_script_instance_deployments" => $propagate_script_instance_deployments
				)
			);
		} ?>
	</span>
	<div id="approve_script_form_submit_container">
		<center>
			<button class="btn btn-primary btn-small" type="button" id="approve_script_form_approve_button"/>Approve</button>
			<input class="btn-small alert-error" type="submit" value="Disapprove" name="disapprove" id="approve_script_form_disapprove_button"/>
			<input class="btn-small alert-success" type="submit" value="Run manual" name="run manual" id="approve_script_form_run_manual_button" title="Bulk execute all manual deployments"/>
		</center>
	</div>
</form>

<div style="margin: 20px">
    <ul id="action_tabs" class="nav nav-tabs">
        <li class="active"><a href="#new_script_tab" data-toggle="tab"><span class="glyphicon glyphicon-plus"></span> New</a></li>
        <li><a href="#redeploy_tab" data-toggle="tab"><span class="glyphicon glyphicon-repeat"></span> Redeploy</a></li>
        <?php if(!empty($propagate_script_deployments)) { ?>
            <li><a href="#deployments_tab" data-toggle="tab"><span class="glyphicon glyphicon-list"></span> Deployments</a></li>
        <?php } ?>
    </ul>

    <div class="tab-content">
        <div class="tab-pane active" id="new_script_tab">
            <h6>New script based on current</h6>

            <div class="pull-left">
                Propagate a new script based on this script's role, schema and code. You will have chance to change any details before submitting.
            </div>

            <div class="pull-right">
                <form action="index.php" method="post" class="form-inline" name="redeploy_script_form" id="submit_new_script_form">
                    <input type="hidden" name="action" value="input_script">
                    <input type="hidden" name="database_role_id" value="<?php echo htmlspecialchars($script["database_role_id"]); ?>">
                    <input type="hidden" name="script_default_schema" value="<?php echo htmlspecialchars($script["default_schema"]); ?>">
                    <input type="hidden" name="script_sql_code" value="<?php echo htmlspecialchars($script["sql_code"]); ?>">
                    <input type="hidden" name="script_description" value="<?php echo htmlspecialchars($script["description"]); ?>">
                    <input class="btn-primary btn-small" type="submit" value="Propagate new" name="submit"/>
                </form>
            </div>
        </div>
        <div class="tab-pane" id="redeploy_tab">
            <h6>Redeploy on servers</h6>

            <div class="pull-left">
                You can redeploy on all servers. This may be desired if new servers have been added after this script was first deployed.<br/>
                At any case the script will not be deployed again on the same server.<br/>
                This redeployment applies to <a href="<?php echo site_url()."?action=database_role&database_role_id=".$script["database_role_id"]; ?>"><?php echo $script["database_role_id"]; ?></a> database role.
            </div>
            <div class="pull-right">
                <form action="index.php" method="GET" class="form-inline" name="redeploy_script_form" id="redeploy_script_form">
                    <input type="hidden" name="action" value="redeploy_script">
                    <input type="hidden" name="propagate_script_id" value="<?php echo $script["propagate_script_id"]; ?>">
                    <input class="btn-primary btn-small" type="submit" value="Redeploy" name="submit"/>
                </form>
            </div>
        </div>
        <?php if(!empty($propagate_script_deployments)) { ?>
            <div class="tab-pane" id="deployments_tab">
                <h6>Script deployments</h6>
                Environments on which this script has been requested to be deployed
                <table class="table table-striped table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>Deployment environment</th>
                            <th>Submitted at</th>
                            <th>Submitter</th>
                            </tr>
                    </thead>
                    <?php foreach($propagate_script_deployments as $deployment) { ?>
                        <a name="deployment-<?php echo $deployment["propagate_script_deployment_id"]; ?>"></a>
                        <tr>
                            <td><?php echo $deployment["deployment_environment"]; ?></td>
                            <td><?php echo $deployment["submitted_at"]; ?></td>
                            <td><?php echo $deployment["submitted_by"]; ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        <?php } ?>
    </div>

    <!--
    <div>
        <h6>Expand deployment servers</h6>
        Choose another deplyment environment or same environment again to propagate script to. Note:<br/>
        <ul>
            <li>Only unlisted servers will be added (you cannot deploy this script on same server twice).</li>
            <li>Applies to <a href="<?php echo site_url()."?action=database_role&database_role_id=".$script["database_role_id"]; ?>"><?php echo $script["database_role_id"]; ?></a> database role.</li>
        </ul>
        <form action="index.php" method="GET" class="form-inline" name="redeploy_script_form" id="redeploy_script_form">
            <input type="hidden" name="action" value="redeploy_script">
            <input type="hidden" name="propagate_script_id" value="<?php echo $script["propagate_script_id"]; ?>">
                    <?php {
                        $this->view('input_deployment_environments', array());
                    } ?>
            <center>
                <input class="btn-primary btn-small" type="submit" value="Propagate" name="submit"/>
            </center>
        </form>
    </div>
     -->
</div>

<script lang="JavaScript">
var need_update_countdown = 0;
$(document).ready(function() {
	
	$("input.all_deployment_instances").live("click", function() {
		var is_checked = this.checked;
	    $("input[name='instance[]']").each(function(index) {
	    	if (!$(this).is(":disabled")) {
		    	$(this).prop("checked", is_checked);
	    	}
		});
		apply_approve_script_form_buttons_enabled();
	});            

	$(":checkbox").live("change", function() {
		apply_approve_script_form_buttons_enabled();
	});
			     
	$("a[data-instance-environment]").live("click", function() {
		// Toggle check/uncheck for group of environments
		var env = $(this).attr("data-instance-environment");
		var target_envs = new Array(env);
		if ($.inArray(env, ["build", "production"]) >= 0)
			target_envs = ["qa", "build", "production"];
		var has_unchecked = false;
	    $("input[name='instance[]']").each(function(index) {
	    	
		    if ($.inArray($(this).attr("data-instance-environment"), target_envs) >= 0) {
			    if (!$(this).prop("checked"))
			    	has_unchecked = true;
		    }			    
		});
	    $("input[name='instance[]']").each(function(index) {
	    	if (!$(this).is(":disabled")) {
			    if ($.inArray($(this).attr("data-instance-environment"), target_envs) >= 0) {
	    			$(this).prop("checked", has_unchecked);
			    }
	    	}
		});
		apply_approve_script_form_buttons_enabled();
		return false;
	});

	$("#approve_script_form_disapprove_button").live("click", function() {
		if (confirm('Are you sure you want to disapprove these deployments?\nThe script will not be deleted.')) {
			$("input[name=deploy_action]").val("disapprove");
			return true;
		}
		return false;
	});                 

	$("#approve_script_form_approve_button").live("click", function() {
		if (has_mysql_credentials()) {
			$("#approve_script_form").submit();
			return true;
		}
		prompt_for_credentials(function() {
			if (has_mysql_credentials()) {
				$("#approve_script_form").submit();
				return true;
			}
		});
		return false;
	});

	$("#approve_script_form_run_manual_button").live("click", function() {
		var approved_manual_deployments = review_approved_manual_deployments();
		approved_manual_deployments.forEach(function(propagate_script_instance_deployment_id) {
            $.get("index.php?action=mark_propagate_script_instance_deployment&status=not_started&propagate_script_instance_deployment_id="+propagate_script_instance_deployment_id, function() {
                $.get("index.php?action=execute_propagate_script_instance_deployment&force_manual=true&message="+encodeURIComponent("Resumed manually")+"&propagate_script_instance_deployment_id="+propagate_script_instance_deployment_id, function(data) {
                    if (data == 'no_credentials') {
                        return prompt_for_credentials();
                    }
                });
                update_deployment_status(true);
            });		
		});
		return false;
	});                 
	
	$("*[data-retry-deployment]").live("click", function() {
		var propagate_script_instance_deployment_id = $(this).attr('data-propagate-script-instance-deployment-id');
        var message = "";
        if ($(this).attr('data-count-executed-queries') == "0") {
            message = 'Are you sure you want to run deployment on '+$(this).attr("data-deployment-host")+'?<br/>'
                + "Deployment will start from first query, "
                + "<a class='current_propagate_script_query' data-propagate-script-query-id='"+$(this).attr('data-current-query')+"' data-content='' href='#'>"+$(this).attr('data-current-query')+"</a><br/>"
                + 'Current status is "'+$(this).attr("data-dba-deployment-status")+'"';
        }
        else {
            message = "Are you sure you want to continue running deployment on "+$(this).attr("data-deployment-host")+"?<br/>"
                + "Deployment will continue from query "
                + "<a class='current_propagate_script_query' data-propagate-script-query-id='"+$(this).attr('data-current-query')+"' data-content='' href='#'>"+$(this).attr('data-current-query')+"</a>";
        }
        bootbox.confirm(message, function(confirm_result) {
            if(confirm_result) {
                $.get("index.php?action=mark_propagate_script_instance_deployment&status=not_started&propagate_script_instance_deployment_id="+propagate_script_instance_deployment_id, function() {
                    $.get("index.php?action=execute_propagate_script_instance_deployment&force_manual=true&message="+encodeURIComponent("Resumed manually")+"&propagate_script_instance_deployment_id="+propagate_script_instance_deployment_id, function(data) {
                        if (data == 'no_credentials') {
                            return prompt_for_credentials();
                        }
                    });
                    update_deployment_status(true);
                });
            }
        });
		return false;
	});

	$("*[data-step-query-deployment]").live("click", function() {
		var propagate_script_instance_deployment_id = $(this).attr('data-propagate-script-instance-deployment-id');
        var message = "";
        if ($(this).attr('data-current-query') == "") {
            alert('No current query, so nothing to step through');
            return false;
        }
        else {
            message = "Are you sure you want to step through a single query "
                + "<a class='current_propagate_script_query' data-propagate-script-query-id='"+$(this).attr('data-current-query')+"' data-content='' href='#'>"+$(this).attr('data-current-query')+"</a>"
                + " on "+$(this).attr("data-deployment-host")+"?";
        }
        bootbox.confirm(message, function(confirm_result) {
            if(confirm_result) {
                $.get("index.php?action=mark_propagate_script_instance_deployment&status=not_started&propagate_script_instance_deployment_id="+propagate_script_instance_deployment_id, function() {
                    $.get("index.php?action=execute_propagate_script_instance_deployment&force_manual=true&message="+encodeURIComponent("Single step")+"&run_single_query=true&propagate_script_instance_deployment_id="+propagate_script_instance_deployment_id, function(data) {
                        if (data == 'no_credentials') {
                            return prompt_for_credentials();
                        }
                    });
                    update_deployment_status(true);
                });
            }
        });
		return false;
	});

	$("*[data-skip-query-deployment]").live("click", function() {
		var propagate_script_instance_deployment_id = $(this).attr('data-propagate-script-instance-deployment-id');
        if ($(this).attr('data-current-query') == "") {
            alert('No current query, so nothing to skip');
            return false;
        }
        var message = "Are you sure you want to skip query "
            + "<a class='current_propagate_script_query' data-propagate-script-query-id='"+$(this).attr('data-current-query')+"' data-content='' href='#'>"+$(this).attr('data-current-query')+"</a>"
            + " on "+$(this).attr("data-deployment-host")+"?";
        bootbox.confirm(message, function(confirm_result) {
            if(confirm_result) {
                $.get("index.php?action=skip_propagate_script_instance_deployment_query&propagate_script_instance_deployment_id="+propagate_script_instance_deployment_id, function(data) {
                    if (data == 'no_credentials') {
                        return prompt_for_credentials();
                    }
                    update_deployment_status(true);
                });
            }
        });
		return false;
	});

	$("*[data-restart-deployment]").live("click", function() {
		var propagate_script_instance_deployment_id = $(this).attr('data-propagate-script-instance-deployment-id');
        var message = 'Are you sure you want to completely restart deployment on '+$(this).attr("data-deployment-host")+'?'
            + '<br/>The script will be executed again in full.<br/>Current status is "'+$(this).attr("data-dba-deployment-status")+'"';
        bootbox.confirm(message, function(confirm_result) {
            if(confirm_result) {
                $.get("index.php?action=mark_propagate_script_instance_deployment&status=not_started&propagate_script_instance_deployment_id="+propagate_script_instance_deployment_id, function() {
                    $.get("index.php?action=execute_propagate_script_instance_deployment&force_manual=true&message="+encodeURIComponent("Restarted manually")+"&restart_script=true&propagate_script_instance_deployment_id="+propagate_script_instance_deployment_id, function(data) {
                        if (data == 'no_credentials') {
                            return prompt_for_credentials();
                        }
                    });
                    update_deployment_status(true);
                });
            }
        });
		return false;
	});

	$("*[data-mark-as-deployed-manually]").live("click", function() {
		var propagate_script_instance_deployment_id = $(this).attr('data-propagate-script-instance-deployment-id');
        var message = 'Mark deployment on '+$(this).attr("data-deployment-host")+' as "deployed manually"?<br/>Current status is "'+$(this).attr("data-dba-deployment-status")+'"';

        bootbox.confirm(message, function(confirm_result) {
            if(confirm_result) {
                $.get("index.php?action=mark_propagate_script_instance_deployment&status=deployed_manually&message="+encodeURIComponent("Deployed manually")+"&propagate_script_instance_deployment_id="+propagate_script_instance_deployment_id, function(data) {
                    if (data == 'no_credentials') {
                        return prompt_for_credentials();
                    }
                    update_deployment_status(true);
                });
            }
        });
		return false;
	});
		
	setInterval("update_deployment_status()", 2000);	

	$('#approve_script_form').submit(function() {
		return apply_approve_script_form_buttons_enabled();
	});

	update_submit_status();
	apply_approve_script_form_buttons_enabled();
	request_execute_propagate_script_instance_deployments();
	setInterval("request_execute_propagate_script_instance_deployments()", 2000);

	$('[data-type="mark_comment"]').click(function() {
		$("#mark_comment").val($(this).attr("data-value"));
		var button_html = "Mark as";
		if ($(this).attr("data-value")) {
			button_html = $(this).html();
		}
		button_html += ' <span class="caret"></span>';
		$("#mark_comment_button").html(button_html);				
	});

    $("#propagate_script_queries").hide();
    $("#toggle_propagate_script_queries").click(function(){
        $("#propagate_script_queries").slideToggle();
        return false;
    });
    $("a.current_propagate_script_query").live("click", function(e){
        var is_first_time = ($(this).attr("data-content") == "");
        var query_id = $(this).attr("data-propagate-script-query-id");
        $(this).attr("data-content", $("pre[data-propagate-script-query="+query_id+"]").attr("data-script-query")).popover({trigger: 'click'});
        if (is_first_time) {
            $(this).popover('show');
        }
        e.preventDefault();
        return false;
    });
	$("a[data-live-popover]").live("click", function(e) {
        var is_first_time = ($(this).attr("data-popover-initialized") != "true");
        $(this).attr("data-popover-initialized", "true");
        if (is_first_time) {
            $(this).popover('show');
        }
        e.preventDefault();
        return false;
	});
});

function review_approved_manual_deployments() {
	var approved_manual_deployments = new Array();
	$("*[data-deployment-status='not_started']").each(function(index) {
		if ($(this).attr("data-deployment-type") == "manual") {
			approved_manual_deployments.push($(this).attr('data-propagate-script-instance-deployment-id'));
		}
	});
	return approved_manual_deployments;
}


function request_execute_propagate_script_instance_deployments() {

	function execute_propagate_script_instance_deployment(propagate_script_instance_deployment_id) {
		$.get("index.php?action=execute_propagate_script_instance_deployment&propagate_script_instance_deployment_id="+propagate_script_instance_deployment_id, function(data) {
			if (data == 'no_credentials') {
				return prompt_for_credentials();
			}
		});
	}
	<?php  if ($is_owner) { ?>
	$("*[data-deployment-status='not_started']").each(function(index) {
		if ($(this).attr("data-deployment-type") == "automatic") {
			execute_propagate_script_instance_deployment($(this).attr('data-propagate-script-instance-deployment-id'));
		}
	});
	$("*[data-deployment-status='awaiting_guinea_pig']").each(function(index) {
		execute_propagate_script_instance_deployment($(this).attr('data-propagate-script-instance-deployment-id'));
	});
	<?php } ?>
}

function apply_approve_script_form_buttons_enabled() {
	var enable_form_buttons = false;
	$("input[name='instance[]']").each(function(index) {
    	if ($(this).is(":checked") && !$(this).is(":disabled")) {
    		enable_form_buttons = true;
    	}
	});
	if (enable_form_buttons) {
		$('#approve_script_form_approve_button').removeAttr('disabled');
		$('#approve_script_form_disapprove_button').removeAttr('disabled');
	}
	else {
		$('#approve_script_form_approve_button').attr('disabled','disabled');
		$('#approve_script_form_disapprove_button').attr('disabled','disabled');
	}

	var approved_manual_deployments = review_approved_manual_deployments();
	if (approved_manual_deployments.length > 0) {
		$('#approve_script_form_run_manual_button').show();
	} else {
		$('#approve_script_form_run_manual_button').hide();
	}
	
	return enable_form_buttons;
}

function update_submit_status() {
    return apply_approve_script_form_buttons_enabled();
}

function update_deployment_status(need_update) {
	if(typeof(need_update)==='undefined') need_update = false;
	if (need_update) {
		need_update_countdown = 5;
	}

    var abort_auto_update = false;
	$("input[name='instance[]']").each(function(index) {
    	if ($(this).is(":checked") && !$(this).is(":disabled")) {
        	// Someone is trying to set a checkbox; don't update because that will
        	// overwrite the entire table, including the checkbox inside.
    		abort_auto_update = true;
    	}
	});
	if (abort_auto_update && !need_update) {
	    return false;
	}
	$("*[data-deployment-status='deploying']").each(function(index) {
		need_update = true;
	});
	$("*[data-deployment-status='awaiting_guinea_pig']").each(function(index) {
		// Update a "waiting on guinea pig" if there are compelted guinea pigs.
		$("*[data-deployment-status='passed'], *[data-deployment-status='deployed_manually']").each(function(index) {
			if ($(this).attr("data-is-guinea-pig") == "1") {
				need_update = true;
			}
		});
	});
	$("*[data-deployment-status='not_started']").each(function(index) {
		if ($(this).attr("data-deployment-type") == "automatic") {
			need_update = true;
		}
	});
	if (need_update || (need_update_countdown > 0)) {
		need_update_countdown = need_update_countdown - 1;
		$.get("index.php?action=view_script_instance_deployments&propagate_script_id=<?php echo $script["propagate_script_id"]; ?>", function(data) {
		    var data_checksum = $("[data-propagate-script-instance-deployments-checksum]", data).attr("data-propagate-script-instance-deployments-checksum");
		    var current_checksum = $("[data-propagate-script-instance-deployments-checksum]").attr("data-propagate-script-instance-deployments-checksum");
			if (data_checksum != current_checksum) {
			    $("#propagate_script_instance_deployments").html(data);
			}
			update_submit_status();
		});
	}
	return need_update;
}
</script>
