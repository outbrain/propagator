<?php
require "Loader.php";
require "PropagatorModel.php";
require "Event.php";
require "EventManager.php";

/**
 * class Propagator
 *
 * This is the controller class for the Outbrain Propagator web application.
 *
 * Public method represent controller actions, callable through the index.php
 *
 * @author Shlomi Noach <snoach@outbrain.com>
 * @license Apache 2.0 license.  See LICENSE document for more info
 * @created 2013-10-25
 *
 */
class Propagator {

    private $conf;
    private $data_model;
    private $event_manager;
    private $header_printed = false;

    /**
     * Constructor.  Pass in the global configuration object
     *
     * @param type $conf
     */
    function __construct($conf)
    {
    	$this->load = new Loader();
        if (empty($conf))
        {
            return;
        }

        $this->conf = $conf;
        $this->data_model = new PropagatorModel($conf);
        $this->event_manager = new EventManager($conf);
        
        session_start();
    }

    
    private function get_credentials() {
    	return new Credentials($_SESSION['propagator_mysql_user'], $_SESSION['propagator_mysql_password']);
    }
    
    private function redirect($page, $params = "") {
    	$redirect_url = site_url() . "?action=" . $page;
    	if (!empty($params))
    		$redirect_url = $redirect_url."&".$params;
    	header("Location: " . $redirect_url);
    	return 0;
    }

    public function index()
    {
		$action = $this->data_model->get_default_action();
        return $this->redirect("{$action}");
    }
    
    public function noconfig()
    {
        $this->header();
        $this->load->view("noconfig");
        $this->footer();
    }

    /**
     * display a message in a formatted div element
     *
     * @param string $string    The message to display
     * @param string $level     The div class to use (default alert-warning)
     */
    private function alert($string, $level = 'alert-warning') {
    	$this->header();
    	print "<div class=\"alert {$level}\">{$string}</div>";
    }
    
    public function splash()
    {
    	$this->header();
    	$this->load->view("about", $data);
    	$this->footer();
    }
    

    public function about()
    {
       	$this->header();
    	$this->load->view("about", array());
    	$this->footer();
    }


    public function manual()
    {
       	$this->header();
    	$this->load->view("manual", array());
    	$this->footer();
    }


    public function input_credentials()
    {
    	$this->header();
    
    	// display the page
    	$this->load->view("input_credentials", array());
    	$this->footer();
    }
    
    
    public function no_credentials()
    {
    	$this->load->view("no_credentials", array());
    }


    public function verify_mysql_credentials()
    {
    	$data['propagator_mysql_user'] = get_var('username');
    	$data['propagator_mysql_password'] = get_var('password');
    
    	try {
    	    if ($this->conf['restrict_credentials_input_to_dbas'] && !$this->user_is_dba()) {
    			throw new Exception("Unauthorized: only DBAs can input credentials");
    		}
    		$this->data_model->verify_mysql_credentials($data['propagator_mysql_user'], $data['propagator_mysql_password']);
    		print '{"success" : true}';
    	}
    	catch(Exception $e) {
    		print '{"success" : false, "error" : '. json_encode($e->getMessage()).'}';
    	}
    }
    

    public function has_mysql_credentials_request()
    {
    	print '{"has_credentials" : '.($this->has_credentials() ? 1 : 0).'}';
    }
    
    
    public function set_credentials()
    {
        if ($this->conf['restrict_credentials_input_to_dbas'] && !$this->user_is_dba()) {
    		throw new Exception("Unauthorized: only DBAs can input credentials");
    	}
    	$data['propagator_mysql_user'] = get_var('propagator_mysql_user');
    	$data['propagator_mysql_password'] = get_var('propagator_mysql_password');
    	$data['ajax'] = get_var('ajax');
    	 
    	$this->store_credentials($data['propagator_mysql_user'], $data['propagator_mysql_password']);
    
    	if ($data['ajax']) {
    		print '{"success" : true}';
    	} else {
	    	return $this->redirect("input_script");
    	}
    }
    
    public function clear_credentials_request()
    {
    	$this->clear_credentials();
    	return $this->redirect("");
    }
    

