<?php

/*
NOTE (PHP): Java picks the right visit(...) overload at compile time
(visit(TextFile), visit(ImageFile), visit(VideoFile)). PHP has no method
overloading, so each element type gets its own clearly-named method:
visitTextFile(), visitImageFile(), visitVideoFile(). Double dispatch still works
the same way: element->accept(visitor) calls the matching visitor method.
*/

abstract class FileSystemItem
{
    protected string $name;

    public function __construct(string $itemName)
    {
        $this->name = $itemName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    abstract public function accept(FileSystemVisitor $visitor): void;
}

class TextFile extends FileSystemItem
{
    private string $content;

    public function __construct(string $fileName, string $fileContent)
    {
        parent::__construct($fileName);
        $this->content = $fileContent;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function accept(FileSystemVisitor $visitor): void
    {
        $visitor->visitTextFile($this);
    }
}

class ImageFile extends FileSystemItem
{
    public function __construct(string $fileName)
    {
        parent::__construct($fileName);
    }

    public function accept(FileSystemVisitor $visitor): void
    {
        $visitor->visitImageFile($this);
    }
}

class VideoFile extends FileSystemItem
{
    public function __construct(string $fileName)
    {
        parent::__construct($fileName);
    }

    public function accept(FileSystemVisitor $visitor): void
    {
        $visitor->visitVideoFile($this);
    }
}

// Visitor Interface
interface FileSystemVisitor
{
    public function visitTextFile(TextFile $file): void;
    public function visitImageFile(ImageFile $file): void;
    public function visitVideoFile(VideoFile $file): void;
}

// 1. Size calculation visitor
class SizeCalculationVisitor implements FileSystemVisitor
{
    public function visitTextFile(TextFile $file): void
    {
        echo "Calculating size for TEXT file: " . $file->getName() . PHP_EOL;
    }

    public function visitImageFile(ImageFile $file): void
    {
        echo "Calculating size for IMAGE file: " . $file->getName() . PHP_EOL;
    }

    public function visitVideoFile(VideoFile $file): void
    {
        echo "Calculating size for VIDEO file: " . $file->getName() . PHP_EOL;
    }
}

// 2. Compression Visitor
class CompressionVisitor implements FileSystemVisitor
{
    public function visitTextFile(TextFile $file): void
    {
        echo "Compressing TEXT file: " . $file->getName() . PHP_EOL;
    }

    public function visitImageFile(ImageFile $file): void
    {
        echo "Compressing IMAGE file: " . $file->getName() . PHP_EOL;
    }

    public function visitVideoFile(VideoFile $file): void
    {
        echo "Compressing VIDEO file: " . $file->getName() . PHP_EOL;
    }
}

// 3. Virus Scanning Visitor
class VirusScanningVisitor implements FileSystemVisitor
{
    public function visitTextFile(TextFile $file): void
    {
        echo "Scanning TEXT file: " . $file->getName() . PHP_EOL;
    }

    public function visitImageFile(ImageFile $file): void
    {
        echo "Scanning IMAGE file: " . $file->getName() . PHP_EOL;
    }

    public function visitVideoFile(VideoFile $file): void
    {
        echo "Scanning VIDEO file: " . $file->getName() . PHP_EOL;
    }
}

class VisitorPattern
{
    public static function main(): void
    {
        /** @var FileSystemItem $img1 */
        $img1 = new ImageFile("sample.jpg");

        $img1->accept(new SizeCalculationVisitor());
        $img1->accept(new CompressionVisitor());
        $img1->accept(new VirusScanningVisitor());

        $vid1 = new VideoFile("test.mp4");
        $vid1->accept(new CompressionVisitor());
    }
}

VisitorPattern::main();
