<?php

/*
 Interactive game: run it in a terminal ->  php SnakeAndLadder.php
 Choose a setup (1/2/3), enter number of players and their names,
 then press Enter to roll the dice on each turn.
*/

// Minimal equivalent of Java's Scanner(System.in) with nextInt() and nextLine().
class EndOfInputException extends RuntimeException {}

class Scanner
{
    /** @var resource */
    private $stream;
    private ?string $line = null; // unread remainder of the current input line

    public function __construct()
    {
        $this->stream = fopen('php://stdin', 'r');
    }

    private function readLine(): string
    {
        $raw = fgets($this->stream);
        if ($raw === false) {
            throw new EndOfInputException("No more input available.");
        }
        return rtrim($raw, "\r\n");
    }

    // Like Java: skips whitespace/newlines, reads one integer token, leaves the rest of the line.
    public function nextInt(): int
    {
        while (true) {
            if ($this->line === null) {
                $this->line = $this->readLine();
            }
            $this->line = ltrim($this->line);
            if ($this->line === "") {
                $this->line = null;
                continue;
            }
            preg_match('/^\S+/', $this->line, $m);
            $token = $m[0];
            $this->line = substr($this->line, strlen($token));
            if (preg_match('/^[+-]?\d+$/', $token)) {
                return (int) $token;
            }
            echo PHP_EOL . "'" . $token . "' is not a number, please enter an integer: ";
        }
    }

    // Like Java: returns the rest of the current line (or the next full line).
    public function nextLine(): string
    {
        if ($this->line !== null) {
            $rest = $this->line;
            $this->line = null;
            return $rest;
        }
        return $this->readLine();
    }

    public function close(): void
    {
        // php://stdin is closed automatically at script end
    }
}

// Java's Math.random(): double in [0, 1)
function mathRandom(): float
{
    return mt_rand() / (mt_getrandmax() + 1);
}

// Observer Pattern
interface IObserver
{
    public function update(string $msg): void;
}

// Sample observer implementation
class SnakeAndLadderConsoleNotifier implements IObserver
{
    public function update(string $msg): void
    {
        echo "[NOTIFICATION] " . $msg . PHP_EOL;
    }
}

// Dice class
class Dice
{
    private int $faces;

    public function __construct(int $f)
    {
        $this->faces = $f;
    }

    public function roll(): int
    {
        return (int)(mathRandom() * $this->faces) + 1;
    }
}

// Base class for Snake and Ladder (both have start and end positions)
abstract class BoardEntity
{
    protected int $startPosition;
    protected int $endPosition;

    public function __construct(int $start, int $end)
    {
        $this->startPosition = $start;
        $this->endPosition = $end;
    }

    public function getStart(): int
    {
        return $this->startPosition;
    }

    public function getEnd(): int
    {
        return $this->endPosition;
    }

    abstract public function display(): void;
    abstract public function name(): string;
}

// Snake class
class Snake extends BoardEntity
{
    public function __construct(int $start, int $end)
    {
        parent::__construct($start, $end);
        if ($end >= $start) {
            echo "Invalid snake! End must be less than start." . PHP_EOL;
        }
    }

    public function display(): void
    {
        echo "Snake: " . $this->startPosition . " -> " . $this->endPosition . PHP_EOL;
    }

    public function name(): string
    {
        return "SNAKE";
    }
}

// Ladder class
class Ladder extends BoardEntity
{
    public function __construct(int $start, int $end)
    {
        parent::__construct($start, $end);
        if ($end <= $start) {
            echo "Invalid ladder! End must be greater than start." . PHP_EOL;
        }
    }

    public function display(): void
    {
        echo "Ladder: " . $this->startPosition . " -> " . $this->endPosition . PHP_EOL;
    }

    public function name(): string
    {
        return "LADDER";
    }
}

// Board class
class Board
{
    private int $size;
    /** @var BoardEntity[] */
    private array $snakesAndLadders;
    /** @var array<int, BoardEntity> start position -> entity */
    private array $boardEntities;

    public function __construct(int $s)
    {
        $this->size = $s * $s;  // m*m board
        $this->snakesAndLadders = [];
        $this->boardEntities = [];
    }

    public function canAddEntity(int $position): bool
    {
        return !array_key_exists($position, $this->boardEntities);
    }

    public function addBoardEntity(BoardEntity $boardEntity): void
    {
        if ($this->canAddEntity($boardEntity->getStart())) {
            $this->snakesAndLadders[] = $boardEntity;
            $this->boardEntities[$boardEntity->getStart()] = $boardEntity;
        }
    }

