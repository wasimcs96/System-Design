<?php
/**
 * B4. TASK SCHEDULER (Cron-like)
 * ============================================================
 * PROBLEM: Schedule tasks to run at intervals. Support priority,
 * retries, and task dependencies.
 *
 * PATTERNS:
 *  - Command  : Task encapsulates work + retry logic
 *  - Strategy : SchedulingPolicy (priority, FIFO)
 * ============================================================
 */

enum TaskStatus: string { case PENDING='Pending'; case RUNNING='Running'; case DONE='Done'; case FAILED='Failed'; }
enum TaskPriority: int  { case LOW=1; case NORMAL=2; case HIGH=3; case CRITICAL=4; }

// ─── Task (Command Pattern) ────────────────────────────────────
class Task {
    public readonly string $taskId;
    public TaskStatus      $status       = TaskStatus::PENDING;
    public int             $retryCount   = 0;
    public ?string         $errorMessage = null;
    public ?float          $scheduledAt  = null;

    /**
     * @param \Closure  $work          Callable work to execute
     * @param TaskPriority $priority   Execution priority
     * @param int       $maxRetries    Auto-retry on failure
     * @param string[]  $dependencies  taskIds that must complete first
     */
    public function __construct(
        public readonly string      $name,
        private readonly \Closure   $work,
        public readonly TaskPriority $priority    = TaskPriority::NORMAL,
        public readonly int          $maxRetries  = 0,
        public readonly array        $dependencies = []
    ) {
        $this->taskId      = uniqid('T-');
        $this->scheduledAt = microtime(true);
    }

    public function execute(): bool {
        $this->status = TaskStatus::RUNNING;
        try {
            ($this->work)($this);
            $this->status = TaskStatus::DONE;
            echo "  ✓ [{$this->name}] completed (attempt {$this->retryCount})\n";
            return true;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->retryCount++;
            if ($this->retryCount <= $this->maxRetries) {
                $this->status = TaskStatus::PENDING; // Re-queue
                echo "  ↺ [{$this->name}] failed: {$e->getMessage()} — retry {$this->retryCount}/{$this->maxRetries}\n";
            } else {
                $this->status = TaskStatus::FAILED;
                echo "  ✗ [{$this->name}] permanently failed: {$e->getMessage()}\n";
            }
            return false;
        }
    }
}

// ─── Scheduling Strategy ───────────────────────────────────────
interface SchedulingStrategy {
    /** Sort the queue in execution order */
    public function sort(array &$tasks): void;
}

class PriorityScheduling implements SchedulingStrategy {
    public function sort(array &$tasks): void {
        usort($tasks, fn($a, $b) => $b->priority->value - $a->priority->value);
    }
}

class FIFOScheduling implements SchedulingStrategy {
    public function sort(array &$tasks): void {
        usort($tasks, fn($a, $b) => $a->scheduledAt <=> $b->scheduledAt);
    }
}

// ─── Task Scheduler ────────────────────────────────────────────
class TaskScheduler {
    /** @var Task[] */
    private array $queue = [];
    /** @var array<string,TaskStatus> taskId → status for dependency checks */
    private array $completed = [];

    public function __construct(private SchedulingStrategy $strategy) {}

    public function schedule(Task $task): void {
        $this->queue[] = $task;
        echo "  Scheduled: [{$task->name}] priority={$task->priority->name}\n";
    }

    public function run(): void {
        echo "\n  --- Running scheduler ---\n";
        $iterations = 0;
        while (!empty($this->queue) && $iterations++ < 20) {
            // Sort by strategy each iteration
            $this->strategy->sort($this->queue);
            $task = array_shift($this->queue);

            // Check dependencies
            foreach ($task->dependencies as $depId) {
                if (!isset($this->completed[$depId])) {
                    echo "  ⏳ [{$task->name}] waiting for dependency $depId\n";
                    $this->queue[] = $task; // Re-queue at end
                    continue 2;
                }
            }

            $task->execute();
            if ($task->status === TaskStatus::DONE) {
                $this->completed[$task->taskId] = $task->status;
            } elseif ($task->status === TaskStatus::PENDING) {
                // Retry: re-add to queue
                $this->queue[] = $task;
            }
        }
        echo "  --- Scheduler finished ---\n";
    }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== B4. Task Scheduler ===\n\n";

$scheduler = new TaskScheduler(new PriorityScheduling());

$t1 = new Task('FetchData',     fn($t) => null, TaskPriority::HIGH);
$t2 = new Task('ProcessData',   fn($t) => null, TaskPriority::NORMAL,  0, [$t1->taskId]);
$t3 = new Task('SendEmail',     fn($t) => null, TaskPriority::LOW);
$t4 = new Task('RetryableTask', function($task) {
    static $count = 0;
    if (++$count < 2) throw new \RuntimeException("Transient error");
}, TaskPriority::CRITICAL, 2);

$scheduler->schedule($t3);
$scheduler->schedule($t2);
$scheduler->schedule($t1);
$scheduler->schedule($t4);

$scheduler->run();
