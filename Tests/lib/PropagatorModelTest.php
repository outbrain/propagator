<?php

set_include_path( get_include_path() . PATH_SEPARATOR . "./lib");
require "Credentials.php";
require "PropagatorModel.php";

class PropagatorModelTest extends PHPUnit_Framework_TestCase
{
	protected static $conf;
	protected static $ut_data_schema;
	protected $data_model;
	protected $simple_script;
	protected $multi_statement_script;
	protected $wildcard_script;
	protected $prop_database;
	protected $ut_database;

    public static function setUpBeforeClass()
    {
        $conf = array();
		include "config.inc.php";
		self::$conf = $conf;
		
		$ds = self::$conf['db'];
		$database = new medoo(array(
			'database_type' => 'mysql',
			'database_name' => $ds['db'],
			'server' => $ds['host'],
			'port' => $ds['port'],
			'username' => $ds['user'],
			'password' => $ds['password']
		));
		$ut_data_schema = $ds['ut_data_schema'];
		$database->query(self::$conf["create_fixture_db"]);
		$database->query(self::$conf["populate_fixture_db"]);
		$database->query("drop database if exists $ut_data_schema; create database $ut_data_schema;");
    }
 
    public static function tearDownAfterClass()
    {
    }
    
	protected function setUp()
	{
		$this->data_model = new PropagatorModel(self::$conf);
		$this->simple_script = array(
			"script_sql_code" => "create or replace view t_v as select 19 as val",
			"script_description" => "unit testing",
			"database_role" => "olap",
			"deployment_environments" => '',
			"default_schema" => 'propagator_ut_data_schema',
			"auth_user" => "unit_test"			
		);
		$this->multi_statement_script = array(
			"script_sql_code" => "
			    drop table if exists prop_test_table;
			    create table prop_test_table (id int primary key);
			    insert into prop_test_table values (3);
			    insert into prop_test_table values (4);
			    insert into prop_test_table values (5);
			    ",
			"script_description" => "unit testing",
			"database_role" => "oltp",
			"deployment_environments" => '',
			"default_schema" => 'propagator_ut_data_schema',
			"auth_user" => "unit_test"
		);
		$this->wildcard_script = array(
			"script_sql_code" => "create or replace view t_v as select 23 as val",
			"script_description" => "unit testing",
			"database_role" => "olap",
			"deployment_environments" => '',
			"default_schema" => 'wildcard_schema',
			"auth_user" => "unit_test"
		);
		$ds = self::$conf['db'];
		$this->prop_database = new medoo(array(
			'database_type' => 'mysql',
			'database_name' => $ds['db'],
			'server' => $ds['host'],
			'port' => $ds['port'],
			'username' => $ds['user'],
			'password' => $ds['password']
		));
		$this->ut_database = new medoo(array(
            'database_type' => 'mysql',
            'database_name' => $ds['ut_data_schema'],
            'server' => $ds['host'],
            'port' => $ds['port'],
            'username' => $ds['user'],
            'password' => $ds['password']
        ));
	}

	private function get_credentials() {
	    return new Credentials(self::$conf['db']["user"], self::$conf['db']["password"]);
	}
	
    public function testConf()
    {
    	$this->assertNotEmpty(self::$conf["db"]);
    	$this->assertNotEmpty(self::$conf["create_fixture_db"]);
    }

    public function testSubmitScriptForPropagation()
    {
    	$propagate_script_id = $this->data_model->submit_script_for_propagation(
    			$this->simple_script["script_sql_code"],
    			$this->simple_script["script_description"],
    			$this->simple_script["database_role"],
    			$this->simple_script["deployment_environments"],
    			$this->simple_script["default_schema"],
    			$this->simple_script["auth_user"],
    			$this->get_credentials());
    	$this->assertGreaterThan(0, $propagate_script_id);
    	
    	$script = $this->data_model->get_propagate_script($propagate_script_id, '');
    	$this->assertEquals($this->simple_script["default_schema"], $script["default_schema"]);
    }
    
