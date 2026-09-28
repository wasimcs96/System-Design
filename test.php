<?php

class Singleton{
    static private $instanse = [];

    private function __constructor(){}

    static function getInstance(){
        $staticClass = static::class;
        if(!isset(self::$instanse[$staticClass])){
            self::$instanse[$staticClass] = new static();
        }
        return self::$instanse[$staticClass];
    }

    static function getInstanceWithThredSafe(){
        //Lock Here
        $staticClass = static::class;
        if(!isset(self::$instanse[$staticClass])){
            self::$instanse[$staticClass] = new static();
        }
        return self::$instanse[$staticClass];
    }

    static function getInstanceWithThreadDoubleCheck(){
        $staticClass = static::class;
        if(!isset(self::$instanse[$staticClass])){
            //Lock Here
            // And Check again
            if(!isset(self::$instanse[$staticClass])){
                self::$instanse[$staticClass] = new static();
            }
            
        }
        return self::$instanse[$staticClass];
    }
}

class Logger extends Singleton{

    private function __constructor(){}

    public function  writeLogs(string $data){
        echo 'write logs';
    }

}

$logger = Logger::getInstance();
$logger->writeLogs('Wirte');
