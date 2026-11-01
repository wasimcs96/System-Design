<?php

// Subsystems
class PowerSupply
{
    public function providePower(): void
    {
        echo "Power Supply: Providing power..." . PHP_EOL;
    }
}

class CoolingSystem
{
    public function startFans(): void
    {
        echo "Cooling System: Fans started..." . PHP_EOL;
    }
}

class CPU
{
    public function initialize(): void
    {
        echo "CPU: Initialization started..." . PHP_EOL;
    }
}

class Memory
{
    public function selfTest(): void
    {
        echo "Memory: Self-test passed..." . PHP_EOL;
    }
}

class HardDrive
{
    public function spinUp(): void
    {
        echo "Hard Drive: Spinning up..." . PHP_EOL;
    }
}

class BIOS
{
    public function boot(CPU $cpu, Memory $memory): void
    {
        echo "BIOS: Booting CPU and Memory checks..." . PHP_EOL;
        $cpu->initialize();
        $memory->selfTest();
    }
}

class OperatingSystem
{
    public function load(): void
    {
        echo "Operating System: Loading into memory..." . PHP_EOL;
    }
}

// Facade
class ComputerFacade
{
    private PowerSupply $powerSupply;
    private CoolingSystem $coolingSystem;
    private CPU $cpu;
    private Memory $memory;
    private HardDrive $hardDrive;
    private BIOS $bios;
    private OperatingSystem $os;

    public function __construct()
    {
        // PHP can't use "new" in property defaults, so subsystems are created here.
        $this->powerSupply = new PowerSupply();
        $this->coolingSystem = new CoolingSystem();
        $this->cpu = new CPU();
        $this->memory = new Memory();
        $this->hardDrive = new HardDrive();
        $this->bios = new BIOS();
        $this->os = new OperatingSystem();
    }

    public function startComputer(): void
    {
        echo "----- Starting Computer -----" . PHP_EOL;
        $this->powerSupply->providePower();
        $this->coolingSystem->startFans();
        $this->bios->boot($this->cpu, $this->memory);
        $this->hardDrive->spinUp();
        $this->os->load();
        echo "Computer Booted Successfully!" . PHP_EOL;
    }
}

// Client
class FacadePattern
{
    public static function main(): void
    {
        $computer = new ComputerFacade();
        $computer->startComputer();
    }
}

FacadePattern::main();