    public function input_script()
    {
    	$data['deployment_environments'] = get_var('deployment_environment');
    	$data['database_role_id'] = get_var('database_role_id');
		$data['script_default_schema'] = get_var('script_default_schema');
       	$data['script_sql_code'] = get_var('script_sql_code');
    	$data['script_description'] = get_var('script_description');
    	
    	$data['database_roles'] = $this->data_model->get_database_roles();
    	$data['known_schemas'] = $this->data_model->get_known_schemas();
    	$data['has_credentials'] = $this->has_credentials();

    	$data["last_script"] = $this->data_model->get_last_propagate_script_for_submitter($this->get_auth_user());
    	// At this point we wish to set some reasonable default for database-role and schema-name.
    	// We begin with checking whether the current user has any sort of deployment history
    	if (empty($data["last_script"])) {
    		// no deployments for this user. Pick defaults via hints in dataset.
    		if (empty($data['database_role_id'])) {
	    		foreach($data['database_roles'] as $database_role) {
	    			if ($database_role["is_default"]) {
	    				$data['database_role_id'] = $database_role['database_role_id'];
	    				break;
	    			}
	    		} 
    		}
    		if (empty($data['script_default_schema'])) {
	    		foreach($data['known_schemas'] as $known_schema) {
	    			if ($known_schema["is_default"]) {
	    				$data['script_default_schema'] = $known_schema['schema_name'];
	    				break;
	    			}
	    		} 
    		}
    	} 
    	else {
    		// History found.
    		// Use last used role+schema by current user, on the assumption
    		// they will use same details...
    		if (empty($data['database_role_id']))
    			$data['database_role_id'] = $data["last_script"]['database_role_id'];
    		if (empty($data['script_default_schema']))
    			$data['script_default_schema'] = $data["last_script"]['default_schema'];
    	}
    	 
    	$this->header();
    
    	// display the page
    	$this->load->view("input_script", $data);
    	$this->footer();
    }


    public function submit_script() {
    	$data['deployment_environments'] = get_var('deployment_environment');
    	$data['database_role_id'] = get_var('database_role_id');
    	$data['script_default_schema'] = get_var('script_default_schema');
    	$data['script_sql_code'] = get_var('script_sql_code');
    	$data['script_description'] = get_var('script_description');
    
    	try
    	{
    		$propagate_script_id = $this->data_model->submit_script_for_propagation($data['script_sql_code'], $data['script_description'], $data['database_role_id'], $data['deployment_environments'], $data['script_default_schema'], $this->get_auth_user(), $this->get_credentials());
    		$data["propagate_script_id"] = $propagate_script_id;
    
    		$this->notify_listeners("new_script", array(
		        "script_id"   => $propagate_script_id,
		        'schema'      => $data['script_default_schema'],
		        'description' => $data['script_description'],
		        'role'        => $script['database_role_id'],
		        'user'        => $this->get_auth_user(),
    		));
    		
    		return $this->redirect("approve_script", "propagate_script_id=" . $propagate_script_id);
    	}
    	catch (Exception $e)
    	{
    		$data['database_roles'] = $this->data_model->get_database_roles();
    		$data['known_schemas'] = $this->data_model->get_known_schemas();
    		$data['has_credentials'] = $this->has_credentials();
    		$this->alert($e->getMessage(), 'alert-error');
    		$this->load->view("input_script", $data);
    		$this->footer();
    		return;
    	}
    }
    
