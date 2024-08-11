<?php


class Vehicle{
    private $vehicleNumber;
    private $vehicleType;
    private $vehicleColour;

    public function __construct(string $vehicleNumber, string $vehicleType, string $vehicleColour){
        $this->vehicleNumber = $vehicleNumber;
        $this->vehicleType = $vehicleType;
        $this->vehicleColour = $vehicleColour;
    }

    public function getVehicleDetails(){
        return ['type'=>$this->vehicleType, 'number'=>$this->vehicleNumber, 'colour'=>$this->vehicleColour];
    } 
}


class ParkingSlot{
    private $slotNumber;
    private $isOccupied;
    private $vehicleType;
    private $parkingLotId;
    private $vehicleInfo;
    private $floorNumber;

    public function __construct(int $floorNumber, int $slotNumber, string $vehicleType, Vehicle $vehicle){
        $this->isOccupied = true;
        $this->slotNumber = $slotNumber;
        $this->vehicleType = $vehicleType;
        $this->parkingLotId = $slotNumber;
        $this->vehicleInfo = $vehicle;
        $this->floorNumber = $floorNumber;
    }

    public function getSlotDetails(){
        return array('type'=>$this->vehicleType, 
        'slotNumber'=>$this->slotNumber, 
        'parkingLotId'=>$this->parkingLotId, 
        'isOccupied'=>$this->isOccupied,  
        'vehicleInfo' =>$this->vehicleInfo,
        'floorNumber'=>$this->floorNumber);
    }

    public function makeFree(){
        $this->vehicleInfo = null;
        $this->isOccupied = false;
        return true;
    }

    public function makeOccupied(){
        $this->isOccupied = true;
    }

    public function getParkingLotId(){
        return $this->parkingLotId;
    }
}

class ParkingFloorSlots{
    private int $floorNumber;
    private int $totalSlots;
    public array $vehicleTypeSlots = [];
    private array $slots = [];
    public int $slotNumber = 1; 

    public function __construct(int $floorNumber, int $totalSlots){
        $this->floorNumber = $floorNumber;
        $this->totalSlots = $totalSlots;
    }

    public function addParkingVehicleTypeWithCapacity(string $vehicleType, int $capacity){
        $this->vehicleTypeSlots[$this->floorNumber][$vehicleType]['totalSlots'] = $capacity;
        $this->vehicleTypeSlots[$this->floorNumber][$vehicleType]['availableSlots'] = $capacity;
        $this->vehicleTypeSlots[$this->floorNumber][$vehicleType]['occupiedSlots'] = 0;
        $this->vehicleTypeSlots[$this->floorNumber][$vehicleType]['slots'] = [];
        if($vehicleType == 'Truck')
            $this->vehicleTypeSlots[$this->floorNumber][$vehicleType]['slots'] = array_fill($this->slotNumber, $capacity, '');
        if($vehicleType == 'Bike')
            $this->vehicleTypeSlots[$this->floorNumber][$vehicleType]['slots'] = array_fill($this->slotNumber, $capacity, '');
        if($vehicleType == 'Car')
            $this->vehicleTypeSlots[$this->floorNumber][$vehicleType]['slots'] = array_fill($this->slotNumber, $capacity, '');

        $this->slotNumber += $capacity;
    }

    public function parkAVehicle(Vehicle $vehicleObj, int $availableSlotNumber){
       $vehicleDetails = $vehicleObj->getVehicleDetails();
       if($this->vehicleTypeSlots[$this->floorNumber][$vehicleDetails['type']]['availableSlots'] > 0){
            //check Available Slot number
            $slot = new ParkingSlot($this->floorNumber, $availableSlotNumber, $vehicleDetails['type'], $vehicleObj);
            $this->vehicleTypeSlots[$this->floorNumber][$vehicleDetails['type']]['availableSlots']--;
            $this->vehicleTypeSlots[$this->floorNumber][$vehicleDetails['type']]['occupiedSlots']++;
            $this->vehicleTypeSlots[$this->floorNumber][$vehicleDetails['type']]['slots'][$availableSlotNumber] = $slot;
            //print_r($this->vehicleTypeSlots);
            return $slot;
       }else{
            throw new Exception($vehicleDetails['type']." type slots does not exist");
       }
       
    }

    public function unParkVehicle(ParkingSlot $parkingSlotDetails){
            $this->vehicleTypeSlots[$this->floorNumber][$parkingSlotDetails['type']]['availableSlots']++;
            $this->vehicleTypeSlots[$this->floorNumber][$parkingSlotDetails['type']]['occupiedSlots']--;
            $this->vehicleTypeSlots[$this->floorNumber][$parkingSlotDetails['type']]['slots'][$parkingSlotDetails->getParkingLotId()] = '';
        print_r( $this->vehicleTypeSlots);
    }

