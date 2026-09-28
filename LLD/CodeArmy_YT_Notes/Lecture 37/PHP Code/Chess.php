<?php

/*
NOTES (PHP):
 - "match" is a reserved keyword in PHP 8, so the Java class "Match" is named ChessMatch here.
 - Java used Map<Position, Piece> relying on Position.equals()/hashCode(). PHP array keys
   can only be int|string, so we key the map by Position::key() ("row,col") and store the
   Position object alongside the Piece.
*/

// Enums for better type safety
enum Color
{
    case WHITE;
    case BLACK;
}

enum PieceType
{
    case KING;
    case QUEEN;
    case ROOK;
    case BISHOP;
    case KNIGHT;
    case PAWN;
}

enum GameStatus
{
    case WAITING;
    case IN_PROGRESS;
    case COMPLETED;
    case ABORTED;
}

// Position class to represent coordinates
class Position
{
    private int $row;
    private int $col;

    // Java had Position() and Position(r, c) -> default parameters in PHP
    public function __construct(int $r = 0, int $c = 0)
    {
        $this->row = $r;
        $this->col = $c;
    }

    public function getRow(): int
    {
        return $this->row;
    }

    public function getCol(): int
    {
        return $this->col;
    }

    public function isValid(): bool
    {
        return $this->row >= 0 && $this->row < 8 && $this->col >= 0 && $this->col < 8;
    }

    public function equals(?object $obj): bool
    {
        if ($this === $obj) return true;
        if ($obj === null || get_class($this) !== get_class($obj)) return false;
        /** @var Position $obj */
        return $this->row === $obj->row && $this->col === $obj->col;
    }

    // Equivalent of hashCode(): a unique key usable as a PHP array key
    public function key(): string
    {
        return $this->row . "," . $this->col;
    }

    public function compareTo(Position $other): int
    {
        if ($this->row !== $other->row) return $this->row <=> $other->row;
        return $this->col <=> $other->col;
    }

    public function __toString(): string
    {
        return "(" . $this->row . "," . $this->col . ")";
    }

    // Convert to chess notation (e.g., e4, f7)
    public function toChessNotation(): string
    {
        $file = chr(ord('a') + $this->col);
        $rank = chr(ord('8') - $this->row);
        return $file . $rank;
    }
}

// Move class to represent a chess move
class Move
{
    private ?Position $from;
    private ?Position $to;
    private ?Piece $piece;
    private ?Piece $capturedPiece;

    public function __construct(?Position $f = null, ?Position $t = null, ?Piece $p = null, ?Piece $captured = null)
    {
        $this->from = $f;
        $this->to = $t;
        $this->piece = $p;
        $this->capturedPiece = $captured;
    }

    public function getFrom(): ?Position
    {
        return $this->from;
    }

    public function getTo(): ?Position
    {
        return $this->to;
    }

    public function getPiece(): ?Piece
    {
        return $this->piece;
    }

    public function getCapturedPiece(): ?Piece
    {
        return $this->capturedPiece;
    }
}

// Abstract Piece class following Strategy Pattern
abstract class Piece
{
    protected Color $color;
    protected PieceType $type;
    protected bool $hasMoved;

    public function __construct(Color $c, PieceType $t)
    {
        $this->color = $c;
        $this->type = $t;
        $this->hasMoved = false;
    }

    public function getColor(): Color
    {
        return $this->color;
    }

    public function getType(): PieceType
    {
        return $this->type;
    }

    public function getHasMoved(): bool
    {
        return $this->hasMoved;
    }

    public function setMoved(bool $moved): void
    {
        $this->hasMoved = $moved;
    }

    /** @return Position[] */
    abstract public function getPossibleMoves(Position $currentPos, Board $board): array;
    abstract public function getSymbol(): string;

    public function __toString(): string
    {
        $colorStr = ($this->color === Color::WHITE) ? "W" : "B";
        return $colorStr . $this->getSymbol();
    }
}

// Concrete Piece implementations
class King extends Piece
{
    public function __construct(Color $color)
    {
        parent::__construct($color, PieceType::KING);
    }

    public function getPossibleMoves(Position $currentPos, Board $board): array
    {
        $moves = [];
        $directions = [[-1, -1], [-1, 0], [-1, 1], [0, -1], [0, 1], [1, -1], [1, 0], [1, 1]];

        for ($i = 0; $i < 8; $i++) {
            $newPos = new Position($currentPos->getRow() + $directions[$i][0], $currentPos->getCol() + $directions[$i][1]);
            if ($newPos->isValid() && !$board->isOccupiedBySameColor($newPos, $this->color)) {
                $moves[] = $newPos;
            }
        }
        return $moves;
    }

