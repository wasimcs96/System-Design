<?php

namespace MusicPlayerApplication\device;

require_once __DIR__ . '/IAudioOutputDevice.php';
require_once __DIR__ . '/../models/Song.php';
require_once __DIR__ . '/../external/WiredSpeakerAPI.php';

use MusicPlayerApplication\external\WiredSpeakerAPI;
use MusicPlayerApplication\models\Song;

class WiredSpeakerAdapter implements IAudioOutputDevice
{
    private WiredSpeakerAPI $wiredApi;

    public function __construct(WiredSpeakerAPI $api)
    {
        $this->wiredApi = $api;
    }

    public function playAudio(Song $song): void
    {
        $payload = $song->getTitle() . " by " . $song->getArtist();
        $this->wiredApi->playSoundViaCable($payload);
    }
}
