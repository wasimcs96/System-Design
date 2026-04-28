<?php
/**
 * B6. IN-MEMORY FILE SYSTEM
 * ============================================================
 * PROBLEM: Design an in-memory file system supporting mkdir,
 * ls, addContentToFile, readContentFromFile (LeetCode 588).
 *
 * PATTERNS:
 *  - Composite : FileSystemNode (files and directories share interface)
 * ============================================================
 */

// ─── Composite Node ────────────────────────────────────────────
class FileSystemNode {
    public bool   $isDirectory;
    public string $content = '';
    /** @var array<string,FileSystemNode> name → child */
    public array  $children = [];

    public function __construct(bool $isDirectory) {
        $this->isDirectory = $isDirectory;
    }
}

// ─── File System ───────────────────────────────────────────────
class FileSystem {
    private FileSystemNode $root;

    public function __construct() {
        $this->root = new FileSystemNode(true); // root is always a dir
    }

    /** List directory contents or return filename */
    public function ls(string $path): array {
        $node = $this->traverse($path);
        if (!$node->isDirectory) {
            // Path is a file — return the filename
            return [basename($path)];
        }
        $names = array_keys($node->children);
        sort($names);
        return $names;
    }

    /** Create directory path (like mkdir -p) */
    public function mkdir(string $path): void {
        $this->traverse($path, true);
    }

    /** Append content to file, creating it if needed */
    public function addContentToFile(string $filePath, string $content): void {
        $parts    = $this->parsePath($filePath);
        $filename = array_pop($parts);
        $dirNode  = $this->traverseParts($parts, true);
        if (!isset($dirNode->children[$filename])) {
            $dirNode->children[$filename] = new FileSystemNode(false);
        }
        $dirNode->children[$filename]->content .= $content;
    }

    /** Read full content of a file */
    public function readContentFromFile(string $filePath): string {
        $node = $this->traverse($filePath);
        if ($node->isDirectory) throw new \RuntimeException("$filePath is a directory");
        return $node->content;
    }

    // ─── Helpers ─────────────────────────────────────────────
    private function parsePath(string $path): array {
        return array_filter(explode('/', $path), fn($p) => $p !== '');
    }

    private function traverse(string $path, bool $createDirs = false): FileSystemNode {
        return $this->traverseParts($this->parsePath($path), $createDirs);
    }

    private function traverseParts(array $parts, bool $createDirs): FileSystemNode {
        $node = $this->root;
        foreach ($parts as $part) {
            if (!isset($node->children[$part])) {
                if (!$createDirs) throw new \RuntimeException("Path component '$part' not found");
                $node->children[$part] = new FileSystemNode(true);
            }
            $node = $node->children[$part];
        }
        return $node;
    }
}

// ─── DRIVER CODE ───────────────────────────────────────────────
echo "=== B6. In-Memory File System ===\n\n";

$fs = new FileSystem();
$fs->mkdir('/a/b/c');
$fs->addContentToFile('/a/b/c/d.txt', 'Hello');
$fs->addContentToFile('/a/b/c/d.txt', ' World');
$fs->addContentToFile('/a/b/e.txt', 'PHP LLD');

echo "ls('/')         = " . implode(', ', $fs->ls('/')) . "\n";
echo "ls('/a/b/c')    = " . implode(', ', $fs->ls('/a/b/c')) . "\n";
echo "read d.txt      = " . $fs->readContentFromFile('/a/b/c/d.txt') . "\n";
echo "read e.txt      = " . $fs->readContentFromFile('/a/b/e.txt') . "\n";
echo "ls('/a/b/c/d.txt') = " . implode(', ', $fs->ls('/a/b/c/d.txt')) . "\n";