    public function getSymbol(): string
    {
        return "K";
    }
}

class Queen extends Piece
{
    public function __construct(Color $color)
    {
        parent::__construct($color, PieceType::QUEEN);
    }

    public function getPossibleMoves(Position $currentPos, Board $board): array
    {
        $moves = [];
        $directions = [[-1, -1], [-1, 0], [-1, 1], [0, -1], [0, 1], [1, -1], [1, 0], [1, 1]];

        for ($d = 0; $d < 8; $d++) {
            for ($i = 1; $i < 8; $i++) {
                $newPos = new Position($currentPos->getRow() + $directions[$d][0] * $i, $currentPos->getCol() + $directions[$d][1] * $i);
                if (!$newPos->isValid()) break;

                if ($board->isOccupiedBySameColor($newPos, $this->color)) break;

                $moves[] = $newPos;
                if ($board->isOccupied($newPos)) break; // Stop after capturing
            }
        }
        return $moves;
    }

    public function getSymbol(): string
    {
        return "Q";
    }
}

class Rook extends Piece
{
    public function __construct(Color $color)
    {
        parent::__construct($color, PieceType::ROOK);
    }

    public function getPossibleMoves(Position $currentPos, Board $board): array
    {
        $moves = [];
        $directions = [[-1, 0], [1, 0], [0, -1], [0, 1]];

        for ($d = 0; $d < 4; $d++) {
            for ($i = 1; $i < 8; $i++) {
                $newPos = new Position($currentPos->getRow() + $directions[$d][0] * $i, $currentPos->getCol() + $directions[$d][1] * $i);
                if (!$newPos->isValid()) break;

                if ($board->isOccupiedBySameColor($newPos, $this->color)) break;

                $moves[] = $newPos;
                if ($board->isOccupied($newPos)) break;
            }
        }
        return $moves;
    }

    public function getSymbol(): string
    {
        return "R";
    }
}

class Bishop extends Piece
{
    public function __construct(Color $color)
    {
        parent::__construct($color, PieceType::BISHOP);
    }

    public function getPossibleMoves(Position $currentPos, Board $board): array
    {
        $moves = [];
        $directions = [[-1, -1], [-1, 1], [1, -1], [1, 1]];

        for ($d = 0; $d < 4; $d++) {
            for ($i = 1; $i < 8; $i++) {
                $newPos = new Position($currentPos->getRow() + $directions[$d][0] * $i, $currentPos->getCol() + $directions[$d][1] * $i);
                if (!$newPos->isValid()) break;
                if ($board->isOccupiedBySameColor($newPos, $this->color)) break;
                $moves[] = $newPos;
                if ($board->isOccupied($newPos)) break;
            }
        }
        return $moves;
    }

    public function getSymbol(): string
    {
        return "B";
    }
}

class Knight extends Piece
{
    public function __construct(Color $color)
    {
        parent::__construct($color, PieceType::KNIGHT);
    }

    public function getPossibleMoves(Position $currentPos, Board $board): array
    {
        $moves = [];
        $knightMoves = [[-2, -1], [-2, 1], [-1, -2], [-1, 2], [1, -2], [1, 2], [2, -1], [2, 1]];

        for ($i = 0; $i < 8; $i++) {
            $newPos = new Position($currentPos->getRow() + $knightMoves[$i][0], $currentPos->getCol() + $knightMoves[$i][1]);
            if ($newPos->isValid() && !$board->isOccupiedBySameColor($newPos, $this->color)) {
                $moves[] = $newPos;
            }
        }
        return $moves;
    }

    public function getSymbol(): string
    {
        return "N";
    }
}

class Pawn extends Piece
{
    public function __construct(Color $color)
    {
        parent::__construct($color, PieceType::PAWN);
    }

