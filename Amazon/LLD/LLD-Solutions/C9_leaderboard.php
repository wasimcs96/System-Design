<?php
/**
 * C9. LEADERBOARD SYSTEM
 * ============================================================
 * PROBLEM: Real-time leaderboard with score updates,
 * rank queries, top-K retrieval, and score history.
 *
 * PATTERNS:
 *  - Strategy : RankingStrategy (score-based, win-rate)
 *  - Observer : Rank change notifications
 * ============================================================
 */

// ─── Score Entry ──────────────────────────────────────────────
class ScoreEntry {
    public readonly \DateTime $updatedAt;
    private array $history = [];

    public function __construct(
        public readonly string $userId,
        public readonly string $username,
        private float          $score = 0.0
    ) {
        $this->updatedAt = new \DateTime();
    }

    public function addScore(float $points): void {
        $this->history[] = ['delta' => $points, 'at' => new \DateTime()];
        $this->score    += $points;
    }

    public function getScore(): float      { return $this->score; }
    public function getHistory(): array    { return $this->history; }
}

// ─── Rank Change Observer ─────────────────────────────────────
interface RankObserver {
    public function onRankChange(string $userId, int $oldRank, int $newRank): void;
}

class RankChangeNotifier implements RankObserver {
    public function onRankChange(string $userId, int $oldRank, int $newRank): void {
        if ($newRank < $oldRank) {
            echo "  🏅 {$userId} moved up: rank {$oldRank} → {$newRank}\n";
        } else {
            echo "  📉 {$userId} moved down: rank {$oldRank} → {$newRank}\n";
        }
    }
}

// ─── Leaderboard ──────────────────────────────────────────────
class Leaderboard {
    /** @var array<string,ScoreEntry> userId → entry */
    private array $entries  = [];
    /** @var array<string,int> userId → last known rank */
    private array $lastRank = [];
    /** @var RankObserver[] */
    private array $observers = [];

    public function __construct(public readonly string $name) {}

    public function addObserver(RankObserver $obs): void { $this->observers[] = $obs; }

    public function addPlayer(string $userId, string $username): void {
        $this->entries[$userId] = new ScoreEntry($userId, $username);
    }

    public function updateScore(string $userId, float $points): void {
        if (!isset($this->entries[$userId])) {
            throw new \InvalidArgumentException("User $userId not on leaderboard");
        }
        $oldRank = $this->getRank($userId);
        $this->entries[$userId]->addScore($points);
        $newRank = $this->getRank($userId);

        if ($oldRank !== $newRank) {
            foreach ($this->observers as $obs) $obs->onRankChange($userId, $oldRank, $newRank);
        }
    }

    /** Get rank of a specific user (1-indexed) */
    public function getRank(string $userId): int {
        $sorted = $this->getSorted();
        foreach ($sorted as $rank => $entry) {
            if ($entry->userId === $userId) return $rank + 1;
        }
        return -1;
    }

    /** Get score of a specific user */
    public function getScore(string $userId): float {
        return $this->entries[$userId]?->getScore() ?? 0.0;
    }

    /** Get top-K entries */
    public function getTopK(int $k): array {
        return array_slice($this->getSorted(), 0, $k);
    }

    /** Get surrounding players (rank window) */
    public function getNeighbors(string $userId, int $window = 2): array {
        $rank   = $this->getRank($userId);
        $sorted = $this->getSorted();
        $from   = max(0, $rank - 1 - $window);
        $to     = min(count($sorted), $rank - 1 + $window + 1);
        return array_slice($sorted, $from, $to - $from);
    }

    private function getSorted(): array {
        $entries = array_values($this->entries);
        usort($entries, fn($a, $b) => $b->getScore() <=> $a->getScore());
        return $entries;
    }

    public function display(int $k = 10): void {
        echo "  === {$this->name} ===\n";
        foreach ($this->getTopK($k) as $rank => $entry) {
            echo "  #" . ($rank+1) . " {$entry->username}: " . number_format($entry->getScore(), 1) . " pts\n";
        }
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C9. Leaderboard System ===\n\n";

$lb = new Leaderboard('Weekly Challenge');
$lb->addObserver(new RankChangeNotifier());
foreach ([['alice', 0], ['bob', 0], ['charlie', 0], ['dave', 0]] as [$user, $_]) {
    $lb->addPlayer($user, ucfirst($user));
}

$lb->updateScore('alice',   500);
$lb->updateScore('bob',     800);
$lb->updateScore('charlie', 300);
$lb->updateScore('dave',    600);
$lb->display();

echo "\n--- Score updates ---\n";
$lb->updateScore('alice', 400); // Alice jumps from #3 to #2
$lb->updateScore('charlie', 600); // Charlie climbs
$lb->display();

echo "\nAlice's rank: " . $lb->getRank('alice') . "\n";
echo "Neighbors of charlie:\n";
foreach ($lb->getNeighbors('charlie', 1) as $r => $e) {
    echo "  #{$e->userId}: " . $e->getScore() . "\n";
}