    public function testCheckForExistingScript() 
    {
    	$propagate_script_id = $this->data_model->submit_script_for_propagation(
    			$this->simple_script["script_sql_code"],
    			$this->simple_script["script_description"],
    			$this->simple_script["database_role"],
    			$this->simple_script["deployment_environments"],
    			$this->simple_script["default_schema"],
    			$this->simple_script["auth_user"],
        		$this->get_credentials());
    	$script = $this->data_model->get_propagate_script_by_code($this->simple_script["script_sql_code"]);
    	$this->assertNotEmpty($script);
    }
    
    public function testSubmitScriptForPropagationInstanceDeployments()
    {
    	$propagate_script_id = $this->data_model->submit_script_for_propagation(
    			$this->simple_script["script_sql_code"],
    			$this->simple_script["script_description"],
    			$this->simple_script["database_role"],
    			$this->simple_script["deployment_environments"],
    			$this->simple_script["default_schema"],
    			$this->simple_script["auth_user"],
                $this->get_credentials());
    	$this->assertGreaterThan(0, $propagate_script_id);
    	
    	$deployments = $this->data_model->get_propagate_script_instance_deployment($propagate_script_id, '');
    	$this->assertNotEmpty($deployments);
    }

    public function testSubmitScriptAndDeploy()
    {
    	$propagate_script_id = $this->data_model->submit_script_for_propagation(
    			$this->simple_script["script_sql_code"],
    			$this->simple_script["script_description"],
    			$this->simple_script["database_role"],
    			$this->simple_script["deployment_environments"],
    			$this->simple_script["default_schema"],
    			$this->simple_script["auth_user"],
                $this->get_credentials());
    	$this->assertGreaterThan(0, $propagate_script_id);

    	$deployments = $this->data_model->get_propagate_script_instance_deployment($propagate_script_id, '');
    	foreach ($deployments as $deployment) {
    	    if ($deployment["database_instance_id"] == 1) {
    	        $this->data_model->approve_propagate_script($propagate_script_id, "unit_test", array($deployment["database_instance_id"]), true);
            	$this->data_model->execute_propagate_script_instance_deployment($deployment["propagate_script_instance_deployment_id"], true, false, false, "unit_test", $this->get_credentials());
        	}
    	}
    	$data = $this->ut_database->query("select * from t_v")->fetchAll();
    	$this->assertEquals(count($data), 1);
    	$this->assertEquals($data[0]["val"], 19);
    }

    public function testSubmitWildcardScriptAndDeploy()
    {
    	$propagate_script_id = $this->data_model->submit_script_for_propagation(
    			$this->wildcard_script["script_sql_code"],
    			$this->wildcard_script["script_description"],
    			$this->wildcard_script["database_role"],
    			$this->wildcard_script["deployment_environments"],
    			$this->wildcard_script["default_schema"],
    			$this->wildcard_script["auth_user"],
                $this->get_credentials());
    	$this->assertGreaterThan(0, $propagate_script_id);

    	$deployments = $this->data_model->get_propagate_script_instance_deployment($propagate_script_id, '');
    	foreach ($deployments as $deployment) {
    	    if ($deployment["database_instance_id"] == 1) {
    	        $this->data_model->approve_propagate_script($propagate_script_id, "unit_test", array($deployment["database_instance_id"]), true);
            	$this->data_model->execute_propagate_script_instance_deployment($deployment["propagate_script_instance_deployment_id"], true, false, false, "unit_test", $this->get_credentials());
        	}
    	}
    	$data = $this->ut_database->query("select * from t_v")->fetchAll();
    	$this->assertEquals(count($data), 1);
    	$this->assertEquals($data[0]["val"], 23);
    }

