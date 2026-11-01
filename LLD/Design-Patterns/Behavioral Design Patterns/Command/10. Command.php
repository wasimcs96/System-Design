<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║              DESIGN PATTERN #10 — COMMAND                        ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  CATEGORY   : Behavioral Pattern                                 ║
 * ║  DIFFICULTY : Medium                                             ║
 * ║  FREQUENCY  : ★★★★★ (Google, Amazon, Flipkart ask this)         ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ PROBLEM STATEMENT                                                │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Problem: You need to:                                            │
 * │  1. Parameterize objects with operations (pass actions around)   │
 * │  2. Queue or schedule operations for later execution             │
 * │  3. Support UNDO / REDO operations                               │
 * │  4. Log all operations for audit trail                           │
 * │  5. Support transactional behavior (rollback on failure)         │
 * │                                                                  │
 * │ Without Command: The UI button directly calls editor.boldText()  │
 * │  → Can't undo it, can't queue it, can't log it easily.          │
 * │                                                                  │
 * │ With Command: Wrap "bold this text" in a BoldCommand object.     │
 * │  → Call execute() to do it, undo() to reverse it.               │
 * │  → Store it in history → unlimited undo/redo.                   │
 * │  → Put it in a queue → deferred execution.                      │
 * │                                                                  │
 * │ KEY INSIGHT: Command turns a METHOD CALL into an OBJECT.        │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ WHEN TO USE                                                      │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ ✓ Text editors, graphic tools — undo/redo history               │
 * │ ✓ Job/task queues — enqueue work to run later                   │
 * │ ✓ Macro recording — record sequence, replay on demand           │
 * │ ✓ Transactional systems — rollback on failure                   │
 * │ ✓ Audit logging — store what happened, by whom, when            │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ ASCII DIAGRAM                                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │                                                                  │
 * │  Client ──creates──► Command (interface)                        │
 * │                          ├── execute()                           │
 * │                          └── undo()                              │
 * │                               │                                  │
 * │  Invoker ──stores──► [cmd1, cmd2, cmd3]  ← command history      │
 * │  (CommandHistory)    calls execute()/undo()                      │
 * │                                                                  │
 * │  ConcreteCommand ──delegates──► Receiver (does actual work)      │
 * │                                                                  │
 * │  Flow: Client → creates Command → passes to Invoker              │
 * │        Invoker → calls cmd.execute() → Command → calls Receiver  │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ STEP-BY-STEP: HOW TO WRITE COMMAND PATTERN                      │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ STEP 1: Define Command interface with execute() and undo()       │
 * │ STEP 2: Create the Receiver — the class that does REAL work      │
 * │ STEP 3: Create ConcreteCommand — stores Receiver + parameters,  │
 * │         calls Receiver methods in execute() and undo()           │
 * │ STEP 4: Create Invoker — stores command history, calls execute()│
 * │         Provides undo() / redo() by walking the history          │
 * │ STEP 5: Client creates Command objects and hands to Invoker      │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ DRY RUN                                                          │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  editor.content = ""                                             │
 * │  execute(InsertCmd("Hello"))  → content = "Hello"               │
 * │  execute(InsertCmd(" World")) → content = "Hello World"         │
 * │  undo()                       → content = "Hello"               │
 * │  undo()                       → content = ""                    │
 * │  redo()                       → content = "Hello"               │
 * └─────────────────────────────────────────────────────────────────┘
 */

// ─── STEP 1: Command Interface ────────────────────────────────────────────────

interface Command
{
    public function execute(): void;
    public function undo(): void;
    public function getDescription(): string;
}

// ─── STEP 2: Receiver — Text Editor ──────────────────────────────────────────

/**
 * TextEditor is the RECEIVER.
 * It knows how to perform the actual operations.
 * Command classes delegate all real work to this.
 */
class TextEditor
{
    private string $content = '';

    public function insertText(string $text, int $position): void
    {
        // Insert $text at $position inside $this->content
        $this->content = substr($this->content, 0, $position)
            . $text
            . substr($this->content, $position);
    }

