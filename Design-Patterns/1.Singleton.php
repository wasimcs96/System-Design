<?php

//Singleton is a creational design pattern, which ensures that only one object of its kind exists and provides a single point of access to it for any other code.

class Singleton{
    private static $instance = null; 

    public static function getInstance(){
        // Check if an instance of the class already exists. If not, create one and return it. Otherwise, just return the existing instance.
        $subclass = static::class; //get Subclass like LOgger
        if(!isset(self::$instance[$subclass])){
            self::$instance[$subclass] = new static();
        }
        return self::$instance[$subclass];
    }
}

class Logger extends Singleton{
    private $logFile;
    protected function __construct(){
        $this->logFile = "File Path";
    }
    public static function log($message) {
        $logger = static::getInstance(); //Give own refernce to Singlton Class
        $logger::writeFile($message);
    }
    public function writeFile($content){
        echo $content;
    }
}

Logger::log("Write Logssss");
?>