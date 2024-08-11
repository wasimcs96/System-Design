<?php

class Vehicle
{
    private $type;
    private $registrationNumber;
    private $color;

    public function __construct($type, $registrationNumber, $color)
    {
        $this->type = $type;
        $this->registrationNumber = $registrationNumber;
        $this->color = $color;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getRegistrationNumber()
    {
        return $this->registrationNumber;
    }

    public function getColor()
    {
        return $this->color;
    }
}

class Slot
{
    private $floor;
    private $slotNumber;
    private $type;
    private $vehicle;

    public function __construct($floor, $slotNumber, $type)
    {
        $this->floor = $floor;
        $this->slotNumber = $slotNumber;
        $this->type = $type;
        $this->vehicle = null;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getFloor()
    {
        return $this->floor;
    }

    public function getSlotNumber()
    {
        return $this->slotNumber;
    }

    public function isAvailable()
    {
        return $this->vehicle === null;
    }

    public function isOccupied()
    {
        return $this->vehicle !== null;
    }

    public function parkVehicle($vehicle)
    {
        $this->vehicle = $vehicle;
    }

    public function unparkVehicle()
    {
        $vehicle = $this->vehicle;
        $this->vehicle = null;
        return $vehicle;
    }
}

class Ticket
{
    private $id;

    public function __construct($lotId, $floor, $slotNumber)
    {
        $this->id = $lotId."_".$floor."_".$slotNumber;
    }

    public function getId()
    {
        return $this->id;
    }
    
}


class ParkingLot
{
    private $id;
    private $floors;
    private $slots;
    private $tickets;
    private $vehicleTypeSlots;

    public function __construct($id, $no_of_floors, $no_of_slots_per_floor)
    {
        $this->id = $id;
        $this->floors = $no_of_floors;
        $this->slots = [];
        $this->tickets = [];
        $this->vehicleTypeSlots = [
            'CAR' => [],
            'BIKE' => [],
            'TRUCK' => []
        ];

        $this->initializeSlots($no_of_slots_per_floor);
    }

    private function initializeSlots($no_of_slots_per_floor)
    {
        for ($floor = 1; $floor <= $this->floors; $floor++) {
            $floorSlots = [];
            for ($slot = 1; $slot <= $no_of_slots_per_floor; $slot++) {
                if ($slot === 1) {
                    $type = 'TRUCK';
                } elseif ($slot <= 3) {
                    $type = 'BIKE';
                } else {
                    $type = 'CAR';
                }
                $slotObj = new Slot($floor, $slot, $type);
                $floorSlots[] = $slotObj;
                $this->vehicleTypeSlots[$type][] = $slotObj;
            }
            $this->slots[$floor] = $floorSlots;
        }
        
    }

    public function parkVehicle($vehicle)
    {
        foreach ($this->vehicleTypeSlots[$vehicle->getType()] as $slot) {
            if ($slot->isAvailable()) {
                $slot->parkVehicle($vehicle);
                $ticket = new Ticket($this->id, $slot->getFloor(), $slot->getSlotNumber());
                $this->tickets[$ticket->getId()] = $ticket;
                //return "Parked vehicle. Ticket ID: " . $ticket->getId();
                return $ticket->getId();
            }
        }
        return "Parking Lot Full";
    }

    public function unparkVehicle($ticketId)
    {
        if (!isset($this->tickets[$ticketId])) {
            return "Invalid Ticket";
        }

        $ticket = $this->tickets[$ticketId];
        $ticketInfo = explode("_", $ticket->getId());
        $slot = $this->getSlot($ticketInfo[1], $ticketInfo[2]);
        if ($slot === null || !$slot->isOccupied()) {
            return "Invalid Ticket";
        }

        $vehicle = $slot->unparkVehicle();
        unset($this->tickets[$ticketId]);
        return "Unparked vehicle with Registration Number: " . $vehicle->getRegistrationNumber() . " and Color: " . $vehicle->getColor();
    }

    public function display($type, $vehicleType)
    {
        $output = '';
        foreach ($this->slots as $floorNumber => $floorSlots) {
            $freeSlots = [];
            $occupiedSlots = [];
            foreach ($floorSlots as $slot) {
                if ($slot->getType() === $vehicleType) {
                    if ($slot->isAvailable()) {
                        $freeSlots[] = $slot->getSlotNumber();
                    } else {
                        $occupiedSlots[] = $slot->getSlotNumber();
                    }
                }
            }

            switch ($type) {
                case 'free_count':
                    $output .= "No. of free slots for $vehicleType on Floor $floorNumber: " . count($freeSlots) . PHP_EOL;
                    break;
                case 'free_slots':
                    $output .= "Free slots for $vehicleType on Floor $floorNumber: " . implode(',', $freeSlots) . PHP_EOL;
                    break;
                case 'occupied_slots':
                    $output .= "Occupied slots for $vehicleType on Floor $floorNumber: " . implode(',', $occupiedSlots) . PHP_EOL;
                    break;
            }
        }
        return $output;
    }