    public function deleteText(int $position, int $length): void
    {
        // Remove $length characters starting at $position
        $this->content = substr($this->content, 0, $position)
            . substr($this->content, $position + $length);
    }

    public function replaceText(int $position, int $length, string $newText): void
    {
        $this->deleteText($position, $length);
        $this->insertText($newText, $position);
    }

    public function getContent(): string { return $this->content; }
    public function getLength(): int     { return strlen($this->content); }
}

// ─── STEP 3: Concrete Commands ────────────────────────────────────────────────

/**
 * InsertTextCommand:
 *  execute() → inserts text at position
 *  undo()    → deletes the same text from same position
 */
class InsertTextCommand implements Command
{
    public function __construct(
        private TextEditor $editor,
        private string     $text,
        private int        $position
    ) {}

    public function execute(): void
    {
        $this->editor->insertText($this->text, $this->position);
    }

    public function undo(): void
    {
        // Reverse: delete exactly what we inserted
        $this->editor->deleteText($this->position, strlen($this->text));
    }

    public function getDescription(): string
    {
        return "Insert '{$this->text}' at position {$this->position}";
    }
}

/**
 * DeleteTextCommand:
 *  execute() → deletes text at position
 *  undo()    → re-inserts the deleted text at same position
 *  Note: We must capture the DELETED text before deleting it so we can restore.
 */
class DeleteTextCommand implements Command
{
    private string $deletedText = ''; // Capture for undo

    public function __construct(
        private TextEditor $editor,
        private int        $position,
        private int        $length
    ) {}

    public function execute(): void
    {
        // Save the text we're about to delete (needed for undo)
        $this->deletedText = substr($this->editor->getContent(), $this->position, $this->length);
        $this->editor->deleteText($this->position, $this->length);
    }

    public function undo(): void
    {
        // Re-insert the previously deleted text
        $this->editor->insertText($this->deletedText, $this->position);
    }

    public function getDescription(): string
    {
        return "Delete {$this->length} chars at position {$this->position}";
    }
}

/**
 * MacroCommand: A composite command that groups multiple commands.
 * execute() runs all in sequence; undo() reverses in reverse order.
 * This is the Command + Composite combo — powerful for batch operations.
 */
class MacroCommand implements Command
{
    private array $commands = [];

    public function addCommand(Command $cmd): void
    {
        $this->commands[] = $cmd;
    }

    public function execute(): void
    {
        // Execute all commands in order
        foreach ($this->commands as $cmd) {
            $cmd->execute();
        }
    }

    public function undo(): void
    {
        // Undo in REVERSE order (last action undone first)
        foreach (array_reverse($this->commands) as $cmd) {
            $cmd->undo();
        }
    }

    public function getDescription(): string
    {
        $descriptions = array_map(fn($c) => $c->getDescription(), $this->commands);
        return "Macro [" . implode(' → ', $descriptions) . "]";
    }
}

// ─── STEP 4: Invoker — CommandHistory (supports undo/redo) ───────────────────

/**
 * The INVOKER.
 * It doesn't know anything about specific commands — only the Command interface.
 * It stores the history for undo/redo and can also queue deferred commands.
 */
class CommandHistory
{
    private array $history = []; // Stack of executed commands
    private int   $cursor  = -1; // Points to last executed command

    /**
     * Execute a command and add to history.
     * If we undo and then execute a NEW command, discard the redo stack.
     */
    public function execute(Command $command): void
    {
        // Discard any commands after current cursor (can't redo after new action)
        if ($this->cursor < count($this->history) - 1) {
            $this->history = array_slice($this->history, 0, $this->cursor + 1);
        }

        $command->execute();
        $this->history[] = $command;
        $this->cursor++;

        echo "  [History] Executed: " . $command->getDescription() . "\n";
    }

    public function undo(): void
    {
        if ($this->cursor < 0) {
            echo "  [History] Nothing to undo\n";
            return;
        }

        $command = $this->history[$this->cursor];
        $command->undo();
        $this->cursor--;

        echo "  [History] Undid: " . $command->getDescription() . "\n";
    }

