<?php
/**
 * C2. AMAZON LOCKER
 * ============================================================
 * PROBLEM: Assign packages to locker slots by size, generate
 * pickup codes, and manage retrieval within an expiry window.
 *
 * PATTERNS:
 *  - Strategy : SlotAssignment (best-fit, first-fit)
 * ============================================================
 */

enum SlotSize: string { case SMALL='S'; case MEDIUM='M'; case LARGE='L'; case XLARGE='XL'; }
enum SlotStatus: string { case EMPTY='Empty'; case OCCUPIED='Occupied'; }

// ─── Package ──────────────────────────────────────────────────
class Package {
    public function __construct(
        public readonly string   $packageId,
        public readonly string   $customerId,
        public readonly SlotSize $requiredSize,
        public readonly float    $weightKg
    ) {}
}

// ─── Locker Slot ──────────────────────────────────────────────
class LockerSlot {
    private SlotStatus  $status     = SlotStatus::EMPTY;
    private ?Package    $package    = null;
    private ?string     $pickupCode = null;
    private ?\DateTime  $expiresAt  = null;

    public function __construct(
        public readonly string   $slotId,
        public readonly SlotSize $size,
        public readonly int      $locationId
    ) {}

    public function isAvailable(): bool     { return $this->status === SlotStatus::EMPTY; }
    public function getSize(): SlotSize     { return $this->size; }
    public function getStatus(): SlotStatus { return $this->status; }
    public function getPackage(): ?Package  { return $this->package; }

    public function assignPackage(Package $pkg, int $expiryHours = 72): string {
        $this->status     = SlotStatus::OCCUPIED;
        $this->package    = $pkg;
        $this->pickupCode = strtoupper(substr(md5($pkg->packageId . microtime(true)), 0, 6));
        $this->expiresAt  = new \DateTime("+{$expiryHours} hours");
        return $this->pickupCode;
    }

    public function pickup(string $code): bool {
        if ($this->pickupCode !== $code) return false;
        if ($this->expiresAt && new \DateTime() > $this->expiresAt) {
            echo "  ✗ Pickup code expired\n"; return false;
        }
        $this->status     = SlotStatus::EMPTY;
        $this->package    = null;
        $this->pickupCode = null;
        $this->expiresAt  = null;
        return true;
    }
}

// ─── Locker Station ───────────────────────────────────────────
class LockerStation {
    /** @var LockerSlot[] slotId → slot */
    private array $slots = [];
    /** @var array<string,string> packageId → slotId */
    private array $packageToSlot = [];
    /** @var array<string,string> packageId → pickupCode */
    private array $pickupCodes = [];

    public function __construct(
        public readonly string $stationId,
        public readonly string $address
    ) {}

    public function addSlot(LockerSlot $slot): void { $this->slots[$slot->slotId] = $slot; }

    /** First-fit assignment by size compatibility */
    public function assignPackage(Package $pkg): ?string {
        $sizeOrder = [SlotSize::SMALL, SlotSize::MEDIUM, SlotSize::LARGE, SlotSize::XLARGE];
        $required  = array_search($pkg->requiredSize, $sizeOrder, true);

        foreach ($this->slots as $slot) {
            $slotSize = array_search($slot->getSize(), $sizeOrder, true);
            if (!$slot->isAvailable() || $slotSize < $required) continue;

            $code = $slot->assignPackage($pkg);
            $this->packageToSlot[$pkg->packageId] = $slot->slotId;
            $this->pickupCodes[$pkg->packageId]   = $code;
            echo "  ✓ Package [{$pkg->packageId}] → Slot [{$slot->slotId}] | Code: {$code}\n";
            return $code;
        }
        echo "  ✗ No slot available for package [{$pkg->packageId}]\n";
        return null;
    }

    public function pickupPackage(string $packageId, string $code): bool {
        $slotId = $this->packageToSlot[$packageId] ?? null;
        if (!$slotId) { echo "  ✗ Package not found at this station\n"; return false; }
        $result = $this->slots[$slotId]->pickup($code);
        if ($result) {
            unset($this->packageToSlot[$packageId], $this->pickupCodes[$packageId]);
            echo "  ✓ Package [{$packageId}] picked up\n";
        } else {
            echo "  ✗ Invalid pickup code\n";
        }
        return $result;
    }

    public function getAvailableCount(): int {
        return count(array_filter($this->slots, fn($s) => $s->isAvailable()));
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C2. Amazon Locker ===\n\n";

$station = new LockerStation('LOC-BLR-001', 'Koramangala, Bangalore');
$station->addSlot(new LockerSlot('S1', SlotSize::SMALL,  1));
$station->addSlot(new LockerSlot('S2', SlotSize::SMALL,  1));
$station->addSlot(new LockerSlot('M1', SlotSize::MEDIUM, 1));
$station->addSlot(new LockerSlot('L1', SlotSize::LARGE,  1));

$p1 = new Package('PKG-001', 'C001', SlotSize::SMALL,  0.5);
$p2 = new Package('PKG-002', 'C002', SlotSize::MEDIUM, 1.2);
$p3 = new Package('PKG-003', 'C003', SlotSize::SMALL,  0.3);
$p4 = new Package('PKG-004', 'C004', SlotSize::SMALL,  0.2); // No small slots left

$code1 = $station->assignPackage($p1);
$code2 = $station->assignPackage($p2);
$station->assignPackage($p3);
$station->assignPackage($p4); // Should fail or use larger slot

echo "\nAvailable slots: " . $station->getAvailableCount() . "\n";

echo "\n--- Pickup ---\n";
$station->pickupPackage('PKG-001', $code1 ?? '');
$station->pickupPackage('PKG-001', 'WRONG');  // Invalid code attempt
echo "Available slots after pickup: " . $station->getAvailableCount() . "\n";
