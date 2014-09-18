<?php 

/**
 * Interface for event listener classes
 * 
 * @author Jeremy Earle <jearle@dealnews.com>
 * @license Apache 2.0 license.  See LICENSE document for more info
 * @created 2014-09-02
 */
interface EventListenerInterface {
    
    /**
     * Method that is called by the EventManager when an event takes place
     * that this listener is register to listen to.
     * 
     * @param    object    $event    An event object that describes the event
     */
    public function update ($event);
    
}


?>