    private function getSlot($floor, $slotNumber)
    {
        return isset($this->slots[$floor][$slotNumber - 1]) ? $this->slots[$floor][$slotNumber - 1] : null;
    }
}




// $id = 'PR1234';
// $no_of_floors = 2;
// $no_of_slots_per_floor = 6;

// $parkingLot = new ParkingLot($id, (int)$no_of_floors, (int)$no_of_slots_per_floor);

// $display_type = 'free_count'; $vehicle_type = 'CAR';
// echo $parkingLot->display($display_type, $vehicle_type);

// $vehicle = new Vehicle('CAR', 'KA-01-DB-1234', 'black'); 
// $ticketId = $parkingLot->parkVehicle($vehicle);//// return Parked vehicle. Ticket ID: " . $ticket->getId()

// $display_type = 'free_count'; $vehicle_type = 'CAR';
// echo $parkingLot->display($display_type, $vehicle_type);

// $parkingLot->unparkVehicle($ticketId);

// $display_type = 'free_count'; $vehicle_type = 'CAR';
// echo $parkingLot->display($display_type, $vehicle_type);


class CommandHandler
{
    private $parkingLot;

    public function __construct($parkingLot)
    {
        $this->parkingLot = $parkingLot;
    }

    public function handleCommand($command)
    {
        $parts = explode(' ', $command);
        $action = array_shift($parts);

        switch ($action) {
            case 'create_parking_lot':
                $this->handleCreateParkingLot($parts);
                break;
            case 'park_vehicle':
                $this->handleParkVehicle($parts);
                break;
            case 'unpark_vehicle':
                $this->handleUnparkVehicle($parts);
                break;
            case 'display':
                $this->handleDisplay($parts);
                break;
            case 'exit':
                exit;
                break;
            default:
                echo "Invalid Command\n";
        }
    }

    private function handleCreateParkingLot($parts)
    {
        list($id, $no_of_floors, $no_of_slots_per_floor) = $parts;
        $this->parkingLot = new ParkingLot($id, (int)$no_of_floors, (int)$no_of_slots_per_floor);
        echo "Created parking lot with $no_of_floors floors and $no_of_slots_per_floor slots per floor\n";
    }

    private function handleParkVehicle($parts)
    {
        list($type, $reg_no, $color) = $parts;
        $vehicle = new Vehicle($type, $reg_no, $color);
        echo $this->parkingLot->parkVehicle($vehicle) . "\n";
    }

    private function handleUnparkVehicle($parts)
    {
        $ticketId = $parts[0];
        echo $this->parkingLot->unparkVehicle($ticketId) . "\n";
    }

    private function handleDisplay($parts)
    {
        list($display_type, $vehicle_type) = $parts;
        echo $this->parkingLot->display($display_type, $vehicle_type);
    }
}



// Initialize a variable to hold the parking lot instance
$parkingLot = null;

// Initialize the CommandHandler with the parking lot
$commandHandler = new CommandHandler($parkingLot);

// Read and process commands from the standard input
while (true) {
    $line = trim(fgets(STDIN));

    if ($line === '') {
        continue;
    }

    // Handle the 'create_parking_lot' command
    if (strpos($line, 'create_parking_lot') === 0) {
        list($command, $id, $no_of_floors, $no_of_slots_per_floor) = explode(' ', $line);
        $parkingLot = new ParkingLot($id, (int)$no_of_floors, (int)$no_of_slots_per_floor);
        $commandHandler = new CommandHandler($parkingLot);
        echo "Created parking lot with $no_of_floors floors and $no_of_slots_per_floor slots per floor\n";
        continue;
    }

    // Exit command
    if ($line === 'exit') {
        break;
    }

    // Handle all other commands
    $commandHandler->handleCommand($line);
}

//php test.php
//create_parking_lot PR1234 2 6
// display free_count CAR
// park_vehicle CAR KA-01-DB-1234 black
// unpark_vehicle PR1234_1_1
// exit







