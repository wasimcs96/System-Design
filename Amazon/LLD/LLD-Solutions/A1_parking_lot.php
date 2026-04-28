<?php
/**
 * A1. PARKING LOT SYSTEM
 * ============================================================
 * PROBLEM: Design a multi-floor parking lot that supports
 * different vehicle types, spot allocation, ticketing, and
 * fee calculation.
 *
 * FUNCTIONAL REQUIREMENTS:
 *  - Park and unpark vehicles (Motorcycle, Car, Truck)
 *  - Multiple floors with multiple spots each
 *  - Assign the nearest available spot per vehicle type
 *  - Generate ticket on entry; calculate fee on exit
 *  - Display real-time availability
 *
 * NON-FUNCTIONAL REQUIREMENTS:
 *  - Extensible: new vehicle types / pricing strategies
 *  - SOLID principles throughout
 *
 * ASSUMPTIONS:
 *  - Motorcycle fits any spot; Car fits Car/Truck; Truck only Truck
 *  - Fee = hourly rate × hours (ceiling)
 *  - Single entry/exit point per lot (simplified)
 *
 * DESIGN PATTERNS USED:
 *  - Factory   : SpotFactory creates spots by type
 *  - Strategy  : PricingStrategy for different pricing models
 *  - Singleton : ParkingLot is the single lot instance
 * ============================================================
 */

// ─── Enums ────────────────────────────────────────────────────
enum VehicleType: string { case MOTORCYCLE = 'Motorcycle'; case CAR = 'Car'; case TRUCK = 'Truck'; }
enum SpotStatus: string  { case FREE = 'Free'; case OCCUPIED = 'Occupied'; }

// ─── Pricing Strategy (Strategy Pattern) ──────────────────────
interface PricingStrategy {
    public function calculateFee(float $hours): float;
}

class HourlyPricing implements PricingStrategy {
    public function __construct(private float $ratePerHour) {}
    public function calculateFee(float $hours): float {
        return ceil($hours) * $this->ratePerHour; // Ceiling hour billing
    }
}

class FlatRatePricing implements PricingStrategy {
    public function __construct(private float $flatRate) {}
    public function calculateFee(float $hours): float {
        return $this->flatRate; // Fixed fee regardless of duration
    }
}

// ─── Vehicle ───────────────────────────────────────────────────
class Vehicle {
    public function __construct(
        public readonly string      $licensePlate,
        public readonly VehicleType $type
    ) {}
}

// ─── Parking Spot ──────────────────────────────────────────────
class ParkingSpot {
    private SpotStatus $status = SpotStatus::FREE;
    private ?Vehicle   $vehicle = null;

    public function __construct(
        public readonly int         $spotNumber,
        public readonly VehicleType $type,     // Type of vehicle this spot fits
        public readonly int         $floor
    ) {}

    public function isAvailable(): bool { return $this->status === SpotStatus::FREE; }

    public function assign(Vehicle $v): void {
        $this->vehicle = $v;
        $this->status  = SpotStatus::OCCUPIED;
    }

    public function vacate(): void {
        $this->vehicle = null;
        $this->status  = SpotStatus::FREE;
    }

    public function getVehicle(): ?Vehicle { return $this->vehicle; }
}

// ─── SpotFactory (Factory Pattern) ─────────────────────────────
class SpotFactory {
    public static function create(int $number, VehicleType $type, int $floor): ParkingSpot {
        return new ParkingSpot($number, $type, $floor);
    }
}

// ─── Ticket ────────────────────────────────────────────────────
class Ticket {
    public readonly string $ticketId;
    public readonly float  $entryTime;

    public function __construct(
        public readonly Vehicle     $vehicle,
        public readonly ParkingSpot $spot
    ) {
        $this->ticketId  = uniqid('TKT-', true);
        $this->entryTime = microtime(true);
    }

    public function getHoursParked(): float {
        return (microtime(true) - $this->entryTime) / 3600;
    }
}

// ─── Parking Floor ─────────────────────────────────────────────
class ParkingFloor {
    /** @var ParkingSpot[] */
    private array $spots = [];

    public function __construct(public readonly int $floorNumber) {}

    public function addSpot(ParkingSpot $spot): void {
        $this->spots[] = $spot;
    }

    /**
     * Find the first free spot that can accommodate the given vehicle type.
     * Motorcycle → any spot | Car → Car or Truck | Truck → Truck only
     */
    public function findAvailableSpot(VehicleType $vehicleType): ?ParkingSpot {
        foreach ($this->spots as $spot) {
            if (!$spot->isAvailable()) continue;
            if ($this->canFit($vehicleType, $spot->type)) return $spot;
        }
        return null;
    }

    private function canFit(VehicleType $vehicle, VehicleType $spot): bool {
        return match($vehicle) {
            VehicleType::MOTORCYCLE => true,                    // Fits anywhere
            VehicleType::CAR        => in_array($spot, [VehicleType::CAR, VehicleType::TRUCK]),
            VehicleType::TRUCK      => $spot === VehicleType::TRUCK,
        };
    }

