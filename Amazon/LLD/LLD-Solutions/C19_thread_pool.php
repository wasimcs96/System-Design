<?php
/**
 * C19. THREAD POOL (Simulated in PHP)
 * ============================================================
 * PROBLEM: Manage a pool of workers to process tasks
 * concurrently. PHP is single-threaded but we can simulate
 * the design with synchronous execution and task queues.
 * (Real implementation would use ReactPHP/Swoole/pcntl)
 *
 * PATTERNS:
 *  - Command  : Task encapsulates work
 *  - Strategy : TaskSchedulingStrategy (FIFO/Priority)
 * ============================================================
 */

enum TaskPriority: int { case CRITICAL=1; case HIGH=2; case NORMAL=3; case LOW=4; }
enum TaskState: string { case QUEUED='Queued'; case RUNNING='Running'; case DONE='Done'; case FAILED='Failed'; }

// ─── Task (Command) ───────────────────────────────────────────
class PoolTask {
    public readonly string $taskId;
    public TaskState       $state    = TaskState::QUEUED;
    public mixed           $result   = null;
    public ?string         $error    = null;
    public float           $startedAt  = 0.0;
    public float           $finishedAt = 0.0;

    public function __construct(
        public readonly string       $name,
        public readonly \Closure     $callable,
        public readonly TaskPriority $priority = TaskPriority::NORMAL,
        public readonly int          $timeoutMs = 5000
    ) {
        $this->taskId = uniqid('TASK-');
    }
}

// ─── Worker ───────────────────────────────────────────────────
class PoolWorker {
    private bool        $busy    = false;
    private ?PoolTask   $current = null;

    public function __construct(public readonly string $workerId) {}

    public function isBusy(): bool { return $this->busy; }

    public function submit(PoolTask $task): void {
        $this->busy    = true;
        $this->current = $task;
        $task->state   = TaskState::RUNNING;
        $task->startedAt = microtime(true);
        echo "  [{$this->workerId}] Running: {$task->name}\n";

        try {
            $task->result     = ($task->callable)();
            $task->state      = TaskState::DONE;
            echo "  [{$this->workerId}] ✓ Done: {$task->name}" . (isset($task->result) ? " → {$task->result}" : "") . "\n";
        } catch (\Throwable $e) {
            $task->state = TaskState::FAILED;
            $task->error = $e->getMessage();
            echo "  [{$this->workerId}] ✗ Failed: {$task->name} | {$e->getMessage()}\n";
        }

        $task->finishedAt = microtime(true);
        $this->busy       = false;
        $this->current    = null;
    }
}

// ─── Thread Pool ──────────────────────────────────────────────
class ThreadPool {
    /** @var PoolWorker[] */
    private array    $workers;
    /** @var PoolTask[] */
    private array    $queue    = [];
    /** @var PoolTask[] */
    private array    $done     = [];
    private bool     $shutdown = false;

    public function __construct(int $poolSize = 4) {
        $this->workers = array_map(
            fn($i) => new PoolWorker("Worker-{$i}"),
            range(1, $poolSize)
        );
    }

    public function submit(PoolTask $task): void {
        if ($this->shutdown) throw new \RuntimeException("ThreadPool is shut down");
        $this->queue[] = $task;
        echo "  [Pool] Queued: {$task->name} (priority={$task->priority->name})\n";
    }

    /** Process all queued tasks (simulated — in real PHP use event loop) */
    public function execute(): void {
        // Sort by priority
        usort($this->queue, fn($a, $b) => $a->priority->value <=> $b->priority->value);

        while (!empty($this->queue)) {
            foreach ($this->workers as $worker) {
                if (!$worker->isBusy() && !empty($this->queue)) {
                    $task = array_shift($this->queue);
                    $worker->submit($task);
                    $this->done[] = $task;
                }
            }
        }
    }

    public function shutdown(): void {
        $this->shutdown = true;
        echo "  [Pool] Shutdown — {$this->completedCount()} tasks completed\n";
    }

    public function completedCount(): int {
        return count(array_filter($this->done, fn($t) => $t->state === TaskState::DONE));
    }

    public function failedCount(): int {
        return count(array_filter($this->done, fn($t) => $t->state === TaskState::FAILED));
    }

    public function getStats(): array {
        $total = count($this->done);
        $durations = array_map(fn($t) => $t->finishedAt - $t->startedAt, array_filter($this->done, fn($t) => $t->finishedAt > 0));
        return [
            'total'     => $total,
            'completed' => $this->completedCount(),
            'failed'    => $this->failedCount(),
            'avg_ms'    => $total > 0 ? round((array_sum($durations) / max(1, $total)) * 1000, 2) : 0,
        ];
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C19. Thread Pool (Simulated) ===\n\n";

$pool = new ThreadPool(3);

$tasks = [
    ['FetchUserData',   TaskPriority::HIGH,     fn() => "user:alice"],
    ['ProcessPayment',  TaskPriority::CRITICAL,  fn() => "payment:ok"],
    ['SendEmail',       TaskPriority::NORMAL,    fn() => "email:sent"],
    ['GenerateReport',  TaskPriority::LOW,       fn() => "report:pdf"],
    ['ResizeImage',     TaskPriority::NORMAL,    fn() => "img:256x256"],
    ['FailingTask',     TaskPriority::NORMAL,    function() { throw new \RuntimeException("Service down"); }],
];

foreach ($tasks as [$name, $prio, $callable]) {
    $pool->submit(new PoolTask($name, $callable, $prio));
}

echo "\n--- Executing tasks ---\n";
$pool->execute();

$stats = $pool->getStats();
echo "\n--- Stats ---\n";
echo "Total: {$stats['total']} | Completed: {$stats['completed']} | Failed: {$stats['failed']} | Avg: {$stats['avg_ms']}ms\n";
$pool->shutdown();
