<?php
/**
 * B12. LOGGING FRAMEWORK (like Log4j/Monolog)
 * ============================================================
 * PROBLEM: Structured logging with multiple levels, handlers,
 * formatters, and log routing.
 *
 * PATTERNS:
 *  - Chain of Responsibility : Log level filtering
 *  - Strategy                : LogFormatter (text, JSON)
 *  - Observer/Handler        : Multiple output handlers
 * ============================================================
 */

enum LogLevel: int {
    case DEBUG   = 10;
    case INFO    = 20;
    case WARNING = 30;
    case ERROR   = 40;
    case CRITICAL= 50;
}

// ─── Log Record ───────────────────────────────────────────────
class LogRecord {
    public readonly string    $id;
    public readonly \DateTime $timestamp;

    public function __construct(
        public readonly LogLevel $level,
        public readonly string   $message,
        public readonly string   $channel = 'app',
        public readonly array    $context = []
    ) {
        $this->id        = uniqid();
        $this->timestamp = new \DateTime();
    }
}

// ─── Formatter Strategy ───────────────────────────────────────
interface LogFormatter {
    public function format(LogRecord $record): string;
}

class TextFormatter implements LogFormatter {
    public function format(LogRecord $record): string {
        $ts  = $record->timestamp->format('Y-m-d H:i:s');
        $ctx = empty($record->context) ? '' : ' ' . json_encode($record->context);
        return "[{$ts}] [{$record->level->name}] [{$record->channel}] {$record->message}{$ctx}";
    }
}

class JsonFormatter implements LogFormatter {
    public function format(LogRecord $record): string {
        return json_encode([
            'timestamp' => $record->timestamp->format('c'),
            'level'     => $record->level->name,
            'channel'   => $record->channel,
            'message'   => $record->message,
            'context'   => $record->context,
        ]);
    }
}

// ─── Handler (Chain of Responsibility) ───────────────────────
abstract class LogHandler {
    private ?LogHandler $next = null;

    public function setNext(LogHandler $handler): LogHandler {
        $this->next = $handler;
        return $handler; // Fluent chaining
    }

    public function handle(LogRecord $record): void {
        if ($record->level->value >= $this->getMinLevel()->value) {
            $this->write($record);
        }
        // Always pass to next handler (fan-out, not chain-stop)
        $this->next?->handle($record);
    }

    abstract protected function getMinLevel(): LogLevel;
    abstract protected function write(LogRecord $record): void;
}

class ConsoleHandler extends LogHandler {
    public function __construct(
        private LogFormatter $formatter,
        private LogLevel     $minLevel = LogLevel::DEBUG
    ) {}

    protected function getMinLevel(): LogLevel { return $this->minLevel; }
    protected function write(LogRecord $record): void {
        echo "  [CONSOLE] " . $this->formatter->format($record) . "\n";
    }
}

class FileHandler extends LogHandler {
    private array $buffer = []; // Simulate file writes in memory

    public function __construct(
        private LogFormatter $formatter,
        private LogLevel     $minLevel = LogLevel::WARNING,
        private string       $filename = 'app.log'
    ) {}

    protected function getMinLevel(): LogLevel { return $this->minLevel; }
    protected function write(LogRecord $record): void {
        $line = $this->formatter->format($record);
        $this->buffer[] = $line;
        echo "  [FILE:{$this->filename}] {$line}\n";
    }

    public function getBuffer(): array { return $this->buffer; }
}

// ─── Logger ───────────────────────────────────────────────────
class Logger {
    private ?LogHandler $handlerChain = null;

    public function __construct(private string $channel = 'app') {}

    public function addHandler(LogHandler $handler): void {
        if ($this->handlerChain === null) {
            $this->handlerChain = $handler;
        }
    }

    private function log(LogLevel $level, string $message, array $ctx = []): void {
        $record = new LogRecord($level, $message, $this->channel, $ctx);
        $this->handlerChain?->handle($record);
    }

    public function debug(string $msg, array $ctx = []): void    { $this->log(LogLevel::DEBUG,    $msg, $ctx); }
    public function info(string $msg, array $ctx = []): void     { $this->log(LogLevel::INFO,     $msg, $ctx); }
    public function warning(string $msg, array $ctx = []): void  { $this->log(LogLevel::WARNING,  $msg, $ctx); }
    public function error(string $msg, array $ctx = []): void    { $this->log(LogLevel::ERROR,    $msg, $ctx); }
    public function critical(string $msg, array $ctx = []): void { $this->log(LogLevel::CRITICAL, $msg, $ctx); }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== B12. Logging Framework ===\n\n";

$console = new ConsoleHandler(new TextFormatter(), LogLevel::DEBUG);
$file    = new FileHandler(new JsonFormatter(), LogLevel::WARNING, 'error.log');
$console->setNext($file);

$logger = new Logger('payment-service');
$logger->addHandler($console);

$logger->debug('Processing payment', ['orderId' => 'ORD-001']);
$logger->info('Payment gateway called');
$logger->warning('Slow response from gateway', ['latency_ms' => 3200]);
$logger->error('Payment failed', ['reason' => 'insufficient_funds']);
$logger->critical('Payment service unreachable!');