    public function getPossibleMoves(Position $currentPos, Board $board): array
    {
        $moves = [];
        $direction = ($this->color === Color::WHITE) ? -1 : 1;

        // Forward move
        $oneStep = new Position($currentPos->getRow() + $direction, $currentPos->getCol());
        if ($oneStep->isValid() && !$board->isOccupied($oneStep)) {
            $moves[] = $oneStep;

            // Double move from starting position
            if (!$this->hasMoved) {
                $twoStep = new Position($currentPos->getRow() + 2 * $direction, $currentPos->getCol());
                if ($twoStep->isValid() && !$board->isOccupied($twoStep)) {
                    $moves[] = $twoStep;
                }
            }
        }

        // Diagonal captures
        $leftCapture = new Position($currentPos->getRow() + $direction, $currentPos->getCol() - 1);
        $rightCapture = new Position($currentPos->getRow() + $direction, $currentPos->getCol() + 1);

        if ($leftCapture->isValid() && $board->isOccupied($leftCapture) &&
            !$board->isOccupiedBySameColor($leftCapture, $this->color)) {
            $moves[] = $leftCapture;
        }

        if ($rightCapture->isValid() && $board->isOccupied($rightCapture) &&
            !$board->isOccupiedBySameColor($rightCapture, $this->color)) {
            $moves[] = $rightCapture;
        }

        return $moves;
    }

    public function getSymbol(): string
    {
        return "P";
    }
}

// Factory Pattern for creating pieces
class PieceFactory
{
    public static function createPiece(PieceType $type, Color $color): Piece
    {
        return match ($type) {
            PieceType::KING   => new King($color),
            PieceType::QUEEN  => new Queen($color),
            PieceType::ROOK   => new Rook($color),
            PieceType::BISHOP => new Bishop($color),
            PieceType::KNIGHT => new Knight($color),
            PieceType::PAWN   => new Pawn($color),
        };
    }
}

// Board class - Dumb object that manages pieces
class Board
{
    /** @var array<int, array<int, ?Piece>> */
    private array $board;
    /** @var array<string, array{pos: Position, piece: Piece}> "row,col" -> [Position, Piece] */
    private array $piecePositions;

    public function __construct()
    {
        // Initialize board to null
        $this->board = array_fill(0, 8, array_fill(0, 8, null));
        $this->piecePositions = [];
        $this->initializeBoard();
    }

    public function initializeBoard(): void
    {
        // Initialize white pieces
        $this->placePiece(new Position(7, 0), PieceFactory::createPiece(PieceType::ROOK, Color::WHITE));
        $this->placePiece(new Position(7, 1), PieceFactory::createPiece(PieceType::KNIGHT, Color::WHITE));
        $this->placePiece(new Position(7, 2), PieceFactory::createPiece(PieceType::BISHOP, Color::WHITE));
        $this->placePiece(new Position(7, 3), PieceFactory::createPiece(PieceType::QUEEN, Color::WHITE));
        $this->placePiece(new Position(7, 4), PieceFactory::createPiece(PieceType::KING, Color::WHITE));
        $this->placePiece(new Position(7, 5), PieceFactory::createPiece(PieceType::BISHOP, Color::WHITE));
        $this->placePiece(new Position(7, 6), PieceFactory::createPiece(PieceType::KNIGHT, Color::WHITE));
        $this->placePiece(new Position(7, 7), PieceFactory::createPiece(PieceType::ROOK, Color::WHITE));

        for ($i = 0; $i < 8; $i++) {
            $this->placePiece(new Position(6, $i), PieceFactory::createPiece(PieceType::PAWN, Color::WHITE));
        }

        // Initialize black pieces
        $this->placePiece(new Position(0, 0), PieceFactory::createPiece(PieceType::ROOK, Color::BLACK));
        $this->placePiece(new Position(0, 1), PieceFactory::createPiece(PieceType::KNIGHT, Color::BLACK));
        $this->placePiece(new Position(0, 2), PieceFactory::createPiece(PieceType::BISHOP, Color::BLACK));
        $this->placePiece(new Position(0, 3), PieceFactory::createPiece(PieceType::QUEEN, Color::BLACK));
        $this->placePiece(new Position(0, 4), PieceFactory::createPiece(PieceType::KING, Color::BLACK));
        $this->placePiece(new Position(0, 5), PieceFactory::createPiece(PieceType::BISHOP, Color::BLACK));
        $this->placePiece(new Position(0, 6), PieceFactory::createPiece(PieceType::KNIGHT, Color::BLACK));
        $this->placePiece(new Position(0, 7), PieceFactory::createPiece(PieceType::ROOK, Color::BLACK));

        for ($i = 0; $i < 8; $i++) {
            $this->placePiece(new Position(1, $i), PieceFactory::createPiece(PieceType::PAWN, Color::BLACK));
        }
    }

    public function placePiece(Position $pos, Piece $piece): void
    {
        $this->board[$pos->getRow()][$pos->getCol()] = $piece;
        $this->piecePositions[$pos->key()] = ['pos' => $pos, 'piece' => $piece];
    }

