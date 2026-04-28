<?php
/**
 * C17. RECOMMENDATION ENGINE
 * ============================================================
 * PROBLEM: Recommend products to users based on collaborative
 * filtering (users with similar tastes).
 *
 * APPROACH:
 *  - User-Item matrix of ratings
 *  - Cosine similarity between users
 *  - Recommend items liked by similar users that target hasn't seen
 *
 * PATTERNS:
 *  - Strategy : RecommendationStrategy (collaborative/content-based)
 * ============================================================
 */

// ─── Item Catalog ─────────────────────────────────────────────
class Item {
    public function __construct(
        public readonly string $itemId,
        public readonly string $name,
        public readonly string $category,
        public readonly array  $tags = []
    ) {}
}

// ─── Rating ───────────────────────────────────────────────────
class Rating {
    public function __construct(
        public readonly string $userId,
        public readonly string $itemId,
        public readonly float  $score,   // 1.0–5.0
        public readonly \DateTime $ratedAt = new \DateTime()
    ) {}
}

// ─── User-Item Matrix ─────────────────────────────────────────
class UserItemMatrix {
    /** @var array<string,array<string,float>> userId → itemId → score */
    private array $matrix = [];
    /** @var array<string,Item> */
    private array $items  = [];

    public function addItem(Item $item): void { $this->items[$item->itemId] = $item; }
    public function getItem(string $id): ?Item { return $this->items[$id] ?? null; }

    public function addRating(Rating $r): void {
        $this->matrix[$r->userId][$r->itemId] = $r->score;
    }

    public function getRatingsForUser(string $userId): array {
        return $this->matrix[$userId] ?? [];
    }

    public function getAllUsers(): array { return array_keys($this->matrix); }
    public function getAllItems(): array { return array_keys($this->items); }
}

// ─── Similarity Calculator ────────────────────────────────────
class SimilarityEngine {
    /** Cosine similarity between two users' rating vectors */
    public function cosineSimilarity(array $vecA, array $vecB): float {
        $allItems = array_unique(array_merge(array_keys($vecA), array_keys($vecB)));
        $dot = 0.0; $magA = 0.0; $magB = 0.0;
        foreach ($allItems as $item) {
            $a = $vecA[$item] ?? 0.0;
            $b = $vecB[$item] ?? 0.0;
            $dot  += $a * $b;
            $magA += $a * $a;
            $magB += $b * $b;
        }
        return ($magA > 0 && $magB > 0) ? $dot / (sqrt($magA) * sqrt($magB)) : 0.0;
    }
}

// ─── Collaborative Filtering Strategy ────────────────────────
interface RecommendationStrategy {
    public function recommend(string $userId, UserItemMatrix $matrix, int $k): array;
}

class CollaborativeFiltering implements RecommendationStrategy {
    public function __construct(
        private SimilarityEngine $sim,
        private int              $neighborCount = 3
    ) {}

    public function recommend(string $userId, UserItemMatrix $matrix, int $k): array {
        $userRatings = $matrix->getRatingsForUser($userId);
        $seen        = array_keys($userRatings);
        $similarities = [];

        foreach ($matrix->getAllUsers() as $otherUser) {
            if ($otherUser === $userId) continue;
            $otherRatings = $matrix->getRatingsForUser($otherUser);
            $similarities[$otherUser] = $this->sim->cosineSimilarity($userRatings, $otherRatings);
        }

        // Sort by similarity, take top neighbors
        arsort($similarities);
        $neighbors = array_slice($similarities, 0, $this->neighborCount, true);

        // Collect candidate items from neighbors (not yet seen)
        $candidateScores = [];
        foreach ($neighbors as $neighbor => $similarity) {
            foreach ($matrix->getRatingsForUser($neighbor) as $itemId => $rating) {
                if (!in_array($itemId, $seen)) {
                    $weighted = $rating * $similarity;
                    $candidateScores[$itemId] = ($candidateScores[$itemId] ?? 0) + $weighted;
                }
            }
        }

        arsort($candidateScores);
        $topK = array_slice(array_keys($candidateScores), 0, $k);
        return array_map(fn($id) => $matrix->getItem($id) ?? $id, $topK);
    }
}

// ─── Recommendation Service ───────────────────────────────────
class RecommendationService {
    public function __construct(
        private UserItemMatrix         $matrix,
        private RecommendationStrategy $strategy
    ) {}

    public function forUser(string $userId, int $k = 5): array {
        return $this->strategy->recommend($userId, $this->matrix, $k);
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C17. Recommendation Engine (Collaborative Filtering) ===\n\n";

$matrix = new UserItemMatrix();
foreach ([
    ['I1','AirPods Pro',  'electronics', ['audio','apple']],
    ['I2','MacBook Air',  'electronics', ['laptop','apple']],
    ['I3','PHP 8 Book',   'books',       ['programming','php']],
    ['I4','Clean Code',   'books',       ['programming']],
    ['I5','Yoga Mat',     'fitness',     ['health']],
    ['I6','Kindle',       'electronics', ['reading']],
] as [$id,$name,$cat,$tags]) {
    $matrix->addItem(new Item($id, $name, $cat, $tags));
}

// User ratings (1-5)
$ratings = [
    ['alice', 'I1', 5.0], ['alice', 'I2', 4.5], ['alice', 'I3', 4.0],
    ['bob',   'I1', 4.8], ['bob',   'I2', 4.0], ['bob',   'I4', 3.5], ['bob', 'I6', 4.2],
    ['carol', 'I3', 5.0], ['carol', 'I4', 4.5], ['carol', 'I6', 3.8],
    ['dave',  'I1', 4.0], ['dave',  'I5', 3.0], ['dave',  'I2', 4.5],
];
foreach ($ratings as [$user, $item, $score]) {
    $matrix->addRating(new Rating($user, $item, $score));
}

$svc = new RecommendationService($matrix, new CollaborativeFiltering(new SimilarityEngine()));

foreach (['alice', 'carol'] as $user) {
    $recs = $svc->forUser($user, 3);
    echo "Recommendations for {$user}:\n";
    foreach ($recs as $i => $item) {
        $name = $item instanceof Item ? $item->name : $item;
        echo "  " . ($i+1) . ". {$name}\n";
    }
    echo "\n";
}
