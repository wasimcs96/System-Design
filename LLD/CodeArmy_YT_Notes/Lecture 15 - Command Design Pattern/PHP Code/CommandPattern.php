<?php



// ----------------------------
// Receivers
// ----------------------------
class Light
{
    public function on(): void
    {
        echo "Light is ON" . PHP_EOL;
    }

    public function off(): void
    {
        echo "Light is OFF" . PHP_EOL;
    }
}

class Fan
{
    public function on(): void
    {
        echo "Fan is ON" . PHP_EOL;
    }

    public function off(): void
    {
        echo "Fan is OFF" . PHP_EOL;
    }
}

// ----------------------------
// Command Interface
// ----------------------------
interface Command
{
    public function execute(): void;
    public function undo(): void;
}

// ----------------------------
// Concrete Command for Light
// ----------------------------
class LightCommand implements Command
{
    private Light $light;

    public function __construct(Light $l)
    {
        $this->light = $l;
    }

    public function execute(): void
    {
        $this->light->on();
    }

    public function undo(): void
    {
        $this->light->off();
    }
}

// ----------------------------
// Concrete Command for Fan
// ----------------------------
class FanCommand implements Command
{
    private Fan $fan;

    public function __construct(Fan $f)
    {
        $this->fan = $f;
    }

    public function execute(): void
    {
        $this->fan->on();
    }

    public function undo(): void
    {
        $this->fan->off();
    }
}

// ----------------------------
// Invoker: Remote Controller with static array of 4 buttons
// ----------------------------
class RemoteController
{
    private const NUM_BUTTONS = 4;
    /** @var (Command|null)[] */
    private array $buttons;
    /** @var bool[] */
    private array $buttonPressed;

    public function __construct()
    {
        $this->buttons = [];
        $this->buttonPressed = [];
        for ($i = 0; $i < self::NUM_BUTTONS; $i++) {
            $this->buttons[$i] = null;
            $this->buttonPressed[$i] = false;  // false = off, true = on
        }
    }

    public function setCommand(int $idx, Command $cmd): void
    {
        if ($idx >= 0 && $idx < self::NUM_BUTTONS) {
            $this->buttons[$idx] = $cmd;
            $this->buttonPressed[$idx] = false;
        }
    }

    public function pressButton(int $idx): void
    {
        if ($idx >= 0 && $idx < self::NUM_BUTTONS && $this->buttons[$idx] !== null) {
            if (!$this->buttonPressed[$idx]) {
                $this->buttons[$idx]->execute();
            } else {
                $this->buttons[$idx]->undo();
            }
            $this->buttonPressed[$idx] = !$this->buttonPressed[$idx];
        } else {
            echo "No command assigned at button " . $idx . PHP_EOL;
        }
    }
}

// ----------------------------
// Main Application
// ----------------------------
class CommandPattern
{
    public static function main(): void
    {
        $livingRoomLight = new Light();
        $ceilingFan = new Fan();

        $remote = new RemoteController();

        $remote->setCommand(0, new LightCommand($livingRoomLight));
        $remote->setCommand(1, new FanCommand($ceilingFan));

        // Simulate button presses (toggle behavior)
        echo "--- Toggling Light Button 0 ---" . PHP_EOL;
        $remote->pressButton(0);  // ON
        $remote->pressButton(0);  // OFF

        echo "--- Toggling Fan Button 1 ---" . PHP_EOL;
        $remote->pressButton(1);  // ON
        $remote->pressButton(1);  // OFF

        // Press unassigned button to show default message
        echo "--- Pressing Unassigned Button 2 ---" . PHP_EOL;
        $remote->pressButton(2);
    }
}

CommandPattern::main();
