<?php

namespace MusicPlayerApplication\device;

require_once __DIR__ . '/IAudioOutputDevice.php';
require_once __DIR__ . '/../models/Song.php';
require_once __DIR__ . '/../external/BluetoothSpeakerAPI.php';

use MusicPlayerApplication\external\BluetoothSpeakerAPI;
use MusicPlayerApplication\models\Song;

class BluetoothSpeakerAdapter implements IAudioOutputDevice
{
    private BluetoothSpeakerAPI $bluetoothApi;

    public function __construct(BluetoothSpeakerAPI $api)
    {
        $this->bluetoothApi = $api;
    }

    public function playAudio(Song $song): void
    {
        $payload = $song->getTitle() . " by " . $song->getArtist();
        $this->bluetoothApi->playSoundViaBluetooth($payload);
    }
}
