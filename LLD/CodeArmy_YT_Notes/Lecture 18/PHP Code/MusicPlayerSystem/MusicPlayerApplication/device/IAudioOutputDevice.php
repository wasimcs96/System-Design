<?php

namespace MusicPlayerApplication\device;

use MusicPlayerApplication\models\Song;

interface IAudioOutputDevice
{
    public function playAudio(Song $song): void;
}
