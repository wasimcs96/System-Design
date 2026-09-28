<?php

namespace MusicPlayerApplication\external;

class BluetoothSpeakerAPI
{
    public function playSoundViaBluetooth(string $data): void
    {
        echo "[BluetoothSpeaker] Playing: " . $data . PHP_EOL;
        // mimics playing music
    }
}
