<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              DESIGN PATTERN #12 — TEMPLATE METHOD                ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Behavioral Pattern                                 ║
 * ║  DIFFICULTY : Easy–Medium                                        ║
 * ║  FREQUENCY  : ★★★★☆ (Common in backend architecture rounds)     ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: Multiple classes share the SAME algorithm skeleton,     │
 * │ but differ in specific steps.                                    │
 * │                                                                  │
 * │ Example: You generate CSV, JSON, and XML reports.               │
 * │ All three follow the SAME sequence:                              │
 * │   1. Open connection / prepare output                           │
 * │   2. Write header                                               │
 * │   3. Write each data row                                        │
 * │   4. Write footer                                               │
 * │   5. Close / return result                                      │
 * │                                                                  │
 * │ Without Template Method: Duplicate the 5-step structure in each │
 * │ class → code duplication + inconsistency risk.                  │
 * │                                                                  │
 * │ Template Method: Define the 5-step SKELETON in the base class   │
 * │ (as a `final` method). Subclasses override only specific steps. │
 * │                                                                  │
 * │ KEY INSIGHT: "Don't call us, we'll call you" (Hollywood Principle)│
 * │ The base class calls the subclass's methods, not vice versa.    │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ WHEN TO USE                                                      │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ ✓ Multiple classes share the same algorithm structure           │
 * │ ✓ You want to control which parts are extensible (hooks vs      │
 * │   required overrides)                                            │
 * │ ✓ Data processing pipelines (ETL, report gen, export)           │
 * │ ✗ Don't use if steps vary wildly across subclasses (use         │
 * │   Strategy instead — inject the full algorithm)                  │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  AbstractClass                                                   │
 * │  ├─ templateMethod() [FINAL]  ← skeleton, calls steps in order  │
 * │  │     calls: step1() → step2() → hook() → step3()              │
 * │  ├─ step1(): abstract          ← MUST override                  │
 * │  ├─ step2(): abstract          ← MUST override                  │
 * │  ├─ hook():  concrete (empty)  ← OPTIONAL override              │
 * │  └─ step3(): concrete          ← common logic, no override      │
 * │       │                                                           │
 * │       ├── ConcreteClassA  (overrides step1, step2)              │
 * │       └── ConcreteClassB  (overrides step1, step2, hook)        │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO WRITE TEMPLATE METHOD                      │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Identify the invariant algorithm structure               │
 * │ STEP 2: Create abstract base class with `final` templateMethod() │
 * │ STEP 3: Break algorithm into steps; abstract = must override,    │
 * │         protected = hook (optional), public = shared logic      │
 * │ STEP 4: Subclasses extend base and override only abstract/hooks │
 * │ STEP 5: Client calls templateMethod() on any subclass — the     │
 * │         skeleton runs with subclass-specific implementations     │
 * └─────────────────────────────────────────────────────────────────┘
 */

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 1: Report Generator (CSV, JSON, HTML)
// All share: prepare → header → rows → footer → finalize
// ═══════════════════════════════════════════════════════════════

// STEP 2: Abstract base class with template method
abstract class ReportGenerator
{
    /**
     * TEMPLATE METHOD — declared `final` so subclasses cannot
     * change the overall sequence, only specific steps.
     */
    final public function generate(array $data, string $title): string
    {
        $this->prepare();                   // STEP A: setup
        $output  = $this->writeHeader($title);  // STEP B: title/header row
        $output .= $this->writeRows($data);     // STEP C: data rows
        $output .= $this->writeFooter(count($data)); // STEP D: footer
        return $this->finalize($output);    // STEP E: post-process & return
    }

    // STEP 3: Abstract steps — subclasses MUST implement these
    abstract protected function writeHeader(string $title): string;
    abstract protected function writeRows(array $data): string;
    abstract protected function writeFooter(int $rowCount): string;

    // Hook — subclasses MAY override (optional extension point)
    protected function prepare(): void {}  // default: nothing