    public function setupBoard(BoardSetupStrategy $strategy): void
    {
        $strategy->setupBoard($this);
    }

    public function getEntity(int $position): ?BoardEntity
    {
        return $this->boardEntities[$position] ?? null;
    }

    public function getBoardSize(): int
    {
        return $this->size;
    }

    public function display(): void
    {
        echo PHP_EOL . "=== Board Configuration ===" . PHP_EOL;
        echo "Board Size: " . $this->size . " cells" . PHP_EOL;

        $snakeCount = 0;
        $ladderCount = 0;
        foreach ($this->snakesAndLadders as $entity) {
            if ($entity->name() === "SNAKE") $snakeCount++;
            else $ladderCount++;
        }

        echo PHP_EOL . "Snakes: " . $snakeCount . PHP_EOL;
        foreach ($this->snakesAndLadders as $entity) {
            if ($entity->name() === "SNAKE") {
                $entity->display();
            }
        }

        echo PHP_EOL . "Ladders: " . $ladderCount . PHP_EOL;
        foreach ($this->snakesAndLadders as $entity) {
            if ($entity->name() === "LADDER") {
                $entity->display();
            }
        }
        echo "=========================" . PHP_EOL;
    }
}

// Strategy Pattern for Board Setup
interface BoardSetupStrategy
{
    public function setupBoard(Board $board): void;
}

// Java: RandomBoardSetupStrategy.Difficulty (nested enum). PHP can't nest, so it is top-level.
enum Difficulty
{
    case EASY;    // More ladders, fewer snakes
    case MEDIUM;  // Equal snakes and ladders
    case HARD;    // More snakes, fewer ladders
}

// Random Strategy with difficulty levels
class RandomBoardSetupStrategy implements BoardSetupStrategy
{
    private Difficulty $difficulty;

    private function setupWithProbability(Board $board, float $snakeProbability): void
    {
        $boardSize = $board->getBoardSize();
        $totalEntities = intdiv($boardSize, 10); // Roughly 10% of board has entities

        for ($i = 0; $i < $totalEntities; $i++) {
            $prob = mathRandom();

            if ($prob < $snakeProbability) {
                // Add snake
                $attempts = 0;
                while ($attempts < 50) {
                    $start = (int)(mathRandom() * ($boardSize - 10)) + 10;
                    $end = (int)(mathRandom() * ($start - 1)) + 1;

                    if ($board->canAddEntity($start)) {
                        $board->addBoardEntity(new Snake($start, $end));
                        break;
                    }
                    $attempts++;
                }
            } else {
                // Add ladder
                $attempts = 0;
                while ($attempts < 50) {
                    $start = (int)(mathRandom() * ($boardSize - 10)) + 1;
                    $end = (int)(mathRandom() * ($boardSize - $start)) + $start + 1;

                    if ($board->canAddEntity($start) && $end < $boardSize) {
                        $board->addBoardEntity(new Ladder($start, $end));
                        break;
                    }
                    $attempts++;
                }
            }
        }
    }

    public function __construct(Difficulty $d)
    {
        $this->difficulty = $d;
    }

    public function setupBoard(Board $board): void
    {
        switch ($this->difficulty) {
            case Difficulty::EASY:
                $this->setupWithProbability($board, 0.3);  // 30% snakes, 70% ladders
                break;
            case Difficulty::MEDIUM:
                $this->setupWithProbability($board, 0.5);  // 50% snakes, 50% ladders
                break;
            case Difficulty::HARD:
                $this->setupWithProbability($board, 0.7);  // 70% snakes, 30% ladders
                break;
        }
    }
}

// Custom Strategy - User provides count
class CustomCountBoardSetupStrategy implements BoardSetupStrategy
{
    private int $numSnakes;
    private int $numLadders;
    private bool $randomPositions;
    /** @var array<int, array{0:int, 1:int}> list of [start, end] (Java used a Pair class) */
    private array $snakePositions;
    /** @var array<int, array{0:int, 1:int}> */
    private array $ladderPositions;

    public function __construct(int $snakes, int $ladders, bool $random)
    {
        $this->numSnakes = $snakes;
        $this->numLadders = $ladders;
        $this->randomPositions = $random;
        $this->snakePositions = [];
        $this->ladderPositions = [];
    }

    public function addSnakePosition(int $start, int $end): void
    {
        $this->snakePositions[] = [$start, $end];
    }

