<?php
/**
 * B20. PRINTER QUEUE
 * ============================================================
 * PROBLEM: Printer spooler with priority queue, job cancellation,
 * and multiple printers.
 *
 * PATTERNS:
 *  - Command   : PrintJob encapsulates print request
 *  - Strategy  : JobScheduler (FCFS, priority)
 * ============================================================
 */

enum PrintJobStatus: string { case PENDING='Pending'; case PRINTING='Printing'; case DONE='Done'; case CANCELLED='Cancelled'; }
enum PrintPriority: int     { case LOW=1; case NORMAL=2; case HIGH=3; case URGENT=4; }

// ─── Print Job (Command) ──────────────────────────────────────
class PrintJob {
    public readonly string  $jobId;
    public PrintJobStatus   $status   = PrintJobStatus::PENDING;
    public ?string          $printerAssigned = null;

    public function __construct(
        public readonly string       $ownerId,
        public readonly string       $documentName,
        public readonly int          $pages,
        public readonly PrintPriority $priority  = PrintPriority::NORMAL,
        public readonly bool         $isColor    = false
    ) {
        $this->jobId = uniqid('JOB-');
    }

    public function execute(Printer $printer): void {
        $this->status          = PrintJobStatus::PRINTING;
        $this->printerAssigned = $printer->printerId;
        echo "  🖨️  [{$printer->printerId}] Printing: {$this->documentName} ({$this->pages}p"
           . ($this->isColor ? ', Color' : ', B&W') . ")\n";
        $this->status = PrintJobStatus::DONE;
        echo "  ✓ Done: {$this->documentName}\n";
    }

    public function cancel(): void {
        if ($this->status === PrintJobStatus::PENDING) {
            $this->status = PrintJobStatus::CANCELLED;
            echo "  ✗ Cancelled: {$this->documentName}\n";
        } else {
            echo "  ✗ Cannot cancel [{$this->status->value}] job\n";
        }
    }
}

// ─── Printer ──────────────────────────────────────────────────
class Printer {
    private bool  $busy      = false;
    private bool  $colorSupport;

    public function __construct(
        public readonly string $printerId,
        public readonly string $model,
        bool                   $colorSupport = true
    ) {
        $this->colorSupport = $colorSupport;
    }

    public function isAvailable(): bool         { return !$this->busy; }
    public function supportsColor(): bool       { return $this->colorSupport; }

    public function print(PrintJob $job): void {
        $this->busy = true;
        $job->execute($this);
        $this->busy = false;
    }
}

// ─── Scheduling Strategy ──────────────────────────────────────
interface JobSchedulingStrategy {
    public function next(array &$jobs): ?PrintJob;
}

class FIFOScheduling implements JobSchedulingStrategy {
    public function next(array &$jobs): ?PrintJob {
        foreach ($jobs as $i => $job) {
            if ($job->status === PrintJobStatus::PENDING) { unset($jobs[$i]); return $job; }
        }
        return null;
    }
}

class PriorityJobScheduling implements JobSchedulingStrategy {
    public function next(array &$jobs): ?PrintJob {
        $pending = array_filter($jobs, fn($j) => $j->status === PrintJobStatus::PENDING);
        if (empty($pending)) return null;
        usort($pending, fn($a, $b) => $b->priority->value - $a->priority->value);
        $chosen = reset($pending);
        $jobs = array_values(array_filter($jobs, fn($j) => $j->jobId !== $chosen->jobId));
        return $chosen;
    }
}

// ─── Print Spooler ─────────────────────────────────────────────
class PrintSpooler {
    /** @var PrintJob[] */
    private array $queue    = [];
    /** @var Printer[] */
    private array $printers = [];

    public function __construct(private JobSchedulingStrategy $scheduler) {}

    public function addPrinter(Printer $p): void { $this->printers[] = $p; }

    public function submitJob(PrintJob $job): void {
        $this->queue[] = $job;
        echo "  Queued: {$job->documentName} [priority={$job->priority->name}]\n";
    }

    public function cancelJob(string $jobId): void {
        foreach ($this->queue as $job) {
            if ($job->jobId === $jobId) { $job->cancel(); return; }
        }
        echo "  Job $jobId not found\n";
    }

    public function processAll(): void {
        echo "\n  --- Processing queue ---\n";
        while (!empty($this->queue)) {
            $job     = $this->scheduler->next($this->queue);
            if (!$job) break;
            if ($job->status === PrintJobStatus::CANCELLED) continue;

            $printer = $this->findAvailablePrinter($job);
            if (!$printer) { echo "  No available printer for {$job->documentName}\n"; break; }
            $printer->print($job);
        }
        echo "  --- Queue empty ---\n";
    }

    private function findAvailablePrinter(PrintJob $job): ?Printer {
        foreach ($this->printers as $p) {
            if ($p->isAvailable() && (!$job->isColor || $p->supportsColor())) return $p;
        }
        return null;
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== B20. Printer Queue ===\n\n";

$spooler = new PrintSpooler(new PriorityJobScheduling());
$spooler->addPrinter(new Printer('P1', 'HP LaserJet', true));

$j1 = new PrintJob('alice', 'Report.pdf',    10, PrintPriority::NORMAL, false);
$j2 = new PrintJob('bob',   'Presentation.pptx', 30, PrintPriority::URGENT, true);
$j3 = new PrintJob('alice', 'Invoice.pdf',    2, PrintPriority::LOW, false);
$j4 = new PrintJob('admin', 'Policy.docx',   50, PrintPriority::HIGH, false);

$spooler->submitJob($j1);
$spooler->submitJob($j2);
$spooler->submitJob($j3);
$spooler->submitJob($j4);

$spooler->cancelJob($j3->jobId); // Cancel low priority

$spooler->processAll();
