<?php

/*
 Interactive game: run it in a terminal ->  php TicTacToeMain.php
 You will be asked for the board size, then each player enters "row col"
 (e.g. "1 2"), exactly like the Java version that used Scanner.nextInt().
*/

// Minimal equivalent of Java's Scanner(System.in).nextInt():
// reads whitespace-separated tokens from STDIN (across lines).
class EndOfInputException extends RuntimeException {}

class Scanner
{
    /** @var resource */
    private $stream;
    /** @var string[] */
    private array $tokens = [];

    public function __construct()
    {
        $this->stream = fopen('php://stdin', 'r');
    }

    public function nextInt(): int
    {
        while (true) {
            while (empty($this->tokens)) {
                $line = fgets($this->stream);
                if ($line === false) {
                    throw new EndOfInputException("No more input available.");
                }
                $this->tokens = preg_split('/\s+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);
            }
            $token = array_shift($this->tokens);
            if (preg_match('/^[+-]?\d+$/', $token)) {
                return (int) $token;
            }
            echo PHP_EOL . "'" . $token . "' is not a number, please enter an integer: ";
        }
    }

    public function close(): void
    {
        // php://stdin is closed automatically at script end
    }
}

// Observer Pattern - for future notification service
interface IObserver
{
    public function update(string $msg): void;
}

// Sample observer implementation
class ConsoleNotifier implements IObserver
{
    public function update(string $msg): void
    {
        echo "[Notification] " . $msg . PHP_EOL;
    }
}

// Symbol/Mark class
class Symbol
{
    private string $mark;

    public function __construct(string $m)
    {
        $this->mark = $m;
    }

    public function getMark(): string
    {
        return $this->mark;
    }
}

// Board class - Dumb object that only manages the grid
class Board
{
    /** @var Symbol[][] */
    private array $grid;
    private int $size;
    private Symbol $emptyCell;

    public function __construct(int $s)
    {
        $this->size = $s;
        $this->emptyCell = new Symbol('-');
        $this->grid = [];
        for ($i = 0; $i < $this->size; $i++) {
            for ($j = 0; $j < $this->size; $j++) {
                $this->grid[$i][$j] = $this->emptyCell;
            }
        }
    }

    public function isCellEmpty(int $row, int $col): bool
    {
        if ($row < 0 || $row >= $this->size || $col < 0 || $col >= $this->size) {
            return false;
        }
        return $this->grid[$row][$col] === $this->emptyCell;
    }

    public function placeMark(int $row, int $col, Symbol $mark): bool
    {
        if ($row < 0 || $row >= $this->size || $col < 0 || $col >= $this->size) {
            return false;
        }
        if (!$this->isCellEmpty($row, $col)) {
            return false;
        }
        $this->grid[$row][$col] = $mark;
        return true;
    }

    public function getCell(int $row, int $col): Symbol
    {
        if ($row < 0 || $row >= $this->size || $col < 0 || $col >= $this->size) {
            return $this->emptyCell;
        }
        return $this->grid[$row][$col];
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getEmptyCell(): Symbol
    {
        return $this->emptyCell;
    }

    public function display(): void
    {
        echo "\n  ";
        for ($i = 0; $i < $this->size; $i++) {
            echo $i . " ";
        }
        echo PHP_EOL;

        for ($i = 0; $i < $this->size; $i++) {
            echo $i . " ";
            for ($j = 0; $j < $this->size; $j++) {
                echo $this->grid[$i][$j]->getMark() . " ";
            }
            echo PHP_EOL;
        }
        echo PHP_EOL;
    }
}

// Player class
class TicTacToePlayer
{
    private int $playerId;
    private string $name;
    private Symbol $symbol;
    private int $score;

    public function __construct(int $playerId, string $n, Symbol $s)
    {
        $this->playerId = $playerId;
        $this->name = $n;
        $this->symbol = $s;
        $this->score = 0;
    }

    // Getters and setters
    public function getName(): string
    {
        return $this->name;
    }

    public function getSymbol(): Symbol
    {
        return $this->symbol;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function incrementScore(): void
    {
        $this->score++;
    }
}

// Strategy Pattern for game rules
interface TicTacToeRules
{
    public function isValidMove(Board $board, int $row, int $col): bool;
    public function checkWinCondition(Board $board, Symbol $symbol): bool;
    public function checkDrawCondition(Board $board): bool;
}

// Standard Tic Tac Toe rules
class StandardTicTacToeRules implements TicTacToeRules
{
    public function isValidMove(Board $board, int $row, int $col): bool
    {
        return $board->isCellEmpty($row, $col);
    }

