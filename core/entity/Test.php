<?php
class Test extends DIEntity {

    var $property = 'default';
    
    function getProperty() {
        return $this->property;
    }
    
    function setProperty($property) {
        $this->property = $property;
    }
    
    function put(){
        echo "Entity: Test->put()<br>property: $this->property <br> ";
    }
    
}