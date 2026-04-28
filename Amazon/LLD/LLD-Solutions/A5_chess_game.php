<?php
/**
 * A5. CHESS GAME
 * ============================================================
 * PROBLEM: Design a two-player chess game with board management,
 * piece hierarchy, move validation, and turn management.
 *
 * PATTERNS:
 *  - Template Method : Piece::isValidMove() calls abstract canMoveTo()
 *  - Strategy        : PlayerStrategy (Human vs AI)
 * ============================================================
 */

enum PieceColor: string { case WHITE = 'White'; case BLACK = 'Black'; }

// ─── Cell ───────────────────────────────────────────────────────
class Cell {
    private ?Piece $piece = null;

    public function __construct(
        public readonly int $row,
        public readonly int $col
    ) {}

    public function getPiece(): ?Piece        { return $this->piece; }
    public function setPiece(?Piece $p): void { $this->piece = $p; }
    public function isEmpty(): bool           { return $this->piece === null; }
    public function getLabel(): string        { return chr(65 + $this->col) . ($this->row + 1); }
}

// ─── Piece (Template Method Pattern) ──────────────────────────
abstract class Piece {
    public function __construct(
        public readonly PieceColor $color,
        public readonly string     $symbol
    ) {}

    /**
     * Template method: common validation + delegate to canMoveTo.
     * canMoveTo() is the "hot spot" each subclass overrides.
     */
    final public function isValidMove(Cell $from, Cell $to, Board $board): bool {
        if ($from === $to) return false;
        // Cannot capture own piece
        if (!$to->isEmpty() && $to->getPiece()?->color === $this->color) return false;
        return $this->canMoveTo($from, $to, $board);
    }

    abstract protected function canMoveTo(Cell $from, Cell $to, Board $board): bool;

    public function __toString(): string { return $this->symbol; }
}

class Rook extends Piece {
    public function __construct(PieceColor $color) {
        parent::__construct($color, $color === PieceColor::WHITE ? 'R' : 'r');
    }
    protected function canMoveTo(Cell $from, Cell $to, Board $board): bool {
        // Rook moves in straight lines (same row OR same col)
        return $from->row === $to->row || $from->col === $to->col;
    }
}

class Bishop extends Piece {
    public function __construct(PieceColor $color) {
        parent::__construct($color, $color === PieceColor::WHITE ? 'B' : 'b');
    }
    protected function canMoveTo(Cell $from, Cell $to, Board $board): bool {
        // Bishop moves diagonally
        return abs($from->row - $to->row) === abs($from->col - $to->col);
    }
}

class Queen extends Piece {
    public function __construct(PieceColor $color) {
        parent::__construct($color, $color === PieceColor::WHITE ? 'Q' : 'q');
    }
    protected function canMoveTo(Cell $from, Cell $to, Board $board): bool {
        // Queen = Rook + Bishop
        $sameRow  = $from->row === $to->row;
        $sameCol  = $from->col === $to->col;
        $diagonal = abs($from->row - $to->row) === abs($from->col - $to->col);
        return $sameRow || $sameCol || $diagonal;
    }
}

class King extends Piece {
    public function __construct(PieceColor $color) {
        parent::__construct($color, $color === PieceColor::WHITE ? 'K' : 'k');
    }
    protected function canMoveTo(Cell $from, Cell $to, Board $board): bool {
        // King moves one step in any direction
        return abs($from->row - $to->row) <= 1 && abs($from->col - $to->col) <= 1;
    }
}

class Pawn extends Piece {
    public function __construct(PieceColor $color) {
        parent::__construct($color, $color === PieceColor::WHITE ? 'P' : 'p');
    }
    protected function canMoveTo(Cell $from, Cell $to, Board $board): bool {
        $dir = ($this->color === PieceColor::WHITE) ? 1 : -1; // White moves up, Black moves down
        $rowDiff = $to->row - $from->row;
        $colDiff = abs($to->col - $from->col);
        // Forward one step
        if ($colDiff === 0 && $rowDiff === $dir && $to->isEmpty()) return true;
        // Diagonal capture
        if ($colDiff === 1 && $rowDiff === $dir && !$to->isEmpty()) return true;
        return false;
    }
}

// ─── Board ──────────────────────────────────────────────────────
class Board {
    /** @var Cell[][] */
    private array $grid;

    public function __construct() {
        // Create 8×8 grid
        for ($r = 0; $r < 8; $r++)
            for ($c = 0; $c < 8; $c++)
                $this->grid[$r][$c] = new Cell($r, $c);
    }

    public function getCell(int $row, int $col): Cell { return $this->grid[$row][$col]; }

