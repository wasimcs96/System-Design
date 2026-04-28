<?php
/**
 * A7. MOVIE TICKET BOOKING SYSTEM (like BookMyShow)
 * ============================================================
 * PROBLEM: Book movie tickets with seat selection, locking,
 * show schedule management, and payment.
 *
 * PATTERNS:
 *  - Observer : Notify customer when seat lock expires or booking confirmed
 *  - Strategy : SeatPricing (normal, premium, VIP)
 * ============================================================
 */

enum SeatType: string  { case NORMAL = 'Normal'; case PREMIUM = 'Premium'; case VIP = 'VIP'; }
enum SeatStatus: string { case AVAILABLE='Available'; case LOCKED='Locked'; case BOOKED='Booked'; }
enum PaymentStatus: string { case PENDING='Pending'; case SUCCESS='Success'; case FAILED='Failed'; }

// ─── Pricing Strategy ──────────────────────────────────────────
interface SeatPricingStrategy {
    public function getPrice(SeatType $type): float;
}

class StandardPricing implements SeatPricingStrategy {
    public function getPrice(SeatType $type): float {
        return match($type) {
            SeatType::NORMAL  => 200.0,
            SeatType::PREMIUM => 350.0,
            SeatType::VIP     => 500.0,
        };
    }
}

// ─── Seat ───────────────────────────────────────────────────────
class Seat {
    private SeatStatus $status = SeatStatus::AVAILABLE;
    private ?string    $lockedBy  = null;
    private ?float     $lockExpiry = null;

    public function __construct(
        public readonly string   $seatId,
        public readonly SeatType $type,
        public readonly int      $row,
        public readonly int      $col
    ) {}

    public function isAvailable(): bool {
        // Auto-expire lock
        if ($this->status === SeatStatus::LOCKED && time() > ($this->lockExpiry ?? 0)) {
            $this->status    = SeatStatus::AVAILABLE;
            $this->lockedBy  = null;
        }
        return $this->status === SeatStatus::AVAILABLE;
    }

    public function lock(string $userId, int $ttlSeconds = 600): bool {
        if (!$this->isAvailable()) return false;
        $this->status      = SeatStatus::LOCKED;
        $this->lockedBy    = $userId;
        $this->lockExpiry  = time() + $ttlSeconds;
        return true;
    }

    public function book(string $userId): bool {
        if ($this->status === SeatStatus::LOCKED && $this->lockedBy === $userId) {
            $this->status = SeatStatus::BOOKED;
            return true;
        }
        return false;
    }

    public function release(): void {
        $this->status   = SeatStatus::AVAILABLE;
        $this->lockedBy = null;
    }

    public function getStatus(): SeatStatus { return $this->status; }
}

// ─── Show ────────────────────────────────────────────────────────
class Show {
    /** @var Seat[] seatId → Seat */
    private array $seats = [];

    public function __construct(
        public readonly string    $showId,
        public readonly string    $movieName,
        public readonly \DateTime $showTime,
        public readonly string    $screenName
    ) {}

    public function addSeat(Seat $seat): void { $this->seats[$seat->seatId] = $seat; }

    public function getAvailableSeats(): array {
        return array_values(array_filter($this->seats, fn($s) => $s->isAvailable()));
    }

    public function getSeat(string $seatId): ?Seat { return $this->seats[$seatId] ?? null; }

    public function lockSeats(array $seatIds, string $userId): bool {
        // Lock all or none (atomic)
        foreach ($seatIds as $id) {
            if (!($this->seats[$id] ?? null)?->isAvailable()) {
                echo "  ✗ Seat $id not available\n";
                return false;
            }
        }
        foreach ($seatIds as $id) $this->seats[$id]->lock($userId);
        return true;
    }
}

// ─── Payment ────────────────────────────────────────────────────
class Payment {
    public readonly string $paymentId;
    public PaymentStatus   $status = PaymentStatus::PENDING;

    public function __construct(public readonly float $amount) {
        $this->paymentId = uniqid('PAY-');
    }

    public function process(): bool {
        // Simulate payment gateway
        $this->status = PaymentStatus::SUCCESS;
        return true;
    }
}

// ─── Booking ────────────────────────────────────────────────────
class Booking {
    public readonly string $bookingId;

    public function __construct(
        public readonly string  $userId,
        public readonly Show    $show,
        public readonly array   $seats,
        public readonly Payment $payment
    ) {
        $this->bookingId = uniqid('BK-');
    }
}

// ─── Booking Service ────────────────────────────────────────────
class BookingService {
    /** @var Show[] showId → Show */
    private array $shows    = [];
    /** @var Booking[] bookingId → Booking */
    private array $bookings = [];

    private SeatPricingStrategy $pricing;

    public function __construct() { $this->pricing = new StandardPricing(); }

    public function addShow(Show $show): void { $this->shows[$show->showId] = $show; }

    public function bookSeats(string $userId, string $showId, array $seatIds): ?Booking {
        $show = $this->shows[$showId] ?? null;
        if (!$show) { echo "  ✗ Show not found\n"; return null; }

        // Step 1: Lock all requested seats (prevents double booking)
        if (!$show->lockSeats($seatIds, $userId)) return null;
        echo "  ✓ Seats locked for 10 min: " . implode(', ', $seatIds) . "\n";

        // Step 2: Calculate total
        $total = 0.0;
        foreach ($seatIds as $id) {
            $seat   = $show->getSeat($id);
            $total += $this->pricing->getPrice($seat->type);
        }

        // Step 3: Process payment
        $payment = new Payment($total);
        if (!$payment->process()) {
            // Release locks on payment failure
            foreach ($seatIds as $id) $show->getSeat($id)?->release();
            echo "  ✗ Payment failed. Seats released.\n";
            return null;
        }

        // Step 4: Confirm booking
        foreach ($seatIds as $id) $show->getSeat($id)?->book($userId);
        $booking = new Booking($userId, $show, $seatIds, $payment);
        $this->bookings[$booking->bookingId] = $booking;
        echo "  ✓ Booking confirmed [{$booking->bookingId}] | ₹{$total}\n";
        echo "  Movie: {$show->movieName} at {$show->showTime->format('Y-m-d H:i')}\n";
        return $booking;
    }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== A7. Movie Ticket Booking (BookMyShow) ===\n\n";

$show = new Show('S001', 'Interstellar', new \DateTime('2026-05-01 18:00'), 'Screen 1');
$show->addSeat(new Seat('A1', SeatType::NORMAL,  1, 1));
$show->addSeat(new Seat('A2', SeatType::NORMAL,  1, 2));
$show->addSeat(new Seat('B1', SeatType::PREMIUM, 2, 1));
$show->addSeat(new Seat('C1', SeatType::VIP,     3, 1));

$service = new BookingService();
$service->addShow($show);

echo "Available seats: " . count($show->getAvailableSeats()) . "\n\n";

$b1 = $service->bookSeats('user_alice', 'S001', ['A1', 'B1']);
echo "\nAvailable after booking: " . count($show->getAvailableSeats()) . "\n";

// Try to book already taken seat
$b2 = $service->bookSeats('user_bob', 'S001', ['A1']);
