<?php
// ParkingLot.php

interface VehicleType {
    const TWO = 'two-wheeler';
    const FOUR = 'four-wheeler';
}

interface ParkingLotInterface {
    public function reserveSpace(string $vehicleNumber, VehicleType $vehicleType);
    public function releaseSpace(string $vehicleNumber, VehicleType $vehicleType);
}

class ParkingLot implements ParkingLotInterface, vehicleType {
    private $totalSpaces;
    private $availableSpaces;
    private $reservedSpaces;
    

    public function __construct($totalSpaces) {
        $this->totalSpaces = $totalSpaces;
        $this->availableSpaces = $totalSpaces;
        $this->reservedSpaces = [];
    }

    public function reserveSpace(string $vehicleNumber, $vehicleType) {
        if (!($vehicleType === self::TWO || $vehicleType === self::FOUR)) {
            throw new InvalidArgumentException("Invalid vehicle type: $vehicleType");
        } 
        if ($this->availableSpaces[$vehicleType] > 0) {
            $this->availableSpaces[$vehicleType]--;
            $this->reservedSpaces[$vehicleType][$vehicleNumber] = true;
            echo "Vehicle has parked";
        } else {
            echo "No available parking space for the given type of Vehicle.";
        }
        
    }

    public function releaseSpace(string $vehicleNumber, $vehicleType) {
        if (isset($this->reservedSpaces[$vehicleType][$vehicleNumber])) {
            $this->availableSpaces[$vehicleType]++;
            unset($this->reservedSpaces[$vehicleType][$vehicleNumber]);
            echo  "The vehicle has been released from the parking lot <BR>";
        } else {
            echo "Invalid vehicle number or it is not in the parking lot.";
        }
    }

    public function getAvailableSpaces($vehicleType) {
        return $this->availableSpaces[$vehicleType];
    }
}

// Example usage:
$spaceArr = [VehicleType::TWO => 100, VehicleType::FOUR=>100];
$parkingLot = new ParkingLot($spaceArr);
$vehicle1 = 'ABC123';
$vehicle2 = 'XYZ789';

$parkingLot->reserveSpace($vehicle1, VehicleType::TWO).'<BR>';;
$parkingLot->reserveSpace("sdfsd", VehicleType::TWO).'<BR>';;
$parkingLot->reserveSpace($vehicle2, VehicleType::FOUR)."<BR>";;

echo "Available spaces for Twowheller:  ".$parkingLot->getAvailableSpaces(VehicleType::TWO) ."<BR>"; // Output: Available spaces for TWO
echo "Available spaces for four wheller:  ".$parkingLot->getAvailableSpaces(VehicleType::FOUR)." <BR>";// Output: Available spaces for FOUR


$parkingLot->releaseSpace($vehicle1, VehicleType::TWO);
$parkingLot->releaseSpace($vehicle2, VehicleType::FOUR);


echo "Available spaces after releasing from 2: " . $parkingLot->getAvailableSpaces(VehicleType::TWO)."<BR>"; // Output: Available spaces after releasing: 99
echo "Available spaces after releasing from 4: " . $parkingLot->getAvailableSpaces(VehicleType::FOUR)."<BR>"; // Output: Available spaces after releasing: 99



?>
