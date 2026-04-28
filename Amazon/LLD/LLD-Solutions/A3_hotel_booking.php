<?php
/**
 * A3. HOTEL ROOM BOOKING SYSTEM
 * ============================================================
 * PROBLEM: Book hotel rooms with date-range conflict detection,
 * guest management, and flexible pricing.
 *
 * PATTERNS:
 *  - Builder    : BookingBuilder constructs complex Booking objects
 *  - Strategy   : PricingStrategy (standard, weekend, seasonal)
 *  - Repository : RoomRepository for room queries
 * ============================================================
 */

// ─── Enums ─────────────────────────────────────────────────────
enum RoomType: string { case STANDARD = 'Standard'; case DELUXE = 'Deluxe'; case SUITE = 'Suite'; }
enum BookingStatus: string { case PENDING='Pending'; case CONFIRMED='Confirmed'; case CANCELLED='Cancelled'; }

// ─── Pricing Strategy (Strategy Pattern) ──────────────────────
interface PricingStrategy {
    public function calculateTotal(Room $room, \DateTime $checkIn, \DateTime $checkOut): float;
}

class StandardPricing implements PricingStrategy {
    public function calculateTotal(Room $room, \DateTime $checkIn, \DateTime $checkOut): float {
        $nights = $checkIn->diff($checkOut)->days;
        return $nights * $room->baseRate;
    }
}

class WeekendSurchargePricing implements PricingStrategy {
    public function calculateTotal(Room $room, \DateTime $checkIn, \DateTime $checkOut): float {
        $total = 0.0;
        $curr  = clone $checkIn;
        while ($curr < $checkOut) {
            $rate   = $room->baseRate;
            $dayNum = (int)$curr->format('N'); // 6=Sat, 7=Sun
            if ($dayNum >= 6) $rate *= 1.3;   // 30% weekend surcharge
            $total += $rate;
            $curr->modify('+1 day');
        }
        return $total;
    }
}

// ─── Room ──────────────────────────────────────────────────────
class Room {
    /** @var array{checkIn:\DateTime,checkOut:\DateTime}[] */
    private array $bookedPeriods = [];

    public function __construct(
        public readonly string   $roomNumber,
        public readonly RoomType $type,
        public readonly int      $capacity,
        public readonly float    $baseRate
    ) {}

    public function isAvailable(\DateTime $checkIn, \DateTime $checkOut): bool {
        foreach ($this->bookedPeriods as $period) {
            // Overlap check: two periods overlap if start1 < end2 AND start2 < end1
            if ($checkIn < $period['checkOut'] && $period['checkIn'] < $checkOut) return false;
        }
        return true;
    }

    public function book(\DateTime $checkIn, \DateTime $checkOut): void {
        $this->bookedPeriods[] = ['checkIn' => $checkIn, 'checkOut' => $checkOut];
    }

    public function cancelBooking(\DateTime $checkIn, \DateTime $checkOut): void {
        $this->bookedPeriods = array_values(array_filter(
            $this->bookedPeriods,
            fn($p) => !($p['checkIn'] == $checkIn && $p['checkOut'] == $checkOut)
        ));
    }
}

// ─── Guest ─────────────────────────────────────────────────────
class Guest {
    public function __construct(
        public readonly string $guestId,
        public readonly string $name,
        public readonly string $email
    ) {}
}

// ─── Booking (Built with Builder Pattern) ──────────────────────
class Booking {
    public readonly string $bookingId;
    public BookingStatus $status = BookingStatus::CONFIRMED;

    public function __construct(
        public readonly Guest     $guest,
        public readonly Room      $room,
        public readonly \DateTime $checkIn,
        public readonly \DateTime $checkOut,
        public readonly float     $totalAmount
    ) {
        $this->bookingId = uniqid('BKG-');
    }
}

class BookingBuilder {
    private ?Guest     $guest    = null;
    private ?Room      $room     = null;
    private ?\DateTime $checkIn  = null;
    private ?\DateTime $checkOut = null;
    private PricingStrategy $pricing;

    public function __construct() {
        $this->pricing = new StandardPricing(); // Default pricing
    }