    public function removePiece(Position $pos): void
    {
        $this->board[$pos->getRow()][$pos->getCol()] = null;
        unset($this->piecePositions[$pos->key()]);
    }

    public function getPiece(Position $pos): ?Piece
    {
        return $this->board[$pos->getRow()][$pos->getCol()];
    }

    public function isOccupied(Position $pos): bool
    {
        return $this->getPiece($pos) !== null;
    }

    public function isOccupiedBySameColor(Position $pos, Color $color): bool
    {
        $piece = $this->getPiece($pos);
        return $piece !== null && $piece->getColor() === $color;
    }

    public function movePiece(Position $from, Position $to): void
    {
        $piece = $this->getPiece($from);
        if ($piece !== null) {
            // Remove captured piece if any
            $capturedPiece = $this->getPiece($to);
            if ($capturedPiece !== null) {
                unset($this->piecePositions[$to->key()]);
            }

            // Move the piece
            $this->board[$from->getRow()][$from->getCol()] = null;
            $this->board[$to->getRow()][$to->getCol()] = $piece;

            // Update piece positions map
            unset($this->piecePositions[$from->key()]);
            $this->piecePositions[$to->key()] = ['pos' => $to, 'piece' => $piece];

            $piece->setMoved(true);
        }
    }

    public function findKing(Color $color): Position
    {
        foreach ($this->piecePositions as $entry) {
            if ($entry['piece']->getType() === PieceType::KING && $entry['piece']->getColor() === $color) {
                return $entry['pos'];
            }
        }
        return new Position(-1, -1); // Invalid position if not found
    }

    /** @return Position[] */
    public function getAllPiecesOfColor(Color $color): array
    {
        $pieces = [];
        foreach ($this->piecePositions as $entry) {
            if ($entry['piece']->getColor() === $color) {
                $pieces[] = $entry['pos'];
            }
        }
        return $pieces;
    }

    public function display(): void
    {
        $cellW = 3;  // cell width

        // — horizontal border —
        $printBorder = function () use ($cellW): void {
            echo "  +";
            for ($i = 0; $i < 8; ++$i)
                echo str_repeat("-", $cellW) . "+";
            echo PHP_EOL;
        };

        // — top border —
        $printBorder();

        // — column labels inside the grid —
        echo "  |";
        foreach (range('a', 'h') as $f) {
            $pad = intdiv($cellW - 1, 2);
            echo str_repeat(" ", $pad) . $f . str_repeat(" ", $cellW - 1 - $pad) . "|";
        }
        echo PHP_EOL;

        // — border under labels —
        $printBorder();

        // — each rank of pieces —
        for ($rank = 8; $rank >= 1; --$rank) {
            $row = 8 - $rank;
            echo $rank . " |";

            for ($file = 0; $file < 8; ++$file) {
                $p = $this->board[$row][$file];
                $s = $p !== null ? (string)$p : "  ";  // two spaces if empty

                // center a 2-char string in cellW
                $pad = intdiv($cellW - 2, 2);
                echo str_repeat(" ", $pad) . $s . str_repeat(" ", $cellW - 2 - $pad) . "|";
            }

            echo " " . $rank . PHP_EOL;
            $printBorder();
        }

        // — bottom labels inside the grid —
        echo "  |";
        foreach (range('a', 'h') as $f) {
            $pad = intdiv($cellW - 1, 2);
            echo str_repeat(" ", $pad) . $f . str_repeat(" ", $cellW - 1 - $pad) . "|";
        }
        echo PHP_EOL;

        // — final border —
        $printBorder();
    }
}

// Chess Rules interface - Strategy Pattern for game rules
interface ChessRules
{
    public function isValidMove(Move $move, Board $board): bool;
    public function isInCheck(Color $color, Board $board): bool;
    public function isCheckmate(Color $color, Board $board): bool;
    public function isStalemate(Color $color, Board $board): bool;
    public function wouldMoveCauseCheck(Move $move, Board $board, Color $kingColor): bool;
}

class StandardChessRules implements ChessRules
{
    public function isValidMove(Move $move, Board $board): bool
    {
        $piece = $move->getPiece();
        $possibleMoves = $piece->getPossibleMoves($move->getFrom(), $board);

        // Check if the target position is in possible moves
        $validDestination = false;
        foreach ($possibleMoves as $pos) {
            if ($pos->equals($move->getTo())) {
                $validDestination = true;
                break;
            }
        }

        if (!$validDestination) {
            return false;
        }

        // Check if move would put own king in check
        return !$this->wouldMoveCauseCheck($move, $board, $piece->getColor());
    }

