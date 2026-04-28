<?php
/**
 * B10. TEXT EDITOR WITH UNDO/REDO
 * ============================================================
 * PROBLEM: Text editor supporting insert, delete, and cursor
 * movement with full undo/redo history.
 *
 * PATTERNS:
 *  - Command  : Each edit = a Command with execute/undo
 *  - Memento  : EditorState snapshot (alternative approach shown)
 *
 * DRY RUN:
 *   type("Hello")   → buffer="Hello"
 *   type(" World")  → buffer="Hello World"
 *   undo()          → buffer="Hello"
 *   redo()          → buffer="Hello World"
 *   delete(5)       → buffer="Hello"
 *   undo()          → buffer="Hello World"
 * ============================================================
 */

// ─── Command Interface ─────────────────────────────────────────
interface EditorCommand {
    public function execute(Editor $editor): void;
    public function undo(Editor $editor): void;
}

// ─── Concrete Commands ────────────────────────────────────────
class TypeCommand implements EditorCommand {
    public function __construct(private string $text) {}

    public function execute(Editor $editor): void {
        $editor->insertAt($editor->getCursorPos(), $this->text);
        $editor->moveCursor(strlen($this->text));
    }

    public function undo(Editor $editor): void {
        $len = strlen($this->text);
        $editor->deleteAt($editor->getCursorPos() - $len, $len);
        $editor->moveCursor(-$len);
    }
}

class DeleteCommand implements EditorCommand {
    private string $deleted = '';

    public function __construct(private int $count) {}

    public function execute(Editor $editor): void {
        $pos          = $editor->getCursorPos() - $this->count;
        $this->deleted = substr($editor->getContent(), max(0, $pos), $this->count);
        $editor->deleteAt(max(0, $pos), $this->count);
        $editor->moveCursor(-$this->count);
    }

    public function undo(Editor $editor): void {
        $editor->insertAt($editor->getCursorPos(), $this->deleted);
        $editor->moveCursor(strlen($this->deleted));
    }
}

// ─── Editor ─────────────────────────────────────────────────────
class Editor {
    private string $content   = '';
    private int    $cursorPos = 0;
    /** @var EditorCommand[] */
    private array  $history   = [];
    /** @var EditorCommand[] */
    private array  $redoStack = [];

    public function getContent(): string  { return $this->content; }
    public function getCursorPos(): int   { return $this->cursorPos; }

    public function insertAt(int $pos, string $text): void {
        $pos           = max(0, min($pos, strlen($this->content)));
        $this->content = substr($this->content, 0, $pos) . $text . substr($this->content, $pos);
    }

    public function deleteAt(int $pos, int $count): void {
        $pos           = max(0, min($pos, strlen($this->content)));
        $this->content = substr($this->content, 0, $pos) . substr($this->content, $pos + $count);
    }

    public function moveCursor(int $delta): void {
        $this->cursorPos = max(0, min(strlen($this->content), $this->cursorPos + $delta));
    }

    public function executeCommand(EditorCommand $cmd): void {
        $cmd->execute($this);
        $this->history[]  = $cmd;
        $this->redoStack  = []; // Clear redo on new command
    }

    public function undo(): void {
        if (empty($this->history)) { echo "  Nothing to undo\n"; return; }
        $cmd = array_pop($this->history);
        $cmd->undo($this);
        $this->redoStack[] = $cmd;
        echo "  Undo → \"{$this->content}\"\n";
    }

    public function redo(): void {
        if (empty($this->redoStack)) { echo "  Nothing to redo\n"; return; }
        $cmd = array_pop($this->redoStack);
        $cmd->execute($this);
        $this->history[] = $cmd;
        echo "  Redo → \"{$this->content}\"\n";
    }

    public function show(): void {
        echo "  Content: \"{$this->content}\" | Cursor: {$this->cursorPos}\n";
    }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== B10. Text Editor with Undo/Redo ===\n\n";

$editor = new Editor();
$editor->executeCommand(new TypeCommand('Hello'));   $editor->show();
$editor->executeCommand(new TypeCommand(' World'));  $editor->show();
$editor->undo();  // Remove " World"
$editor->undo();  // Remove "Hello"
$editor->redo();  // Re-type "Hello"
$editor->redo();  // Re-type " World"
$editor->executeCommand(new DeleteCommand(5)); $editor->show(); // Delete "World"
$editor->undo();  // Restore "World"
$editor->show();
