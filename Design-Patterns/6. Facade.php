<?php
//Facade is a structural design pattern that provides a simplified (but limited) interface to a complex system of classes, library or framework.
//While Facade decreases the overall complexity of the application, it also helps to move unwanted dependencies to one place.

class Facade{
    public $subsystem1;
    public $subsystem2;
    public function __construct(Subsystem1 $subsystem1 = null, Subsystem1 $subsystem2 = null){
        $this->subsystem1 = $subsystem1 ?: new Subsystem1();
        $this->subsystem2 = $subsystem2 ?: new Subsystem2();
    }
}

?>