    public function check_for_existing_script() {
    	$data['script_sql_code'] = get_var('script_sql_code');
    
  		$existing_scripts = $this->data_model->get_propagate_script_by_code($data['script_sql_code'], true);
  		$sample_propagate_script_id = 0;
  		if (count($existing_scripts)) {
  		    $sample_propagate_script_id = $existing_scripts[0]["propagate_script_id"];
  		}

  		$same_script_exists = count($existing_scripts);
  		print '{"script_exists" : '.$same_script_exists.', "propagate_script_id" : '.$sample_propagate_script_id.'}';
    }
    
    
    public function redeploy_script() {
    	$submitter = ($this->user_is_dba() ? '' : $this->get_auth_user());
    	$propagate_script_id = get_var('propagate_script_id');
    	$deployment_environments = get_var('deployment_environment');
    	$script = $this->data_model->get_propagate_script($propagate_script_id, $submitter);
    	$role = $this->data_model->get_database_role($script['database_role_id']);
    	try
    	{
    		$this->data_model->submit_script_for_propagation_on_environments($propagate_script_id, $role, $deployment_environments, $script["default_schema"], $submitter, $this->get_credentials(), true);
    		
    		$this->notify_listeners ("redeploy_script", array(
    		        'script_id'   => $propagate_script_id,
    		        'schema'      => $script['default_schema'],
    		        'description' => $script['description'],
    		        'role'        => $script['database_role_id'],
    		        'user'        => $this->get_auth_user(),
    		));
    		
    		return $this->redirect("view_script", "propagate_script_id=" . $propagate_script_id . "#instance_deployments");
    	}
    	catch (Exception $e)
    	{
    	    return $this->redirect("view_script", "propagate_script_id=" . $propagate_script_id . "&error_message=".$e->getMessage());
       	}
    }
    
    
    public function approve_script() {
    	$propagate_script_id = get_var('propagate_script_id');
    	$submitter = ($this->user_is_dba() ? '' : $this->get_auth_user());
    	$data["script"] = $this->data_model->get_propagate_script($propagate_script_id, $submitter);
    	$data["propagate_script_queries"] = $this->data_model->get_propagate_script_query($propagate_script_id, $submitter);
    	$data["propagate_script_deployments"] = $this->data_model->get_propagate_script_deployments($propagate_script_id, $submitter);
    	$data["propagate_script_instance_deployments"] = $this->data_model->get_propagate_script_instance_deployment($propagate_script_id, $submitter);
    
		$data['auth_user'] = $this->get_auth_user();
    	$data['is_dba'] = $this->user_is_dba();
		$data["approve_script_mode"] = true;
    	$data['has_credentials'] = $this->has_credentials();
    	$data['is_owner'] = (($this->get_auth_user() == $data["script"]["submitted_by"]) ? 1 : 0);

    	$this->header();
    
    	$this->load->view("view_script", $data);
    	$this->footer();
    }
    

    public function submit_approve_script() {
    	if (!$this->has_credentials()) {
    		return $this->redirect("input_credentials");
    	}
    	$propagate_script_id = get_var('propagate_script_id');
    	$deploy_action = get_var('deploy_action');
    	$submitter = ($this->user_is_dba() ? '' : $this->get_auth_user());
    	
    	$instances = get_var("instance");
    	
    	if ($deploy_action == 'approve') {
    	    $event_name = "approve_script";
    	} else {
    	    $event_name = "disapprove_script";
    	}
    	
   		try
   		{
   		    
   		    $this->data_model->approve_propagate_script($propagate_script_id, $submitter, $instances, ($deploy_action == 'approve'));
   		    
   		    $this->notify_listeners ($event_name, array(
	            'script_id' => $propagate_script_id,
	            'user'      => $this->get_auth_user(),
   		    ));
   		    
    		return $this->redirect("view_script", "propagate_script_id=".$propagate_script_id . "#instance_deployments");
   		}
   		catch (Exception $e)
   		{
   		    $data['database_roles'] = $this->data_model->get_database_roles();
   			$this->alert($e->getMessage(), 'alert-error');
   			prettyprint($data['script_sql_code']);
   			$this->load->view("input_script", $data);
   			$this->footer();
   			return;
   		}    		
    }

    public function execute_propagate_script_instance_deployment() {
    	if (!$this->has_credentials()) {
    		return $this->redirect("no_credentials");
    	}
    	$submitter = ($this->user_is_dba() ? '' : $this->get_auth_user());
    	$propagate_script_instance_deployment_id = get_var('propagate_script_instance_deployment_id');
     	$force_manual = (get_var('force_manual') == 'true'? true : false);
     	$restart_script = (get_var('restart_script') == 'true'? true : false);
     	$run_single_query = (get_var('run_single_query') == 'true'? true : false);

    	try {
    		$this->data_model->execute_propagate_script_instance_deployment($propagate_script_instance_deployment_id, $force_manual, $restart_script, $run_single_query, $submitter, $this->get_credentials());
    		
    		$this->notify_listeners ("execute_script", array(
		        'propagate_script_instance_deployment_id' => $propagate_script_instance_deployment_id,
		        'user'                                    => $this->get_auth_user(),
    		));
    		
    		print '{"success" : true}';
    	}
    	catch(Exception $e) {
    	    print '{"success" : false, "error" : "'. addslashes($e->getMessage()).'"}';
    	}
    }

