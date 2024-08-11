<?php
// Behavioral
// Strategy
// Terminology:
// -Context
// -Strategy
// -Concrete Strategy

// Purpose
// To separate strategies and to enable fast switching between them. Also this pattern is a good alternative to inheritance (instead of having an abstract class that is extended).

// Examples
// -sorting a list of objects, one strategy by date, the other by id
// -simplify unit testing: e.g. switching between file and in-memory storage

//WithoutStretegyPattern
class Vehicle{
    public function drive(){
        echo "Normal Drive";
    }
}
class ElectricVehicle extends Vehicle{
    public function drive(){
        echo "Electric Drive";
    }
}
class SportsVehicle extends Vehicle{
    public function drive(){
        echo "Sprots Drive";
    }
}
//Rewrite drive defination

//WithStretegyPattern
interface Drive{
    public function drive();
}
class ElectricVehicleDrive implements Drive{
    public function drive(){
        echo "Electric Drive\n";
    }
}
class SportsVehicleDrive implements Drive{
    public function drive(){
        echo "Sprots Drive\n";
    }
}

class VehicleStretagy{
    private Drive $driveStretegy;
    public function __construct(Drive $driveStretegy){
        $this->driveStretegy = $driveStretegy;
    }
    public function drive(){
        $this->driveStretegy->drive();
    }
}

class ElectricVehiclee extends VehicleStretagy{
    // public function getDriveStretegy(){
    //     $this->drive();
    // }
}
class SportsVehiclee extends VehicleStretagy{
    // public function getDriveStretegy(){
    //     $this->drive();
    // }
}

$ElectricVehicleDrive = new ElectricVehicleDrive();
$SportsVehicleDrive = new SportsVehicleDrive();

$ElectricVehiclee = new ElectricVehiclee($ElectricVehicleDrive);
$SportsVehiclee = new SportsVehiclee($SportsVehicleDrive);

$ElectricVehiclee->drive();
$SportsVehiclee->drive();

