<?php 

/**
 * Generic event class that is used to describe an event
 * 
 * @author Jeremy Earle <jearle@dealnews.com>
 * @license Apache 2.0 license.  See LICENSE document for more info
 * @created 2014-09-02
 */
class Event {
    
    /**
     * Name of the event
     * 
     * @var string
     */
    public $name;
    
    /**
     * Propagate script id related to event
     * 
     * @var int
     */
    public $script_id;
    
    /**
     * Role related to event
     * 
     * @var string
     */
    public $role;
    
    /**
     * Schema related to event
     * 
     * @var schema
     */
    public $schema;
    
    /**
     * User related to event
     * 
     * @var string
     */
    public $user;
    
    /**
     * Description of propagate script related to event
     * 
     * @var string
     */
    public $description;
    
    /**
     * A comment that was made on a script related to this event
     * 
     * @var string
     */
    public $comment;
    
    /**
     * A mark that was made on a comment related to this event
     * 
     * Examples: ("ok", "fixed", "todo", etc..)
     * 
     * @var string
     */
    public $comment_mark;
    
    /**
     * A list if instances related to the event.
     * 
     * This associative array will contain an array for each environment.
     * Each array can contain the following keys: 
     * environment, deployment_status, marked_status, deployment_type, 
     * processing_start_time, processing_end_time, last_message
     * 
     * @var array
     */
    public $instances;
    
    
    /**
     * Should the event continue to propagate?
     * 
     * @var boolean
     */
    private $propagate = true;
    
    /**
     * Constructor.
     * 
     * Builds out the details of the event. 
     * See class properties for list of accepted details.
     * 
     * @param    array    $details    An array of event details that will be saved in the event object
     */
    public function __construct($details) {
        if (!empty($details) && is_array($details)) {
            foreach ($details as $key => $value) {
                if ($key != "propagation") {
                    if (property_exists($this, $key)) {
                        $this->$key = $value;
                    }
                }
            }
        }
    }
    
    /**
     * Stop the event from propagating to other observers/listeners
     * 
     */
    public function stop_propagation() {
        $this->propagate = false;
    }
    
    /**
     * Checks to see if propagation has been stopped/halted
     * 
     * @return boolean
     */
    public function propagation_stopped() {
        return ($this->propagate === false);
    }
}


?>