    public function addLadderPosition(int $start, int $end): void
    {
        $this->ladderPositions[] = [$start, $end];
    }

    public function setupBoard(Board $board): void
    {
        if ($this->randomPositions) {
            // Random placement with user-defined counts
            $boardSize = $board->getBoardSize();

            // Add snakes
            $snakesAdded = 0;
            while ($snakesAdded < $this->numSnakes) {
                $start = (int)(mathRandom() * ($boardSize - 10)) + 10;
                $end = (int)(mathRandom() * ($start - 1)) + 1;

                if ($board->canAddEntity($start)) {
                    $board->addBoardEntity(new Snake($start, $end));
                    $snakesAdded++;
                }
            }

            // Add ladders
            $laddersAdded = 0;
            while ($laddersAdded < $this->numLadders) {
                $start = (int)(mathRandom() * ($boardSize - 10)) + 1;
                $end = (int)(mathRandom() * ($boardSize - $start)) + $start + 1;

                if ($board->canAddEntity($start) && $end < $boardSize) {
                    $board->addBoardEntity(new Ladder($start, $end));
                    $laddersAdded++;
                }
            }
        } else {
            // User-defined positions
            foreach ($this->snakePositions as [$start, $end]) {
                if ($board->canAddEntity($start)) {
                    $board->addBoardEntity(new Snake($start, $end));
                }
            }

            foreach ($this->ladderPositions as [$start, $end]) {
                if ($board->canAddEntity($start)) {
                    $board->addBoardEntity(new Ladder($start, $end));
                }
            }
        }
    }
}

// Standard Board Strategy - Traditional Snake & Ladder positions
class StandardBoardSetupStrategy implements BoardSetupStrategy
{
    public function setupBoard(Board $board): void
    {
        // Only works for 10x10 board (100 cells)
        if ($board->getBoardSize() !== 100) {
            echo "Standard setup only works for 10x10 board!" . PHP_EOL;
            return;
        }

        // Standard snake positions (based on traditional board)
        $board->addBoardEntity(new Snake(99, 54));
        $board->addBoardEntity(new Snake(95, 75));
        $board->addBoardEntity(new Snake(92, 88));
        $board->addBoardEntity(new Snake(89, 68));
        $board->addBoardEntity(new Snake(74, 53));
        $board->addBoardEntity(new Snake(64, 60));
        $board->addBoardEntity(new Snake(62, 19));
        $board->addBoardEntity(new Snake(49, 11));
        $board->addBoardEntity(new Snake(46, 25));
        $board->addBoardEntity(new Snake(16, 6));

        // Standard ladder positions
        $board->addBoardEntity(new Ladder(2, 38));
        $board->addBoardEntity(new Ladder(7, 14));
        $board->addBoardEntity(new Ladder(8, 31));
        $board->addBoardEntity(new Ladder(15, 26));
        $board->addBoardEntity(new Ladder(21, 42));
        $board->addBoardEntity(new Ladder(28, 84));
        $board->addBoardEntity(new Ladder(36, 44));
        $board->addBoardEntity(new Ladder(51, 67));
        $board->addBoardEntity(new Ladder(71, 91));
        $board->addBoardEntity(new Ladder(78, 98));
        $board->addBoardEntity(new Ladder(87, 94));
    }
}

// Player class
class SnakeAndLadderPlayer
{
    private int $playerId;
    private string $name;
    private int $position;
    private int $score;

    public function __construct(int $playerId, string $n)
    {
        $this->playerId = $playerId;
        $this->name = $n;
        $this->position = 0;
        $this->score = 0;
    }