    public function testSubmitScriptAndDeployStepByStep()
    {
    	$propagate_script_id = $this->data_model->submit_script_for_propagation(
    			$this->multi_statement_script["script_sql_code"],
    			$this->multi_statement_script["script_description"],
    			$this->multi_statement_script["database_role"],
    			$this->multi_statement_script["deployment_environments"],
    			$this->multi_statement_script["default_schema"],
    			$this->multi_statement_script["auth_user"],
                $this->get_credentials());
    	$this->assertGreaterThan(0, $propagate_script_id);

    	$deployments = $this->data_model->get_propagate_script_instance_deployment($propagate_script_id, '');
    	foreach ($deployments as $deployment) {
    	    if ($deployment["database_instance_id"] == 1) {
    	        $this->data_model->approve_propagate_script($propagate_script_id, "unit_test", array($deployment["database_instance_id"]), true);
    	        //
            	$this->data_model->execute_propagate_script_instance_deployment($deployment["propagate_script_instance_deployment_id"], true, false, true, "unit_test", $this->get_credentials());
            	$this->data_model->execute_propagate_script_instance_deployment($deployment["propagate_script_instance_deployment_id"], true, false, true, "unit_test", $this->get_credentials());
            	// table created
                $data = $this->ut_database->query("select * from prop_test_table")->fetchAll();;
                $this->assertEquals(count($data), 0);


            	$this->data_model->execute_propagate_script_instance_deployment($deployment["propagate_script_instance_deployment_id"], true, false, true, "unit_test", $this->get_credentials());
            	// row inserted
                $data = $this->ut_database->query("select * from prop_test_table")->fetchAll();;
                $this->assertEquals(count($data), 1);
            	$this->data_model->execute_propagate_script_instance_deployment($deployment["propagate_script_instance_deployment_id"], true, false, true, "unit_test", $this->get_credentials());
            	// row inserted
                $data = $this->ut_database->query("select * from prop_test_table")->fetchAll();;
                $this->assertEquals(count($data), 2);
            	$this->data_model->execute_propagate_script_instance_deployment($deployment["propagate_script_instance_deployment_id"], true, false, true, "unit_test", $this->get_credentials());
            	// row inserted
                $data = $this->ut_database->query("select * from prop_test_table")->fetchAll();;
                $this->assertEquals(count($data), 3);
        	}
    	}
    }

    public function testSubmitScriptAndDeployStepByStepWithSkip()
    {
    	$propagate_script_id = $this->data_model->submit_script_for_propagation(
    			$this->multi_statement_script["script_sql_code"],
    			$this->multi_statement_script["script_description"],
    			$this->multi_statement_script["database_role"],
    			$this->multi_statement_script["deployment_environments"],
    			$this->multi_statement_script["default_schema"],
    			$this->multi_statement_script["auth_user"],
                $this->get_credentials());
    	$this->assertGreaterThan(0, $propagate_script_id);

    	$deployments = $this->data_model->get_propagate_script_instance_deployment($propagate_script_id, '');
    	foreach ($deployments as $deployment) {
    	    if ($deployment["database_instance_id"] == 1) {
    	        $this->data_model->approve_propagate_script($propagate_script_id, "unit_test", array($deployment["database_instance_id"]), true);
    	        //
            	$this->data_model->execute_propagate_script_instance_deployment($deployment["propagate_script_instance_deployment_id"], true, false, true, "unit_test", $this->get_credentials());
            	$this->data_model->execute_propagate_script_instance_deployment($deployment["propagate_script_instance_deployment_id"], true, false, true, "unit_test", $this->get_credentials());
            	// table created
                $data = $this->ut_database->query("select * from prop_test_table")->fetchAll();;
                $this->assertEquals(count($data), 0);


            	$this->data_model->execute_propagate_script_instance_deployment($deployment["propagate_script_instance_deployment_id"], true, false, true, "unit_test", $this->get_credentials());
            	// row inserted
                $data = $this->ut_database->query("select * from prop_test_table")->fetchAll();;
                $this->assertEquals(count($data), 1);
                $this->data_model->skip_propagate_script_instance_deployment_query($deployment["propagate_script_instance_deployment_id"], "unit_test");
            	// row inserted
                $data = $this->ut_database->query("select * from prop_test_table")->fetchAll();;
                $this->assertEquals(count($data), 1);
            	$this->data_model->execute_propagate_script_instance_deployment($deployment["propagate_script_instance_deployment_id"], true, false, true, "unit_test", $this->get_credentials());
            	// row inserted
                $data = $this->ut_database->query("select * from prop_test_table")->fetchAll();;
                $this->assertEquals(count($data), 2);
                $this->assertEquals($data[0]["id"], 3);
                $this->assertEquals($data[1]["id"], 5);
        	}
    	}
    }