    public function checkWinCondition(Board $board, Symbol $symbol): bool
    {
        $size = $board->getSize();

        // Check rows
        for ($i = 0; $i < $size; $i++) {
            $win = true;
            for ($j = 0; $j < $size; $j++) {
                if ($board->getCell($i, $j) !== $symbol) {
                    $win = false;
                    break;
                }
            }
            if ($win) return true;
        }

        // Check columns
        for ($j = 0; $j < $size; $j++) {
            $win = true;
            for ($i = 0; $i < $size; $i++) {
                if ($board->getCell($i, $j) !== $symbol) {
                    $win = false;
                    break;
                }
            }
            if ($win) return true;
        }

        // Check main diagonal
        $win = true;
        for ($i = 0; $i < $size; $i++) {
            if ($board->getCell($i, $i) !== $symbol) {
                $win = false;
                break;
            }
        }
        if ($win) return true;

        // Check anti-diagonal
        $win = true;
        for ($i = 0; $i < $size; $i++) {
            if ($board->getCell($i, $size - 1 - $i) !== $symbol) {
                $win = false;
                break;
            }
        }
        return $win;
    }

    // If all cells are filled and no winner
    public function checkDrawCondition(Board $board): bool
    {
        $size = $board->getSize();
        for ($i = 0; $i < $size; $i++) {
            for ($j = 0; $j < $size; $j++) {
                if ($board->getCell($i, $j) === $board->getEmptyCell()) {
                    return false;
                }
            }
        }
        return true;
    }
}

// Game class --> Observable
class TicTacToeGame
{
    private Board $board;
    /** @var TicTacToePlayer[] used as a deque (array_shift / append) */
    private array $players;
    private TicTacToeRules $rules;
    /** @var IObserver[] */
    private array $observers;
    private bool $gameOver;

    public function __construct(int $boardSize)
    {
        $this->board = new Board($boardSize);
        $this->players = [];
        $this->rules = new StandardTicTacToeRules();
        $this->observers = [];
        $this->gameOver = false;
    }

    public function addPlayer(TicTacToePlayer $player): void
    {
        $this->players[] = $player; // addLast
    }

    public function addObserver(IObserver $observer): void
    {
        $this->observers[] = $observer;
    }

    public function notify(string $msg): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($msg);
        }
    }

    public function play(Scanner $scanner): void
    {
        if (count($this->players) < 2) {
            echo "Need at least 2 players!" . PHP_EOL;
            return;
        }

        $this->notify("Tic Tac Toe Game Started!");

        while (!$this->gameOver) {
            $this->board->display();

            // Take out the current player from dequeue
            $currentPlayer = $this->players[0]; // peekFirst
            echo $currentPlayer->getName() . " (" . $currentPlayer->getSymbol()->getMark() . ") - Enter row and column: ";

            $row = $scanner->nextInt();
            $col = $scanner->nextInt();

            // check if move is valid
            if ($this->rules->isValidMove($this->board, $row, $col)) {
                $this->board->placeMark($row, $col, $currentPlayer->getSymbol());
                $this->notify($currentPlayer->getName() . " played (" . $row . "," . $col . ")");

                if ($this->rules->checkWinCondition($this->board, $currentPlayer->getSymbol())) {
                    $this->board->display();
                    echo $currentPlayer->getName() . " wins!" . PHP_EOL;
                    $currentPlayer->incrementScore();

                    $this->notify($currentPlayer->getName() . " wins!");

                    $this->gameOver = true;
                } elseif ($this->rules->checkDrawCondition($this->board)) {
                    $this->board->display();

                    echo "It's a draw!" . PHP_EOL;
                    $this->notify("Game is Draw!");

                    $this->gameOver = true;
                } else {
                    // Move player to back of queue
                    array_shift($this->players);        // removeFirst
                    $this->players[] = $currentPlayer;  // addLast
                }
            } else {
                echo "Invalid move! Try again." . PHP_EOL;
            }
        }
    }
}

// Enum & Factory Pattern for game creation
enum GameType
{
    case STANDARD;
}

class TicTacToeGameFactory
{
    public static function createGame(GameType $gt, int $boardSize): ?TicTacToeGame
    {
        if (GameType::STANDARD === $gt) {
            return new TicTacToeGame($boardSize);
        }
        return null;
    }
}

// Main class for Tic Tac Toe
class TicTacToeMain
{
    public static function main(): void
    {
        echo "=== TIC TAC TOE GAME ===" . PHP_EOL;

        // One shared Scanner for the whole program (PHP reads STDIN through one stream)
        $scanner = new Scanner();

        try {
            // Create game with custom board size
            echo "Enter board size (e.g., 3 for 3x3): ";
            $boardSize = $scanner->nextInt();

            $game = TicTacToeGameFactory::createGame(GameType::STANDARD, $boardSize);

            // Add observer
            $notifier = new ConsoleNotifier();
            $game->addObserver($notifier);

            // Create players with custom symbols
            $player1 = new TicTacToePlayer(1, "Aditya", new Symbol('X'));
            $player2 = new TicTacToePlayer(2, "Harshita", new Symbol('O'));

            $game->addPlayer($player1);
            $game->addPlayer($player2);

            // Play the game
            $game->play($scanner);
        } catch (EndOfInputException $e) {
            echo PHP_EOL . "Input ended before the game finished. Exiting." . PHP_EOL;
        }

        $scanner->close();
    }
}

TicTacToeMain::main();
