<?php

require_once 'medoo.php';
require_once 'DatabaseWrapper.php';
require_once "Credentials.php";

/**
 * class PropagatorModel
 *
 * Handles the lower level logic and database access.
 * Called by Propagator class (the controller), or can be used via command line
 *
 * @author Shlomi Noach <snoach@outbrain.com>
 * @license Apache 2.0 license.  See LICENSE document for more info
 * @created 2013-10-25
 */
class PropagatorModel {

    private $conf;
    private $instance_credentials;
    private $database;
    public $pagination_page_size;

    /**
     * Constructor.  Initialize the model object
     *
     * @param array $conf   The global config information
     */
    function __construct($conf) {
        $this->conf = $conf;
        
        $ds = $this->conf['db'];
        $this->database = new medoo(array(
			'database_type' => 'mysql',
			'database_name' => $ds['db'],
			'server' => $ds['host'],
        	'port' => $ds['port'],
			'username' => $ds['user'],
			'password' => $ds['password']
		));
        $this->pagination_page_size = 50;

        $this->instance_credentials = array();
        if(array_key_exists("instance_credentials", $this->conf) && $this->conf["instance_credentials"]) {
            $this->instance_credentials = $this->conf["instance_credentials"];
        }
    }
    
    function get_database() {
		session_write_close();
    	return $this->database;
    }
    
    function error_result($message) {
    	return array('error' => $message);
    }

    function get_conf_param($param_name, $default_value) {
    	if(array_key_exists($param_name, $this->conf) && $this->conf[$param_name]) {
    		return $this->conf[$param_name];
    	}
    	return $default_value;
    }
    
