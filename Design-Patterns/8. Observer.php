<?php
// Behavioral

// Purpose
// To implement a publish/subscribe behaviour to an object, whenever a “Subject” object changes its state, the attached “Observers” will be notified. It is used to shorten the amount of coupled objects and uses loose coupling instead.

// Examples
// a message queue system is observed to show the progress of a job in a GUI
// Notify Users when Stock avialble of iphone 

//Observer design pattern implementation whether and devices example

interface ObserverableTempreture{
    public function registerObserver(ObserverDevice $observer);
    public function removeObserver(ObserverDevice $observer);
    public function notifyObservers();
}
interface ObserverDevice{
    //public function notifyObservers(ObserverableTempreture $controlStation);
    public function notifyObservers();
}
class MobileDevice implements ObserverDevice{
    private ObserverableTempreture $controlStation;
    public function __construct(ObserverableTempreture $controlStation){
        $this->controlStation = $controlStation;
    }
    // public function notifyObservers(ObserverableTempreture $controlStation){
    //     echo "Mobile device temperature updated to ".$controlStation->tempretur." \n";
    // }
    public function notifyObservers(){
        echo "Mobile device temperature updated to ".$this->controlStation->tempretur." \n";
    }
}
class SmartTvDevice implements ObserverDevice{
    private ObserverableTempreture $controlStation;
    public function __construct(ObserverableTempreture $controlStation){
        $this->controlStation = $controlStation;
    }
    // public function notifyObservers(ObserverableTempreture $controlStation){
    //     echo "Smart TV device temperature updated to ".$controlStation->tempretur." \n";
    // }
    public function notifyObservers(){
        print_r($this->controlStation);
        echo "Smart TV device temperature updated to ".$this->controlStation->tempretur." \n";
    }
}
class TempretureControlStation implements ObserverableTempreture{
    private $observers = [];
    public $tempretur;

    public function __construct(int $tempretur){
        $this->tempretur = $tempretur;
    }
    public function registerObserver(ObserverDevice $observer){
        $this->observers[] = $observer;
    }
    public function removeObserver(ObserverDevice $observer){
        $key = array_search($observer, $this->observers);
        unset($this->observers[$key]);
    }
    public function notifyObservers(){
        $count = count($this->observers);
        if($count){
            for($i = 0; $i < $count; $i++){
                //$this->observers[$i]->notifyObservers($this);
                $this->observers[$i]->notifyObservers();
            }
        }
    }
    public function updateTempreture(int $tempretur){
        $this->tempretur = $tempretur;
        $this->notifyObservers();
    }

    public function getUpdateTempreture(){
        return $this->tempretur;
    }

}

$tempreturStation = new TempretureControlStation(10);

$mobileObserver = new MobileDevice($tempreturStation);
$smartTvObserver = new SmartTvDevice($tempreturStation);

$tempreturStation->registerObserver($mobileObserver);
$tempreturStation->registerObserver($smartTvObserver);
$tempreturStation->updateTempreture(25);


