<?php

interface IImage
{
    public function display(): void;
}

class RealImage implements IImage
{
    private string $filename;

    public function __construct(string $file)
    {
        $this->filename = $file;
        echo "[RealImage] Loading image from disk: " . $this->filename . PHP_EOL;
    }

    public function display(): void
    {
        echo "[RealImage] Displaying " . $this->filename . PHP_EOL;
    }
}

class ImageProxy implements IImage
{
    private ?RealImage $realImage;
    private string $filename;

    public function __construct(string $file)
    {
        $this->filename = $file;
        $this->realImage = null;
    }

    public function display(): void
    {
        if ($this->realImage === null) {
            $this->realImage = new RealImage($this->filename); // lazy creation
        }
        $this->realImage->display();
    }
}

class VirtualProxy
{
    public static function main(): void
    {
        /** @var IImage $image1 */
        $image1 = new ImageProxy("sample.jpg");
        $image1->display();
    }
}

VirtualProxy::main();