    public function testSubmitScriptAndDeployRequiringDbaApproval()
    {
    	$propagate_script_id = $this->data_model->submit_script_for_propagation(
    			$this->simple_script["script_sql_code"],
    			$this->simple_script["script_description"],
    			$this->simple_script["database_role"],
    			$this->simple_script["deployment_environments"],
    			$this->simple_script["default_schema"],
    			$this->simple_script["auth_user"],
    			$this->get_credentials());
    	$this->assertGreaterThan(0, $propagate_script_id);
    
        	$deployments = $this->data_model->get_propagate_script_instance_deployment($propagate_script_id, '');
    	foreach ($deployments as $deployment) {
    		if ($deployment["database_instance_id"] == 6) {
    			$this->data_model->approve_propagate_script($propagate_script_id, "unit_test", array($deployment["database_instance_id"]), true);
    			$this->data_model->execute_propagate_script_instance_deployment($deployment["propagate_script_instance_deployment_id"], true, false, false, "unit_test", $this->get_credentials());
    		}
    	}
    	$found_deployment = false;
        $deployments = $this->data_model->get_propagate_script_instance_deployment($propagate_script_id, '');
    	foreach ($deployments as $deployment) {
    		if ($deployment["database_instance_id"] == 6) {
    			$this->assertEquals($deployment["deployment_status"], "awaiting_dba_approval");
    			$found_deployment = true;
    		}
    	}
    	$this->assertTrue($found_deployment);
    }
    
    public function testDuplicateDatabaseInstance()
    {
        $database_instance_id = $this->data_model->duplicate_database_instance(2, "dup_host", 1234, "duplicate of database instance 2");

    	$orig = $this->prop_database->query("select database_role_id from database_instance_role where database_instance_id = 2 order by database_role_id")->fetchAll();;
    	$dup = $this->prop_database->query("select database_role_id from database_instance_role where database_instance_id = $database_instance_id order by database_role_id")->fetchAll();;
    	$this->assertEquals($orig, $dup);

    	$orig = $this->prop_database->query("select from_schema, to_schema from database_instance_schema_mapping where database_instance_id = 2 order by from_schema, to_schema")->fetchAll();;
    	$dup = $this->prop_database->query("select from_schema, to_schema from database_instance_schema_mapping where database_instance_id = $database_instance_id order by from_schema, to_schema")->fetchAll();;

    	$orig = $this->prop_database->query("select  mapping_type, mapping_key, mapping_value from database_instance_query_mapping where database_instance_id = 2 order by mapping_type, mapping_key, mapping_value")->fetchAll();;
    	$dup = $this->prop_database->query("select  mapping_type, mapping_key, mapping_value from database_instance_query_mapping where database_instance_id = $database_instance_id order by mapping_type, mapping_key, mapping_value")->fetchAll();;

    	$this->assertEquals($orig, $dup);
    }

    public function testDuplicateDatabaseRole()
    {
        $new_database_role_id = 'oltp-dup';
        $this->data_model->duplicate_database_role('oltp', $new_database_role_id, 'duplicate of oltp');

    	$orig = $this->prop_database->query("select database_instance_id from database_instance_role where database_role_id = 'oltp' order by database_instance_id")->fetchAll();;
    	$dup = $this->prop_database->query("select database_instance_id from database_instance_role where database_role_id = '$new_database_role_id' order by database_instance_id")->fetchAll();;
    	$this->assertEquals($orig, $dup);

    	$orig = $this->prop_database->query("select  mapping_type, mapping_key, mapping_value from database_role_query_mapping where database_role_id = 'oltp' order by mapping_type, mapping_key, mapping_value")->fetchAll();;
    	$dup = $this->prop_database->query("select  mapping_type, mapping_key, mapping_value from database_role_query_mapping where database_role_id = '$new_database_role_id' order by mapping_type, mapping_key, mapping_value")->fetchAll();;
    	$this->assertEquals($orig, $dup);
    }
}
?>