    function submit_script_for_propagation($script_sql_code, $script_description, $database_role, $deployment_environments, $default_schema, $auth_user, $credentials) {
    	$script_sql_code = trim($script_sql_code);
        if (empty($database_role)) {
    		throw new Exception("Got empty database role");
    	}
    	if (!in_array($database_role, $this->get_database_role_ids())) {
    		throw new Exception("Unknown database role: " . $database_role);
    	}
    	
    	$queries = parse_script_queries($script_sql_code);
    	if (empty($queries)) {
    		throw new Exception("No queries found in script");
    	}
    	$script_description = trim($script_description);
    	if (empty($script_description)) {
    		throw new Exception("Description is mandatory");
    	}
    	 
    	$propagate_script_id = $this->get_database()->insert("propagate_script", array(
    			"submitted_by" => $auth_user,
    			"database_role_id" => $database_role,
    			"default_schema" => $default_schema,
    			"sql_code" => $script_sql_code,
    			"description" => trim($script_description)
    	));
    	$this->get_database()->query("
            update
                propagate_script
            set
                checksum = " . $this->get_database()->quote(checksum_query($script_sql_code)) . "
            where
                propagate_script_id = " . $this->get_database()->quote($propagate_script_id) . "
        ");

    	foreach($queries as $query) {
    		$this->get_database()->insert("propagate_script_query", array(
    				"propagate_script_id" => $propagate_script_id,
    				"sql_code" => $query
    		));
    	}
    	
    	$this->submit_script_for_propagation_on_environments($propagate_script_id, $this->get_database_role($database_role), $deployment_environments, $default_schema, $auth_user, $credentials, false);
    	
    	return $propagate_script_id;
    }
    
    function submit_propagate_script_deployment($propagate_script_id, $deployment_environment, $auth_user) {
    	$propagate_script_deployment_id = $this->get_database()->insert("propagate_script_deployment", array(
    			"submitted_by" => $auth_user,
    			"propagate_script_id" => $propagate_script_id,
    			"deployment_environment" => $deployment_environment
    	));
    	return $propagate_script_deployment_id;
    }


    function submit_script_for_propagation_on_environments($propagate_script_id, $role, $deployment_environments, $default_schema, $auth_user, $credentials, $silent) {
    	if (empty($deployment_environments)) {
    		$deployment_environments = array('dev', 'test', 'production');
    	}
    	foreach($deployment_environments as $deployment_environment) {
    		$propagate_script_deployment_id = $this->submit_propagate_script_deployment(
    				$propagate_script_id, $deployment_environment, $auth_user);
    		$this->submit_script_for_propagation_on_instances($propagate_script_id, $propagate_script_deployment_id, $role, $deployment_environment, $default_schema, $auth_user, $credentials, $silent);
    	}
    }
    
    function submit_script_for_propagation_on_instances($propagate_script_id, $propagate_script_deployment_id, $role, $deployment_environment, $default_schema, $auth_user, $credentials, $silent) {
    	if (!$deployment_environment)
    		return;
    	$instances = $this->get_instances_by_role_and_env($role['database_role_id'], $deployment_environment);
    	foreach ($instances as $instance) {
    		try {
    			$this->submit_script_for_propagation_on_instance($propagate_script_id, $propagate_script_deployment_id, $role, $instance, $default_schema, $auth_user, $credentials);
    		}
    		catch (Exception $e) {
    			if (!$silent)
    				throw $e;
    		}
    	}
    
    	return $instances;
    }

    
    function approve_propagate_script($propagate_script_id, $submitter, $instances, $is_approved) {
    	if (empty($instances)) {
    		throw new Exception("No instances approved");
    	}
       	if(empty($submitter)) {
    		$submitter = '%';
    	}
    	$database = $this->get_database();
    	$instances = array_map(
    			function($e) use ($database) { return $database->quote($e); },
    			$instances
    	);

    	$this->set_current_propagate_script_query_id($propagate_script_id, $submitter, $instances, $is_approved);
    }

    function set_current_propagate_script_query_id($propagate_script_id, $submitter, $instances, $is_approved) {
    	// May only affect deployments at specific states:
    	// - An awaiting-approval state (everything goes here)
    	// - A disapproved one
    	// - A not-started & manual (so it's not going to surprise us with sudden deployment)
    	$where_clause = "
   			WHERE
    			propagate_script_id = " . $this->get_database()->quote($propagate_script_id) . "
   				AND propagate_script.submitted_by LIKE ".$this->get_database()->quote($submitter)."
   				AND
    				(
    					(deployment_status IN ('awaiting_approval', 'disapproved'))
    				OR
    					(deployment_status = 'not_started' AND deployment_type = 'manual')
    				)
    			AND database_instance_id IN (" . implode(",", $instances) . ")
    	";

    	if ($is_approved) {
    	    $query = "
                UPDATE
                    propagate_script_instance_deployment
                    JOIN propagate_script USING (propagate_script_id)
                SET
                    deployment_status = 'not_started',
                    current_propagate_script_query_id = (SELECT MIN(propagate_script_query_id) FROM propagate_script_query WHERE propagate_script_query.propagate_script_id = propagate_script.propagate_script_id),
                    last_message =
                        CASE
                            WHEN deployment_type = 'manual' THEN 'This is a manual deployment. Your manual action required to proceed; press the <b>Play</b> button'
                            ELSE ''
                        END
                $where_clause
                ";
    	}
    	else {
    	    $query = "
                UPDATE
                    propagate_script_instance_deployment
                    JOIN propagate_script USING (propagate_script_id)
                SET
                    deployment_status = 'disapproved',
                    current_propagate_script_query_id = NULL,
                    last_message = 'This instance will not be deployed'
                $where_clause
    	    ";
    	}

    	$this->get_database()->query($query);
    }
    
    function submit_script_for_propagation_on_instance($propagate_script_id, $propagate_script_deployment_id, $role, $instance, $default_schema, $auth_user, $credentials) {
	    $mapped_schemas = $this->get_computed_database_instance_mapped_schemas($role, $instance, $default_schema, $credentials);
	    foreach($mapped_schemas as $mapped_schema) {
            $this->get_database()->insert("propagate_script_instance_deployment", array(
                    "propagate_script_id" => $propagate_script_id,
                    "propagate_script_deployment_id" => $propagate_script_deployment_id,
                    "database_instance_id" => $instance["database_instance_id"],
                    "deploy_schema" => $mapped_schema,
                    "is_approved" => 0,
                    "deployment_type" => $this->get_deployment_type($instance),
                    "submitted_by" => $auth_user
            ));
	    }
    }
    
    function get_deployment_type($instance) {
    	return $this->conf['instance_type_deployment'][$instance["environment"]];
    }

    function delete_propagate_script($propagate_script_id, $submitter) {
    	$where_conditions = array(
    			"propagate_script_id" => $propagate_script_id
    	);
    	if (!empty($submitter)) {
    		$where_conditions["propagate_script.submitted_by"] = $submitter;
    	}
    	$this->get_database()->delete("propagate_script", $where_conditions);
    	$this->get_database()->delete("propagate_script_query", $where_conditions);
    	$this->get_database()->delete("propagate_script_instance_deployment", $where_conditions);
    }

    function abort_unapproved_propagate_script_instance_deployments($propagate_script_id, $submitter) {
    	$where_conditions = array( "AND" =>
    			array(
	    			"propagate_script_id" => $propagate_script_id,
    				"deployment_status" => "awaiting_approval"
    			)
    	);
    	$this->get_database()->delete("propagate_script_instance_deployment", $where_conditions);
    }

    function comment_script($propagate_script_id, $script_comment, $comment_mark, $submitter) {
    	$script_comment = trim($script_comment);
    	if (!$script_comment) {
    		throw new Exception("Comment must not be empty");
    	}
    	$this->get_database()->insert("propagate_script_comment", array(
    			"propagate_script_id" => $propagate_script_id,
    			"submitted_by" => $submitter,
    			"comment_mark" => $comment_mark,
    			"comment" => $script_comment
    	));
    }
    
    function get_database_role($database_role_id) {
    	$datas = $this->get_database()->select("database_role", "*", array("database_role_id" => $database_role_id));
    	if (empty($datas))
    		return $datas;
    	return $datas[0];
    }
    

    function get_database_instance($database_instance_id) {
    	$datas = $this->get_database()->select("database_instance", "*", array("database_instance_id" => $database_instance_id));
    	if (empty($datas))
    		return $datas;
    	return $datas[0];
    }
    
    
    function get_all_database_instances($sort_hint = '') {
    	$sort_clause = '';
    	if ($sort_hint == 'env') {
    		$sort_clause = "environment+0 DESC";
    	}
        if ($sort_hint == 'guinea') {
    		$sort_clause = "is_guinea_pig DESC";
    	}
    	if($sort_clause) {
	    	$sort_clause = $sort_clause.", ";
    	}
    	$datas = $this->get_database()->query("
    		SELECT
    			database_instance.*
    		FROM
    			database_instance
    		ORDER BY
    			$sort_clause
    			host, port
    		")->fetchAll();
    	return $datas;
    }
    
    function get_database_roles() {
    	$datas = $this->get_database()->select("database_role", "*");
    	return $datas;
    }


    function duplicate_database_role($database_role_id, $new_database_role_id, $new_database_role_description) {
        $new_database_role_id = trim($new_database_role_id);
        if (empty($new_database_role_id)) {
            throw new Exception("Duplicate database role: new role id must not be empty");
        }
      	$this->get_database()->query("
      	    insert into
      	        database_role (database_role_id, database_type, description, is_default)
      	    select
      	        " . $this->get_database()->quote($new_database_role_id) . ",
      	        database_type,
      	        " . $this->get_database()->quote($new_database_role_description) . ",
      	        0
      	    from
      	        database_role
      	    where
      	        database_role_id = " . $this->get_database()->quote($database_role_id) . "
      	    ");
      	$this->get_database()->query("
      	    insert into
      	        database_instance_role (database_instance_id, database_role_id)
      	    select
      	        database_instance_id,
      	        " . $this->get_database()->quote($new_database_role_id) . "
      	    from
      	        database_instance_role
      	    where
      	        database_role_id = " . $this->get_database()->quote($database_role_id) . "
      	    ");
      	$this->get_database()->query("
      	    insert into
      	        database_role_query_mapping (database_role_id, mapping_type, mapping_key, mapping_value)
      	    select
      	        " . $this->get_database()->quote($new_database_role_id) . ", mapping_type, mapping_key, mapping_value
      	    from
      	        database_role_query_mapping
      	    where
      	        database_role_id = " . $this->get_database()->quote($database_role_id) . "
      	    ");
    }


    function rewire_database_role($database_role_id, $assigned_instance_ids) {
        $database = $this->get_database();
        $assigned_instance_ids = array_map(
                function($e) use ($database, $database_role_id) { return "(".$database->quote($e).", ".$database->quote($database_role_id).")"; },
                $assigned_instance_ids
        );

      	$this->get_database()->query("
      	    start transaction;
      	    delete from
      	            database_instance_role
      	        where
      	            database_role_id = " . $this->get_database()->quote($database_role_id) . "
      	    ;
      	    insert into
      	            database_instance_role (database_instance_id, database_role_id)
      	        values
      	            " . implode(",", $assigned_instance_ids) . "
      	    ;
      	    commit;
   	    ");
    }


    function duplicate_database_instance($database_instance_id, $new_database_instance_host, $new_database_instance_port, $new_database_instance_description) {
        $new_database_instance_host = trim($new_database_instance_host);
        $new_database_instance_port = trim($new_database_instance_port);
        if (empty($new_database_instance_host) || empty($new_database_instance_port)) {
            throw new Exception("Duplicate database instance: new instance host & port must not be empty");
        }
        $database_instance = $this->get_database_instance($database_instance_id);
        $new_database_instance_id = $this->get_database()->insert(
            "database_instance", array(
                "host" => $new_database_instance_host,
                "port" => $new_database_instance_port,
                "environment" => $database_instance["environment"],
                "description" => $new_database_instance_description,
                "is_active" => 1,
                "is_guinea_pig" => 0
            )
        );

      	$this->get_database()->query("
      	    insert into
      	        database_instance_role (database_instance_id, database_role_id)
      	    select
      	        " . $this->get_database()->quote($new_database_instance_id) . ",
      	        database_role_id
      	    from
      	        database_instance_role
      	    where
      	        database_instance_id = " . $this->get_database()->quote($database_instance_id) . "
      	    ");
      	$this->get_database()->query("
      	    insert into
      	        database_instance_query_mapping (database_instance_id, mapping_type, mapping_key, mapping_value)
      	    select
      	        " . $this->get_database()->quote($new_database_instance_id) . ", mapping_type, mapping_key, mapping_value
      	    from
      	        database_instance_query_mapping
      	    where
      	        database_instance_id = " . $this->get_database()->quote($database_instance_id) . "
      	    ");
      	$this->get_database()->query("
      	    insert into
      	        database_instance_schema_mapping (database_instance_id, from_schema, to_schema)
      	    select
      	        " . $this->get_database()->quote($new_database_instance_id) . ", from_schema, to_schema
      	    from
      	        database_instance_schema_mapping
      	    where
      	        database_instance_id = " . $this->get_database()->quote($database_instance_id) . "
      	    ");
      	return $new_database_instance_id;
    }




    function rewire_database_instance($database_instance_id, $assigned_role_ids) {
        $database = $this->get_database();
        $assigned_role_ids = array_map(
                function($e) use ($database, $database_instance_id) { return "(".$database->quote($database_instance_id).", ".$database->quote($e).")"; },
                $assigned_role_ids
        );

      	$this->get_database()->query("
      	    start transaction;
      	    delete from
      	            database_instance_role
      	        where
      	            database_instance_id = " . $this->get_database()->quote($database_instance_id) . "
      	    ;
      	    insert into
      	            database_instance_role (database_instance_id, database_role_id)
      	        values
      	            " . implode(",", $assigned_role_ids) . "
      	    ;
      	    commit;
   	    ");
    }

    function get_database_instance_topology($database_instance, $username, $password) {
    	$host = $database_instance ['host'];
    	$port = $database_instance ['port'];
    	$pt_slave_find = $this->get_conf_param('pt-slave-find', 'pt-slave-find');
    	$process_command = $pt_slave_find." --recursion-method=processlist,hosts --report-format=hostname h=$host,P=$port,u=$username,p=$password";
    	exec($process_command, $exec_output, $exec_return_code);
    
    	$exec_output = convert_ips_to_hostnames($exec_output);
    
    	return $exec_output;
    }
    

    function get_database_instances_diff($database_instance_src, $database_instance_dst, $schema, $credentials) {
    	$host_src = $database_instance_src['host'];
    	$port_src = $database_instance_src['port'];
		$schema_src = $this->get_computed_database_instance_mapped_schemas(null, $database_instance_src, $schema, $credentials);
		$schema_src = $schema_src[0];
    	$host_dst = $database_instance_dst['host'];
    	$port_dst = $database_instance_dst['port'];
		$schema_dst = $this->get_computed_database_instance_mapped_schemas(null, $database_instance_dst, $schema, $credentials);
		$schema_dst = $schema_dst[0];
		
		$username = $credentials->get_username();
		$password = $credentials->get_password();
		
    	$mysqldiff = $this->get_conf_param('mysqldiff', 'mysqldiff');
    	$process_command = $mysqldiff." --server1=$username:$password@$host_src:$port_src --server2=$username:$password@$host_dst:$port_dst $schema_src:$schema_dst";
		error_log("host: $host_src");
		error_log("schema: $schema_src");
		error_log("processcommand: $process_command");
		
    	exec($process_command, $exec_output, $exec_return_code);
    	
    	return $exec_output;
    }
    
    function get_known_schemas() {
    	$datas = $this->get_database()->query("
    		SELECT
    			known_deploy_schema_id, schema_name, is_default,
    			count(database_instance_id) > 0 as has_mapping
    		FROM
    			known_deploy_schema
    			LEFT JOIN database_instance_schema_mapping ON (schema_name = from_schema)
    		GROUP BY
    			known_deploy_schema_id, schema_name, is_default
    		ORDER BY
    			schema_name
    		")->fetchAll();
    	return $datas;
    }
    
    function get_database_roles_by_instance($database_instance_id) {
    	$datas = $this->get_database()->query("
    		SELECT
    			database_role.*
    		FROM
    			database_role
    			JOIN database_instance_role USING (database_role_id)
   			WHERE
    			database_instance_id = " . $this->get_database()->quote($database_instance_id) . "
    		ORDER BY
    			database_type, database_role_id
    		")->fetchAll();
		return $datas;
    }
    
    function get_database_role_ids() {
    	$datas = $this->get_database_roles();
    	$datas = array_map(function($role) { return $role['database_role_id']; }, $datas); 
    	return $datas;
    }

    function get_instances_by_role($database_role) {
    	$datas = $this->get_database()->query("
    		SELECT
    			database_instance.*
    		FROM
    			database_instance
    			JOIN database_instance_role USING (database_instance_id)
   			WHERE
    			database_role_id = " . $this->get_database()->quote($database_role) . "
    		ORDER BY
    			environment+0 DESC,
    			host ASC, port ASC
    		")->fetchAll();
    	return $datas;
    }

    function get_instances_by_role_database_type($role_database_type, $sort_hint = '') {
    	$sort_clause = '';
    	if ($sort_hint == 'env') {
    		$sort_clause = "environment+0 DESC";
    	}
        if ($sort_hint == 'guinea') {
    		$sort_clause = "is_guinea_pig DESC";
    	}
    	if($sort_clause ) {
    		$sort_clause = $sort_clause.", ";
    	}
    	$datas = $this->get_database()->query("
    		SELECT
    			database_instance.*
    		FROM
    			database_instance
    			JOIN database_instance_role USING (database_instance_id)
    			JOIN database_role USING (database_role_id)
   			WHERE
    			database_role.database_type = " . $this->get_database()->quote($role_database_type) . "
    		ORDER BY
    			$sort_clause
    			host ASC, port ASC
    		")->fetchAll();
    	return $datas;
    }
    

    function get_instances_by_role_and_env($database_role, $deployment_environment) {
    	 
    	$instance_environment_types = array('invalid');
    	if ($deployment_environment == 'dev') {
    		$instance_environment_types = array('dev');
    	}
    	elseif ($deployment_environment == 'test') {
    		$instance_environment_types = array('qa');
    	}
        	elseif ($deployment_environment == 'production') {
    		$instance_environment_types = array('qa', 'build', 'production');
    	}
    	elseif ($deployment_environment == 'all') {
    		$instance_environment_types = array('dev', 'qa', 'build', 'production');
    	}
    	 
    	$database = $this->get_database();
    	$instance_environment_types = array_map(
    			function($e) use ($database) { return $database->quote($e); },
    			$instance_environment_types
    	);
    
    	$datas = $this->get_database()->query("
    		SELECT
    			database_instance.*
    		FROM
    			database_instance
    			JOIN database_instance_role USING (database_instance_id)
   			WHERE
    			database_role_id = " . $this->get_database()->quote($database_role) . "
    			AND environment IN (" . implode(",", $instance_environment_types) . ")
    		ORDER BY
    			host, port
    		")->fetchAll();
    	return $datas;
    }
 
    public function verify_mysql_credentials($user, $password) {
    	$instances = $this->get_instances_by_role_database_type('mysql', 'guinea');
    	foreach ($instances as $instance) {
    			$mysql_database = new medoo(array(
    				'database_type' => 'mysql',
    				'database_name' => 'information_schema',
    				'server' => $instance['host'],
    				'port' => $instance['port'],
    				'username' => $user,
    				'password' => $password
    		));
    		// One is enough
    		return;
    	}
    }
    
    
    function get_computed_database_instance_mapped_schemas($role, $instance, $schema_name, $credentials) {
        $mapped_schemas = array();
        $database_instance_schema_mapping = $this->get_database_instance_schema_mapping($instance["database_instance_id"]);
        foreach($database_instance_schema_mapping as $mapping) {
            if($mapping["from_schema"] == $schema_name) {
                if (strpos($mapping["to_schema"], '%') === false) {
                    // Normal mapping
                    $mapped_schemas[] = $mapping["to_schema"];
                }
                else {
                    // Wildcard mapping
                    if ($credentials->is_empty()) {
                        throw new Exception("Wildcard mapping found, but no credentials available");
                    }
                    // Connect to remote database and find databases (schemas) matching wildcard

                    //~~~
                    $database_wrapper = new DatabaseWrapper(array(
                            'database_type' => $role['database_type'],
                            'default_schema' => '',
                            'host' => $instance['host'],
                            'port' => $instance['port'],
                            'user' => $credentials->get_username(),
                            'password' => $credentials->get_password()
    	        	));
    	        	$schemas = $database_wrapper->get_schemas_like($mapping["to_schema"]);
    	        	foreach($schemas as $schema) {
    	        	    $mapped_schemas[] = $schema;
    	        	}
                }
            }
        }
        if (empty($mapped_schemas)) {
            // No mapping? Then map the schema onto itself
            $mapped_schemas[] = $schema_name;
        }
    	return $mapped_schemas;
    }


    function get_database_instance_schema_mapping($database_instance_id) {
    	$datas = $this->get_database()->query("
    		SELECT
    			database_instance_schema_mapping.*
    		FROM
    			database_instance_schema_mapping
    			JOIN database_instance USING (database_instance_id)
   			WHERE
    			database_instance_id = " . $this->get_database()->quote($database_instance_id) . "
    		ORDER BY
    			from_schema
    		")->fetchAll();
    	return $datas;
    }


    function get_all_database_instances_schema_mapping() {
    	$datas = $this->get_database()->query("
    		SELECT
    		    database_instance.*,
    			database_instance_schema_mapping.*
    		FROM
    			database_instance_schema_mapping
    			JOIN database_instance USING (database_instance_id)
    		ORDER BY
    		    database_instance.environment+0 DESC,
    			database_instance_schema_mapping.database_instance_id,
    			from_schema
    		")->fetchAll();
    	return $datas;
    }


    function get_general_query_mapping() {
    	$datas = $this->get_database()->query("
    		SELECT
    			general_query_mapping.*
    		FROM
    			general_query_mapping
    		ORDER BY
    			mapping_type
    		")->fetchAll();
    	return $datas;
    }


    function get_all_database_roles_query_mapping() {
    	$datas = $this->get_database()->query("
    		SELECT
    		    database_role.*,
    			database_role_query_mapping.*
    		FROM
    			database_role_query_mapping
    			JOIN database_role USING (database_role_id)
    		ORDER BY
    			database_role_query_mapping.database_role_id, mapping_type, database_role_query_mapping_id
    		")->fetchAll();
    	return $datas;
    }


    function get_database_role_query_mapping($database_role_id) {
    	$datas = $this->get_database()->query("
    		SELECT
    			database_role_query_mapping.*
    		FROM
    			database_role_query_mapping
    			JOIN database_role USING (database_role_id)
   			WHERE
    			database_role_id = " . $this->get_database()->quote($database_role_id) . "
    		ORDER BY
    			mapping_type
    		")->fetchAll();
    	return $datas;
    }


    function get_all_database_instance_query_mapping() {
    	$datas = $this->get_database()->query("
    		SELECT
    		    database_instance.*,
    			database_instance_query_mapping.*
    		FROM
    			database_instance_query_mapping
    			JOIN database_instance USING (database_instance_id)
    		ORDER BY
    		    database_instance.environment+0 DESC,
    			database_instance_query_mapping.database_instance_id,
    			mapping_type,
    			database_instance_query_mapping_id
    		")->fetchAll();
    	return $datas;
    }


    function get_database_instance_query_mapping($database_instance_id) {
    	$datas = $this->get_database()->query("
    		SELECT
    			database_instance_query_mapping.*
    		FROM
    			database_instance_query_mapping
    			JOIN database_instance USING (database_instance_id)
   			WHERE
    			database_instance_id = " . $this->get_database()->quote($database_instance_id) . "
    		ORDER BY
    			mapping_type
    		")->fetchAll();
    	return $datas;
    }


    function get_instance_credentials($host, $port) {
        if(empty($this->instance_credentials)) {
            return null;
        }
        $instance_credentials_key = "$host:$port";
        if ($this->instance_credentials[$instance_credentials_key]) {
            // Entries in format "host:port" => "user:pass"
            $credentials = explode(":", $this->instance_credentials[$instance_credentials_key], 2);
            return new Credentials($credentials[0], $credentials[1]);
        }
        return null;
    }

    /**
     * This is the real thing: run a given instance-deployment request. This means applying the script on the instance,
     * which in turn means executing script queries one by one.
     * The request must be in the 'not_started' state.
     * 
     * @param unknown $propagate_script_instance_deployment_id
     * @param unknown $submitter
     * @param unknown $user
     * @param unknown $password
     * @throws Exception
     */
    public function execute_propagate_script_instance_deployment($propagate_script_instance_deployment_id, $force_manual, $restart_script, $run_single_query, $submitter, $credentials) {
		if ($force_manual) {
			$submitter_mask = (empty($submitter) ? '%' : $submitter);
			$this->get_database()->query("
	    			UPDATE 
	    				propagate_script_instance_deployment 
	    			SET 
	    				manual_approved=1 
	    			WHERE 
	    				propagate_script_instance_deployment_id = " . $this->get_database()->quote($propagate_script_instance_deployment_id) . " 
	    				AND submitted_by LIKE ".$this->get_database()->quote($submitter_mask)."
	    			");
    	}
    	$datas = $this->get_database()->query("
    		SELECT
    			*
    		FROM
    			propagate_script_instance_deployment 
    			JOIN database_instance USING (database_instance_id)
   			WHERE
    			propagate_script_instance_deployment_id = " . $this->get_database()->quote($propagate_script_instance_deployment_id) . "
    			AND deployment_status IN ('not_started', 'awaiting_guinea_pig', 'paused', 'failed', 'awaiting_dba_approval')
    		")->fetchAll();
    	
    	if (empty($datas)) {
    		throw new Exception("Internal error: cannot read instance deployment info: propagate_script_instance_deployment_id=".$propagate_script_instance_deployment_id);
    	}
    	$propagate_script_instance_deployment = $datas[0];
    	if (array_key_exists('two_step_approval_environments', $this->conf) 
    		&& !empty($this->conf['two_step_approval_environments'])
    		&& in_array($propagate_script_instance_deployment["environment"], $this->conf['two_step_approval_environments'])
                    ) {
			// This deployment has to further get approval of dba.    		
    		if ($submitter != '') {
				// normal user.
    			$this->update_propagate_script_instance_deployment_status($propagate_script_instance_deployment_id, "awaiting_dba_approval", "A DBA must approve this deployment", $submitter);
    			return;
			}
    	}
    	if ($propagate_script_instance_deployment['deployment_type'] == 'manual' && !$propagate_script_instance_deployment['manual_approved']) {
    		// Do nothing: this is a manual deployment, and force_manual was not provided.
    		return;
    	}
    	 
    	// Check for guinea pigs: 
    	// - A guinea pig can start off right away
    	// - A non-guinea pig must wait on at least one guinea pig to succeed
    	// + - unless there is no guinea pig, in which case it is free to go
    	if (!$propagate_script_instance_deployment['is_guinea_pig']) {
    		$guinea_pig_deployment_status = $this->get_propagate_script_guinea_pig_deployment_status($propagate_script_instance_deployment['propagate_script_id'], $submitter);
    		if ($guinea_pig_deployment_status['count_guinea_pigs'] > 0) {
    			if ($guinea_pig_deployment_status['count_guinea_pigs'] == $guinea_pig_deployment_status['count_failed_guinea_pigs']) {
    				$this->update_propagate_script_instance_deployment_status($propagate_script_instance_deployment_id, "awaiting_approval", "All guinea pigs failed. Will not deploy", $submitter);
    				// No point in polling anymore... Wait for manual intervention.
    				return;
    			}
    			 
    			if ($guinea_pig_deployment_status['count_complete_guinea_pigs'] == 0) {
    				$this->update_propagate_script_instance_deployment_status($propagate_script_instance_deployment_id, "awaiting_guinea_pig", "No deployed guinea pigs yet. Awaiting", $submitter);
    				// Will not continue. Wait for next poll.
    				return;
       			}    			
    		}
    	}
    	 
    	// Begin with status updates
    	$this->get_database()->query("UPDATE propagate_script_instance_deployment SET processing_start_time=NOW(), processing_end_time=NULL WHERE propagate_script_instance_deployment_id = " . $this->get_database()->quote($propagate_script_instance_deployment_id));
    	$this->update_propagate_script_instance_deployment_status($propagate_script_instance_deployment_id, "deploying", "", $submitter);

    	// Grab host connection and start issuing queries
    	try {
    		$propagate_script = $this->get_propagate_script($propagate_script_instance_deployment['propagate_script_id'], $submitter);
    		$general_query_mapping = $this->get_general_query_mapping();
    		$database_role_query_mapping = $this->get_database_role_query_mapping($propagate_script["database_role_id"]);
    		$database_instance_query_mapping = $this->get_database_instance_query_mapping($propagate_script_instance_deployment["database_instance_id"]);
    		$deploy_schema = (empty($propagate_script_instance_deployment["deploy_schema"]) ? "information_schema" : $propagate_script_instance_deployment["deploy_schema"]);
    		$database_role = $this->get_database_role($propagate_script['database_role_id']);
            if($credentials->is_empty() && $this->instance_credentials) {
                $stored_credentials = $this->get_instance_credentials($propagate_script_instance_deployment['host'], $propagate_script_instance_deployment['port']);
                if ($stored_credentials) {
                    $credentials = $stored_credentials;
                }
            }

    		$database_wrapper = new DatabaseWrapper(array(
    				'database_type' => $database_role['database_type'], 
    				'default_schema' => $deploy_schema,
    				'host' => $propagate_script_instance_deployment['host'], 
    				'port' => $propagate_script_instance_deployment['port'], 
    				'user' => $credentials->get_username(),
    				'password' => $credentials->get_password()
    		));

	    	if ($restart_script) {
       	    	$start_from = 0;
	    	}
	    	else {
	    		$start_from = intval($propagate_script_instance_deployment["current_propagate_script_query_id"]);
	    	}
	    	$queries = $this->get_propagate_script_query($propagate_script_instance_deployment['propagate_script_id'], $submitter, $start_from);
	    	$query_counter = 0;
	    	foreach($queries as $query) {
	    	    $query_counter++;
	    		$this->update_propagate_script_instance_deployment_current_query_id($propagate_script_instance_deployment_id, $submitter, $query['propagate_script_query_id']);
	    		if ($run_single_query && $query_counter > 1) {
                    break;
                }
	    		$sql_code = $query['sql_code'];
	    		$sql_code = rewrite_query($sql_code, $general_query_mapping);
	    		$sql_code = rewrite_query($sql_code, $database_role_query_mapping);
	    		$sql_code = rewrite_query($sql_code, $database_instance_query_mapping);
	    		$database_wrapper->execute($sql_code);

	    	}
	    	if ($run_single_query && $query_counter > 1) {
	    	    $this->update_propagate_script_instance_deployment_status($propagate_script_instance_deployment_id, "paused", "User initiated a step-single-query", $submitter);
	    	}
	    	else {
                // Weepee!
                $this->update_propagate_script_instance_deployment_status($propagate_script_instance_deployment_id, "passed", "Script executed successfully", $submitter);
            }
    	}
	    catch (Exception $e)
	    {
	    	// Bummer
	    	$this->update_propagate_script_instance_deployment_status($propagate_script_instance_deployment_id, "failed", $e->getMessage(), $submitter);
	    }
	    $this->get_database()->query("UPDATE propagate_script_instance_deployment SET processing_end_time=NOW() WHERE propagate_script_instance_deployment_id = " . $this->get_database()->quote($propagate_script_instance_deployment_id));
	}

	public function update_propagate_script_instance_deployment_status($propagate_script_instance_deployment_id, $status, $message, $submitter) {
		if(empty($submitter)) {
			$submitter = '%';
		}
		$this->get_database()->query("
			UPDATE
				propagate_script_instance_deployment
			SET 
				deployment_status = ".$this->get_database()->quote($status).",
				last_message = ".$this->get_database()->quote($message)."
			WHERE
				propagate_script_instance_deployment_id = ".$this->get_database()->quote($propagate_script_instance_deployment_id)."
				AND submitted_by LIKE ".$this->get_database()->quote($submitter)."				
		");
		if (in_array($status , array("disapproved", "passed", "deployed_manually"))) {
		    $this->update_propagate_script_instance_deployment_current_query_id($propagate_script_instance_deployment_id, $submitter, null);
		}
		if (in_array($status , array("not_started"))) {
		    $propagate_script_instance_deployment = $this->get_propagate_script_instance_deployment_entry($propagate_script_instance_deployment_id, $submitter);
    		$this->set_current_propagate_script_query_id($propagate_script_instance_deployment["propagate_script_id"], $submitter, array($propagate_script_instance_deployment["database_instance_id"]), true);
		}
	}


	public function update_propagate_script_instance_deployment_current_query_id($propagate_script_instance_deployment_id, $submitter, $current_propagate_script_query_id) {
		if(empty($submitter)) {
			$submitter = '%';
		}
		$propagate_script_instance_deployment_id_quoted_value = (is_null($current_propagate_script_query_id) ? 'NULL' : $this->get_database()->quote($current_propagate_script_query_id));
		$this->get_database()->query("
			UPDATE
				propagate_script_instance_deployment
			SET
				current_propagate_script_query_id = $propagate_script_instance_deployment_id_quoted_value
			WHERE
				propagate_script_instance_deployment_id = ".$this->get_database()->quote($propagate_script_instance_deployment_id)."
				AND submitted_by LIKE ".$this->get_database()->quote($submitter)."
		");
	}


	public function skip_propagate_script_instance_deployment_query($propagate_script_instance_deployment_id, $submitter) {
		if(empty($submitter)) {
			$submitter = '%';
		}
		$this->get_database()->query("
			UPDATE
				propagate_script_instance_deployment
			SET
				current_propagate_script_query_id = (
				    SELECT
				        MIN(propagate_script_query_id)
				    FROM
				        propagate_script_query
				    WHERE
				        propagate_script_query.propagate_script_id = propagate_script_instance_deployment.propagate_script_id
				        AND propagate_script_query.propagate_script_query_id > current_propagate_script_query_id
				)
			WHERE
				propagate_script_instance_deployment_id = ".$this->get_database()->quote($propagate_script_instance_deployment_id)."
				AND submitted_by LIKE ".$this->get_database()->quote($submitter)."
		");
	}


	function get_propagate_script_guinea_pig_deployment_status($propagate_script_id, $submitter) {
		if(empty($submitter)) {
			$submitter = '%';
		}
		$datas = $this->get_database()->query("
    		select
    			COUNT(*) AS count_guinea_pigs,
				SUM(deployment_status = 'deploying') AS count_deploying_guinea_pigs,
				SUM(deployment_status IN ('passed', 'deployed_manually')) AS count_complete_guinea_pigs,
				SUM(deployment_status = 'failed') AS count_failed_guinea_pigs
			FROM
				propagate_script_instance_deployment
    			JOIN propagate_script USING (propagate_script_id)
				JOIN database_instance USING (database_instance_id)
    		WHERE
    			propagate_script_id = ".$this->get_database()->quote($propagate_script_id)."
    			AND	propagate_script.submitted_by LIKE ".$this->get_database()->quote($submitter)."
				AND is_guinea_pig
				AND deployment_status != 'disapprove'
				")->fetchAll();
		return $datas[0];
	}
	

	function get_propagate_script_instance_deployment_entry($propagate_script_instance_deployment_id, $submitter) {
		if(empty($submitter)) {
			$submitter = '%';
		}
		$datas = $this->get_database()->query("
    		select
    			*
    		FROM
				propagate_script_instance_deployment
    			JOIN propagate_script USING (propagate_script_id)
    		WHERE
    			propagate_script_instance_deployment_id = ".$this->get_database()->quote($propagate_script_instance_deployment_id)."
    			AND	propagate_script.submitted_by LIKE ".$this->get_database()->quote($submitter)."
    		")->fetchAll();
		if (empty($datas))
			return null;
		return $datas[0];
	}


	function get_propagate_script_instance_deployment_status($propagate_script_instance_deployment_id, $submitter) {
		if(empty($submitter)) {
			$submitter = '%';
		}
		$datas = $this->get_database()->query("
    		select
    			*,
				deployment_status IN ('not_started','failed','paused','awaiting_dba_approval') AS restartable_by_user
    		FROM
				propagate_script_instance_deployment
    			JOIN propagate_script USING (propagate_script_id)
				JOIN database_instance USING (database_instance_id)
    		WHERE
    			propagate_script_instance_deployment_id = ".$this->get_database()->quote($propagate_script_instance_deployment_id)."
    			AND	propagate_script.submitted_by LIKE ".$this->get_database()->quote($submitter)."
			ORDER BY
				database_instance.environment+0,
				host,
				port
    		")->fetchAll();
		if (empty($datas))
			return null;
		return $datas[0]['deployment_status'];
	}


	function get_propagate_script($propagate_script_id, $submitter) {
		if(empty($submitter)) {
			$submitter = '%';
		}
		$datas = $this->get_database()->query("
    		select
    			*
    		FROM
    			propagate_script
    		WHERE
    			propagate_script_id = ".$this->get_database()->quote($propagate_script_id)."
    			AND	submitted_by LIKE ".$this->get_database()->quote($submitter)."
    		")->fetchAll();
		if (empty($datas))
			return $datas;
		return $datas[0];
	}


	function get_last_propagate_script_for_submitter($submitter) {
		$datas = $this->get_database()->query("
    		select
    			*
    		FROM
    			propagate_script
    		WHERE
    			submitted_by = ".$this->get_database()->quote($submitter)."
			ORDER BY 
				propagate_script_id DESC
			LIMIT 1
    		")->fetchAll();
		if (empty($datas))
			return $datas;
		return $datas[0];
	}
	
	
	function get_propagate_script_by_code($sql_code, $relaxed_match = false) {
		$sql_code = trim($sql_code);
		if ($relaxed_match) {
		    $where_clause = "checksum = ".$this->get_database()->quote(checksum_query($sql_code));
		}
		else {
		    $where_clause = "sql_code = ".$this->get_database()->quote($sql_code);
		}
		$datas = $this->get_database()->query("
    		select
    			propagate_script_id
    		FROM
    			propagate_script
    		WHERE
    			$where_clause
    	    ORDER BY
    	        propagate_script_id DESC
    		")->fetchAll();
		return $datas;
	}
	

    function get_propagate_script_history($page, $submitter, $script_fragment, $database_role_id, $database_schema){
    	if(empty($page)) {
    		$page = 0 ;
    	}
    	$offset = $page * $this->pagination_page_size;
    	if(empty($submitter)) {
    		$submitter = '%';
    	}
    	$where_conditions = "";
        if ($script_fragment) {
    		$like_expression = "'%".trim($this->get_database()->quote($script_fragment), "'")."%'";
    		$where_conditions = $where_conditions . "
    			(
	    			propagate_script.sql_code LIKE ".$like_expression."
	    			OR propagate_script.description LIKE ".$like_expression."
	    			OR propagate_script.submitted_by LIKE ".$like_expression."
	    			OR propagate_script.database_role_id LIKE ".$like_expression."
    			)
	    		AND
    		";
    	}
        if ($database_role_id) {
    		$where_conditions = $where_conditions . "
    			(
	    			propagate_script.database_role_id = ".$this->get_database()->quote($database_role_id)."
    			)
	    		AND
			";
    	}
        if ($database_schema) {
    		$where_conditions = $where_conditions . "
    			(
	    			propagate_script.default_schema = ".$this->get_database()->quote($database_schema)."
    			)
	    		AND
			";
    	}
    	$datas = $this->get_database()->query("
    		select
    			propagate_script.*,
    			(SELECT IFNULL(GROUP_CONCAT(DISTINCT deployment_environment ORDER BY deployment_environment SEPARATOR ', '), '') FROM propagate_script_deployment WHERE (propagate_script_deployment.propagate_script_id = propagate_script.propagate_script_id)) AS deployment_environments,
    			(SELECT IFNULL(GROUP_CONCAT('\t', comment_mark, ':', comment ORDER BY propagate_script_comment_id SEPARATOR ''), '') FROM propagate_script_comment WHERE (propagate_script_comment.propagate_script_id = propagate_script.propagate_script_id)) AS script_comments,
    			SUM(deployment_status != 'disapproved') AS count_deployment_servers,
    			SUM(deployment_status IN ('passed','deployed_manually')) AS count_deployment_servers_passed,
    			SUM(deployment_status = 'failed') AS count_deployment_servers_failed
    		FROM
    			propagate_script
    			LEFT JOIN propagate_script_instance_deployment USING (propagate_script_id)
    		WHERE
    			$where_conditions
    			(
	    			propagate_script.submitted_by LIKE ".$this->get_database()->quote($submitter)."
    			)
       		GROUP BY
    			propagate_script_id
    		ORDER BY
    			propagate_script.submitted_at DESC,
    			propagate_script.propagate_script_id DESC
    		LIMIT ".$this->pagination_page_size."
    		OFFSET ".$offset."
    		")->fetchAll();
    	return $datas;
    }
    
    
    function get_propagate_script_like($script_fragment, $submitter) {
    	$like_expression = "'%".trim($this->get_database()->quote($script_fragment), "'")."%'";
    	if(empty($submitter)) {
    		$submitter = '%';
    	}
    	if($this->conf['history_visible_to_all']) {
    		$submitter = '%';
    	}
    	$datas = $this->get_database()->query("
    		select
    			propagate_script.*,
    			(SELECT IFNULL(GROUP_CONCAT(DISTINCT deployment_environment ORDER BY deployment_environment SEPARATOR ', '), '') FROM propagate_script_deployment WHERE (propagate_script_deployment.propagate_script_id = propagate_script.propagate_script_id))AS deployment_environments,
       			SUM(deployment_status != 'disapproved') AS count_deployment_servers,
    			SUM(deployment_status IN ('passed','deployed_manually')) AS count_deployment_servers_passed,
    			SUM(deployment_status = 'failed') AS count_deployment_servers_failed
    		FROM
    			propagate_script
    			LEFT JOIN propagate_script_instance_deployment USING (propagate_script_id)
    		WHERE
    			(
	    			propagate_script.sql_code LIKE ".$like_expression."
	    			OR propagate_script.description LIKE ".$like_expression."
	    			OR propagate_script.submitted_by LIKE ".$like_expression."
	    			OR propagate_script.database_role_id LIKE ".$like_expression."
    			)
    			AND
    			(
    				propagate_script.submitted_by LIKE ".$this->get_database()->quote($submitter)."
    			)
    		GROUP BY
    			propagate_script_id
    		ORDER BY
    			propagate_script.submitted_at DESC,
                propagate_script.propagate_script_id DESC
    		LIMIT 100
    		")->fetchAll();
    	return $datas;
    }
    

    function get_propagate_script_history_for_submitter($submitter) {
    	if(empty($submitter)) {
    		$submitter = '%';
    	}
    	$datas = $this->get_database()->query("
    		select
    			propagate_script.*,
    			(SELECT IFNULL(GROUP_CONCAT(DISTINCT deployment_environment ORDER BY deployment_environment SEPARATOR ', '), '') FROM propagate_script_deployment WHERE (propagate_script_deployment.propagate_script_id = propagate_script.propagate_script_id))AS deployment_environments, 
    			SUM(deployment_status != 'disapproved') AS count_deployment_servers, 
    			SUM(deployment_status IN ('passed','deployed_manually')) AS count_deployment_servers_passed, 
    			SUM(deployment_status = 'failed') AS count_deployment_servers_failed
       		FROM
    			propagate_script
    			LEFT JOIN propagate_script_instance_deployment USING (propagate_script_id)
    		WHERE
   				propagate_script.submitted_by LIKE ".$this->get_database()->quote($submitter)."
    		GROUP BY 
    			propagate_script_id
    		ORDER BY
    			propagate_script.submitted_at DESC,
                propagate_script.propagate_script_id DESC
    		LIMIT 100
    		")->fetchAll();
    	return $datas;
    }
    
    

    function get_propagate_script_query($propagate_script_id, $submitter, $start_from = 0) {
    	if(empty($submitter)) {
    		$submitter = '%';
    	}
    	$datas = $this->get_database()->query("
    		select
    			propagate_script_query.*
    		FROM
    			propagate_script
    			JOIN propagate_script_query USING(propagate_script_id)
    		WHERE
    			propagate_script_id = ".$this->get_database()->quote($propagate_script_id)."
    			AND	propagate_script.submitted_by LIKE ".$this->get_database()->quote($submitter)."
    			AND propagate_script_query_id >= ".$this->get_database()->quote($start_from)." 
    		ORDER BY
    			propagate_script_query_id
    		")->fetchAll();
    	return $datas;
    }
    

    function get_propagate_script_deployments($propagate_script_id, $submitter) {
    	if(empty($submitter)) {
    		$submitter = '%';
    	}
    	$datas = $this->get_database()->query("
    		select
    			propagate_script_deployment.*
    		FROM
    			propagate_script
    			JOIN propagate_script_deployment USING(propagate_script_id)
    		WHERE
    			propagate_script_id = ".$this->get_database()->quote($propagate_script_id)."
    			AND	propagate_script.submitted_by LIKE ".$this->get_database()->quote($submitter)."
    		ORDER BY
				propagate_script_deployment_id ASC
    		")->fetchAll();
    	return $datas;
    }


    function get_propagate_script_instance_deployment($propagate_script_id, $submitter) {
    	if(empty($submitter)) {
    		$submitter = '%';
    	}
    	$datas = $this->get_database()->query("
    		select
    			propagate_script_instance_deployment.*,
    			TIMESTAMPDIFF(SECOND, processing_start_time, processing_end_time) AS processing_duration_seconds,
    			(SELECT SUM(propagate_script_query_id < propagate_script_instance_deployment.current_propagate_script_query_id)
    			  FROM propagate_script_query
    			  WHERE propagate_script_query.propagate_script_id = propagate_script_instance_deployment.propagate_script_id
    			) AS count_executed_queries,
    			(SELECT CONCAT(SUM(propagate_script_query_id < propagate_script_instance_deployment.current_propagate_script_query_id), '/', COUNT(*))
    			  FROM propagate_script_query
    			  WHERE propagate_script_query.propagate_script_id = propagate_script_instance_deployment.propagate_script_id
    			) AS query_progress_status,
    			database_instance.*,
    			deployment_status IN ('not_started','failed','paused','awaiting_dba_approval') AS restartable_by_user	
    		FROM
    			propagate_script
    			JOIN propagate_script_instance_deployment USING(propagate_script_id)
    			JOIN database_instance USING(database_instance_id)
    		WHERE
    			propagate_script_id = ".$this->get_database()->quote($propagate_script_id)."
    			AND	propagate_script.submitted_by LIKE ".$this->get_database()->quote($submitter)."
    		ORDER BY
				database_instance.environment+0 DESC,
    			is_guinea_pig DESC,
    			deployment_type ASC,
    			host ASC,
    			port ASC,
    			propagate_script_instance_deployment_id
    		")->fetchAll();
    	foreach ($datas as &$deployment) {
    		$action_enabled = false;
    		if(in_array($deployment["deployment_status"], array('awaiting_approval', 'disapproved'))) {
    			$action_enabled = true;
    		}
    		elseif (($deployment["deployment_status"] == "not_started") && ($deployment["deployment_type"] == "manual")) {
    			$action_enabled = true;
    		}
    		$deployment["action_enabled"] = $action_enabled;
    	}
    	return $datas;
    }
    

    function get_instance_deployments_history($database_instance_id, $submitter) {
    	if(empty($submitter)) {
    		$submitter = '%';
    	}
    	$datas = $this->get_database()->query("
    		select
    			propagate_script_instance_deployment.*,
    			database_instance.*
    		FROM
    			database_instance
    			JOIN propagate_script_instance_deployment USING(database_instance_id)
    			JOIN propagate_script USING(propagate_script_id)
    		WHERE
    			database_instance_id = ".$this->get_database()->quote($database_instance_id)."
    			AND	propagate_script.submitted_by LIKE ".$this->get_database()->quote($submitter)."
    		ORDER BY
    			propagate_script_id DESC,
				is_guinea_pig DESC,
    			deployment_type ASC,
    			propagate_script_instance_deployment_id
    		LIMIT 100
    		")->fetchAll();
    	return $datas;
    }
    

    function get_pending_instance_deployments_history($database_instance_id, $submitter) {
    	if(empty($submitter)) {
    		$submitter = '%';
    	}
    	$datas = $this->get_database()->query("
    		select
    			propagate_script_instance_deployment.*,
    			database_instance.*,
    			TRUE AS action_enabled
    		FROM
    			database_instance
    			JOIN propagate_script_instance_deployment USING(database_instance_id)
    			JOIN propagate_script USING(propagate_script_id)
    		WHERE
    			database_instance_id = ".$this->get_database()->quote($database_instance_id)."
    			AND deployment_status NOT IN ('disapproved', 'passed', 'deployed_manually')
    		ORDER BY
    			propagate_script_id DESC,
				is_guinea_pig DESC,
    			deployment_type ASC,
    			propagate_script_instance_deployment_id
    		")->fetchAll();
    	return $datas;
    }
    
    
	function get_propagate_script_comments($propagate_script_id, $submitter) {
		if(empty($submitter)) {
			$submitter = '%';
		}
		$datas = $this->get_database()->query("
    		select
    			propagate_script_comment.*
    		FROM
    			propagate_script_comment
    			JOIN propagate_script USING(propagate_script_id)
    		WHERE
    			propagate_script_id = ".$this->get_database()->quote($propagate_script_id)."
    			AND	propagate_script.submitted_by LIKE ".$this->get_database()->quote($submitter)."
    		ORDER BY
    			propagate_script_comment.submitted_at DESC
    		")->fetchAll();
		return $datas;
	}
	
	
    /**
     * return the default action name, which makes for the default main page
     * @return string       the action name
     */
    public function get_default_action() {
    	return $this->conf['default_action'];
    }
    
}

?>
