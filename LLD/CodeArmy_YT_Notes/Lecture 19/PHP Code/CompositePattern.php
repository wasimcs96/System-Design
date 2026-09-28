<?php

// Base interface for files and folders
interface FileSystemItem
{
    public function ls(int $indent): void;
    public function openAll(int $indent): void;
    public function getSize(): int;
    public function cd(string $name): ?FileSystemItem;
    public function getName(): string;
    public function isFolder(): bool;
}

// Leaf: File
class File implements FileSystemItem
{
    private string $name;
    private int $size;

    public function __construct(string $n, int $s)
    {
        $this->name = $n;
        $this->size = $s;
    }

    public function ls(int $indent): void
    {
        $indentSpaces = str_repeat(" ", $indent);
        echo $indentSpaces . $this->name . PHP_EOL;
    }

    public function openAll(int $indent): void
    {
        $indentSpaces = str_repeat(" ", $indent);
        echo $indentSpaces . $this->name . PHP_EOL;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function cd(string $name): ?FileSystemItem
    {
        return null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isFolder(): bool
    {
        return false;
    }
}

// Composite: Folder
class Folder implements FileSystemItem
{
    private string $name;
    /** @var FileSystemItem[] */
    private array $children;

    public function __construct(string $n)
    {
        $this->name = $n;
        $this->children = [];
    }

    public function add(FileSystemItem $item): void
    {
        $this->children[] = $item;
    }

    public function ls(int $indent): void
    {
        $indentSpaces = str_repeat(" ", $indent);
        foreach ($this->children as $child) {
            if ($child->isFolder()) {
                echo $indentSpaces . "+ " . $child->getName() . PHP_EOL;
            } else {
                echo $indentSpaces . $child->getName() . PHP_EOL;
            }
        }
    }

    public function openAll(int $indent): void
    {
        $indentSpaces = str_repeat(" ", $indent);
        echo $indentSpaces . "+ " . $this->name . PHP_EOL;
        foreach ($this->children as $child) {
            $child->openAll($indent + 4);
        }
    }

    public function getSize(): int
    {
        $total = 0;
        foreach ($this->children as $child) {
            $total += $child->getSize();
        }
        return $total;
    }

    public function cd(string $target): ?FileSystemItem
    {
        foreach ($this->children as $child) {
            if ($child->isFolder() && $child->getName() === $target) {
                return $child;
            }
        }
        // not found or not a folder
        return null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isFolder(): bool
    {
        return true;
    }
}

class CompositePattern
{
    public static function main(): void
    {
        // Build file system
        $root = new Folder("root");
        $root->add(new File("file1.txt", 1));
        $root->add(new File("file2.txt", 1));

        $docs = new Folder("docs");
        $docs->add(new File("resume.pdf", 1));
        $docs->add(new File("notes.txt", 1));
        $root->add($docs);

        $images = new Folder("images");
        $images->add(new File("photo.jpg", 1));
        $root->add($images);

        $root->ls(0);

        $docs->ls(0);

        $root->openAll(0);

        $cwd = $root->cd("docs");
        if ($cwd !== null) {
            $cwd->ls(0);
        } else {
            echo PHP_EOL . "Could not cd into docs" . PHP_EOL . PHP_EOL;
        }

        echo $root->getSize() . PHP_EOL;
    }
}

CompositePattern::main();
