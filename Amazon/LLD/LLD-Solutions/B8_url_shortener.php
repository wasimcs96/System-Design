<?php
/**
 * B8. URL SHORTENER (like bit.ly / TinyURL)
 * ============================================================
 * PROBLEM: Generate short codes for long URLs, resolve codes
 * back to originals, handle custom aliases, expiry.
 *
 * PATTERNS:
 *  - Strategy : CodeGenerator (base62, random, hash-based)
 *  - Repository: UrlRepository
 * ============================================================
 */

// ─── Short URL Entity ──────────────────────────────────────────
class ShortUrl {
    public int $clickCount = 0;

    public function __construct(
        public readonly string   $shortCode,
        public readonly string   $originalUrl,
        public readonly string   $createdBy,
        public readonly ?\DateTime $expiresAt = null
    ) {}

    public function isExpired(): bool {
        return $this->expiresAt !== null && new \DateTime() > $this->expiresAt;
    }
}

// ─── Code Generator Strategy ───────────────────────────────────
interface CodeGeneratorStrategy {
    public function generate(string $originalUrl): string;
}

class Base62Generator implements CodeGeneratorStrategy {
    private const CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function generate(string $originalUrl): string {
        // Use CRC32 of URL + timestamp for uniqueness
        $num  = abs(crc32($originalUrl . microtime(true)));
        $code = '';
        while ($num > 0) {
            $code = self::CHARS[$num % 62] . $code;
            $num  = intdiv($num, 62);
        }
        return str_pad(substr($code, 0, 7), 7, '0', STR_PAD_LEFT);
    }
}

class HashGenerator implements CodeGeneratorStrategy {
    public function generate(string $originalUrl): string {
        return substr(md5($originalUrl . microtime(true)), 0, 7);
    }
}

// ─── URL Repository ────────────────────────────────────────────
class UrlRepository {
    /** @var array<string,ShortUrl> shortCode → ShortUrl */
    private array $byCode = [];
    /** @var array<string,string> originalUrl → shortCode */
    private array $byUrl  = [];

    public function save(ShortUrl $su): void {
        $this->byCode[$su->shortCode]  = $su;
        $this->byUrl[$su->originalUrl] = $su->shortCode;
    }

    public function findByCode(string $code): ?ShortUrl { return $this->byCode[$code] ?? null; }
    public function findByUrl(string $url): ?ShortUrl {
        $code = $this->byUrl[$url] ?? null;
        return $code ? $this->byCode[$code] : null;
    }
    public function codeExists(string $code): bool { return isset($this->byCode[$code]); }
}

// ─── URL Shortener Service ─────────────────────────────────────
class UrlShortenerService {
    private string $baseUrl = 'https://short.ly/';

    public function __construct(
        private UrlRepository       $repo,
        private CodeGeneratorStrategy $generator
    ) {}

    public function shorten(
        string    $originalUrl,
        string    $userId,
        ?string   $customAlias = null,
        ?string   $expiresIn  = null  // e.g. '+7 days'
    ): ShortUrl {
        // Return existing short URL if already shortened
        $existing = $this->repo->findByUrl($originalUrl);
        if ($existing && !$existing->isExpired()) return $existing;

        if ($customAlias) {
            if ($this->repo->codeExists($customAlias))
                throw new \InvalidArgumentException("Alias '$customAlias' already taken");
            $code = $customAlias;
        } else {
            do { $code = $this->generator->generate($originalUrl); }
            while ($this->repo->codeExists($code)); // Ensure uniqueness
        }

        $expiry = $expiresIn ? new \DateTime($expiresIn) : null;
        $su = new ShortUrl($code, $originalUrl, $userId, $expiry);
        $this->repo->save($su);
        return $su;
    }

    public function resolve(string $shortCode): ?string {
        $su = $this->repo->findByCode($shortCode);
        if (!$su) { echo "  ✗ Code not found\n"; return null; }
        if ($su->isExpired()) { echo "  ✗ Link expired\n"; return null; }
        $su->clickCount++;
        return $su->originalUrl;
    }

    public function getFullShortUrl(ShortUrl $su): string { return $this->baseUrl . $su->shortCode; }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== B8. URL Shortener ===\n\n";

$service = new UrlShortenerService(new UrlRepository(), new Base62Generator());

$su1 = $service->shorten('https://www.amazon.com/very/long/product/url', 'user1');
echo "Short URL: " . $service->getFullShortUrl($su1) . "\n";

$su2 = $service->shorten('https://leetcode.com/problems/lru-cache/', 'user2', 'lru');
echo "Short URL: " . $service->getFullShortUrl($su2) . "\n";

$resolved = $service->resolve($su1->shortCode);
echo "Resolved: $resolved\n";
echo "Clicks: {$su1->clickCount}\n";

// Idempotent — same URL returns same code
$su3 = $service->shorten('https://leetcode.com/problems/lru-cache/', 'user2', 'lru');
echo "Same alias: " . ($su2->shortCode === $su3->shortCode ? 'Yes' : 'No') . "\n";

// Try duplicate alias
try { $service->shorten('https://other.com', 'user3', 'lru'); }
catch (\InvalidArgumentException $e) { echo "Error: {$e->getMessage()}\n"; }
