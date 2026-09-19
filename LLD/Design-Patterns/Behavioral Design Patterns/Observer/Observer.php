<?php

interface Observerable{
    public function subScribe(Observer $observer);
    public function unSubScribe(Observer $observer);
    public function notify();
    public function addData(string $data);
    public function getData();
}

class Youtube implements Observerable{
    public array $observers = [];
    public array $data = [];
    public string $latestVideo;

    public function subScribe(Observer $observer){
        $this->observers[] = $observer;
        $observer->updateObserverableList($this);
    }
    public function unSubScribe(Observer $observer){
        $key = array_search($observer, $this->observers);
        if($key !== false){
            unset($this->observers[$key]);
            $observer->removeFromObserverableList($this);
        }
    }
    public function notify(){
        foreach($this->observers as $observer){
            $observer->notifyForData($this);
        }
    }
    public function addData(string $data){
        $this->latestVideo = $data;
        $this->data[] = $data;
        $this->notify();
    }

    public function getData(){
        return $this->latestVideo;
    }
}

interface Observer{
    public function updateObserverableList(Observerable $Observerable);
    public function removeFromObserverableList(Observerable $Observerable);
    public function notifyForData(Observerable $Observerable);
}

class User implements Observer{
    public array $observableList = [];
    public string $name;

    public function __construct($name = "User"){
        $this->name = $name;
    }

    public function updateObserverableList(Observerable $Observerable){
        $this->observableList[] = $Observerable;
    }
    public function removeFromObserverableList(Observerable $Observerable){
        $key = array_search($Observerable, $this->observableList);
        if($key !== false){
            unset($this->observableList[$key]);
        }
    }
    public function notifyForData(Observerable $Observerable){
        echo $this->name." notified for data: ".$Observerable->getData()."\n";
    }
}

class client{
    function main(){
        $youtube = new Youtube();
        $user_1 = new User("Alice");
        $user_2 = new User("Bob");
        $youtube->subScribe($user_1);
        $youtube->subScribe($user_2);
        $youtube->addData("New Video Uploaded");
        $youtube->unSubScribe($user_2);
        $youtube->addData("New Video 2 Uploaded");
    }
}
//Simple Factory
$client = new client();
$client->main();