    // Getters and Setters
    public function getName(): string
    {
        return $this->name;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $pos): void
    {
        $this->position = $pos;
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
interface SnakeAndLadderRules
{
    public function isValidMove(int $currentPos, int $diceValue, int $boardSize): bool;
    public function calculateNewPosition(int $currentPos, int $diceValue, Board $board): int;
    public function checkWinCondition(int $position, int $boardSize): bool;
}

// Standard rules
class StandardSnakeAndLadderRules implements SnakeAndLadderRules
{
    public function isValidMove(int $currentPos, int $diceValue, int $boardSize): bool
    {
        return ($currentPos + $diceValue) <= $boardSize;
    }

    public function calculateNewPosition(int $currentPos, int $diceValue, Board $board): int
    {
        $newPos = $currentPos + $diceValue;
        $entity = $board->getEntity($newPos);

        if ($entity !== null) {
            return $entity->getEnd();
        }
        return $newPos;
    }

    public function checkWinCondition(int $position, int $boardSize): bool
    {
        return $position === $boardSize;
    }
}

// Game class
class SnakeAndLadderGame
{
    private Board $board;
    private Dice $dice;
    /** @var SnakeAndLadderPlayer[] used as a deque */
    private array $players;
    private SnakeAndLadderRules $rules;
    /** @var IObserver[] */
    private array $observers;
    private bool $gameOver;

    public function __construct(Board $b, Dice $d)
    {
        $this->board = $b;
        $this->dice = $d;
        $this->players = [];
        $this->rules = new StandardSnakeAndLadderRules();
        $this->observers = [];
        $this->gameOver = false;
    }

    public function addPlayer(SnakeAndLadderPlayer $player): void
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

    public function displayPlayerPositions(): void
    {
        echo PHP_EOL . "=== Current Positions ===" . PHP_EOL;
        foreach ($this->players as $player) {
            echo $player->getName() . ": " . $player->getPosition() . PHP_EOL;
        }
        echo "=======================" . PHP_EOL;
    }

    public function play(Scanner $scanner): void
    {
        if (count($this->players) < 2) {
            echo "Need at least 2 players!" . PHP_EOL;
            return;
        }

        $this->notify("Game started");

        $this->board->display();

        while (!$this->gameOver) {
            $currentPlayer = $this->players[0]; // peekFirst

            echo PHP_EOL . $currentPlayer->getName() . "'s turn. Press Enter to roll dice..." . PHP_EOL;
            $scanner->nextLine();

            $diceValue = $this->dice->roll();
            echo "Rolled: " . $diceValue . PHP_EOL;

            $currentPos = $currentPlayer->getPosition();

            if ($this->rules->isValidMove($currentPos, $diceValue, $this->board->getBoardSize())) {
                $intermediatePos = $currentPos + $diceValue;
                $newPos = $this->rules->calculateNewPosition($currentPos, $diceValue, $this->board);

                $currentPlayer->setPosition($newPos);

                // Check if player encountered snake or ladder
                $entity = $this->board->getEntity($intermediatePos);
                if ($entity !== null) {
                    $isSnake = $entity->name() === "SNAKE";
                    if ($isSnake) {
                        echo "Oh no! Snake at " . $intermediatePos . "! Going down to " . $newPos . PHP_EOL;
                        $this->notify($currentPlayer->getName() . " encountered snake at " . $intermediatePos . " now going down to " . $newPos);
                    } else {
                        echo "Great! Ladder at " . $intermediatePos . "! Going up to " . $newPos . PHP_EOL;
                        $this->notify($currentPlayer->getName() . " encountered ladder at " . $intermediatePos . " now going up to " . $newPos);
                    }
                }

                $this->notify($currentPlayer->getName() . " played. New Position : " . $newPos);
                $this->displayPlayerPositions();

                if ($this->rules->checkWinCondition($newPos, $this->board->getBoardSize())) {
                    echo PHP_EOL . $currentPlayer->getName() . " wins!" . PHP_EOL;
                    $currentPlayer->incrementScore();

                    $this->notify("Game Ended. Winner is : " . $currentPlayer->getName());
                    $this->gameOver = true;
                } else {
                    // Move player to back of queue
                    array_shift($this->players);
                    $this->players[] = $currentPlayer;
                }
            } else {
                echo "Need exact roll to reach " . $this->board->getBoardSize() . "!" . PHP_EOL;
                // Move player to back of queue
                array_shift($this->players);
                $this->players[] = $currentPlayer;
            }
        }
    }
}

// Factory Pattern
class SnakeAndLadderGameFactory
{
    public static function createStandardGame(): SnakeAndLadderGame
    {
        $board = new Board(10);  // Standard 10x10 board
        $strategy = new StandardBoardSetupStrategy();
        $board->setupBoard($strategy);

        $dice = new Dice(6);  // Standard 6-faced dice

        return new SnakeAndLadderGame($board, $dice);
    }

    public static function createRandomGame(int $boardSize, Difficulty $difficulty): SnakeAndLadderGame
    {
        $board = new Board($boardSize);
        $strategy = new RandomBoardSetupStrategy($difficulty);
        $board->setupBoard($strategy);

        $dice = new Dice(6);

        return new SnakeAndLadderGame($board, $dice);
    }

    public static function createCustomGame(int $boardSize, BoardSetupStrategy $strategy): SnakeAndLadderGame
    {
        $board = new Board($boardSize);
        $board->setupBoard($strategy);

        $dice = new Dice(6);

        return new SnakeAndLadderGame($board, $dice);
    }
}

// Main class for Snake and Ladder
class SnakeAndLadder
{
    public static function main(): void
    {
        echo "=== SNAKE AND LADDER GAME ===" . PHP_EOL;

        $game = null;
        $board = null;

        echo "Choose game setup:" . PHP_EOL;
        echo "1. Standard Game (10x10 board with traditional positions)" . PHP_EOL;
        echo "2. Random Game with Difficulty" . PHP_EOL;
        echo "3. Custom Game" . PHP_EOL;

        // One shared Scanner for the whole program (PHP reads STDIN through one stream)
        $scanner = new Scanner();

        try {
            $choice = $scanner->nextInt();

            if ($choice === 1) {
                // Standard game
                $game = SnakeAndLadderGameFactory::createStandardGame();
                $board = new Board(10);
            } elseif ($choice === 2) {
                // Random game with difficulty
                echo "Enter board size (e.g., 10 for 10x10 board): ";
                $boardSize = $scanner->nextInt();

                echo "Choose difficulty:" . PHP_EOL;
                echo "1. Easy (more ladders)" . PHP_EOL;
                echo "2. Medium (balanced)" . PHP_EOL;
                echo "3. Hard (more snakes)" . PHP_EOL;

                $diffChoice = $scanner->nextInt();

                $diff = match ($diffChoice) {
                    1 => Difficulty::EASY,
                    2 => Difficulty::MEDIUM,
                    3 => Difficulty::HARD,
                    default => Difficulty::MEDIUM,
                };

                $game = SnakeAndLadderGameFactory::createRandomGame($boardSize, $diff);
                $board = new Board($boardSize);
            } elseif ($choice === 3) {
                // Custom game
                echo "Enter board size (e.g., 10 for 10x10 board): ";
                $boardSize = $scanner->nextInt();

                echo "Choose custom setup type:" . PHP_EOL;
                echo "1. Specify counts only (random placement)" . PHP_EOL;
                echo "2. Specify exact positions" . PHP_EOL;

                $customChoice = $scanner->nextInt();

                if ($customChoice === 1) {
                    echo "Enter number of snakes: ";
                    $numSnakes = $scanner->nextInt();
                    echo "Enter number of ladders: ";
                    $numLadders = $scanner->nextInt();

                    $strategy = new CustomCountBoardSetupStrategy($numSnakes, $numLadders, true);
                    $game = SnakeAndLadderGameFactory::createCustomGame($boardSize, $strategy);
                } else {
                    echo "Enter number of snakes: ";
                    $numSnakes = $scanner->nextInt();
                    echo "Enter number of ladders: ";
                    $numLadders = $scanner->nextInt();

                    $strategy = new CustomCountBoardSetupStrategy($numSnakes, $numLadders, false);

                    // Get snake positions
                    for ($i = 0; $i < $numSnakes; $i++) {
                        echo "Enter snake " . ($i + 1) . " start and end positions: ";
                        $start = $scanner->nextInt();
                        $end = $scanner->nextInt();
                        $strategy->addSnakePosition($start, $end);
                    }

                    // Get ladder positions
                    for ($i = 0; $i < $numLadders; $i++) {
                        echo "Enter ladder " . ($i + 1) . " start and end positions: ";
                        $start = $scanner->nextInt();
                        $end = $scanner->nextInt();
                        $strategy->addLadderPosition($start, $end);
                    }

                    $game = SnakeAndLadderGameFactory::createCustomGame($boardSize, $strategy);
                }

                $board = new Board($boardSize);
            }

            if ($game === null) {
                echo "Invalid choice!" . PHP_EOL;
                $scanner->close();
                return;
            }

            // Add observer
            $notifier = new SnakeAndLadderConsoleNotifier();
            $game->addObserver($notifier);

            // Create players
            echo "Enter number of players: ";
            $numPlayers = $scanner->nextInt();
            $scanner->nextLine(); // consume newline

            for ($i = 0; $i < $numPlayers; $i++) {
                echo "Enter name for player " . ($i + 1) . ": ";
                $name = $scanner->nextLine();
                $player = new SnakeAndLadderPlayer($i + 1, $name);
                $game->addPlayer($player);
            }

            // Play the game
            $game->play($scanner);
        } catch (EndOfInputException $e) {
            echo PHP_EOL . "Input ended before the game finished. Exiting." . PHP_EOL;
        }

        $scanner->close();
    }
}

SnakeAndLadder::main();
