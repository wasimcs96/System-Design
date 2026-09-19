<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              DESIGN PATTERN #15 — COMPOSITE                      ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Structural Pattern                                 ║
 * ║  DIFFICULTY : Medium                                             ║
 * ║  FREQUENCY  : ★★★★☆ (File systems, org charts, UI trees)        ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: You have a TREE structure (files inside folders, menus  │
 * │ inside menus, employees in departments). You want to treat       │
 * │ individual items AND groups of items UNIFORMLY — same interface. │
 * │                                                                  │
 * │ Without Composite:                                               │
 * │   if ($node instanceof File)      { process file }              │
 * │   elseif ($node instanceof Dir)   { foreach children ... }      │
 * │   → Client must differentiate between leaf and container.        │
 * │   → New types require updating client code everywhere.           │
 * │                                                                  │
 * │ With Composite:                                                   │
 * │   $node->getSize()  ← works on BOTH File AND Directory          │
 * │   File.getSize()    → returns its own size                      │
 * │   Directory.getSize() → recursively sums children's sizes        │
 * │   Client treats them the same: $node->getSize() always works.   │
 * │                                                                  │
 * │ KEY RULE: Leaf and Composite implement the same Component        │
 * │ interface. Composite delegates to its children recursively.      │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Component (interface)                                           │
 * │  ├── Leaf           (no children — File, Employee)              │
 * │  └── Composite      (has children — Directory, Department)      │
 * │        ├─ add(Component)                                         │
 * │        ├─ remove(Component)                                      │
 * │        └─ children: Component[]                                  │
 * │                                                                  │
 * │  File System Tree:                                               │
 * │  /root [Dir, 1.9KB]                                              │
 * │  ├── /src [Dir, 1.2KB]                                           │
 * │  │   ├── app.php  [File, 800B]                                  │
 * │  │   └── utils.php [File, 400B]                                  │
 * │  └── README.md     [File, 700B]                                  │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO WRITE COMPOSITE                            │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Define Component interface (operations on leaf/composite)│
 * │ STEP 2: Create Leaf class — implements Component, no children    │
 * │ STEP 3: Create Composite class — implements Component, has      │
 * │         children array, add()/remove(), delegates to children    │
 * │ STEP 4: Composite methods RECURSE through children              │
 * │ STEP 5: Client uses Component interface — no type-checking needed│
 * └─────────────────────────────────────────────────────────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 1: File System (Files and Directories)
// ═══════════════════════════════════════════════════════════════

// STEP 1: Component interface — uniform interface for both leaf and composite
interface FileSystemNode
{
    public function getName(): string;
    public function getSize(): int;             // bytes
    public function getType(): string;
    public function display(int $depth = 0): void;
    public function search(string $pattern): array; // Find files matching name
}

// STEP 2: Leaf — File (no children)
class File implements FileSystemNode
{
    public function __construct(
        private string $name,
        private int    $size,        // bytes
        private string $extension
    ) {}

    public function getName(): string  { return $this->name; }
    public function getSize(): int     { return $this->size; }
    public function getType(): string  { return "File ({$this->extension})"; }

    public function display(int $depth = 0): void
    {
        $indent = str_repeat("  ", $depth);
        $sizeKb = round($this->size / 1024, 1);
        echo "{$indent}📄 {$this->name} ({$sizeKb} KB)\n";
    }

    // Leaf search: match this file's name against pattern
    public function search(string $pattern): array
    {
        return str_contains(strtolower($this->name), strtolower($pattern))
            ? [$this]
            : [];
    }
}

// STEP 3 & 4: Composite — Directory (has children, delegates recursively)
class Directory implements FileSystemNode
{
    private array $children = []; // FileSystemNode[]

    public function __construct(private string $name) {}

    // Manage children
    public function add(FileSystemNode $node): void
    {
        $this->children[] = $node;
    }

    public function remove(FileSystemNode $node): void
    {
        $this->children = array_filter(
            $this->children,
            fn($c) => $c !== $node
        );
    }