    public function mark_propagate_script_instance_deployment() {
    	if (!$this->has_credentials()) {
    		return $this->redirect("no_credentials");
    	}
    	$submitter = ($this->user_is_dba() ? '' : $this->get_auth_user());

       	$propagate_script_instance_deployment_id = get_var('propagate_script_instance_deployment_id');
    	$status = get_var('status');
    	$message = get_var('message');
    	try {
    		$this->data_model->update_propagate_script_instance_deployment_status($propagate_script_instance_deployment_id, $status, $message, $submitter);
    	    
    		$this->notify_listeners ("mark_script", array(
		        'propagate_script_instance_deployment_id' => $propagate_script_instance_deployment_id,
		        'user'                                    => $this->get_auth_user(),
		        'marked_status'                           => $status,
    		));
    		
    		print '{"success" : true}';
    	}
    	catch(Exception $e) {
    	    print '{"success" : false, "error" : "'. addslashes($e->getMessage()).'"}';
    	}
    }

    public function skip_propagate_script_instance_deployment_query() {
    	if (!$this->has_credentials()) {
    		return $this->redirect("no_credentials");
    	}
    	$submitter = ($this->user_is_dba() ? '' : $this->get_auth_user());

       	$propagate_script_instance_deployment_id = get_var('propagate_script_instance_deployment_id');
    	try {
    		$this->data_model->skip_propagate_script_instance_deployment_query($propagate_script_instance_deployment_id, $submitter);
    	    
    		$this->notify_listeners ("skip_script", array(
		        'propagate_script_instance_deployment_id' => $propagate_script_instance_deployment_id,
		        'user'                                    => $this->get_auth_user(),
    		));
    		
    		print '{"success" : true}';
    	}
    	catch(Exception $e) {
    	    print '{"success" : false, "error" : "'. addslashes($e->getMessage()).'"}';
    	}
    }

    public function get_propagate_script_instance_deployment_status() {
    	if (!$this->has_credentials()) {
    		return $this->redirect("input_credentials");
    	}
    	$submitter = ($this->user_is_dba() ? '' : $this->get_auth_user());
    	$propagate_script_instance_deployment_id = get_var('propagate_script_instance_deployment_id');
    	print $this->data_model->get_propagate_script_instance_deployment_status($propagate_script_instance_deployment_id, $submitter);
    }

    public function view_script() {
    	$propagate_script_id = get_var('propagate_script_id');
    	$submitter = (($this->user_is_dba() || $this->conf['history_visible_to_all']) ? '' : $this->get_auth_user());

    	$data["script"] = $this->data_model->get_propagate_script($propagate_script_id, $submitter);
    	$data["propagate_script_queries"] = $this->data_model->get_propagate_script_query($propagate_script_id, $submitter);
    	$data["propagate_script_deployments"] = $this->data_model->get_propagate_script_deployments($propagate_script_id, $submitter);
    	$data["propagate_script_instance_deployments"] = $this->data_model->get_propagate_script_instance_deployment($propagate_script_id, $submitter);
    	$data["propagate_script_instance_deployments_checksum"] = md5(serialize($data["propagate_script_instance_deployments"]));
    	$data["propagate_script_comments"] = $this->data_model->get_propagate_script_comments($propagate_script_id, $submitter);
    	 
    	$data['deployment_actions_available'] = true;
    	$data['auth_user'] = $this->get_auth_user();
    	$data['is_dba'] = $this->user_is_dba();
    	$data["approve_script_mode"] = false;
    	$data['has_credentials'] = $this->has_credentials();
    	$data['is_owner'] = (($this->get_auth_user() == $data["script"]["submitted_by"]) ? 1 : 0);
    	 
    	$this->header();
    
    	$this->load->view("view_script", $data);
    	$this->footer();
    }
    

    public function view_script_instance_deployments() {
    	$propagate_script_id = get_var('propagate_script_id');
    	$submitter = ($this->user_is_dba() ? '' : $this->get_auth_user());
    	$data["propagate_script_instance_deployments"] = $this->data_model->get_propagate_script_instance_deployment($propagate_script_id, $submitter);
    	$data["propagate_script_instance_deployments_checksum"] = md5(serialize($data["propagate_script_instance_deployments"]));
    
    	$data['deployment_actions_available'] = true;
    	$data['auth_user'] = $this->get_auth_user();
    	$data['is_dba'] = $this->user_is_dba();
    	$data["approve_script_mode"] = false;
    
    	$this->load->view("view_script_instance_deployments", $data);
    }
    