    public function wouldMoveCauseCheck(Move $move, Board $board, Color $kingColor): bool
    {
        // Create a temporary copy to simulate the move safely
        $movingPiece = $board->getPiece($move->getFrom());
        $capturedPiece = $board->getPiece($move->getTo());

        if ($movingPiece === null) return true; // Invalid move

        // Temporarily execute the move
        $board->removePiece($move->getFrom());
        if ($capturedPiece !== null) {
            $board->removePiece($move->getTo());
        }
        $board->placePiece($move->getTo(), $movingPiece);

        // Check if king is in check after the move
        $inCheck = $this->isInCheck($kingColor, $board);

        // Undo the move
        $board->removePiece($move->getTo());
        $board->placePiece($move->getFrom(), $movingPiece);
        if ($capturedPiece !== null) {
            $board->placePiece($move->getTo(), $capturedPiece);
        }

        return $inCheck;
    }

    public function isInCheck(Color $color, Board $board): bool
    {
        $kingPos = $board->findKing($color);
        if ($kingPos->getRow() === -1) return false; // King not found

        $opponentColor = ($color === Color::WHITE) ? Color::BLACK : Color::WHITE;
        $opponentPieces = $board->getAllPiecesOfColor($opponentColor);

        foreach ($opponentPieces as $pos) {
            $piece = $board->getPiece($pos);
            $moves = $piece->getPossibleMoves($pos, $board);
            foreach ($moves as $targetPos) {
                if ($targetPos->equals($kingPos)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function isCheckmate(Color $color, Board $board): bool
    {
        if (!$this->isInCheck($color, $board)) return false;

        $pieces = $board->getAllPiecesOfColor($color);
        foreach ($pieces as $pos) {
            $piece = $board->getPiece($pos);
            $moves = $piece->getPossibleMoves($pos, $board);

            foreach ($moves as $targetPos) {
                $move = new Move($pos, $targetPos, $piece, $board->getPiece($targetPos));
                if ($this->isValidMove($move, $board)) {
                    return false; // Found a valid move, not checkmate
                }
            }
        }
        return true;
    }

    public function isStalemate(Color $color, Board $board): bool
    {
        if ($this->isInCheck($color, $board)) return false;

        $pieces = $board->getAllPiecesOfColor($color);
        foreach ($pieces as $pos) {
            $piece = $board->getPiece($pos);
            $moves = $piece->getPossibleMoves($pos, $board);

            foreach ($moves as $targetPos) {
                $move = new Move($pos, $targetPos, $piece, $board->getPiece($targetPos));
                if ($this->isValidMove($move, $board)) {
                    return false; // Found a valid move, not stalemate
                }
            }
        }
        return true;
    }
}

// Message class for chat functionality
class Message
{
    private string $senderId;
    private string $content;
    private int $timestamp;

    public function __construct(string $sId, string $msg)
    {
        $this->senderId = $sId;
        $this->content = $msg;
        $this->timestamp = (int) floor(microtime(true) * 1000);
    }

    public function getSenderId(): string
    {
        return $this->senderId;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function __toString(): string
    {
        return "[" . $this->senderId . "]: " . $this->content;
    }
}

// Mediator Pattern - Interface
interface ChatMediator
{
    public function sendMessage(Message $message, User $user): void;
    public function addUser(User $user): void;
    public function removeUser(User $user): void;
}

// Colleague interface for Mediator Pattern
abstract class Colleague
{
    protected ?ChatMediator $mediator;

    public function __construct()
    {
        $this->mediator = null;
    }

    abstract public function send(Message $message): void;
    abstract public function receive(Message $message): void;

    public function setMediator(ChatMediator $med): void
    {
        $this->mediator = $med;
    }

    public function getMediator(): ?ChatMediator
    {
        return $this->mediator;
    }
}

// User class now inheriting from Colleague for proper Mediator Pattern
class User extends Colleague
{
    private string $id;
    private string $name;
    private int $score;

    public function __construct(string $userId, string $userName)
    {
        parent::__construct();
        $this->id = $userId;
        $this->name = $userName;
        $this->score = 1000; // Starting score
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function incrementScore(int $points): void
    {
        $this->score += $points;
    }

    public function decrementScore(int $points): void
    {
        $this->score -= $points;
    }

    public function __toString(): string
    {
        return $this->name . " (Score: " . $this->score . ")";
    }

    // Implement Colleague interface
    public function send(Message $message): void
    {
        if ($this->mediator !== null) {
            $this->mediator->sendMessage($message, $this);
        }
    }

    public function receive(Message $message): void
    {
        echo "User " . $this->name . " received message from " . $message->getSenderId() . ": " . $message->getContent() . PHP_EOL;
    }
}

// Match class implementing Mediator Pattern ("Match" is reserved in PHP -> ChessMatch)
class ChessMatch implements ChatMediator
{
    private string $matchId;
    private User $whitePlayer;
    private User $blackPlayer;
    private Board $board;
    private ChessRules $rules;
    private Color $currentTurn;
    private GameStatus $status;
    /** @var Move[] */
    private array $moveHistory;
    /** @var Message[] */
    private array $chatHistory;

    public function __construct(string $mId, User $white, User $black)
    {
        $this->matchId = $mId;
        $this->whitePlayer = $white;
        $this->blackPlayer = $black;
        $this->board = new Board();
        $this->rules = new StandardChessRules();
        $this->currentTurn = Color::WHITE;
        $this->status = GameStatus::IN_PROGRESS;
        $this->moveHistory = [];
        $this->chatHistory = [];

        // Set mediator for both users
        $this->whitePlayer->setMediator($this);
        $this->blackPlayer->setMediator($this);

        echo "Match started between " . $this->whitePlayer->getName() . " (White) and "
            . $this->blackPlayer->getName() . " (Black)" . PHP_EOL;
    }

    public function makeMove(Position $from, Position $to, User $player): bool
    {
        if ($this->status !== GameStatus::IN_PROGRESS) {
            echo "Game is not in progress!" . PHP_EOL;
            return false;
        }

        $playerColor = $this->getPlayerColor($player);
        if ($playerColor !== $this->currentTurn) {
            echo "It's not your turn!" . PHP_EOL;
            return false;
        }

        $piece = $this->board->getPiece($from);
        if ($piece === null || $piece->getColor() !== $playerColor) {
            echo "Invalid piece selection!" . PHP_EOL;
            return false;
        }

        $move = new Move($from, $to, $piece, $this->board->getPiece($to));

        if (!$this->rules->isValidMove($move, $this->board)) {
            echo "Invalid move!" . PHP_EOL;
            return false;
        }

        // Execute move
        $this->board->movePiece($from, $to);
        $this->moveHistory[] = $move;

        echo $player->getName() . " moved " . $piece->getSymbol()
            . " from " . $from->toChessNotation() . " to " . $to->toChessNotation() . PHP_EOL;

        $this->board->display();

        // Check game end conditions
        $opponentColor = ($this->currentTurn === Color::WHITE) ? Color::BLACK : Color::WHITE;
        if ($this->rules->isCheckmate($opponentColor, $this->board)) {
            $this->endGame($player, "checkmate");
            return true;
        } elseif ($this->rules->isStalemate($opponentColor, $this->board)) {
            $this->endGame($player, "stalemate");
            return true;
        } else {
            $this->currentTurn = $opponentColor;
            if ($this->rules->isInCheck($opponentColor, $this->board)) {
                echo $this->getPlayerByColor($opponentColor)->getName() . " is in check!" . PHP_EOL;
            }
        }

        return true;
    }

    public function quitGame(User $player): void
    {
        $opponent = ($player === $this->whitePlayer) ? $this->blackPlayer : $this->whitePlayer;
        $this->endGame($opponent, "quit");
        $player->decrementScore(50); // Penalty for quitting
        echo $player->getName() . " quit the game. Score decreased by 50." . PHP_EOL;
    }

    public function endGame(?User $winner, string $reason): void
    {
        $this->status = GameStatus::COMPLETED;

        if ($winner !== null) {
            $loser = ($winner === $this->whitePlayer) ? $this->blackPlayer : $this->whitePlayer;
            $winner->incrementScore(30);
            $loser->decrementScore(20);
            echo "Game ended - " . $winner->getName() . " wins by " . $reason . "!" . PHP_EOL;
            echo "Score update: " . $winner->getName() . " +30, " . $loser->getName() . " -20" . PHP_EOL;
        } else {
            echo "Game ended in " . $reason . "! No score change." . PHP_EOL;
        }
    }

    public function getPlayerColor(User $player): Color
    {
        return ($player === $this->whitePlayer) ? Color::WHITE : Color::BLACK;
    }

    public function getPlayerByColor(Color $color): User
    {
        return ($color === Color::WHITE) ? $this->whitePlayer : $this->blackPlayer;
    }

    // Mediator Pattern implementation
    public function sendMessage(Message $message, User $user): void
    {
        $this->chatHistory[] = $message;

        $recipient = ($user === $this->whitePlayer) ? $this->blackPlayer : $this->whitePlayer;
        $recipient->receive($message);
        echo "Chat in match " . $this->matchId . " - " . $message->getContent() . PHP_EOL;
    }

    public function addUser(User $user): void
    {
        // Not applicable for chess match (always 2 users)
    }

    public function removeUser(User $user): void
    {
        $this->quitGame($user);
    }

    public function getMatchId(): string
    {
        return $this->matchId;
    }

    public function getStatus(): GameStatus
    {
        return $this->status;
    }

    public function getWhitePlayer(): User
    {
        return $this->whitePlayer;
    }

    public function getBlackPlayer(): User
    {
        return $this->blackPlayer;
    }

    public function getBoard(): Board
    {
        return $this->board;
    }
}

// Matching Strategy interface
interface MatchingStrategy
{
    /** @param User[] $waitingUsers */
    public function findMatch(User $user, array $waitingUsers): ?User;
}

// Score-based matching strategy
class ScoreBasedMatching implements MatchingStrategy
{
    private int $scoreTolerance;

    public function __construct(int $tolerance)
    {
        $this->scoreTolerance = $tolerance;
    }

    public function findMatch(User $user, array $waitingUsers): ?User
    {
        $bestMatch = null;
        $bestScoreDiff = PHP_INT_MAX;

        foreach ($waitingUsers as $waitingUser) {
            if ($waitingUser->getId() !== $user->getId()) {
                $scoreDiff = abs($waitingUser->getScore() - $user->getScore());
                if ($scoreDiff <= $this->scoreTolerance && $scoreDiff < $bestScoreDiff) {
                    $bestMatch = $waitingUser;
                    $bestScoreDiff = $scoreDiff;
                }
            }
        }
        return $bestMatch;
    }
}

// Game Manager - Singleton Pattern
class GameManager
{
    private static ?GameManager $instance = null;
    /** @var array<string, ChessMatch> matchId --> Match */
    private array $activeMatches;
    /** @var User[] */
    private array $waitingUsers;
    private MatchingStrategy $matchingStrategy;
    private int $matchCounter;

    private function __construct()
    {
        $this->activeMatches = [];
        $this->waitingUsers = [];
        $this->matchingStrategy = new ScoreBasedMatching(100); // 100 points tolerance
        $this->matchCounter = 0;
    }

    public static function getInstance(): GameManager
    {
        if (self::$instance === null) {
            self::$instance = new GameManager();
        }
        return self::$instance;
    }

    public function requestMatch(User $user): void
    {
        echo $user->getName() . " is looking for a match..." . PHP_EOL;

        $opponent = $this->matchingStrategy->findMatch($user, $this->waitingUsers);

        if ($opponent !== null) {
            // Remove opponent from waiting list
            $idx = array_search($opponent, $this->waitingUsers, true);
            if ($idx !== false) {
                array_splice($this->waitingUsers, $idx, 1);
            }

            $matchId = "MATCH_" . (++$this->matchCounter);
            $match = new ChessMatch($matchId, $user, $opponent);
            $this->activeMatches[$matchId] = $match;

            echo "Match found! " . $user->getName() . " vs " . $opponent->getName() . PHP_EOL;
            $match->getBoard()->display();
        } else {
            $this->waitingUsers[] = $user;
            echo $user->getName() . " added to waiting list." . PHP_EOL;
        }
    }

    public function makeMove(string $matchId, Position $from, Position $to, User $player): void
    {
        if (array_key_exists($matchId, $this->activeMatches)) {
            $match = $this->activeMatches[$matchId];
            $match->makeMove($from, $to, $player);

            if ($match->getStatus() === GameStatus::COMPLETED) {
                unset($this->activeMatches[$matchId]);
                echo "Match " . $matchId . " completed and removed from active matches." . PHP_EOL;
            }
        }
    }

    public function quitMatch(string $matchId, User $player): void
    {
        if (array_key_exists($matchId, $this->activeMatches)) {
            $match = $this->activeMatches[$matchId];
            $match->quitGame($player);
            unset($this->activeMatches[$matchId]);
        }
    }

    public function sendChatMessage(string $matchId, string $message, User $user): void
    {
        if (array_key_exists($matchId, $this->activeMatches)) {
            $match = $this->activeMatches[$matchId];
            $msg = new Message($user->getId(), $message);
            $match->sendMessage($msg, $user);
        }
    }

    public function getMatch(string $matchId): ?ChessMatch
    {
        return $this->activeMatches[$matchId] ?? null;
    }

    public function displayActiveMatches(): void
    {
        echo PHP_EOL . "=== Active Matches ===" . PHP_EOL;
        foreach ($this->activeMatches as $match) {
            echo "Match " . $match->getMatchId() . ": "
                . $match->getWhitePlayer()->getName() . " vs "
                . $match->getBlackPlayer()->getName() . PHP_EOL;
        }
        echo "Total active matches: " . count($this->activeMatches) . PHP_EOL;
        echo "Users waiting: " . count($this->waitingUsers) . PHP_EOL;
    }
}

// Util class for basic demo
class ChessSystemDemo
{
    // Method to demonstrate Scholar's Mate (4-move checkmate)
    public static function demonstrateScholarsMate(): void
    {
        echo PHP_EOL . "=== Scholar's Mate Demo (4-move checkmate) ===" . PHP_EOL;

        $aditya = new User("DEMO_1", "Aditya");
        $rohit = new User("DEMO_2", "Rohit");

        $demoMatch = new ChessMatch("DEMO_MATCH", $aditya, $rohit);
        $demoMatch->getBoard()->display();

        // Proper Scholar's Mate sequence with correct coordinates
        echo PHP_EOL . "Move 1: White e2-e4" . PHP_EOL;
        $demoMatch->makeMove(new Position(6, 4), new Position(4, 4), $aditya); // e2-e4

        echo PHP_EOL . "Move 1: Black e7-e5" . PHP_EOL;
        $demoMatch->makeMove(new Position(1, 4), new Position(3, 4), $rohit); // e7-e5

        echo PHP_EOL . "Move 2: White Bf1-c4 (targeting f7)" . PHP_EOL;
        $demoMatch->makeMove(new Position(7, 5), new Position(4, 2), $aditya); // Bf1-c4

        echo PHP_EOL . "Move 2: Black Nb8-c6 (developing)" . PHP_EOL;
        $demoMatch->makeMove(new Position(0, 1), new Position(2, 2), $rohit); // Nb8-c6

        echo PHP_EOL . "Move 3: White Qd1-h5 (attacking f7 and h7)" . PHP_EOL;
        $demoMatch->makeMove(new Position(7, 3), new Position(3, 7), $aditya); // Qd1-h5 (row 3, col 7 = h5)

        echo PHP_EOL . "Move 3: Black Ng8-f6?? (defending h7 but exposing f7)" . PHP_EOL;
        $demoMatch->makeMove(new Position(0, 6), new Position(2, 5), $rohit); // Ng8-f6

        echo PHP_EOL . "Move 4: White Qh5xf7# (Checkmate!)" . PHP_EOL;
        $gameEnded = $demoMatch->makeMove(new Position(3, 7), new Position(1, 5), $aditya); // Qh5xf7#

        if ($demoMatch->getStatus() !== GameStatus::COMPLETED) {
            echo "Note: Checkmate detection may need refinement for this position." . PHP_EOL;
        }

        // Demonstrate chat functionality
        echo PHP_EOL . "=== Testing Chat Functionality ===" . PHP_EOL;
        $aditya->send(new Message($aditya->getId(), "Good game!"));
        $rohit->send(new Message($rohit->getId(), "Thanks, that was a quick one!"));
    }
}

// Main class to run the chess system
class Chess
{
    public static function main(): void
    {
        echo "=== Chess System with Design Patterns Demo ===" . PHP_EOL;

        // Test Scholar's Mate
        ChessSystemDemo::demonstrateScholarsMate();

        // Demonstrate Game Manager functionality
        echo PHP_EOL . "=== Game Manager Demo ===" . PHP_EOL;
        $gm = GameManager::getInstance();

        $saurav = new User("USER_1", "Saurav");
        $manish = new User("USER_2", "Manish");
        $abhishek = new User("USER_3", "Abishek");

        echo PHP_EOL . "Users: " . $saurav . ", " . $manish . ", " . $abhishek . PHP_EOL;

        // Request matches
        $gm->requestMatch($saurav);
        $gm->requestMatch($manish);  // Should create a match
        $gm->requestMatch($abhishek); // Should go to waiting list

        $gm->displayActiveMatches();
    }
}

Chess::main();
