<?php
//purpose => To create series of related or dependent objects without specifying their concrete classes. Usually the created classes all implement the same interface. The client of the abstract factory does not care about how these objects are created, it just knows how they go together.
interface CsvWrite{
    public function write();
}
interface JsonWrite{
    public function JsonWrite();
}
//For Linux
class LinuxCsvWriter implements CsvWrite{
    public function write(){
        return "linux Csv Writer";
    }
}
class LinuxJsonWriter implements JsonWrite{
    public function JsonWrite(){
        return "linux Json Writer";
    }
}
//For Window
class WindowCsvWriter implements CsvWrite{
    public function write(){
        return "Window Csv Writer";
    }
}
class WindowJsonWriter implements JsonWrite{
    public function JsonWrite(){
        return "Window Json Writer";
    }
}

//create a abstract factory
abstract class AbstractWriteFactory {
    abstract function createCsvWriteClass():CsvWrite;
    abstract function createJsonWriteClass():JsonWrite;
}

//Create Linux Factory
class LinuxWriteFactory extends AbstractWriteFactory {
    function createCsvWriteClass():CsvWrite{
        return new LinuxCsvWriter();
    }
    function createJsonWriteClass():JsonWrite{
        return new LinuxJsonWriter();
    }
}

//Create Window Factory
class WindowWriteFactory extends AbstractWriteFactory {
    function createCsvWriteClass():CsvWrite{
        return new WindowCsvWriter();
    }
    function createJsonWriteClass():JsonWrite{
        return new WindowJsonWriter();
    }
}

$linuxfactoryClass = new LinuxWriteFactory();
echo $linuxfactoryClass->createCsvWriteClass()->write(); echo "\n";
echo $linuxfactoryClass->createJsonWriteClass()->JsonWrite();echo "\n";

$WindowfactoryClass = new WindowWriteFactory();
echo $WindowfactoryClass->createCsvWriteClass()->write();echo "\n";
echo $WindowfactoryClass->createJsonWriteClass()->JsonWrite();echo "\n";
?>