<?php

// Interface for document elements
interface DocumentElement
{
    public function render(): string;
}

// Concrete implementation for text elements
class TextElement implements DocumentElement
{
    private string $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public function render(): string
    {
        return $this->text;
    }
}

// Concrete implementation for image elements
class ImageElement implements DocumentElement
{
    private string $imagePath;

    public function __construct(string $imagePath)
    {
        $this->imagePath = $imagePath;
    }

    public function render(): string
    {
        return "[Image: " . $this->imagePath . "]";
    }
}

// NewLineElement represents a line break in the document.
class NewLineElement implements DocumentElement
{
    public function render(): string
    {
        return "\n";
    }
}

// TabSpaceElement represents a tab space in the document.
class TabSpaceElement implements DocumentElement
{
    public function render(): string
    {
        return "\t";
    }
}

// Document class responsible for holding a collection of elements
class Document
{
    /** @var DocumentElement[] */
    private array $documentElements = [];

    public function addElement(DocumentElement $element): void
    {
        $this->documentElements[] = $element;
    }

    // Renders the document by concatenating the render output of all elements.
    public function render(): string
    {
        $result = "";
        foreach ($this->documentElements as $element) {
            $result .= $element->render();
        }
        return $result;
    }
}

// Persistence Interface
interface Persistence
{
    public function save(string $data): void;
}

// FileStorage implementation of Persistence
class FileStorage implements Persistence
{
    public function save(string $data): void
    {
        $written = @file_put_contents("document.txt", $data);
        if ($written === false) {
            echo "Error: Unable to open file for writing." . PHP_EOL;
            return;
        }
        echo "Document saved to document.txt" . PHP_EOL;
    }
}

// Placeholder DBStorage implementation
class DBStorage implements Persistence
{
    public function save(string $data): void
    {
        // Save to DB
    }
}

// DocumentEditor class managing client interactions
class DocumentEditor
{
    private Document $document;
    private Persistence $storage;
    private string $renderedDocument = "";

    public function __construct(Document $document, Persistence $storage)
    {
        $this->document = $document;
        $this->storage = $storage;
    }

    public function addText(string $text): void
    {
        $this->document->addElement(new TextElement($text));
    }

    public function addImage(string $imagePath): void
    {
        $this->document->addElement(new ImageElement($imagePath));
    }

    // Adds a new line to the document.
    public function addNewLine(): void
    {
        $this->document->addElement(new NewLineElement());
    }

    // Adds a tab space to the document.
    public function addTabSpace(): void
    {
        $this->document->addElement(new TabSpaceElement());
    }

    public function renderDocument(): string
    {
        if ($this->renderedDocument === "") {
            $this->renderedDocument = $this->document->render();
        }
        return $this->renderedDocument;
    }

    public function saveDocument(): void
    {
        $this->storage->save($this->renderDocument());
    }
}

// Client usage example
class DocumentEditorClient
{
    public static function main(): void
    {
        $document = new Document();
        $persistence = new FileStorage();

        $editor = new DocumentEditor($document, $persistence);

        // Simulate a client using the editor with common text formatting features.
        $editor->addText("Hello, world!");
        $editor->addNewLine();
        $editor->addText("This is a real-world document editor example.");
        $editor->addNewLine();
        $editor->addTabSpace();
        $editor->addText("Indented text after a tab space.");
        $editor->addNewLine();
        $editor->addImage("picture.jpg");

        // Render and display the final document.
        echo $editor->renderDocument() . PHP_EOL;

        $editor->saveDocument();
    }
}

DocumentEditorClient::main();