    public function getVehicleTypeSlots(){
        return $this->vehicleTypeSlots[$this->floorNumber];
    }
}

Class ParkingLot{
    private $parkingLotName;
    private $parkingLotFloorNumbers;
    public $parkingLotFloors = [];

    public function __construct($parkingLotName){
        $this->parkingLotName = $parkingLotName;
    }
    
    public function addFloor(int $floorNumber, ParkingFloorSlots $parkingFloorSlots){
        $this->parkingLotFloors[$floorNumber] = $parkingFloorSlots;
        return $this->parkingLotFloors[$floorNumber];
    }

    public function getAllFloorsVehicleTypeAvailableSlots($checkVehicleType = ''){
        $vehicleTypeAvailableSlots = [];
        foreach($this->parkingLotFloors as $floorNumber => $parkingFloorSlots){
            foreach($parkingFloorSlots->getVehicleTypeSlots() as $vehicleType => $vehicle){
                $vehicleTypeAvailableSlots[$floorNumber][$vehicleType] = $vehicle['availableSlots'];
                if(!empty($checkVehicleType) && $checkVehicleType == $vehicleType && $vehicleTypeAvailableSlots[$floorNumber][$vehicleType] > 0){
                    
                    foreach($vehicle['slots'] as $slotNumber => $slot){
                        if(empty($slot)){
                            return [$floorNumber, $slotNumber] ;
                        }
                    }
                }
            }
        }
        return $vehicleTypeAvailableSlots;
    }

    public function isSlotAvailable(string $vehicleType){
        list($floorSlots, $slotNumber) = $this->getAllFloorsVehicleTypeAvailableSlots($vehicleType);
        if(!empty($floorSlots) && !empty($slotNumber)){
            return [$floorSlots, $slotNumber];
        }
        return null;
    }

    public function parkAVehicle(Vehicle $vehicle){
        $vehicleDetails = $vehicle->getVehicleDetails();
        list($availableFloor, $availableSlot) = $this->isSlotAvailable($vehicleDetails['type']);
        if($availableFloor !== null){
            $parkingFloorSlots = $this->parkingLotFloors[$availableFloor];
            $occupiedSlot = $parkingFloorSlots->parkAVehicle($vehicle, $availableSlot);
            return $occupiedSlot;
        }else{
            throw new Exception($vehicleDetails['type']." type slots does not exist");
        }
    }

    public function unParkVehicle(ParkingSlot $parkingSlotDetails){
        $slotDeatils = $parkingSlotDetails->getSlotDetails();
        $floorNumber = $slotDeatils['floorNumber'];
        $slotNumber = $slotDeatils['slotNumber'];
        $parkingFloorSlot = $this->parkingLotFloors[$floorNumber]->vehicleTypeSlots[$floorNumber]['type'];
        $parkingFloorSlot->unParkVehicle($parkingFloorSlot);
    }
}


$southParking = new ParkingLot("South Parking");
$floorNumber = 1; $firstFloorSlots = 100;
$southFirstFloor = $southParking->addFloor(1, new ParkingFloorSlots($floorNumber, $firstFloorSlots));
$southFirstFloor->addParkingVehicleTypeWithCapacity('Truck', 5);
$southFirstFloor->addParkingVehicleTypeWithCapacity('Car', 10);
$southFirstFloor->addParkingVehicleTypeWithCapacity('Bike', 15);

$southSecondFloor = $southParking->addFloor(2, new ParkingFloorSlots(2, 100));
$southSecondFloor->addParkingVehicleTypeWithCapacity('Truck', 10);
$southSecondFloor->addParkingVehicleTypeWithCapacity('Car', 20);
$southSecondFloor->addParkingVehicleTypeWithCapacity('Bike', 30);

//print_r($southParking->getAllFloorsVehicleTypeAvailableSlots()); die;

$t1 = new Vehicle('1234', 'Truck', 'Red');
$t1occupiedSlot = $southParking->parkAVehicle($t1);

$occupiedSlot2 = $southParking->unParkVehicle($t1occupiedSlot);


$vehicle2 = new Vehicle('1235', 'Truck', 'Black');
$occupiedSlot2 = $southParking->parkAVehicle($vehicle2); die;

// $vehicle3 = new Vehicle('1236', 'Truck', 'Blue');
// $occupiedSlot3 = $southParking->parkAVehicle($vehicle3);


$vehicle3 = new Vehicle('1111', 'Car', 'Blue');
$occupiedSlot3 = $southParking->parkAVehicle($vehicle3);


$vehicle3 = new Vehicle('1110', 'Bike', 'Blue');
$occupiedSlot3 = $southParking->parkAVehicle($vehicle3);




print_r($southParking->getAllFloorsVehicleTypeAvailableSlots());







 












 