    public function getChildren(): array { return $this->children; }

    public function getName(): string { return $this->name; }
    public function getType(): string { return 'Directory'; }

    // STEP 4: RECURSIVE delegation — sum all children sizes
    public function getSize(): int
    {
        return array_sum(array_map(
            fn($child) => $child->getSize(),
            $this->children
        ));
    }

    // Recursive tree display
    public function display(int $depth = 0): void
    {
        $indent = str_repeat("  ", $depth);
        $sizeKb = round($this->getSize() / 1024, 1);
        echo "{$indent}📁 {$this->name}/ ({$sizeKb} KB)\n";
        foreach ($this->children as $child) {
            $child->display($depth + 1); // Recurse
        }
    }

    // Recursive search — aggregate results from all children
    public function search(string $pattern): array
    {
        $results = [];
        // Check own name
        if (str_contains(strtolower($this->name), strtolower($pattern))) {
            $results[] = $this;
        }
        // Recurse into children
        foreach ($this->children as $child) {
            $results = array_merge($results, $child->search($pattern));
        }
        return $results;
    }
}

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 2: Organization Hierarchy (Employees and Departments)
// ═══════════════════════════════════════════════════════════════

interface OrgNode
{
    public function getName(): string;
    public function getSalary(): float;
    public function display(int $depth = 0): void;
    public function getHeadcount(): int;
}

// Leaf: Individual Employee
class Employee implements OrgNode
{
    public function __construct(
        private string $name,
        private string $title,
        private float  $salary
    ) {}

    public function getName(): string     { return $this->name; }
    public function getSalary(): float    { return $this->salary; }
    public function getHeadcount(): int   { return 1; }

    public function display(int $depth = 0): void
    {
        $indent = str_repeat("  ", $depth);
        echo "{$indent}👤 {$this->name} ({$this->title}) — ₹" . number_format($this->salary) . "/mo\n";
    }
}

// Composite: Department (contains employees OR sub-departments)
class Department implements OrgNode
{
    private array $members = []; // OrgNode[] — employees OR sub-departments

    public function __construct(private string $name, private string $head) {}

    public function add(OrgNode $node): void    { $this->members[] = $node; }
    public function getName(): string           { return $this->name; }

    // Total salary budget = sum of all members (recursively)
    public function getSalary(): float
    {
        return array_sum(array_map(fn($m) => $m->getSalary(), $this->members));
    }

    // Total headcount = sum of all members' headcounts (recursively)
    public function getHeadcount(): int
    {
        return array_sum(array_map(fn($m) => $m->getHeadcount(), $this->members));
    }

