<?php
class SimpleCounter
{
    public int $value = 0;
    public string $label;

    public function __construct(string $label)
    {
        echo "  [SimpleCounter] Constructor ran for '{$label}' (expensive setup simulated)\n";
        $this->label = $label;
    }
}

echo "--- TIER 1: clone does NOT call __construct() ---\n";
$counterPrototype = new SimpleCounter('master'); // constructor runs ONCE, here
$counterA = clone $counterPrototype;              // no constructor output below
$counterB = clone $counterPrototype;              // no constructor output below
$counterA->value = 5;
$counterB->value = 99;
echo "  counterA->value = {$counterA->value}, counterB->value = {$counterB->value}\n";
echo "  (constructor ran exactly ONCE above, for both clones combined)\n\n";


class Address
{
    public function __construct(public string $city)
    {
    }
}

/** BUGGY — no __clone() override. Kept deliberately so driver code can
 *  PROVE the bug before showing the fix. Never ship this shape. */
class CustomerBuggy
{
    public Address $address;

    public function __construct(Address $address)
    {
        $this->address = $address;
    }
}

/** FIXED — __clone() explicitly re-clones the nested Address. */
class CustomerFixed
{
    public Address $address;

    public function __construct(Address $address)
    {
        $this->address = $address;
    }

    public function __clone(): void
    {
        // Runs AFTER PHP's shallow copy already exists. Replace the
        // shared reference with an independent clone.
        $this->address = clone $this->address;
    }
}


$buggyOriginal = new CustomerBuggy(new Address('Dubai'));
$buggyClone = clone $buggyOriginal;
echo "  before". $buggyClone->address->city;

$buggyClone->address->city = 'Riyadh';
echo "  buggyClone->address->city    = {$buggyClone->address->city}\n";
echo "  buggyOriginal->address->city = {$buggyOriginal->address->city}"
    . " <-- BUG! changed too, they share ONE Address object\n";
echo "  Same object? " . ($buggyClone->address === $buggyOriginal->address ? "YES (bug confirmed)" : "no") . "\n\n";

echo "--- TIER 2b: THE FIX (__clone() re-clones the nested object) ---\n";
$fixedOriginal = new CustomerFixed(new Address('Dubai'));
$fixedClone = clone $fixedOriginal;
$fixedClone->address->city = 'Riyadh';
echo "  fixedClone->address->city    = {$fixedClone->address->city}\n";
echo "  fixedOriginal->address->city = {$fixedOriginal->address->city} <-- correct, unaffected\n";
echo "  Same object? " . ($fixedClone->address === $fixedOriginal->address ? "YES (still buggy)" : "NO (fixed, independent)") . "\n\n";





