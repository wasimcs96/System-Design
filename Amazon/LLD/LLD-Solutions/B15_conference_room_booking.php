<?php
/**
 * B15. CONFERENCE ROOM BOOKING
 * ============================================================
 * PROBLEM: Book conference rooms with time-slot conflict detection,
 * capacity checks, and amenities filtering.
 *
 * PATTERNS:
 *  - Repository : RoomRepository with conflict detection
 *  - Builder    : BookingRequestBuilder
 * ============================================================
 */

// ─── Amenity ──────────────────────────────────────────────────
enum Amenity: string {
    case PROJECTOR   = 'Projector';
    case VIDEO_CONF  = 'VideoConference';
    case WHITEBOARD  = 'Whiteboard';
    case CATERING    = 'Catering';
}

// ─── Room ─────────────────────────────────────────────────────
class ConferenceRoom {
    /** @var array{start:\DateTime,end:\DateTime,bookedBy:string}[] */
    private array $bookings = [];

    public function __construct(
        public readonly string $roomId,
        public readonly string $name,
        public readonly int    $capacity,
        /** @var Amenity[] */
        public readonly array  $amenities = []
    ) {}

    public function hasAmenity(Amenity $amenity): bool {
        return in_array($amenity, $this->amenities, true);
    }

    public function isAvailable(\DateTime $start, \DateTime $end): bool {
        foreach ($this->bookings as $b) {
            if ($start < $b['end'] && $b['start'] < $end) return false;
        }
        return true;
    }

    public function book(\DateTime $start, \DateTime $end, string $bookedBy): string {
        $bookingId = uniqid('CB-');
        $this->bookings[] = ['start' => $start, 'end' => $end, 'bookedBy' => $bookedBy, 'id' => $bookingId];
        return $bookingId;
    }

    public function cancel(string $bookingId): bool {
        $before = count($this->bookings);
        $this->bookings = array_values(array_filter($this->bookings, fn($b) => $b['id'] !== $bookingId));
        return count($this->bookings) < $before;
    }

    public function getSchedule(): array { return $this->bookings; }
}

// ─── Booking Request (Builder) ────────────────────────────────
class BookingRequest {
    public ?int      $minCapacity = null;
    public ?\DateTime $startTime  = null;
    public ?\DateTime $endTime    = null;
    public array      $required   = [];  // Amenity[]
    public string     $bookedBy   = '';
}

class BookingRequestBuilder {
    private BookingRequest $req;
    public function __construct() { $this->req = new BookingRequest(); }
    public function forPerson(string $who): self    { $this->req->bookedBy    = $who; return $this; }
    public function withCapacity(int $n): self      { $this->req->minCapacity = $n;   return $this; }
    public function from(string $dt): self          { $this->req->startTime   = new \DateTime($dt); return $this; }
    public function to(string $dt): self            { $this->req->endTime     = new \DateTime($dt); return $this; }
    public function requiring(Amenity ...$a): self  { $this->req->required    = $a;   return $this; }
    public function build(): BookingRequest         { return $this->req; }
}

// ─── Booking Service ──────────────────────────────────────────
class RoomBookingService {
    /** @var ConferenceRoom[] */
    private array $rooms = [];

    public function addRoom(ConferenceRoom $r): void { $this->rooms[] = $r; }

    public function findAvailable(BookingRequest $req): array {
        return array_values(array_filter($this->rooms, function(ConferenceRoom $room) use ($req) {
            if ($req->minCapacity && $room->capacity < $req->minCapacity) return false;
            foreach ($req->required as $amenity) if (!$room->hasAmenity($amenity)) return false;
            if ($req->startTime && $req->endTime && !$room->isAvailable($req->startTime, $req->endTime)) return false;
            return true;
        }));
    }

    public function bookRoom(ConferenceRoom $room, BookingRequest $req): ?string {
        if (!$room->isAvailable($req->startTime, $req->endTime)) {
            echo "  ✗ Room {$room->name} is not available\n"; return null;
        }
        $id = $room->book($req->startTime, $req->endTime, $req->bookedBy);
        echo "  ✓ Booked {$room->name} [{$id}] by {$req->bookedBy}"
           . " | " . $req->startTime->format('H:i') . "–" . $req->endTime->format('H:i') . "\n";
        return $id;
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== B15. Conference Room Booking ===\n\n";

$service = new RoomBookingService();
$service->addRoom(new ConferenceRoom('CR1', 'Apollo', 10, [Amenity::PROJECTOR, Amenity::WHITEBOARD]));
$service->addRoom(new ConferenceRoom('CR2', 'Hubble', 20, [Amenity::PROJECTOR, Amenity::VIDEO_CONF]));
$service->addRoom(new ConferenceRoom('CR3', 'Kepler', 5,  [Amenity::WHITEBOARD]));

$req = (new BookingRequestBuilder())
    ->forPerson('Alice')
    ->withCapacity(8)
    ->requiring(Amenity::PROJECTOR)
    ->from('2026-06-01 10:00')->to('2026-06-01 11:00')
    ->build();

$available = $service->findAvailable($req);
echo "Available rooms: " . implode(', ', array_map(fn($r) => $r->name, $available)) . "\n";
$bookingId = $service->bookRoom($available[0], $req);

// Try overlapping booking
$req2 = (new BookingRequestBuilder())
    ->forPerson('Bob')
    ->withCapacity(8)
    ->requiring(Amenity::PROJECTOR)
    ->from('2026-06-01 10:30')->to('2026-06-01 11:30')
    ->build();

$available2 = $service->findAvailable($req2);
echo "Available after booking: " . implode(', ', array_map(fn($r) => $r->name, $available2)) . "\n";