    public function redo(): void
    {
        if ($this->cursor >= count($this->history) - 1) {
            echo "  [History] Nothing to redo\n";
            return;
        }

        $this->cursor++;
        $command = $this->history[$this->cursor];
        $command->execute();

        echo "  [History] Redid: " . $command->getDescription() . "\n";
    }

    public function getHistoryLog(): array
    {
        return array_map(fn($c) => $c->getDescription(), $this->history);
    }
}

// ═══════════════════════════════════════════════════════════════
// EXAMPLE 2: Bank Transaction Commands (with rollback)
// ═══════════════════════════════════════════════════════════════

class BankAccount
{
    private float $balance;
    private array $ledger = [];

    public function __construct(private string $id, float $initialBalance)
    {
        $this->balance = $initialBalance;
    }

    public function credit(float $amount, string $ref): void
    {
        $this->balance    += $amount;
        $this->ledger[]    = "+$amount ($ref)";
    }

    public function debit(float $amount, string $ref): void
    {
        if ($this->balance < $amount) {
            throw new \RuntimeException("Insufficient funds: balance={$this->balance}, requested=$amount");
        }
        $this->balance    -= $amount;
        $this->ledger[]    = "-$amount ($ref)";
    }

    public function getBalance(): float  { return $this->balance; }
    public function getId(): string      { return $this->id; }
    public function getLedger(): array   { return $this->ledger; }
}

class DebitCommand implements Command
{
    private bool $executed = false;

    public function __construct(
        private BankAccount $account,
        private float       $amount,
        private string      $reference
    ) {}

    public function execute(): void
    {
        $this->account->debit($this->amount, $this->reference);
        $this->executed = true;
        echo "  [Debit]  ₹{$this->amount} from {$this->account->getId()} → ref:{$this->reference}\n";
    }

    public function undo(): void
    {
        if (!$this->executed) return;
        // Reverse: credit back the amount
        $this->account->credit($this->amount, "REVERSAL:{$this->reference}");
        echo "  [Undo Debit] ₹{$this->amount} reversed to {$this->account->getId()}\n";
    }

    public function getDescription(): string
    {
        return "Debit ₹{$this->amount} from {$this->account->getId()}";
    }
}

class CreditCommand implements Command
{
    private bool $executed = false;

    public function __construct(
        private BankAccount $account,
        private float       $amount,
        private string      $reference
    ) {}

    public function execute(): void
    {
        $this->account->credit($this->amount, $this->reference);
        $this->executed = true;
        echo "  [Credit] ₹{$this->amount} to {$this->account->getId()} → ref:{$this->reference}\n";
    }

    public function undo(): void
    {
        if (!$this->executed) return;
        $this->account->debit($this->amount, "REVERSAL:{$this->reference}");
        echo "  [Undo Credit] ₹{$this->amount} debited back from {$this->account->getId()}\n";
    }