    public function comment_script() {
    	$propagate_script_id = get_var('propagate_script_id');
    	$script_comment = get_var('script_comment');
    	$comment_mark = get_var('mark_comment');
    	$submitter = ($this->user_is_dba() ? '' : $this->get_auth_user());
    	 
       	try
    	{
    		$this->data_model->comment_script($propagate_script_id, $script_comment, $comment_mark, $this->get_auth_user(), $submitter);
    		
    		$this->notify_listeners ("comment_script", array(
    		        'script_id'    => $propagate_script_id,
    		        'user'         => $this->get_auth_user(),
    		        'comment'      => $script_comment,
    		        'comment_mark' => $comment_mark,
    		));
    		
    		return $this->redirect("view_script", "propagate_script_id=" . $propagate_script_id);
    	}
    	catch (Exception $e)
    	{
    	    return $this->redirect("view_script", "propagate_script_id=" . $propagate_script_id . "&error_message=".$e->getMessage());
    	}
    }
    

    public function propagate_script_history() {
       	$submitter = get_var('submitter');
        if ($submitter == ":me:") {
    		$submitter = $this->get_auth_user();
    	}
       	if (!$this->user_is_dba() && !$this->conf['history_visible_to_all']) {
    		$submitter = $this->get_auth_user();
    	}
    	$script_fragment = get_var('script_fragment');
    	$database_role_id = get_var('database_role_id');
    	$default_schema = get_var('default_schema');
    	$filter = get_var('filter');
    	$page = (int)get_var('page');
    	if (empty($page)) {
    		$page = 0;
    	}
    	if ($page < 0) {
    		$page = 0;
    	}
    	 
    	$data["propagate_scripts"] = $this->data_model->get_propagate_script_history($page, $submitter, $script_fragment, $database_role_id, $default_schema, $filter);
    	$data["submitter"] = $submitter;
    	$data["script_fragment"] = $script_fragment;
    	$data["database_role_id"] = $database_role_id;
    	$data["default_schema"] = $default_schema;
    	$data["filter"] = $filter;
    	$data["page"] = $page;
    	$data["has_previous_page"] = ($page > 0);
    	$data["has_next_page"] = (count($data["propagate_scripts"]) >= $this->data_model->pagination_page_size);
    	 
    	$this->header();    
    	$this->load->view("list_scripts", $data);
    	$this->footer();
    }


    public function database_role() {
    	$data = $this->header();
    
    	$data['database_role_id'] = get_var('database_role_id');

    	$data['database_role'] = $this->data_model->get_database_role($data['database_role_id']);
    	$data['instances'] = $this->data_model->get_instances_by_role($data['database_role_id']);
    	$data['instances_compact'] = implode("\n", array_map(function($instance) { return $instance['host'].":".$instance['port']; }, $data['instances']));
    	$data['assigned_instance_ids'] = array_map(function($instance) { return $instance['database_instance_id']; }, $data['instances']);

    	$data['query_mappings'] = safe_presentation_query_mappings($this->data_model->get_database_role_query_mapping($data['database_role_id']));

    	$this->load->view("database_role", $data);
    	$this->footer();
    }


    public function database_roles() {
    	$this->header();
    
    	$data['database_roles'] = $this->data_model->get_database_roles();
    
    	$this->load->view("database_roles", $data);
    	$this->footer();
    }


    public function duplicate_database_role() {
    	$data['database_role_id'] = get_var('database_role_id');
    	$data['new_database_role_id'] = get_var('new_database_role_id');
    	$data['new_database_role_description'] = get_var('new_database_role_description');

   		try
   		{
            if (!$this->user_is_dba()) {
                throw new Exception("Unauthorized");
            }
    		$this->data_model->duplicate_database_role($data['database_role_id'], $data['new_database_role_id'], $data['new_database_role_description']);
            return $this->redirect("database_role", "database_role_id=" . $data['new_database_role_id']);
   		}
   		catch (Exception $e)
   		{
            return $this->redirect("database_role", "database_role_id=" . $data['database_role_id'] . "&error_message=".$e->getMessage());
   		}
    }


    public function rewire_database_role() {
    	$data['database_role_id'] = get_var('database_role_id');
    	$data['assigned_instance_ids'] = get_var('assigned_instance_ids');

   		try
   		{
            if (!$this->user_is_dba()) {
                throw new Exception("Unauthorized");
            }
    		$this->data_model->rewire_database_role($data['database_role_id'], $data['assigned_instance_ids']);
            return $this->redirect("database_role", "database_role_id=" . $data['database_role_id']);
   		}
   		catch (Exception $e)
   		{
            return $this->redirect("database_role", "database_role_id=" . $data['database_role_id'] . "&error_message=".$e->getMessage());
   		}
    }

