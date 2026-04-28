<?php
/**
 * B18. SOCIAL MEDIA FEED (like Twitter/Instagram feed)
 * ============================================================
 * PROBLEM: Users post content, follow others, and get a
 * chronological/ranked feed of posts from followed users.
 *
 * PATTERNS:
 *  - Observer  : New post notifies followers (push model)
 *  - Strategy  : FeedRanking (chronological, engagement-based)
 * ============================================================
 */

// ─── Post ─────────────────────────────────────────────────────
class Post {
    public readonly string    $postId;
    public readonly \DateTime $createdAt;
    private int  $likes    = 0;
    private int  $comments = 0;

    public function __construct(
        public readonly string $authorId,
        public readonly string $content,
        public readonly array  $tags = []
    ) {
        $this->postId    = uniqid('POST-');
        $this->createdAt = new \DateTime();
    }

    public function like(): void    { $this->likes++; }
    public function comment(): void { $this->comments++; }
    public function getLikes(): int    { return $this->likes; }
    public function getEngagement(): int { return $this->likes * 2 + $this->comments * 3; }
}

// ─── Feed Ranking Strategy ────────────────────────────────────
interface FeedRankingStrategy {
    /** @param Post[] $posts */
    public function rank(array $posts): array;
}

class ChronologicalFeed implements FeedRankingStrategy {
    public function rank(array $posts): array {
        usort($posts, fn($a, $b) => $b->createdAt <=> $a->createdAt);
        return $posts;
    }
}

class EngagementFeed implements FeedRankingStrategy {
    public function rank(array $posts): array {
        usort($posts, fn($a, $b) => $b->getEngagement() - $a->getEngagement());
        return $posts;
    }
}

// ─── User ─────────────────────────────────────────────────────
class SocialUser {
    /** @var Post[] */
    private array $posts        = [];
    /** @var string[] userIds */
    private array $following    = [];
    /** @var string[] userIds */
    private array $followers    = [];
    /** @var Post[] in-box feed from followed users */
    private array $inboxFeed    = [];

    public function __construct(
        public readonly string $userId,
        public readonly string $username
    ) {}

    public function follow(SocialUser $user): void {
        if (!in_array($user->userId, $this->following)) {
            $this->following[]         = $user->userId;
            $user->followers[]         = $this->userId;
            echo "  {$this->username} followed {$user->username}\n";
        }
    }

    public function createPost(string $content, array $tags = []): Post {
        $post = new Post($this->userId, $content, $tags);
        $this->posts[] = $post;
        return $post;
    }

    public function receivePost(Post $post): void { $this->inboxFeed[] = $post; }

    public function getPosts(): array    { return $this->posts; }
    public function getInbox(): array    { return $this->inboxFeed; }
    public function getFollowing(): array { return $this->following; }
    public function getFollowers(): array { return $this->followers; }
}

// ─── Feed Service ─────────────────────────────────────────────
class FeedService {
    /** @var SocialUser[] userId → user */
    private array $users = [];

    public function __construct(private FeedRankingStrategy $strategy) {}

    public function registerUser(SocialUser $user): void { $this->users[$user->userId] = $user; }

    public function publishPost(SocialUser $author, Post $post): void {
        echo "  📝 [{$author->username}] posted: \"" . substr($post->content, 0, 40) . "...\"\n";
        // Fan-out to all followers (push model)
        foreach ($this->users as $user) {
            if (in_array($author->userId, $user->getFollowing())) {
                $user->receivePost($post);
            }
        }
    }

    public function getFeed(SocialUser $user, int $limit = 10): array {
        $allPosts = [];
        // Pull model: aggregate posts from all followed users
        foreach ($this->users as $u) {
            if (in_array($u->userId, $user->getFollowing())) {
                $allPosts = array_merge($allPosts, $u->getPosts());
            }
        }
        $ranked = $this->strategy->rank($allPosts);
        return array_slice($ranked, 0, $limit);
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== B18. Social Media Feed ===\n\n";

$alice   = new SocialUser('U1', 'alice');
$bob     = new SocialUser('U2', 'bob');
$charlie = new SocialUser('U3', 'charlie');

$service = new FeedService(new ChronologicalFeed());
$service->registerUser($alice);
$service->registerUser($bob);
$service->registerUser($charlie);

$alice->follow($bob);
$alice->follow($charlie);

$p1 = $bob->createPost('Learning PHP design patterns!', ['php', 'oop']);
$p2 = $charlie->createPost('Amazon LLD interview tips.', ['interview']);
$p3 = $bob->createPost('Observer pattern explained.', ['php']);

$p1->like(); $p1->like(); $p1->comment();
$p3->like();

$service->publishPost($bob, $p1);
$service->publishPost($charlie, $p2);
$service->publishPost($bob, $p3);

echo "\n--- Alice's Feed (Chronological) ---\n";
$feed = $service->getFeed($alice, 5);
foreach ($feed as $post) echo "  [{$post->authorId}] {$post->content} (👍{$post->getLikes()})\n";
