<?php

interface Car{
    public function getAssembleDescription();
    public function getCost();
}

class InternationalBrandCar implements Car{
    public function getAssembleDescription() :string{
        return "German Engine";
    }
    public function getCost() :float{
        return 10000;
    }
}

class LocalBrandCar implements Car{
    public function getAssembleDescription() :string{
        return "Local Engine";
    }
    public function getCost() :float{
        return 5000;
    }
}

class CarDecorater implements Car{
    public function __construct(Protected Car $car){
        $this->car = $car;
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
        return parent::getAssembleDescription() . " + Sun Roof";
    }
    public function getCost() :float{
        return parent::getCost() + 1000;
    }
}

class AutoPilotCarDecorator extends CarDecorater{
    public function getAssembleDescription() :string{
        return parent::getAssembleDescription() . "  + Auto Pilot";
    }
    public function getCost() :float{
        return parent::getCost() + 1500;
    }
}

class AutoCloseGateCarDecorator extends CarDecorater{
    public function getAssembleDescription() :string{
        return parent::getAssembleDescription() . " + Auto Close Gate";
    }
    public function getCost() :float {
        return parent::getCost() + 2000;
    }
}

$brandCar = new AutoCloseGateCarDecorator(new AutoPilotCarDecorator(new SunRoofCarDecorator(new InternationalBrandCar())));
echo "Car Description: " . $brandCar->getAssembleDescription() . "\n";
echo "Car Cost: " . $brandCar->getCost() . "\n";