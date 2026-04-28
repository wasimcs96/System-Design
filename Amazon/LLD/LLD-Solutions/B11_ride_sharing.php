<?php
/**
 * B11. RIDE SHARING (like Uber/Ola)
 * ============================================================
 * PROBLEM: Rider requests rides, drivers accept, real-time tracking,
 * fare calculation.
 *
 * PATTERNS:
 *  - Observer  : RideStatus changes notify rider + driver
 *  - Strategy  : FareCalculation (base, surge, shared)
 * ============================================================
 */

enum RideStatus: string {
    case REQUESTED  = 'Requested';
    case ACCEPTED   = 'Accepted';
    case STARTED    = 'Started';
    case COMPLETED  = 'Completed';
    case CANCELLED  = 'Cancelled';
}

enum VehicleType: string { case AUTO = 'Auto'; case MINI = 'Mini'; case SEDAN = 'Sedan'; }

// ─── Location ─────────────────────────────────────────────────
class Location {
    public function __construct(public float $lat, public float $lng) {}
    public function distanceTo(Location $other): float {
        // Euclidean approx in km (simplified)
        return round(sqrt(pow(($this->lat - $other->lat) * 111, 2) + pow(($this->lng - $other->lng) * 111, 2)), 2);
    }
    public function __toString(): string { return "({$this->lat},{$this->lng})"; }
}

// ─── Fare Strategy ────────────────────────────────────────────
interface FareStrategy {
    public function calculate(float $distanceKm, VehicleType $type): float;
}

class BaseFare implements FareStrategy {
    private const RATES = [VehicleType::AUTO->value => 12, VehicleType::MINI->value => 15, VehicleType::SEDAN->value => 20];
    public function calculate(float $distanceKm, VehicleType $type): float {
        return max(30, $distanceKm * (self::RATES[$type->value] ?? 15));
    }
}

class SurgeFare implements FareStrategy {
    public function __construct(private BaseFare $base, private float $multiplier = 1.5) {}
    public function calculate(float $distanceKm, VehicleType $type): float {
        return $this->base->calculate($distanceKm, $type) * $this->multiplier;
    }
}

// ─── Observer ─────────────────────────────────────────────────
interface RideObserver {
    public function onStatusChange(Ride $ride): void;
}

class RiderNotifier implements RideObserver {
    public function onStatusChange(Ride $ride): void {
        echo "  📱 Rider[{$ride->riderId}]: Ride status → {$ride->getStatus()->value}\n";
    }
}

class DriverNotifier implements RideObserver {
    public function onStatusChange(Ride $ride): void {
        $driver = $ride->getDriver();
        if ($driver) echo "  🚗 Driver[{$driver->name}]: {$ride->getStatus()->value}\n";
    }
}

// ─── Driver ───────────────────────────────────────────────────
class Driver {
    public bool $available = true;
    public function __construct(
        public readonly string      $driverId,
        public readonly string      $name,
        public readonly VehicleType $vehicleType,
        public Location             $location
    ) {}
}

// ─── Ride ─────────────────────────────────────────────────────
class Ride {
    public readonly string $rideId;
    private RideStatus     $status = RideStatus::REQUESTED;
    private ?Driver        $driver = null;
    private float          $fare   = 0.0;
    /** @var RideObserver[] */
    private array $observers = [];

    public function __construct(
        public readonly string   $riderId,
        public readonly Location $pickup,
        public readonly Location $dropoff,
        public readonly VehicleType $vehicleType
    ) {
        $this->rideId = uniqid('RIDE-');
    }

    public function addObserver(RideObserver $o): void { $this->observers[] = $o; }

    private function notify(): void {
        foreach ($this->observers as $o) $o->onStatusChange($this);
    }

    public function acceptBy(Driver $driver): void {
        $driver->available = false;
        $this->driver      = $driver;
        $this->status      = RideStatus::ACCEPTED;
        $this->notify();
    }

    public function start(): void { $this->status = RideStatus::STARTED; $this->notify(); }

    public function complete(FareStrategy $fare): void {
        $dist         = $this->pickup->distanceTo($this->dropoff);
        $this->fare   = $fare->calculate($dist, $this->vehicleType);
        $this->status = RideStatus::COMPLETED;
        if ($this->driver) $this->driver->available = true;
        $this->notify();
        echo "  💰 Fare: ₹{$this->fare} for {$dist}km\n";
    }

    public function cancel(): void { $this->status = RideStatus::CANCELLED; $this->notify(); }
    public function getStatus(): RideStatus { return $this->status; }
    public function getDriver(): ?Driver    { return $this->driver; }
}

// ─── Dispatch Service ─────────────────────────────────────────
class RideDispatchService {
    /** @var Driver[] */
    private array $drivers = [];

    public function registerDriver(Driver $d): void { $this->drivers[] = $d; }

    public function findNearestDriver(Location $pickup, VehicleType $type): ?Driver {
        $best = null; $minDist = PHP_INT_MAX;
        foreach ($this->drivers as $d) {
            if (!$d->available || $d->vehicleType !== $type) continue;
            $dist = $d->location->distanceTo($pickup);
            if ($dist < $minDist) { $minDist = $dist; $best = $d; }
        }
        return $best;
    }

    public function requestRide(string $riderId, Location $pickup, Location $drop, VehicleType $type): Ride {
        $ride = new Ride($riderId, $pickup, $drop, $type);
        $ride->addObserver(new RiderNotifier());
        $ride->addObserver(new DriverNotifier());

        $driver = $this->findNearestDriver($pickup, $type);
        if (!$driver) { echo "  ✗ No drivers available\n"; return $ride; }

        echo "  ✓ Ride #{$ride->rideId} matched with {$driver->name}\n";
        $ride->acceptBy($driver);
        return $ride;
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== B11. Ride Sharing System ===\n\n";

$service = new RideDispatchService();
$service->registerDriver(new Driver('D1', 'Ramesh', VehicleType::MINI, new Location(12.97, 77.59)));
$service->registerDriver(new Driver('D2', 'Suresh', VehicleType::SEDAN, new Location(12.95, 77.60)));

$pickup  = new Location(12.97, 77.60);
$dropoff = new Location(13.02, 77.65);

$ride = $service->requestRide('R001', $pickup, $dropoff, VehicleType::MINI);
$ride->start();
$ride->complete(new SurgeFare(new BaseFare(), 1.5));