    public function compare_database_role() {
    	if (!$this->user_is_dba()) {
    		throw new Exception("Unauthorized");
    	}
    	
    	$data = $this->header();
    
    	$data['database_role_id'] = get_var('database_role_id');
    
    	$data['database_role'] = $this->data_model->get_database_role($data['database_role_id']);
    	$data['instances'] = $this->data_model->get_instances_by_role($data['database_role_id']);
    	$data['instances_compact'] = implode("\n", array_map(function($instance) { return $instance['host'].":".$instance['port']; }, $data['instances']));
    	$data['assigned_instance_ids'] = array_map(function($instance) { return $instance['database_instance_id']; }, $data['instances']);
    
    	$data['query_mappings'] = safe_presentation_query_mappings($this->data_model->get_database_role_query_mapping($data['database_role_id']));
    
    	$this->load->view("compare_database_role", $data);
    	$this->footer();
    }
    
    
    public function database_instances() {
    	$this->header();

    	$data['instances'] = $this->data_model->get_all_database_instances('env');

    	$this->load->view("database_instances", $data);
    	$this->footer();
    }

    
    public function database_instance() {
    	$submitter = ($this->user_is_dba() ? '' : $this->get_auth_user());
    	
    	$data = $this->header();
    
    	$data['database_instance_id'] = get_var('database_instance_id');
    
    	$data['instance'] = $this->data_model->get_database_instance($data['database_instance_id']);
    	$data['roles'] = $this->data_model->get_database_roles_by_instance($data['database_instance_id']);
    	$data['assigned_role_ids'] = array_map(function($role) { return $role['database_role_id']; }, $data['roles']);
    	$data['schema_mappings'] = $this->data_model->get_database_instance_schema_mapping($data['database_instance_id']);
    	$data['query_mappings'] = safe_presentation_query_mappings($this->data_model->get_database_instance_query_mapping($data['database_instance_id']));
    	$data['instance_deployments_history'] = $this->data_model->get_instance_deployments_history($data['database_instance_id'], $submitter);
    	$data['pending_instance_deployments_history'] = $this->data_model->get_pending_instance_deployments_history($data['database_instance_id']);
    	$data['is_dba'] = $this->user_is_dba();
    	$data['deployment_actions_available'] = false;
    	 
    	$this->load->view("database_instance", $data);
    	$this->footer();
    }



    public function duplicate_database_instance() {
    	$data['database_instance_id'] = get_var('database_instance_id');
    	$data['new_database_instance_host'] = get_var('new_database_instance_host');
    	$data['new_database_instance_port'] = get_var('new_database_instance_port');
    	$data['new_database_instance_description'] = get_var('new_database_instance_description');

   		try
   		{
            if (!$this->user_is_dba()) {
                throw new Exception("Unauthorized");
            }
    		$new_database_instance_id = $this->data_model->duplicate_database_instance($data['database_instance_id'], $data['new_database_instance_host'], $data['new_database_instance_port'], $data['new_database_instance_description']);
            return $this->redirect("database_instance", "database_instance_id=" . $new_database_instance_id);
   		}
   		catch (Exception $e)
   		{
            return $this->redirect("database_instance", "database_instance_id=" . $data['database_instance_id'] . "&error_message=".$e->getMessage());
   		}
    }


    public function rewire_database_instance() {
    	$data['database_instance_id'] = get_var('database_instance_id');
    	$data['assigned_role_ids'] = get_var('assigned_role_ids');
    
    	try
    	{
    		if (!$this->user_is_dba()) {
    			throw new Exception("Unauthorized");
    		}
    		$this->data_model->rewire_database_instance($data['database_instance_id'], $data['assigned_role_ids']);
    		return $this->redirect("database_instance", "database_instance_id=" . $data['database_instance_id']);
    	}
    	catch (Exception $e)
    	{
    		return $this->redirect("database_instance", "database_instance_id=" . $data['database_instance_id'] . "&error_message=".$e->getMessage());
    	}
    }


    public function delete_database_instance() {
    	$data['database_instance_id'] = get_var('database_instance_id');
    
    	try
    	{
    		if (!$this->user_is_dba()) {
    			throw new Exception("Unauthorized");
    		}
    		$this->data_model->delete_database_instance($data['database_instance_id']);
    		return $this->redirect("database_instances"	);
    	}
    	catch (Exception $e)
    	{
    		return $this->redirect("database_instance", "database_instance_id=" . $data['database_instance_id'] . "&error_message=".$e->getMessage());
    	}
    }
    

