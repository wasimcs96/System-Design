<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║   OOP CONCEPT #6 — ASSOCIATION, AGGREGATION & COMPOSITION        ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  LEVEL     : Intermediate → Advanced                             ║
 * ║  FREQUENCY : ★★★★☆  (asked in senior PHP/system design rounds)  ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/**
 * THE THREE OBJECT RELATIONSHIPS (HAS-A family)
 *
 *  Inheritance = IS-A  relationship  (Dog IS-A Animal)
 *  These three = HAS-A relationships (varying strength of coupling)
 *
 * ┌────────────────┬──────────────────────────────────────────────┐
 * │ Relationship   │ Description                                  │
 * ├────────────────┼──────────────────────────────────────────────┤
 * │ Association    │ Objects know about each other (uses-a)       │
 * │                │ Independent lifecycles, loosest coupling     │
 * ├────────────────┼──────────────────────────────────────────────┤
 * │ Aggregation    │ "Whole-Part" but part can exist alone        │
 * │  (weak)        │ Parent destroyed → children survive         │
 * ├────────────────┼──────────────────────────────────────────────┤
 * │ Composition    │ "Whole-Part" and part CANNOT exist alone    │
 * │  (strong)      │ Parent destroyed → children destroyed too   │
 * └────────────────┴──────────────────────────────────────────────┘
 *
 * ANALOGY:
 *   Association  : Teacher ↔ Student  (teacher can exist without students)
 *   Aggregation  : Department ↔ Employee (employee survives dept. deletion)
 *   Composition  : House ↔ Room  (room can't exist without the house)
 */

// ═══════════════════════════════════════════════════════════════
// 1. ASSOCIATION — Objects reference each other, both independent
// ═══════════════════════════════════════════════════════════════
/**
 *  VISUAL:
 *
 *  [Teacher] ─────uses─────> [Student]
 *
 *  Teacher KNOWS about Student, but neither owns the other.
 *  Both can exist independently.
 */

class Student
{
    public function __construct(
        private string $name,
        private int    $rollNumber
    ) {}

    public function getName(): string { return $this->name; }
    public function getRoll(): int    { return $this->rollNumber; }

    public function submit(string $assignment): void
    {
        echo "  [{$this->name}] submitted: {$assignment}\n";
    }
}

class Teacher
{
    private array $students = [];  // Association — references, doesn't own them

    public function __construct(private string $name, private string $subject) {}

    public function getName(): string { return $this->name; }

    // Teacher USES Student objects — association
    public function addStudent(Student $student): void
    {
        $this->students[] = $student;
    }

    public function teach(): void
    {
        echo "  {$this->name} is teaching {$this->subject} to:\n";
        foreach ($this->students as $s) {
            echo "    → Roll #{$s->getRoll()}: {$s->getName()}\n";
        }
    }

    public function assignWork(string $task): void
    {
        echo "  {$this->name} assigned: '{$task}'\n";
        foreach ($this->students as $s) {
            $s->submit($task);
        }
    }
}

echo "=== ASSOCIATION / AGGREGATION / COMPOSITION DEMO ===\n\n";

echo "--- 1. Association ---\n";

// Students exist independently of Teacher
$alice = new Student('Alice', 101);
$bob   = new Student('Bob',   102);
$carol = new Student('Carol', 103);

$teacher = new Teacher('Mr. Smith', 'PHP Development');
$teacher->addStudent($alice);
$teacher->addStudent($bob);
$teacher->addStudent($carol);

$teacher->teach();
echo "\n";
$teacher->assignWork('OOP Assignment #1');

// Both Teacher and Students are independent
unset($teacher);  // Teacher removed — students still usable
echo "\n  Teacher gone but student still exists: " . $alice->getName() . "\n";

