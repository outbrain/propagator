<?php 

class DumpAndStopListener implements EventListenerInterface {
    
    public function update ($event) {
        var_dump($event);
        $event->stop_propagation();
    }
    
}

?>