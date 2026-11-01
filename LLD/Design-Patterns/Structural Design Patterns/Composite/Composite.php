<?php

interface FileSystemItem{
    public function isFolder():bool;
    public function getName():string;
    public function getSize():int;
    public function ls():void;
    public function expendAll(int $intent = 0): void;
    public function cd(string $target):?FileSystemItem; 
}

class File implements FileSystemItem{
    public string $name;
    public int $size;
    public bool $isFolder;

    public function __construct(string $name, int $size){
        $this->name = $name;
        $this->size = $size;
        $this->isFolder = false;
    }
    public function isFolder():bool{
        return $this->isFolder;
    }
    public function getName():string{
        return $this->name;
    }
    public function getSize():int{
        return $this->size;
    }
    public function ls():void{
        echo $this->name;
    }
    public function expendAll(int $intent = 0): void{
        $spaces = str_repeat(" ", $intent);
        echo $spaces;
        echo $this->isFolder == true ? "+" : "-". $this->getName(). PHP_EOL;
    }
    public function cd(string $target):?FileSystemItem{
        return null;
    } 
}

class Folder implements FileSystemItem{
    public string $name;
    public int $size;
    public bool $isFolder;
    public array $FileSystemItems;

    public function __construct(string $name){
        $this->name = $name;
        $this->size = 0;
        $this->isFolder = true;
        $this->FileSystemItems = [];
    }
    public function isFolder():bool{
        return $this->isFolder;
    }
    public function getName():string{
        return $this->name;
    }
    public function getSize():int{
        $totlaSize = 0;
        foreach($this->FileSystemItems as $fileItem){
            $totlaSize += $fileItem->getSize();
        }

        return $totlaSize;
    }
    public function ls():void{
        foreach($this->FileSystemItems as $fileItem){
            echo $this->isFolder == true ? "+" : "-" ;
            echo $fileItem->getName(). PHP_EOL;
        }
    }
    public function expendAll(int $intent = 0): void{
        $spaces = str_repeat(" ", $intent);
        echo $spaces;
        echo $this->isFolder == true ? "+" : "-" ;
        echo $this->getName(). PHP_EOL;
        foreach($this->FileSystemItems as $fileItem){
                $fileItem->expendAll($intent+1);
        }
        
    }
    public function cd(string $target):?FileSystemItem{
        if($this->isFolder){
            foreach($this->FileSystemItems as $key => $fileItem){
                if($key == $target){
                    return $fileItem;
                }
            }
        }
    } 
    public function add(FileSystemItem $folderName){
        $this->FileSystemItems[$folderName->getName()] = $folderName;
    } 
}


$root = new Folder('root');
$file_root = new File('File1', '1');
$root->add($file_root);

$child1 = new Folder('child1');
$root->add($child1);
$child1_1 = new Folder('child1_1');
$child1_2 = new Folder('child1_2');
$child1->add($child1_1);
$child1->add($child1_2);
$fie_1_1 = new File('File1_1', '1');
$child1->add($fie_1_1);


$child2 = new Folder('child2');
$root->add($child2);
$child2_1 = new Folder('child2_1');
$child2->add($child2_1);


$child3 = new Folder('child3');
$fie_3_1 = new File('File3_1', '1');
$child3->add($fie_3_1);
$root->add($child3);

echo $root->getName(). PHP_EOL;
$root->expendAll();

echo $root->getSize();
print_r($root->cd("child2"));