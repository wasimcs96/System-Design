<?php

namespace MusicPlayerApplication\factories;

require_once __DIR__ . '/../enums/DeviceType.php';
require_once __DIR__ . '/../device/IAudioOutputDevice.php';
require_once __DIR__ . '/../device/BluetoothSpeakerAdapter.php';
require_once __DIR__ . '/../device/HeadphonesAdapter.php';
require_once __DIR__ . '/../device/WiredSpeakerAdapter.php';
require_once __DIR__ . '/../external/BluetoothSpeakerAPI.php';
require_once __DIR__ . '/../external/HeadphonesAPI.php';
require_once __DIR__ . '/../external/WiredSpeakerAPI.php';

use MusicPlayerApplication\device\BluetoothSpeakerAdapter;
use MusicPlayerApplication\device\HeadphonesAdapter;
use MusicPlayerApplication\device\IAudioOutputDevice;
use MusicPlayerApplication\device\WiredSpeakerAdapter;
use MusicPlayerApplication\enums\DeviceType;
use MusicPlayerApplication\external\BluetoothSpeakerAPI;
use MusicPlayerApplication\external\HeadphonesAPI;
use MusicPlayerApplication\external\WiredSpeakerAPI;

class DeviceFactory
{
    public static function createDevice(DeviceType $deviceType): IAudioOutputDevice
    {
        return match ($deviceType) {
            DeviceType::BLUETOOTH => new BluetoothSpeakerAdapter(new BluetoothSpeakerAPI()),
            DeviceType::WIRED     => new WiredSpeakerAdapter(new WiredSpeakerAPI()),
            default               => new HeadphonesAdapter(new HeadphonesAPI()), // HEADPHONES
        };
    }
}
