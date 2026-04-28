<?php
/**
 * C12. JOB QUEUE WITH RETRY & EXPONENTIAL BACKOFF
 * ============================================================
 * PROBLEM: Background job processing with priority, retry on
 * failure with exponential backoff, and dead-letter handling.
 *
 * PATTERNS:
 *  - Command  : Job encapsulates work unit
 *  - Strategy : SchedulingStrategy (priority vs FIFO)
 * ============================================================
 */

enum JobStatus: string { case QUEUED='Queued'; case RUNNING='Running'; case DONE='Done'; case FAILED='Failed'; case DLQ='DeadLetter'; }

// ─── Job (Command) ────────────────────────────────────────────
class Job {
    public readonly string $jobId;
    public JobStatus       $status    = JobStatus::QUEUED;
    public int             $attempts  = 0;
    public float           $nextRunAt;
    public ?string         $lastError = null;

    public function __construct(
        public readonly string   $name,
        public readonly \Closure $work,
        public readonly int      $priority    = 5,    // 1=highest, 10=lowest
        public readonly int      $maxAttempts = 3,
        public readonly int      $baseDelayMs = 1000  // Base for backoff
    ) {
        $this->jobId     = uniqid('JOB-');
        $this->nextRunAt = microtime(true);
    }

    /** Calculate next retry time with exponential backoff */
    public function scheduleRetry(): void {
        $delayMs         = $this->baseDelayMs * (2 ** ($this->attempts - 1)); // 1s, 2s, 4s...
        $this->nextRunAt = microtime(true) + ($delayMs / 1000.0);
    }
}

// ─── Dead Letter Queue ────────────────────────────────────────
class DeadLetterQueue {
    /** @var Job[] */
    private array $jobs = [];

    public function add(Job $job): void {
        $this->jobs[] = $job;
        echo "  ⚠️  DLQ: Job {$job->jobId} ({$job->name}) after {$job->attempts} attempts. Error: {$job->lastError}\n";
    }

    public function getJobs(): array { return $this->jobs; }
    public function size(): int      { return count($this->jobs); }
}

// ─── Worker Pool ──────────────────────────────────────────────
class JobWorker {
    public function __construct(public readonly string $workerId) {}

    public function process(Job $job): bool {
        $job->status   = JobStatus::RUNNING;
        $job->attempts++;
        echo "  [{$this->workerId}] Running '{$job->name}' (attempt {$job->attempts})\n";
        try {
            ($job->work)();
            $job->status = JobStatus::DONE;
            echo "  [{$this->workerId}] ✓ '{$job->name}' completed\n";
            return true;
        } catch (\Throwable $e) {
            $job->lastError = $e->getMessage();
            if ($job->attempts >= $job->maxAttempts) {
                $job->status = JobStatus::FAILED;
                echo "  [{$this->workerId}] ✗ '{$job->name}' failed permanently: {$e->getMessage()}\n";
            } else {
                $job->status = JobStatus::QUEUED;
                $job->scheduleRetry();
                echo "  [{$this->workerId}] ↺ '{$job->name}' will retry (next in " . round(($job->nextRunAt - microtime(true)) * 1000) . "ms)\n";
            }
            return false;
        }
    }
}

// ─── Job Queue ────────────────────────────────────────────────
class JobQueue {
    /** @var Job[] */
    private array          $queue  = [];
    private DeadLetterQueue $dlq;
    /** @var JobWorker[] */
    private array          $workers = [];

    public function __construct(int $workerCount = 2) {
        $this->dlq = new DeadLetterQueue();
        for ($i = 1; $i <= $workerCount; $i++) {
            $this->workers[] = new JobWorker("Worker-{$i}");
        }
    }

    public function dispatch(Job $job): void {
        $this->queue[] = $job;
        echo "  [Queue] Dispatched '{$job->name}' (priority={$job->priority})\n";
    }

    /** Process all ready jobs sorted by priority */
    public function run(): void {
        $iteration = 0;
        while (!empty($this->queue)) {
            $iteration++;
            if ($iteration > 20) break; // Safety limit

            $now = microtime(true);
            usort($this->queue, fn($a, $b) => $a->priority <=> $b->priority);
            $ready = array_filter($this->queue, fn($j) => $j->nextRunAt <= $now && $j->status === JobStatus::QUEUED);

            if (empty($ready)) {
                // Simulate time passage (move clocks forward)
                $next = min(array_map(fn($j) => $j->nextRunAt, $this->queue));
                foreach ($this->queue as $j) $j->nextRunAt -= ($next - $now + 0.001);
                continue;
            }

            foreach ($this->workers as $worker) {
                $job = array_shift($ready);
                if (!$job) break;
                $worker->process($job);
                if ($job->status === JobStatus::FAILED) {
                    $this->dlq->add($job);
                    $this->queue = array_values(array_filter($this->queue, fn($j) => $j->jobId !== $job->jobId));
                } elseif ($job->status === JobStatus::DONE) {
                    $this->queue = array_values(array_filter($this->queue, fn($j) => $j->jobId !== $job->jobId));
                }
            }
        }
    }

    public function getDLQ(): DeadLetterQueue { return $this->dlq; }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C12. Job Queue with Retry & Exponential Backoff ===\n\n";

$queue = new JobQueue(2);

$callCount = 0;
$queue->dispatch(new Job('SendEmail', function() {
    echo "    → Sending welcome email...\n";
}, priority: 1));

$attempts = 0;
$queue->dispatch(new Job('ProcessPayment', function() use (&$attempts) {
    $attempts++;
    if ($attempts < 3) throw new \RuntimeException("Payment gateway timeout");
    echo "    → Payment processed!\n";
}, priority: 2, maxAttempts: 3, baseDelayMs: 100));

$queue->dispatch(new Job('GenerateReport', function() {
    throw new \RuntimeException("Report service unavailable");
}, priority: 5, maxAttempts: 2, baseDelayMs: 100));

$queue->run();
echo "\nDLQ size: " . $queue->getDLQ()->size() . "\n";
