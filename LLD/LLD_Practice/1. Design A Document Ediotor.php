<?php

interface DocumentElement{
    public function render();
}

class TextElement implements DocumentElement{
    private string $text;

    public function __construct(string $text){
        $this->text = $text;
    }

    public function render(){
        return $this->text;
    }
}

class ImageElement implements DocumentElement{
    private string $text;

    public function __construct(string $text){
        $this->text = $text;
    }

    public function render(){
        return '[Image : '.$this->text.']';
    }
}

class TabElement implements DocumentElement{
    private string $text;

    public function __construct(){
        $this->text = '\n';
    }

    public function render(){
        return $this->text;
    }
}

class Document{
    public $elementList = [];

    public function addElements(DocumentElement $element){
        $this->elementList[] = $element;
    }

    public function getElements(){
        return $this->elementList;
    }
}

class DocumentRendere{
    public Document $doc;

    public function __construct(Document $doc){
        $this->doc = $doc;
    }

    public function render(){
        $result = '';
        $elementList = $this->doc->getElements();
        foreach($elementList as $element){
            $result .= $element->render();
        }
        return $result;
    }
}

interface Persistance{
    public function save();
}

class MongoDB implements Persistance{
    public function save(){
        echo 'Save to MongoDB';
    }
}

class FileStorage implements Persistance{
    public function save(){
        echo 'Save to FileStorage';
    }
}

class DocumentEditor{
    public Document $doc;

    public function __construct(Document $doc){
        $this->doc = $doc;
    }

    public function AddText(string $text){
        $this->doc->addElements(new TextElement($text));
    }

    public function AddImage(string $text){
        $this->doc->addElements(new ImageElement($text));
    }
}

$document = new Document();
$documentRender = new DocumentRendere($document);
$documentEditor = new DocumentEditor($document);


$documentEditor->AddText('Hi');
$documentEditor->AddImage('Image Path');

$result = $documentRender->render();
echo $result;
$fileStorgae = new FileStorage($result);
$fileStorgae->save();