    // Shared logic — common across all formats, no override
    protected function finalize(string $output): string
    {
        // Shared: trim trailing whitespace and add generated timestamp
        return rtrim($output) . "\n<!-- Generated: " . date('Y-m-d H:i:s') . " -->\n";
    }
}

// STEP 4: Concrete subclasses — override only what differs

class CsvReportGenerator extends ReportGenerator
{
    protected function prepare(): void
    {
        // Could open a file handle in real code
        echo "  [CSV] Preparing CSV output stream\n";
    }

    protected function writeHeader(string $title): string
    {
        return "# Report: $title\n" . "id,name,email,amount\n";
    }

    protected function writeRows(array $data): string
    {
        $rows = '';
        foreach ($data as $row) {
            // Escape commas in values (CSV edge case)
            $rows .= implode(',', array_map(
                fn($v) => str_contains((string)$v, ',') ? "\"$v\"" : $v,
                $row
            )) . "\n";
        }
        return $rows;
    }

    protected function writeFooter(int $rowCount): string
    {
        return "# Total rows: $rowCount\n";
    }
}

class JsonReportGenerator extends ReportGenerator
{
    protected function writeHeader(string $title): string
    {
        // Start JSON structure
        return "{\n  \"report\": \"$title\",\n  \"data\": [\n";
    }

    protected function writeRows(array $data): string
    {
        $jsonRows = array_map(
            fn($row) => "    " . json_encode($row, JSON_UNESCAPED_UNICODE),
            $data
        );
        return implode(",\n", $jsonRows) . "\n";
    }

    protected function writeFooter(int $rowCount): string
    {
        return "  ],\n  \"total\": $rowCount\n}\n";
    }
}

class HtmlReportGenerator extends ReportGenerator
{
    protected function writeHeader(string $title): string
    {
        $escaped = htmlspecialchars($title, ENT_QUOTES);
        return "<table>\n  <caption>$escaped</caption>\n"
             . "  <tr><th>ID</th><th>Name</th><th>Email</th><th>Amount</th></tr>\n";
    }

    protected function writeRows(array $data): string
    {
        $html = '';
        foreach ($data as $row) {
            $cells = implode('', array_map(
                fn($v) => "    <td>" . htmlspecialchars((string)$v, ENT_QUOTES) . "</td>",
                $row
            ));
            $html .= "  <tr>\n$cells\n  </tr>\n";
        }
        return $html;
    }

    protected function writeFooter(int $rowCount): string
    {
        return "  <tfoot><tr><td colspan='4'>Total: $rowCount rows</td></tr></tfoot>\n</table>\n";
    }
}

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 2: Data Scraper / ETL Pipeline
// All scrapers: connect → fetchData → parseData → saveData
// ═══════════════════════════════════════════════════════════════

abstract class DataScraper
{
    /** Template method: the ETL pipeline */
    final public function scrape(string $source): array
    {
        echo "  Connecting to: $source\n";
        $raw    = $this->fetchData($source);    // Extract
        $parsed = $this->parseData($raw);        // Transform
        if ($this->shouldSave()) {               // Hook: save step is optional
            $this->saveData($parsed);
        }
        $this->cleanup();                        // Hook: cleanup
        return $parsed;
    }

    abstract protected function fetchData(string $source): string;
    abstract protected function parseData(string $raw): array;
    protected function saveData(array $data): void { /* default: no-op */ }
    protected function shouldSave(): bool { return false; } // Hook: opt-in
    protected function cleanup(): void    { /* default: nothing */ }
}

class JsonApiScraper extends DataScraper
{
    protected function fetchData(string $source): string
    {
        // Simulate HTTP GET
        echo "  [JsonScraper] GET $source\n";
        return '[{"id":1,"name":"Alice"},{"id":2,"name":"Bob"}]';
    }

    protected function parseData(string $raw): array
    {
        return json_decode($raw, true) ?? [];
    }

    protected function shouldSave(): bool { return true; }

