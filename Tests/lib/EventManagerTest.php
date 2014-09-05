<?php 

set_include_path( get_include_path() . PATH_SEPARATOR . "./lib");
require "EventManager.php";
require "Event.php";
require "EventListenerInterface.php";

class EventManagerTest extends PHPUnit_Framework_TestCase {
    
    protected static $conf;
    protected $event_manager;
    protected $event;
    
    public static function setUpBeforeClass() {
        require "config.inc.php";
        self::$conf = $conf;
    }
    
    public function setUp () {
        $this->event_manager = new EventManager(self::$conf);
        
        $this->event = new Event(array(
            'name' => 'new_script',
            'script_id' => 1,
            'role' => 'mysql',
            'schema' => 'someschema',
            'user' => 'johndoe',
            'description' => 'creating a new table',
            'instances' => array(
                'production' => array(
                    "deployment_status" => "awaiting_approval",
                    "marked_status" => null,
                    "deployment_type" => "manual",
                    "environment" => 'production',
                    "processing_start_time" => null,
                    "processing_end_time" => null,
                    "last_message" => null,
                ),
                'build' => array(
                    "deployment_status" => "awaiting_approval",
                    "marked_status" => null,
                    "deployment_type" => "automatic",
                    "environment" => 'build',
                    "processing_start_time" => null,
                    "processing_end_time" => null,
                    "last_message" => null,
                ),
                'qa' => array(
                    "deployment_status" => "awaiting_approval",
                    "marked_status" => null,
                    "deployment_type" => "automatic",
                    "environment" => 'qa',
                    "processing_start_time" => null,
                    "processing_end_time" => null,
                    "last_message" => null,
                ),
                'dev' => array(
                    "deployment_status" => "awaiting_approval",
                    "marked_status" => null,
                    "deployment_type" => "automatic",
                    "environment" => 'dev',
                    "processing_start_time" => null,
                    "processing_end_time" => null,
                    "last_message" => null,
                ),
            ),
        ));
    }
    
    public function testAttachedListeners () {
        $this->assertTrue($this->event_manager->has_listeners('new_script'));
        $this->assertTrue($this->event_manager->has_listeners('approve_script'));
        $this->assertTrue($this->event_manager->has_listeners('comment_script'));
        
        $this->assertTrue($this->event_manager->has_listeners('mark_script'));
        
        $this->assertFalse($this->event_manager->has_listeners('redeploy_script'));
    }
    
    
    public function testNotify () {
        $this->event->name = "new_script";
        ob_start();
        var_dump($this->event);
        $dump = ob_get_clean();
        
        $this->expectOutputString($dump);
        
        $this->event_manager->notify("new_script", $this->event);
    }
    
    
    public function testNotifyStopPropagation () {
        $this->event->name = "approve_script";
        ob_start();
        var_dump($this->event);
        var_dump($this->event);
        $dump = ob_get_clean();
        
        $this->expectOutputString($dump);

        $this->event_manager->notify("approve_script", $this->event);
    }
    
    
    public function testNotifyComment () {
        $this->event->name = "comment_script";
        $this->event->comment = "This is my comment";
        $this->event->comment_mark = "ok";
        ob_start();
        var_dump($this->event);
        $dump = ob_get_clean();
        
        $this->expectOutputString($dump);
        
        $this->event_manager->notify("comment_script", $this->event);
    }
    
    
    public function testNotifyMark () {
        $this->event->name = "mark_script";
        $this->event->instances['production']['marked_status'] = "not_started";
        $this->event->instances['production']['deployment_status'] = "not_started";
        unset($this->event->instances['build']);
        unset($this->event->instances['qa']);
        unset($this->event->instances['dev']);
        ob_start();
        var_dump($this->event);
        $dump = ob_get_clean();
        
        $this->expectOutputString($dump);
        
        $this->event_manager->notify("mark_script", $this->event);
    }
}

?>