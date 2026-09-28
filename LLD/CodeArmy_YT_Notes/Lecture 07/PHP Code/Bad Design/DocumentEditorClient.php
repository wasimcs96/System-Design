<?php

class DocumentEditor
{
    /** @var string[] */
    private array $documentElements;
    private string $renderedDocument;

    public function __construct()
    {
        $this->documentElements = [];
        $this->renderedDocument = "";
    }

    // Adds text as a plain string
    public function addText(string $text): void
    {
        $this->documentElements[] = $text;
    }

    // Adds an image represented by its file path
    public function addImage(string $imagePath): void
    {
        $this->documentElements[] = $imagePath;
    }

    // Renders the document by checking the type of each element at runtime
    public function renderDocument(): string
    {
        if ($this->renderedDocument === "") {
            $result = "";
            foreach ($this->documentElements as $element) {
                if (strlen($element) > 4 &&
                    (str_ends_with($element, ".jpg") || str_ends_with($element, ".png"))) {
                    $result .= "[Image: " . $element . "]\n";
                } else {
                    $result .= $element . "\n";
                }
            }
            $this->renderedDocument = $result;
        }
        return $this->renderedDocument;
    }

    public function saveToFile(): void
    {
        // @ suppresses PHP's own warning so that we print our custom error instead (like Java's catch).
        $written = @file_put_contents("document.txt", $this->renderDocument());
        if ($written === false) {
            echo "Error: Unable to open file for writing." . PHP_EOL;
            return;
        }
        echo "Document saved to document.txt" . PHP_EOL;
    }
}

class DocumentEditorClient
{
    public static function main(): void
    {
        $editor = new DocumentEditor();
        $editor->addText("Hello, world!");
        $editor->addImage("picture.jpg");
        $editor->addText("This is a document editor.");

        echo $editor->renderDocument() . PHP_EOL;

        $editor->saveToFile();
    }
}

DocumentEditorClient::main();