    public function placePiece(Piece $piece, int $row, int $col): void {
        $this->grid[$row][$col]->setPiece($piece);
    }

    public function display(): void {
        echo "  A B C D E F G H\n";
        for ($r = 7; $r >= 0; $r--) {
            echo ($r + 1) . " ";
            for ($c = 0; $c < 8; $c++) {
                $p = $this->grid[$r][$c]->getPiece();
                echo ($p ? $p->symbol : '.') . ' ';
            }
            echo "\n";
        }
    }
}

// ─── Move ────────────────────────────────────────────────────────
class Move {
    public function __construct(
        public readonly Cell $from,
        public readonly Cell $to
    ) {}
}

// ─── Player ─────────────────────────────────────────────────────
class Player {
    public function __construct(
        public readonly string     $name,
        public readonly PieceColor $color
    ) {}
}

// ─── Game ───────────────────────────────────────────────────────
class ChessGame {
    private Board   $board;
    private Player  $white;
    private Player  $black;
    private Player  $currentPlayer;
    private bool    $gameOver = false;

    public function __construct(Player $white, Player $black) {
        $this->board         = new Board();
        $this->white         = $white;
        $this->black         = $black;
        $this->currentPlayer = $white;
        $this->setupPieces();
    }

    private function setupPieces(): void {
        // Simplified setup: kings, queens, and a few pawns
        $this->board->placePiece(new King(PieceColor::WHITE),   0, 4);
        $this->board->placePiece(new Queen(PieceColor::WHITE),  0, 3);
        $this->board->placePiece(new Rook(PieceColor::WHITE),   0, 0);
        $this->board->placePiece(new Rook(PieceColor::WHITE),   0, 7);
        for ($c = 0; $c < 8; $c++) $this->board->placePiece(new Pawn(PieceColor::WHITE), 1, $c);

        $this->board->placePiece(new King(PieceColor::BLACK),   7, 4);
        $this->board->placePiece(new Queen(PieceColor::BLACK),  7, 3);
        $this->board->placePiece(new Rook(PieceColor::BLACK),   7, 0);
        $this->board->placePiece(new Rook(PieceColor::BLACK),   7, 7);
        for ($c = 0; $c < 8; $c++) $this->board->placePiece(new Pawn(PieceColor::BLACK), 6, $c);
    }

    public function makeMove(int $fromRow, int $fromCol, int $toRow, int $toCol): bool {
        if ($this->gameOver) { echo "  Game is over\n"; return false; }

        $from  = $this->board->getCell($fromRow, $fromCol);
        $to    = $this->board->getCell($toRow,   $toCol);
        $piece = $from->getPiece();

        if (!$piece) { echo "  ✗ No piece at {$from->getLabel()}\n"; return false; }
        if ($piece->color !== $this->currentPlayer->color) {
            echo "  ✗ Not {$this->currentPlayer->name}'s piece\n"; return false;
        }
        if (!$piece->isValidMove($from, $to, $this->board)) {
            echo "  ✗ Invalid move for {$piece->symbol}: {$from->getLabel()} → {$to->getLabel()}\n";
            return false;
        }

        // Check if captured king → game over
        if ($to->getPiece() instanceof King) {
            echo "  ♛ {$this->currentPlayer->name} captures the King! Game over!\n";
            $this->gameOver = true;
        }

        $to->setPiece($piece);
        $from->setPiece(null);
        echo "  ✓ [{$this->currentPlayer->name}] {$piece->symbol}: {$from->getLabel()} → {$to->getLabel()}\n";

        // Switch turn
        $this->currentPlayer = ($this->currentPlayer === $this->white) ? $this->black : $this->white;
        return true;
    }

    public function display(): void { $this->board->display(); }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== A5. Chess Game ===\n\n";

$game = new ChessGame(new Player('Alice', PieceColor::WHITE), new Player('Bob', PieceColor::BLACK));
$game->display();

echo "\n--- Moves ---\n";
$game->makeMove(1, 4, 3, 4); // White pawn e2→e4
$game->makeMove(6, 4, 4, 4); // Black pawn e7→e5
$game->makeMove(0, 3, 4, 7); // White queen d1→h5 (invalid – pawn blocks)
$game->makeMove(0, 3, 2, 5); // White queen d1→f3
$game->makeMove(1, 4, 2, 4); // Wrong turn (White pawn, but it's Black's turn actually)

/**
 * INTERVIEW FOLLOW-UPS:
 *  1. Check / Checkmate detection? → After every move, scan if King is threatened
 *  2. En passant / castling? → Special move flags in Move + GameState
 *  3. AI player? → MinimaxStrategy implements PlayerStrategy
 *  4. Replay / undo? → MoveHistory stack + Command undo
 */