    public function database_instance_topology() {
    	if (!$this->user_is_dba()) {
    		return $this->redirect("splash");
    	}
    	$submitter = ($this->user_is_dba() ? '' : $this->get_auth_user());
    	 
    	$this->header();
    
    	$data['database_instance_id'] = get_var('database_instance_id');
    	$data['has_credentials'] = $this->has_credentials();
    	 
    	$data['instance'] = $this->data_model->get_database_instance($data['database_instance_id']);
    	$exec_result = $this->data_model->get_database_instance_topology($data['instance'], $_SESSION['propagator_mysql_user'], $_SESSION['propagator_mysql_password']);
    	
    	$topology_output = array();
    	foreach($exec_result as $exec_result_row) {
    		if ($this->conf['instance_topology_pattern_colorify']) {
    			foreach($this->conf['instance_topology_pattern_colorify'] as $palette_key => $search_pattern) {
    				if(preg_match($search_pattern, $exec_result_row)) {
    					$exec_result_row = "<span class='palette-$palette_key'>".$exec_result_row."</span>";
    				}
    			}
    		}
    		$topology_output[] = $exec_result_row;
    	}
    	$data['topology'] = implode("\n", $topology_output);
    	 
    	$this->load->view("database_instance_topology", $data);
    	$this->footer();
    }
    


    public function database_instances_diff() {
    	if (!$this->user_is_dba()) {
    		return $this->redirect("splash");
    	}
    	$submitter = ($this->user_is_dba() ? '' : $this->get_auth_user());
    	 
    	$this->header();
    
    	$data['database_instance_id_src'] = get_var('database_instance_id_src');
    	$data['database_instance_id_dst'] = get_var('database_instance_id_dst');
    	$data['schema'] = get_var('schema');
    	$data['has_credentials'] = $this->has_credentials();
    	 
    	$data['database_instance_src'] = $this->data_model->get_database_instance($data['database_instance_id_src']);
    	$data['database_instance_dst'] = $this->data_model->get_database_instance($data['database_instance_id_dst']);
    	$exec_result = $this->data_model->get_database_instances_diff($data['database_instance_src'], $data['database_instance_dst'], $data['schema'], $this->get_credentials());
    	
    	$data['instances_diff'] = implode("\n", $exec_result);
    	 
    	$this->load->view("database_instances_diff", $data);
    	$this->footer();
    }
    
    

    public function mappings() {
    	$this->header();

    	$data['general_query_mappings'] = safe_presentation_query_mappings($this->data_model->get_general_query_mapping());
    	$data['database_roles_query_mappings'] = safe_presentation_query_mappings($this->data_model->get_all_database_roles_query_mapping());
    	$data['database_instances_query_mappings'] = safe_presentation_query_mappings($this->data_model->get_all_database_instance_query_mapping());
    	$data['database_instance_schema_mapping'] = $this->data_model->get_all_database_instances_schema_mapping();

    	$this->load->view("mappings", $data);
    	$this->footer();
    }


    /**
     * display the global web application footer
     */
    private function footer() {
        $this->load->view("footer");
    }

    private function store_credentials($mysql_user, $mysql_password) {
    	$_SESSION['propagator_mysql_user'] = $mysql_user;
    	$_SESSION['propagator_mysql_password'] = $mysql_password;
    }
    

    private function clear_credentials() {
    	unset($_SESSION['propagator_mysql_user']);
    	unset($_SESSION['propagator_mysql_password']);
    }
    
    private function has_credentials() {
        if (array_key_exists('propagator_mysql_user', $_SESSION) && array_key_exists('propagator_mysql_password', $_SESSION))
            return true;
        if ($this->conf['instance_credentials'])
            return true;
        return false;
    }

    /**
     * return the current username.  First from any .htaccess login if set, or
     * from the session if possible.
     */
    public function get_auth_user() {
        $auth_user = null;
        if ($this->conf['default_login']) {
            $auth_user = $this->conf['default_login'];
        }
        if (array_key_exists('PHP_AUTH_USER', $_SERVER)) {
            $auth_user = $_SERVER['PHP_AUTH_USER'];
        }
        if(in_array($auth_user, $this->conf['blocked'])) {
            $auth_user = null;
        }

        return $auth_user;
    }
    
    private function user_is_dba() {
    	return in_array($this->get_auth_user(), $this->conf['dbas']);
    }

