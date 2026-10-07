<?php

interface Car{
    public function getAssembleDescription();
    public function getCost();
}

class InternationalBrandCar implements Car{
    public function getAssembleDescription() :string{
        return "German Cars With ";
    }
    public function getCost() :float{
        return 10000;
    }
}

class LocalBrandCar implements Car{
    public function getAssembleDescription() :string{
        return "Local Cars With ";
    }
    public function getCost() :float{
        return 5000;
    }
}

abstract class Decorator implements Car{
    protected Car $car;

    public function __construct(Car $car){
        $this->car = $car;
    }
}

class CarDecorater extends Decorator{

    public function __construct(Car $car){
        Parent::__construct($car);
    }

    public function getAssembleDescription() :string{
        return $this->car->getAssembleDescription();
    }
    public function getCost() :float{
        return $this->car->getCost();
    }
}

class SunRoofCarDecorator extends CarDecorater{
    public function getAssembleDescription() :string{
        return $this->car->getAssembleDescription() . "Sun Roof";
    }
    public function getCost() :float{
        return $this->car->getCost() + 100;
    }
}

class AutoPilotCarDecorator extends CarDecorater{
    public function getAssembleDescription() :string{
        return $this->car->getAssembleDescription(). " and Autopilot";
    }
    public function getCost() :float{
        return $this->car->getCost() + 50;
    }
}

class AutoCloseGateCarDecorator extends CarDecorater{
    public function getAssembleDescription() :string{
        return $this->car->getAssembleDescription() ." + AutoCloase Gate";
    }
    public function getCost() :float{
        return $this->car->getCost() + 100;
    }
}

$brandCar = new AutoCloseGateCarDecorator(
                new AutoPilotCarDecorator(
                        new InternationalBrandCar()));
echo "Car Description: " . $brandCar->getAssembleDescription() . "\n";
echo "Car Cost: " . $brandCar->getCost() . "\n";