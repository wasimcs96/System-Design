<?php
/**
 * C11. COLLABORATIVE TEXT EDITOR (Operational Transformation simplified)
 * ============================================================
 * PROBLEM: Multiple users edit same document concurrently.
 * Resolve conflicts so all clients converge to same final state.
 *
 * APPROACH: Simplified OT — transform operations against
 * concurrent operations using position adjustments.
 *
 * PATTERNS:
 *  - Command  : Operation (Insert/Delete) are commands with undo
 *  - Observer : Broadcast changes to all connected clients
 * ============================================================
 */

enum OperationType: string { case INSERT='insert'; case DELETE='delete'; }

// ─── Operation (Command) ──────────────────────────────────────
class TextOperation {
    public readonly string $opId;
    public function __construct(
        public readonly OperationType $type,
        public readonly int           $position,  // Character index
        public readonly string        $char,       // For insert; empty for delete
        public readonly string        $clientId,
        public readonly int           $version     // Document version when op was generated
    ) {
        $this->opId = uniqid('OP-');
    }
}

// ─── Operational Transformation ───────────────────────────────
class OTEngine {
    /**
     * Transform op1 against op2 (both applied at same version).
     * Returns adjusted op1 that can be applied after op2.
     */
    public function transform(TextOperation $op1, TextOperation $op2): TextOperation {
        if ($op2->type === OperationType::INSERT) {
            // op2 inserted a char before or at op1's position → shift op1 right
            $newPos = ($op2->position <= $op1->position) ? $op1->position + 1 : $op1->position;
        } else {
            // op2 deleted a char before op1's position → shift op1 left
            $newPos = ($op2->position < $op1->position) ? $op1->position - 1 : $op1->position;
        }

        return new TextOperation($op1->type, $newPos, $op1->char, $op1->clientId, $op1->version + 1);
    }
}

// ─── Document ─────────────────────────────────────────────────
class Document {
    private array $content  = [];  // Character array
    private int   $version  = 0;
    private array $opLog    = [];

    public function __construct(public readonly string $docId, string $initialContent = '') {
        $this->content = str_split($initialContent);
    }

    public function apply(TextOperation $op): bool {
        if ($op->type === OperationType::INSERT) {
            array_splice($this->content, $op->position, 0, [$op->char]);
        } else {
            if ($op->position < 0 || $op->position >= count($this->content)) return false;
            array_splice($this->content, $op->position, 1);
        }
        $this->opLog[] = $op;
        $this->version++;
        return true;
    }

    public function getText(): string { return implode('', $this->content); }
    public function getVersion(): int { return $this->version; }
    public function getOpsAfter(int $version): array {
        return array_slice($this->opLog, $version);
    }
}

// ─── Collaboration Server ─────────────────────────────────────
interface EditorObserver {
    public function onOperation(TextOperation $op, string $newText): void;
}

class ClientSession implements EditorObserver {
    public function __construct(public readonly string $clientId) {}
    public function onOperation(TextOperation $op, string $newText): void {
        if ($op->clientId !== $this->clientId) {
            echo "  [{$this->clientId}] received op from {$op->clientId}: '{$newText}'\n";
        }
    }
}

class CollaborationServer {
    private Document  $doc;
    private OTEngine  $ot;
    /** @var ClientSession[] */
    private array $sessions = [];

    public function __construct(string $docId, string $initialContent = '') {
        $this->doc = new Document($docId, $initialContent);
        $this->ot  = new OTEngine();
    }

    public function addClient(ClientSession $session): void { $this->sessions[$session->clientId] = $session; }

    public function submitOperation(TextOperation $op): void {
        // Get all ops applied after the version client knew about
        $serverOps = $this->doc->getOpsAfter($op->version);

        // Transform op against each server op
        $transformed = $op;
        foreach ($serverOps as $serverOp) {
            $transformed = $this->ot->transform($transformed, $serverOp);
        }

        $this->doc->apply($transformed);
        $text = $this->doc->getText();
        echo "  [Server] v{$this->doc->getVersion()}: '{$text}'\n";

        // Broadcast to all clients
        foreach ($this->sessions as $session) $session->onOperation($transformed, $text);
    }

    public function getText(): string { return $this->doc->getText(); }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C11. Collaborative Text Editor (OT) ===\n\n";

$server = new CollaborationServer('doc-001', 'Hello');
$alice  = new ClientSession('alice');
$bob    = new ClientSession('bob');
$server->addClient($alice);
$server->addClient($bob);

echo "Initial: 'Hello'\n\n";

// Alice inserts ' World' at position 5 (version 0)
echo "--- Alice: insert ' World' at pos 5 ---\n";
$server->submitOperation(new TextOperation(OperationType::INSERT, 5, ' ', 'alice', 0));
$server->submitOperation(new TextOperation(OperationType::INSERT, 6, 'W', 'alice', 1));
$server->submitOperation(new TextOperation(OperationType::INSERT, 7, 'o', 'alice', 2));

echo "\n--- Bob: insert '!' at position 5 (based on version 0, concurrent) ---\n";
// Bob's op was generated at v0 ('Hello'), so server transforms it
$server->submitOperation(new TextOperation(OperationType::INSERT, 5, '!', 'bob', 0));

echo "\nFinal: '" . $server->getText() . "'\n";