    /**
     * display the web application header
     * @return boolean  return true if the header was actually printed
     */
    private function header() {
        if ($this->header_printed) {
            return false;
        }

        $data['database_roles'] = $this->data_model->get_database_roles();
        $data['all_database_instances'] = $this->data_model->get_all_database_instances();
        $data['auth_user'] = $this->get_auth_user();
        $data['history_visible_to_all'] = $this->conf['history_visible_to_all'];
        $data['is_dba'] = $this->user_is_dba();
        $data['has_credentials'] = $this->has_credentials();
        $data['error_message'] = get_var('error_message');
                
        if (!get_var('noheader')) {
            $this->load->view("header", $data);
            $this->load->view("navbar", $data);
        }

        $this->header_printed = true;
        return $data;
    }

    /**
     * Takes details of an event and fills in the missing details
     * then sends the event and event details off to the event manager
     * 
     * @param    string    $event_name
     * @param    array     $details
     */
    private function notify_listeners ($event_name, $details) {
        $queried_info = array();
        $script_info = array("description", "schema", "role");
        $instances_info = array("deployment_status", "marked_status", "deployment_type", "environment", "processing_start_time", "processing_end_time", "last_message");
        $additional_details = array("user", "comment", "comment_mark");
        
        // no point in doing anything if there aren't any listeners
        if ($this->event_manager->has_listeners($event_name)) {
            $event = array();
            
            // piece together information about the script
            if (!empty($details['script_id'])) {
                $event['script_id'] = $details['script_id'];
                foreach ($script_info as $si) {
                    if (!empty($details[$si])) {
                        $event[$si] = $details[$si];
                    } else {
                        $queried_info = $this->data_model->get_propagate_script_and_instance_and_deployment($details['script_id']);
                        if (!empty($queried_info[0]['propagate_script_id'])) {
                            $event['description'] = $queried_info[0]['description'];
                            $event['schema']      = $queried_info[0]['default_schema'];
                            $event['role']        = $queried_info[0]['database_role_id'];
                        }
                        break;
                    }
                }
            } elseif (!empty($details['propagate_script_instance_deployment_id'])) {
                $queried_info = $this->data_model->get_propagate_script_and_instance_and_deployment(null, $details['propagate_script_instance_deployment_id']);
                if (!empty($queried_info[0]['propagate_script_id'])) {
                    $event['script_id']   = $queried_info[0]['propagate_script_id'];
                    $event['description'] = $queried_info[0]['description'];
                    $event['schema']      = $queried_info[0]['default_schema'];
                    $event['role']        = $queried_info[0]['database_role_id'];
                }
            }
            
            // piece together information about the instances
            if (empty($details['instances'])) {
                if (empty($queried_info[0]['propagate_script_id'])) {
                    if (!empty($details['script_id'])) {
                        $queried_info = $this->data_model->get_propagate_script_and_instance_and_deployment($details['script_id']);
                    } elseif (!empty($details['propagate_script_instance_deployment_id'])) {
                        $queried_info = $this->data_model->get_propagate_script_and_instance_and_deployment(null, $details['propagate_script_instance_deployment_id']);
                    }
                }

                if (!empty($queried_info[0]['propagate_script_id'])) {
                    foreach ($queried_info as $int) {
                        $marked_status = null;
                        if (!empty($details['marked_status'])) {
                            $marked_status = $details['marked_status'];
                        }
                        $event['instances'][$int['environment']] = array(
                            "deployment_status"     => $int['deployment_status'],
                            "marked_status"         => $marked_status,
                            "deployment_type"       => $int['deployment_type'],
                            "environment"           => $int['environment'],
                            "processing_start_time" => $int['processing_start_time'],
                            "processing_end_time"   => $int['processing_end_time'],
                            "last_message"          => $int['last_message'],
                        );
                    }
                }
            } else {
                $event['instances'] = $details['instances'];
                if (!empty($details['marked_status'])) {
                    foreach ($event['instances'] as $key => $value) {
                        $event['instances'][$key]['marked_status'] = $details['marked_status'];
                    }
                }
            }
            
            // grab any additional details
            foreach ($additional_details as $ai) {
                if (!empty($details[$ai])) {
                    $event[$ai] = $details[$ai];
                }
            }
            
            // notify the listeners
            if (!empty($event)) {
                $this->event_manager->notify($event_name, new Event($event));
            }
        }
    }
}

?>
