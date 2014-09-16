<?php 

/**
 * class EventManager
 * 
 * Handles the distribution of events to listeners
 * 
 * @author Jeremy Earle <jearle@dealnews.com>
 * @license Apache 2.0 license.  See LICENSE document for more info
 * @created 2014-09-02
 */
class EventManager {
    
    protected $listeners;
    
    protected $listeners_dir = "./listeners/";
    
    /**
     * Constructor.  Initialize the model object
     * 
     * @param    array    $conf    The global config information
     */
    public function __construct($conf) {
        if (!empty($conf['event_listener_dir']) && file_exists($conf['event_listener_dir'])) {
            $this->listeners_dir = $conf['event_listener_dir'];
            if (substr($this->listeners_dir, 0, -1) != "/") {
                $this->listeners_dir .= "/";
            }
        }
        if (!empty($conf['event_listeners']) && is_array($conf['event_listeners'])) {
            foreach ($conf['event_listeners'] as $el) {
                $this->add_listener($el);
            }
        }
    }
    
    /**
     * Notify listeners of an event
     * 
     * @param    string    $event_name    The name of the event
     * @param    object    $event         An event object that describes the event
     */
    public function notify ($event_name, $event) {
        if ($this->has_listeners($event_name)) {
            foreach ($this->listeners[$event_name] as $listener) {
                include_once($listener['file']);
                $classname = $listener['class'];
                $observer = new $classname;
                $event->name = $event_name;
                $observer->update($event);
                if ($event->propagation_stopped()) {
                    break;
                }
            }
        }
    }
    
    /**
     * Does this event have listeners?
     * 
     * @param    string    $event_name
     */
    public function has_listeners ($event_name) {
        return !empty($this->listeners[$event_name]);
    }
    
    
    /**
     * Adds a listener to the list for a particular event
     * 
     * @param    array    $listener    An array that describes a listener. Must contain a 'class', 'file', and 'event'.
     */
    private function add_listener ($listener) {
        if (!empty($listener) && is_array($listener)) {
            $listen = array();
            $event = array();
            
            if (!empty($listener['event'])) {
                if (is_string($listener['event'])) {
                    $event[] = $listener['event'];
                } elseif (is_array($listener['event'])) {
                    foreach ($listener['event'] as $e) {
                        if (is_string($e)) {
                            $event[] = $e;
                        }
                    }
                }
            }
            
            if (!empty($event)) {
                if (!empty($listener['class'])) {
                    if (!empty($listener['file']) && $this->event_file_exists($listener['file'])) {
                        $listen['file'] = $this->listeners_dir . $listener['file'];
                        $listen['class'] = $listener['class'];
                    }
                }
                
                if (!empty($listen)) {
                    foreach ($event as $eve) {
                        $this->listeners[$eve][] = $listen;
                    }
                }
            }
        }
    }
    
    /**
     * Checks to see if the file exists relative to the listeners directory
     * 
     * @param    string    $file    Filename (with path relative to listeners directory).
     */
    private function event_file_exists ($file) {
        if (file_exists($this->listeners_dir . $file)) {
            return true;
        }
        
        return false;
    }
}

?>