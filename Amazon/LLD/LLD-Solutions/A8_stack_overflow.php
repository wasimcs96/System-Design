<?php
/**
 * A8. STACK OVERFLOW (SIMPLIFIED)
 * ============================================================
 * PROBLEM: Q&A platform with questions, answers, comments,
 * voting, tags, and reputation.
 *
 * PATTERNS:
 *  - Observer  : Notify question author on new answer
 *  - Decorator : Add badge functionality to User
 * ============================================================
 */

// ─── Tag ────────────────────────────────────────────────────────
class Tag {
    public function __construct(public readonly string $name) {}
}

// ─── Vote ───────────────────────────────────────────────────────
enum VoteType: string { case UP = 'up'; case DOWN = 'down'; }

// ─── Votable Interface ─────────────────────────────────────────
interface Votable {
    public function vote(User $voter, VoteType $type): void;
    public function getScore(): int;
}

// ─── Comment ────────────────────────────────────────────────────
class Comment {
    public readonly string   $commentId;
    public readonly \DateTime $createdAt;

    public function __construct(
        public readonly User   $author,
        public readonly string $body
    ) {
        $this->commentId = uniqid('CMT-');
        $this->createdAt = new \DateTime();
    }
}

// ─── Answer Observer ───────────────────────────────────────────
interface AnswerPostedObserver {
    public function onAnswerPosted(Question $question, Answer $answer): void;
}

// ─── User ───────────────────────────────────────────────────────
class User implements AnswerPostedObserver {
    private int   $reputation = 0;
    private array $badges     = [];

    public function __construct(
        public readonly string $userId,
        public readonly string $username
    ) {}

    public function getReputation(): int  { return $this->reputation; }
    public function addReputation(int $n): void { $this->reputation += $n; }
    public function addBadge(string $badge): void { $this->badges[] = $badge; }
    public function getBadges(): array    { return $this->badges; }

    // Observer: notified when someone answers their question
    public function onAnswerPosted(Question $question, Answer $answer): void {
        echo "  📧 [{$this->username}] Someone answered your question: '{$question->title}'\n";
    }
}

// ─── Abstract Content (shared by Question and Answer) ─────────
abstract class Content implements Votable {
    public readonly string    $contentId;
    public readonly \DateTime $createdAt;
    /** @var Comment[] */
    protected array $comments = [];
    /** @var array{user:User,type:VoteType}[] */
    protected array $votes    = [];

    public function __construct(public readonly User $author, public readonly string $body) {
        $this->contentId = uniqid();
        $this->createdAt = new \DateTime();
    }

    public function addComment(Comment $c): void { $this->comments[] = $c; }

    public function vote(User $voter, VoteType $type): void {
        // Prevent double voting by same user
        foreach ($this->votes as $v) {
            if ($v['user']->userId === $voter->userId) {
                echo "  ✗ {$voter->username} already voted\n"; return;
            }
        }
        $this->votes[] = ['user' => $voter, 'type' => $type];
        $rep = $type === VoteType::UP ? 10 : -2;
        $this->author->addReputation($rep);
    }

    public function getScore(): int {
        $score = 0;
        foreach ($this->votes as $v) $score += ($v['type'] === VoteType::UP) ? 1 : -1;
        return $score;
    }
}

// ─── Question ───────────────────────────────────────────────────
class Question extends Content {
    /** @var Answer[] */
    private array $answers   = [];
    /** @var Tag[] */
    private array $tags      = [];
    /** @var AnswerPostedObserver[] */
    private array $observers = [];
    private bool  $closed    = false;

    public function __construct(
        User   $author,
        public readonly string $title,
        string $body
    ) {
        parent::__construct($author, $body);
        $this->subscribe($author); // Author watches their own question
    }

    public function subscribe(AnswerPostedObserver $obs): void { $this->observers[] = $obs; }

    public function addTag(Tag $tag): void { $this->tags[] = $tag; }

    public function postAnswer(Answer $answer): void {
        if ($this->closed) { echo "  ✗ Question is closed\n"; return; }
        $this->answers[] = $answer;
        foreach ($this->observers as $obs) $obs->onAnswerPosted($this, $answer);
    }

    public function getAcceptedAnswer(): ?Answer {
        foreach ($this->answers as $a) if ($a->isAccepted()) return $a;
        return null;
    }

    public function getAnswersSortedByScore(): array {
        $sorted = $this->answers;
        usort($sorted, fn($a, $b) => $b->getScore() - $a->getScore());
        return $sorted;
    }

    public function close(): void { $this->closed = true; }
}

// ─── Answer ─────────────────────────────────────────────────────
class Answer extends Content {
    private bool $accepted = false;

    public function accept(): void {
        $this->accepted = true;
        $this->author->addReputation(15); // Bonus for accepted answer
    }

    public function isAccepted(): bool { return $this->accepted; }
}

// ─── Search Service ─────────────────────────────────────────────
class QuestionRepository {
    /** @var Question[] */
    private array $questions = [];

    public function add(Question $q): void { $this->questions[$q->contentId] = $q; }

    public function searchByTag(string $tagName): array {
        return array_values(array_filter($this->questions, function($q) use ($tagName) {
            foreach ($q->tags ?? [] as $t) if ($t->name === $tagName) return true;
            return false;
        }));
    }

    public function searchByTitle(string $keyword): array {
        return array_values(array_filter(
            $this->questions,
            fn($q) => stripos($q->title, $keyword) !== false
        ));
    }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== A8. Stack Overflow (Simplified) ===\n\n";

$alice = new User('U001', 'alice');
$bob   = new User('U002', 'bob');
$charlie = new User('U003', 'charlie');

$repo = new QuestionRepository();

$q = new Question($alice, 'What is Dependency Injection?',
                  'Please explain DI with examples.');
$q->addTag(new Tag('php'));
$q->addTag(new Tag('oop'));
$repo->add($q);

echo "--- Bob posts an answer ---\n";
$a1 = new Answer($bob, 'DI is a technique where an object receives its dependencies...');
$q->postAnswer($a1);

echo "\n--- Charlie also answers ---\n";
$a2 = new Answer($charlie, 'Dependency Injection decouples object creation from usage...');
$q->postAnswer($a2);

echo "\n--- Voting ---\n";
$a1->vote($alice, VoteType::UP);
$a1->vote($charlie, VoteType::UP);
$a2->vote($alice, VoteType::DOWN);
$a1->vote($alice, VoteType::UP); // Duplicate — should be blocked

echo "\n--- Accept answer ---\n";
$a1->accept();

echo "Bob's reputation: {$bob->getReputation()}\n";
echo "Charlie's reputation: {$charlie->getReputation()}\n";

$results = $repo->searchByTag('php');
echo "Questions tagged 'php': " . count($results) . "\n";
