<div class="navbar navbar-fixed-top">
  <div class="navbar-inner">
    <div class="container">
      <a class="brand" href="https://github.com/outbrain/propagator"><img src="img/outbrain-logo-s.png" alt="outbrain"><br/>propagator</a>
      <div class="nav-collapse">
        <ul class="nav">
           <li class="divider-vertical"></li>
          
          
			<li class="dropdown">
	        	<a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    About
					<b class="caret"></b> 
				</a>
                <ul class="dropdown-menu">
	                <li><a href="<?php echo site_url().'?action=about' ?>">About Propagator</a></li>
					<li><a href="<?php echo site_url().'?action=manual' ?>">Manual</a></li>
            	</ul>
			</li>
            <li><a href="<?php echo site_url(). "?action=input_script"; ?>">Propagate script</a></li>
            <li class="dropdown">
                <a href="#"
                      class="dropdown-toggle"
                      data-toggle="dropdown">
                        Roles
                      <b class="caret"></b>

                </a>
                <ul class="dropdown-menu">
                  <li><a href="<?php echo site_url().'?action=database_roles' ?>">All</a></li>
                  <li role="presentation" class="divider"></li>
                  <?php foreach ($database_roles as $database_role) { ?>
                    <li><a href="<?php echo site_url().'?action=database_role&database_role_id='.$database_role['database_role_id']; ?>"><?php echo '['.$database_role['database_type'].'] '.$database_role['database_role_id']; ?></a></li>
                  <?php } ?>
                </ul>
            </li>
            <li class="dropdown">
                <a href="#"
                      class="dropdown-toggle"
                      data-toggle="dropdown">
                        Instances
                      <b class="caret"></b>

                </a>
                <ul class="dropdown-menu">
                  <li><a href="<?php echo site_url().'?action=database_instances' ?>">All</a></li>
                  <li role="presentation" class="divider"></li>
                  <?php foreach ($all_database_instances as $database_instance) { ?>
                    <li><a href="<?php echo site_url().'?action=database_instance&database_instance_id='.$database_instance['database_instance_id']; ?>"><?php echo $database_instance['host'].':'.$database_instance['port']; ?></a></li>
                  <?php } ?>
                </ul>
            </li>
            <li><a href="<?php echo site_url(). "?action=mappings"; ?>">Mappings</a></li>
        	<?php if ($is_dba) { ?>
        	<!-- 
				<li class="dropdown">
		        	<a href="#" class="dropdown-toggle" data-toggle="dropdown">
	                    Sync
						<b class="caret"></b> 
					</a>
	                <ul class="dropdown-menu">
		                <li><a href="<?php echo site_url().'?action=update_instance' ?>">Update instance</a></li>
						<li><a href="<?php echo site_url().'?action=compare_instances' ?>">Compare instances</a></li>
						<li class="divider"></li>
						<?php foreach ($database_roles as $database_role) { ?>
							<?php if ($database_role['database_type'] == 'mysql') { ?>
								<li><a href="<?php echo site_url().'?action=compare_database_role&database_role_id='.$database_role['database_role_id']; ?>"><span class="glyphicon glyphicon-transfer"></span> <?php echo $database_role['database_role_id']; ?></a></li>
							<?php } ?>
						<?php } ?>
					</ul>
				</li>
				 -->
			<?php } ?>
            <?php if (!$has_credentials ) { ?>
	           	<li data-warn-missing-credentials="true"><a data-link-type="input_credentials" href="<?php echo site_url().'?action=input_credentials' ?>" title="No credentials"><img src="img/exclamation-mark.png" alt="no credentials"></a></li>
	        <?php } ?>
            <li class="dropdown">
	            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
	                Welcome, <?php echo $auth_user ?>
					<b class="caret"></b>	                      
                </a>
                <ul class="dropdown-menu">
	                <li><a href="<?php echo site_url().'?action=propagate_script_history&submitter=:me:' ?>">My history</a></li>
                    <li><a href="<?php echo site_url().'?action=propagate_script_history&submitter=:me:&filter=incomplete' ?>">My incomplete deployments</a></li>
                    <?php if ($history_visible_to_all) { ?>
	                	<li><a href="<?php echo site_url().'?action=propagate_script_history' ?>">Script history</a></li>
                	<?php } ?>
                	<li><a href="<?php echo site_url().'?action=input_credentials' ?>" data-link-type="input_credentials">Set credentials</a></li>
   					<li><a href="<?php echo site_url().'?action=clear_credentials_request' ?>">Clear credentials</a></li>
                </ul>
            </li>
          </ul>
      </div><!--/.nav-collapse -->  
        <form class="form-search form-inline pull-right" id="script_search" action="index.php" method="get">
        	<input type="hidden" name="action" value="propagate_script_history"/>
			<input type="text" class="input-medium" name="script_fragment" placeholder="Script fragment"/>
			<a class="btn" href="javascript:document.getElementById('script_search').submit()"><i class="icon-search"></i> Find script</a>
        </form>
    </div>
  </div>
</div>
