<?php
/**
 * B5. ELEVATOR SYSTEM
 * ============================================================
 * PROBLEM: Design elevator control with multiple cars,
 * floor requests, and a dispatch algorithm.
 *
 * PATTERNS:
 *  - State    : Elevator states (Idle, Moving Up/Down, Maintenance)
 *  - Strategy : DispatchAlgorithm (nearest, SCAN)
 * ============================================================
 */

enum Direction: string { case UP='Up'; case DOWN='Down'; case IDLE='Idle'; }
enum ElevatorStatus: string { case IDLE='Idle'; case MOVING='Moving'; case MAINTENANCE='Maintenance'; }

// ─── Request ────────────────────────────────────────────────────
class ElevatorRequest {
    public function __construct(
        public readonly int        $fromFloor,
        public readonly int        $toFloor,
        public readonly Direction  $direction
    ) {}
}

// ─── Elevator ──────────────────────────────────────────────────
class Elevator {
    private ElevatorStatus $status    = ElevatorStatus::IDLE;
    private Direction      $direction = Direction::IDLE;
    /** @var int[] sorted list of destination floors */
    private array          $stops     = [];

    public function __construct(
        public readonly string $id,
        private int            $currentFloor = 0
    ) {}

    public function getCurrentFloor(): int      { return $this->currentFloor; }
    public function getStatus(): ElevatorStatus { return $this->status; }
    public function getDirection(): Direction   { return $this->direction; }
    public function getStops(): array           { return $this->stops; }

    public function addStop(int $floor): void {
        if (!in_array($floor, $this->stops)) {
            $this->stops[] = $floor;
            sort($this->stops);
        }
    }

    /** Simulate moving one step (one floor) */
    public function step(): void {
        if (empty($this->stops)) { $this->status = ElevatorStatus::IDLE; $this->direction = Direction::IDLE; return; }

        $this->status = ElevatorStatus::MOVING;
        $next = $this->stops[0];
        $this->direction = ($next > $this->currentFloor) ? Direction::UP : Direction::DOWN;

        if ($this->direction === Direction::UP) $this->currentFloor++;
        else                                    $this->currentFloor--;

        if ($this->currentFloor === $next) {
            echo "    [{$this->id}] Opened doors at floor {$this->currentFloor}\n";
            array_shift($this->stops);
        }
    }

    /** Simulate full trip to all stops */
    public function run(): void {
        $maxIter = 100;
        while (!empty($this->stops) && $maxIter-- > 0) $this->step();
        $this->status    = ElevatorStatus::IDLE;
        $this->direction = Direction::IDLE;
        echo "    [{$this->id}] Now idle at floor {$this->currentFloor}\n";
    }
}

// ─── Dispatch Strategy ─────────────────────────────────────────
interface DispatchStrategy {
    /** @param Elevator[] $elevators */
    public function dispatch(array $elevators, ElevatorRequest $request): Elevator;
}

/** Nearest car dispatch: pick elevator closest to request floor */
class NearestCarDispatch implements DispatchStrategy {
    public function dispatch(array $elevators, ElevatorRequest $request): Elevator {
        $best = null; $minDist = PHP_INT_MAX;
        foreach ($elevators as $e) {
            if ($e->getStatus() === ElevatorStatus::MAINTENANCE) continue;
            $dist = abs($e->getCurrentFloor() - $request->fromFloor);
            if ($dist < $minDist) { $minDist = $dist; $best = $e; }
        }
        return $best ?? $elevators[0];
    }
}

// ─── ElevatorController (Facade) ───────────────────────────────
class ElevatorController {
    /** @var Elevator[] */
    private array $elevators = [];

    public function __construct(private DispatchStrategy $strategy) {}

    public function addElevator(Elevator $e): void { $this->elevators[] = $e; }

    public function requestElevator(int $from, int $to): void {
        $dir     = $from < $to ? Direction::UP : Direction::DOWN;
        $request = new ElevatorRequest($from, $to, $dir);
        $car     = $this->strategy->dispatch($this->elevators, $request);
        echo "  Dispatched {$car->id} (at floor {$car->getCurrentFloor()}) for request {$from}→{$to}\n";
        $car->addStop($from);
        $car->addStop($to);
        $car->run();
    }

    public function displayStatus(): void {
        foreach ($this->elevators as $e) {
            echo "  {$e->id}: floor={$e->getCurrentFloor()} status={$e->getStatus()->value}\n";
        }
    }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== B5. Elevator System ===\n\n";

$controller = new ElevatorController(new NearestCarDispatch());
$controller->addElevator(new Elevator('E1', 0));
$controller->addElevator(new Elevator('E2', 5));

echo "--- Initial status ---\n";
$controller->displayStatus();

echo "\n--- Request 1: Floor 2 → Floor 7 ---\n";
$controller->requestElevator(2, 7);

echo "\n--- Request 2: Floor 4 → Floor 1 ---\n";
$controller->requestElevator(4, 1);

echo "\n--- Final status ---\n";
$controller->displayStatus();
