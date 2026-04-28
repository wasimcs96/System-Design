<?php
/**
 * A9. TIC-TAC-TOE GAME
 * ============================================================
 * PROBLEM: Two-player Tic-Tac-Toe on a 3×3 board.
 *
 * PATTERNS:
 *  - Strategy : PlayerStrategy (Human vs AI)
 * ============================================================
 */

enum Symbol: string { case X = 'X'; case O = 'O'; }

// ─── Player Strategy ────────────────────────────────────────────
interface PlayerStrategy {
    /** Returns [row, col] of chosen move */
    public function chooseMove(Board3x3 $board): array;
}

class HumanPlayer implements PlayerStrategy {
    public function __construct(private int $row, private int $col) {}
    public function chooseMove(Board3x3 $board): array { return [$this->row, $this->col]; }
    public function setNextMove(int $r, int $c): void { $this->row = $r; $this->col = $c; }
}

/** Naive AI: picks first available cell */
class RandomAI implements PlayerStrategy {
    public function chooseMove(Board3x3 $board): array {
        foreach ($board->getCells() as $row => $cols)
            foreach ($cols as $col => $cell)
                if ($cell === null) return [$row, $col];
        return [-1, -1];
    }
}

// ─── Player ─────────────────────────────────────────────────────
class Player {
    public function __construct(
        public readonly string   $name,
        public readonly Symbol   $symbol,
        public readonly PlayerStrategy $strategy
    ) {}
}

// ─── Board ──────────────────────────────────────────────────────
class Board3x3 {
    /** @var Symbol|null[][] */
    private array $cells;

    public function __construct(private int $size = 3) {
        for ($r = 0; $r < $size; $r++)
            for ($c = 0; $c < $size; $c++)
                $this->cells[$r][$c] = null;
    }

    public function getCells(): array { return $this->cells; }

    public function place(Symbol $symbol, int $row, int $col): bool {
        if ($row < 0 || $row >= $this->size || $col < 0 || $col >= $this->size) return false;
        if ($this->cells[$row][$col] !== null) return false;
        $this->cells[$row][$col] = $symbol;
        return true;
    }

    public function checkWinner(): ?Symbol {
        $n = $this->size;
        // Check rows and columns
        for ($i = 0; $i < $n; $i++) {
            if ($this->allSame(array_map(fn($c) => $this->cells[$i][$c], range(0, $n-1)))) return $this->cells[$i][0];
            if ($this->allSame(array_map(fn($r) => $this->cells[$r][$i], range(0, $n-1)))) return $this->cells[0][$i];
        }
        // Diagonals
        if ($this->allSame(array_map(fn($i) => $this->cells[$i][$i], range(0, $n-1)))) return $this->cells[0][0];
        if ($this->allSame(array_map(fn($i) => $this->cells[$i][$n-1-$i], range(0, $n-1)))) return $this->cells[0][$n-1];
        return null;
    }

    public function isFull(): bool {
        foreach ($this->cells as $row)
            foreach ($row as $cell)
                if ($cell === null) return false;
        return true;
    }

    private function allSame(array $arr): bool {
        return $arr[0] !== null && count(array_unique(array_map(fn($s) => $s?->value, $arr))) === 1;
    }

    public function display(): void {
        foreach ($this->cells as $row) {
            echo " " . implode(' | ', array_map(fn($c) => $c?->value ?? '.', $row)) . "\n";
        }
    }
}

// ─── Game ───────────────────────────────────────────────────────
class TicTacToeGame {
    private Board3x3 $board;
    private array    $players; // [Player, Player]
    private int      $turn = 0;
    private bool     $over = false;

    public function __construct(Player $p1, Player $p2) {
        $this->board   = new Board3x3();
        $this->players = [$p1, $p2];
    }

    public function playTurn(): bool {
        if ($this->over) { echo "Game already over\n"; return false; }

        $player = $this->players[$this->turn % 2];
        [$r, $c] = $player->strategy->chooseMove($this->board);

        if (!$this->board->place($player->symbol, $r, $c)) {
            echo "  ✗ Invalid move ({$r},{$c}) by {$player->name}\n";
            return false;
        }

        echo "  {$player->name} ({$player->symbol->value}) → ({$r},{$c})\n";
        $this->board->display();

        $winner = $this->board->checkWinner();
        if ($winner) {
            echo "  🏆 {$player->name} WINS!\n";
            $this->over = true;
            return true;
        }
        if ($this->board->isFull()) {
            echo "  🤝 It is a DRAW!\n";
            $this->over = true;
            return true;
        }

        $this->turn++;
        return true;
    }

    public function isOver(): bool { return $this->over; }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== A9. Tic-Tac-Toe ===\n\n";

$h1 = new HumanPlayer(0, 0);
$h2 = new HumanPlayer(0, 0);

$p1 = new Player('Alice', Symbol::X, $h1);
$p2 = new Player('Bob',   Symbol::O, $h2);

$game = new TicTacToeGame($p1, $p2);

// Simulate moves
$moves = [[0,0],[0,1],[1,1],[0,2],[2,2]]; // X wins diagonal
foreach ($moves as $i => $move) {
    if ($i % 2 === 0) $h1->setNextMove($move[0], $move[1]);
    else              $h2->setNextMove($move[0], $move[1]);
    $game->playTurn();
    if ($game->isOver()) break;
}