    public function getAvailableCount(): int {
        return count(array_filter($this->spots, fn($s) => $s->isAvailable()));
    }
}

// ─── Parking Lot (Singleton) ────────────────────────────────────
class ParkingLot {
    private static ?ParkingLot $instance = null;

    /** @var ParkingFloor[] */
    private array   $floors  = [];
    /** @var Ticket[] ticketId → Ticket */
    private array   $tickets = [];

    private PricingStrategy $pricingStrategy;

    private function __construct(private readonly string $name) {
        // Default: hourly pricing
        $this->pricingStrategy = new HourlyPricing(ratePerHour: 20.0);
    }

    public static function getInstance(string $name = 'Main Lot'): self {
        if (self::$instance === null) self::$instance = new self($name);
        return self::$instance;
    }

    public function setPricingStrategy(PricingStrategy $strategy): void {
        $this->pricingStrategy = $strategy;
    }

    public function addFloor(ParkingFloor $floor): void {
        $this->floors[] = $floor;
    }

    /**
     * Entry point: find a spot, create a ticket.
     * Returns the ticket or null if full.
     */
    public function parkVehicle(Vehicle $vehicle): ?Ticket {
        foreach ($this->floors as $floor) {
            $spot = $floor->findAvailableSpot($vehicle->type);
            if ($spot !== null) {
                $spot->assign($vehicle);
                $ticket = new Ticket($vehicle, $spot);
                $this->tickets[$ticket->ticketId] = $ticket;
                echo "✓ Parked {$vehicle->type->value} [{$vehicle->licensePlate}]"
                   . " → Floor {$spot->floor}, Spot #{$spot->spotNumber}\n";
                echo "  Ticket: {$ticket->ticketId}\n";
                return $ticket;
            }
        }
        echo "✗ No spot available for {$vehicle->type->value}\n";
        return null;
    }

    /**
     * Exit point: vacate spot, calculate fee.
     */
    public function unparkVehicle(string $ticketId): float {
        $ticket = $this->tickets[$ticketId] ?? null;
        if ($ticket === null) {
            echo "✗ Invalid ticket ID: $ticketId\n";
            return 0.0;
        }
        $ticket->spot->vacate();
        $hours = max($ticket->getHoursParked(), 1/3600); // At least billing test works
        $fee   = $this->pricingStrategy->calculateFee($hours);
        unset($this->tickets[$ticketId]);
        echo "✓ Unparked [{$ticket->vehicle->licensePlate}]  Fee: ₹{$fee}\n";
        return $fee;
    }

    public function displayAvailability(): void {
        echo "\n--- Parking Availability: {$this->name} ---\n";
        foreach ($this->floors as $floor) {
            echo "  Floor {$floor->floorNumber}: {$floor->getAvailableCount()} spots free\n";
        }
        echo "-------------------------------------------\n";
    }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== A1. Parking Lot System ===\n\n";

// Build the lot
$lot = ParkingLot::getInstance('Downtown Lot');

$f1 = new ParkingFloor(1);
$f1->addSpot(SpotFactory::create(101, VehicleType::MOTORCYCLE, 1));
$f1->addSpot(SpotFactory::create(102, VehicleType::CAR,        1));
$f1->addSpot(SpotFactory::create(103, VehicleType::CAR,        1));
$f1->addSpot(SpotFactory::create(104, VehicleType::TRUCK,      1));

$f2 = new ParkingFloor(2);
$f2->addSpot(SpotFactory::create(201, VehicleType::CAR,   2));
$f2->addSpot(SpotFactory::create(202, VehicleType::TRUCK, 2));

$lot->addFloor($f1);
$lot->addFloor($f2);
$lot->displayAvailability();

// Park vehicles
$t1 = $lot->parkVehicle(new Vehicle('MH-01-AA-1234', VehicleType::MOTORCYCLE));
$t2 = $lot->parkVehicle(new Vehicle('MH-02-BB-5678', VehicleType::CAR));
$t3 = $lot->parkVehicle(new Vehicle('MH-03-CC-9012', VehicleType::TRUCK));
$lot->displayAvailability();

// Unpark
if ($t2) $lot->unparkVehicle($t2->ticketId);
$lot->displayAvailability();

/**
 * EDGE CASES HANDLED:
 *  - Lot full: parkVehicle returns null + prints message
 *  - Invalid ticket: unparkVehicle returns 0.0
 *  - Motorcycle fits any spot (backward compatible)
 *  - Car can use oversized Truck spot
 *
 * COMPLEXITY:
 *  - parkVehicle: O(F × S) where F=floors, S=spots per floor
 *  - unparkVehicle: O(1) with hash map lookup
 *  - Space: O(F × S) for spot storage
 *
 * INTERVIEW FOLLOW-UPS:
 *  1. How to make spot allocation O(1)? → Priority queue per type
 *  2. Concurrent parking? → Lock per floor or spot-level optimistic lock
 *  3. Reservation system? → Add a ReservedSpot concept with expiry
 *  4. How to add EV charging spots? → Extend SpotType enum
 *  5. Fee calculation with discount? → Decorate PricingStrategy
 */
