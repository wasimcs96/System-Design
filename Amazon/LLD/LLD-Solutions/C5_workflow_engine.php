<?php
/**
 * C5. WORKFLOW ENGINE (State Machine)
 * ============================================================
 * PROBLEM: Define workflows as state machines with transitions,
 * conditions, and actions. Model order processing pipeline.
 *
 * PATTERNS:
 *  - State Machine : Explicit transitions with guards + actions
 *  - Command       : Transition actions
 * ============================================================
 */

// ─── Workflow State ───────────────────────────────────────────
class WorkflowState {
    /** @var WorkflowTransition[] */
    private array $transitions = [];
    /** @var callable[] */
    private array $onEnterActions = [];
    /** @var callable[] */
    private array $onExitActions  = [];

    public function __construct(
        public readonly string $name,
        public readonly bool   $isFinal = false
    ) {}

    public function addTransition(WorkflowTransition $t): void { $this->transitions[] = $t; }
    public function onEnter(callable $action): void { $this->onEnterActions[] = $action; }
    public function onExit(callable $action): void  { $this->onExitActions[]  = $action; }

    /** Find valid transition for given event + context */
    public function getTransition(string $event, array $ctx): ?WorkflowTransition {
        foreach ($this->transitions as $t) {
            if ($t->event === $event && ($t->guard === null || ($t->guard)($ctx))) return $t;
        }
        return null;
    }

    public function enter(array $ctx): void {
        foreach ($this->onEnterActions as $action) $action($ctx);
    }

    public function exit(array $ctx): void {
        foreach ($this->onExitActions as $action) $action($ctx);
    }
}

// ─── Workflow Transition ──────────────────────────────────────
class WorkflowTransition {
    public readonly ?\Closure $guard;
    public readonly ?\Closure $action;

    public function __construct(
        public readonly string $event,
        public readonly string $targetState,
        ?callable $guard  = null,
        ?callable $action = null
    ) {
        $this->guard  = $guard  !== null ? \Closure::fromCallable($guard)  : null;
        $this->action = $action !== null ? \Closure::fromCallable($action) : null;
    }
}

// ─── Workflow Definition ──────────────────────────────────────
class Workflow {
    /** @var array<string,WorkflowState> name → state */
    private array $states = [];

    public function __construct(public readonly string $name) {}

    public function addState(WorkflowState $state): void { $this->states[$state->name] = $state; }
    public function getState(string $name): ?WorkflowState { return $this->states[$name] ?? null; }
}

// ─── Workflow Instance ────────────────────────────────────────
class WorkflowInstance {
    private WorkflowState $currentState;
    private array         $history = [];
    private array         $context = [];

    public function __construct(
        private Workflow $workflow,
        string           $initialState,
        array            $context = []
    ) {
        $this->currentState = $workflow->getState($initialState)
            ?? throw new \InvalidArgumentException("State '$initialState' not found");
        $this->context = $context;
        $this->currentState->enter($this->context);
        $this->history[] = $initialState;
        echo "  [Workflow] Started in state: {$initialState}\n";
    }

    public function trigger(string $event, array $extra = []): bool {
        $this->context = array_merge($this->context, $extra);
        $transition = $this->currentState->getTransition($event, $this->context);

        if (!$transition) {
            echo "  ✗ No valid transition for event '{$event}' in state '{$this->currentState->name}'\n";
            return false;
        }

        $nextState = $this->workflow->getState($transition->targetState);
        if (!$nextState) {
            echo "  ✗ Target state '{$transition->targetState}' not found\n"; return false;
        }

        // Run transition action
        if ($transition->action) ($transition->action)($this->context);

        // Exit current, enter next
        $this->currentState->exit($this->context);
        $this->currentState = $nextState;
        $nextState->enter($this->context);
        $this->history[] = $nextState->name;

        echo "  [Workflow] {$event} → {$nextState->name}\n";
        return true;
    }

    public function getCurrentState(): string  { return $this->currentState->name; }
    public function isFinal(): bool            { return $this->currentState->isFinal; }
    public function getHistory(): array        { return $this->history; }
}

// ─── Order Workflow Example ────────────────────────────────────
function buildOrderWorkflow(): Workflow {
    $wf = new Workflow('OrderProcessing');

    $pending   = new WorkflowState('Pending');
    $payment   = new WorkflowState('PaymentPending');
    $confirmed = new WorkflowState('Confirmed');
    $shipping  = new WorkflowState('Shipping');
    $delivered = new WorkflowState('Delivered', true);  // Final state
    $cancelled = new WorkflowState('Cancelled',  true); // Final state

    // Actions on enter
    $confirmed->onEnter(fn($ctx) => print("    ✉ Order confirmed email sent\n"));
    $shipping->onEnter( fn($ctx) => print("    🚚 Shipping label created\n"));
    $delivered->onEnter(fn($ctx) => print("    ✓ Delivery confirmed\n"));

    // Transitions
    $pending->addTransition(new WorkflowTransition('submit', 'PaymentPending'));
    $payment->addTransition(new WorkflowTransition('pay_success', 'Confirmed',
        null,
        fn($ctx) => print("    💳 Payment of ₹{$ctx['amount']} processed\n")
    ));
    $payment->addTransition(new WorkflowTransition('pay_fail', 'Cancelled'));
    $confirmed->addTransition(new WorkflowTransition('ship', 'Shipping'));
    $confirmed->addTransition(new WorkflowTransition('cancel', 'Cancelled'));
    $shipping->addTransition(new WorkflowTransition('deliver', 'Delivered'));

    foreach ([$pending, $payment, $confirmed, $shipping, $delivered, $cancelled] as $s) {
        $wf->addState($s);
    }
    return $wf;
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C5. Workflow Engine (Order Processing) ===\n\n";

$workflow = buildOrderWorkflow();

echo "--- Happy path ---\n";
$order = new WorkflowInstance($workflow, 'Pending', ['orderId' => 'ORD-001', 'amount' => 1299]);
$order->trigger('submit');
$order->trigger('pay_success');
$order->trigger('ship');
$order->trigger('deliver');
echo "Final state: " . $order->getCurrentState() . " | Is final: " . ($order->isFinal() ? 'Yes' : 'No') . "\n";
echo "History: " . implode(' → ', $order->getHistory()) . "\n";

echo "\n--- Payment failure path ---\n";
$order2 = new WorkflowInstance($workflow, 'Pending', ['orderId' => 'ORD-002', 'amount' => 500]);
$order2->trigger('submit');
$order2->trigger('pay_fail');
echo "Final state: " . $order2->getCurrentState() . "\n";
