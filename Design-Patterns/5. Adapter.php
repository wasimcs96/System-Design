<?php
interface Book{
    public function open();
    public function turnPage();
    public function getPage();
    public function close();
}

class AlgoBook implements Book{
    private $page;
    public function open(){
        $this->page = 1;
        echo "Opening book\n";
    }
    public function turnPage(){
        $this->page++;
    }
    public function getPage(){
        echo "Reading Page $this->page \n";
    }
    public function close(){
        $this->page = 0;
        echo "Closing book\n";
    }
}

$book = new AlgoBook();
$book->open();
$book->turnPage();
$book->getPage();
$book->close();


interface Ebook{
    public function openApp();
    public function pressNextPage();
    public function viewPage();
    public function closeApp();
}

class XBook implements Ebook{
    private $page;

    public function openApp(){
        $this->page = 1;
        echo "Opening App to read book\n";
    }
    public function pressNextPage(){
        $this->page++;
        echo "Click to next page\n";
    }
    public function viewPage(){
        echo "Reading Page $this->page \n";
    }
    public function closeApp(){
        $this->page = 0;
        echo "Closing App Book\n";
    }
}

class EbookAdapter implements Book{
    public $Ebook;
    public function __construct(Ebook $Ebook){
        $this->Ebook = $Ebook;
    }

    public function open(){
        $this->Ebook->openApp();
    }
    public function turnPage(){
        $this->Ebook->pressNextPage();
    }
    public function getPage(){
        $this->Ebook->viewPage();
    }
    public function close(){
        $this->Ebook->closeApp();
    }
}

$Ebook = new XBook(); 
$EbookAdapter = new EbookAdapter($Ebook);
$EbookAdapter->open();
$EbookAdapter->turnPage();
$EbookAdapter->turnPage();
$EbookAdapter->turnPage();
$EbookAdapter->getPage();
$EbookAdapter->close();






?>