    protected function saveData(array $data): void
    {
        echo "  [JsonScraper] Saving " . count($data) . " records to database\n";
    }
}

class CsvFileScraper extends DataScraper
{
    protected function fetchData(string $source): string
    {
        echo "  [CsvScraper] Reading file: $source\n";
        // Simulate file contents
        return "id,name\n1,Alice\n2,Bob\n3,Carol";
    }

    protected function parseData(string $raw): array
    {
        $lines  = explode("\n", trim($raw));
        $header = str_getcsv(array_shift($lines));
        return array_map(
            fn($line) => array_combine($header, str_getcsv($line)),
            $lines
        );
    }

    protected function cleanup(): void
    {
        echo "  [CsvScraper] File handle closed\n";
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────────────────────

echo "=== TEMPLATE METHOD PATTERN DEMO ===\n\n";

$data = [
    [1, 'Alice Smith',  'alice@example.com',  1999.99],
    [2, 'Bob Jones',   'bob@example.com',    3499.00],
    [3, 'Carol, M.',   'carol@example.com',  799.50],   // CSV edge case: comma in name
];

$generators = [
    'CSV'  => new CsvReportGenerator(),
    'JSON' => new JsonReportGenerator(),
    'HTML' => new HtmlReportGenerator(),
];

foreach ($generators as $format => $generator) {
    echo "--- $format Report ---\n";
    $output = $generator->generate($data, 'Monthly Sales');
    // Show first 200 chars of output to keep demo concise
    echo substr($output, 0, 300) . (strlen($output) > 300 ? "...\n" : '') . "\n";
}

echo "--- ETL Pipeline ---\n";
$jsonScraper = new JsonApiScraper();
$records = $jsonScraper->scrape('https://api.example.com/users');
echo "  Parsed " . count($records) . " records\n\n";

$csvScraper = new CsvFileScraper();
$csvRecords = $csvScraper->scrape('/data/users.csv');
echo "  CSV records: " . count($csvRecords) . "\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the Template Method pattern?                         │
 * │ A: Defines the skeleton of an algorithm in a base class, with  │
 * │    some steps deferred to subclasses. Subclasses can override  │
 * │    specific steps without changing the overall structure.        │
 * │                                                                  │
 * │ Q2: Why declare the template method `final`?                     │
 * │ A: To prevent subclasses from overriding the skeleton itself.   │
 * │    The algorithm's ORDER of steps is the invariant part. Only   │
 * │    the individual steps can vary (via abstract/hook methods).    │
 * │                                                                  │
 * │ Q3: What is a "Hook" method?                                     │
 * │ A: A concrete method in the base class with a default (usually  │
 * │    empty) implementation. Subclasses MAY override it to add     │
 * │    optional behavior at that point in the algorithm.             │
 * │    Example: shouldSave() returns false by default — subclass    │
 * │    overrides to true to opt into the save step.                  │
 * │                                                                  │
 * │ Q4: Template Method vs Strategy — how to choose?                 │
 * │ A: Template Method: uses INHERITANCE (subclass overrides steps). │
 * │    Compile-time decision. Best when algorithm skeleton is fixed.│
 * │    Strategy: uses COMPOSITION (inject different strategy object).│
 * │    Runtime decision. Best when full algorithm needs swapping.   │
 * │    Rule of thumb: prefer Strategy (composition > inheritance).   │
 * │                                                                  │
 * │ Q5: What is the Hollywood Principle?                             │
 * │ A: "Don't call us, we'll call you." The parent class CALLS the  │
 * │    subclass's methods (not the other way around). The framework │
 * │    controls the flow; your subclass provides the specifics.     │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Hook methods need sensible defaults (empty/false/null)         │
 * │ ✓ Be careful with abstract steps — too many → forces subclass   │
 * │   to implement too much                                           │
 * │ ✓ Template method should be `final` to protect the invariant    │
 * └─────────────────────────────────────────────────────────────────┘
 */
