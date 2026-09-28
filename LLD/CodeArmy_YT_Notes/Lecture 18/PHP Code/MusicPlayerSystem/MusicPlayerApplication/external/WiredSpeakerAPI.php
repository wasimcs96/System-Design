<?php

namespace MusicPlayerApplication\external;

class WiredSpeakerAPI
{
    public function playSoundViaCable(string $data): void
    {
        echo "[WiredSpeaker] Playing: " . $data . PHP_EOL;
        // mimics playing music
    }
}
