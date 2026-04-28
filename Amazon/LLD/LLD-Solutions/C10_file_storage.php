<?php
/**
 * C10. DISTRIBUTED FILE STORAGE (like S3 simplified)
 * ============================================================
 * PROBLEM: Upload, download, list, and delete files with
 * metadata, access control, and version history.
 *
 * PATTERNS:
 *  - Repository : BucketRepository stores file metadata
 *  - Decorator  : EncryptedStorage wraps base storage with AES encryption simulation
 * ============================================================
 */

enum StorageAccessLevel: string { case PUBLIC='public'; case PRIVATE='private'; }

// ─── File Metadata ────────────────────────────────────────────
class FileObject {
    public readonly string    $objectId;
    public readonly \DateTime $uploadedAt;
    private int               $version = 1;
    private array             $versionHistory = [];

    public function __construct(
        public readonly string           $bucketName,
        public readonly string           $key,            // Path/filename
        public string                    $contentType,
        public int                       $sizeBytes,
        public StorageAccessLevel        $accessLevel,
        public readonly string           $ownerId,
        private string                   $content         // Actual content (in real: binary)
    ) {
        $this->objectId   = uniqid('OBJ-');
        $this->uploadedAt = new \DateTime();
    }

    public function update(string $newContent, string $contentType): void {
        $this->versionHistory[] = [
            'version' => $this->version, 'content' => $this->content,
            'size' => $this->sizeBytes, 'at' => new \DateTime(),
        ];
        $this->version++;
        $this->content     = $newContent;
        $this->sizeBytes   = strlen($newContent);
        $this->contentType = $contentType;
    }

    public function getContent(): string { return $this->content; }
    public function getVersion(): int    { return $this->version; }
    public function getVersionHistory(): array { return $this->versionHistory; }
}

// ─── Storage Interface ────────────────────────────────────────
interface StorageBackend {
    public function put(string $bucket, string $key, string $content, string $contentType, string $ownerId, StorageAccessLevel $acl): FileObject;
    public function get(string $bucket, string $key, string $requesterId): ?string;
    public function delete(string $bucket, string $key, string $requesterId): bool;
    public function list(string $bucket, string $prefix = ''): array;
}

// ─── In-Memory Storage ────────────────────────────────────────
class InMemoryStorage implements StorageBackend {
    /** @var array<string,array<string,FileObject>> bucket → key → object */
    private array $store = [];

    public function put(string $bucket, string $key, string $content, string $contentType, string $ownerId, StorageAccessLevel $acl): FileObject {
        if (!isset($this->store[$bucket])) $this->store[$bucket] = [];
        if (isset($this->store[$bucket][$key])) {
            $this->store[$bucket][$key]->update($content, $contentType);
            return $this->store[$bucket][$key];
        }
        $obj = new FileObject($bucket, $key, $contentType, strlen($content), $acl, $ownerId, $content);
        $this->store[$bucket][$key] = $obj;
        return $obj;
    }

    public function get(string $bucket, string $key, string $requesterId): ?string {
        $obj = $this->store[$bucket][$key] ?? null;
        if (!$obj) return null;
        if ($obj->accessLevel === StorageAccessLevel::PRIVATE && $obj->ownerId !== $requesterId) {
            throw new \RuntimeException("Access denied to {$bucket}/{$key}");
        }
        return $obj->getContent();
    }

    public function delete(string $bucket, string $key, string $requesterId): bool {
        $obj = $this->store[$bucket][$key] ?? null;
        if (!$obj || $obj->ownerId !== $requesterId) return false;
        unset($this->store[$bucket][$key]);
        return true;
    }

    public function list(string $bucket, string $prefix = ''): array {
        if (!isset($this->store[$bucket])) return [];
        $keys = array_keys($this->store[$bucket]);
        if ($prefix) $keys = array_values(array_filter($keys, fn($k) => str_starts_with($k, $prefix)));
        return $keys;
    }
}

// ─── Encryption Decorator ─────────────────────────────────────
class EncryptedStorage implements StorageBackend {
    public function __construct(private StorageBackend $inner, private string $key = 'secret') {}

    private function encrypt(string $data): string { return base64_encode($data); }  // Simulated
    private function decrypt(string $data): string { return base64_decode($data); }

    public function put(string $bucket, string $key, string $content, string $contentType, string $ownerId, StorageAccessLevel $acl): FileObject {
        return $this->inner->put($bucket, $key, $this->encrypt($content), $contentType, $ownerId, $acl);
    }

    public function get(string $bucket, string $key, string $requesterId): ?string {
        $enc = $this->inner->get($bucket, $key, $requesterId);
        return $enc !== null ? $this->decrypt($enc) : null;
    }

    public function delete(string $bucket, string $key, string $requesterId): bool {
        return $this->inner->delete($bucket, $key, $requesterId);
    }

    public function list(string $bucket, string $prefix = ''): array {
        return $this->inner->list($bucket, $prefix);
    }
}

// ─── Storage Service (Facade) ─────────────────────────────────
class StorageService {
    public function __construct(private StorageBackend $backend) {}

    public function upload(string $bucket, string $key, string $content, string $ownerId, StorageAccessLevel $acl = StorageAccessLevel::PRIVATE): FileObject {
        $ext         = pathinfo($key, PATHINFO_EXTENSION);
        $contentType = match($ext) { 'jpg','png' => 'image/'.$ext, 'pdf' => 'application/pdf', default => 'text/plain' };
        $obj = $this->backend->put($bucket, $key, $content, $contentType, $ownerId, $acl);
        echo "  ✓ Uploaded: {$bucket}/{$key} (v{$obj->getVersion()}, " . strlen($content) . "B)\n";
        return $obj;
    }

    public function download(string $bucket, string $key, string $requesterId): ?string {
        try {
            return $this->backend->get($bucket, $key, $requesterId);
        } catch (\RuntimeException $e) {
            echo "  ✗ {$e->getMessage()}\n"; return null;
        }
    }

    public function listFiles(string $bucket, string $prefix = ''): array {
        return $this->backend->list($bucket, $prefix);
    }

    public function delete(string $bucket, string $key, string $ownerId): bool {
        $result = $this->backend->delete($bucket, $key, $ownerId);
        echo ($result ? "  ✓" : "  ✗") . " Delete: {$bucket}/{$key}\n";
        return $result;
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== C10. Distributed File Storage (S3-like) ===\n\n";

$storage = new StorageService(new EncryptedStorage(new InMemoryStorage()));

$storage->upload('photos',   'alice/profile.jpg', '<binary-data>', 'alice', StorageAccessLevel::PUBLIC);
$storage->upload('documents','alice/resume.pdf',  'Resume content', 'alice', StorageAccessLevel::PRIVATE);
$storage->upload('documents','alice/notes.txt',   'Meeting notes', 'alice');

// List
$files = $storage->listFiles('documents', 'alice/');
echo "Files in documents/alice/: " . implode(', ', $files) . "\n";

// Download
$content = $storage->download('documents', 'alice/resume.pdf', 'alice');
echo "Downloaded: " . substr($content ?? '', 0, 20) . "\n";

// Access denied
$content2 = $storage->download('documents', 'alice/resume.pdf', 'bob');

// Delete
$storage->delete('documents', 'alice/notes.txt', 'alice');
$files2 = $storage->listFiles('documents', 'alice/');
echo "After delete: " . implode(', ', $files2) . "\n";