    public function display(int $depth = 0): void
    {
        $indent  = str_repeat("  ", $depth);
        $budget  = number_format($this->getSalary());
        $heads   = $this->getHeadcount();
        echo "{$indent}🏢 {$this->name} (Head: {$this->head}) — Budget: ₹$budget/mo | {$heads} people\n";
        foreach ($this->members as $member) {
            $member->display($depth + 1);
        }
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────────────────────

echo "=== COMPOSITE PATTERN DEMO ===\n\n";

echo "--- Example 1: File System Tree ---\n\n";

// Build tree: STEP 5 — client builds tree, uses uniform interface
$root = new Directory('project');

$src = new Directory('src');
$src->add(new File('app.php',    81920, 'php'));   // 80 KB
$src->add(new File('helpers.php', 20480, 'php'));  // 20 KB
$src->add(new File('config.php',  5120, 'php'));   //  5 KB

$tests = new Directory('tests');
$tests->add(new File('AppTest.php',  30720, 'php')); // 30 KB
$tests->add(new File('HelperTest.php', 10240, 'php'));// 10 KB

$src->add($tests); // Nested directory

$root->add($src);
$root->add(new File('README.md',     7168, 'md'));   // 7 KB
$root->add(new File('composer.json', 2048, 'json')); // 2 KB
$root->add(new File('.gitignore',    1024, ''));      // 1 KB

// Display entire tree — client calls display() on root, works for all
$root->display();

echo "\n  Total project size: " . round($root->getSize() / 1024, 1) . " KB\n";
echo "  Total files/dirs: " . count($root->getChildren()) . " top-level items\n";

// Search — works recursively, same call on File or Directory
echo "\n  Search for 'php' files:\n";
$results = $root->search('.php');
foreach ($results as $node) {
    echo "  Found: {$node->getName()} ({$node->getType()})\n";
}

echo "\n--- Example 2: Organization Hierarchy ---\n\n";

// Engineering Department
$engineering = new Department('Engineering', 'Ravi');
$engineering->add(new Employee('Alice',  'Senior Engineer', 180000));
$engineering->add(new Employee('Bob',    'Engineer',        140000));

$backend = new Department('Backend Team', 'Carol');
$backend->add(new Employee('Dave',   'Lead Engineer',  160000));
$backend->add(new Employee('Eve',    'Engineer',       130000));
$backend->add(new Employee('Frank',  'Junior Engineer', 90000));

$frontend = new Department('Frontend Team', 'Grace');
$frontend->add(new Employee('Henry', 'Lead Engineer',  155000));
$frontend->add(new Employee('Iris',  'Engineer',       125000));

$engineering->add($backend);
$engineering->add($frontend);

// HR Department
$hr = new Department('HR', 'Jack');
$hr->add(new Employee('Karen', 'HR Manager',     120000));
$hr->add(new Employee('Leo',   'HR Specialist',   90000));

// Company
$company = new Department('Acme Corp', 'CEO');
$company->add($engineering);
$company->add($hr);

$company->display();
echo "\n  Total headcount: " . $company->getHeadcount() . "\n";
echo "  Total salary budget: ₹" . number_format($company->getSalary()) . "/mo\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the Composite pattern?                               │
 * │ A: Composes objects into tree structures to represent part-whole │
 * │    hierarchies. Composite lets clients treat individual objects  │
 * │    (Leaf) and compositions (Composite) uniformly via the same   │
 * │    Component interface.                                          │
 * │                                                                  │
 * │ Q2: What is the key benefit over using instanceof checks?        │
 * │ A: Without Composite, client must type-check:                   │
 * │      if ($node instanceof File) {...}                            │
 * │      elseif ($node instanceof Dir) {foreach...}                 │
 * │    With Composite: just call $node->getSize() — it works on     │
 * │    both. Adding a new node type doesn't break client code.       │
 * │                                                                  │
 * │ Q3: How does Composite implement recursive operations?           │
 * │ A: Directory.getSize() calls $child->getSize() for each child.  │
 * │    If child is a File, returns its own size. If it's a          │
 * │    Directory, it recurses again. This is natural tree traversal. │
 * │                                                                  │
 * │ Q4: When should Leaf implement add()/remove()?                   │
 * │ A: Two approaches:                                               │
 * │    "Transparency": Define add/remove in Component. Leaf throws  │
 * │    UnsupportedOperationException. Client doesn't need to check. │
 * │    "Safety": Only Composite has add/remove. Client must         │
 * │    cast to Composite before managing children. Safer, less clean.│
 * │                                                                  │
 * │ Q5: Real-world examples in big product companies?                │
 * │ A: File system (every OS) — File and Directory same interface.  │
 * │    DOM tree — Element and TextNode both are Node (getChildren). │
 * │    React component tree — components composed of components.    │
 * │    Organization chart — individual + department, same interface. │
 * │    Menu/sub-menu structures, expression trees in compilers.     │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Circular references: Directory A contains Directory B which   │
 * │   contains A → infinite recursion. Prevent with a visited set.  │
 * │ ✓ Empty composite: getSize() on empty dir returns 0 ✓           │
 * │ ✓ Ordering of children may matter (sorted directory listing)    │
 * └─────────────────────────────────────────────────────────────────┘
 */