// ═══════════════════════════════════════════════════════════════
// 2. AGGREGATION — Parent "has" children, but they're independent
// ═══════════════════════════════════════════════════════════════
/**
 *  VISUAL:
 *
 *  [Department] ◇─────has────> [Employee]
 *                ◇ (open diamond = aggregation)
 *
 *  Department is dissolved → Employees still exist.
 *  Employee can be in multiple departments (or none).
 */

class Employee
{
    public function __construct(
        private string $name,
        private string $role,
        private float  $salary
    ) {}

    public function getName(): string   { return $this->name; }
    public function getRole(): string   { return $this->role; }
    public function getSalary(): float  { return $this->salary; }

    public function work(): void
    {
        echo "  [{$this->name}] is working as {$this->role}\n";
    }
}

class Department
{
    private array $employees = [];

    public function __construct(private string $deptName) {}

    // Aggregation — employees are passed in, not created here
    public function addEmployee(Employee $emp): void
    {
        $this->employees[] = $emp;
        echo "  {$emp->getName()} joined {$this->deptName}\n";
    }

    public function removeEmployee(string $name): void
    {
        $this->employees = array_filter(
            $this->employees,
            fn(Employee $e) => $e->getName() !== $name
        );
        echo "  {$name} left {$this->deptName} (still exists as Employee)\n";
    }

    public function listEmployees(): void
    {
        echo "  {$this->deptName} members:\n";
        foreach ($this->employees as $emp) {
            echo "    → {$emp->getName()} ({$emp->getRole()}) ₹" . number_format($emp->getSalary()) . "/mo\n";
        }
    }

    public function getTotalPayroll(): float
    {
        return array_sum(array_map(fn(Employee $e) => $e->getSalary(), $this->employees));
    }

    public function __destruct()
    {
        echo "  Department '{$this->deptName}' dissolved. Employees still exist!\n";
    }
}

echo "\n--- 2. Aggregation ---\n";

// Employees created independently
$emp1 = new Employee('Ravi',  'Senior Dev',    90000);
$emp2 = new Employee('Priya', 'Designer',      70000);
$emp3 = new Employee('John',  'QA Engineer',   65000);

$engineering = new Department('Engineering');
$engineering->addEmployee($emp1);
$engineering->addEmployee($emp2);
$engineering->addEmployee($emp3);

$engineering->listEmployees();
echo "  Payroll: ₹" . number_format($engineering->getTotalPayroll()) . "/mo\n";
$engineering->removeEmployee('Ravi');

// Employee still usable after department removal
unset($engineering); // Department destroyed
echo "  Ravi still works: ";
$emp1->work();  // Ravi still exists!

// ═══════════════════════════════════════════════════════════════
// 3. COMPOSITION — Parent OWNS children; children can't exist alone
// ═══════════════════════════════════════════════════════════════
/**
 *  VISUAL:
 *
 *  [House] ◆─────contains───> [Room]
 *          ◆ (filled diamond = composition)
 *
 *  Room is created INSIDE House constructor.
 *  Room has NO meaning outside a House.
 *  House destroyed → Rooms destroyed.
 */

class Room
{
    private bool $occupied = false;

    public function __construct(
        private string $type,    // 'Bedroom', 'Kitchen', 'Bathroom'
        private float  $areaSqft
    ) {
        echo "    [Room Created] {$this->type} ({$this->areaSqft} sqft)\n";
    }

    public function getType(): string    { return $this->type; }
    public function getArea(): float     { return $this->areaSqft; }

    public function occupy(): void
    {
        $this->occupied = true;
        echo "    {$this->type} is now occupied.\n";
    }

    public function isOccupied(): bool { return $this->occupied; }

    public function __destruct()
    {
        echo "    [Room Destroyed] {$this->type}\n";
    }
}

class House
{
    private array  $rooms = [];
    private string $address;

    // Composition — House CREATES and OWNS its Rooms
    public function __construct(string $address, array $roomConfigs)
    {
        $this->address = $address;
        echo "  Building house at: {$address}\n";

        // Rooms are created internally — they belong to this House
        foreach ($roomConfigs as [$type, $area]) {
            $this->rooms[] = new Room($type, $area);  // Room lives inside House
        }
    }

