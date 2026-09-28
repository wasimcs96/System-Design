<?php

namespace MusicPlayerApplication\device;

require_once __DIR__ . '/IAudioOutputDevice.php';
require_once __DIR__ . '/../models/Song.php';
require_once __DIR__ . '/../external/HeadphonesAPI.php';

use MusicPlayerApplication\external\HeadphonesAPI;
use MusicPlayerApplication\models\Song;

class HeadphonesAdapter implements IAudioOutputDevice
{
    private HeadphonesAPI $headphonesApi;

    public function __construct(HeadphonesAPI $api)
    {
        $this->headphonesApi = $api;
    }

    public function playAudio(Song $song): void
    {
        $payload = $song->getTitle() . " by " . $song->getArtist();
        $this->headphonesApi->playSoundViaJack($payload);
    }
}