    public function getDescription(): string
    {
        return "Credit ₹{$this->amount} to {$this->account->getId()}";
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────────────────────

echo "=== COMMAND PATTERN DEMO ===\n\n";

echo "--- Example 1: Text Editor with Undo/Redo ---\n";

$editor  = new TextEditor();
$history = new CommandHistory();

$history->execute(new InsertTextCommand($editor, "Hello", 0));
echo "  Content: '{$editor->getContent()}'\n";

$history->execute(new InsertTextCommand($editor, " World", 5));
echo "  Content: '{$editor->getContent()}'\n";

$history->execute(new InsertTextCommand($editor, "!", 11));
echo "  Content: '{$editor->getContent()}'\n";

echo "\n  -- Undo x2 --\n";
$history->undo();
echo "  Content: '{$editor->getContent()}'\n";
$history->undo();
echo "  Content: '{$editor->getContent()}'\n";

echo "\n  -- Redo x1 --\n";
$history->redo();
echo "  Content: '{$editor->getContent()}'\n";

echo "\n  -- Delete command --\n";
$history->execute(new DeleteTextCommand($editor, 0, 5));
echo "  Content: '{$editor->getContent()}'\n";

echo "\n  -- Undo delete --\n";
$history->undo();
echo "  Content: '{$editor->getContent()}'\n";

echo "\n  -- Macro Command (grouped operations) --\n";
$macro = new MacroCommand();
$macro->addCommand(new InsertTextCommand($editor, " PHP", 11));
$macro->addCommand(new InsertTextCommand($editor, " Rocks", 15));
$history->execute($macro);
echo "  Content: '{$editor->getContent()}'\n";
$history->undo(); // Undo both at once
echo "  Content after macro undo: '{$editor->getContent()}'\n";

echo "\n--- Example 2: Bank Transfer with Rollback ---\n";

$alice = new BankAccount('ACC-001', 10000.0);
$bob   = new BankAccount('ACC-002', 5000.0);

$txHistory = new CommandHistory();

// Transfer ₹2000 from Alice to Bob
$transferMacro = new MacroCommand();
$transferMacro->addCommand(new DebitCommand($alice,  2000.0, 'TXN-XYZ'));
$transferMacro->addCommand(new CreditCommand($bob, 2000.0, 'TXN-XYZ'));

try {
    $txHistory->execute($transferMacro);
    echo "  Alice: ₹{$alice->getBalance()} | Bob: ₹{$bob->getBalance()}\n";
} catch (\RuntimeException $e) {
    echo "  Transfer failed: {$e->getMessage()} — rolling back\n";
    $txHistory->undo(); // Entire macro undone atomically
}

echo "\n  -- Rollback transfer --\n";
$txHistory->undo();
echo "  Alice: ₹{$alice->getBalance()} | Bob: ₹{$bob->getBalance()}\n";

/**
 * ┌─────────────────────────────────────────────────────────────────┐
 * │ INTERVIEW QUESTIONS & ANSWERS                                    │
 * ├─────────────────────────────────────────────────────────────────┤
 * │ Q1: What is the Command pattern?                                 │
 * │ A: Encapsulates a request as an object (with execute/undo),     │
 * │    separating the request sender (Invoker) from the request      │
 * │    executor (Receiver). Enables undo/redo, queuing, logging,    │
 * │    and transactional rollback.                                   │
 * │                                                                  │
 * │ Q2: What are the four participants in Command pattern?           │
 * │ A: Command (interface: execute/undo), ConcreteCommand (wraps    │
 * │    Receiver + params), Receiver (does actual work), Invoker     │
 * │    (stores and calls commands).                                  │
 * │                                                                  │
 * │ Q3: How do you implement undo for a DELETE operation?            │
 * │ A: Before deleting, capture (save) the deleted content into     │
 * │    the command object. In undo(), re-insert the saved content.  │
 * │    The command object acts as a snapshot of the pre-state.       │
 * │                                                                  │
 * │ Q4: What is a Macro Command?                                     │
 * │ A: A Command that contains multiple sub-commands (Command +     │
 * │    Composite pattern). execute() runs all; undo() reverses all  │
 * │    in reverse order. Useful for batch/transaction operations.    │
 * │                                                                  │
 * │ Q5: How does Command differ from Strategy?                       │
 * │ A: Strategy: Different algorithms for the SAME task (sort data).│
 * │    Command: Different ACTIONS encapsulated as objects that can  │
 * │    be queued, undone, logged. They're about WHAT to do, not     │
 * │    HOW to do the same thing.                                     │
 * │                                                                  │
 * │ Q6: Real-world use cases?                                        │
 * │ A: Text editors (Ctrl+Z/Y), Git commits (each is a command),   │
 * │    Database transactions (BEGIN/ROLLBACK), Job queues (Laravel  │
 * │    Queue), UI action history, HTTP request retries.             │
 * │                                                                  │
 * │ EDGE CASES:                                                      │
 * │ ✓ Undo limit: cap history size to prevent memory issues          │
 * │ ✓ Non-reversible commands: some actions can't be undone (send   │
 * │   email) — document or throw in undo()                          │
 * │ ✓ Failed execute: don't add to history if execute() throws      │
 * │ ✓ Redo after new action: must discard redo stack                 │
 * └─────────────────────────────────────────────────────────────────┘
 */
