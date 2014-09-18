<?php 

class DumpListener implements EventListenerInterface {
    
    public function update ($event) {
        var_dump($event);
    }
    
}

?>