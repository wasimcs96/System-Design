<?php

class Light{
    public function on(){
        echo "Room light is on". PHP_EOL;
    }
    public function off(){
        echo "Room light is off". PHP_EOL;
    }
}

class Fan{
    public function on(){
        echo "Room Fan is on". PHP_EOL;
    }
    public function off(){
        echo "Room Fan is of". PHP_EOL;
    }
}

interface Command{
    public function execute();
    public function undo();
}

class LightCommand implements Command{

    public Light $light;
    public function __construct(Light $l){
        $this->light = $l;
    }
    public function execute(){
         $this->light->on();
    }
    public function undo(){
        $this->light->off();
    }
}

class FanCommand implements Command{

    public Fan $fan;
    public function __construct(Fan $l){
        $this->fan = $l;
    }
    public function execute(){
         $this->fan->on();
    }
    public function undo(){
        $this->fan->off();
    }
}

class Remote{
    public array $buttons;
    public array $buttonPressed;

    public static int $uniqueIndex = 0;

    public function __construct(){
        $this->buttons = [];
        $this->buttonPressed = [];
    }

    public function setCommands(Command $cmd){
        self::$uniqueIndex++;
        $this->buttons[self::$uniqueIndex] = $cmd;
        $this->buttonPressed[self::$uniqueIndex] = false;

        return self::$uniqueIndex;
    }

    public function preesButton(int $idx){
        if(isset($this->buttons[$idx]) && isset($this->buttonPressed[$idx])){
            if(!$this->buttonPressed[$idx]){
                $this->buttons[$idx]->execute();
                $this->buttonPressed[$idx] = true;
            }else{
                $this->buttons[$idx]->undo();
                $this->buttonPressed[$idx] = false;
            }
        }else {
            echo  "Errror : Undefined Command";
        }
    }

}

$fan = new Fan();
$light = new Light();

$fanCommand = new FanCommand($fan);
$LightCommand = new LightCommand($light);

$remote = new Remote();
$fanCommandCode = $remote->setCommands($fanCommand);
$remote->preesButton($fanCommandCode);



$LightCommandCode = $remote->setCommands($LightCommand);
//$remote->preesButton($LightCommandCode);
$remote->preesButton($LightCommandCode);
$remote->preesButton($fanCommandCode);



