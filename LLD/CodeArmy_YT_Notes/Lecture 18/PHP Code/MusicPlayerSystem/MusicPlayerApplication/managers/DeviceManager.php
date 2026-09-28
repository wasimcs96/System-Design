<?php

namespace MusicPlayerApplication\managers;

require_once __DIR__ . '/../enums/DeviceType.php';
require_once __DIR__ . '/../device/IAudioOutputDevice.php';
require_once __DIR__ . '/../factories/DeviceFactory.php';

use MusicPlayerApplication\device\IAudioOutputDevice;
use MusicPlayerApplication\enums\DeviceType;
use MusicPlayerApplication\factories\DeviceFactory;
use RuntimeException;

class DeviceManager
{
    private static ?DeviceManager $instance = null;
    private ?IAudioOutputDevice $currentOutputDevice;

    private function __construct()
    {
        $this->currentOutputDevice = null;
    }

    public static function getInstance(): DeviceManager
    {
        if (self::$instance === null) {
            self::$instance = new DeviceManager();
        }
        return self::$instance;
    }

    public function connect(DeviceType $deviceType): void
    {
        if ($this->currentOutputDevice !== null) {
            // In C++: delete currentOutputDevice;
            // In PHP (like Java), the garbage collector handles it, so no explicit delete.
        }

        $this->currentOutputDevice = DeviceFactory::createDevice($deviceType);

        switch ($deviceType) {
            case DeviceType::BLUETOOTH:
                echo "Bluetooth device connected " . PHP_EOL;
                break;
            case DeviceType::WIRED:
                echo "Wired device connected " . PHP_EOL;
                break;
            case DeviceType::HEADPHONES:
                echo "Headphones connected " . PHP_EOL;
                break;
        }
    }

    public function getOutputDevice(): IAudioOutputDevice
    {
        if ($this->currentOutputDevice === null) {
            throw new RuntimeException("No output device is connected.");
        }
        return $this->currentOutputDevice;
    }

    public function hasOutputDevice(): bool
    {
        return $this->currentOutputDevice !== null;
    }
}