    public function listRooms(): void
    {
        echo "  Rooms in {$this->address}:\n";
        $totalArea = 0;
        foreach ($this->rooms as $room) {
            $status = $room->isOccupied() ? 'occupied' : 'vacant';
            echo "    → {$room->getType()} — {$room->getArea()} sqft [{$status}]\n";
            $totalArea += $room->getArea();
        }
        echo "  Total area: {$totalArea} sqft\n";
    }

    public function occupyRoom(string $type): void
    {
        foreach ($this->rooms as $room) {
            if ($room->getType() === $type) {
                $room->occupy();
                return;
            }
        }
        echo "  Room '{$type}' not found.\n";
    }

    public function __destruct()
    {
        echo "  [House Destroyed] {$this->address}\n";
        // Rooms will be destroyed automatically as they go out of scope
    }
}

echo "\n--- 3. Composition ---\n";

$house = new House('123 Baker Street', [
    ['Bedroom',   350.0],
    ['Kitchen',   200.0],
    ['Bathroom',  80.0],
    ['Living Room', 450.0],
]);

echo "\n";
$house->listRooms();
$house->occupyRoom('Bedroom');

echo "\n  Destroying house...\n";
unset($house);  // House destroyed → all Rooms destroyed too!
echo "  (Rooms no longer accessible)\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ QUICK REFERENCE TABLE                                           │
 * ├──────────────────┬───────────────┬────────────────┬────────────┤
 * │                  │ Association   │ Aggregation    │ Composition │
 * ├──────────────────┼───────────────┼────────────────┼────────────┤
 * │ UML symbol       │ plain arrow   │ open diamond ◇ │ filled ◆   │
 * │ Ownership        │ No            │ Weak (shares)  │ Strong     │
 * │ Lifecycle        │ Independent   │ Independent    │ Dependent  │
 * │ Parent deleted   │ Both survive  │ Child survives │ Child dies  │
 * │ Object created   │ Passed in     │ Passed in      │ Inside ctor │
 * │ Example          │ Teacher-Student│ Dept-Employee │ House-Room  │
 * └──────────────────┴───────────────┴────────────────┴────────────┘
 *
 * INTERVIEW QUESTIONS & ANSWERS
 * ─────────────────────────────
 * Q1: What is the difference between aggregation and composition?
 * A: Both are HAS-A relationships. In aggregation, the child can exist
 *    independently of the parent (Employee survives Department deletion).
 *    In composition, the child's lifecycle is completely dependent on the
 *    parent (Room cannot exist without House; destroying House destroys Rooms).
 *
 * Q2: How is composition different from inheritance?
 * A: Inheritance is IS-A (Dog IS-A Animal) — the child inherits all
 *    parent behavior. Composition is HAS-A (House HAS-A Room) — the
 *    parent delegates specific behavior to the owned object.
 *    "Favor composition over inheritance" — composition is more flexible.
 *
 * Q3: What is "favor composition over inheritance"?
 * A: A design principle recommending that instead of inheriting behavior,
 *    you build objects by composing them from smaller objects. This avoids
 *    tight coupling, fragile base class problems, and deep inheritance chains.
 *    Example: Instead of ElectricCar extends Car extends Vehicle (3 levels),
 *    ElectricCar can HAVE a Battery object (composition).
 *
 * PITFALLS:
 * ✗ Confusing aggregation and composition — ask "can part exist alone?"
 * ✗ Using inheritance for HAS-A relationships (e.g., Car extends Engine).
 * ✗ Deep nested composition becoming hard to test (inject deps instead).
 *
 * KEY TAKEAWAYS:
 * ✓ Association : uses-a, loosest coupling.
 * ✓ Aggregation : has-a (shared), parts are independent.
 * ✓ Composition : owns-a, parts are created and destroyed by parent.
 * ✓ All three avoid the rigidity of deep inheritance chains.
 */
