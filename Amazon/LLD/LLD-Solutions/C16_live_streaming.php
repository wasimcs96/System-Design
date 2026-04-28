<?php
/**
 * C16. LIVE STREAMING SYSTEM
 * ============================================================
 * PROBLEM: Streamers go live, viewers join/leave streams,
 * chat messages are sent, streams are recorded.
 *
 * PATTERNS:
 *  - Observer  : Viewers notified of stream events
 *  - State     : Stream state machine (Offline→Live→Ended)
 *  - Mediator  : StreamHub coordinates streamers ↔ viewers
 * ============================================================
 */

enum StreamState: string { case OFFLINE='Offline'; case LIVE='Live'; case ENDED='Ended'; }
enum StreamQuality: string { case SD='360p'; case HD='720p'; case FHD='1080p'; }

// ─── Chat Message ─────────────────────────────────────────────
class ChatMessage {
    public readonly \DateTime $sentAt;
    public function __construct(
        public readonly string $userId,
        public readonly string $username,
        public readonly string $text
    ) {
        $this->sentAt = new \DateTime();
    }
}

// ─── Stream Observer ──────────────────────────────────────────
interface StreamObserver {
    public function onViewerJoined(string $streamId, string $viewerName): void;
    public function onViewerLeft(string $streamId, string $viewerName): void;
    public function onChatMessage(string $streamId, ChatMessage $msg): void;
    public function onStreamEnded(string $streamId): void;
}

class StreamRecorder implements StreamObserver {
    private array $chatLog = [];
    private array $events  = [];

    public function onViewerJoined(string $streamId, string $viewerName): void {
        $this->events[] = "[+] {$viewerName} joined";
    }
    public function onViewerLeft(string $streamId, string $viewerName): void {
        $this->events[] = "[-] {$viewerName} left";
    }
    public function onChatMessage(string $streamId, ChatMessage $msg): void {
        $this->chatLog[] = "[{$msg->username}]: {$msg->text}";
    }
    public function onStreamEnded(string $streamId): void {
        echo "  📼 Recording saved: " . count($this->chatLog) . " chat msgs, " . count($this->events) . " events\n";
    }
    public function getRecording(): array { return ['events' => $this->events, 'chat' => $this->chatLog]; }
}

// ─── Live Stream ──────────────────────────────────────────────
class LiveStream {
    private StreamState    $state      = StreamState::OFFLINE;
    /** @var string[] viewerId → viewerName */
    private array          $viewers    = [];
    private array          $chatLog    = [];
    /** @var StreamObserver[] */
    private array          $observers  = [];
    public readonly string $streamId;
    private int            $peakViewers = 0;
    private ?\DateTime     $startedAt  = null;

    public function __construct(
        public readonly string        $streamerId,
        public readonly string        $title,
        public readonly StreamQuality $quality = StreamQuality::HD
    ) {
        $this->streamId = uniqid('STREAM-');
    }

    public function addObserver(StreamObserver $obs): void { $this->observers[] = $obs; }

    public function goLive(): void {
        if ($this->state !== StreamState::OFFLINE) return;
        $this->state     = StreamState::LIVE;
        $this->startedAt = new \DateTime();
        echo "  🎙️  Stream LIVE: '{$this->title}' ({$this->quality->value})\n";
    }

    public function viewerJoin(string $viewerId, string $viewerName): bool {
        if ($this->state !== StreamState::LIVE) return false;
        $this->viewers[$viewerId] = $viewerName;
        $this->peakViewers = max($this->peakViewers, count($this->viewers));
        echo "  👁️  {$viewerName} joined (viewers: " . count($this->viewers) . ")\n";
        foreach ($this->observers as $obs) $obs->onViewerJoined($this->streamId, $viewerName);
        return true;
    }

    public function viewerLeave(string $viewerId): void {
        $name = $this->viewers[$viewerId] ?? 'unknown';
        unset($this->viewers[$viewerId]);
        echo "  👋 {$name} left (viewers: " . count($this->viewers) . ")\n";
        foreach ($this->observers as $obs) $obs->onViewerLeft($this->streamId, $name);
    }

    public function sendChat(string $userId, string $username, string $text): void {
        if ($this->state !== StreamState::LIVE) return;
        $msg = new ChatMessage($userId, $username, $text);
        $this->chatLog[] = $msg;
        echo "  💬 [{$username}]: {$text}\n";
        foreach ($this->observers as $obs) $obs->onChatMessage($this->streamId, $msg);
    }

    public function end(): void {
        if ($this->state !== StreamState::LIVE) return;
        $this->state = StreamState::ENDED;
        $duration    = $this->startedAt ? (new \DateTime())->diff($this->startedAt)->s : 0;
        echo "  🔴 Stream ended | Peak viewers: {$this->peakViewers} | Duration: {$duration}s\n";
        foreach ($this->observers as $obs) $obs->onStreamEnded($this->streamId);
    }

    public function getViewerCount(): int { return count($this->viewers); }
}

// ─── Stream Hub ───────────────────────────────────────────────
class StreamHub {
    /** @var LiveStream[] streamId → stream */
    private array $activeStreams = [];

    public function startStream(string $streamerId, string $title, StreamQuality $quality = StreamQuality::HD): LiveStream {
        $stream = new LiveStream($streamerId, $title, $quality);
        $stream->addObserver(new StreamRecorder());
        $stream->goLive();
        $this->activeStreams[$stream->streamId] = $stream;
        return $stream;
    }

    public function getActiveStreams(): array { return array_values($this->activeStreams); }

    public function endStream(string $streamId): void {
        $this->activeStreams[$streamId]?->end();
        unset($this->activeStreams[$streamId]);
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C16. Live Streaming System ===\n\n";

$hub    = new StreamHub();
$stream = $hub->startStream('streamer1', 'PHP LLD Interview Tips', StreamQuality::HD);

$stream->viewerJoin('V1', 'alice');
$stream->viewerJoin('V2', 'bob');
$stream->viewerJoin('V3', 'charlie');
$stream->sendChat('V1', 'alice', 'Great stream!');
$stream->sendChat('V2', 'bob', 'Question: is PHP good for backend?');
$stream->sendChat('streamer1', 'streamer', 'Yes! PHP 8 is production-ready.');
$stream->viewerLeave('V2');
$stream->sendChat('V3', 'charlie', 'Thanks for streaming!');

echo "\nActive viewers: " . $stream->getViewerCount() . "\n";
$hub->endStream($stream->streamId);