    public function forGuest(Guest $g): self  { $this->guest   = $g; return $this; }
    public function inRoom(Room $r): self      { $this->room    = $r; return $this; }
    public function from(string $date): self   { $this->checkIn = new \DateTime($date); return $this; }
    public function to(string $date): self     { $this->checkOut = new \DateTime($date); return $this; }
    public function withPricing(PricingStrategy $p): self { $this->pricing = $p; return $this; }

    public function build(): Booking {
        if (!$this->guest || !$this->room || !$this->checkIn || !$this->checkOut)
            throw new \InvalidArgumentException("Incomplete booking details");
        $amount = $this->pricing->calculateTotal($this->room, $this->checkIn, $this->checkOut);
        $this->room->book($this->checkIn, $this->checkOut);
        return new Booking($this->guest, $this->room, $this->checkIn, $this->checkOut, $amount);
    }
}

// ─── Booking Service (Facade) ──────────────────────────────────
class BookingService {
    /** @var Room[] */
    private array $rooms = [];
    /** @var Booking[] */
    private array $bookings = [];

    public function addRoom(Room $room): void { $this->rooms[$room->roomNumber] = $room; }

    public function findAvailableRooms(RoomType $type, string $checkIn, string $checkOut): array {
        $in  = new \DateTime($checkIn);
        $out = new \DateTime($checkOut);
        return array_values(array_filter(
            $this->rooms,
            fn($r) => $r->type === $type && $r->isAvailable($in, $out)
        ));
    }

    public function createBooking(Booking $booking): void {
        $this->bookings[$booking->bookingId] = $booking;
        echo "✓ Booking [{$booking->bookingId}] confirmed for {$booking->guest->name}\n";
        echo "  Room: {$booking->room->roomNumber} | "
           . $booking->checkIn->format('Y-m-d') . " → " . $booking->checkOut->format('Y-m-d')
           . " | Total: ₹{$booking->totalAmount}\n";
    }

    public function cancelBooking(string $bookingId): void {
        $booking = $this->bookings[$bookingId] ?? null;
        if (!$booking) { echo "✗ Booking not found\n"; return; }
        $booking->status = BookingStatus::CANCELLED;
        $booking->room->cancelBooking($booking->checkIn, $booking->checkOut);
        echo "✓ Booking [{$bookingId}] cancelled\n";
    }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== A3. Hotel Room Booking System ===\n\n";

$service = new BookingService();
$service->addRoom(new Room('101', RoomType::STANDARD, 2, 2000.0));
$service->addRoom(new Room('201', RoomType::DELUXE,   2, 4000.0));
$service->addRoom(new Room('301', RoomType::SUITE,    4, 8000.0));

$alice = new Guest('G001', 'Alice', 'alice@example.com');
$bob   = new Guest('G002', 'Bob',   'bob@example.com');

// Find and book
$available = $service->findAvailableRooms(RoomType::STANDARD, '2026-05-01', '2026-05-03');
echo "Available Standard rooms (May 1-3): " . count($available) . "\n";

$booking1 = (new BookingBuilder())
    ->forGuest($alice)->inRoom($available[0])
    ->from('2026-05-01')->to('2026-05-03')
    ->withPricing(new WeekendSurchargePricing())
    ->build();
$service->createBooking($booking1);

// Try to double-book same room same dates
$available2 = $service->findAvailableRooms(RoomType::STANDARD, '2026-05-01', '2026-05-02');
echo "Available Standard rooms (May 1-2 after booking): " . count($available2) . "\n";

// Cancel
$service->cancelBooking($booking1->bookingId);
$available3 = $service->findAvailableRooms(RoomType::STANDARD, '2026-05-01', '2026-05-02');
echo "Available Standard after cancel: " . count($available3) . "\n";

/**
 * INTERVIEW FOLLOW-UPS:
 *  1. Payment integration? → Add PaymentGateway strategy in BookingBuilder
 *  2. Room upgrade mid-stay? → New Booking + cancel old, or ModifyBooking command
 *  3. Concurrent bookings same room? → Optimistic locking with version field
 *  4. Housekeeping schedule? → Observer on checkout date
 */
