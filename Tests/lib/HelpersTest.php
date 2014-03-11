<?php

set_include_path( get_include_path() . PATH_SEPARATOR . "./lib");
require "Helpers.php";

class HelpersTest extends PHPUnit_Framework_TestCase
{
    public function testParseScriptQueries()
    {
        $script = "select 1; select 2 ; select 'abc';";
        $queries = parse_script_queries($script);
        $this->assertEquals(3, count($queries));
 
        $this->assertEquals("select 1", $queries[0]);
        $this->assertEquals("select 2", $queries[1]);
        $this->assertEquals("select 'abc'", $queries[2]);
        
    }
    
    public function testParseScriptQueriesWithQuotedDelimiter()
    {
        $script = "select 1; select 2;select 'try;this';";
        $queries = parse_script_queries($script);
        $this->assertEquals(3, count($queries));
    }
    
    public function testParseScriptQueriesWithEmptyDelimiters()
    {
        $script = "select 1; select 2;;;;   ;; select 'try;this';";
        $queries = parse_script_queries($script);
        $this->assertEquals(3, count($queries));
        $this->assertEquals("select 'try;this'", $queries[2]);
    }
    
    public function testParseScriptQueriesWithQuotedDelimiterAndEscapedQuotes()
    {
        $script = "select 1; select 2;  select '', 'it''s', 'another \'escaped;\' text', 'try;this'; select 4";
        $queries = parse_script_queries($script);        
        $this->assertEquals(4, count($queries));
        $this->assertEquals("select '', 'it''s', 'another \'escaped;\' text', 'try;this'", $queries[2]);
    }
    
    public function testRewriteQueryFederated()
    {
    	$original_query = "create table t (id int) engine=federated default charset=utf8 connection='mysql://myuser:mypass@originalhost:3306/myschema/t'";
    	$mapping_rules = array(
    		array(
    			"mapping_type" => "federated",
    			"mapping_value" => "super:duper@localhost:3307/superschema"
    		)
    	);
    	$query = rewrite_query($original_query, $mapping_rules);
    	$this->assertEquals("create table t (id int) engine=federated default charset=utf8 connection='mysql://super:duper@localhost:3307/superschema/t'", $query);
    }
    
    public function testRewriteQueryFederated2()
    {
    	$original_query = "create table t (id int) engine=federated default charset=utf8 connection='mysql://myuser:mypass@originalhost:3306/myschema/t'";
    	$mapping_rules = array(
    		array(
    			"mapping_type" => "federated",
    			"mapping_value" => "localhost/superschema"
    		)
    	);
    	$query = rewrite_query($original_query, $mapping_rules);
    	$this->assertEquals("create table t (id int) engine=federated default charset=utf8 connection='mysql://myuser:mypass@localhost:3306/superschema/t'", $query);
    }

    public function testRewriteQueryFederated3()
    {
    	$original_query = "create table t (id int) engine=federated default charset=utf8 connection='mysql://myuser:mypass@originalhost:3306/myschema/t'";
    	$mapping_rules = array(
    			array(
    					"mapping_type" => "federated",
    					"mapping_value" => "localhost:3311"
    			)
    	);
    	$query = rewrite_query($original_query, $mapping_rules);
    	$this->assertEquals("create table t (id int) engine=federated default charset=utf8 connection='mysql://myuser:mypass@localhost:3311/myschema/t'", $query);
    }

    
    public function testRewriteQueryRegex()
    {
    	$original_query = "CREATE\n trigger t_bu on t before update";
    	$mapping_rules = array(
    		array(
    			"mapping_type" => "regex",
    			"mapping_key" => "/CREATE[\s]+TRIGGER/i",
    			"mapping_value" => "CREATE DEFINER='root'@'localhost' TRIGGER"
    		)
    	);
    	$query = rewrite_query($original_query, $mapping_rules);
    	$this->assertEquals("CREATE DEFINER='root'@'localhost' TRIGGER t_bu on t before update", $query);
    }
}
?>